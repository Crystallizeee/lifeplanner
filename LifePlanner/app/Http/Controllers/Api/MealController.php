<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MealPlanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'week_start' => 'nullable|date',
        ]);

        $dateInput = $request->input('week_start') ? Carbon::parse($request->input('week_start')) : Carbon::now();
        $startOfWeek = $dateInput->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->addDays(6);

        $meals = MealPlanner::where('user_id', $request->user()->id)
            ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->get()
            ->map(fn($m) => $this->format($m));

        return response()->json([
            'success' => true,
            'data' => $meals,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'meal_time' => 'required|in:breakfast,lunch,dinner,snack',
            'meal_name' => 'required|string|max:255',
            'calories' => 'nullable|integer|min:0',
            'recipe_notes' => 'nullable|string|max:1000',
        ]);

        $meal = MealPlanner::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'date' => $request->date,
                'meal_time' => $request->meal_time,
            ],
            [
                'meal_name' => $request->meal_name,
                'calories' => $request->calories,
                'recipe_notes' => $request->recipe_notes,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $this->format($meal),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $meal = MealPlanner::where('user_id', $request->user()->id)->find($id);

        if (!$meal) {
            return response()->json(['success' => false, 'message' => 'Meal not found'], 404);
        }

        $meal->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function copyWeek(Request $request): JsonResponse
    {
        $request->validate([
            'target_week_start' => 'required|date',
        ]);

        $targetWeekStart = Carbon::parse($request->target_week_start)->startOfWeek(Carbon::MONDAY);
        $sourceWeekStart = $targetWeekStart->copy()->subWeek();
        $sourceWeekEnd = $sourceWeekStart->copy()->addDays(6);

        $sourceMeals = MealPlanner::where('user_id', $request->user()->id)
            ->whereBetween('date', [$sourceWeekStart->format('Y-m-d'), $sourceWeekEnd->format('Y-m-d')])
            ->get();

        $copiedCount = 0;
        foreach ($sourceMeals as $sourceMeal) {
            $dayOffset = Carbon::parse($sourceMeal->date)->dayOfWeekIso - 1; // 0 for Monday, 6 for Sunday
            $targetDate = $targetWeekStart->copy()->addDays($dayOffset);

            MealPlanner::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'date' => $targetDate->format('Y-m-d'),
                    'meal_time' => $sourceMeal->meal_time,
                ],
                [
                    'meal_name' => $sourceMeal->meal_name,
                    'calories' => $sourceMeal->calories,
                    'recipe_notes' => $sourceMeal->recipe_notes,
                ]
            );
            $copiedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully copied {$copiedCount} meals from the previous week",
        ]);
    }

    private function format(MealPlanner $m): array
    {
        return [
            'id' => $m->id,
            'date' => $m->date?->format('Y-m-d'),
            'meal_time' => $m->meal_time,
            'meal_name' => $m->meal_name,
            'calories' => $m->calories,
            'recipe_notes' => $m->recipe_notes,
        ];
    }
}
