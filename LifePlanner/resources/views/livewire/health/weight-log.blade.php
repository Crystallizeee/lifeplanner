<div>
    <x-slot:header>Weight Log</x-slot:header>
    <x-slot:subtitle>Kesehatan · Tracking berat badan harian</x-slot:subtitle>

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
        <div class="card-stat card-stat--forest">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Berat Saat Ini</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-forest);">
                {{ $latestWeight ? number_format($latestWeight, 1) . ' kg' : '—' }}
            </div>
        </div>
        <div class="card-stat card-stat--{{ $weightChange !== null ? ($weightChange > 0 ? 'gold' : ($weightChange < 0 ? 'forest' : 'violet')) : 'violet' }}">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Perubahan Terakhir</div>
            <div style="font-size:28px; font-family:var(--font-serif);
                {{ $weightChange !== null ? ($weightChange > 0 ? 'color:var(--color-warning);' : ($weightChange < 0 ? 'color:var(--color-forest);' : 'color:var(--color-violet);')) : 'color:var(--color-ink-3);' }}">
                {{ $weightChange !== null ? ($weightChange > 0 ? '+' : '') . number_format($weightChange, 1) . ' kg' : '—' }}
            </div>
        </div>
        <div class="card-stat card-stat--violet">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Total Entri</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-violet);">{{ $logs->count() }}</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:start;">
        {{-- Left: Chart + History --}}
        <div>
            {{-- Mini Chart --}}
            @if($chartData->count() >= 2)
                <div class="card" style="margin-bottom:20px; padding:20px;">
                    <h3 style="font-size:16px; margin-bottom:16px;">📈 Tren Berat Badan</h3>
                    <div style="display:flex; align-items:end; gap:4px; height:120px;">
                        @php
                            $minW = $chartData->min('weight_kg');
                            $maxW = $chartData->max('weight_kg');
                            $range = max($maxW - $minW, 1);
                        @endphp
                        @foreach($chartData as $entry)
                            @php $height = (($entry->weight_kg - $minW) / $range) * 100; @endphp
                            <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:4px;">
                                <span style="font-size:9px; font-family:var(--font-mono); color:var(--color-ink-4);">{{ number_format($entry->weight_kg, 1) }}</span>
                                <div style="width:100%; height:{{ max(8, $height) }}px; background:var(--color-forest-light); border-radius:var(--radius-sm) var(--radius-sm) 0 0; min-height:8px;"></div>
                                <span style="font-size:8px; color:var(--color-ink-4);">{{ $entry->date->format('d/m') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- History --}}
            <h3 style="font-size:18px; margin-bottom:12px;">📋 Riwayat</h3>
            @if($logs->count() === 0)
                <div class="card" style="text-align:center; padding:48px;">
                    <div style="font-size:48px; margin-bottom:12px;">⚖️</div>
                    <p style="color:var(--color-ink-3);">Belum ada data. Catat berat badanmu hari ini!</p>
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:6px;">
                    @foreach($logs as $log)
                        <div class="card" style="padding:12px 20px; display:flex; align-items:center; gap:14px;" wire:key="wl-{{ $log->id }}">
                            <div style="font-family:var(--font-mono); font-size:12px; color:var(--color-ink-4); min-width:80px;">
                                {{ $log->date->format('d M Y') }}
                            </div>
                            <div style="font-family:var(--font-mono); font-size:18px; font-weight:700; color:var(--color-forest); flex:1;">
                                {{ number_format($log->weight_kg, 1) }} kg
                            </div>
                            @if($log->body_fat_pct)
                                <span class="badge badge-neutral" style="font-family:var(--font-mono);">BF: {{ number_format($log->body_fat_pct, 1) }}%</span>
                            @endif
                            @if($log->notes)
                                <span style="font-size:12px; color:var(--color-ink-3); max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $log->notes }}">
                                    📝 {{ $log->notes }}
                                </span>
                            @endif
                            <button wire:click="deleteEntry({{ $log->id }})" wire:confirm="Hapus entri ini?"
                                    style="padding:4px; border:none; background:none; cursor:pointer; color:var(--color-ink-4);">🗑️</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Right: Log Form --}}
        <div class="card" style="position:sticky; top:100px;">
            <h3 style="font-size:16px; margin-bottom:16px;">⚖️ Catat Berat Badan</h3>
            <form wire:submit="save">
                <div style="margin-bottom:14px;">
                    <label class="form-label">Berat (kg)</label>
                    <input type="number" wire:model="weight_kg" class="form-input" placeholder="70.5" step="0.1" min="20" max="500" style="font-family:var(--font-mono); font-size:22px; font-weight:700; text-align:center;">
                    @error('weight_kg') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div style="margin-bottom:14px;">
                    <label class="form-label">Body Fat % (opsional)</label>
                    <input type="number" wire:model="body_fat_pct" class="form-input" placeholder="20.0" step="0.1" min="1" max="80">
                </div>
                <div style="margin-bottom:14px;">
                    <label class="form-label">Tanggal</label>
                    <input type="date" wire:model="date" class="form-input">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Catatan (opsional)</label>
                    <input type="text" wire:model="notes" class="form-input" placeholder="Mis. Setelah olahraga">
                </div>
                <button type="submit" class="btn btn-forest" style="width:100%; justify-content:center;">
                    💾 Simpan
                </button>
            </form>
        </div>
    </div>

    <style>
        @media (max-width: 768px) {
            div[style*="grid-template-columns:1fr 380px"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</div>
