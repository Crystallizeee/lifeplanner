<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="LifePlanner SIM — Personal Information Management System for Finance, Productivity & Health">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'LifePlanner SIM' }}</title>

    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen" x-data="{ sidebarOpen: false }">

    {{-- Sidebar Navigation --}}
    <aside class="sidebar" :class="{ 'open': sidebarOpen }" @click.outside="sidebarOpen = false">
        <div class="sidebar-brand">
            <h1>Life<em>Planner</em></h1>
            <span>SIM v1.0</span>
        </div>

        <nav class="sidebar-nav">
            {{-- Dashboard --}}
            <div class="sidebar-section">
                <a href="{{ route('dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">📊</span>
                    Dashboard
                </a>
            </div>

            {{-- Finance Module --}}
            <div class="sidebar-section">
                <div class="sidebar-section-label">Keuangan</div>
                <a href="{{ route('finance.quick-entry') }}"
                   class="sidebar-link {{ request()->routeIs('finance.quick-entry') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">⚡</span>
                    Quick Entry
                </a>
                <a href="{{ route('finance.budget') }}"
                   class="sidebar-link {{ request()->routeIs('finance.budget*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">💰</span>
                    Budget Overview
                </a>
                <a href="{{ route('finance.bills') }}"
                   class="sidebar-link {{ request()->routeIs('finance.bills*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">📋</span>
                    Bill Tracker
                </a>
                <a href="{{ route('finance.savings') }}"
                   class="sidebar-link {{ request()->routeIs('finance.savings*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🎯</span>
                    Savings Goals
                </a>
                <a href="{{ route('finance.investments') }}"
                   class="sidebar-link {{ request()->routeIs('finance.investments*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">📈</span>
                    Investment Tracker
                </a>
            </div>

            {{-- Productivity Module --}}
            <div class="sidebar-section">
                <div class="sidebar-section-label">Produktivitas</div>
                <a href="{{ route('productivity.todos') }}"
                   class="sidebar-link {{ request()->routeIs('productivity.todos*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">✅</span>
                    Kanban To-Do
                </a>
                <a href="{{ route('productivity.goals') }}"
                   class="sidebar-link {{ request()->routeIs('productivity.goals*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🏆</span>
                    Goal Tracker
                </a>
            </div>

            {{-- Health Module --}}
            <div class="sidebar-section">
                <div class="sidebar-section-label">Kesehatan</div>
                <a href="{{ route('health.habits') }}"
                   class="sidebar-link {{ request()->routeIs('health.habits*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🔥</span>
                    Habit Matrix
                </a>
                <a href="{{ route('health.weight') }}"
                   class="sidebar-link {{ request()->routeIs('health.weight*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">⚖️</span>
                    Weight Log
                </a>
                <a href="{{ route('health.meals') }}"
                   class="sidebar-link {{ request()->routeIs('health.meals*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🍽️</span>
                    Meal Planner
                </a>
                <a href="{{ route('health.grocery') }}"
                   class="sidebar-link {{ request()->routeIs('health.grocery*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🛒</span>
                    Grocery List
                </a>
            </div>

            {{-- Settings Module --}}
            <div class="sidebar-section">
                <div class="sidebar-section-label">Sistem</div>
                <a href="{{ route('settings') }}"
                   class="sidebar-link {{ request()->routeIs('settings*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">⚙️</span>
                    Pengaturan
                </a>
            </div>
        </nav>

        {{-- User Section --}}
        <div style="padding: 16px 12px; border-top: 1px solid rgba(255,255,255,0.08);">
            <div class="sidebar-link" style="cursor: default;">
                <span class="sidebar-link-icon">👤</span>
                <div style="flex:1; overflow:hidden;">
                    <div style="font-size:13px; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ auth()->user()->name }}
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-link-icon" style="color:rgba(255,255,255,0.4); cursor:pointer; border:none; background:none;" title="Logout">
                        🚪
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Mobile Hamburger --}}
    <button class="fixed top-4 left-4 z-50 p-2 rounded-lg bg-ink text-white md:hidden"
            @click="sidebarOpen = !sidebarOpen"
            style="display:none;"
            id="mobile-menu-btn">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    {{-- Mobile Backdrop --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/40 z-30 md:hidden"
         @click="sidebarOpen = false"
         style="display:none;">
    </div>

    {{-- Main Content Area --}}
    <main class="main-content">
        {{-- Top Bar --}}
        <header style="padding: 20px 32px; display:flex; align-items:center; justify-content:space-between; border-bottom: 1px solid var(--color-paper-3); background: var(--color-header-bg); backdrop-filter: blur(8px);">
            <div>
                <h2 style="font-size:24px; margin:0; color:var(--color-ink);">{{ $header ?? 'Dashboard' }}</h2>
                @if(isset($subtitle))
                    <p style="font-size:12px; color:var(--color-ink-3); margin-top:2px; font-family:var(--font-mono);">{{ $subtitle }}</p>
                @endif
            </div>
            <div style="display:flex; align-items:center; gap:16px;">
                {{-- Date Display --}}
                <span style="font-family:var(--font-mono); font-size:11px; color:var(--color-ink-4);">
                    {{ now()->format('l, d M Y') }}
                </span>

                {{-- Notification Center --}}
                <livewire:partials.notification-center />

                {{-- Dark Mode Toggle --}}
                <div x-data="{ 
                     darkMode: localStorage.getItem('theme') === 'dark',
                     toggle() {
                         this.darkMode = !this.darkMode;
                         localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
                         if (this.darkMode) {
                             document.documentElement.classList.add('dark');
                         } else {
                             document.documentElement.classList.remove('dark');
                         }
                     }
                }">
                    <button @click="toggle()" 
                            style="width:36px; height:36px; border-radius:50%; background:var(--color-paper-2); border:1px solid var(--color-paper-3); cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; transition:all 0.2s;"
                            onmouseover="this.style.background='var(--color-cream)'"
                            onmouseout="this.style.background='var(--color-paper-2)'"
                            title="Ubah Tema">
                        <span x-show="!darkMode">🌙</span>
                        <span x-show="darkMode">☀️</span>
                    </button>
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <div style="padding: 32px;">
            {{ $slot }}
        </div>
    </main>

    {{-- Toast Notification --}}
    <div x-data x-init="
        Alpine.store('toast', {
            show: false,
            message: '',
            type: 'success',
            fire(message, type = 'success') {
                this.message = message;
                this.type = type;
                this.show = true;
                setTimeout(() => { this.show = false; }, 4000);
            }
        })
    ">
        <div x-show="$store.toast?.show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-full"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-full"
             :class="{
                 'toast-success': $store.toast?.type === 'success',
                 'toast-error': $store.toast?.type === 'error',
                 'toast-warning': $store.toast?.type === 'warning'
             }"
             class="toast"
             style="display:none;">
            <span x-text="$store.toast?.message"></span>
        </div>
    </div>

    <style>
        @media (max-width: 768px) {
            #mobile-menu-btn { display: block !important; }
        }
    </style>
</body>
</html>
