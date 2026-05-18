<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\SavingsGoal;
use App\Models\TodoList;
use App\Models\Transaction;
use App\Models\WeightLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-01 Dashboard Overview
#[Layout('components.layouts.app')]
#[Title('Dashboard — LifePlanner SIM')]
class Dashboard extends Component
{
    public function render()
    {
        $userId = Auth::id();
        $activeBudget = Budget::where('user_id', $userId)->where('is_active', true)->first();

        // ── Finance Summary ──
        $totalIncome = 0;
        $totalExpense = 0;
        $totalBills = 0;
        $overdueBills = 0;

        if ($activeBudget) {
            $totalIncome = Transaction::where('budget_id', $activeBudget->id)
                ->where('type', 'income')
                ->sum('amount');

            $totalExpense = Transaction::where('budget_id', $activeBudget->id)
                ->where('type', 'expense')
                ->sum('amount');

            $totalBills = Transaction::where('budget_id', $activeBudget->id)
                ->where('type', 'bill')
                ->where('status', '!=', 'paid')
                ->sum('amount');

            $overdueBills = Transaction::where('budget_id', $activeBudget->id)
                ->where('type', 'bill')
                ->where('status', 'overdue')
                ->count();
        }

        // ── Productivity Summary ──
        $todaysTasks = TodoList::where('user_id', $userId)
            ->whereNotIn('status', ['done', 'canceled'])
            ->count();

        $completedTasks = TodoList::where('user_id', $userId)
            ->where('status', 'done')
            ->count();

        // ── Active Goals ──
        $activeGoals = Goal::where('user_id', $userId)
            ->where('status', 'active')
            ->with(['steps' => fn($q) => $q->orderBy('sort_order')])
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        // ── Habit Summary ──
        $activeHabits = Habit::where('user_id', $userId)
            ->where('is_archived', false)
            ->count();

        $topStreak = Habit::where('user_id', $userId)
            ->where('is_archived', false)
            ->max('current_streak') ?? 0;

        // Today's habit check count
        $habitsCheckedToday = HabitLog::whereHas('habit', fn($q) => $q->where('user_id', $userId)->where('is_archived', false))
            ->whereDate('date', today())
            ->where('is_checked', true)
            ->count();

        // ── Health Summary ──
        $latestWeight = WeightLog::where('user_id', $userId)
            ->orderByDesc('date')
            ->first();

        $savingsProgress = SavingsGoal::where('user_id', $userId)
            ->where('is_achieved', false)
            ->get();

        $totalSavingsTarget = $savingsProgress->sum('target_amount');
        $totalSavingsSaved = $savingsProgress->sum('current_saved');

        // ── Recent Activity (last 5 transactions) ──
        $recentActivity = Transaction::where('user_id', $userId)
            ->with('category')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // ── Spending by category (top 5 for mini chart) ──
        $spendingByCategory = [];
        if ($activeBudget) {
            $spendingByCategory = Transaction::where('budget_id', $activeBudget->id)
                ->where('type', 'expense')
                ->selectRaw('category_id, SUM(amount) as total')
                ->groupBy('category_id')
                ->with('category')
                ->orderByDesc('total')
                ->take(5)
                ->get()
                ->map(fn($t) => [
                    'name' => $t->category->name ?? 'Lainnya',
                    'icon' => $t->category->icon ?? '📦',
                    'total' => (float) $t->total,
                ])
                ->toArray();
        }

        return view('livewire.dashboard', [
            'activeBudget' => $activeBudget,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalBills' => $totalBills,
            'overdueBills' => $overdueBills,
            'todaysTasks' => $todaysTasks,
            'completedTasks' => $completedTasks,
            'activeGoals' => $activeGoals,
            'activeHabits' => $activeHabits,
            'topStreak' => $topStreak,
            'habitsCheckedToday' => $habitsCheckedToday,
            'latestWeight' => $latestWeight,
            'totalSavingsTarget' => $totalSavingsTarget,
            'totalSavingsSaved' => $totalSavingsSaved,
            'recentActivity' => $recentActivity,
            'spendingByCategory' => $spendingByCategory,
        ]);
    }
}
