<?php

namespace App\Livewire\Finance;

use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-03 Budget Overview
#[Layout('components.layouts.app')]
#[Title('Budget Overview — LifePlanner SIM')]
class BudgetOverview extends Component
{
    // Budget creation form
    public bool $showCreateForm = false;

    #[Validate('required|date')]
    public string $period_start = '';

    #[Validate('required|date|after_or_equal:period_start')]
    public string $period_end = '';

    #[Validate('required|numeric|min:0')]
    public string $starting_balance = '0';

    // Allocation form
    public bool $showAllocationForm = false;

    #[Validate('required|exists:categories,id')]
    public string $alloc_category_id = '';

    #[Validate('required|numeric|min:0')]
    public string $alloc_amount = '';

    public function mount(): void
    {
        $this->period_start = now()->startOfMonth()->format('Y-m-d');
        $this->period_end = now()->endOfMonth()->format('Y-m-d');
    }

    public function createBudget(): void
    {
        $this->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'starting_balance' => 'required|numeric|min:0',
        ]);

        $userId = Auth::id();

        // Deactivate any existing active budget
        $previousBudget = Budget::where('user_id', $userId)->where('is_active', true)->first();
        if ($previousBudget) {
            $previousBudget->update(['is_active' => false]);
        }

        // @see LP-DB-SCHEMA-2026-001 | budgets table
        $newBudget = Budget::create([
            'user_id' => $userId,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'starting_balance' => $this->starting_balance,
            'is_active' => true,
        ]);

        // Migrate orphaned transactions from old budgets within the new period range
        // This ensures transactions recorded via QuickEntry auto-budget are properly linked
        Transaction::where('user_id', $userId)
            ->where('budget_id', '!=', $newBudget->id)
            ->whereBetween('transaction_date', [$this->period_start, $this->period_end])
            ->update(['budget_id' => $newBudget->id]);

        // Auto-clone recurring bills from the previous budget
        if ($previousBudget) {
            $recurringBills = Transaction::where('budget_id', $previousBudget->id)
                ->where('type', 'bill')
                ->where('is_recurring', true)
                ->get();

            foreach ($recurringBills as $bill) {
                // Calculate new due date (typically matches the new period start with same day of month)
                $oldDue = Carbon::parse($bill->due_date);
                $newDue = Carbon::parse($this->period_start)->day(
                    min($oldDue->day, Carbon::parse($this->period_end)->day)
                );

                // If due date lands before start, shift to end or middle safely
                if ($newDue->lt(Carbon::parse($this->period_start))) {
                    $newDue = Carbon::parse($this->period_start);
                }

                Transaction::create([
                    'budget_id' => $newBudget->id,
                    'user_id' => $userId,
                    'category_id' => $bill->category_id,
                    'type' => 'bill',
                    'amount' => $bill->amount,
                    'description' => $bill->description,
                    'transaction_date' => $this->period_start,
                    'due_date' => $newDue->format('Y-m-d'),
                    'status' => 'planned',
                    'is_recurring' => true,
                    'notes' => $bill->notes,
                ]);
            }

            // ALSO: Auto-clone budget allocations for premium convenience!
            $allocations = BudgetAllocation::where('budget_id', $previousBudget->id)->get();
            foreach ($allocations as $alloc) {
                BudgetAllocation::create([
                    'budget_id' => $newBudget->id,
                    'category_id' => $alloc->category_id,
                    'allocated_amount' => $alloc->allocated_amount,
                ]);
            }
        }

        $this->showCreateForm = false;
        $this->dispatch('toast', message: 'Budget & tagihan rutin berhasil dibuat! 🎉', type: 'success');
    }

    public function activateBudget(int $budgetId): void
    {
        $userId = Auth::id();
        Budget::where('user_id', $userId)->where('is_active', true)->update(['is_active' => false]);
        Budget::where('user_id', $userId)->where('id', $budgetId)->update(['is_active' => true]);
        $this->dispatch('toast', message: 'Budget diaktifkan.', type: 'success');
    }

    public function addAllocation(): void
    {
        $this->validate([
            'alloc_category_id' => 'required|exists:categories,id',
            'alloc_amount' => 'required|numeric|min:0',
        ]);

        $activeBudget = Budget::where('user_id', Auth::id())->where('is_active', true)->first();

        if (!$activeBudget) {
            $this->dispatch('toast', message: 'Buat budget terlebih dahulu!', type: 'error');
            return;
        }

        // @see LP-DB-SCHEMA-2026-001 | budget_allocations UNIQUE(budget_id, category_id)
        BudgetAllocation::updateOrCreate(
            [
                'budget_id' => $activeBudget->id,
                'category_id' => $this->alloc_category_id,
            ],
            [
                'allocated_amount' => $this->alloc_amount,
            ]
        );

        $this->reset(['alloc_category_id', 'alloc_amount']);
        $this->showAllocationForm = false;
        $this->dispatch('toast', message: 'Alokasi berhasil diperbarui! ✅', type: 'success');
    }

    public function deleteAllocation(int $id): void
    {
        BudgetAllocation::where('id', $id)
            ->whereHas('budget', fn($q) => $q->where('user_id', Auth::id()))
            ->delete();

        $this->dispatch('toast', message: 'Alokasi dihapus.', type: 'warning');
    }

    public function render()
    {
        $userId = Auth::id();
        $activeBudget = Budget::where('user_id', $userId)->where('is_active', true)->first();

        $allocations = [];
        $totalAllocated = 0;
        $totalIncome = 0;
        $totalExpense = 0;
        $savingsRate = 0;
        $savingsRateHealth = 'Belum Aktif ⏳';
        $savingsRateColor = 'var(--color-ink-4)';
        $savingsRateDescription = 'Buat budget periode untuk melihat analisis kesehatan finansial.';

        if ($activeBudget) {
            $allocations = BudgetAllocation::where('budget_id', $activeBudget->id)
                ->with('category')
                ->get()
                ->map(function ($alloc) use ($activeBudget) {
                    $spent = Transaction::where('budget_id', $activeBudget->id)
                        ->where('category_id', $alloc->category_id)
                        ->where('type', 'expense')
                        ->sum('amount');

                    return [
                        'id' => $alloc->id,
                        'category_name' => $alloc->category->name ?? '-',
                        'category_icon' => $alloc->category->icon ?? '📦',
                        'allocated' => (float) $alloc->allocated_amount,
                        'spent' => (float) $spent,
                        'remaining' => (float) $alloc->allocated_amount - (float) $spent,
                        'percentage' => $alloc->allocated_amount > 0
                            ? min(100, round(($spent / $alloc->allocated_amount) * 100))
                            : 0,
                    ];
                })
                ->toArray();

            $totalAllocated = array_sum(array_column($allocations, 'allocated'));

            $totalIncome = Transaction::where('budget_id', $activeBudget->id)
                ->where('type', 'income')
                ->sum('amount');

            $totalExpense = Transaction::where('budget_id', $activeBudget->id)
                ->where('type', 'expense')
                ->sum('amount');

            // Savings Rate Health Calculations
            $savingsRate = 0;
            $savingsRateHealth = 'Belum Aktif ⏳';
            $savingsRateColor = 'var(--color-ink-4)';
            $savingsRateDescription = 'Belum ada pemasukan yang tercatat untuk menghitung metrik rasio.';

            if ($totalIncome > 0) {
                $savingsRate = round((($totalIncome - $totalExpense) / $totalIncome) * 100);
                if ($savingsRate >= 20) {
                    $savingsRateHealth = 'Sangat Sehat 🌟';
                    $savingsRateColor = 'var(--color-forest)';
                    $savingsRateDescription = 'Luar biasa! Tingkat tabungan Anda di atas 20%. Kebebasan finansial di depan mata!';
                } elseif ($savingsRate >= 10) {
                    $savingsRateHealth = 'Cukup Baik 👍';
                    $savingsRateColor = 'var(--color-warning)';
                    $savingsRateDescription = 'Rasio tabungan standar yang sehat (10-20%). Pertahankan konsistensi Anda.';
                } else {
                    $savingsRateHealth = 'Kurang Sehat ⚠️';
                    $savingsRateColor = 'var(--color-danger)';
                    if ($savingsRate < 0) {
                        $savingsRateDescription = 'Pengeluaran melebihi pemasukan! Segera kurangi biaya non-primer.';
                    } else {
                        $savingsRateDescription = 'Rasio tabungan di bawah 10%. Usahakan sisihkan lebih banyak tabungan.';
                    }
                }
            }
        }

        // Get expense categories for allocation form
        $expenseCategories = Category::where('user_id', $userId)
            ->whereIn('type', ['expense', 'bill'])
            ->orderBy('name')
            ->get();

        // All budgets (including inactive) for history and switching
        $allBudgets = Budget::where('user_id', $userId)
            ->orderByDesc('period_start')
            ->get();

        return view('livewire.finance.budget-overview', [
            'activeBudget' => $activeBudget,
            'allocations' => $allocations,
            'totalAllocated' => $totalAllocated,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'savingsRate' => $savingsRate,
            'savingsRateHealth' => $savingsRateHealth,
            'savingsRateColor' => $savingsRateColor,
            'savingsRateDescription' => $savingsRateDescription,
            'expenseCategories' => $expenseCategories,
            'allBudgets' => $allBudgets,
        ]);
    }
}
