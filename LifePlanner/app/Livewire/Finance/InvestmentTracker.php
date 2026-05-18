<?php

namespace App\Livewire\Finance;

use App\Models\Investment;
use App\Models\InvestmentLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Investment Tracker — LifePlanner SIM')]
class InvestmentTracker extends Component
{
    public bool $showForm = false;
    public bool $showSellForm = false;
    public string $filterType = 'all';

    // ── Add Investment Form ──
    #[Validate('required|min:2|max:150')]
    public string $asset_name = '';

    #[Validate('required|in:saham,reksadana,crypto,emas,deposito,properti,lainnya')]
    public string $asset_type = 'saham';

    #[Validate('required|numeric|min:0.00000001')]
    public string $quantity = '';

    #[Validate('required|numeric|min:1')]
    public string $buy_price = '';

    #[Validate('required|numeric|min:1')]
    public string $current_price = '';

    #[Validate('required|date')]
    public string $buy_date = '';

    #[Validate('nullable|string|max:500')]
    public ?string $notes = null;

    // ── Quick Update Price ──
    public ?int $editingPriceId = null;
    public string $newPrice = '';

    // ── Sell Form ──
    public ?int $sellingId = null;
    public string $sell_price = '';
    public string $sell_date = '';

    public function mount(): void
    {
        $this->buy_date = now()->format('Y-m-d');
        $this->sell_date = now()->format('Y-m-d');
    }

    // ── Add Investment ──
    public function addInvestment(): void
    {
        $this->validate([
            'asset_name' => 'required|min:2|max:150',
            'asset_type' => 'required|in:saham,reksadana,crypto,emas,deposito,properti,lainnya',
            'quantity' => 'required|numeric|min:0.00000001',
            'buy_price' => 'required|numeric|min:1',
            'current_price' => 'required|numeric|min:1',
            'buy_date' => 'required|date',
        ]);

        $userId = Auth::id();

        $investment = Investment::create([
            'user_id' => $userId,
            'asset_name' => $this->asset_name,
            'asset_type' => $this->asset_type,
            'quantity' => $this->quantity,
            'buy_price' => $this->buy_price,
            'current_price' => $this->current_price,
            'buy_date' => $this->buy_date,
            'notes' => $this->notes,
        ]);

        // Log the initial buy
        InvestmentLog::create([
            'investment_id' => $investment->id,
            'user_id' => $userId,
            'action' => 'buy',
            'quantity' => $this->quantity,
            'price' => $this->buy_price,
            'notes' => 'Pembelian awal',
            'logged_at' => $this->buy_date . ' ' . now()->format('H:i:s'),
        ]);

        $this->reset(['asset_name', 'quantity', 'buy_price', 'current_price', 'notes']);
        $this->asset_type = 'saham';
        $this->buy_date = now()->format('Y-m-d');
        $this->showForm = false;
        $this->dispatch('toast', message: 'Investasi baru ditambahkan! 📈', type: 'success');
    }

    // ── Quick Update Price ──
    public function startEditPrice(int $id): void
    {
        $inv = Investment::where('user_id', Auth::id())->find($id);
        if ($inv) {
            $this->editingPriceId = $id;
            $this->newPrice = (string) $inv->current_price;
        }
    }

    public function cancelEditPrice(): void
    {
        $this->editingPriceId = null;
        $this->newPrice = '';
    }

    public function savePrice(int $id): void
    {
        $this->validate(['newPrice' => 'required|numeric|min:0.01']);

        $inv = Investment::where('user_id', Auth::id())->active()->find($id);
        if (!$inv) return;

        $oldPrice = $inv->current_price;
        $inv->update(['current_price' => $this->newPrice]);

        InvestmentLog::create([
            'investment_id' => $inv->id,
            'user_id' => Auth::id(),
            'action' => 'price_update',
            'price' => $this->newPrice,
            'notes' => 'Update harga: Rp ' . number_format($oldPrice, 0, ',', '.') . ' → Rp ' . number_format($this->newPrice, 0, ',', '.'),
            'logged_at' => now(),
        ]);

        $this->editingPriceId = null;
        $this->newPrice = '';
        $this->dispatch('toast', message: 'Harga pasar berhasil diperbarui! ✅', type: 'success');
    }

    // ── Sell Asset ──
    public function startSell(int $id): void
    {
        $this->sellingId = $id;
        $inv = Investment::where('user_id', Auth::id())->find($id);
        if ($inv) {
            $this->sell_price = (string) $inv->current_price;
        }
        $this->sell_date = now()->format('Y-m-d');
        $this->showSellForm = true;
    }

    public function cancelSell(): void
    {
        $this->sellingId = null;
        $this->sell_price = '';
        $this->showSellForm = false;
    }

    public function confirmSell(): void
    {
        $this->validate([
            'sell_price' => 'required|numeric|min:0.01',
            'sell_date' => 'required|date',
        ]);

        $inv = Investment::where('user_id', Auth::id())->find($this->sellingId);
        if (!$inv) return;

        $inv->update([
            'is_sold' => true,
            'sold_price' => $this->sell_price,
            'sold_date' => $this->sell_date,
            'current_price' => $this->sell_price,
        ]);

        InvestmentLog::create([
            'investment_id' => $inv->id,
            'user_id' => Auth::id(),
            'action' => 'sell',
            'quantity' => $inv->quantity,
            'price' => $this->sell_price,
            'notes' => 'Aset dijual seluruhnya',
            'logged_at' => $this->sell_date . ' ' . now()->format('H:i:s'),
        ]);

        $this->cancelSell();
        $this->dispatch('toast', message: 'Aset berhasil dijual! 💰', type: 'success');
    }

    // ── Delete ──
    public function deleteInvestment(int $id): void
    {
        Investment::where('user_id', Auth::id())->where('id', $id)->delete();
        $this->dispatch('toast', message: 'Investasi dihapus.', type: 'warning');
    }

    // ── Filter ──
    public function setFilter(string $type): void
    {
        $this->filterType = $type;
    }

    // ── Render ──
    public function render()
    {
        $userId = Auth::id();

        $query = Investment::where('user_id', $userId)->orderBy('is_sold')->orderByDesc('updated_at');

        if ($this->filterType !== 'all') {
            $query->where('asset_type', $this->filterType);
        }

        $investments = $query->get();

        // Aggregate stats (active only)
        $activeInvestments = Investment::where('user_id', $userId)->active()->get();
        $totalValue = $activeInvestments->sum(fn($i) => $i->total_value);
        $totalCost = $activeInvestments->sum(fn($i) => $i->total_cost);
        $totalPnl = $totalValue - $totalCost;
        $totalPnlPercent = $totalCost > 0 ? round(($totalPnl / $totalCost) * 100, 2) : 0;
        $activeCount = $activeInvestments->count();

        // Allocation breakdown (active only)
        $allocationBreakdown = $activeInvestments->groupBy('asset_type')->map(function ($group, $type) use ($totalValue) {
            $groupValue = $group->sum(fn($i) => $i->total_value);
            return [
                'type' => $type,
                'label' => Investment::assetTypeLabel($type),
                'icon' => Investment::assetTypeIcon($type),
                'value' => $groupValue,
                'count' => $group->count(),
                'percent' => $totalValue > 0 ? round(($groupValue / $totalValue) * 100, 1) : 0,
            ];
        })->sortByDesc('value')->values();

        // Recent logs (last 10)
        $recentLogs = InvestmentLog::where('user_id', $userId)
            ->with('investment')
            ->orderByDesc('logged_at')
            ->limit(10)
            ->get();

        return view('livewire.finance.investment-tracker', [
            'investments' => $investments,
            'totalValue' => $totalValue,
            'totalCost' => $totalCost,
            'totalPnl' => $totalPnl,
            'totalPnlPercent' => $totalPnlPercent,
            'activeCount' => $activeCount,
            'allocationBreakdown' => $allocationBreakdown,
            'recentLogs' => $recentLogs,
        ]);
    }
}
