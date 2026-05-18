<div>
    <x-slot:header>Habit Matrix</x-slot:header>
    <x-slot:subtitle>Kesehatan · {{ $totalActiveHabits }} habit aktif · 🔥 Top streak: {{ $topStreak }} hari</x-slot:subtitle>

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
        <div class="card-stat card-stat--blush">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Habit Aktif</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-blush);">{{ $totalActiveHabits }}</div>
        </div>
        <div class="card-stat card-stat--gold">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Top Streak</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-gold);">{{ $topStreak }} 🔥</div>
        </div>
    </div>

    {{-- Week Navigation + Add Button --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
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
            {{ $showForm ? '✕ Tutup' : '+ Tambah Habit' }}
        </button>
    </div>

    {{-- Add Habit Form --}}
    @if($showForm)
        <div class="card" style="margin-bottom:20px; border-color:var(--color-blush-light); background:var(--color-blush-bg);">
            <form wire:submit="addHabit" style="display:flex; gap:12px; align-items:end;">
                <div style="width:80px;">
                    <label class="form-label">Emoji</label>
                    <input type="text" wire:model="emoji" class="form-input" style="text-align:center; font-size:20px;" maxlength="4">
                </div>
                <div style="flex:1;">
                    <label class="form-label">Nama Habit</label>
                    <input type="text" wire:model="habit_name" class="form-input" placeholder="Mis. Olahraga 30 menit" autofocus>
                    @error('habit_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn btn-forest btn-sm">Simpan</button>
            </form>
        </div>
    @endif

    {{-- Habit Matrix Grid --}}
    @if($habits->count() === 0)
        <div class="card" style="text-align:center; padding:48px;">
            <div style="font-size:48px; margin-bottom:12px;">🔥</div>
            <p style="color:var(--color-ink-3);">Belum ada habit. Mulai bangun kebiasaan baik!</p>
        </div>
    @else
        <div class="card" style="padding:0; overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="padding:12px 16px; text-align:left; font-size:12px; color:var(--color-ink-3); border-bottom:1px solid var(--color-paper-3); min-width:200px;">
                            HABIT
                        </th>
                        @foreach($weekDates as $date)
                            <th style="padding:12px 8px; text-align:center; font-size:11px; border-bottom:1px solid var(--color-paper-3); min-width:52px;
                                {{ $date->isToday() ? 'background:var(--color-forest-bg); font-weight:700; color:var(--color-forest);' : 'color:var(--color-ink-4);' }}">
                                <div>{{ $date->locale('id')->isoFormat('dd') }}</div>
                                <div style="font-family:var(--font-mono); font-size:14px; font-weight:600; color:var(--color-ink-2);">{{ $date->format('d') }}</div>
                            </th>
                        @endforeach
                        <th style="padding:12px 8px; text-align:center; font-size:11px; color:var(--color-ink-4); border-bottom:1px solid var(--color-paper-3); min-width:60px;">
                            STREAK
                        </th>
                        <th style="padding:12px 8px; border-bottom:1px solid var(--color-paper-3); width:40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($habits as $habit)
                        <tr wire:key="habit-row-{{ $habit->id }}">
                            <td style="padding:10px 16px; border-bottom:1px solid var(--color-paper-3);">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-size:18px;">{{ $habit->emoji }}</span>
                                    <span style="font-size:13px; font-weight:600;">{{ $habit->habit_name }}</span>
                                </div>
                            </td>
                            @foreach($weekDates as $date)
                                @php
                                    $dateStr = $date->format('Y-m-d');
                                    $isChecked = isset($logs[$habit->id]) && in_array($dateStr, $logs[$habit->id]);
                                    $isFuture = $date->isFuture();
                                @endphp
                                <td style="padding:6px; text-align:center; border-bottom:1px solid var(--color-paper-3);
                                    {{ $date->isToday() ? 'background:var(--color-forest-bg);' : '' }}">
                                    @if(!$isFuture)
                                        <button wire:click="toggleDay({{ $habit->id }}, '{{ $dateStr }}')"
                                                style="width:36px; height:36px; border-radius:var(--radius-md); border:2px solid {{ $isChecked ? 'var(--color-forest)' : 'var(--color-stone)' }};
                                                background:{{ $isChecked ? 'var(--color-forest)' : 'transparent' }};
                                                cursor:pointer; display:flex; align-items:center; justify-content:center; margin:0 auto;
                                                transition:all 0.15s ease;">
                                            @if($isChecked)
                                                <span style="color:#fff; font-size:16px;">✓</span>
                                            @endif
                                        </button>
                                    @else
                                        <div style="width:36px; height:36px; border-radius:var(--radius-md); border:2px dashed var(--color-paper-3); margin:0 auto;"></div>
                                    @endif
                                </td>
                            @endforeach
                            <td style="padding:6px; text-align:center; border-bottom:1px solid var(--color-paper-3);">
                                <span style="font-family:var(--font-mono); font-size:14px; font-weight:700;
                                    {{ $habit->current_streak > 0 ? 'color:var(--color-gold);' : 'color:var(--color-ink-4);' }}">
                                    {{ $habit->current_streak }}🔥
                                </span>
                            </td>
                            <td style="padding:6px; text-align:center; border-bottom:1px solid var(--color-paper-3);">
                                <div x-data="{ open: false }" style="position:relative;">
                                    <button @click="open = !open" style="border:none; background:none; cursor:pointer; color:var(--color-ink-4); font-size:14px;">⋮</button>
                                    <div x-show="open" @click.outside="open = false" x-transition
                                         style="position:absolute; right:0; top:100%; background:#fff; border:1px solid var(--color-paper-3); border-radius:var(--radius-md); box-shadow:0 4px 12px rgba(0,0,0,0.08); z-index:10; min-width:120px;">
                                        <button wire:click="archiveHabit({{ $habit->id }})" @click="open = false"
                                                style="display:block; width:100%; text-align:left; padding:8px 12px; border:none; background:none; cursor:pointer; font-size:12px; color:var(--color-ink-3);">
                                            📦 Arsipkan
                                        </button>
                                        <button wire:click="deleteHabit({{ $habit->id }})" wire:confirm="Hapus habit ini?" @click="open = false"
                                                style="display:block; width:100%; text-align:left; padding:8px 12px; border:none; background:none; cursor:pointer; font-size:12px; color:var(--color-danger);">
                                            🗑️ Hapus
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
