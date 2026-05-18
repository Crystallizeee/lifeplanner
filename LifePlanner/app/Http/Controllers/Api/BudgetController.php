<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $budgets = Budget::where('user_id', $request->user()->id)
            ->orderByDesc('period_start')
            ->get()
            ->map(fn($b) => $this->format($b));
        
        return response()->json(['success' => true, 'data' => $budgets]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'period_start'     => 'required|date',
            'period_end'       => 'required|date|after_or_equal:period_start',
            'starting_balance' => 'nullable|numeric|min:0',
        ]);

        // Non-aktifkan budget lain jika yang ini aktif
        Budget::where('user_id', $request->user()->id)->update(['is_active' => false]);

        $budget = Budget::create([
            'user_id'          => $request->user()->id,
            'period_start'     => $request->period_start,
            'period_end'       => $request->period_end,
            'starting_balance' => $request->starting_balance ?? 0,
            'is_active'        => true,
        ]);

        return response()->json(['success' => true, 'data' => $this->format($budget)], 201);
    }

    public function show(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        return response()->json(['success' => true, 'data' => $this->format($budget)]);
    }

    public function update(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        
        $request->validate([
            'period_start'     => 'sometimes|date',
            'period_end'       => 'sometimes|date|after_or_equal:period_start',
            'starting_balance' => 'sometimes|numeric|min:0',
            'is_active'        => 'sometimes|boolean',
        ]);

        if ($request->is_active) {
            Budget::where('user_id', $request->user()->id)->update(['is_active' => false]);
        }

        $budget->update($request->only(['period_start', 'period_end', 'starting_balance', 'is_active']));
        return response()->json(['success' => true, 'data' => $this->format($budget->fresh())]);
    }

    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $budget->delete();
        return response()->json(['success' => true]);
    }

    private function format(Budget $b): array
    {
        return [
            'id'               => $b->id,
            'period_start'     => $b->period_start->format('Y-m-d'),
            'period_end'       => $b->period_end->format('Y-m-d'),
            'starting_balance' => (float) $b->starting_balance,
            'is_active'        => (bool) $b->is_active,
        ];
    }
}
