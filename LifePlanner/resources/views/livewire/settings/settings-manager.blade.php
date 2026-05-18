<div>
    <x-slot:header>Pengaturan & Utilitas</x-slot:header>
    <x-slot:subtitle>Sistem · Kelola kategori kustom dan ekspor data cadangan</x-slot:subtitle>

    {{-- Tabs --}}
    <div style="display:flex; gap:8px; margin-bottom:24px; border-bottom:1px solid var(--color-paper-3); padding-bottom:8px;">
        <button wire:click="selectTab('categories')"
                style="padding:10px 18px; font-weight:600; font-size:14px; border:none; background:none; cursor:pointer; border-radius:var(--radius-md); transition:all 0.15s;
                {{ $activeTab === 'categories'
                    ? 'background:var(--color-ink); color:#fff;'
                    : 'color:var(--color-ink-3);' }}">
            ⚙️ Kelola Kategori
        </button>
        <button wire:click="selectTab('export')"
                style="padding:10px 18px; font-weight:600; font-size:14px; border:none; background:none; cursor:pointer; border-radius:var(--radius-md); transition:all 0.15s;
                {{ $activeTab === 'export'
                    ? 'background:var(--color-ink); color:#fff;'
                    : 'color:var(--color-ink-3);' }}">
            💾 Ekspor Data CSV
        </button>
    </div>

    {{-- Content Areas --}}
    @if($activeTab === 'categories')
        <div style="display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start;">
            {{-- Left Column: Category List --}}
            <div>
                {{-- Type Filters --}}
                <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:16px; background:var(--color-paper-2); padding:6px; border-radius:var(--radius-md);">
                    @foreach([
                        'expense' => '💸 Pengeluaran',
                        'income' => '💰 Pemasukan',
                        'bill' => '📋 Tagihan',
                        'saving' => '🎯 Tabungan',
                        'task' => '✅ Task',
                        'grocery' => '🛒 Grocery'
                    ] as $type => $label)
                        <button wire:click="$set('filterType', '{{ $type }}')"
                                style="padding:6px 12px; font-size:12px; font-weight:600; border:none; border-radius:6px; cursor:pointer; transition:all 0.15s;
                                {{ $filterType === $type
                                    ? 'background:#fff; color:var(--color-ink); box-shadow:0 1px 3px rgba(0,0,0,0.08);'
                                    : 'background:transparent; color:var(--color-ink-3);' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Category Grid --}}
                @if($categories->isEmpty())
                    <div class="card" style="text-align:center; padding:48px 16px;">
                        <div style="font-size:40px; margin-bottom:12px;">📦</div>
                        <p style="color:var(--color-ink-3); font-size:14px;">Belum ada kategori kustom untuk tipe ini.</p>
                    </div>
                @else
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:12px;">
                        @foreach($categories as $cat)
                            <div class="card" style="padding:14px 18px; display:flex; align-items:center; justify-content:space-between;" wire:key="cat-{{ $cat->id }}">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="font-size:22px;">{{ $cat->icon }}</span>
                                    <span style="font-size:14px; font-weight:600; color:var(--color-ink);">{{ $cat->name }}</span>
                                </div>
                                <div style="display:flex; gap:4px;">
                                    <button wire:click="startEditCategory({{ $cat->id }})"
                                            style="border:none; background:none; cursor:pointer; padding:4px; font-size:13px;"
                                            title="Edit">
                                        ✏️
                                    </button>
                                    <button wire:click="deleteCategory({{ $cat->id }})"
                                            wire:confirm="Yakin ingin menghapus kategori ini?"
                                            style="border:none; background:none; cursor:pointer; padding:4px; font-size:13px;"
                                            title="Hapus">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Right Column: Add/Edit Form --}}
            <div>
                <div class="card">
                    <h3 style="font-size:16px; margin-bottom:16px;">
                        {{ $editingCategoryId ? '✏️ Edit Kategori' : '➕ Tambah Kategori' }}
                    </h3>

                    <form wire:submit="saveCategory">
                        {{-- Name --}}
                        <div style="margin-bottom:14px;">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" wire:model="categoryName" class="form-input" placeholder="Mis. Kopi, Buku, Listrik">
                            @error('categoryName') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Type --}}
                        <div style="margin-bottom:14px;">
                            <label class="form-label">Tipe Modul</label>
                            <select wire:model="categoryType" class="form-select">
                                <option value="expense">💸 Pengeluaran</option>
                                <option value="income">💰 Pemasukan</option>
                                <option value="bill">📋 Tagihan</option>
                                <option value="saving">🎯 Tabungan</option>
                                <option value="task">✅ Tugas (Kanban)</option>
                                <option value="grocery">🛒 Grocery List</option>
                            </select>
                            @error('categoryType') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Icon/Emoji --}}
                        <div style="margin-bottom:20px;">
                            <label class="form-label">Emoji Icon</label>
                            <div style="display:grid; grid-template-columns:1fr auto; gap:8px; align-items:center;">
                                <input type="text" wire:model="categoryIcon" class="form-input" placeholder="📦" maxlength="5">
                                <span style="font-size:12px; color:var(--color-ink-4);">Gunakan picker emoji</span>
                            </div>
                            @error('categoryIcon') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div style="display:flex; gap:8px;">
                            <button type="submit" class="btn btn-forest" style="flex:1;">
                                {{ $editingCategoryId ? 'Update' : 'Simpan' }}
                            </button>
                            @if($editingCategoryId)
                                <button type="button" wire:click="resetCategoryForm" class="btn btn-secondary">
                                    Batal
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'export')
        <div class="card" style="max-width:680px; margin:0 auto;">
            <h3 style="font-size:18px; margin-bottom:16px;">💾 Cadangkan Data SIM Anda</h3>
            <p style="color:var(--color-ink-3); font-size:13px; margin-bottom:24px; line-height:1.5;">
                Ekspor semua data transaksi keuangan, log berat badan harian, dan daftar tugas Kanban Anda ke format file CSV standar yang bersih. Anda dapat membukanya dengan Microsoft Excel, Google Sheets, atau aplikasi Spreadsheet lainnya.
            </p>

            <div style="display:flex; flex-direction:column; gap:12px;">
                {{-- Finance --}}
                <div style="display:flex; align-items:center; justify-content:space-between; padding:16px; background:var(--color-paper-2); border-radius:var(--radius-md);">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="font-size:24px;">💸</span>
                        <div>
                            <div style="font-weight:700; font-size:14px; color:var(--color-ink);">Log Transaksi Keuangan</div>
                            <div style="font-size:11px; color:var(--color-ink-4);">Pengeluaran, pemasukan, tagihan, tabungan</div>
                        </div>
                    </div>
                    <button wire:click="exportTransactions" class="btn btn-sm btn-forest">📥 Ekspor CSV</button>
                </div>

                {{-- Weight --}}
                <div style="display:flex; align-items:center; justify-content:space-between; padding:16px; background:var(--color-paper-2); border-radius:var(--radius-md);">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="font-size:24px;">⚖️</span>
                        <div>
                            <div style="font-weight:700; font-size:14px; color:var(--color-ink);">Tren Berat Badan</div>
                            <div style="font-size:11px; color:var(--color-ink-4);">Log harian berat badan dan body fat</div>
                        </div>
                    </div>
                    <button wire:click="exportWeightLogs" class="btn btn-sm btn-forest">📥 Ekspor CSV</button>
                </div>

                {{-- Todo --}}
                <div style="display:flex; align-items:center; justify-content:space-between; padding:16px; background:var(--color-paper-2); border-radius:var(--radius-md);">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="font-size:24px;">✅</span>
                        <div>
                            <div style="font-weight:700; font-size:14px; color:var(--color-ink);">Tugas & Prioritas Kanban</div>
                            <div style="font-size:11px; color:var(--color-ink-4);">Daftar task, prioritas, status kolumnar</div>
                        </div>
                    </div>
                    <button wire:click="exportTodos" class="btn btn-sm btn-forest">📥 Ekspor CSV</button>
                </div>
            </div>
        </div>
    @endif
</div>
