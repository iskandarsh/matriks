@props([
'base' => '/interbat/messenger',
'right' => '5.5rem', // jarak dari kanan di mobile (di sebelah widget AI)
'rightSm' => '6rem', // jarak dari kanan di desktop (>= 640px)
'pusherKey' => config('broadcasting.connections.pusher.key') ?: '4d0f15f12f95429f5c36',
'cluster' => config('broadcasting.connections.pusher.options.cluster') ?: 'ap1',
'authEndpoint' => '/interbat/broadcasting/auth', // endpoint auth channel privat (sesuaikan prefix aplikasi)
// stiker bawaan: semua file di public/stickers/*.{webp,png,gif} (stiker buatan user ada di storage)
'stickers' => collect(glob(public_path('stickers/*.{webp,png,gif}'), GLOB_BRACE) ?: [])
->map(fn ($f) => asset('stickers/' . basename($f)))->values()->all(),
])

{{-- ============================================================
     Messenger Widget (Blade component) — chat antar user (realtime Echo/Pusher + polling cadangan)
     Pemakaian : <x-messenger-widget />
     Kebutuhan : jQuery, SweetAlert2 (Swal), Tailwind CSS (scan file ini), meta csrf-token
                 Realtime (opsional): pusher.min.js + echo.life.js dimuat SEBELUM komponen ini.
                 Bila tidak ada / gagal tersambung, widget otomatis memakai polling.
     Fitur     : kirim, edit, tarik pesan (semua orang), hapus untuk saya, balas pesan,
                 emoji, GIF (Giphy + unggah GIF sendiri), stiker bawaan + stiker buatan sendiri,
                 zoom foto profil / stiker / GIF (hanya lihat, digambar di canvas + watermark)
     Backend   : kolom `type` (text|sticker|gif), tabel user_stickers, route:
                 GET gifs | POST gif-upload | GET/POST stickers | POST stickers/save | DELETE stickers/{id}
     ============================================================ --}}

@once
<style>
    #um-widget {
        position: fixed;
        right: var(--um-right, 5.5rem);
        bottom: 1rem;
        z-index: 55;
    }

    @media (min-width: 640px) {
        #um-widget {
            right: var(--um-right-sm, 6rem);
            bottom: 1.5rem;
        }
    }

    #um-toggle {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 9999px;
        border: 0;
        cursor: pointer;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        background: linear-gradient(135deg, #0891b2, #4f46e5);
        box-shadow: 0 10px 25px -5px rgba(8, 145, 178, .4);
    }

    #um-toggle svg {
        width: 1.6rem;
        height: 1.6rem;
    }

    /* stiker/GIF: tidak bisa di-drag, diseleksi, atau long-press simpan (iOS) */
    #um-widget img {
        -webkit-user-drag: none;
        user-select: none;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }

    #um-panel.hidden,
    #um-widget .hidden {
        display: none;
    }

    @keyframes um-pop {
        from {
            opacity: 0;
            transform: translateY(12px) scale(.98);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    #um-panel.um-open {
        animation: um-pop .2s cubic-bezier(.2, .8, .2, 1);
    }

    #um-messages,
    #um-contacts,
    #um-pk-body {
        scrollbar-width: thin;
        scrollbar-color: #d1d5db transparent;
        overscroll-behavior: contain;
    }

    /* pastikan popup SweetAlert selalu di atas widget */
    .swal2-container {
        z-index: 100000 !important;
    }

    @media (prefers-reduced-motion: reduce) {
        #um-panel.um-open {
            animation: none !important;
        }
    }

    @keyframes um-flash {

        0%,
        100% {
            background: transparent;
        }

        30% {
            background: rgba(99, 102, 241, .18);
        }
    }

    #um-widget .um-flash {
        animation: um-flash 1.2s ease;
        border-radius: .75rem;
    }

    /* modal zoom (foto profil / stiker / GIF) */
    #um-zoom {
        position: absolute;
        inset: 0;
        z-index: 30;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: .75rem;
        background: rgba(15, 23, 42, .92);
        padding: 1rem;
        touch-action: none;
    }

    #um-zoom.um-zoom-open {
        display: flex;
    }

    #um-zoom canvas {
        max-width: 100%;
        max-height: 70%;
        border-radius: 1rem;
        pointer-events: none;
        user-select: none;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }

    /* sumber gambar tersembunyi untuk modal zoom (harus ada di DOM agar GIF tetap beranimasi) */
    #um-zoom-src {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    #um-widget .cursor-zoom-in {
        cursor: zoom-in;
    }
</style>
@endonce

<div id="um-widget"
    data-user-id="{{ Auth::id() }}"
    data-user-name="{{ Auth::user()->name ?? 'User' }}"
    data-base="{{ $base }}"
    data-pusher-key="{{ $pusherKey }}"
    data-pusher-cluster="{{ $cluster }}"
    data-auth-endpoint="{{ $authEndpoint }}"
    data-stickers="{{ json_encode($stickers) }}"
    data-session-hash="{{ hash('sha256', (string) session('device_session_id')) }}"
    style="--um-right: {{ $right }}; --um-right-sm: {{ $rightSm }};"
    class="font-sans">

    {{-- ================= PANEL ================= --}}
    <div id="um-panel" role="dialog" aria-label="Pesan"
        class="hidden fixed inset-0 sm:absolute sm:inset-auto sm:bottom-[4.5rem] sm:right-0
               sm:w-[22rem] sm:h-[34rem] sm:max-h-[calc(100dvh-7rem)]
               bg-white overflow-hidden sm:rounded-2xl sm:shadow-2xl sm:ring-1 sm:ring-black/5">

        {{-- ===== VIEW 1: DAFTAR KONTAK ===== --}}
        <section id="um-list-view" class="flex flex-col h-full">
            <header class="flex items-center gap-3 px-4 py-3.5 text-white"
                style="background:linear-gradient(90deg,#0891b2,#4f46e5)">
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-[15px] leading-tight">Pesan</div>
                    <div id="um-online-count" class="text-xs text-cyan-100">Memuat…</div>
                </div>
                <button type="button" class="um-close p-2 rounded-lg text-cyan-100 hover:text-white hover:bg-white/15 transition"
                    aria-label="Tutup">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </header>

            <div class="px-3 py-2.5 border-b border-gray-100 bg-white">
                <input id="um-search" type="search" placeholder="Cari nama…" autocomplete="off"
                    class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3.5 py-2 text-sm text-gray-800
                           placeholder-gray-400 focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
            </div>

            <div id="um-contacts" class="flex-1 overflow-y-auto"></div>
        </section>

        {{-- ===== VIEW 2: PERCAKAPAN ===== --}}
        <section id="um-chat-view" class="hidden flex-col h-full">
            <header class="flex items-center gap-2 px-2 py-3 text-white"
                style="background:linear-gradient(90deg,#0891b2,#4f46e5)">
                <button id="um-back" type="button" aria-label="Kembali"
                    class="p-2 rounded-lg text-cyan-100 hover:text-white hover:bg-white/15 transition">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </button>
                <div id="um-chat-avatar" class="shrink-0"></div>
                <div class="min-w-0 flex-1">
                    <div id="um-chat-name" class="font-semibold text-[15px] leading-tight truncate"></div>
                    <div id="um-chat-status" class="text-xs text-cyan-100"></div>
                </div>
                <button type="button" class="um-close p-2 rounded-lg text-cyan-100 hover:text-white hover:bg-white/15 transition"
                    aria-label="Tutup">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </header>

            <div id="um-messages" role="log" aria-live="polite"
                class="flex-1 overflow-y-auto px-4 py-4 space-y-2 bg-slate-50"></div>

            <div id="um-error" class="hidden px-4 py-1.5 text-xs text-red-700 bg-red-50 border-t border-red-200"></div>

            {{-- bar mode edit --}}
            <div id="um-edit-bar" class="hidden items-center gap-2 px-4 py-1.5 text-xs text-indigo-700 bg-indigo-50 border-t border-indigo-100">
                <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9" />
                    <path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                </svg>
                <span class="flex-1 truncate font-medium">Mengedit pesan</span>
                <button id="um-edit-cancel" type="button" class="font-semibold hover:text-indigo-900 underline underline-offset-2">Batal</button>
            </div>
            {{-- bar balas pesan --}}
            <div id="um-reply-bar" class="hidden items-center gap-2 px-4 py-2 text-xs bg-slate-50 border-t border-gray-200">
                <div class="w-1 self-stretch rounded bg-indigo-500"></div>
                <div class="min-w-0 flex-1">
                    <div id="um-reply-name" class="font-semibold text-indigo-700 truncate"></div>
                    <div id="um-reply-text" class="truncate text-gray-500"></div>
                </div>
                <button id="um-reply-cancel" type="button" aria-label="Batal balas"
                    class="shrink-0 w-6 h-6 rounded-full text-gray-500 hover:bg-gray-200 flex items-center justify-center text-base leading-none">×</button>
            </div>
            {{-- picker emoji / GIF / stiker --}}
            <div id="um-picker" class="hidden flex-col border-t border-gray-200 bg-white" style="height:15rem">
                <div class="flex items-center border-b border-gray-100 text-sm">
                    <button type="button" data-tab="emoji" class="um-tab flex-1 py-2 font-medium">😀 Emoji</button>
                    <button type="button" data-tab="gif" class="um-tab flex-1 py-2 font-medium">GIF</button>
                    <button type="button" data-tab="sticker" class="um-tab flex-1 py-2 font-medium">Stiker</button>
                </div>
                <div id="um-pk-sub" class="px-2 pt-2"></div>
                <div id="um-pk-body" class="flex-1 overflow-y-auto p-2"></div>

                {{-- input file tersembunyi (dipicu tombol "Tambah" / "Unggah") --}}
                <input type="file" id="um-file-sticker" class="hidden" accept="image/png,image/jpeg,image/webp,image/gif">
                <input type="file" id="um-file-gif" class="hidden" accept="image/gif">
            </div>

            <div class="bg-white border-t border-gray-200 px-3 pt-3"
                style="padding-bottom:max(0.75rem, env(safe-area-inset-bottom));">
                <div class="flex items-end gap-1 rounded-2xl border border-gray-300 bg-white pl-1.5 pr-1.5 py-1.5
                            focus-within:border-cyan-500 focus-within:ring-2 focus-within:ring-cyan-500/20 transition">
                    <button id="um-emoji-btn" type="button" aria-label="Emoji, GIF, stiker"
                        class="shrink-0 w-9 h-9 rounded-xl text-gray-500 hover:text-indigo-600 hover:bg-gray-100 flex items-center justify-center transition">
                        <svg class="w-[20px] h-[20px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M8 14s1.5 2 4 2 4-2 4-2" />
                            <path d="M9 9h.01M15 9h.01" />
                        </svg>
                    </button>
                    <textarea id="um-input" rows="1" maxlength="2000" placeholder="Ketik pesan…" autocomplete="off"
                        aria-label="Pesan"
                        class="flex-1 resize-none bg-transparent py-2 text-[15px] sm:text-sm text-gray-800
                               placeholder-gray-400 focus:outline-none max-h-[120px] leading-snug"></textarea>
                    <button id="um-send" type="button" aria-label="Kirim"
                        class="shrink-0 w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center
                               hover:bg-indigo-700 active:scale-95 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 2L11 13" />
                            <path d="M22 2l-7 20-4-9-9-4 20-7z" />
                        </svg>
                    </button>
                </div>
            </div>
        </section>

        {{-- menu aksi pesan (edit / tarik / hapus) --}}
        <div id="um-menu" role="menu"
            class="hidden absolute left-0 top-0 z-20 w-52 rounded-xl bg-white py-1 text-sm shadow-xl ring-1 ring-black/10"></div>

        {{-- modal zoom (foto profil / stiker / GIF) — hanya lihat --}}
        <div id="um-zoom" role="dialog" aria-label="Pratinjau gambar" oncontextmenu="return false">
            <button id="um-zoom-close" type="button" aria-label="Tutup"
                class="absolute top-3 right-3 w-9 h-9 rounded-full bg-white/15 text-white text-xl leading-none
                       flex items-center justify-center hover:bg-white/25 transition">×</button>
            <canvas id="um-zoom-canvas"></canvas>
            <div id="um-zoom-name" class="text-sm font-semibold text-white"></div>
        </div>
    </div>

    {{-- ================= TOMBOL ================= --}}
    <div class="flex justify-end">
        <button id="um-toggle" type="button" title="Pesan" aria-label="Buka pesan" aria-expanded="false"
            class="relative w-14 h-14 rounded-full text-white shadow-lg flex items-center justify-center
                   bg-gradient-to-br from-cyan-600 to-indigo-600 hover:scale-105 active:scale-95 transition">
            <svg id="um-icon-open" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-1m10-5V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l4-4h4a2 2 0 002-2z" />
            </svg>
            <svg id="um-icon-close" class="hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
            </svg>
            <span id="um-badge"
                class="hidden absolute -top-1 -right-1 min-w-[1.25rem] h-5 px-1 rounded-full bg-rose-500 text-white
                       text-[11px] font-semibold ring-2 ring-white"></span>
        </button>
    </div>
</div>

@once
<script>
    (function() {
        // ---------- Konfigurasi ----------
        const $w = $('#um-widget');
        if (!$w.length) return;
        const ME = parseInt($w.attr('data-user-id'), 10);
        const MY_NAME = $w.attr('data-user-name') || 'User';
        const BASE = $w.attr('data-base') || '/interbat/messenger';
        const CSRF = $('meta[name="csrf-token"]').attr('content');
        const RT_KEY = $w.attr('data-pusher-key');
        const RT_CLUSTER = $w.attr('data-pusher-cluster');
        const RT_AUTH = $w.attr('data-auth-endpoint');
        const MY_SESSION_HASH = $w.attr('data-session-hash') || '';
        const STICKERS = (() => {
            try {
                return JSON.parse($w.attr('data-stickers') || '[]');
            } catch (e) {
                return [];
            }
        })();

        // batas waktu (samakan dengan EDIT_MINUTES & UNSEND_MINUTES di controller)
        const EDIT_MS = 15 * 60 * 1000;
        const UNSEND_MS = 60 * 60 * 1000;
        const MAX_UPLOAD = 5 * 1024 * 1024; // samakan dengan max:5120 di controller

        // ---------- Elemen ----------
        const $panel = $('#um-panel'),
            $toggle = $('#um-toggle'),
            $badge = $('#um-badge');
        const $listView = $('#um-list-view'),
            $chatView = $('#um-chat-view');
        const $contacts = $('#um-contacts'),
            $search = $('#um-search');
        const $msgs = $('#um-messages'),
            $input = $('#um-input'),
            $send = $('#um-send'),
            $err = $('#um-error');
        const $menu = $('#um-menu'),
            $editBar = $('#um-edit-bar');
        const $replyBar = $('#um-reply-bar');
        const $picker = $('#um-picker'),
            $pkBody = $('#um-pk-body'),
            $pkSub = $('#um-pk-sub');
        const isMobile = () => window.matchMedia('(max-width: 639px)').matches;

        // ---------- State ----------
        const st = {
            contacts: new Map(), // id -> {id,name,avatar,unread,last}
            online: new Set(), // id user yang online
            active: null, // id lawan bicara yang sedang dibuka
            msgs: [],
            hasMore: false,
            busy: false,
            open: false,
            sig: null, // penanda perubahan pesan dari server
            since: null, // titik waktu server untuk sinkron edit/tarik
            editing: null, // pesan yang sedang diedit
            rtConn: false, // koneksi Pusher tersambung
            rtSub: false, // subscribe channel privat berhasil
            replying: null, // pesan yang sedang dibalas
        };

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': CSRF
            }
        });

        // ---------- Cache foto yang 404 (tidak diminta ulang, cukup inisial) ----------
        const BAD_KEY = 'um-bad-avatars';
        const badAvatars = new Set((() => {
            try {
                return JSON.parse(sessionStorage.getItem(BAD_KEY) || '[]');
            } catch (e) {
                return [];
            }
        })());

        function markBadAvatar(url) {
            if (!url || badAvatars.has(url)) return;
            badAvatars.add(url);
            try {
                sessionStorage.setItem(BAD_KEY, JSON.stringify([...badAvatars].slice(-500)));
            } catch (e) {}
        }

        // ---------- Util ----------
        const initials = n => (n || '?').trim().split(/\s+/).slice(0, 2).map(s => s[0]).join('').toUpperCase();
        const hue = n => {
            let h = 0;
            for (const c of (n || '')) h = (h * 31 + c.charCodeAt(0)) % 360;
            return h;
        };
        const fmtTime = iso => new Date(iso).toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        }).replace('.', ':');

        // label ringkas pesan (untuk daftar kontak & pratinjau dialog)
        const labelOf = m => m.unsent ? 'Pesan ditarik' :
            m.type === 'sticker' ? '🖼️ Stiker' :
            m.type === 'gif' ? '🎞️ GIF' : m.body;

        // stiker buatan user disimpan di storage -> boleh disimpan ke koleksi penerima
        const isUserSticker = m => m.type === 'sticker' && /\/stickers\/users\//.test(m.body || '');

        function dayLabel(d) {
            const today = new Date(),
                yest = new Date();
            yest.setDate(today.getDate() - 1);
            if (d.toDateString() === today.toDateString()) return 'Hari ini';
            if (d.toDateString() === yest.toDateString()) return 'Kemarin';
            return d.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }

        function shortTime(iso) {
            const d = new Date(iso),
                today = new Date(),
                yest = new Date();
            yest.setDate(today.getDate() - 1);
            if (d.toDateString() === today.toDateString()) return fmtTime(iso);
            if (d.toDateString() === yest.toDateString()) return 'Kemarin';
            return d.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: '2-digit'
            });
        }

        function flashError(msg) {
            $err.text(msg).removeClass('hidden');
            clearTimeout(flashError.t);
            flashError.t = setTimeout(() => $err.addClass('hidden'), 4000);
        }

        function errMsg(xhr, fallback) {
            if (xhr.status === 429) return 'Terlalu cepat. Tunggu sebentar.';
            if (xhr.status === 419) return 'Sesi berakhir. Muat ulang halaman.';
            if (xhr.status === 413) return 'File terlalu besar.';
            if (xhr.responseJSON && xhr.responseJSON.message && [403, 404, 422].includes(xhr.status)) {
                return xhr.responseJSON.message;
            }
            return fallback;
        }

        function autoResize() {
            const el = $input[0];
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        }

        function updateSend() {
            $send.prop('disabled', st.busy || !$input.val().trim());
        }

        function replaceMsg(m) {
            const i = st.msgs.findIndex(x => x.id === m.id);
            if (i >= 0) st.msgs[i] = m;
        }

        const escHtml = s => $('<div>').text(s == null ? '' : String(s)).html();

        // ---------- SweetAlert (fallback ke confirm bila Swal tidak ada) ----------
        function confirmDialog(o) {
            if (window.Swal) {
                return Swal.fire({
                    title: o.title,
                    html: o.html,
                    text: o.html ? undefined : o.text,
                    icon: o.icon || 'warning',
                    showCancelButton: true,
                    confirmButtonText: o.confirmText || 'Ya',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                    focusCancel: true,
                    returnFocus: false
                }).then(r => r.isConfirmed);
            }
            return Promise.resolve(window.confirm(o.text || o.title));
        }

        function toast(msg) {
            if (!window.Swal) return;
            Swal.fire({
                toast: true,
                position: 'top',
                icon: 'success',
                title: msg,
                showConfirmButton: false,
                timer: 1800,
                timerProgressBar: true
            });
        }

        const previewHtml = m => {
            const t = (labelOf(m) || '').replace(/\s+/g, ' ').trim();
            const s = t.length > 80 ? t.slice(0, 80) + '…' : t;
            return s ? '<div style="margin-top:.5rem;padding:.5rem .75rem;border-radius:.5rem;background:#f3f4f6;color:#374151;font-size:.85rem;text-align:left;word-break:break-word;">' + escHtml(s) + '</div>' : '';
        };

        // ---------- Avatar (foto / inisial) + titik status ----------
        function avatar(u, px, withDot, zoomable) {
            const $a = $('<div class="relative shrink-0">').css({
                width: px,
                height: px
            });
            const $c = $('<div class="w-full h-full rounded-full overflow-hidden flex items-center justify-center text-white font-semibold select-none">')
                .css({
                    background: 'hsl(' + hue(u.name) + ' 55% 48%)',
                    fontSize: Math.round(px * 0.38)
                });

            // foto yang pernah 404 dilewati -> langsung inisial (tanpa request baru)
            let hasPhoto = false;
            if (u.avatar && !badAvatars.has(u.avatar)) {
                hasPhoto = true;
                $('<img alt="" class="w-full h-full object-cover">').attr('src', u.avatar)
                    .on('error', function() {
                        markBadAvatar(u.avatar);
                        $(this).remove();
                        $c.text(initials(u.name));
                        $a.removeClass('cursor-zoom-in').off('click.zoom');
                    })
                    .appendTo($c);
            } else {
                $c.text(initials(u.name));
            }
            $a.append($c);

            if (withDot) {
                $a.append($('<span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full ring-2 ring-white">')
                    .addClass(st.online.has(u.id) ? 'bg-emerald-500' : 'bg-gray-300'));
            }

            if (zoomable && hasPhoto) {
                $a.addClass('cursor-zoom-in').attr('title', 'Klik untuk memperbesar foto')
                    .on('click.zoom', e => {
                        e.stopPropagation();
                        openZoom(u.avatar, u.name);
                    });
            }
            return $a;
        }

        // ---------- Zoom media (foto profil / stiker / GIF) — hanya lihat, digambar di canvas ----------
        const $zoom = $('#um-zoom'),
            zoomCv = document.getElementById('um-zoom-canvas');
        let zoomRaf = null,
            zoomImg = null,
            zoomBlobUrl = null,
            zoomToken = 0; // pembatalan callback async yang sudah kedaluwarsa

        function closeZoom() {
            zoomToken++;
            $zoom.removeClass('um-zoom-open');
            if (zoomRaf) cancelAnimationFrame(zoomRaf);
            zoomRaf = null;
            if (zoomImg) {
                zoomImg.remove();
                zoomImg = null;
            }
            zoomCv.getContext('2d').clearRect(0, 0, zoomCv.width, zoomCv.height);
            zoomCv.width = zoomCv.height = 0;
            if (zoomBlobUrl) {
                URL.revokeObjectURL(zoomBlobUrl);
                zoomBlobUrl = null;
            }
        }

        function openZoom(src, title) {
            if (!src) return;
            closeZoom();
            const token = zoomToken;
            $('#um-zoom-name').text(title || '');

            const show = url => {
                if (token !== zoomToken) return;

                const img = new Image();
                img.id = 'um-zoom-src';
                img.alt = '';
                img.draggable = false;

                img.onload = () => {
                    if (token !== zoomToken) return;

                    const box = $panel[0].getBoundingClientRect();
                    const maxW = Math.min(box.width - 32, 420);
                    const maxH = box.height * 0.65;
                    const scale = Math.min(maxW / img.naturalWidth, maxH / img.naturalHeight, 4);
                    const w = Math.max(1, Math.round(img.naturalWidth * scale));
                    const h = Math.max(1, Math.round(img.naturalHeight * scale));

                    zoomCv.width = w;
                    zoomCv.height = h;

                    // watermark digambar sekali di canvas terpisah, lalu ditempel tiap frame
                    const wm = document.createElement('canvas');
                    wm.width = w;
                    wm.height = h;
                    const wctx = wm.getContext('2d');
                    const label = 'Interbat · ' + MY_NAME + ' · ' + new Date().toLocaleString('id-ID');
                    wctx.rotate(-Math.PI / 8);
                    wctx.fillStyle = 'rgba(255,255,255,.28)';
                    wctx.font = '600 ' + Math.max(11, Math.round(w / 22)) + 'px sans-serif';
                    for (let y = -h; y < h * 2; y += Math.max(24, Math.round(h / 4))) {
                        for (let x = -w; x < w * 2; x += Math.max(80, Math.round(w / 1.4))) {
                            wctx.fillText(label, x, y);
                        }
                    }

                    zoomImg = img;
                    $zoom.addClass('um-zoom-open');

                    const ctx = zoomCv.getContext('2d');
                    const loop = () => {
                        if (token !== zoomToken) return;
                        ctx.clearRect(0, 0, w, h);
                        ctx.drawImage(img, 0, 0, w, h); // GIF / WebP beranimasi ikut bergerak
                        ctx.drawImage(wm, 0, 0);
                        zoomRaf = requestAnimationFrame(loop);
                    };
                    loop();
                };

                img.onerror = () => {
                    if (token === zoomToken) flashError('Gambar tidak dapat dimuat.');
                };

                $zoom.append(img); // harus ada di DOM supaya animasi GIF berjalan
                img.src = url;
            };

            // diambil sebagai blob -> URL asli tidak muncul di DOM; kalau CORS menolak, pakai URL langsung
            fetch(src, {
                    credentials: 'same-origin'
                })
                .then(r => {
                    if (!r.ok) throw new Error('bad');
                    return r.blob();
                })
                .then(blob => {
                    if (token !== zoomToken) return;
                    zoomBlobUrl = URL.createObjectURL(blob);
                    show(zoomBlobUrl);
                })
                .catch(() => show(src));
        }

        $('#um-zoom-close').on('click', closeZoom);
        $zoom.on('click', e => {
            if (e.target === $zoom[0]) closeZoom(); // klik area gelap -> tutup
        });
        $zoom.on('contextmenu dragstart selectstart', e => e.preventDefault());

        // ---------- Title halaman + suara notifikasi ----------
        let prevUnread = null; // null = belum pernah dihitung (tidak bunyi saat halaman baru dibuka)
        let audioCtx = null;
        let lastDing = 0;

        function unlockAudio() {
            try {
                const AC = window.AudioContext || window.webkitAudioContext;
                if (!AC) return;
                if (!audioCtx) audioCtx = new AC();
                if (audioCtx.state === 'suspended') audioCtx.resume();
            } catch (e) {}
        }
        ['click', 'keydown', 'touchstart'].forEach(ev =>
            document.addEventListener(ev, unlockAudio, {
                passive: true
            })
        );

        function playDing() {
            const now = Date.now();
            if (now - lastDing < 1000) return;
            lastDing = now;
            try {
                if (!audioCtx || audioCtx.state !== 'running') return;
                const t = audioCtx.currentTime;

                // compressor: mencegah suara pecah saat volume dinaikkan
                const comp = audioCtx.createDynamicsCompressor();
                comp.threshold.value = -12;
                comp.ratio.value = 6;
                comp.connect(audioCtx.destination);

                // 3 nada naik, tiap nada sedikit lebih panjang
                [
                    [880, 0],
                    [1175, 0.14],
                    [1568, 0.28]
                ].forEach(([freq, delay]) => {
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'triangle'; // lebih nyaring dari 'sine'
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.0001, t + delay);
                    gain.gain.exponentialRampToValueAtTime(0.9, t + delay + 0.02); // sebelumnya 0.25
                    gain.gain.exponentialRampToValueAtTime(0.0001, t + delay + 0.35);
                    osc.connect(gain).connect(comp);
                    osc.start(t + delay);
                    osc.stop(t + delay + 0.4);
                });
            } catch (e) {}
        }

        function updateTitle(total) {
            const clean = document.title.replace(/^\(\d+\+?\)\s*/, '');
            document.title = total > 0 ? '(' + (total > 99 ? '99+' : total) + ') ' + clean : clean;
        }

        // ---------- Badge total belum dibaca ----------
        function updateBadge() {
            let total = 0;
            st.contacts.forEach(c => total += c.unread || 0);

            if (prevUnread !== null && total > prevUnread) playDing();
            prevUnread = total;

            updateTitle(total);

            if (total > 0) {
                $badge.text(total > 99 ? '99+' : total).removeClass('hidden').css('display', 'flex')
                    .css({
                        alignItems: 'center',
                        justifyContent: 'center'
                    });
            } else {
                $badge.addClass('hidden').css('display', '');
            }
        }

        // ---------- Render daftar kontak ----------
        function renderContacts() {
            const q = $search.val().trim().toLowerCase();
            const list = [...st.contacts.values()]
                .filter(c => !q || c.name.toLowerCase().includes(q))
                .sort((a, b) => {
                    const ta = a.last ? Date.parse(a.last.created_at) : 0;
                    const tb = b.last ? Date.parse(b.last.created_at) : 0;
                    if (tb !== ta) return tb - ta;
                    const oa = st.online.has(a.id),
                        ob = st.online.has(b.id);
                    if (oa !== ob) return ob - oa;
                    return a.name.localeCompare(b.name);
                });

            $contacts.empty();
            if (!list.length) {
                $contacts.append('<p class="p-6 text-center text-sm text-gray-400">Tidak ada pengguna</p>');
                return;
            }

            list.forEach(c => {
                let preview = 'Mulai percakapan';
                if (c.last) {
                    preview = (c.last.sender_id === ME && !c.last.unsent ? 'Anda: ' : '') + labelOf(c.last);
                }

                const $top = $('<div class="flex items-center justify-between gap-2">').append(
                    $('<span class="truncate text-sm font-semibold text-gray-800">').text(c.name),
                    $('<span class="shrink-0 text-[11px] ' + (c.unread ? 'text-indigo-600 font-medium' : 'text-gray-400') + '">')
                    .text(c.last ? shortTime(c.last.created_at) : '')
                );

                const $bottom = $('<div class="flex items-center justify-between gap-2 mt-0.5">').append(
                    $('<span class="truncate text-xs ' + (c.unread ? 'text-gray-800 font-medium' : 'text-gray-500') + (c.last && c.last.unsent ? ' italic' : '') + '">').text(preview)
                );
                if (c.unread) {
                    $bottom.append($('<span class="shrink-0 min-w-[1.25rem] h-5 px-1.5 rounded-full bg-rose-500 text-white text-[11px] font-semibold flex items-center justify-center">')
                        .text(c.unread > 99 ? '99+' : c.unread));
                }

                $('<button type="button" class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 transition border-b border-gray-100">')
                    .append(avatar(c, 44, true), $('<div class="min-w-0 flex-1">').append($top, $bottom))
                    .on('click', () => openChat(c.id))
                    .appendTo($contacts);
            });
        }

        // ---------- Header percakapan ----------
        function renderHeader() {
            const c = st.contacts.get(st.active);
            if (!c) return;
            $('#um-chat-avatar').empty().append(avatar(c, 38, true, true));
            $('#um-chat-name').text(c.name);
            $('#um-chat-status').text(st.online.has(c.id) ? '● Online' : 'Offline');
        }

        // ---------- Status online ----------
        function refreshPresence() {
            const n = [...st.online].filter(id => id !== ME).length;
            $('#um-online-count').text(n + ' online');
            renderContacts();
            renderHeader();
        }

        // ---------- Menu aksi pesan ----------
        function closeMenu() {
            $menu.addClass('hidden').empty().removeData('mid');
        }

        function openMenu(m, $btn) {
            if (!$menu.hasClass('hidden') && $menu.data('mid') === m.id) return closeMenu();

            const mine = m.sender_id === ME;
            const age = Date.now() - Date.parse(m.created_at);
            const items = [];
            if (!m.unsent) {
                items.push({
                    label: 'Balas',
                    fn: () => startReply(m)
                });
            }
            // simpan stiker buatan orang lain ke koleksi sendiri (seperti WhatsApp)
            if (!mine && !m.unsent && isUserSticker(m)) {
                items.push({
                    label: 'Simpan ke stiker saya',
                    fn: () => saveSticker(m)
                });
            }
            // edit hanya untuk pesan teks
            if (mine && !m.unsent && age <= EDIT_MS && (m.type || 'text') === 'text') {
                items.push({
                    label: 'Edit pesan',
                    fn: () => startEdit(m)
                });
            }
            if (mine && !m.unsent && age <= UNSEND_MS) {
                items.push({
                    label: 'Tarik pesan (untuk semua)',
                    fn: () => unsendMsg(m),
                    danger: true
                });
            }
            items.push({
                label: 'Hapus untuk saya',
                fn: () => deleteMsg(m),
                danger: true
            });

            $menu.empty();
            items.forEach(it => {
                $('<button type="button" role="menuitem" class="w-full text-left px-3.5 py-2 hover:bg-gray-50 transition ' +
                        (it.danger ? 'text-rose-600' : 'text-gray-700') + '">')
                    .text(it.label)
                    .on('click', () => {
                        closeMenu();
                        it.fn();
                    })
                    .appendTo($menu);
            });

            $menu.removeClass('hidden').data('mid', m.id);

            const p = $panel[0].getBoundingClientRect(),
                b = $btn[0].getBoundingClientRect();
            const mw = $menu.outerWidth(),
                mh = $menu.outerHeight();
            let left = mine ? b.right - p.left - mw : b.left - p.left;
            left = Math.max(8, Math.min(left, p.width - mw - 8));
            let top = b.bottom - p.top + 4;
            if (top + mh > p.height - 8) top = Math.max(8, b.top - p.top - mh - 4);
            $menu.css({
                left: left,
                top: top
            });
        }

        // ---------- Render pesan ----------
        function renderMessages(mode) {
            closeMenu();
            const el = $msgs[0],
                prevTop = el.scrollTop,
                prevH = el.scrollHeight;
            $msgs.empty();

            if (st.hasMore) {
                $('<button type="button" class="block mx-auto mb-2 text-xs font-medium text-indigo-600 hover:text-indigo-800 underline underline-offset-2">')
                    .text('Muat pesan sebelumnya').on('click', loadOlder).appendTo($msgs);
            }

            if (!st.msgs.length) {
                $msgs.append('<p class="py-10 text-center text-sm text-gray-400">Belum ada pesan. Sapa dulu 👋</p>');
            }

            let lastDay = '';
            st.msgs.forEach(m => {
                const d = new Date(m.created_at),
                    day = d.toDateString();
                if (day !== lastDay) {
                    lastDay = day;
                    $msgs.append($('<div class="flex justify-center py-1">').append(
                        $('<span class="px-2.5 py-0.5 rounded-full bg-gray-200/70 text-[11px] text-gray-500">').text(dayLabel(d))));
                }

                const mine = m.sender_id === ME;
                const isMedia = !m.unsent && (m.type === 'sticker' || m.type === 'gif');

                let $b;
                if (isMedia) {
                    // stiker: tanpa gelembung | GIF: gambar dengan sudut membulat (GIF asli, beranimasi)
                    $b = $('<div>').addClass(m.type === 'gif' ?
                        'rounded-2xl overflow-hidden ' + (mine ? 'rounded-br-md' : 'rounded-bl-md border border-gray-200') : '');
                    $('<img alt="">')
                        .addClass(m.type === 'sticker' ? 'w-32 h-32 object-contain' : 'block max-w-[13rem] max-h-56')
                        .addClass('cursor-zoom-in')
                        .attr('src', m.body)
                        .attr('title', 'Klik untuk memperbesar')
                        .on('click', () => openZoom(m.body, senderName(m.sender_id)))
                        .on('load', () => {
                            if (mode === 'bottom') el.scrollTop = el.scrollHeight;
                        })
                        .on('error', function() {
                            $(this).replaceWith('<span class="block px-3 py-2 text-xs text-gray-400">Media tidak dapat dimuat</span>');
                        })
                        .appendTo($b);
                } else {
                    $b = $('<div class="px-3 py-2 text-[15px] sm:text-sm leading-relaxed whitespace-pre-wrap break-words rounded-2xl ' +
                        (mine ? 'bg-indigo-600 text-white rounded-br-md' : 'bg-white text-gray-800 border border-gray-200 shadow-sm rounded-bl-md') + '">');
                    if (m.reply && !m.unsent) $b.append(quoteEl(m.reply, mine, false));
                    $b.append($('<div>').text(m.unsent ? '🚫 Pesan ini telah ditarik' : m.body));
                    if (m.unsent) $b.addClass('italic opacity-70');
                }
                if (st.editing && st.editing.id === m.id) $b.addClass('ring-2 ring-indigo-300');

                const $meta = $('<div class="mt-0.5 flex items-center gap-1 text-[11px] text-gray-400 ' + (mine ? 'justify-end' : '') + '">')
                    .append($('<span>').text((m.edited_at && !m.unsent ? 'diedit · ' : '') + fmtTime(m.created_at)));
                if (mine && !m.unsent) {
                    $meta.append($('<span class="' + (m.read_at ? 'text-sky-500' : 'text-gray-400') + '">')
                        .attr('title', m.read_at ? 'Dibaca' : 'Terkirim')
                        .text(m.read_at ? '✓✓' : '✓'));
                }

                const $more = $('<button type="button" aria-label="Opsi pesan" aria-haspopup="menu" ' +
                        'class="um-more shrink-0 mt-1 p-1 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-200/70 ' +
                        'sm:opacity-0 group-hover:opacity-100 focus:opacity-100 transition">')
                    .html('<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>')
                    .on('click', function(e) {
                        e.stopPropagation();
                        openMenu(m, $(this));
                    });
                // klik kanan / long-press pada stiker & GIF -> menu aksi widget (bukan menu browser)
                if (isMedia) {
                    $b.on('contextmenu', function(e) {
                        e.preventDefault();
                        openMenu(m, $more);
                    });
                }
                const $wrap = $('<div class="max-w-[80%] min-w-0">').append($b, $meta);
                // stiker/GIF tidak punya gelembung teks, jadi kutipan ditaruh di atasnya
                if (isMedia && m.reply) $wrap.prepend(quoteEl(m.reply, mine, true));
                const $row = $('<div class="group flex items-start gap-1 ' + (mine ? 'justify-end' : 'justify-start') + '">')
                    .attr('data-mid', m.id);
                if (mine) $row.append($more, $wrap);
                else $row.append($wrap, $more);

                $msgs.append($row);
            });

            if (mode === 'bottom') el.scrollTop = el.scrollHeight;
            else if (mode === 'older') el.scrollTop = el.scrollHeight - prevH + prevTop;
            else el.scrollTop = prevTop;
        }

        const nearBottom = () => {
            const e = $msgs[0];
            return e.scrollHeight - e.scrollTop - e.clientHeight < 80;
        };

        // ---------- Pindah view ----------
        function showView(list) {
            $listView.toggleClass('flex', list).toggleClass('hidden', !list);
            $chatView.toggleClass('flex', !list).toggleClass('hidden', list);
        }

        // ---------- Data ----------
        function loadContacts() {
            return $.get(BASE + '/contacts').done(res => {
                st.contacts = new Map(res.contacts.map(c => [c.id, c]));
                res.contacts.forEach(c => {
                    if (c.online) st.online.add(c.id);
                });
                updateBadge();
                renderContacts();
                renderHeader();
            });
        }

        function markRead(id) {
            $.post(BASE + '/' + id + '/read').done(() => {
                const c = st.contacts.get(id);
                if (c) c.unread = 0;
                updateBadge();
                renderContacts();
            });
        }

        // tandai terbaca bila percakapan terbuka & terlihat
        function syncRead() {
            const c = st.contacts.get(st.active);
            if (st.open && st.active && c && c.unread > 0 && document.visibilityState === 'visible') markRead(st.active);
        }

        function openChat(id) {
            const c = st.contacts.get(id);
            if (!c) return;
            cancelEdit();
            cancelReply();
            setPicker(false);
            st.active = id;
            st.msgs = [];
            st.hasMore = false;
            st.since = null;
            showView(false);
            renderHeader();
            $msgs.html('<p class="py-10 text-center text-sm text-gray-400">Memuat…</p>');
            $input.val('');
            autoResize();
            updateSend();

            $.get(BASE + '/' + id).done(res => {
                if (st.active !== id) return;
                st.msgs = res.messages;
                st.hasMore = res.has_more;
                st.since = res.now || null;
                renderMessages('bottom');
                if (c.unread > 0) markRead(id);
            }).fail(() => $msgs.html('<p class="py-10 text-center text-sm text-red-500">Gagal memuat pesan.</p>'));

            setTimeout(() => $input.trigger('focus'), 50);
        }

        function closeChat() {
            cancelEdit();
            cancelReply();
            closeZoom();
            closeMenu();
            setPicker(false);
            st.active = null;
            st.since = null;
            showView(true);
            renderContacts();
        }

        function loadOlder() {
            if (!st.msgs.length || !st.active) return;
            const id = st.active;
            $.get(BASE + '/' + id, {
                before: st.msgs[0].id
            }).done(res => {
                if (st.active !== id) return;
                st.msgs = res.messages.concat(st.msgs);
                st.hasMore = res.has_more;
                renderMessages('older');
            });
        }

        // ---------- Balas pesan ----------
        function senderName(id) {
            return id === ME ? 'Anda' : ((st.contacts.get(id) || {}).name || 'Pengguna');
        }

        function startReply(m) {
            cancelEdit();
            st.replying = m;
            $('#um-reply-name').text(senderName(m.sender_id));
            $('#um-reply-text').text(labelOf(m));
            $replyBar.removeClass('hidden').addClass('flex');
            if (!isMobile()) $input.trigger('focus');
        }

        function cancelReply() {
            if (!st.replying) return;
            st.replying = null;
            $replyBar.addClass('hidden').removeClass('flex');
        }

        // kutipan pesan yang dibalas (di dalam gelembung, atau berdiri sendiri untuk stiker/GIF)
        function quoteEl(r, mine, standalone) {
            const label = r.unsent ? '🚫 Pesan ditarik' :
                r.type === 'sticker' ? '🖼️ Stiker' :
                r.type === 'gif' ? '🎞️ GIF' : (r.body || '');
            const tone = standalone ? 'bg-gray-200/70 text-gray-600 border-indigo-400' :
                (mine ? 'bg-white/15 text-indigo-50 border-white/70' : 'bg-gray-100 text-gray-600 border-indigo-500');
            const nameTone = standalone || !mine ? 'text-indigo-600' : 'text-white';

            return $('<button type="button" class="block w-full text-left mb-1.5 rounded-lg border-l-4 px-2 py-1 text-xs whitespace-normal ' + tone + '">')
                .append(
                    $('<div class="font-semibold truncate ' + nameTone + '">').text(senderName(r.sender_id)),
                    $('<div class="truncate">').text(label)
                )
                .on('click', e => {
                    e.stopPropagation();
                    jumpTo(r.id);
                });
        }

        function jumpTo(id) {
            const $r = $msgs.find('[data-mid="' + id + '"]');
            if (!$r.length) return flashError('Pesan asli ada di riwayat yang lebih lama.');
            $r[0].scrollIntoView({
                block: 'center',
                behavior: 'smooth'
            });
            $r.addClass('um-flash');
            setTimeout(() => $r.removeClass('um-flash'), 1300);
        }

        // ---------- Edit / tarik / hapus ----------
        function startEdit(m) {
            cancelReply();
            setPicker(false);
            st.editing = m;
            $editBar.removeClass('hidden').addClass('flex');
            $input.val(m.body);
            autoResize();
            updateSend();
            renderMessages('keep');
            setTimeout(() => {
                $input.trigger('focus');
                const l = $input.val().length;
                $input[0].setSelectionRange(l, l);
            }, 30);
        }

        function cancelEdit() {
            if (!st.editing) return;
            st.editing = null;
            $editBar.addClass('hidden').removeClass('flex');
            $input.val('');
            autoResize();
            updateSend();
            if (st.active) renderMessages('keep');
        }

        function saveEdit(body) {
            const m = st.editing;
            if (!m) return;
            if (body === m.body) return cancelEdit();

            st.busy = true;
            updateSend();

            $.ajax({
                    url: BASE + '/message/' + m.id,
                    method: 'PATCH',
                    data: {
                        body: body
                    }
                })
                .done(res => {
                    replaceMsg(res.message);
                    st.editing = null;
                    $editBar.addClass('hidden').removeClass('flex');
                    $input.val('');
                    autoResize();
                    renderMessages('keep');
                    loadContacts();
                })
                .fail(xhr => flashError(errMsg(xhr, 'Gagal menyimpan perubahan. Coba lagi.')))
                .always(() => {
                    st.busy = false;
                    updateSend();
                    $input.trigger('focus');
                });
        }

        function unsendMsg(m) {
            confirmDialog({
                title: 'Tarik pesan?',
                html: 'Pesan akan ditarik untuk <b>semua orang</b> dan tidak dapat dikembalikan.' + previewHtml(m),
                text: 'Tarik pesan ini untuk semua orang?',
                confirmText: 'Ya, tarik'
            }).then(ok => {
                if (!ok) return;

                $.post(BASE + '/message/' + m.id + '/unsend')
                    .done(res => {
                        if (st.editing && st.editing.id === m.id) cancelEdit();
                        replaceMsg(res.message);
                        renderMessages('keep');
                        loadContacts();
                        toast('Pesan ditarik');
                    })
                    .fail(xhr => flashError(errMsg(xhr, 'Gagal menarik pesan. Coba lagi.')));
            });
        }

        function deleteMsg(m) {
            confirmDialog({
                title: 'Hapus pesan?',
                html: 'Pesan hanya dihapus untuk <b>Anda</b>. Lawan bicara tetap dapat melihatnya.' + (m.unsent ? '' : previewHtml(m)),
                text: 'Hapus pesan ini hanya untuk Anda?',
                confirmText: 'Ya, hapus'
            }).then(ok => {
                if (!ok) return;

                $.ajax({
                        url: BASE + '/message/' + m.id,
                        method: 'DELETE'
                    })
                    .done(() => {
                        if (st.editing && st.editing.id === m.id) cancelEdit();
                        st.msgs = st.msgs.filter(x => x.id !== m.id);
                        renderMessages('keep');
                        loadContacts();
                        toast('Pesan dihapus');
                    })
                    .fail(xhr => flashError(errMsg(xhr, 'Gagal menghapus pesan. Coba lagi.')));
            });
        }

        // ---------- Emoji / GIF / Stiker ----------
        const EMOJI = {
            '😀': '😀 😃 😄 😁 😆 😅 😂 🤣 🥲 😊 😇 🙂 🙃 😉 😍 🥰 😘 😋 😛 😜 🤪 🤗 🤔 🤭 🤫 😐 😑 😶 🙄 😏 😴 🤤 😷 🤒 🥵 🥶 😵 🤯 🤠 😎 🤓 😢 😭 😤 😡 🥺 😱 😳 😬 🫠',
            '👍': '👍 👎 👌 ✌️ 🤞 🤟 🤘 🤙 👈 👉 👆 👇 ☝️ 👏 🙌 🫶 🤝 🙏 💪 👋 🤚 ✋ 🖖 ✍️ 🫡 🫰 🤌',
            '❤️': '❤️ 🧡 💛 💚 💙 💜 🖤 🤍 🤎 💔 ❣️ 💕 💞 💓 💗 💖 💘 💝 ✨ 🔥 💯 💥 💫 ⭐ 🌟 🎉 🎊 🎁',
            '🐶': '🐶 🐱 🐭 🐹 🐰 🦊 🐻 🐼 🐨 🐯 🦁 🐮 🐷 🐸 🐵 🐔 🐧 🐦 🦆 🦉 🐢 🐍 🐙 🐠 🐬 🌸 🌹 🌻 🌴 🍀',
            '🍔': '🍔 🍟 🍕 🌭 🥪 🌮 🌯 🍜 🍝 🍣 🍱 🍛 🍚 🍗 🍖 🍳 🥗 🍿 🍩 🍪 🎂 🍰 🍫 🍦 ☕ 🍵 🥤 🍺',
            '⚽': '⚽ 🏀 🏈 🎾 🏐 🎱 🏓 🏸 🥊 🎮 🎲 🎯 🎵 🎶 🎤 🎧 📱 💻 📷 📌 📎 ✅ ❌ ⚠️ ❓ ❗ 💡 🔔 🚗 ✈️ 🏠'
        };
        const pk = {
            open: false,
            tab: 'emoji',
            cat: Object.keys(EMOJI)[0],
            gifT: null,
            gifQ: '',
            mine: null, // koleksi stiker saya (null = belum dimuat)
            loadingMine: false,
            uploading: false
        };

        function insertAtCursor(txt) {
            const el = $input[0],
                s = el.selectionStart,
                e = el.selectionEnd,
                v = el.value;
            if (v.length + txt.length > 2000) return;
            el.value = v.slice(0, s) + txt + v.slice(e);
            const p = s + txt.length;
            el.setSelectionRange(p, p);
            autoResize();
            updateSend();
            if (!isMobile()) el.focus(); // di HP jangan fokus supaya keyboard tidak menutupi picker
        }

        function setPicker(open) {
            pk.open = !!open && !!st.active;
            $picker.toggleClass('hidden', !pk.open).toggleClass('flex', pk.open);
            if (pk.open) renderPicker();
        }

        const imgTile = (url, cls) => $('<img alt="" loading="lazy">').addClass(cls).attr('src', url);

        function renderPicker() {
            $('.um-tab').each(function() {
                const on = $(this).data('tab') === pk.tab;
                $(this).toggleClass('text-indigo-600 border-b-2 border-indigo-600', on)
                    .toggleClass('text-gray-500', !on);
            });
            $pkSub.empty();
            $pkBody.empty();

            // ----- EMOJI -----
            if (pk.tab === 'emoji') {
                const $cats = $('<div class="flex gap-1 mb-1">').appendTo($pkSub);
                Object.keys(EMOJI).forEach(k => {
                    $('<button type="button" class="px-2 py-1 rounded-lg text-lg hover:bg-gray-100">')
                        .toggleClass('bg-gray-100', k === pk.cat)
                        .text(k)
                        .on('click', () => {
                            pk.cat = k;
                            renderPicker();
                        })
                        .appendTo($cats);
                });
                const $g = $('<div class="grid grid-cols-8 gap-1">').appendTo($pkBody);
                EMOJI[pk.cat].split(' ').forEach(e => {
                    $('<button type="button" class="text-2xl leading-none p-1 rounded-lg hover:bg-gray-100 active:scale-90 transition">')
                        .text(e)
                        .on('click', () => insertAtCursor(e))
                        .appendTo($g);
                });
            }

            // ----- STIKER (saya + bawaan) -----
            if (pk.tab === 'sticker') {
                if (pk.mine === null && !pk.loadingMine) {
                    pk.loadingMine = true;
                    $.get(BASE + '/stickers')
                        .done(r => {
                            pk.mine = r.stickers || [];
                        })
                        .fail(() => {
                            pk.mine = [];
                        })
                        .always(() => {
                            pk.loadingMine = false;
                            if (pk.open && pk.tab === 'sticker') renderPicker();
                        });
                }

                $pkBody.append('<p class="mb-1 text-[11px] font-medium text-gray-400">Stiker saya</p>');
                const $g = $('<div class="grid grid-cols-4 gap-2">').appendTo($pkBody);

                // tile tambah stiker
                $('<button type="button" title="Tambah stiker dari gambar" aria-label="Tambah stiker" ' +
                        'class="aspect-square rounded-xl border-2 border-dashed border-gray-300 text-gray-400 hover:text-indigo-600 ' +
                        'hover:border-indigo-400 flex flex-col items-center justify-center text-[11px] gap-0.5 transition">')
                    .html('<span class="text-2xl leading-none">+</span>Tambah')
                    .on('click', () => $('#um-file-sticker').trigger('click'))
                    .appendTo($g);

                (pk.mine || []).forEach(s => {
                    const $t = $('<div class="relative aspect-square">').appendTo($g);
                    $('<button type="button" class="w-full h-full rounded-lg hover:bg-gray-100 p-1 active:scale-95 transition">')
                        .append(imgTile(s.url, 'w-full h-full object-contain'))
                        .on('click', () => send({
                            type: 'sticker',
                            url: s.url
                        }))
                        .appendTo($t);
                    $('<button type="button" aria-label="Hapus stiker" title="Hapus stiker" ' +
                            'class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-gray-700/80 text-white text-[12px] leading-none flex items-center justify-center hover:bg-rose-600">')
                        .text('×')
                        .on('click', e => {
                            e.stopPropagation();
                            deleteMySticker(s);
                        })
                        .appendTo($t);
                });

                if (pk.mine === null) {
                    $pkBody.append('<p class="mt-1 text-center text-xs text-gray-400">Memuat…</p>');
                }

                if (STICKERS.length) {
                    $pkBody.append('<p class="mt-3 mb-1 text-[11px] font-medium text-gray-400">Bawaan</p>');
                    const $g2 = $('<div class="grid grid-cols-4 gap-2">').appendTo($pkBody);
                    STICKERS.forEach(u => {
                        $('<button type="button" class="aspect-square rounded-lg hover:bg-gray-100 p-1 active:scale-95 transition">')
                            .append(imgTile(u, 'w-full h-full object-contain'))
                            .on('click', () => send({
                                type: 'sticker',
                                url: u
                            }))
                            .appendTo($g2);
                    });
                }
            }

            // ----- GIF (Giphy + unggah sendiri) -----
            if (pk.tab === 'gif') {
                const $row = $('<div class="flex items-center gap-1">').appendTo($pkSub);
                $('<input type="search" placeholder="Cari GIF…" autocomplete="off" ' +
                        'class="flex-1 min-w-0 rounded-lg border border-gray-300 bg-gray-50 px-3 py-1.5 text-sm focus:outline-none focus:border-cyan-500">')
                    .val(pk.gifQ)
                    .on('input', function() {
                        const q = this.value.trim();
                        clearTimeout(pk.gifT);
                        pk.gifT = setTimeout(() => loadGifs(q), 400);
                    })
                    .appendTo($row);
                $('<button type="button" title="Unggah file GIF dari perangkat" ' +
                        'class="shrink-0 px-2.5 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50 transition">')
                    .text('Unggah')
                    .on('click', () => $('#um-file-gif').trigger('click'))
                    .appendTo($row);
                loadGifs(pk.gifQ);
            }
        }

        function loadGifs(q) {
            pk.gifQ = q;
            $pkBody.html('<p class="py-8 text-center text-sm text-gray-400">Memuat…</p>');
            $.get(BASE + '/gifs', {
                q: q
            }).done(res => {
                if (pk.tab !== 'gif' || pk.gifQ !== q) return;
                $pkBody.empty();
                if (!res.gifs.length) {
                    $pkBody.html('<p class="py-8 text-center text-sm text-gray-400">GIF tidak ditemukan.</p>');
                    return;
                }
                const $g = $('<div class="columns-2 gap-2">').appendTo($pkBody);
                res.gifs.forEach(g => {
                    $('<button type="button" class="block w-full mb-2 rounded-lg overflow-hidden bg-gray-100 hover:opacity-80 transition">')
                        .append(imgTile(g.preview, 'w-full'))
                        .on('click', () => send({
                            type: 'gif',
                            url: g.url
                        }))
                        .appendTo($g);
                });
                $pkBody.append('<p class="pt-1 text-center text-[10px] text-gray-400">Powered by GIPHY</p>');
            }).fail(() => {
                if (pk.tab === 'gif' && pk.gifQ === q) {
                    $pkBody.html('<p class="py-8 text-center text-sm text-red-500">Gagal memuat GIF.</p>');
                }
            });
        }

        // ---------- Stiker buatan sendiri ----------
        function addMySticker(s) {
            if (!pk.mine) return; // belum dimuat -> akan ikut saat dimuat dari server
            if (!pk.mine.some(x => x.id === s.id)) pk.mine.unshift(s);
        }

        function uploadSticker(file) {
            if (pk.uploading) return;
            if (!/^image\/(png|jpeg|webp|gif)$/.test(file.type)) return flashError('Format harus PNG, JPG, WebP, atau GIF.');
            if (file.size > MAX_UPLOAD) return flashError('Ukuran gambar maksimal 5 MB.');

            const fd = new FormData();
            fd.append('file', file);
            pk.uploading = true;

            $.ajax({
                    url: BASE + '/stickers',
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                })
                .done(r => {
                    addMySticker(r.sticker);
                    if (pk.open && pk.tab === 'sticker') renderPicker();
                    toast('Stiker ditambahkan');
                })
                .fail(xhr => flashError(errMsg(xhr, 'Gagal menambah stiker. Coba lagi.')))
                .always(() => {
                    pk.uploading = false;
                });
        }

        function saveSticker(m) {
            $.post(BASE + '/stickers/save', {
                    url: m.body
                })
                .done(r => {
                    addMySticker(r.sticker);
                    toast(r.duplicate ? 'Stiker sudah ada di koleksi' : 'Stiker disimpan');
                })
                .fail(xhr => flashError(errMsg(xhr, 'Gagal menyimpan stiker.')));
        }

        function deleteMySticker(s) {
            confirmDialog({
                title: 'Hapus stiker?',
                text: 'Stiker dihapus dari koleksi Anda. Pesan yang sudah terkirim tidak terpengaruh.',
                confirmText: 'Ya, hapus'
            }).then(ok => {
                if (!ok) return;

                $.ajax({
                        url: BASE + '/stickers/' + s.id,
                        method: 'DELETE'
                    })
                    .done(() => {
                        pk.mine = (pk.mine || []).filter(x => x.id !== s.id);
                        if (pk.open && pk.tab === 'sticker') renderPicker();
                    })
                    .fail(xhr => flashError(errMsg(xhr, 'Gagal menghapus stiker.')));
            });
        }

        // ---------- Unggah GIF sendiri -> langsung dikirim sebagai GIF ----------
        function uploadGif(file) {
            if (pk.uploading) return;
            if (file.type !== 'image/gif') return flashError('Pilih file berformat GIF.');
            if (file.size > MAX_UPLOAD) return flashError('Ukuran GIF maksimal 5 MB.');

            const fd = new FormData();
            fd.append('file', file);
            pk.uploading = true;

            $.ajax({
                    url: BASE + '/gif-upload',
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                })
                .done(r => send({
                    type: 'gif',
                    url: r.url
                }))
                .fail(xhr => flashError(errMsg(xhr, 'Gagal mengunggah GIF. Coba lagi.')))
                .always(() => {
                    pk.uploading = false;
                });
        }

        $('#um-file-sticker').on('change', function() {
            const f = this.files && this.files[0];
            this.value = '';
            if (f) uploadSticker(f);
        });
        $('#um-file-gif').on('change', function() {
            const f = this.files && this.files[0];
            this.value = '';
            if (f) uploadGif(f);
        });

        $('#um-emoji-btn').on('click', () => setPicker(!pk.open));
        $('.um-tab').on('click', function() {
            pk.tab = $(this).data('tab');
            renderPicker();
        });

        // ---------- Kirim (teks, stiker, atau GIF) ----------
        function send(media) {
            const body = media ? media.url : $input.val().trim();
            const type = media ? media.type : 'text';
            if (!body || st.busy || !st.active) return;
            if (!media && st.editing) return saveEdit(body);
            if (media && st.editing) cancelEdit();

            const to = st.active;
            const data = {
                body: body,
                type: type
            };
            if (st.replying) data.reply_to_id = st.replying.id;

            st.busy = true;
            updateSend();

            $.post(BASE + '/' + to, data)
                .done(res => {
                    const m = res.message;
                    cancelReply();
                    if (!media) {
                        $input.val('');
                        autoResize();
                    } else {
                        setPicker(false);
                    }
                    const c = st.contacts.get(to);
                    if (c) c.last = m;
                    if (st.active === to && !st.msgs.some(x => x.id === m.id)) {
                        st.msgs.push(m);
                        renderMessages('bottom');
                    }
                    renderContacts();
                })
                .fail(xhr => flashError(errMsg(xhr, 'Gagal mengirim pesan. Coba lagi.')))
                .always(() => {
                    st.busy = false;
                    updateSend();
                    if (!media) $input.trigger('focus');
                });
        }

        // ---------- Buka / tutup panel ----------
        function openPanel() {
            st.open = true;
            $panel.removeClass('hidden').addClass('um-open');
            $toggle.attr('aria-expanded', 'true');
            $('#um-icon-open').addClass('hidden');
            $('#um-icon-close').removeClass('hidden');
            if (isMobile()) $toggle.addClass('hidden');
            document.body.classList.toggle('overflow-hidden', isMobile());
            if (st.active) {
                renderMessages('bottom');
                syncRead();
            }
        }

        function closePanel() {
            closeMenu();
            closeZoom();
            setPicker(false);
            st.open = false;
            $panel.addClass('hidden').removeClass('um-open');
            $toggle.removeClass('hidden').attr('aria-expanded', 'false');
            $('#um-icon-open').removeClass('hidden');
            $('#um-icon-close').addClass('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        $toggle.on('click', () => st.open ? closePanel() : openPanel());
        $('.um-close').on('click', closePanel);
        $('#um-back').on('click', closeChat);
        $('#um-edit-cancel').on('click', cancelEdit);
        $('#um-reply-cancel').on('click', cancelReply);
        // Escape: (popup Swal) -> zoom -> tutup menu -> tutup picker -> batal edit -> batal balas -> tutup panel
        $(document).on('keydown', e => {
            if (e.key !== 'Escape' || !st.open) return;
            if (document.body.classList.contains('swal2-shown')) return; // Esc milik SweetAlert
            if ($zoom.hasClass('um-zoom-open')) return closeZoom();
            if (!$menu.hasClass('hidden')) return closeMenu();
            if (pk.open) return setPicker(false);
            if (st.editing) return cancelEdit();
            if (st.replying) return cancelReply();
            closePanel();
        });

        // klik di luar menu -> tutup menu
        $(document).on('click', e => {
            if (!$(e.target).closest('#um-menu, .um-more').length) closeMenu();
        });
        $msgs.on('scroll', closeMenu);
        // stiker/GIF/gambar di widget: tanpa menu klik kanan browser & tanpa drag
        $w.on('contextmenu', 'img', e => e.preventDefault());
        $w.on('dragstart', 'img', e => e.preventDefault());
        $search.on('input', renderContacts);
        $input.on('input', () => {
            autoResize();
            updateSend();
        });
        $input.on('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey && !e.originalEvent.isComposing) {
                e.preventDefault();
                send();
            }
        });
        $send.on('click', () => send());

        // ---------- Polling (+ dipicu sinyal realtime) ----------
        const lastMsgId = () => st.msgs.length ? st.msgs[st.msgs.length - 1].id : 0;
        const rtOn = () => st.rtConn && st.rtSub;

        let pollTimer = null,
            polling = false,
            pollAgain = false;

        function pollDelay() {
            if (document.visibilityState !== 'visible') return rtOn() ? 60000 : 20000;
            if (rtOn()) return 30000; // realtime aktif: polling hanya untuk status online & cadangan
            return st.open ? 3000 : 8000;
        }

        function schedulePoll(ms) {
            clearTimeout(pollTimer);
            pollTimer = setTimeout(poll, ms == null ? pollDelay() : ms);
        }

        // panggil segera (sinyal realtime beruntun digabung jadi satu request)
        function pollNow() {
            if (polling) {
                pollAgain = true;
                return;
            }
            schedulePoll(0);
        }

        function poll() {
            if (polling) return;
            polling = true;
            pollAgain = false;

            const active = st.active;
            $.get(BASE + '/poll', {
                    active: active || '',
                    after: active ? lastMsgId() : 0,
                    since: active ? (st.since || '') : ''
                })
                .done(res => {
                    // 1. status online
                    st.online = new Set(res.online);
                    refreshPresence();

                    // 2. ada perubahan pesan (baru/edit/tarik) -> muat ulang daftar kontak
                    if (res.sig !== st.sig) {
                        const first = st.sig === null;
                        st.sig = res.sig;
                        if (!first) loadContacts();
                    }

                    if (active && st.active === active) {
                        let dirty = false;
                        const stick = nearBottom();

                        // 3. pesan baru di percakapan yang sedang dibuka
                        if (res.messages.length) {
                            let added = false;
                            res.messages.forEach(m => {
                                if (!st.msgs.some(x => x.id === m.id)) {
                                    st.msgs.push(m);
                                    added = true;
                                }
                            });
                            if (added) playDing();
                            dirty = true;
                            if (st.open && document.visibilityState === 'visible') markRead(active);
                        }

                        // 4. pesan diedit / ditarik
                        (res.changes || []).forEach(m => {
                            const old = st.msgs.find(x => x.id === m.id);
                            if (!old) return;
                            if (old.body !== m.body || old.unsent !== m.unsent || old.edited_at !== m.edited_at) {
                                replaceMsg(m);
                                dirty = true;
                                if (m.unsent && st.editing && st.editing.id === m.id) cancelEdit();
                            }
                        });
                        if (res.now) st.since = res.now;

                        // 5. centang dibaca
                        const readSet = new Set(res.read);
                        const now = new Date().toISOString();
                        st.msgs.forEach(m => {
                            if (m.sender_id === ME && !m.read_at && readSet.has(m.id)) {
                                m.read_at = now;
                                dirty = true;
                            }
                        });

                        if (dirty) renderMessages(stick && res.messages.length ? 'bottom' : 'keep');
                    }
                })
                .always(() => {
                    polling = false;
                    schedulePoll(pollAgain ? 0 : pollDelay());
                });
        }

        // langsung segarkan saat tab kembali aktif
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                pollNow();
                syncRead();
            }
        });

        // ---------- Realtime (Laravel Echo + Pusher, channel privat) ----------
        function initRealtime() {
            try {
                // window.Echo bisa berupa class (belum dibuat) atau instance (sudah dibuat halaman) -> ambil class-nya
                const EchoCls = typeof window.Echo === 'function' ? window.Echo : (window.Echo && window.Echo.constructor);
                if (!EchoCls || typeof window.Pusher === 'undefined' || !RT_KEY) return;

                const echo = new EchoCls({
                    broadcaster: 'pusher',
                    key: RT_KEY,
                    cluster: RT_CLUSTER,
                    forceTLS: true,
                    authEndpoint: RT_AUTH,
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': CSRF
                        }
                    }
                });

                echo.connector.pusher.connection.bind('state_change', s => {
                    st.rtConn = s.current === 'connected';
                    if (st.rtConn) pollNow(); // sinkron ulang setelah (re)connect
                    else schedulePoll(); // putus: kembali ke polling cepat
                });

                const ch = echo.private('messenger.' + ME);
                ch.listen('.SessionReplaced', e => {
                    if (e.except !== MY_SESSION_HASH) sessionReplaced();
                });
                ch.listen('.MessengerUpdated', () => pollNow());
                ch.subscribed(() => {
                    st.rtSub = true;
                    pollNow();
                });
                ch.error(() => {
                    st.rtSub = false; // auth channel gagal -> tetap polling
                    schedulePoll();
                });
            } catch (e) {
                console.warn('Messenger realtime tidak aktif, memakai polling.', e);
            }
        }

        // ---------- Sesi dipakai device lain -> logout otomatis ----------
        let kicked = false;

        function sessionReplaced(msg) {
            if (kicked) return;
            kicked = true;
            const text = msg || 'Akun ini digunakan untuk login di device lain.';
            const go = () => window.location.reload();

            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi berakhir',
                    text: text,
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(go);
            } else {
                alert(text);
                go();
            }
        }

        $(document).ajaxError((e, xhr) => {
            if (xhr.status === 401 && xhr.responseJSON && xhr.responseJSON.code === 'SESSION_REPLACED') {
                sessionReplaced(xhr.responseJSON.message);
            }
        });

        // ---------- Init ----------
        loadContacts();
        updateSend();
        initRealtime();
        poll();
    })();
</script>
@endonce