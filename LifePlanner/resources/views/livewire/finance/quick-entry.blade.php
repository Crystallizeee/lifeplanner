<div>
    <x-slot:header>Quick Entry</x-slot:header>
    <x-slot:subtitle>Keuangan · Catat transaksi cepat</x-slot:subtitle>

    <div style="display:grid; grid-template-columns:1fr 1.2fr; gap:24px; align-items:start;">

        {{-- Quick Entry Form --}}
        <div class="card" style="position:sticky; top:100px;">
            <h3 style="font-size:18px; margin-bottom:20px;">⚡ Catat Transaksi</h3>

            <form wire:submit="save">
                {{-- Transaction Type Tabs --}}
                <div style="display:flex; gap:4px; margin-bottom:20px; background:var(--color-paper-2); border-radius:var(--radius-md); padding:4px;">
                    @foreach(['expense' => '💸 Pengeluaran', 'income' => '💰 Pemasukan', 'bill' => '📋 Tagihan', 'saving' => '🎯 Tabungan'] as $val => $label)
                        <button type="button"
                                wire:click="$set('type', '{{ $val }}')"
                                style="flex:1; padding:8px 4px; font-size:12px; font-weight:600; border:none; border-radius:6px; cursor:pointer; transition:all 0.15s;
                                {{ $type === $val
                                    ? 'background:#fff; color:var(--color-ink); box-shadow:0 1px 3px rgba(0,0,0,0.08);'
                                    : 'background:transparent; color:var(--color-ink-3);' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Description --}}
                <div style="margin-bottom:16px;">
                    <label for="qe-desc" class="form-label">Keterangan</label>
                    <input type="text"
                           id="qe-desc"
                           wire:model="description"
                           class="form-input"
                           placeholder="Mis. Makan siang di kantin"
                           autofocus>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Amount --}}
                <div style="margin-bottom:16px;">
                    <label for="qe-amount" class="form-label">Jumlah (Rp)</label>
                    <input type="number"
                           id="qe-amount"
                           wire:model="amount"
                           class="form-input"
                           placeholder="0"
                           min="1"
                           step="1"
                           style="font-family:var(--font-mono); font-size:18px; font-weight:600;">
                    @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Category --}}
                <div style="margin-bottom:16px;">
                    <label for="qe-category" class="form-label">Kategori</label>
                    <select id="qe-category"
                            wire:model="category_id"
                            class="form-select">
                        <option value="">— Pilih Kategori —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Savings Goal (only for type=saving) --}}
                @if($type === 'saving')
                    <div style="margin-bottom:16px;">
                        <label for="qe-savings-goal" class="form-label">Target Tabungan</label>
                        <select id="qe-savings-goal"
                                wire:model="savings_goal_id"
                                class="form-select">
                            <option value="">— Pilih Target —</option>
                            @foreach($savingsGoals as $goal)
                                <option value="{{ $goal->id }}">
                                    {{ $goal->goal_name }} ({{ number_format($goal->current_saved, 0, ',', '.') }} / {{ number_format($goal->target_amount, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        @error('savings_goal_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Transaction Date --}}
                <div style="margin-bottom:16px;">
                    <label for="qe-date" class="form-label">Tanggal</label>
                    <input type="date"
                           id="qe-date"
                           wire:model="transaction_date"
                           class="form-input">
                    @error('transaction_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Due Date (only for bills) --}}
                @if($type === 'bill')
                    <div style="margin-bottom:16px;">
                        <label for="qe-due" class="form-label">Jatuh Tempo</label>
                        <input type="date"
                               id="qe-due"
                               wire:model="due_date"
                               class="form-input">
                        @error('due_date') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Notes (collapsible) --}}
                <div style="margin-bottom:20px;" x-data="{ showNotes: false }">
                    <button type="button" @click="showNotes = !showNotes"
                            style="font-size:12px; color:var(--color-ink-3); background:none; border:none; cursor:pointer; display:flex; align-items:center; gap:4px;">
                        <span x-text="showNotes ? '▾' : '▸'"></span>
                        Catatan tambahan
                    </button>
                    <div x-show="showNotes" x-transition style="margin-top:8px;">
                        <textarea wire:model="notes"
                                  class="form-input"
                                  rows="2"
                                  placeholder="Catatan opsional..."></textarea>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn btn-forest" style="width:100%; justify-content:center;">
                    <span wire:loading.remove wire:target="save">💾 Simpan Transaksi</span>
                    <span wire:loading wire:target="save">
                        <span style="display:flex; align-items:center; gap:6px; justify-content:center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="animation: spin 1s linear infinite;">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10" stroke-linecap="round"/>
                            </svg>
                            Menyimpan...
                        </span>
                    </span>
                </button>
            </form>
        </div>

        {{-- Recent Transactions --}}
        <div>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <h3 style="font-size:18px;">📜 Transaksi Terakhir</h3>
                <span class="badge badge-neutral">{{ count($recentTransactions) }} entri</span>
            </div>

            @if(count($recentTransactions) === 0)
                <div class="card" style="text-align:center; padding:48px 24px;">
                    <div style="font-size:48px; margin-bottom:12px;">📝</div>
                    <p style="color:var(--color-ink-3); font-size:14px;">Belum ada transaksi. Mulai catat pengeluaranmu!</p>
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach($recentTransactions as $tx)
                        <div class="card" style="padding:16px 20px; display:flex; align-items:center; gap:14px;" wire:key="tx-{{ $tx['id'] }}">
                            {{-- Icon --}}
                            <div style="width:40px; height:40px; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; font-size:20px;
                                {{ $tx['type'] === 'income' ? 'background:var(--color-forest-bg);' :
                                   ($tx['type'] === 'expense' ? 'background:var(--color-gold-bg);' :
                                   ($tx['type'] === 'bill' ? 'background:var(--color-blush-bg);' :
                                   'background:var(--color-violet-bg);')) }}">
                                {{ $tx['category_icon'] }}
                            </div>

                            {{-- Details --}}
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:14px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $tx['description'] }}
                                </div>
                                <div style="font-size:11px; color:var(--color-ink-4); display:flex; gap:8px; margin-top:2px;">
                                    <span>{{ $tx['category_name'] }}</span>
                                    <span>·</span>
                                    <span>{{ $tx['transaction_date'] }}</span>
                                    @if($tx['type'] === 'bill')
                                        <span>·</span>
                                        @if($tx['status'] === 'paid')
                                            <span class="badge badge-success" style="font-size:9px; padding:1px 6px;">✓ Lunas</span>
                                        @elseif($tx['status'] === 'overdue')
                                            <span class="badge badge-danger" style="font-size:9px; padding:1px 6px;">⚠ Overdue</span>
                                        @else
                                            <span class="badge badge-warning" style="font-size:9px; padding:1px 6px;">⏳ Pending</span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            {{-- Amount --}}
                            <div style="text-align:right;">
                                <div style="font-family:var(--font-mono); font-size:15px; font-weight:600;
                                    {{ $tx['type'] === 'income' ? 'color:var(--color-forest);' :
                                       ($tx['type'] === 'expense' ? 'color:var(--color-warning);' :
                                       ($tx['type'] === 'bill' ? 'color:var(--color-blush);' :
                                       'color:var(--color-violet);')) }}">
                                    {{ $tx['type'] === 'income' ? '+' : '-' }}Rp {{ number_format($tx['amount'], 0, ',', '.') }}
                                </div>
                                <div class="text-label" style="color:var(--color-ink-4); font-size:9px;">{{ strtoupper($tx['type']) }}</div>
                            </div>

                            {{-- Delete --}}
                            <button wire:click="deleteTransaction({{ $tx['id'] }})"
                                    wire:confirm="Yakin hapus transaksi ini?"
                                    style="padding:6px; border:none; background:none; cursor:pointer; color:var(--color-ink-4); border-radius:var(--radius-sm); transition:all 0.15s;"
                                    onmouseover="this.style.color='var(--color-danger)';this.style.background='var(--color-blush-bg)'"
                                    onmouseout="this.style.color='var(--color-ink-4)';this.style.background='none'"
                                    title="Hapus">
                                🗑️
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 768px) {
            div[style*="grid-template-columns:1fr 1.2fr"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</div>
