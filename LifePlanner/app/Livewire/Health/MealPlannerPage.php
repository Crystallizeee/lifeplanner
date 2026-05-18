<?php

namespace App\Livewire\Health;

use App\Models\MealPlanner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-10 Meal Planner
#[Layout('components.layouts.app')]
#[Title('Meal Planner — LifePlanner SIM')]
class MealPlannerPage extends Component
{
    public int $weekOffset = 0;
    public bool $showForm = false;

    #[Validate('required|date')]
    public string $date = '';

    #[Validate('required|in:breakfast,lunch,dinner,snack')]
    public string $meal_type = 'breakfast';

    #[Validate('required|min:2|max:200')]
    public string $meal_name = '';

    #[Validate('nullable|numeric|min:0')]
    public ?string $calories = null;

    #[Validate('nullable|string|max:500')]
    public ?string $recipe_notes = null;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function previousWeek(): void { $this->weekOffset--; }
    public function nextWeek(): void { $this->weekOffset = min(0, $this->weekOffset + 1); }

    public function addMeal(): void
    {
        $this->validate();

        MealPlanner::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'date' => $this->date,
                'meal_type' => $this->meal_type,
            ],
            [
                'meal_name' => $this->meal_name,
                'calories' => $this->calories,
                'recipe_notes' => $this->recipe_notes,
            ]
        );

        $this->reset(['meal_name', 'calories', 'recipe_notes']);
        $this->showForm = false;
        $this->dispatch('toast', message: 'Menu berhasil disimpan! 🍽️', type: 'success');
    }

    public function deleteMeal(int $id): void
    {
        MealPlanner::where('user_id', Auth::id())->where('id', $id)->delete();
        $this->dispatch('toast', message: 'Menu dihapus.', type: 'warning');
    }

    public function render()
    {
        $userId = Auth::id();
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks($this->weekOffset);
        $weekDates = collect();
        for ($i = 0; $i < 7; $i++) {
            $weekDates->push($startOfWeek->copy()->addDays($i));
        }

        $meals = MealPlanner::where('user_id', $userId)
            ->whereBetween('date', [$weekDates->first()->format('Y-m-d'), $weekDates->last()->format('Y-m-d')])
            ->get()
            ->groupBy(fn($m) => $m->date->format('Y-m-d'));

        $mealTypes = [
            'breakfast' => ['label' => '🌅 Sarapan', 'color' => 'var(--color-gold-bg)'],
            'lunch' => ['label' => '☀️ Makan Siang', 'color' => 'var(--color-forest-bg)'],
            'dinner' => ['label' => '🌙 Makan Malam', 'color' => 'var(--color-violet-bg)'],
            'snack' => ['label' => '🍪 Camilan', 'color' => 'var(--color-blush-bg)'],
        ];

        $totalCaloriesToday = MealPlanner::where('user_id', $userId)
            ->whereDate('date', today())
            ->sum('calories');

        return view('livewire.health.meal-planner', [
            'weekDates' => $weekDates,
            'meals' => $meals,
            'mealTypes' => $mealTypes,
            'startOfWeek' => $startOfWeek,
            'totalCaloriesToday' => $totalCaloriesToday,
        ]);
    }
}
