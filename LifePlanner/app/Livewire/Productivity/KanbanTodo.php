<?php

namespace App\Livewire\Productivity;

use App\Models\Category;
use App\Models\TodoList;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-06 Kanban To-Do
#[Layout('components.layouts.app')]
#[Title('Kanban To-Do — LifePlanner SIM')]
class KanbanTodo extends Component
{
    public bool $showForm = false;

    #[Validate('required|min:3|max:255')]
    public string $task_name = '';

    #[Validate('required|in:very_high,high,medium,low')]
    public string $priority = 'medium';

    #[Validate('nullable|exists:categories,id')]
    public ?string $category_id = null;

    #[Validate('nullable|date')]
    public ?string $due_date = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $notes = null;

    // Filter
    public string $filterPriority = 'all';

    // Inline edit
    public ?int $editingTaskId = null;
    public string $editTaskName = '';
    public string $editPriority = 'medium';

    // Quick add per column
    public string $quickAddTask = '';

    public function addTask(): void
    {
        $this->validate();

        TodoList::create([
            'user_id' => Auth::id(),
            'task_name' => $this->task_name,
            'status' => 'todo',
            'priority' => $this->priority,
            'category_id' => $this->category_id ?: null,
            'due_date' => $this->due_date,
            'notes' => $this->notes,
        ]);

        $this->reset(['task_name', 'priority', 'category_id', 'due_date', 'notes']);
        $this->priority = 'medium';
        $this->showForm = false;
        $this->dispatch('toast', message: 'Task berhasil ditambahkan! ✅', type: 'success');
    }

    public function quickAdd(string $status): void
    {
        if (empty(trim($this->quickAddTask))) return;

        TodoList::create([
            'user_id' => Auth::id(),
            'task_name' => $this->quickAddTask,
            'status' => $status,
            'priority' => 'medium',
        ]);

        $this->quickAddTask = '';
        $this->dispatch('toast', message: 'Task ditambahkan! ✅', type: 'success');
    }

    public function startEditing(int $id): void
    {
        $task = TodoList::where('user_id', Auth::id())->find($id);
        if ($task) {
            $this->editingTaskId = $id;
            $this->editTaskName = $task->task_name;
            $this->editPriority = $task->priority;
        }
    }

    public function saveEdit(): void
    {
        if (!$this->editingTaskId) return;
        if (empty(trim($this->editTaskName))) return;

        TodoList::where('user_id', Auth::id())
            ->where('id', $this->editingTaskId)
            ->update([
                'task_name' => $this->editTaskName,
                'priority' => $this->editPriority,
            ]);

        $this->editingTaskId = null;
        $this->editTaskName = '';
        $this->dispatch('toast', message: 'Task diperbarui! ✅', type: 'success');
    }

    public function cancelEdit(): void
    {
        $this->editingTaskId = null;
        $this->editTaskName = '';
    }

    public function moveTask(int $id, string $newStatus): void
    {
        $task = TodoList::where('user_id', Auth::id())->find($id);
        if ($task) {
            $task->update(['status' => $newStatus]);
        }
    }

    public function deleteTask(int $id): void
    {
        TodoList::where('user_id', Auth::id())->where('id', $id)->delete();
        $this->dispatch('toast', message: 'Task dihapus.', type: 'warning');
    }

    public function render()
    {
        $userId = Auth::id();

        $query = TodoList::where('user_id', $userId)->with('category');

        if ($this->filterPriority !== 'all') {
            $query->where('priority', $this->filterPriority);
        }

        $tasks = $query->orderByRaw("
            CASE priority
                WHEN 'very_high' THEN 0
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END
        ")->orderBy('due_date')->get();

        // Group by status for Kanban columns
        $columns = [
            'todo' => ['label' => '📝 To Do', 'color' => 'var(--color-ink-3)', 'tasks' => $tasks->where('status', 'todo')],
            'in_progress' => ['label' => '🔄 In Progress', 'color' => 'var(--color-violet)', 'tasks' => $tasks->where('status', 'in_progress')],
            'hold' => ['label' => '⏸️ On Hold', 'color' => 'var(--color-warning)', 'tasks' => $tasks->where('status', 'hold')],
            'done' => ['label' => '✅ Done', 'color' => 'var(--color-forest)', 'tasks' => $tasks->where('status', 'done')],
            'canceled' => ['label' => '❌ Canceled', 'color' => 'var(--color-ink-4)', 'tasks' => $tasks->where('status', 'canceled')],
        ];

        $taskCategories = Category::where('user_id', $userId)
            ->where('type', 'task')
            ->orderBy('name')
            ->get();

        // Stats
        $todayDue = TodoList::where('user_id', $userId)
            ->whereDate('due_date', today())
            ->whereNotIn('status', ['done', 'canceled'])
            ->count();

        $totalActive = $tasks->whereNotIn('status', ['done', 'canceled'])->count();
        $totalDone = $tasks->where('status', 'done')->count();

        return view('livewire.productivity.kanban-todo', [
            'columns' => $columns,
            'taskCategories' => $taskCategories,
            'todayDue' => $todayDue,
            'totalActive' => $totalActive,
            'totalDone' => $totalDone,
        ]);
    }
}
