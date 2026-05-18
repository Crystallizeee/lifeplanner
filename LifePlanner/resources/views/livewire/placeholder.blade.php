<x-layouts.app>
    <x-slot:header>{{ $title }}</x-slot:header>
    <x-slot:subtitle>{{ $module }} · Coming in Sprint 1-4</x-slot:subtitle>

    <div class="card" style="text-align:center; padding:64px 32px;">
        <div style="font-size:64px; margin-bottom:16px;">{{ $icon }}</div>
        <h2 style="font-size:28px; margin-bottom:8px;">{{ $title }}</h2>
        <p style="color:var(--color-ink-3); font-size:14px; max-width:400px; margin:0 auto 24px;">
            Fitur ini sedang dalam pengembangan dan akan tersedia di sprint berikutnya.
        </p>
        <div class="badge badge-info">🚧 Under Development</div>
    </div>
</x-layouts.app>
