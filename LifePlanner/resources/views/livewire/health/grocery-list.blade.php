<div>
    <x-slot:header>Grocery List</x-slot:header>
    <x-slot:subtitle>Kesehatan · Daftar belanjaan mingguan</x-slot:subtitle>

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
        <div class="card-stat card-stat--forest">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Total Item</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-forest);">{{ $totalItems }}</div>
        </div>
        <div class="card-stat card-stat--gold">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Sudah Dibeli</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-gold);">{{ $checkedItems }}</div>
        </div>
        <div class="card-stat card-stat--violet">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Sisa</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-violet);">{{ $totalItems - $checkedItems }}</div>
        </div>
    </div>

    {{-- Top Bar --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-family:var(--font-mono); font-size:12px; color:var(--color-ink-3);">
                Minggu: {{ \Carbon\Carbon::parse($week_of)->format('d M Y') }}
            </span>
        </div>
        <div style="display:flex; gap:6px;">
            @if($checkedItems > 0)
                <button wire:click="clearChecked" wire:confirm="Hapus semua item yang sudah dicentang?" class="btn btn-sm btn-danger">
                    🧹 Bersihkan ({{ $checkedItems }})
                </button>
            @endif
            <button class="btn btn-sm {{ $showForm ? 'btn-danger' : 'btn-forest' }}" wire:click="$toggle('showForm')">
                {{ $showForm ? '✕ Tutup' : '+ Tambah Item' }}
            </button>
        </div>
    </div>

    {{-- Add Item Form --}}
    @if($showForm)
        <div class="card" style="margin-bottom:20px; border-color:var(--color-forest-light); background:var(--color-forest-bg);">
            <form wire:submit="addItem" style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr auto; gap:12px; align-items:end;">
                <div>
                    <label class="form-label">Nama Item</label>
                    <input type="text" wire:model="item_name" class="form-input" placeholder="Mis. Telur ayam" autofocus>
                    @error('item_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Jumlah</label>
                    <input type="number" wire:model="qty" class="form-input" placeholder="2" step="0.1" min="0.1">
                </div>
                <div>
                    <label class="form-label">Satuan</label>
                    <select wire:model="unit" class="form-select">
                        <option value="">—</option>
                        <option value="pcs">pcs</option>
                        <option value="kg">kg</option>
                        <option value="gram">gram</option>
                        <option value="liter">liter</option>
                        <option value="pack">pack</option>
                        <option value="bungkus">bungkus</option>
                        <option value="botol">botol</option>
                        <option value="dus">dus</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Kategori</label>
                    <select wire:model="category" class="form-select">
                        <option value="">Umum</option>
                        <option value="Sayuran">🥬 Sayuran</option>
                        <option value="Buah">🍎 Buah</option>
                        <option value="Protein">🥩 Protein</option>
                        <option value="Dairy">🧀 Dairy</option>
                        <option value="Bumbu">🌶️ Bumbu</option>
                        <option value="Snack">🍪 Snack</option>
                        <option value="Minuman">🥤 Minuman</option>
                        <option value="Kebutuhan Rumah">🏠 Kebutuhan Rumah</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-forest btn-sm">Tambah</button>
            </form>
        </div>
    @endif

    {{-- Grocery Items Grouped by Category --}}
    @if($totalItems === 0)
        <div class="card" style="text-align:center; padding:48px;">
            <div style="font-size:48px; margin-bottom:12px;">🛒</div>
            <p style="color:var(--color-ink-3);">Belum ada item. Mulai buat daftar belanjaan mingguan!</p>
        </div>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:16px;">
            @foreach($grouped as $categoryName => $items)
                <div class="card" style="padding:0; overflow:hidden;">
                    <div style="padding:10px 16px; background:var(--color-paper-2); border-bottom:1px solid var(--color-paper-3); font-size:13px; font-weight:700; color:var(--color-ink-3);">
                        {{ $categoryName ?: 'Umum' }}
                        <span class="badge badge-neutral" style="font-size:10px; margin-left:6px;">{{ $items->count() }}</span>
                    </div>
                    <div style="padding:8px;">
                        @foreach($items as $item)
                            <div style="display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:var(--radius-sm);
                                {{ $item->is_checked ? 'opacity:0.5;' : '' }}" wire:key="grocery-{{ $item->id }}">
                                <input type="checkbox"
                                       {{ $item->is_checked ? 'checked' : '' }}
                                       wire:click="toggleItem({{ $item->id }})"
                                       style="width:16px; height:16px; accent-color:var(--color-forest); cursor:pointer;">
                                <span style="flex:1; font-size:13px; {{ $item->is_checked ? 'text-decoration:line-through; color:var(--color-ink-4);' : '' }}">
                                    {{ $item->item_name }}
                                </span>
                                @if($item->qty)
                                    <span style="font-family:var(--font-mono); font-size:11px; color:var(--color-ink-3); background:var(--color-paper-2); padding:2px 6px; border-radius:var(--radius-pill);">
                                        {{ $item->qty }} {{ $item->unit }}
                                    </span>
                                @endif
                                <button wire:click="deleteItem({{ $item->id }})"
                                        style="padding:2px; border:none; background:none; cursor:pointer; color:var(--color-ink-4); font-size:12px;" title="Hapus">🗑️</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Progress Bar --}}
    @if($totalItems > 0)
        <div style="margin-top:24px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                <span style="font-size:12px; color:var(--color-ink-3);">Progress belanja</span>
                <span style="font-family:var(--font-mono); font-size:12px; font-weight:600; color:var(--color-forest);">
                    {{ $totalItems > 0 ? round(($checkedItems / $totalItems) * 100) : 0 }}%
                </span>
            </div>
            <div style="height:8px; background:var(--color-paper-3); border-radius:var(--radius-pill); overflow:hidden;">
                <div style="height:100%; border-radius:var(--radius-pill); background:var(--color-forest); transition:width 0.5s ease;
                    width:{{ $totalItems > 0 ? round(($checkedItems / $totalItems) * 100) : 0 }}%;"></div>
            </div>
        </div>
    @endif
</div>
