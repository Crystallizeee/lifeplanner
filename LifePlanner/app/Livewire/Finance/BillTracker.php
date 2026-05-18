<?php

namespace App\Livewire\Finance;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-04 Bill Tracker
#[Layout('components.layouts.app')]
#[Title('Bill Tracker — LifePlanner SIM')]
class BillTracker extends Component
{
    public bool $showForm = false;

    #[Validate('required|min:3|max:255')]
    public string $description = '';

    #[Validate('required|numeric|min:1')]
    public string $amount = '';

    #[Validate('required|exists:categories,id')]
    public string $category_id = '';

    #[Validate('required|date')]
    public string $due_date = '';

    #[Validate('boolean')]
    public bool $is_recurring = false;

    #[Validate('nullable|string|max:500')]
    public ?string $notes = null;

    public function mount(): void
    {
        $this->due_date = now()->format('Y-m-d');
    }

    public function addBill(): void
    {
        $this->validate();
        $userId = Auth::id();

        $activeBudget = Budget::where('user_id', $userId)->where('is_active', true)->first();
        if (!$activeBudget) {
            $activeBudget = Budget::create([
                'user_id' => $userId,
                'period_start' => now()->startOfMonth()->format('Y-m-d'),
                'period_end' => now()->endOfMonth()->format('Y-m-d'),
                'starting_balance' => 0,
                'is_active' => true,
            ]);
        }

        Transaction::create([
            'user_id' => $userId,
            'budget_id' => $activeBudget->id,
            'category_id' => $this->category_id,
            'type' => 'bill',
            'amount' => $this->amount,
            'description' => $this->description,
            'transaction_date' => now()->format('Y-m-d'),
            'due_date' => $this->due_date,
            'status' => 'planned',
            'is_recurring' => $this->is_recurring,
            'notes' => $this->notes,
        ]);

        $this->reset(['description', 'amount', 'category_id', 'notes', 'is_recurring']);
        $this->due_date = now()->format('Y-m-d');
        $this->showForm = false;
        $this->dispatch('toast', message: 'Tagihan baru ditambahkan! 📋', type: 'success');
    }

    public function markPaid(int $id): void
    {
        $tx = Transaction::where('user_id', Auth::id())->where('type', 'bill')->find($id);
        if ($tx) {
            $tx->update(['status' => 'paid']);
            $this->dispatch('toast', message: 'Tagihan ditandai lunas! ✅', type: 'success');
        }
    }

    public function markOverdue(int $id): void
    {
        $tx = Transaction::where('user_id', Auth::id())->where('type', 'bill')->find($id);
        if ($tx) {
            $tx->update(['status' => 'overdue']);
        }
    }

    public function deleteBill(int $id): void
    {
        Transaction::where('user_id', Auth::id())->where('type', 'bill')->where('id', $id)->delete();
        $this->dispatch('toast', message: 'Tagihan dihapus.', type: 'warning');
    }

    public function render()
    {
        $userId = Auth::id();

        // Auto-detect overdue bills
        Transaction::where('user_id', $userId)
            ->where('type', 'bill')
            ->where('status', 'planned')
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        $bills = Transaction::where('user_id', $userId)
            ->where('type', 'bill')
            ->with('category')
            ->orderByRaw("CASE status WHEN 'overdue' THEN 0 WHEN 'planned' THEN 1 WHEN 'paid' THEN 2 END")
            ->orderBy('due_date')
            ->get();

        $totalPending = $bills->whereIn('status', ['planned', 'overdue'])->sum('amount');
        $totalPaid = $bills->where('status', 'paid')->sum('amount');
        $overdueCount = $bills->where('status', 'overdue')->count();

        $billCategories = Category::where('user_id', $userId)
            ->where('type', 'bill')
            ->orderBy('name')
            ->get();

        return view('livewire.finance.bill-tracker', [
            'bills' => $bills,
            'totalPending' => $totalPending,
            'totalPaid' => $totalPaid,
            'overdueCount' => $overdueCount,
            'billCategories' => $billCategories,
        ]);
    }
}
