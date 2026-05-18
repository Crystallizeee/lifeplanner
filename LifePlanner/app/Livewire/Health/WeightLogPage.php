<?php

namespace App\Livewire\Health;

use App\Models\WeightLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | US-09 Weight Log
#[Layout('components.layouts.app')]
#[Title('Weight Log — LifePlanner SIM')]
class WeightLogPage extends Component
{
    #[Validate('required|numeric|min:20|max:500')]
    public string $weight_kg = '';

    #[Validate('nullable|numeric|min:1|max:80')]
    public ?string $body_fat_pct = null;

    #[Validate('nullable|string|max:500')]
    public ?string $notes = null;

    #[Validate('required|date')]
    public string $date = '';

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate();
        $userId = Auth::id();

        WeightLog::updateOrCreate(
            ['user_id' => $userId, 'date' => $this->date],
            [
                'weight_kg' => $this->weight_kg,
                'body_fat_pct' => $this->body_fat_pct,
                'notes' => $this->notes,
            ]
        );

        $this->reset(['weight_kg', 'body_fat_pct', 'notes']);
        $this->date = now()->format('Y-m-d');
        $this->dispatch('toast', message: 'Berat badan tercatat! ⚖️', type: 'success');
    }

    public function deleteEntry(int $id): void
    {
        WeightLog::where('user_id', Auth::id())->where('id', $id)->delete();
        $this->dispatch('toast', message: 'Entri dihapus.', type: 'warning');
    }

    public function render()
    {
        $userId = Auth::id();

        $logs = WeightLog::where('user_id', $userId)
            ->orderByDesc('date')
            ->take(30)
            ->get();

        $latestWeight = $logs->first()?->weight_kg;
        $previousWeight = $logs->skip(1)->first()?->weight_kg;
        $weightChange = ($latestWeight && $previousWeight) ? $latestWeight - $previousWeight : null;

        // Chart data for last 14 entries (reversed for chronological order)
        $chartData = $logs->take(14)->reverse()->values();

        return view('livewire.health.weight-log', [
            'logs' => $logs,
            'latestWeight' => $latestWeight,
            'weightChange' => $weightChange,
            'chartData' => $chartData,
        ]);
    }
}
