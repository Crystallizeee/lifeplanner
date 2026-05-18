<div>
    <x-slot:header>
        @php
            $hour = now()->hour;
            $greeting = match(true) {
                $hour < 5 => '🌙 Selamat malam',
                $hour < 12 => '☀️ Selamat pagi',
                $hour < 15 => '🌤️ Selamat siang',
                $hour < 18 => '🌅 Selamat sore',
                default => '🌙 Selamat malam',
            };
        @endphp
        {{ $greeting }}, {{ Auth::user()->name }}
    </x-slot:header>
    <x-slot:subtitle>{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }} · Dashboard ringkasan harianmu</x-slot:subtitle>

    {{-- ═══════ Financial Stats Row ═══════ --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:28px;">
        {{-- Balance --}}
        <div class="card-stat card-stat--forest">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:8px;">Saldo Periode</div>
            <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-forest);">
                Rp {{ number_format(($totalIncome - $totalExpense), 0, ',', '.') }}
            </div>
            <div style="font-size:11px; color:var(--color-ink-4); margin-top:4px; font-family:var(--font-mono);">
                @if($activeBudget)
                    {{ $activeBudget->period_start->format('d M') }} — {{ $activeBudget->period_end->format('d M Y') }}
                @else
                    Belum ada budget aktif
                @endif
            </div>
        </div>

        {{-- Income --}}
        <div class="card-stat card-stat--forest">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:8px;">Pemasukan</div>
            <div style="font-size:24px; font-family:var(--font-serif); color:var(--color-forest);">
                Rp {{ number_format($totalIncome, 0, ',', '.') }}
            </div>
            <div style="margin-top:6px;">
                <span class="badge badge-success">↑ Income</span>
            </div>
        </div>

        {{-- Expense --}}
        <div class="card-stat card-stat--gold">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:8px;">Pengeluaran</div>
            <div style="font-size:24px; font-family:var(--font-serif); color:var(--color-warning);">
                Rp {{ number_format($totalExpense, 0, ',', '.') }}
            </div>
            <div style="margin-top:6px;">
                <span class="badge badge-warning">↓ Expense</span>
            </div>
        </div>

        {{-- Bills --}}
        <div class="card-stat card-stat--blush">
            <div class="text-label" style="color:var(--color-ink-4); margin-bottom:8px;">Tagihan Pending</div>
            <div style="font-size:24px; font-family:var(--font-serif); color:var(--color-blush);">
                Rp {{ number_format($totalBills, 0, ',', '.') }}
            </div>
            <div style="margin-top:6px;">
                @if($overdueBills > 0)
                    <span class="badge badge-danger">{{ $overdueBills }} overdue</span>
                @else
                    <span class="badge badge-neutral">Aman</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════ Main Content Grid ═══════ --}}
    <div style="display:grid; grid-template-columns:1fr 380px; gap:20px; align-items:start;">

        {{-- ═══════ LEFT COLUMN ═══════ --}}
        <div style="display:flex; flex-direction:column; gap:20px;">

            {{-- Quick Actions --}}
            <div class="card">
                <h3 style="font-size:16px; margin-bottom:14px;">⚡ Quick Actions</h3>
                <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:8px;">
                    <a href="{{ route('finance.quick-entry') }}" class="btn btn-forest btn-sm" style="justify-content:center; font-size:12px;">
                        💰 Transaksi
                    </a>
                    <a href="{{ route('productivity.todos') }}" class="btn btn-secondary btn-sm" style="justify-content:center; font-size:12px;">
                        ✅ Task
                    </a>
                    <a href="{{ route('health.habits') }}" class="btn btn-secondary btn-sm" style="justify-content:center; font-size:12px;">
                        🔥 Habits
                    </a>
                    <a href="{{ route('health.weight') }}" class="btn btn-secondary btn-sm" style="justify-content:center; font-size:12px;">
                        ⚖️ Berat
                    </a>
                </div>
            </div>

            {{-- Spending by Category --}}
            @if(count($spendingByCategory) > 0)
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <h3 style="font-size:16px;">📊 Pengeluaran per Kategori</h3>
                    <a href="{{ route('finance.budget') }}" style="font-size:11px; color:var(--color-forest); text-decoration:none;">Lihat detail →</a>
                </div>
                @php $maxSpend = collect($spendingByCategory)->max('total') ?: 1; @endphp
                @foreach($spendingByCategory as $catSpend)
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                        <span style="font-size:16px; width:24px; text-align:center;">{{ $catSpend['icon'] }}</span>
                        <div style="flex:1;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                                <span style="font-size:12px; font-weight:600;">{{ $catSpend['name'] }}</span>
                                <span style="font-size:11px; font-family:var(--font-mono); color:var(--color-ink-3);">Rp {{ number_format($catSpend['total'], 0, ',', '.') }}</span>
                            </div>
                            <div style="height:4px; background:var(--color-paper-3); border-radius:var(--radius-pill); overflow:hidden;">
                                <div style="height:100%; background:var(--color-gold-light); border-radius:var(--radius-pill); width:{{ ($catSpend['total'] / $maxSpend) * 100 }}%;"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            {{-- Active Goals --}}
            @if($activeGoals->count() > 0)
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <h3 style="font-size:16px;">🏆 Goal Aktif</h3>
                    <a href="{{ route('productivity.goals') }}" style="font-size:11px; color:var(--color-forest); text-decoration:none;">Semua goal →</a>
                </div>
                @foreach($activeGoals as $goal)
                    <div style="padding:10px 0; {{ !$loop->last ? 'border-bottom:1px solid var(--color-paper-3);' : '' }}">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                            <span style="font-size:13px; font-weight:600;">🎯 {{ $goal->title }}</span>
                            <span style="font-family:var(--font-mono); font-size:12px; font-weight:700;
                                {{ $goal->progress_pct >= 50 ? 'color:var(--color-forest);' : 'color:var(--color-violet);' }}">
                                {{ number_format($goal->progress_pct, 0) }}%
                            </span>
                        </div>
                        <div style="height:4px; background:var(--color-paper-3); border-radius:var(--radius-pill); overflow:hidden;">
                            <div style="height:100%; border-radius:var(--radius-pill); transition:width 0.5s ease;
                                width:{{ $goal->progress_pct }}%;
                                {{ $goal->progress_pct >= 50 ? 'background:var(--color-forest);' : 'background:var(--color-violet-light);' }}">
                            </div>
                        </div>
                        @if($goal->steps->count() > 0)
                            <div style="font-size:10px; color:var(--color-ink-4); margin-top:4px;">
                                {{ $goal->steps->where('is_completed', true)->count() }}/{{ $goal->steps->count() }} langkah selesai
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif

            {{-- Recent Activity --}}
            @if($recentActivity->count() > 0)
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <h3 style="font-size:16px;">📝 Aktivitas Terakhir</h3>
                    <a href="{{ route('finance.quick-entry') }}" style="font-size:11px; color:var(--color-forest); text-decoration:none;">Semua →</a>
                </div>
                @foreach($recentActivity as $tx)
                    <div style="display:flex; align-items:center; gap:10px; padding:8px 0; {{ !$loop->last ? 'border-bottom:1px solid var(--color-paper-3);' : '' }}">
                        <span style="font-size:16px; width:28px; height:28px; background:var(--color-paper-2); border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center;">
                            {{ $tx->category->icon ?? '📦' }}
                        </span>
                        <div style="flex:1;">
                            <div style="font-size:13px; font-weight:600;">{{ $tx->description }}</div>
                            <div style="font-size:10px; color:var(--color-ink-4);">{{ $tx->category->name ?? '—' }} · {{ $tx->transaction_date->format('d M') }}</div>
                        </div>
                        <div style="font-family:var(--font-mono); font-size:13px; font-weight:700;
                            {{ $tx->type === 'income' ? 'color:var(--color-forest);' : 'color:var(--color-danger);' }}">
                            {{ $tx->type === 'income' ? '+' : '-' }}Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- ═══════ RIGHT COLUMN ═══════ --}}
        <div style="display:flex; flex-direction:column; gap:20px;">

            {{-- Today's Focus --}}
            <div class="card">
                <h3 style="font-size:16px; margin-bottom:14px;">🎯 Fokus Hari Ini</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div style="text-align:center; padding:14px; background:var(--color-violet-bg); border-radius:var(--radius-md);">
                        <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-violet);">{{ $todaysTasks }}</div>
                        <div class="text-label" style="color:var(--color-ink-4);">Tasks Aktif</div>
                    </div>
                    <div style="text-align:center; padding:14px; background:var(--color-forest-bg); border-radius:var(--radius-md);">
                        <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-forest);">{{ $completedTasks }}</div>
                        <div class="text-label" style="color:var(--color-ink-4);">Selesai</div>
                    </div>
                </div>
            </div>

            {{-- Habit Streak --}}
            <div class="card">
                <h3 style="font-size:16px; margin-bottom:14px;">🔥 Habit Tracker</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                    <div style="text-align:center; padding:14px; background:var(--color-blush-bg); border-radius:var(--radius-md);">
                        <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-blush);">{{ $activeHabits }}</div>
                        <div class="text-label" style="color:var(--color-ink-4);">Active</div>
                    </div>
                    <div style="text-align:center; padding:14px; background:var(--color-gold-bg); border-radius:var(--radius-md);">
                        <div style="font-size:28px; font-family:var(--font-serif); color:var(--color-gold);">{{ $topStreak }}🔥</div>
                        <div class="text-label" style="color:var(--color-ink-4);">Best Streak</div>
                    </div>
                </div>
                @if($activeHabits > 0)
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:var(--color-paper-2); border-radius:var(--radius-md);">
                        <span style="font-size:12px; color:var(--color-ink-3);">Hari ini selesai</span>
                        <span style="font-family:var(--font-mono); font-size:14px; font-weight:700;
                            {{ $habitsCheckedToday >= $activeHabits ? 'color:var(--color-forest);' : 'color:var(--color-violet);' }}">
                            {{ $habitsCheckedToday }}/{{ $activeHabits }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Health Snapshot --}}
            <div class="card">
                <h3 style="font-size:16px; margin-bottom:14px;">❤️ Kesehatan</h3>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    {{-- Weight --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; background:var(--color-paper-2); border-radius:var(--radius-md);">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:18px;">⚖️</span>
                            <span style="font-size:12px; color:var(--color-ink-3);">Berat badan</span>
                        </div>
                        <span style="font-family:var(--font-mono); font-size:16px; font-weight:700; color:var(--color-forest);">
                            {{ $latestWeight ? number_format($latestWeight->weight_kg, 1) . ' kg' : '—' }}
                        </span>
                    </div>

                    {{-- Savings --}}
                    @if($totalSavingsTarget > 0)
                        <div style="padding:10px 12px; background:var(--color-paper-2); border-radius:var(--radius-md);">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-size:18px;">🎯</span>
                                    <span style="font-size:12px; color:var(--color-ink-3);">Tabungan</span>
                                </div>
                                <span style="font-family:var(--font-mono); font-size:12px; font-weight:600; color:var(--color-violet);">
                                    {{ $totalSavingsTarget > 0 ? round(($totalSavingsSaved / $totalSavingsTarget) * 100) : 0 }}%
                                </span>
                            </div>
                            <div style="height:4px; background:var(--color-paper-3); border-radius:var(--radius-pill); overflow:hidden;">
                                <div style="height:100%; background:var(--color-violet-light); border-radius:var(--radius-pill);
                                    width:{{ $totalSavingsTarget > 0 ? min(100, ($totalSavingsSaved / $totalSavingsTarget) * 100) : 0 }}%;"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Getting Started --}}
            @if(!$activeBudget)
            <div class="card" style="border-color:var(--color-gold-light); background:var(--color-gold-bg);">
                <h3 style="font-size:16px; margin-bottom:8px; color:var(--color-warning);">🚀 Mulai Sekarang</h3>
                <p style="font-size:12px; color:var(--color-ink-3); margin-bottom:14px;">
                    Buat budget periode pertama untuk mulai mencatat transaksi.
                </p>
                <a href="{{ route('finance.budget') }}" class="btn btn-primary btn-sm" style="width:100%; justify-content:center;">
                    Buat Budget Pertama →
                </a>
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
