<?php

namespace App\Livewire\Health;

use App\Models\GroceryList;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-11 Grocery List
#[Layout('components.layouts.app')]
#[Title('Grocery List — LifePlanner SIM')]
class GroceryListPage extends Component
{
    public bool $showForm = false;

    #[Validate('required|min:2|max:150')]
    public string $item_name = '';

    #[Validate('nullable|numeric|min:0.1')]
    public ?string $qty = null;

    #[Validate('nullable|string|max:20')]
    public ?string $unit = null;

    #[Validate('nullable|string|max:50')]
    public ?string $category = null;

    #[Validate('required|date')]
    public string $week_of = '';

    public function mount(): void
    {
        $this->week_of = now()->startOfWeek()->format('Y-m-d');
    }

    public function addItem(): void
    {
        $this->validate();

        GroceryList::create([
            'user_id' => Auth::id(),
            'item_name' => $this->item_name,
            'qty' => $this->qty,
            'unit' => $this->unit,
            'category' => $this->category ?: 'Umum',
            'is_checked' => false,
            'week_of' => $this->week_of,
        ]);

        $this->reset(['item_name', 'qty', 'unit', 'category']);
        $this->showForm = false;
        $this->dispatch('toast', message: 'Item ditambahkan! 🛒', type: 'success');
    }

    public function toggleItem(int $id): void
    {
        $item = GroceryList::where('user_id', Auth::id())->find($id);
        if ($item) {
            $item->update(['is_checked' => !$item->is_checked]);
        }
    }

    public function deleteItem(int $id): void
    {
        GroceryList::where('user_id', Auth::id())->where('id', $id)->delete();
        $this->dispatch('toast', message: 'Item dihapus.', type: 'warning');
    }

    public function clearChecked(): void
    {
        GroceryList::where('user_id', Auth::id())
            ->where('is_checked', true)
            ->where('week_of', $this->week_of)
            ->delete();
        $this->dispatch('toast', message: 'Item yang dicentang dihapus.', type: 'warning');
    }

    public function render()
    {
        $items = GroceryList::where('user_id', Auth::id())
            ->where('week_of', $this->week_of)
            ->orderBy('is_checked')
            ->orderBy('category')
            ->orderBy('item_name')
            ->get();

        // Group by category
        $grouped = $items->groupBy('category');

        $totalItems = $items->count();
        $checkedItems = $items->where('is_checked', true)->count();

        return view('livewire.health.grocery-list', [
            'grouped' => $grouped,
            'totalItems' => $totalItems,
            'checkedItems' => $checkedItems,
        ]);
    }
}
