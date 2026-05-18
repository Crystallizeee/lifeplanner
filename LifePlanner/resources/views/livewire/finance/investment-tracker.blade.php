<div>
    <x-slot:header>Investment Tracker</x-slot:header>
    <x-slot:subtitle>Keuangan · Kelola portofolio investasi & tracking P&L</x-slot:subtitle>

    {{-- Portfolio Summary Stats --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
        <div class="card-stat card-stat--forest">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Total Nilai Pasar</div>
            <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-forest);">
                Rp {{ number_format($totalValue, 0, ',', '.') }}
            </div>
        </div>
        <div class="card-stat card-stat--violet">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Total Modal</div>
            <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-violet);">
                Rp {{ number_format($totalCost, 0, ',', '.') }}
            </div>
        </div>
        <div class="card-stat" style="border-left:4px solid {{ $totalPnl >= 0 ? 'var(--color-forest)' : 'var(--color-danger)' }}; background:var(--color-paper-2);">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Unrealized P&L</div>
            <div style="font-size:22px; font-family:var(--font-serif); color:{{ $totalPnl >= 0 ? 'var(--color-forest)' : 'var(--color-danger)' }};">
                {{ $totalPnl >= 0 ? '+' : '' }}Rp {{ number_format($totalPnl, 0, ',', '.') }}
                <span style="font-size:13px; font-family:var(--font-mono);">({{ $totalPnlPercent >= 0 ? '+' : '' }}{{ $totalPnlPercent }}%)</span>
            </div>
        </div>
        <div class="card-stat card-stat--gold">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Aset Aktif</div>
            <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-gold);">
                {{ $activeCount }} aset
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start;">

        {{-- Left: Investments List --}}
        <div>
            {{-- Header + Add Button --}}
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <h3 style="font-size:18px;">📈 Portofolio Investasi</h3>
                <button class="btn btn-sm {{ $showForm ? 'btn-danger' : 'btn-forest' }}" wire:click="$toggle('showForm')">
                    {{ $showForm ? '✕ Tutup' : '+ Tambah Aset' }}
                </button>
            </div>

            {{-- Filter Tabs --}}
            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px;">
                @php
                    $types = ['all' => '🔍 Semua', 'saham' => '📊 Saham', 'reksadana' => '📈 Reksa Dana', 'crypto' => '₿ Crypto', 'emas' => '🥇 Emas', 'deposito' => '🏦 Deposito', 'properti' => '🏠 Properti', 'lainnya' => '💼 Lainnya'];
                @endphp
                @foreach($types as $typeKey => $typeLabel)
                    <button wire:click="setFilter('{{ $typeKey }}')"
                            style="padding:5px 12px; border-radius:var(--radius-pill); font-size:11px; font-weight:600; cursor:pointer; border:1px solid {{ $filterType === $typeKey ? 'var(--color-forest)' : 'var(--color-paper-3)' }}; background:{{ $filterType === $typeKey ? 'var(--color-forest)' : 'var(--color-card-bg)' }}; color:{{ $filterType === $typeKey ? '#fff' : 'var(--color-ink-3)' }}; transition:all 0.2s;">
                        {{ $typeLabel }}
                    </button>
                @endforeach
            </div>

            {{-- Add Investment Form --}}
            @if($showForm)
                <div class="card" style="margin-bottom:20px; border-color:var(--color-forest-light); background:var(--color-forest-bg);">
                    <form wire:submit="addInvestment" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; align-items:end;">
                        <div>
                            <label class="form-label">Nama Aset</label>
                            <input type="text" wire:model="asset_name" class="form-input" placeholder="Mis. BBCA, Bitcoin, Emas Antam">
                            @error('asset_name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Tipe Aset</label>
                            <select wire:model="asset_type" class="form-select">
                                <option value="saham">📊 Saham</option>
                                <option value="reksadana">📈 Reksa Dana</option>
                                <option value="crypto">₿ Crypto</option>
                                <option value="emas">🥇 Emas</option>
                                <option value="deposito">🏦 Deposito</option>
                                <option value="properti">🏠 Properti</option>
                                <option value="lainnya">💼 Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Jumlah (Lot/Unit/Gram)</label>
                            <input type="number" wire:model="quantity" class="form-input" placeholder="0" step="0.00000001" min="0.00000001">
                            @error('quantity') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Harga Beli / Unit (Rp)</label>
                            <input type="number" wire:model="buy_price" class="form-input" placeholder="0" min="1">
                            @error('buy_price') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Harga Pasar Terkini (Rp)</label>
                            <input type="number" wire:model="current_price" class="form-input" placeholder="0" min="1">
                            @error('current_price') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Tanggal Beli</label>
                            <input type="date" wire:model="buy_date" class="form-input">
                            @error('buy_date') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div style="grid-column: span 2;">
                            <label class="form-label">Catatan</label>
                            <input type="text" wire:model="notes" class="form-input" placeholder="Opsional">
                        </div>
                        <button type="submit" class="btn btn-forest btn-sm" style="width:100%;">💾 Simpan Aset</button>
                    </form>
                </div>
            @endif

            {{-- Sell Asset Modal --}}
            @if($showSellForm && $sellingId)
                <div class="card" style="margin-bottom:16px; border-color:var(--color-blush-light); background:var(--color-blush-bg);">
                    <div style="font-weight:700; font-size:14px; margin-bottom:12px; color:var(--color-blush);">🔴 Jual Aset</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:12px; align-items:end;">
                        <div>
                            <label class="form-label">Harga Jual / Unit (Rp)</label>
                            <input type="number" wire:model="sell_price" class="form-input" min="0.01" step="0.01">
                        </div>
                        <div>
                            <label class="form-label">Tanggal Jual</label>
                            <input type="date" wire:model="sell_date" class="form-input">
                        </div>
                        <div style="display:flex; gap:6px;">
                            <button wire:click="confirmSell" class="btn btn-sm btn-danger">Konfirmasi Jual</button>
                            <button wire:click="cancelSell" class="btn btn-sm btn-secondary">Batal</button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Investments List --}}
            @if($investments->count() === 0)
                <div class="card" style="text-align:center; padding:48px;">
                    <div style="font-size:48px; margin-bottom:12px;">📈</div>
                    <p style="color:var(--color-ink-3);">Belum ada aset investasi. Tambahkan portofolio pertama Anda!</p>
                </div>
            @else
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:12px;">
                    @foreach($investments as $inv)
                        @php
                            $pnl = $inv->unrealized_pnl;
                            $pnlPct = $inv->unrealized_pnl_percent;
                            $isSold = $inv->is_sold;
                            $pnlColor = $isSold ? 'var(--color-ink-3)' : ($pnl > 0 ? 'var(--color-forest)' : ($pnl < 0 ? 'var(--color-danger)' : 'var(--color-gold)'));
                            $borderColor = $isSold ? 'var(--color-ink-4)' : ($pnl > 0 ? 'var(--color-forest-light)' : ($pnl < 0 ? 'rgba(235,94,85,0.3)' : 'var(--color-gold-light)'));
                            $typeColors = [
                                'saham' => 'var(--color-violet)',
                                'reksadana' => 'var(--color-forest)',
                                'crypto' => 'var(--color-gold)',
                                'emas' => 'var(--color-gold)',
                                'deposito' => 'var(--color-ink-2)',
                                'properti' => 'var(--color-blush)',
                                'lainnya' => 'var(--color-ink-3)',
                            ];
                        @endphp
                        <div class="card" style="padding:18px 20px; border-left:4px solid {{ $borderColor }}; {{ $isSold ? 'opacity:0.6;' : '' }}" wire:key="inv-{{ $inv->id }}">
                            {{-- Header Row --}}
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:38px; height:38px; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; font-size:18px; background:var(--color-paper-3);">
                                        {{ \App\Models\Investment::assetTypeIcon($inv->asset_type) }}
                                    </div>
                                    <div>
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <span style="font-weight:700; font-size:15px;">{{ $inv->asset_name }}</span>
                                            @if($isSold)
                                                <span style="font-size:9px; padding:2px 6px; border-radius:4px; background:var(--color-ink-4); color:#fff; font-weight:700;">TERJUAL</span>
                                            @endif
                                        </div>
                                        <div style="display:flex; align-items:center; gap:6px; margin-top:1px;">
                                            <span style="font-size:10px; padding:1px 6px; border-radius:3px; font-weight:600; background:{{ $typeColors[$inv->asset_type] ?? 'var(--color-ink-3)' }}20; color:{{ $typeColors[$inv->asset_type] ?? 'var(--color-ink-3)' }};">
                                                {{ \App\Models\Investment::assetTypeLabel($inv->asset_type) }}
                                            </span>
                                            <span style="font-size:10px; color:var(--color-ink-4); font-family:var(--font-mono);">
                                                {{ $inv->quantity + 0 }} unit · {{ $inv->buy_date->format('d M Y') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                @if(!$isSold)
                                    <div style="display:flex; align-items:center; gap:4px;">
                                        <button wire:click="startEditPrice({{ $inv->id }})"
                                                style="padding:4px 8px; border:1px solid var(--color-paper-3); background:var(--color-card-bg); border-radius:4px; cursor:pointer; font-size:10px; color:var(--color-ink-2);" title="Update Harga">
                                            📊
                                        </button>
                                        <button wire:click="startSell({{ $inv->id }})"
                                                style="padding:4px 8px; border:1px solid var(--color-paper-3); background:var(--color-card-bg); border-radius:4px; cursor:pointer; font-size:10px; color:var(--color-blush);" title="Jual Aset">
                                            💰
                                        </button>
                                        <button wire:click="deleteInvestment({{ $inv->id }})"
                                                wire:confirm="Hapus aset investasi ini?"
                                                style="padding:4px 8px; border:none; background:none; cursor:pointer; font-size:10px; color:var(--color-ink-4);" title="Hapus">
                                            🗑️
                                        </button>
                                    </div>
                                @endif
                            </div>

                            {{-- Quick Price Editor --}}
                            @if($editingPriceId === $inv->id)
                                <div style="display:flex; gap:6px; align-items:center; margin-bottom:10px; padding:8px; background:var(--color-paper-3); border-radius:var(--radius-sm);">
                                    <input type="number" wire:model="newPrice" class="form-input" style="flex:1; padding:6px 10px; font-size:13px;" placeholder="Harga baru" min="0.01" step="0.01">
                                    <button wire:click="savePrice({{ $inv->id }})" class="btn btn-sm btn-forest" style="padding:6px 12px; font-size:11px;">✓</button>
                                    <button wire:click="cancelEditPrice" class="btn btn-sm btn-secondary" style="padding:6px 12px; font-size:11px;">✕</button>
                                </div>
                            @endif

                            {{-- Price Comparison --}}
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                                <div>
                                    <div style="font-size:10px; color:var(--color-ink-4); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">Harga Beli</div>
                                    <div style="font-family:var(--font-mono); font-size:14px; font-weight:600; color:var(--color-ink-2);">
                                        Rp {{ number_format($inv->buy_price, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size:10px; color:var(--color-ink-4); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">{{ $isSold ? 'Harga Jual' : 'Harga Pasar' }}</div>
                                    <div style="font-family:var(--font-mono); font-size:14px; font-weight:600; color:{{ $pnlColor }};">
                                        Rp {{ number_format($inv->current_price, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>

                            {{-- P&L Row --}}
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; border-radius:var(--radius-sm); background:{{ $pnl > 0 ? 'rgba(76,175,80,0.06)' : ($pnl < 0 ? 'rgba(235,94,85,0.06)' : 'rgba(255,193,7,0.06)') }};">
                                <div>
                                    <div style="font-size:10px; color:var(--color-ink-4); margin-bottom:1px;">{{ $isSold ? 'Realized P&L' : 'Unrealized P&L' }}</div>
                                    <div style="font-family:var(--font-mono); font-size:16px; font-weight:700; color:{{ $pnlColor }};">
                                        {{ $pnl >= 0 ? '+' : '' }}Rp {{ number_format($pnl, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="font-family:var(--font-mono); font-size:13px; font-weight:700; color:{{ $pnlColor }};">
                                        {{ $pnlPct >= 0 ? '+' : '' }}{{ $pnlPct }}%
                                    </span>
                                    <span style="font-size:10px; padding:2px 8px; border-radius:var(--radius-pill); font-weight:700;
                                        {{ $pnl > 0 ? 'background:var(--color-forest); color:#fff;' : ($pnl < 0 ? 'background:var(--color-danger); color:#fff;' : 'background:var(--color-gold); color:#333;') }}">
                                        {{ $pnl > 0 ? '▲ Untung' : ($pnl < 0 ? '▼ Rugi' : '● Impas') }}
                                    </span>
                                </div>
                            </div>

                            {{-- Total Value Footer --}}
                            <div style="display:flex; justify-content:space-between; margin-top:8px; font-size:11px; color:var(--color-ink-4);">
                                <span>Modal: Rp {{ number_format($inv->total_cost, 0, ',', '.') }}</span>
                                <span style="font-weight:600; color:var(--color-ink-2);">Nilai: Rp {{ number_format($inv->total_value, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Right Sidebar: Allocation + Activity --}}
        <div style="display:flex; flex-direction:column; gap:16px;">

            {{-- Allocation Breakdown --}}
            <div class="card" style="padding:20px;">
                <h4 style="font-size:15px; font-weight:700; margin-bottom:14px;">🥧 Alokasi Portofolio</h4>
                @if($allocationBreakdown->count() === 0)
                    <p style="font-size:12px; color:var(--color-ink-4); text-align:center; padding:16px 0;">Belum ada aset aktif.</p>
                @else
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        @foreach($allocationBreakdown as $alloc)
                            <div>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <span style="font-size:12px; font-weight:600;">{{ $alloc['icon'] }} {{ $alloc['label'] }}</span>
                                    <span style="font-size:11px; font-family:var(--font-mono); color:var(--color-ink-3);">
                                        {{ $alloc['percent'] }}% ({{ $alloc['count'] }})
                                    </span>
                                </div>
                                <div style="height:6px; background:var(--color-paper-3); border-radius:var(--radius-pill); overflow:hidden;">
                                    <div style="height:100%; border-radius:var(--radius-pill); transition:width 0.5s ease; width:{{ $alloc['percent'] }}%;
                                        background:var(--color-forest-light);"></div>
                                </div>
                                <div style="font-size:10px; color:var(--color-ink-4); margin-top:2px; font-family:var(--font-mono);">
                                    Rp {{ number_format($alloc['value'], 0, ',', '.') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recent Activity Log --}}
            <div class="card" style="padding:20px;">
                <h4 style="font-size:15px; font-weight:700; margin-bottom:14px;">📜 Aktivitas Terakhir</h4>
                @if($recentLogs->count() === 0)
                    <p style="font-size:12px; color:var(--color-ink-4); text-align:center; padding:16px 0;">Belum ada aktivitas.</p>
                @else
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach($recentLogs as $log)
                            <div style="display:flex; align-items:start; gap:8px; padding:6px 0; border-bottom:1px solid var(--color-paper-3);">
                                <span style="font-size:14px; flex-shrink:0; margin-top:1px;">
                                    {{ match($log->action) { 'buy' => '🟢', 'sell' => '🔴', 'dividend' => '💰', 'price_update' => '📊', 'top_up' => '➕', default => '•' } }}
                                </span>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:12px; font-weight:600;">
                                        {{ $log->investment->asset_name ?? '-' }}
                                    </div>
                                    <div style="font-size:10px; color:var(--color-ink-4);">
                                        {{ $log->notes ?? \App\Models\InvestmentLog::actionLabel($log->action) }}
                                    </div>
                                </div>
                                <div style="text-align:right; flex-shrink:0;">
                                    <div style="font-size:11px; font-family:var(--font-mono); font-weight:600;">
                                        Rp {{ number_format($log->price, 0, ',', '.') }}
                                    </div>
                                    <div style="font-size:9px; color:var(--color-ink-4); font-family:var(--font-mono);">
                                        {{ $log->logged_at->format('d M H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
