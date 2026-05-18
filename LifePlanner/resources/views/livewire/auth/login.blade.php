<div>
    <div class="card" style="padding:40px;">
        {{-- Brand --}}
        <div style="text-align:center; margin-bottom:32px;">
            <h1 style="font-size:32px; margin-bottom:4px;">Life<em>Planner</em></h1>
            <p class="text-mono" style="font-size:11px; color:var(--color-ink-4); letter-spacing:0.1em; text-transform:uppercase;">
                Personal Information Management
            </p>
        </div>

        <form wire:submit="authenticate">
            {{-- Email --}}
            <div style="margin-bottom:20px;">
                <label for="login-email" class="form-label">Email</label>
                <input type="email"
                       id="login-email"
                       wire:model="email"
                       class="form-input"
                       placeholder="nama@email.com"
                       autofocus
                       autocomplete="email">
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div style="margin-bottom:20px;">
                <label for="login-password" class="form-label">Password</label>
                <input type="password"
                       id="login-password"
                       wire:model="password"
                       class="form-input"
                       placeholder="••••••••"
                       autocomplete="current-password">
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember Me --}}
            <div style="margin-bottom:24px; display:flex; align-items:center; gap:8px;">
                <input type="checkbox"
                       id="login-remember"
                       wire:model="remember"
                       style="width:16px; height:16px; accent-color:var(--color-forest);">
                <label for="login-remember" style="font-size:13px; color:var(--color-ink-3); cursor:pointer;">
                    Ingat saya
                </label>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                <span wire:loading.remove wire:target="authenticate">Masuk</span>
                <span wire:loading wire:target="authenticate">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="animate-spin">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10" stroke-linecap="round"/>
                    </svg>
                    Memproses...
                </span>
            </button>
        </form>

        @if(\App\Models\User::count() === 0)
            <div style="text-align:center; margin-top:20px;">
                <a href="{{ route('register') }}" style="font-size:13px; color:var(--color-forest-light); text-decoration:none;">
                    Belum punya akun? Daftar
                </a>
            </div>
        @endif
    </div>

    {{-- Version --}}
    <p style="text-align:center; margin-top:16px; font-family:var(--font-mono); font-size:10px; color:var(--color-ink-4);">
        LifePlanner SIM v1.0 · {{ date('Y') }}
    </p>
</div>
