<?php

namespace App\Livewire\Finance;

use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-02 Quick Transaction Entry
#[Layout('components.layouts.app')]
#[Title('Quick Entry — LifePlanner SIM')]
class QuickEntry extends Component
{
    #[Validate('required|min:3|max:255')]
    public string $description = '';

    #[Validate('required|numeric|min:1')]
    public string $amount = '';

    #[Validate('required|in:income,expense,bill,saving')]
    public string $type = 'expense';

    #[Validate('required|exists:categories,id')]
    public string $category_id = '';

    #[Validate('nullable|exists:savings_goals,id')]
    public ?string $savings_goal_id = null;

    #[Validate('required|date')]
    public string $transaction_date = '';

    #[Validate('nullable|date')]
    public ?string $due_date = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $notes = null;

    public array $recentTransactions = [];

    public function mount(): void
    {
        $this->transaction_date = now()->format('Y-m-d');
        $this->loadRecentTransactions();
    }

    public function updatedType(): void
    {
        // Reset category when type changes
        $this->category_id = '';
        $this->savings_goal_id = null;
        $this->due_date = null;
    }

    public function save(): void
    {
        $this->validate();

        $userId = Auth::id();

        // Get or create active budget
        $activeBudget = Budget::where('user_id', $userId)->where('is_active', true)->first();

        if (!$activeBudget) {
            // Auto-create a budget for current month if none exists
            $activeBudget = Budget::create([
                'user_id' => $userId,
                'period_start' => now()->startOfMonth()->format('Y-m-d'),
                'period_end' => now()->endOfMonth()->format('Y-m-d'),
                'starting_balance' => 0,
                'is_active' => true,
            ]);
        }

        // Create transaction
        // @see LP-DB-SCHEMA-2026-001 | transactions table
        $transaction = Transaction::create([
            'user_id' => $userId,
            'budget_id' => $activeBudget->id,
            'category_id' => $this->category_id,
            'savings_goal_id' => $this->type === 'saving' ? $this->savings_goal_id : null,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date,
            'due_date' => $this->type === 'bill' ? $this->due_date : null,
            'status' => $this->type === 'bill' ? 'planned' : 'paid',
            'notes' => $this->notes,
        ]);

        // Update savings goal current_saved if type is saving
        if ($this->type === 'saving' && $this->savings_goal_id) {
            $goal = SavingsGoal::find($this->savings_goal_id);
            if ($goal) {
                $goal->update([
                    'current_saved' => $goal->current_saved + $this->amount,
                    'is_achieved' => ($goal->current_saved + $this->amount) >= $goal->target_amount,
                ]);
            }
        }

        // Dispatch toast
        $this->dispatch('toast', message: 'Transaksi berhasil disimpan! 🎉', type: 'success');

        // Reset form
        $this->reset(['description', 'amount', 'notes', 'savings_goal_id', 'due_date']);
        $this->transaction_date = now()->format('Y-m-d');

        // Reload recent
        $this->loadRecentTransactions();
    }

    public function deleteTransaction(int $id): void
    {
        $transaction = Transaction::where('user_id', Auth::id())->find($id);
        if ($transaction) {
            // Reverse savings if applicable
            if ($transaction->type === 'saving' && $transaction->savings_goal_id) {
                $goal = SavingsGoal::find($transaction->savings_goal_id);
                if ($goal) {
                    $goal->update([
                        'current_saved' => max(0, $goal->current_saved - $transaction->amount),
                        'is_achieved' => max(0, $goal->current_saved - $transaction->amount) >= $goal->target_amount,
                    ]);
                }
            }

            $transaction->delete();
            $this->dispatch('toast', message: 'Transaksi dihapus.', type: 'warning');
            $this->loadRecentTransactions();
        }
    }

    private function loadRecentTransactions(): void
    {
        $this->recentTransactions = Transaction::where('user_id', Auth::id())
            ->with('category')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'description' => $t->description,
                'amount' => $t->amount,
                'type' => $t->type,
                'status' => $t->status,
                'category_name' => $t->category->name ?? '-',
                'category_icon' => $t->category->icon ?? '📦',
                'transaction_date' => $t->transaction_date->format('d M Y'),
                'due_date' => $t->due_date?->format('d M Y'),
            ])
            ->toArray();
    }

    public function render()
    {
        $userId = Auth::id();

        return view('livewire.finance.quick-entry', [
            'categories' => Category::where('user_id', $userId)
                ->where('type', $this->type)
                ->orderBy('name')
                ->get(),
            'savingsGoals' => SavingsGoal::where('user_id', $userId)
                ->where('is_achieved', false)
                ->orderBy('goal_name')
                ->get(),
        ]);
    }
}
