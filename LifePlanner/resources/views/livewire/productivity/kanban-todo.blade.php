<div>
    <x-slot:header>Kanban To-Do</x-slot:header>
    <x-slot:subtitle>Produktivitas · {{ $totalActive }} aktif · {{ $totalDone }} selesai · {{ $todayDue }} jatuh tempo hari ini</x-slot:subtitle>

    {{-- Top Bar --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:8px;">
            {{-- Priority Filter --}}
            <select wire:model.live="filterPriority" class="form-select" style="width:auto; padding:6px 32px 6px 10px; font-size:12px;">
                <option value="all">Semua Prioritas</option>
                <option value="very_high">🔴 Very High</option>
                <option value="high">🟠 High</option>
                <option value="medium">🟡 Medium</option>
                <option value="low">🟢 Low</option>
            </select>
        </div>
        <button class="btn btn-sm {{ $showForm ? 'btn-danger' : 'btn-forest' }}" wire:click="$toggle('showForm')">
            {{ $showForm ? '✕ Tutup' : '+ Tambah Task' }}
        </button>
    </div>

    {{-- Add Task Form --}}
    @if($showForm)
        <div class="card" style="margin-bottom:20px; border-color:var(--color-forest-light); background:var(--color-forest-bg);">
            <form wire:submit="addTask">
                <div style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:12px; align-items:end;">
                    <div>
                        <label class="form-label">Nama Task</label>
                        <input type="text" wire:model="task_name" class="form-input" placeholder="Apa yang perlu dikerjakan?" autofocus>
                        @error('task_name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Prioritas</label>
                        <select wire:model="priority" class="form-select">
                            <option value="very_high">🔴 Very High</option>
                            <option value="high">🟠 High</option>
                            <option value="medium">🟡 Medium</option>
                            <option value="low">🟢 Low</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Kategori</label>
                        <select wire:model="category_id" class="form-select">
                            <option value="">— Opsional —</option>
                            @foreach($taskCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="date" wire:model="due_date" class="form-input">
                    </div>
                </div>
                <div style="display:flex; gap:12px; align-items:end; margin-top:12px;">
                    <div style="flex:1;">
                        <label class="form-label">Catatan</label>
                        <input type="text" wire:model="notes" class="form-input" placeholder="Opsional">
                    </div>
                    <button type="submit" class="btn btn-forest btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Kanban Board --}}
    <div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:12px; overflow-x:auto; min-height:400px;">
        @foreach($columns as $status => $column)
            <div style="background:var(--color-paper-2); border-radius:var(--radius-base); padding:12px; min-width:220px;">
                {{-- Column Header --}}
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; padding-bottom:8px; border-bottom:2px solid {{ $column['color'] }};">
                    <span style="font-size:13px; font-weight:700; color:{{ $column['color'] }};">
                        {{ $column['label'] }}
                    </span>
                    <span class="badge badge-neutral" style="font-size:10px;">{{ $column['tasks']->count() }}</span>
                </div>

                {{-- Task Cards --}}
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @forelse($column['tasks'] as $task)
                        <div class="card" style="padding:12px; cursor:default;" wire:key="task-{{ $task->id }}">
                            @if($editingTaskId === $task->id)
                                {{-- Edit Mode --}}
                                <div style="margin-bottom:8px;">
                                    <input type="text" wire:model="editTaskName" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit"
                                           class="form-input" style="font-size:12px; padding:4px 8px;" autofocus>
                                </div>
                                <div style="display:flex; gap:4px; margin-bottom:6px;">
                                    <select wire:model="editPriority" class="form-select" style="font-size:10px; padding:2px 4px;">
                                        <option value="very_high">🔴</option>
                                        <option value="high">🟠</option>
                                        <option value="medium">🟡</option>
                                        <option value="low">🟢</option>
                                    </select>
                                    <button wire:click="saveEdit" class="btn btn-sm btn-forest" style="padding:2px 8px; font-size:10px;">✓</button>
                                    <button wire:click="cancelEdit" class="btn btn-sm btn-secondary" style="padding:2px 8px; font-size:10px;">✕</button>
                                </div>
                            @else
                                {{-- Display Mode --}}
                                {{-- Priority dot + name --}}
                                <div style="display:flex; align-items:start; gap:6px; margin-bottom:6px;" wire:click="startEditing({{ $task->id }})" style="cursor:pointer;">
                                    <span style="display:inline-block; width:8px; height:8px; border-radius:50%; margin-top:5px; flex-shrink:0;
                                        {{ $task->priority === 'very_high' ? 'background:#e24b4a;' :
                                           ($task->priority === 'high' ? 'background:#e88c30;' :
                                           ($task->priority === 'medium' ? 'background:#d4a72c;' :
                                           'background:#4a8c60;')) }}">
                                    </span>
                                    <span style="font-size:13px; font-weight:600; line-height:1.3; word-break:break-word; cursor:pointer;"
                                          wire:click="startEditing({{ $task->id }})" title="Klik untuk edit">
                                        {{ $task->task_name }}
                                    </span>
                                </div>
                            @endif

                            {{-- Meta --}}
                            <div style="display:flex; flex-wrap:wrap; gap:4px; margin-bottom:8px;">
                                @if($task->category)
                                    <span style="font-size:10px; background:var(--color-paper-3); padding:1px 6px; border-radius:var(--radius-pill); color:var(--color-ink-3);">
                                        {{ $task->category->icon }} {{ $task->category->name }}
                                    </span>
                                @endif
                                @if($task->due_date)
                                    <span style="font-size:10px; padding:1px 6px; border-radius:var(--radius-pill);
                                        {{ $task->due_date->isPast() && !in_array($status, ['done', 'canceled'])
                                            ? 'background:var(--color-blush-bg); color:var(--color-danger); font-weight:700;'
                                            : 'background:var(--color-paper-3); color:var(--color-ink-3);' }}">
                                        📅 {{ $task->due_date->format('d M') }}
                                    </span>
                                @endif
                                @if($task->notes)
                                    <span style="font-size:10px; padding:1px 6px; border-radius:var(--radius-pill); background:var(--color-paper-3); color:var(--color-ink-4);" title="{{ $task->notes }}">
                                        📝
                                    </span>
                                @endif
                            </div>

                            {{-- Status Actions --}}
                            <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                @if($status !== 'todo')
                                    <button wire:click="moveTask({{ $task->id }}, 'todo')" class="btn btn-sm btn-secondary" style="padding:2px 6px; font-size:10px;" title="Move to To Do">📝</button>
                                @endif
                                @if($status !== 'in_progress')
                                    <button wire:click="moveTask({{ $task->id }}, 'in_progress')" class="btn btn-sm btn-secondary" style="padding:2px 6px; font-size:10px;" title="Move to In Progress">🔄</button>
                                @endif
                                @if($status !== 'hold')
                                    <button wire:click="moveTask({{ $task->id }}, 'hold')" class="btn btn-sm btn-secondary" style="padding:2px 6px; font-size:10px;" title="Move to On Hold">⏸️</button>
                                @endif
                                @if($status !== 'done')
                                    <button wire:click="moveTask({{ $task->id }}, 'done')" class="btn btn-sm btn-forest" style="padding:2px 6px; font-size:10px;" title="Mark as Done">✅</button>
                                @endif
                                @if($status !== 'canceled')
                                    <button wire:click="moveTask({{ $task->id }}, 'canceled')" class="btn btn-sm btn-secondary" style="padding:2px 6px; font-size:10px;" title="Cancel">❌</button>
                                @endif
                                <button wire:click="deleteTask({{ $task->id }})" wire:confirm="Hapus task ini?" class="btn btn-sm btn-secondary" style="padding:2px 6px; font-size:10px; margin-left:auto;" title="Delete">🗑️</button>
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center; padding:24px 8px; color:var(--color-ink-4); font-size:12px;">
                            Kosong
                        </div>
                    @endforelse
                </div>

                {{-- Quick Add per Column --}}
                @if(in_array($status, ['todo', 'in_progress']))
                    <div style="margin-top:8px;">
                        <input type="text"
                               wire:model="quickAddTask"
                               wire:keydown.enter="quickAdd('{{ $status }}')"
                               class="form-input"
                               placeholder="+ Tambah cepat..."
                               style="font-size:11px; padding:6px 8px; background:var(--color-paper); border:1px dashed var(--color-stone);">
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <style>
        @media (max-width: 1200px) {
            div[style*="grid-template-columns:repeat(5"] {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }
        @media (max-width: 768px) {
            div[style*="grid-template-columns:repeat(5"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</div>
