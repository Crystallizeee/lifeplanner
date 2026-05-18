<div>
    <x-slot:header>Goal Tracker</x-slot:header>
    <x-slot:subtitle>Produktivitas · {{ $activeCount }} aktif · {{ $completedCount }} selesai</x-slot:subtitle>

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
        <div class="card-stat card-stat--violet">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Goal Aktif</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-violet);">{{ $activeCount }}</div>
        </div>
        <div class="card-stat card-stat--forest">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Tercapai</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-forest);">{{ $completedCount }}</div>
        </div>
        <div class="card-stat card-stat--gold">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:6px;">Rata-rata Progress</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-gold);">{{ $avgProgress }}%</div>
        </div>
    </div>

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <h3 style="font-size:18px;">🏆 Daftar Goal</h3>
        <button class="btn btn-sm {{ $showForm ? 'btn-danger' : 'btn-forest' }}" wire:click="$toggle('showForm')">
            {{ $showForm ? '✕ Tutup' : '+ Tambah Goal' }}
        </button>
    </div>

    {{-- Add Goal Form --}}
    @if($showForm)
        <div class="card" style="margin-bottom:20px; border-color:var(--color-violet-light); background:var(--color-violet-bg);">
            <form wire:submit="addGoal">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label class="form-label">Judul Goal</label>
                        <input type="text" wire:model="title" class="form-input" placeholder="Mis. Belajar Laravel Testing" autofocus>
                        @error('title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Target Tanggal</label>
                        <input type="date" wire:model="due_date" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Mengapa ini penting?</label>
                        <input type="text" wire:model="why" class="form-input" placeholder="Motivasi utama...">
                    </div>
                    <div>
                        <label class="form-label">Hadiah untuk diri sendiri</label>
                        <input type="text" wire:model="reward" class="form-input" placeholder="Mis. Beli gadget baru">
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <label class="form-label">Tantangan yang mungkin dihadapi</label>
                    <input type="text" wire:model="challenges" class="form-input" placeholder="Mis. Waktu terbatas, materi sulit">
                </div>
                <div style="margin-top:16px; text-align:right;">
                    <button type="submit" class="btn btn-forest btn-sm">Buat Goal</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Goals List --}}
    @if($goals->count() === 0)
        <div class="card" style="text-align:center; padding:48px;">
            <div style="font-size:48px; margin-bottom:12px;">🏆</div>
            <p style="color:var(--color-ink-3);">Belum ada goal. Tetapkan tujuanmu dan mulai melangkah!</p>
        </div>
    @else
        <div style="display:flex; flex-direction:column; gap:12px;">
            @foreach($goals as $goal)
                <div class="card" style="padding:0; overflow:hidden; {{ $goal->status === 'completed' ? 'border-color:var(--color-forest-light);' : ($goal->status === 'archived' ? 'opacity:0.6;' : '') }}" wire:key="goal-{{ $goal->id }}">
                    {{-- Goal Header --}}
                    <div style="padding:20px; cursor:pointer;" wire:click="toggleExpand({{ $goal->id }})">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                @if($goal->status === 'completed')
                                    <span style="font-size:22px;">🏆</span>
                                @elseif($goal->status === 'archived')
                                    <span style="font-size:22px;">📦</span>
                                @else
                                    <span style="font-size:22px;">🎯</span>
                                @endif
                                <div>
                                    <div style="font-size:16px; font-weight:700; {{ $goal->status === 'completed' ? 'text-decoration:line-through; color:var(--color-forest);' : '' }}">
                                        {{ $goal->title }}
                                    </div>
                                    <div style="display:flex; gap:6px; margin-top:4px;">
                                        @if($goal->due_date)
                                            <span style="font-size:11px; color:var(--color-ink-4); font-family:var(--font-mono);">
                                                📅 {{ $goal->due_date->format('d M Y') }}
                                            </span>
                                        @endif
                                        @if($goal->status === 'completed')
                                            <span class="badge badge-success">Tercapai!</span>
                                        @elseif($goal->status === 'archived')
                                            <span class="badge badge-neutral">Diarsipkan</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:12px;">
                                <span style="font-family:var(--font-mono); font-size:18px; font-weight:700;
                                    {{ $goal->progress_pct >= 100 ? 'color:var(--color-forest);' :
                                       ($goal->progress_pct >= 50 ? 'color:var(--color-gold);' :
                                       'color:var(--color-violet);') }}">
                                    {{ number_format($goal->progress_pct, 0) }}%
                                </span>
                                <span style="font-size:16px; color:var(--color-ink-4); transition:transform 0.2s;
                                    {{ $expandedGoalId === $goal->id ? 'transform:rotate(180deg);' : '' }}">
                                    ▾
                                </span>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div style="height:6px; background:var(--color-paper-3); border-radius:var(--radius-pill); overflow:hidden;">
                            <div style="height:100%; border-radius:var(--radius-pill); transition:width 0.5s ease;
                                width:{{ $goal->progress_pct }}%;
                                {{ $goal->progress_pct >= 100 ? 'background:var(--color-forest);' :
                                   ($goal->progress_pct >= 50 ? 'background:var(--color-gold-light);' :
                                   'background:var(--color-violet-light);') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Expanded Section --}}
                    @if($expandedGoalId === $goal->id)
                        <div style="border-top:1px solid var(--color-paper-3); padding:20px; background:var(--color-paper-2);">
                            {{-- Motivation Context --}}
                            @if($goal->why || $goal->challenges || $goal->reward)
                                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:16px;">
                                    @if($goal->why)
                                        <div style="padding:10px; background:#fff; border-radius:var(--radius-md); font-size:12px;">
                                            <div class="text-label" style="color:var(--color-violet); margin-bottom:4px;">💡 Mengapa</div>
                                            {{ $goal->why }}
                                        </div>
                                    @endif
                                    @if($goal->challenges)
                                        <div style="padding:10px; background:#fff; border-radius:var(--radius-md); font-size:12px;">
                                            <div class="text-label" style="color:var(--color-warning); margin-bottom:4px;">⚡ Tantangan</div>
                                            {{ $goal->challenges }}
                                        </div>
                                    @endif
                                    @if($goal->reward)
                                        <div style="padding:10px; background:#fff; border-radius:var(--radius-md); font-size:12px;">
                                            <div class="text-label" style="color:var(--color-gold); margin-bottom:4px;">🎁 Hadiah</div>
                                            {{ $goal->reward }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Steps --}}
                            <div style="margin-bottom:12px;">
                                <div class="text-label" style="color:var(--color-ink-3); margin-bottom:8px;">
                                    Langkah-langkah ({{ $goal->steps->where('is_completed', true)->count() }}/{{ $goal->steps->count() }})
                                </div>

                                @foreach($goal->steps as $step)
                                    <div style="display:flex; align-items:center; gap:8px; padding:8px 10px; margin-bottom:4px; background:#fff; border-radius:var(--radius-md);" wire:key="step-{{ $step->id }}">
                                        <input type="checkbox"
                                               {{ $step->is_completed ? 'checked' : '' }}
                                               wire:click="toggleStep({{ $step->id }})"
                                               style="width:16px; height:16px; accent-color:var(--color-forest); cursor:pointer;">
                                        <span style="flex:1; font-size:13px; {{ $step->is_completed ? 'text-decoration:line-through; color:var(--color-ink-4);' : '' }}">
                                            {{ $step->step_name }}
                                        </span>
                                        @if($step->due_date)
                                            <span style="font-size:10px; color:var(--color-ink-4); font-family:var(--font-mono);">
                                                {{ $step->due_date->format('d M') }}
                                            </span>
                                        @endif
                                        <button wire:click="deleteStep({{ $step->id }})"
                                                style="padding:2px; border:none; background:none; cursor:pointer; color:var(--color-ink-4); font-size:11px;" title="Hapus">🗑️</button>
                                    </div>
                                @endforeach

                                {{-- Add Step --}}
                                @if($goal->status === 'active')
                                    <div style="display:flex; gap:8px; margin-top:8px;">
                                        <input type="text"
                                               wire:model="new_step"
                                               wire:keydown.enter="addStep({{ $goal->id }})"
                                               class="form-input"
                                               placeholder="Tambah langkah baru..."
                                               style="font-size:12px; padding:6px 10px;">
                                        <button wire:click="addStep({{ $goal->id }})" class="btn btn-sm btn-forest" style="padding:6px 10px;">+</button>
                                    </div>
                                @endif
                            </div>

                            {{-- Actions --}}
                            @if($goal->status === 'active')
                                <div style="display:flex; gap:8px; padding-top:12px; border-top:1px solid var(--color-paper-3);">
                                    <button wire:click="archiveGoal({{ $goal->id }})" class="btn btn-sm btn-secondary">📦 Arsipkan</button>
                                    <button wire:click="deleteGoal({{ $goal->id }})" wire:confirm="Hapus goal ini beserta semua langkahnya?" class="btn btn-sm btn-danger">🗑️ Hapus</button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
