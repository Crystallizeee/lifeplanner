<?php

namespace App\Livewire\Partials;

use App\Models\Habit;
use App\Models\Goal;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationCenter extends Component
{
    public bool $open = false;
    public array $notifications = [];

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function toggleNotifications(): void
    {
        $this->open = !$this->open;
        if ($this->open) {
            $this->loadNotifications();
        }
    }

    public function loadNotifications(): void
    {
        $userId = Auth::id();
        if (!$userId) return;

        $items = [];

        // 1. Unpaid & Overdue Bills
        $overdueBills = Transaction::where('user_id', $userId)
            ->where('type', 'bill')
            ->where('status', 'planned')
            ->whereDate('due_date', '<', today())
            ->get();

        foreach ($overdueBills as $bill) {
            $items[] = [
                'id' => 'bill-overdue-' . $bill->id,
                'type' => 'danger',
                'icon' => '🚨',
                'title' => 'Tagihan Terlambat!',
                'message' => "Tagihan \"{$bill->description}\" sebesar Rp " . number_format($bill->amount, 0, ',', '.') . " melewati tanggal jatuh tempo (" . $bill->due_date->format('d M') . ").",
                'url' => route('finance.bills'),
            ];
        }

        // 2. Uncompleted Habits for Today
        $todayName = strtolower(now()->format('l')); // e.g. monday
        $activeHabits = Habit::where('user_id', $userId)
            ->where('is_active', true)
            ->where($todayName, true)
            ->get();

        foreach ($activeHabits as $habit) {
            $hasLogToday = $habit->logs()->whereDate('logged_at', today())->exists();
            if (!$hasLogToday) {
                $items[] = [
                    'id' => 'habit-today-' . $habit->id,
                    'type' => 'warning',
                    'icon' => '🔥',
                    'title' => 'Habit Menunggumu',
                    'message' => "Kamu belum menyelesaikan kebiasaan \"{$habit->habit_name}\" hari ini.",
                    'url' => route('health.habits'),
                ];
            }
        }

        // 3. Goals Due Soon (within 3 days)
        $soonGoals = Goal::where('user_id', $userId)
            ->where('is_completed', false)
            ->whereNotNull('target_date')
            ->whereBetween('target_date', [today(), today()->addDays(3)])
            ->get();

        foreach ($soonGoals as $goal) {
            $items[] = [
                'id' => 'goal-due-' . $goal->id,
                'type' => 'info',
                'icon' => '🎯',
                'title' => 'Target Hampir Tiba',
                'message' => "Target \"{$goal->title}\" berakhir pada " . $goal->target_date->format('d M Y') . ".",
                'url' => route('productivity.goals'),
            ];
        }

        // 4. Achieved Savings Goals
        $achievedSavings = SavingsGoal::where('user_id', $userId)
            ->where('current_saved', '>=', \DB::raw('target_amount'))
            ->get();

        foreach ($achievedSavings as $saving) {
            $items[] = [
                'id' => 'saving-achieved-' . $saving->id,
                'type' => 'success',
                'icon' => '🎉',
                'title' => 'Tabungan Tercapai!',
                'message' => "Selamat! Target tabungan \"{$saving->goal_name}\" telah terkumpul 100%.",
                'url' => route('finance.savings'),
            ];
        }

        $this->notifications = $items;
    }

    public function render()
    {
        return view('livewire.partials.notification-center');
    }
}
