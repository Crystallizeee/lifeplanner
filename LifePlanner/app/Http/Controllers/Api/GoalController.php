<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $goals = Goal::where('user_id', $request->user()->id)
            ->with(['steps' => fn($q) => $q->orderBy('sort_order')])
            ->orderByDesc('created_at')->get()->map(fn($g) => $this->format($g));
        return response()->json(['success' => true, 'data' => $goals]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'deadline' => 'nullable|date',
            'notes'    => 'nullable|string|max:1000',
        ]);
        $goal = Goal::create([
            'user_id'  => $request->user()->id,
            'title'    => $request->title,
            'deadline' => $request->deadline,
            'notes'    => $request->notes,
            'status'   => 'active',
        ]);
        return response()->json(['success' => true, 'data' => $this->format($goal)], 201);
    }

    public function show(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $goal->load(['steps' => fn($q) => $q->orderBy('sort_order')]);
        return response()->json(['success' => true, 'data' => $this->format($goal)]);
    }

    public function update(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $goal->update($request->only(['title','deadline','notes','status']));
        return response()->json(['success' => true, 'data' => $this->format($goal->fresh(['steps']))]);
    }

    public function destroy(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $goal->delete();
        return response()->json(['success' => true]);
    }

    private function format(Goal $g): array
    {
        $steps = $g->steps ?? collect();
        return [
            'id'          => $g->id,
            'title'       => $g->title,
            'status'      => $g->status,
            'deadline'    => $g->deadline?->format('Y-m-d'),
            'notes'       => $g->notes,
            'progress'    => $steps->count() ? round($steps->where('is_done', true)->count() / $steps->count() * 100) : 0,
            'steps_total' => $steps->count(),
            'steps_done'  => $steps->where('is_done', true)->count(),
            'steps'       => $steps->map(fn($s) => ['id' => $s->id, 'title' => $s->title, 'is_done' => (bool) $s->is_done]),
        ];
    }
}
