<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroceryList;
use App\Models\MealPlanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GroceryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'week_start' => 'nullable|date',
        ]);

        $dateInput = $request->input('week_start') ? Carbon::parse($request->input('week_start')) : Carbon::now();
        $weekStart = $dateInput->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $items = GroceryList::where('user_id', $request->user()->id)
            ->whereDate('week_start', $weekStart)
            ->orderBy('is_bought')
            ->orderBy('category_id')
            ->orderBy('item_name')
            ->get()
            ->map(fn($item) => $this->format($item));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'week_start' => 'required|date',
            'item_name' => 'required|string|max:150',
            'qty' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:30',
            'category' => 'nullable|string|max:50',
            'is_checked' => 'nullable|boolean',
        ]);

        $weekStart = Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $item = GroceryList::create([
            'user_id' => $request->user()->id,
            'week_start' => $weekStart,
            'item_name' => $request->item_name,
            'qty' => $request->qty,
            'unit' => $request->unit,
            'category' => $request->category ?: 'Umum',
            'is_checked' => $request->is_checked ?? false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->format($item),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = GroceryList::where('user_id', $request->user()->id)->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $data = [];
        if ($request->has('item_name')) $data['item_name'] = $request->item_name;
        if ($request->has('qty')) $data['qty'] = $request->qty;
        if ($request->has('unit')) $data['unit'] = $request->unit;
        if ($request->has('category')) $data['category'] = $request->category;
        if ($request->has('is_checked')) $data['is_checked'] = $request->is_checked;

        $item->update($data);

        return response()->json([
            'success' => true,
            'data' => $this->format($item->fresh()),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = GroceryList::where('user_id', $request->user()->id)->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'week_start' => 'required|date',
        ]);

        $weekStart = Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        GroceryList::where('user_id', $request->user()->id)
            ->whereDate('week_start', $weekStart)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grocery list reset successfully',
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'week_start' => 'required|date',
        ]);

        $weekStart = Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY);
        $weekStartStr = $weekStart->format('Y-m-d');
        $weekEndStr = $weekStart->copy()->addDays(6)->format('Y-m-d');

        // Fetch all meals for this week
        $meals = MealPlanner::where('user_id', $request->user()->id)
            ->whereBetween('date', [$weekStartStr, $weekEndStr])
            ->get();

        $generatedCount = 0;
        foreach ($meals as $meal) {
            $mealName = $meal->meal_name;
            if (empty($mealName)) {
                continue;
            }

            // Check if item already exists in this week's grocery list
            $exists = GroceryList::where('user_id', $request->user()->id)
                ->whereDate('week_start', $weekStartStr)
                ->where('item_name', $mealName)
                ->exists();

            if (!$exists) {
                GroceryList::create([
                    'user_id' => $request->user()->id,
                    'week_start' => $weekStartStr,
                    'item_name' => $mealName,
                    'qty' => 1,
                    'unit' => 'porsi',
                    'category' => 'Bahan Masak',
                    'is_checked' => false,
                ]);
                $generatedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully generated {$generatedCount} items from the meal plan",
        ]);
    }

    private function format(GroceryList $item): array
    {
        return [
            'id' => $item->id,
            'week_start' => $item->week_start?->format('Y-m-d'),
            'item_name' => $item->item_name,
            'qty' => $item->qty !== null ? (float)$item->qty : null,
            'unit' => $item->unit,
            'category' => $item->category,
            'is_checked' => $item->is_checked,
        ];
    }
}
