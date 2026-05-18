<div>
    <div class="card" style="padding:40px;">
        {{-- Brand --}}
        <div style="text-align:center; margin-bottom:32px;">
            <h1 style="font-size:32px; margin-bottom:4px;">Life<em>Planner</em></h1>
            <p class="text-mono" style="font-size:11px; color:var(--color-ink-4); letter-spacing:0.1em; text-transform:uppercase;">
                Buat Akun Baru
            </p>
        </div>

        <form wire:submit="register">
            {{-- Name --}}
            <div style="margin-bottom:20px;">
                <label for="reg-name" class="form-label">Nama Lengkap</label>
                <input type="text"
                       id="reg-name"
                       wire:model="name"
                       class="form-input"
                       placeholder="Nama Anda"
                       autofocus
                       autocomplete="name">
                @error('name')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div style="margin-bottom:20px;">
                <label for="reg-email" class="form-label">Email</label>
                <input type="email"
                       id="reg-email"
                       wire:model="email"
                       class="form-input"
                       placeholder="nama@email.com"
                       autocomplete="email">
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div style="margin-bottom:20px;">
                <label for="reg-password" class="form-label">Password</label>
                <input type="password"
                       id="reg-password"
                       wire:model="password"
                       class="form-input"
                       placeholder="Minimal 8 karakter"
                       autocomplete="new-password">
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div style="margin-bottom:24px;">
                <label for="reg-password-confirm" class="form-label">Konfirmasi Password</label>
                <input type="password"
                       id="reg-password-confirm"
                       wire:model="password_confirmation"
                       class="form-input"
                       placeholder="Ulangi password"
                       autocomplete="new-password">
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn btn-forest" style="width:100%; justify-content:center;">
                <span wire:loading.remove wire:target="register">Daftar & Mulai</span>
                <span wire:loading wire:target="register">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="animate-spin">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10" stroke-linecap="round"/>
                    </svg>
                    Membuat akun...
                </span>
            </button>
        </form>

        <div style="text-align:center; margin-top:20px;">
            <a href="{{ route('login') }}" style="font-size:13px; color:var(--color-forest-light); text-decoration:none;">
                Sudah punya akun? Masuk
            </a>
        </div>
    </div>

    <p style="text-align:center; margin-top:16px; font-family:var(--font-mono); font-size:10px; color:var(--color-ink-4);">
        LifePlanner SIM v1.0 · {{ date('Y') }}
    </p>
</div>
