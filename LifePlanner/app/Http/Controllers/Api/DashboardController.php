<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\SavingsGoal;
use App\Models\TodoList;
use App\Models\Transaction;
use App\Models\WeightLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId       = $request->user()->id;
        $activeBudget = Budget::where('user_id', $userId)->where('is_active', true)->first();

        // Finance
        $totalIncome  = 0;
        $totalExpense = 0;
        $totalBills   = 0;
        $overdueBills = 0;

        if ($activeBudget) {
            $totalIncome = Transaction::where('budget_id', $activeBudget->id)->where('type', 'income')->sum('amount');
            $totalExpense = Transaction::where('budget_id', $activeBudget->id)->where('type', 'expense')->sum('amount');
            $totalBills   = Transaction::where('budget_id', $activeBudget->id)->where('type', 'bill')->where('status', '!=', 'paid')->sum('amount');
            $overdueBills = Transaction::where('budget_id', $activeBudget->id)->where('type', 'bill')->where('status', 'overdue')->count();
        }

        // Productivity
        $todaysTasks    = TodoList::where('user_id', $userId)->whereNotIn('status', ['done', 'canceled'])->count();
        $completedTasks = TodoList::where('user_id', $userId)->where('status', 'done')->count();

        // Active Goals
        $activeGoals = Goal::where('user_id', $userId)
            ->where('status', 'active')
            ->with(['steps' => fn($q) => $q->orderBy('sort_order')])
            ->orderByDesc('created_at')
            ->take(3)
            ->get()
            ->map(fn($g) => [
                'id'       => $g->id,
                'title'    => $g->title,
                'deadline' => $g->deadline?->format('d M Y'),
                'progress' => $g->steps->count()
                    ? round($g->steps->where('is_done', true)->count() / $g->steps->count() * 100)
                    : 0,
            ]);

        // Habits
        $activeHabits      = Habit::where('user_id', $userId)->where('is_archived', false)->count();
        $topStreak         = Habit::where('user_id', $userId)->where('is_archived', false)->max('current_streak') ?? 0;
        $habitsCheckedToday = HabitLog::whereHas('habit', fn($q) => $q->where('user_id', $userId)->where('is_archived', false))
            ->whereDate('date', today())
            ->where('is_checked', true)
            ->count();

        // Health
        $latestWeight = WeightLog::where('user_id', $userId)->orderByDesc('date')->first();

        // Savings
        $savingsProgress    = SavingsGoal::where('user_id', $userId)->where('is_achieved', false)->get();
        $totalSavingsTarget = $savingsProgress->sum('target_amount');
        $totalSavingsSaved  = $savingsProgress->sum('current_saved');

        // Recent Activity
        $recentActivity = Transaction::where('user_id', $userId)
            ->with('category')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn($t) => [
                'id'               => $t->id,
                'description'      => $t->description,
                'amount'           => (float) $t->amount,
                'type'             => $t->type,
                'status'           => $t->status,
                'category_name'    => $t->category->name ?? '-',
                'category_icon'    => $t->category->icon ?? '📦',
                'transaction_date' => $t->transaction_date?->format('d M Y'),
            ]);

        // Spending by category
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
                    'name'  => $t->category->name ?? 'Lainnya',
                    'icon'  => $t->category->icon ?? '📦',
                    'total' => (float) $t->total,
                ]);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'finance' => [
                    'total_income'   => (float) $totalIncome,
                    'total_expense'  => (float) $totalExpense,
                    'total_bills'    => (float) $totalBills,
                    'overdue_bills'  => $overdueBills,
                    'net_balance'    => (float) ($totalIncome - $totalExpense),
                    'budget_period'  => $activeBudget
                        ? $activeBudget->period_start->format('M Y')
                        : null,
                ],
                'productivity' => [
                    'todays_tasks'    => $todaysTasks,
                    'completed_tasks' => $completedTasks,
                    'active_goals'    => $activeGoals,
                ],
                'habits' => [
                    'active_habits'       => $activeHabits,
                    'top_streak'          => $topStreak,
                    'habits_checked_today' => $habitsCheckedToday,
                ],
                'health' => [
                    'latest_weight' => $latestWeight
                        ? ['value' => (float) $latestWeight->weight_kg, 'date' => $latestWeight->date->format('d M Y')]
                        : null,
                ],
                'savings' => [
                    'total_target' => (float) $totalSavingsTarget,
                    'total_saved'  => (float) $totalSavingsSaved,
                    'progress_pct' => $totalSavingsTarget > 0
                        ? round($totalSavingsSaved / $totalSavingsTarget * 100, 1)
                        : 0,
                ],
                'recent_activity'      => $recentActivity,
                'spending_by_category' => $spendingByCategory,
            ],
        ]);
    }
}
