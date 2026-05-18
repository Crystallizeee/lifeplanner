<div>
    <x-slot:header>Meal Planner</x-slot:header>
    <x-slot:subtitle>Kesehatan · Rencana makan mingguan · {{ $totalCaloriesToday ? number_format($totalCaloriesToday) . ' kcal hari ini' : 'Belum ada data hari ini' }}</x-slot:subtitle>

    {{-- Week Navigation --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <button wire:click="previousWeek" class="btn btn-sm btn-secondary">← Minggu Lalu</button>
            <span style="font-family:var(--font-mono); font-size:13px; color:var(--color-ink-3);">
                {{ $startOfWeek->format('d M') }} — {{ $startOfWeek->copy()->addDays(6)->format('d M Y') }}
            </span>
            @if($weekOffset < 0)
                <button wire:click="nextWeek" class="btn btn-sm btn-secondary">Minggu Depan →</button>
            @endif
        </div>
        <button class="btn btn-sm {{ $showForm ? 'btn-danger' : 'btn-forest' }}" wire:click="$toggle('showForm')">
            {{ $showForm ? '✕ Tutup' : '+ Tambah Menu' }}
        </button>
    </div>

    {{-- Add Meal Form --}}
    @if($showForm)
        <div class="card" style="margin-bottom:20px; border-color:var(--color-gold-light); background:var(--color-gold-bg);">
            <form wire:submit="addMeal" style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:12px; align-items:end;">
                <div>
                    <label class="form-label">Tanggal</label>
                    <input type="date" wire:model="date" class="form-input">
                </div>
                <div>
                    <label class="form-label">Waktu Makan</label>
                    <select wire:model="meal_type" class="form-select">
                        @foreach($mealTypes as $key => $mt)
                            <option value="{{ $key }}">{{ $mt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Menu</label>
                    <input type="text" wire:model="meal_name" class="form-input" placeholder="Mis. Nasi goreng">
                    @error('meal_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Kalori</label>
                    <input type="number" wire:model="calories" class="form-input" placeholder="500" min="0">
                </div>
            </form>
            <div style="display:flex; gap:12px; align-items:end; margin-top:12px;">
                <div style="flex:1;">
                    <label class="form-label">Catatan Resep</label>
                    <input type="text" wire:model="recipe_notes" class="form-input" placeholder="Opsional...">
                </div>
                <button wire:click="addMeal" class="btn btn-forest btn-sm">Simpan</button>
            </div>
        </div>
    @endif

    {{-- Weekly Grid --}}
    <div style="display:grid; grid-template-columns:repeat(7, 1fr); gap:8px; overflow-x:auto;">
        @foreach($weekDates as $date)
            @php $dateStr = $date->format('Y-m-d'); @endphp
            <div style="min-width:150px; border-radius:var(--radius-base); overflow:hidden; border:1px solid {{ $date->isToday() ? 'var(--color-forest-light)' : 'var(--color-paper-3)' }};">
                {{-- Day Header --}}
                <div style="padding:10px; text-align:center; font-size:12px; font-weight:700;
                    {{ $date->isToday() ? 'background:var(--color-forest); color:#fff;' : 'background:var(--color-paper-2); color:var(--color-ink-3);' }}">
                    <div>{{ $date->locale('id')->isoFormat('dddd') }}</div>
                    <div style="font-family:var(--font-mono); font-size:14px;">{{ $date->format('d M') }}</div>
                </div>

                {{-- Meal Slots --}}
                <div style="padding:8px;">
                    @foreach($mealTypes as $type => $meta)
                        @php $meal = ($meals[$dateStr] ?? collect())->firstWhere('meal_type', $type); @endphp
                        <div style="padding:6px 8px; margin-bottom:4px; border-radius:var(--radius-sm); font-size:11px; min-height:36px;
                            background:{{ $meal ? $meta['color'] : 'var(--color-paper-2)' }};">
                            @if($meal)
                                <div style="font-weight:600; font-size:11px;">{{ Str::limit($meal->meal_name, 20) }}</div>
                                @if($meal->calories)
                                    <div style="font-family:var(--font-mono); font-size:9px; color:var(--color-ink-4);">{{ $meal->calories }} kcal</div>
                                @endif
                            @else
                                <div style="color:var(--color-ink-4); font-size:10px;">{{ explode(' ', $meta['label'])[0] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
