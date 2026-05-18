<?php

namespace App\Livewire\Productivity;

use App\Models\Goal;
use App\Models\GoalStep;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-07 Goal Tracker
#[Layout('components.layouts.app')]
#[Title('Goal Tracker — LifePlanner SIM')]
class GoalTracker extends Component
{
    public bool $showForm = false;
    public ?int $expandedGoalId = null;

    // Goal form
    #[Validate('required|min:3|max:200')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $why = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $challenges = null;

    #[Validate('nullable|string|max:200')]
    public ?string $reward = null;

    #[Validate('nullable|date')]
    public ?string $due_date = null;

    // Step form
    public string $new_step = '';

    public function addGoal(): void
    {
        $this->validate();

        Goal::create([
            'user_id' => Auth::id(),
            'title' => $this->title,
            'why' => $this->why,
            'challenges' => $this->challenges,
            'reward' => $this->reward,
            'status' => 'active',
            'progress_pct' => 0,
            'due_date' => $this->due_date,
        ]);

        $this->reset(['title', 'why', 'challenges', 'reward', 'due_date']);
        $this->showForm = false;
        $this->dispatch('toast', message: 'Goal baru ditambahkan! 🏆', type: 'success');
    }

    public function toggleExpand(int $goalId): void
    {
        $this->expandedGoalId = $this->expandedGoalId === $goalId ? null : $goalId;
        $this->new_step = '';
    }

    public function addStep(int $goalId): void
    {
        if (empty(trim($this->new_step))) return;

        $goal = Goal::where('user_id', Auth::id())->find($goalId);
        if (!$goal) return;

        $maxSort = $goal->steps()->max('sort_order') ?? 0;

        GoalStep::create([
            'goal_id' => $goalId,
            'step_name' => $this->new_step,
            'is_completed' => false,
            'sort_order' => $maxSort + 1,
        ]);

        $goal->recalculateProgress();
        $this->new_step = '';
    }

    public function toggleStep(int $stepId): void
    {
        $step = GoalStep::find($stepId);
        if (!$step) return;

        $goal = Goal::where('user_id', Auth::id())->find($step->goal_id);
        if (!$goal) return;

        $step->update(['is_completed' => !$step->is_completed]);
        $goal->recalculateProgress();

        // Auto-complete goal if all steps done
        if ($goal->fresh()->progress_pct >= 100 && $goal->status === 'active') {
            $goal->update(['status' => 'completed']);
            $this->dispatch('toast', message: '🎉 Goal tercapai! Selamat!', type: 'success');
        }
    }

    public function deleteStep(int $stepId): void
    {
        $step = GoalStep::find($stepId);
        if (!$step) return;

        $goal = Goal::where('user_id', Auth::id())->find($step->goal_id);
        if (!$goal) return;

        $step->delete();
        $goal->recalculateProgress();
    }

    public function archiveGoal(int $goalId): void
    {
        Goal::where('user_id', Auth::id())->where('id', $goalId)
            ->update(['status' => 'archived']);
        $this->dispatch('toast', message: 'Goal diarsipkan.', type: 'warning');
    }

    public function deleteGoal(int $goalId): void
    {
        Goal::where('user_id', Auth::id())->where('id', $goalId)->delete();
        $this->dispatch('toast', message: 'Goal dihapus.', type: 'warning');
    }

    public function render()
    {
        $goals = Goal::where('user_id', Auth::id())
            ->with(['steps' => fn($q) => $q->orderBy('sort_order')])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'completed' THEN 1 WHEN 'archived' THEN 2 END")
            ->orderByDesc('created_at')
            ->get();

        $activeCount = $goals->where('status', 'active')->count();
        $completedCount = $goals->where('status', 'completed')->count();
        $avgProgress = $goals->where('status', 'active')->avg('progress_pct') ?? 0;

        return view('livewire.productivity.goal-tracker', [
            'goals' => $goals,
            'activeCount' => $activeCount,
            'completedCount' => $completedCount,
            'avgProgress' => round($avgProgress),
        ]);
    }
}
