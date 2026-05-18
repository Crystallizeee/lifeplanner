<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavingsGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingsGoalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $goals = SavingsGoal::where('user_id', $request->user()->id)
            ->orderBy('goal_name')->get()->map(fn($g) => $this->format($g));
        return response()->json(['success' => true, 'data' => $goals]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'goal_name'     => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
            'current_saved' => 'nullable|numeric|min:0',
            'target_date'   => 'nullable|date',
            'icon'          => 'nullable|string|max:20',
        ]);
        $saved = $request->current_saved ?? 0;
        $goal  = SavingsGoal::create([
            'user_id'       => $request->user()->id,
            'goal_name'     => $request->goal_name,
            'target_amount' => $request->target_amount,
            'current_saved' => $saved,
            'target_date'   => $request->target_date,
            'icon'          => $request->icon ?? '🎯',
            'is_achieved'   => $saved >= $request->target_amount,
        ]);
        return response()->json(['success' => true, 'data' => $this->format($goal)], 201);
    }

    public function show(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        return response()->json(['success' => true, 'data' => $this->format($savingsGoal)]);
    }

    public function update(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $savingsGoal->update($request->only(['goal_name','target_amount','current_saved','target_date','icon','notes']));
        $savingsGoal->update(['is_achieved' => $savingsGoal->current_saved >= $savingsGoal->target_amount]);
        return response()->json(['success' => true, 'data' => $this->format($savingsGoal->fresh())]);
    }

    public function destroy(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $savingsGoal->delete();
        return response()->json(['success' => true, 'message' => 'Tabungan dihapus.']);
    }

    private function format(SavingsGoal $g): array
    {
        return [
            'id'            => $g->id,
            'goal_name'     => $g->goal_name,
            'target_amount' => (float) $g->target_amount,
            'current_saved' => (float) $g->current_saved,
            'progress_pct'  => $g->target_amount > 0 ? round($g->current_saved / $g->target_amount * 100, 1) : 0,
            'target_date'   => $g->target_date?->format('Y-m-d'),
            'icon'          => $g->icon ?? '🎯',
            'is_achieved'   => (bool) $g->is_achieved,
        ];
    }
}
