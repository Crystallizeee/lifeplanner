<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TodoList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $todos = TodoList::where('user_id', $request->user()->id)
            ->orderBy('priority')->orderByDesc('created_at')
            ->get()->map(fn($t) => $this->format($t));
        return response()->json(['success' => true, 'data' => $todos]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'priority'    => 'nullable|in:low,medium,high',
            'due_date'    => 'nullable|date',
        ]);
        $todo = TodoList::create([
            'user_id'   => $request->user()->id,
            'task_name' => $request->title,
            'notes'     => $request->description,
            'priority'  => $request->priority ?? 'medium',
            'due_date'  => $request->due_date,
            'status'    => 'todo',
        ]);
        return response()->json(['success' => true, 'data' => $this->format($todo)], 201);
    }

    public function show(Request $request, TodoList $todo): JsonResponse
    {
        if ($todo->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        return response()->json(['success' => true, 'data' => $this->format($todo)]);
    }

    public function update(Request $request, TodoList $todo): JsonResponse
    {
        if ($todo->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        
        $data = [];
        if ($request->has('title')) $data['task_name'] = $request->title;
        if ($request->has('description')) $data['notes'] = $request->description;
        if ($request->has('priority')) $data['priority'] = $request->priority;
        if ($request->has('due_date')) $data['due_date'] = $request->due_date;
        if ($request->has('status')) $data['status'] = $request->status;

        $todo->update($data);
        return response()->json(['success' => true, 'data' => $this->format($todo->fresh())]);
    }

    public function toggle(Request $request, TodoList $todo): JsonResponse
    {
        if ($todo->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $todo->update(['status' => $todo->status === 'done' ? 'todo' : 'done']);
        return response()->json(['success' => true, 'data' => $this->format($todo->fresh())]);
    }

    public function destroy(Request $request, TodoList $todo): JsonResponse
    {
        if ($todo->user_id !== $request->user()->id) return response()->json(['success' => false], 404);
        $todo->delete();
        return response()->json(['success' => true]);
    }

    private function format(TodoList $t): array
    {
        return [
            'id'          => $t->id,
            'title'       => $t->task_name,
            'description' => $t->notes,
            'priority'    => $t->priority,
            'status'      => $t->status,
            'due_date'    => $t->due_date?->format('Y-m-d'),
        ];
    }
}
