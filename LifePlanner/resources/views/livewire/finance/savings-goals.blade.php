<div>
    <x-slot:header>Savings Goals</x-slot:header>
    <x-slot:subtitle>Keuangan · Target tabungan & tracking progress</x-slot:subtitle>

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
        <div class="card-stat card-stat--violet">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Total Target</div>
            <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-violet);">
                Rp {{ number_format($totalTarget, 0, ',', '.') }}
            </div>
        </div>
        <div class="card-stat card-stat--forest">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Total Terkumpul</div>
            <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-forest);">
                Rp {{ number_format($totalSaved, 0, ',', '.') }}
            </div>
        </div>
        <div class="card-stat card-stat--gold">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Tercapai</div>
            <div style="font-size:22px; font-family:var(--font-serif); color:var(--color-gold);">
                {{ $achievedCount }} / {{ $goals->count() }}
            </div>
        </div>
    </div>

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <h3 style="font-size:18px;">🎯 Daftar Target</h3>
        <button class="btn btn-sm {{ $showForm ? 'btn-danger' : 'btn-forest' }}" wire:click="$toggle('showForm')">
            {{ $showForm ? '✕ Tutup' : '+ Tambah Target' }}
        </button>
    </div>

    {{-- Add Form --}}
    @if($showForm)
        <div class="card" style="margin-bottom:20px; border-color:var(--color-violet-light); background:var(--color-violet-bg);">
            <form wire:submit="addGoal" style="display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:12px; align-items:end;">
                <div>
                    <label class="form-label">Nama Target</label>
                    <input type="text" wire:model="goal_name" class="form-input" placeholder="Mis. iPhone 16 Pro">
                    @error('goal_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Target (Rp)</label>
                    <input type="number" wire:model="target_amount" class="form-input" placeholder="0" min="1">
                    @error('target_amount') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Target Tanggal</label>
                    <input type="date" wire:model="target_date" class="form-input">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
            </form>
        </div>
    @endif

    {{-- Goals List --}}
    @if($goals->count() === 0)
        <div class="card" style="text-align:center; padding:48px;">
            <div style="font-size:48px; margin-bottom:12px;">🎯</div>
            <p style="color:var(--color-ink-3);">Belum ada target tabungan. Ayo mulai menabung!</p>
        </div>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:16px;">
            @foreach($goals as $goal)
                <div class="card" style="padding:20px; {{ $goal->is_achieved ? 'border-color:var(--color-forest-light); background:var(--color-forest-bg);' : '' }}" wire:key="goal-{{ $goal->id }}">
                    <div style="display:flex; align-items:start; justify-content:space-between; margin-bottom:12px;">
                        <div>
                            <div style="font-size:16px; font-weight:700;">
                                {{ $goal->is_achieved ? '🏆' : '🎯' }} {{ $goal->goal_name }}
                            </div>
                            @if($goal->target_date)
                                <div style="font-size:11px; color:var(--color-ink-4); font-family:var(--font-mono); margin-top:2px;">
                                    Target: {{ $goal->target_date->format('d M Y') }}
                                </div>
                            @endif
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            @if($goal->is_achieved)
                                <span class="badge badge-success">✓ Tercapai!</span>
                            @endif
                            <button wire:click="deleteGoal({{ $goal->id }})"
                                    wire:confirm="Hapus target ini?"
                                    style="padding:4px; border:none; background:none; cursor:pointer; color:var(--color-ink-4);" title="Hapus">🗑️</button>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span style="font-family:var(--font-mono); font-size:18px; font-weight:700; color:var(--color-forest);">
                            Rp {{ number_format($goal->current_saved, 0, ',', '.') }}
                        </span>
                        <span style="font-family:var(--font-mono); font-size:13px; color:var(--color-ink-3);">
                            / Rp {{ number_format($goal->target_amount, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Progress --}}
                    <div style="height:8px; background:var(--color-paper-3); border-radius:var(--radius-pill); overflow:hidden; margin-bottom:6px;">
                        <div style="height:100%; border-radius:var(--radius-pill); transition:width 0.5s ease;
                            width:{{ $goal->progress_percent }}%;
                            background: {{ $goal->is_achieved ? 'var(--color-forest)' : 'var(--color-violet-light)' }};">
                        </div>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                        <span style="font-size:10px; color:var(--color-ink-3); font-weight:600; font-family:var(--font-mono); letter-spacing:0.02em;">
                            {{ $goal->projection_text }}
                        </span>
                        <span style="font-family:var(--font-mono); font-size:12px; font-weight:600;
                            color:{{ $goal->is_achieved ? 'var(--color-forest)' : 'var(--color-violet)' }};">
                            {{ $goal->progress_percent }}%
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
