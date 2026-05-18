<div>
    <x-slot:header>Bill Tracker</x-slot:header>
    <x-slot:subtitle>Keuangan · Kelola tagihan & jatuh tempo</x-slot:subtitle>

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
        <div class="card-stat card-stat--blush">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Pending</div>
            <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-blush);">
                Rp {{ number_format($totalPending, 0, ',', '.') }}
            </div>
        </div>
        <div class="card-stat card-stat--forest">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Sudah Dibayar</div>
            <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-forest);">
                Rp {{ number_format($totalPaid, 0, ',', '.') }}
            </div>
        </div>
        <div class="card-stat card-stat--gold">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Overdue</div>
            <div style="font-size:22px; font-family:var(--font-serif); color:{{ $overdueCount > 0 ? 'var(--color-danger)' : 'var(--color-forest)' }};">
                {{ $overdueCount }} tagihan
            </div>
        </div>
    </div>

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <h3 style="font-size:18px;">📋 Daftar Tagihan</h3>
        <button class="btn btn-sm {{ $showForm ? 'btn-danger' : 'btn-forest' }}" wire:click="$toggle('showForm')">
            {{ $showForm ? '✕ Tutup' : '+ Tambah Tagihan' }}
        </button>
    </div>

    {{-- Add Bill Form --}}
    @if($showForm)
        <div class="card" style="margin-bottom:20px; border-color:var(--color-blush-light); background:var(--color-blush-bg);">
            <form wire:submit="addBill" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; align-items:end;">
                <div>
                    <label class="form-label">Keterangan</label>
                    <input type="text" wire:model="description" class="form-input" placeholder="Mis. Listrik bulan Mei">
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Jumlah (Rp)</label>
                    <input type="number" wire:model="amount" class="form-input" placeholder="0" min="1">
                    @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Kategori</label>
                    <select wire:model="category_id" class="form-select">
                        <option value="">— Pilih —</option>
                        @foreach($billCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Jatuh Tempo</label>
                    <input type="date" wire:model="due_date" class="form-input">
                    @error('due_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Catatan</label>
                    <input type="text" wire:model="notes" class="form-input" placeholder="Opsional">
                </div>
                <div style="display:flex; align-items:center; gap:8px; padding-bottom:8px;">
                    <input type="checkbox" id="add-recurring" wire:model="is_recurring" style="width:18px; height:18px; cursor:pointer;">
                    <label for="add-recurring" style="font-size:12px; font-weight:600; color:var(--color-ink); cursor:pointer;">🔁 Ulangi Bulanan</label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">Simpan</button>
            </form>
        </div>
    @endif

    {{-- Bills List --}}
    @if($bills->count() === 0)
        <div class="card" style="text-align:center; padding:48px;">
            <div style="font-size:48px; margin-bottom:12px;">📋</div>
            <p style="color:var(--color-ink-3);">Belum ada tagihan. Tambahkan tagihan rutin Anda!</p>
        </div>
    @else
        <div style="display:flex; flex-direction:column; gap:8px;">
            @foreach($bills as $bill)
                <div class="card" style="padding:16px 20px; display:flex; align-items:center; gap:14px;" wire:key="bill-{{ $bill->id }}">
                    <div style="width:40px; height:40px; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; font-size:20px;
                        {{ $bill->status === 'paid' ? 'background:var(--color-forest-bg);' :
                           ($bill->status === 'overdue' ? 'background:var(--color-blush-bg);' :
                           'background:var(--color-gold-bg);') }}">
                        {{ $bill->category->icon ?? '📋' }}
                    </div>

                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="font-size:14px; font-weight:600;">{{ $bill->description }}</span>
                            @if($bill->is_recurring)
                                <span style="font-size:10px; background:var(--color-cream); color:var(--color-ink-2); padding:2px 6px; border-radius:4px; font-weight:600;">🔁 Rutin</span>
                            @endif
                        </div>
                        <div style="font-size:11px; color:var(--color-ink-4); display:flex; gap:8px; margin-top:2px;">
                            <span>{{ $bill->category->name ?? '-' }}</span>
                            <span>·</span>
                            <span>Jatuh tempo: {{ $bill->due_date->format('d M Y') }}</span>
                        </div>
                    </div>

                    <div style="font-family:var(--font-mono); font-size:15px; font-weight:600; color:var(--color-blush);">
                        Rp {{ number_format($bill->amount, 0, ',', '.') }}
                    </div>

                    <div style="display:flex; align-items:center; gap:6px;">
                        @if($bill->status === 'paid')
                            <span class="badge badge-success">✓ Lunas</span>
                        @elseif($bill->status === 'overdue')
                            <span class="badge badge-danger">⚠ Overdue</span>
                            <button wire:click="markPaid({{ $bill->id }})" class="btn btn-sm btn-forest" style="padding:4px 8px; font-size:11px;">
                                Bayar
                            </button>
                        @else
                            <span class="badge badge-warning">⏳ Pending</span>
                            <button wire:click="markPaid({{ $bill->id }})" class="btn btn-sm btn-forest" style="padding:4px 8px; font-size:11px;">
                                Bayar
                            </button>
                        @endif
                        <button wire:click="deleteBill({{ $bill->id }})"
                                wire:confirm="Hapus tagihan ini?"
                                style="padding:4px; border:none; background:none; cursor:pointer; color:var(--color-ink-4);" title="Hapus">🗑️</button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
