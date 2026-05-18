<div>
    <x-slot:header>Budget Overview</x-slot:header>
    <x-slot:subtitle>Keuangan · Kelola anggaran periode bulan ini</x-slot:subtitle>

    {{-- Budget Summary Cards --}}
    @if($activeBudget)
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
            {{-- Period --}}
            <div class="card-stat card-stat--forest">
                <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Periode Aktif</div>
                <div style="font-size:16px; font-family:var(--font-serif);">
                    {{ $activeBudget->period_start->format('d M') }} — {{ $activeBudget->period_end->format('d M Y') }}
                </div>
                <div style="font-size:11px; color:var(--color-ink-4); margin-top:2px; font-family:var(--font-mono);">
                    Saldo awal: Rp {{ number_format($activeBudget->starting_balance, 0, ',', '.') }}
                </div>
            </div>

            {{-- Total Income --}}
            <div class="card-stat card-stat--forest">
                <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Total Pemasukan</div>
                <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-forest);">
                    Rp {{ number_format($totalIncome, 0, ',', '.') }}
                </div>
            </div>

            {{-- Total Expense --}}
            <div class="card-stat card-stat--gold">
                <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Total Pengeluaran</div>
                <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-warning);">
                    Rp {{ number_format($totalExpense, 0, ',', '.') }}
                </div>
            </div>

            {{-- Total Allocated --}}
            <div class="card-stat card-stat--violet">
                <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Total Dialokasikan</div>
                <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-violet);">
                    Rp {{ number_format($totalAllocated, 0, ',', '.') }}
                </div>
            </div>

            {{-- Savings Health Rate --}}
            <div class="card-stat" style="border-left:4px solid {{ $savingsRateColor }}; background:var(--color-paper-2);">
                <div class="text-label" style="color:var(--color-ink-4); margin-bottom:4px;">Kesehatan Finansial (Savings Rate)</div>
                <div style="font-size:18px; font-weight:700; color:{{ $savingsRateColor }};">
                    {{ $savingsRateHealth }} ({{ $savingsRate }}%)
                </div>
                <div style="font-size:10px; color:var(--color-ink-3); margin-top:2px; line-height:1.3;">
                    {{ $savingsRateDescription }}
                </div>
            </div>
        </div>
    @endif

    <div style="display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:start;">

        {{-- Left: Allocations --}}
        <div>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <h3 style="font-size:18px;">📊 Alokasi Anggaran</h3>
                @if($activeBudget)
                    <button class="btn btn-secondary btn-sm" wire:click="$toggle('showAllocationForm')">
                        {{ $showAllocationForm ? '✕ Tutup' : '+ Tambah Alokasi' }}
                    </button>
                @endif
            </div>

            {{-- Allocation Form --}}
            @if($showAllocationForm && $activeBudget)
                <div class="card" style="margin-bottom:16px; border-color:var(--color-forest-light); background:var(--color-forest-bg);">
                    <form wire:submit="addAllocation" style="display:grid; grid-template-columns:1fr 1fr auto; gap:12px; align-items:end;">
                        <div>
                            <label class="form-label">Kategori</label>
                            <select wire:model="alloc_category_id" class="form-select">
                                <option value="">— Pilih —</option>
                                @foreach($expenseCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('alloc_category_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Anggaran (Rp)</label>
                            <input type="number" wire:model="alloc_amount" class="form-input" placeholder="0" min="0">
                            @error('alloc_amount') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn btn-forest btn-sm">Simpan</button>
                    </form>
                </div>
            @endif

            {{-- Allocation Cards --}}
            @if(count($allocations) === 0)
                <div class="card" style="text-align:center; padding:40px;">
                    <div style="font-size:40px; margin-bottom:12px;">📊</div>
                    <p style="color:var(--color-ink-3); font-size:14px; margin-bottom:16px;">
                        @if($activeBudget)
                            Belum ada alokasi anggaran. Tambahkan kategori pengeluaran yang ingin dianggarkan.
                        @else
                            Buat budget periode dulu untuk mulai mengatur alokasi.
                        @endif
                    </p>
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:10px;">
                    @foreach($allocations as $alloc)
                        <div class="card" style="padding:16px 20px;" wire:key="alloc-{{ $alloc['id'] }}">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="font-size:20px;">{{ $alloc['category_icon'] }}</span>
                                    <div>
                                        <div style="font-weight:600; font-size:14px;">{{ $alloc['category_name'] }}</div>
                                        <div style="font-size:11px; color:var(--color-ink-4); font-family:var(--font-mono);">
                                            Rp {{ number_format($alloc['spent'], 0, ',', '.') }} / Rp {{ number_format($alloc['allocated'], 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-family:var(--font-mono); font-size:13px; font-weight:700;
                                        {{ $alloc['percentage'] >= 90 ? 'color:var(--color-danger);' :
                                           ($alloc['percentage'] >= 70 ? 'color:var(--color-warning);' :
                                           'color:var(--color-forest);') }}">
                                        {{ $alloc['percentage'] }}%
                                    </span>
                                    <button wire:click="deleteAllocation({{ $alloc['id'] }})"
                                            wire:confirm="Hapus alokasi ini?"
                                            style="padding:4px; border:none; background:none; cursor:pointer; color:var(--color-ink-4); font-size:12px;"
                                            title="Hapus">🗑️</button>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div style="height:6px; background:var(--color-paper-3); border-radius:var(--radius-pill); overflow:hidden;">
                                <div style="height:100%; border-radius:var(--radius-pill); transition:width 0.5s ease;
                                    width:{{ $alloc['percentage'] }}%;
                                    {{ $alloc['percentage'] >= 90 ? 'background:var(--color-danger);' :
                                       ($alloc['percentage'] >= 70 ? 'background:var(--color-warning);' :
                                       'background:var(--color-forest-light);') }}">
                                </div>
                            </div>

                            {{-- Over-Budget Alert banner --}}
                            @if($alloc['remaining'] < 0)
                                <div style="margin-top:8px; padding:6px 10px; background:rgba(235, 94, 85, 0.08); border:1px solid rgba(235, 94, 85, 0.2); border-radius:6px; font-size:11px; color:var(--color-danger); font-weight:600; display:flex; align-items:center; gap:6px;">
                                    🚨 Anggaran terlampaui sebesar Rp {{ number_format(abs($alloc['remaining']), 0, ',', '.') }}!
                                </div>
                            @endif

                            {{-- Remaining --}}
                            <div style="display:flex; justify-content:space-between; margin-top:6px;">
                                <span style="font-size:11px; color:var(--color-ink-4);">Terpakai</span>
                                <span style="font-size:11px; font-family:var(--font-mono);
                                    {{ $alloc['remaining'] < 0 ? 'color:var(--color-danger); font-weight:700;' : 'color:var(--color-ink-3);' }}">
                                    Sisa: Rp {{ number_format($alloc['remaining'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Right: Budget Management --}}
        <div>
            {{-- Create Budget --}}
            <div class="card" style="margin-bottom:20px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                    <h3 style="font-size:16px;">🗓️ Budget Periode</h3>
                    <button class="btn btn-sm {{ $showCreateForm ? 'btn-danger' : 'btn-primary' }}"
                            wire:click="$toggle('showCreateForm')">
                        {{ $showCreateForm ? '✕ Batal' : '+ Buat Baru' }}
                    </button>
                </div>

                @if($showCreateForm)
                    <form wire:submit="createBudget">
                        <div style="margin-bottom:12px;">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" wire:model="period_start" class="form-input">
                            @error('period_start') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div style="margin-bottom:12px;">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" wire:model="period_end" class="form-input">
                            @error('period_end') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div style="margin-bottom:16px;">
                            <label class="form-label">Saldo Awal (Rp)</label>
                            <input type="number" wire:model="starting_balance" class="form-input" min="0">
                            @error('starting_balance') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn btn-forest" style="width:100%; justify-content:center;">
                            Buat Budget
                        </button>
                    </form>
                @else
                    @if($activeBudget)
                        <div style="padding:12px; background:var(--color-forest-bg); border-radius:var(--radius-md); text-align:center;">
                            <span class="badge badge-success">✓ Aktif</span>
                            <div style="margin-top:8px; font-family:var(--font-mono); font-size:12px; color:var(--color-ink-3);">
                                {{ $activeBudget->period_start->format('d M Y') }} — {{ $activeBudget->period_end->format('d M Y') }}
                            </div>
                        </div>
                    @else
                        <div style="text-align:center; padding:16px; color:var(--color-ink-3); font-size:13px;">
                            Belum ada budget aktif. Klik "Buat Baru" untuk memulai.
                        </div>
                    @endif
                @endif
            </div>

            {{-- Budget History --}}
            @if($allBudgets->count() > 0)
                <div class="card">
                    <h3 style="font-size:16px; margin-bottom:12px;">📚 Semua Budget</h3>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach($allBudgets as $budget)
                            <div style="padding:10px 12px; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:space-between;
                                {{ $budget->is_active ? 'background:var(--color-forest-bg); border:1px solid var(--color-forest-light);' : 'background:var(--color-paper-2);' }}" wire:key="budget-hist-{{ $budget->id }}">
                                <div>
                                    <div style="font-size:12px; font-weight:600;">
                                        {{ $budget->period_start->format('M Y') }}
                                    </div>
                                    <div style="font-size:11px; color:var(--color-ink-4); font-family:var(--font-mono);">
                                        {{ $budget->period_start->format('d M') }} — {{ $budget->period_end->format('d M') }}
                                    </div>
                                </div>
                                @if($budget->is_active)
                                    <span class="badge badge-success">✓ Aktif</span>
                                @else
                                    <button wire:click="activateBudget({{ $budget->id }})" class="btn btn-sm btn-secondary" style="font-size:10px; padding:3px 8px;">
                                        Aktifkan
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
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
