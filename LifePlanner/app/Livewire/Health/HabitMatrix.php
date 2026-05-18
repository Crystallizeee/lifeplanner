<?php

namespace App\Livewire\Health;

use App\Models\Habit;
use App\Models\HabitLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-08 Habit Matrix
#[Layout('components.layouts.app')]
#[Title('Habit Matrix — LifePlanner SIM')]
class HabitMatrix extends Component
{
    public bool $showForm = false;

    #[Validate('required|min:2|max:100')]
    public string $habit_name = '';

    #[Validate('nullable|string|max:10')]
    public ?string $emoji = '🔥';

    // Matrix navigation
    public int $weekOffset = 0;

    public function previousWeek(): void { $this->weekOffset--; }
    public function nextWeek(): void { $this->weekOffset = min(0, $this->weekOffset + 1); }

    public function addHabit(): void
    {
        $this->validate();

        Habit::create([
            'user_id' => Auth::id(),
            'habit_name' => $this->habit_name,
            'emoji' => $this->emoji ?: '🔥',
            'is_archived' => false,
            'current_streak' => 0,
            'longest_streak' => 0,
        ]);

        $this->reset(['habit_name', 'emoji']);
        $this->emoji = '🔥';
        $this->showForm = false;
        $this->dispatch('toast', message: 'Habit baru ditambahkan! 🔥', type: 'success');
    }

    public function toggleDay(int $habitId, string $date): void
    {
        $habit = Habit::where('user_id', Auth::id())->find($habitId);
        if (!$habit) return;

        $log = HabitLog::where('habit_id', $habitId)->where('date', $date)->first();

        if ($log) {
            $log->update(['is_checked' => !$log->is_checked]);
        } else {
            HabitLog::create([
                'habit_id' => $habitId,
                'date' => $date,
                'is_checked' => true,
            ]);
        }

        // Recalculate streak
        $this->recalculateStreak($habit);
    }

    public function archiveHabit(int $id): void
    {
        Habit::where('user_id', Auth::id())->where('id', $id)
            ->update(['is_archived' => true]);
        $this->dispatch('toast', message: 'Habit diarsipkan.', type: 'warning');
    }

    public function deleteHabit(int $id): void
    {
        Habit::where('user_id', Auth::id())->where('id', $id)->delete();
        $this->dispatch('toast', message: 'Habit dihapus.', type: 'warning');
    }

    private function recalculateStreak(Habit $habit): void
    {
        $streak = 0;
        $date = Carbon::today();

        // Check if today is completed, if not start from yesterday
        $todayLog = HabitLog::where('habit_id', $habit->id)
            ->where('date', $date->format('Y-m-d'))
            ->where('is_checked', true)
            ->exists();

        if (!$todayLog) {
            $date = $date->subDay();
        }

        // Count consecutive days backwards
        while (true) {
            $log = HabitLog::where('habit_id', $habit->id)
                ->where('date', $date->format('Y-m-d'))
                ->where('is_checked', true)
                ->exists();

            if ($log) {
                $streak++;
                $date->subDay();
            } else {
                break;
            }
        }

        $habit->update([
            'current_streak' => $streak,
            'longest_streak' => max($habit->longest_streak, $streak),
        ]);
    }

    public function render()
    {
        $userId = Auth::id();

        $habits = Habit::where('user_id', $userId)
            ->where('is_archived', false)
            ->orderBy('created_at')
            ->get();

        // Generate week dates
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks($this->weekOffset);
        $weekDates = collect();
        for ($i = 0; $i < 7; $i++) {
            $weekDates->push($startOfWeek->copy()->addDays($i));
        }

        // Get logs for all habits in this week
        $dateRange = [$weekDates->first()->format('Y-m-d'), $weekDates->last()->format('Y-m-d')];
        $logs = HabitLog::whereIn('habit_id', $habits->pluck('id'))
            ->whereBetween('date', $dateRange)
            ->where('is_checked', true)
            ->get()
            ->groupBy('habit_id')
            ->map(fn($group) => $group->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray());

        $totalActiveHabits = $habits->count();
        $topStreak = $habits->max('current_streak') ?? 0;

        return view('livewire.health.habit-matrix', [
            'habits' => $habits,
            'weekDates' => $weekDates,
            'logs' => $logs,
            'totalActiveHabits' => $totalActiveHabits,
            'topStreak' => $topStreak,
            'startOfWeek' => $startOfWeek,
        ]);
    }
}
