<div style="position:relative;" x-data="{ open: @entangle('open') }" @click.outside="open = false">
    {{-- Bell Button --}}
    <button @click="open = !open; $wire.loadNotifications()"
            style="position:relative; width:36px; height:36px; border-radius:50%; background:var(--color-paper-2); border:1px solid var(--color-paper-3); cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; transition:all 0.2s;"
            onmouseover="this.style.background='var(--color-cream)'"
            onmouseout="this.style.background='var(--color-paper-2)'"
            title="Notifikasi">
        🔔
        @if(count($notifications) > 0)
            <span style="position:absolute; top:-2px; right:-2px; background:var(--color-danger); color:#fff; font-size:10px; font-weight:700; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-family:var(--font-mono); box-shadow:0 0 0 2px var(--color-paper);">
                {{ count($notifications) }}
            </span>
        @endif
    </button>

    {{-- Dropdown Panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 translate-y-[-10px]"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-[-10px]"
         style="position:absolute; right:0; top:44px; width:340px; background:var(--color-card-bg); border:1px solid var(--color-paper-3); border-radius:var(--radius-base); box-shadow:0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); z-index:50; display:none; overflow:hidden;">
        
        {{-- Header --}}
        <div style="padding:16px; border-bottom:1px solid var(--color-paper-3); display:flex; align-items:center; justify-content:space-between; background:var(--color-paper-2);">
            <span style="font-weight:700; font-size:14px; color:var(--color-ink);">🔔 Notifikasi SIM</span>
            <span class="badge badge-neutral" style="font-size:10px;">{{ count($notifications) }} pending</span>
        </div>

        {{-- Notifications List --}}
        <div style="max-height:360px; overflow-y:auto; padding:8px 0;">
            @forelse($notifications as $item)
                <a href="{{ $item['url'] }}"
                   style="display:flex; gap:12px; padding:12px 16px; text-decoration:none; transition:all 0.15s; border-bottom:1px solid var(--color-paper-2);"
                   onmouseover="this.style.background='var(--color-paper-2)'"
                   onmouseout="this.style.background='transparent'"
                   wire:key="notif-{{ $item['id'] }}">
                    {{-- Icon Container --}}
                    <div style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;
                        {{ $item['type'] === 'danger' ? 'background:var(--color-blush-bg);' :
                           ($item['type'] === 'warning' ? 'background:var(--color-gold-bg);' :
                           ($item['type'] === 'info' ? 'background:var(--color-violet-bg);' :
                           'background:var(--color-forest-bg);')) }}">
                        {{ $item['icon'] }}
                    </div>

                    {{-- Message Content --}}
                    <div style="flex:1;">
                        <div style="font-size:12px; font-weight:700; color:var(--color-ink);">
                            {{ $item['title'] }}
                        </div>
                        <div style="font-size:11px; color:var(--color-ink-3); margin-top:2px; line-height:1.4;">
                            {{ $item['message'] }}
                        </div>
                    </div>
                </a>
            @empty
                <div style="text-align:center; padding:32px 16px; color:var(--color-ink-4);">
                    <div style="font-size:32px; margin-bottom:8px;">☕</div>
                    <div style="font-size:13px; font-weight:600;">Semua Beres!</div>
                    <div style="font-size:11px; color:var(--color-ink-4); margin-top:2px;">Tidak ada pengingat pending saat ini.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
