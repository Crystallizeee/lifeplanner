<?php

namespace App\Livewire\Finance;

use App\Models\SavingsGoal;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-05 Savings Goals
#[Layout('components.layouts.app')]
#[Title('Savings Goals — LifePlanner SIM')]
class SavingsGoals extends Component
{
    public bool $showForm = false;

    #[Validate('required|min:3|max:150')]
    public string $goal_name = '';

    #[Validate('required|numeric|min:1')]
    public string $target_amount = '';

    #[Validate('nullable|date')]
    public ?string $target_date = null;

    public function addGoal(): void
    {
        $this->validate();

        SavingsGoal::create([
            'user_id' => Auth::id(),
            'goal_name' => $this->goal_name,
            'target_amount' => $this->target_amount,
            'current_saved' => 0,
            'target_date' => $this->target_date,
            'is_achieved' => false,
        ]);

        $this->reset(['goal_name', 'target_amount', 'target_date']);
        $this->showForm = false;
        $this->dispatch('toast', message: 'Target tabungan baru ditambahkan! 🎯', type: 'success');
    }

    public function deleteGoal(int $id): void
    {
        SavingsGoal::where('user_id', Auth::id())->where('id', $id)->delete();
        $this->dispatch('toast', message: 'Target tabungan dihapus.', type: 'warning');
    }

    public function render()
    {
        $userId = Auth::id();
        $goals = SavingsGoal::where('user_id', $userId)
            ->orderBy('is_achieved')
            ->orderByDesc('created_at')
            ->get();

        // Calculate monthly savings rate based on active budget allocations of type 'saving'
        $activeBudget = \App\Models\Budget::where('user_id', $userId)->where('is_active', true)->first();
        $monthlySavingsRate = 0;
        if ($activeBudget) {
            $monthlySavingsRate = \App\Models\BudgetAllocation::where('budget_id', $activeBudget->id)
                ->whereHas('category', fn($q) => $q->where('type', 'saving'))
                ->sum('allocated_amount');
        }

        // Add dynamic time-to-goal projection text to each goal
        $goals->each(function($goal) use ($monthlySavingsRate) {
            if ($goal->is_achieved) {
                $goal->projection_text = '🎉 Target telah tercapai dengan sukses!';
            } else {
                $remaining = max(0, $goal->target_amount - $goal->current_saved);
                if ($monthlySavingsRate > 0) {
                    $months = ceil($remaining / $monthlySavingsRate);
                    $goal->projection_text = "📈 Diproyeksikan tercapai dalam sekitar " . $months . " bulan lagi dengan alokasi Rp " . number_format($monthlySavingsRate, 0, ',', '.') . "/bulan.";
                } else {
                    // Default fallback suggestion
                    $goal->projection_text = "💡 Atur alokasi anggaran tabungan di Budget Overview untuk melihat proyeksi penyelesaian.";
                }
            }
        });

        $totalTarget = $goals->sum('target_amount');
        $totalSaved = $goals->sum('current_saved');
        $achievedCount = $goals->where('is_achieved', true)->count();

        return view('livewire.finance.savings-goals', [
            'goals' => $goals,
            'totalTarget' => $totalTarget,
            'totalSaved' => $totalSaved,
            'achievedCount' => $achievedCount,
        ]);
    }
}
