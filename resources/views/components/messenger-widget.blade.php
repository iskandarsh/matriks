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
     Messenger Widget v2 (Blade component) — chat antar user (realtime Echo/Pusher + polling cadangan)
     Pemakaian : <x-messenger-widget />
     Kebutuhan : jQuery, meta csrf-token. (SweetAlert2 hanya dipakai untuk popup "sesi berakhir", opsional)
                 Tailwind TIDAK wajib lagi: semua gaya ada di file ini (prefix um-).
                 Realtime (opsional): pusher.min.js + echo.life.js dimuat SEBELUM komponen ini.
                 Bila tidak ada / gagal tersambung, widget otomatis memakai polling.
     Backend   : TIDAK ADA perubahan route/controller dibanding versi sebelumnya.
                 GET contacts | GET poll | GET {id} | POST {id} | POST {id}/read
                 PATCH/DELETE message/{id} | POST message/{id}/unsend
                 GET gifs | POST gif-upload | GET/POST stickers | POST stickers/save | DELETE stickers/{id}

     Yang baru di v2 :
       - Tampilan modern (gradien, bubble berkelompok, avatar, animasi halus) + mode gelap/terang
       - Filter kontak (Semua / Belum dibaca / Online) + strip "Sedang online"
       - Pemisah "Pesan belum dibaca", tombol lompat ke pesan terbaru + penghitung pesan baru
       - Draf per percakapan tersimpan otomatis, hitungan karakter, emoji terakhir dipakai
       - Link otomatis bisa diklik, emoji-saja tampil besar, salin teks pesan
       - Geser pesan ke kanan (HP) untuk membalas, tekan lama untuk menu, tombol panah atas untuk edit pesan terakhir
       - Dialog konfirmasi & toast bawaan (tidak menutupi widget), skeleton loading, tombol senyap suara
     ============================================================ --}}

@once
<style>
    /* ============ Variabel tema ============ */
    #um-widget {
        --um-bg: #ffffff;
        --um-bg2: #f4f5fa;
        --um-text: #0f172a;
        --um-sub: #64748b;
        --um-line: #e7e9f1;
        --um-acc: #6366f1;
        --um-in: #ffffff;
        --um-in-text: #1e293b;
        --um-out-a: #6366f1;
        --um-out-b: #4f46e5;
        --um-chip: #eceeff;
        --um-chip-text: #4338ca;
        --um-dot: rgba(99, 102, 241, .07);
        --um-shadow: 0 28px 70px -14px rgba(30, 27, 75, .38), 0 0 0 1px rgba(15, 23, 42, .06);
        position: fixed;
        right: var(--um-right, 5.5rem);
        bottom: 1rem;
        z-index: 55;
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    #um-widget[data-theme="dark"] {
        --um-bg: #111827;
        --um-bg2: #0b1020;
        --um-text: #f1f5f9;
        --um-sub: #94a3b8;
        --um-line: #1f2937;
        --um-in: #1f2937;
        --um-in-text: #e5e7eb;
        --um-chip: #26235c;
        --um-chip-text: #c7d2fe;
        --um-dot: rgba(148, 163, 184, .06);
        --um-shadow: 0 28px 70px -14px rgba(0, 0, 0, .7), 0 0 0 1px rgba(255, 255, 255, .06);
    }

    /* ============ Tooltip pesan lengkap (hover) ============ */
    .um-tip {
        position: absolute;
        z-index: 35;
        left: 0;
        top: 0;
        max-width: 17rem;
        max-height: 13rem;
        overflow: hidden;
        padding: .55rem .8rem;
        border-radius: .85rem;
        background: var(--um-bg);
        color: var(--um-text);
        font-size: .8rem;
        line-height: 1.45;
        box-shadow: 0 16px 36px -10px rgba(0, 0, 0, .4), 0 0 0 1px var(--um-line);
        pointer-events: none;
        animation: um-pop .12s ease-out;
    }

    .um-tip .tn {
        margin-bottom: .2rem;
        font-size: .7rem;
        font-weight: 700;
        color: var(--um-acc);
    }

    .um-tip .tt {
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    @media (min-width: 640px) {
        #um-widget {
            right: var(--um-right-sm, 6rem);
            bottom: 1.5rem;
        }
    }

    #um-widget *,
    #um-widget *::before,
    #um-widget *::after {
        box-sizing: border-box;
    }

    :where(#um-widget) button {
        background: none;
        border: 0;
        padding: 0;
        margin: 0;
        cursor: pointer;
        font: inherit;
        color: inherit;
        -webkit-tap-highlight-color: transparent;
    }

    :where(#um-widget) input,
    :where(#um-widget) textarea {
        font: inherit;
        color: inherit;
        margin: 0;
    }

    #um-widget :focus-visible {
        outline: 2px solid var(--um-acc);
        outline-offset: 2px;
    }

    #um-widget .um-hide {
        display: none !important;
    }

    #um-widget img {
        -webkit-user-drag: none;
        user-select: none;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }

    .swal2-container {
        z-index: 100000 !important;
    }

    /* ============ Tombol pembuka ============ */
    #um-toggle {
        position: relative;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 9999px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #06b6d4 0%, #4f46e5 60%, #7c3aed 100%);
        box-shadow: 0 12px 28px -6px rgba(79, 70, 229, .55);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    #um-toggle:hover {
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 16px 32px -6px rgba(79, 70, 229, .6);
    }

    #um-toggle:active {
        transform: scale(.94);
    }

    #um-toggle.um-ring::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        border: 2px solid rgba(99, 102, 241, .55);
        animation: um-ring 1.8s ease-out infinite;
        pointer-events: none;
    }

    .um-badge {
        position: absolute;
        top: -.3rem;
        right: -.3rem;
        min-width: 1.35rem;
        height: 1.35rem;
        padding: 0 .35rem;
        border-radius: 9999px;
        background: #f43f5e;
        color: #fff;
        font-size: .68rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
    }

    /* ============ Panel ============ */
    #um-panel {
        display: none;
        position: fixed;
        inset: 0;
        height: 100dvh;
        background: var(--um-bg);
        color: var(--um-text);
        overflow: hidden;
    }

    #um-panel.um-open {
        display: block;
        animation: um-pop .24s cubic-bezier(.2, .8, .2, 1);
    }

    @media (min-width: 640px) {
        #um-panel {
            position: absolute;
            inset: auto 0 4.75rem auto;
            width: 24rem;
            height: 37rem;
            max-height: calc(100dvh - 7rem);
            border-radius: 1.4rem;
            box-shadow: var(--um-shadow);
        }
    }

    .um-view {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
    }

    .um-scroll,
    .um-msgs,
    .um-pk-body {
        scrollbar-width: thin;
        scrollbar-color: var(--um-line) transparent;
        overscroll-behavior: contain;
    }

    .um-scroll {
        flex: 1;
        overflow-y: auto;
        min-height: 0;
    }

    /* ============ Header ============ */
    .um-head {
        position: relative;
        display: flex;
        align-items: center;
        gap: .35rem;
        padding: .85rem .7rem .85rem 1rem;
        color: #fff;
        background: linear-gradient(135deg, #0891b2 0%, #4f46e5 62%, #7c3aed 100%);
        overflow: hidden;
        flex-shrink: 0;
    }

    .um-head::before {
        content: "";
        position: absolute;
        width: 11rem;
        height: 11rem;
        right: -3rem;
        top: -6rem;
        border-radius: 50%;
        background: rgba(255, 255, 255, .1);
        pointer-events: none;
    }

    .um-head>* {
        position: relative;
    }

    .um-head-chat {
        padding-left: .5rem;
        gap: .55rem;
    }

    .um-head-title {
        flex: 1;
        min-width: 0;
    }

    .um-h1 {
        font-weight: 700;
        font-size: 1.02rem;
        line-height: 1.2;
        letter-spacing: -.01em;
    }

    .um-h2 {
        margin-top: .1rem;
        font-size: .74rem;
        color: rgba(255, 255, 255, .8);
        display: flex;
        align-items: center;
        gap: .35rem;
    }

    .um-h2 .um-pulse {
        width: .5rem;
        height: .5rem;
        border-radius: 50%;
        background: #34d399;
        box-shadow: 0 0 0 0 rgba(52, 211, 153, .7);
        animation: um-pulse 1.8s infinite;
    }

    .um-trunc {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .um-icon-btn {
        flex-shrink: 0;
        width: 2.2rem;
        height: 2.2rem;
        border-radius: .8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, .88);
        transition: background .15s, color .15s;
    }

    .um-icon-btn:hover {
        background: rgba(255, 255, 255, .18);
        color: #fff;
    }

    .um-icon-btn:focus-visible {
        outline-color: #fff;
    }

    /* ============ Pencarian & filter ============ */
    .um-search-wrap {
        position: relative;
        padding: .7rem .8rem .35rem;
        flex-shrink: 0;
    }

    .um-search-ic {
        position: absolute;
        left: 1.5rem;
        top: 50%;
        transform: translateY(-30%);
        color: var(--um-sub);
        pointer-events: none;
        display: flex;
    }

    .um-search {
        width: 100%;
        padding: .6rem 2.1rem .6rem 2.4rem;
        border-radius: .95rem;
        border: 1px solid transparent;
        background: var(--um-bg2);
        font-size: .88rem;
        outline: none;
        -webkit-appearance: none;
        appearance: none;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }

    .um-search::placeholder {
        color: var(--um-sub);
        opacity: .8;
    }

    .um-search::-webkit-search-cancel-button {
        display: none;
    }

    .um-search:focus {
        background: var(--um-bg);
        border-color: var(--um-acc);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .16);
    }

    .um-search-clear {
        position: absolute;
        right: 1.25rem;
        top: 50%;
        transform: translateY(-30%);
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        color: var(--um-sub);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .um-search-clear:hover {
        background: var(--um-line);
    }

    .um-chips {
        display: flex;
        gap: .4rem;
        padding: .3rem .8rem .65rem;
        border-bottom: 1px solid var(--um-line);
        flex-shrink: 0;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .um-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .75rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 600;
        white-space: nowrap;
        background: var(--um-bg2);
        color: var(--um-sub);
        transition: background .15s, color .15s;
    }

    .um-chip:hover {
        color: var(--um-text);
    }

    .um-chip.on {
        background: var(--um-chip);
        color: var(--um-chip-text);
    }

    .um-chip-n {
        min-width: 1.1rem;
        height: 1.1rem;
        padding: 0 .3rem;
        border-radius: 999px;
        background: var(--um-acc);
        color: #fff;
        font-size: .65rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* ============ Kontak ============ */
    .um-strip-title {
        padding: .7rem 1rem .25rem;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--um-sub);
    }

    .um-strip {
        display: flex;
        gap: .85rem;
        overflow-x: auto;
        padding: .35rem 1rem .8rem;
        border-bottom: 1px solid var(--um-line);
        scrollbar-width: none;
    }

    .um-strip-item {
        flex-shrink: 0;
        width: 3.4rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .3rem;
        font-size: .68rem;
        color: var(--um-sub);
    }

    .um-strip-item span.n {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .um-contact {
        display: flex;
        align-items: center;
        gap: .8rem;
        width: 100%;
        text-align: left;
        padding: .75rem 1rem;
        transition: background .15s;
    }

    .um-contact:hover {
        background: var(--um-bg2);
    }

    .um-contact-body {
        min-width: 0;
        flex: 1;
    }

    .um-row2 {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }

    .um-cname {
        font-size: .9rem;
        font-weight: 600;
        color: var(--um-text);
    }

    .um-ctime {
        flex-shrink: 0;
        font-size: .68rem;
        color: var(--um-sub);
    }

    .um-ctime.new {
        color: var(--um-acc);
        font-weight: 700;
    }

    .um-cprev {
        display: flex;
        align-items: center;
        gap: .25rem;
        margin-top: .15rem;
        min-width: 0;
        font-size: .78rem;
        color: var(--um-sub);
    }

    .um-cprev.new {
        color: var(--um-text);
        font-weight: 600;
    }

    .um-cprev .t {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .um-cprev .t.it {
        font-style: italic;
    }

    .um-cprev .draft {
        color: #e11d48;
        font-weight: 600;
        flex-shrink: 0;
    }

    .um-unread {
        flex-shrink: 0;
        min-width: 1.3rem;
        height: 1.3rem;
        padding: 0 .4rem;
        border-radius: 999px;
        background: var(--um-acc);
        color: #fff;
        font-size: .68rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ============ Avatar ============ */
    .um-av {
        position: relative;
        flex-shrink: 0;
    }

    .um-av-in {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        user-select: none;
    }

    .um-av-in img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .um-dot {
        position: absolute;
        right: -1px;
        bottom: -1px;
        width: .8rem;
        height: .8rem;
        border-radius: 50%;
        border: 2px solid var(--um-bg);
        background: #cbd5e1;
    }

    .um-dot.on {
        background: #10b981;
    }

    .um-head .um-dot {
        border-color: #4f46e5;
    }

    .um-zoomable {
        cursor: zoom-in;
    }

    /* ============ Pesan ============ */
    .um-msgs-wrap {
        position: relative;
        flex: 1;
        min-height: 0;
        display: flex;
    }

    .um-msgs {
        position: relative;
        flex: 1;
        overflow-y: auto;
        padding: 1rem .85rem 1.1rem;
        display: flex;
        flex-direction: column;
        background-color: var(--um-bg2);
        background-image: radial-gradient(var(--um-dot) 1.2px, transparent 1.2px);
        background-size: 18px 18px;
    }

    .um-older {
        align-self: center;
        margin-bottom: .5rem;
        padding: .3rem .8rem;
        border-radius: 999px;
        font-size: .74rem;
        font-weight: 600;
        color: var(--um-chip-text);
        background: var(--um-chip);
    }

    .um-day {
        align-self: center;
        margin: .7rem 0 .35rem;
    }

    .um-day span {
        display: inline-block;
        padding: .2rem .7rem;
        border-radius: 999px;
        background: var(--um-bg);
        color: var(--um-sub);
        font-size: .68rem;
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
    }

    .um-unread-div {
        display: flex;
        align-items: center;
        gap: .6rem;
        margin: .8rem 0 .5rem;
        color: var(--um-chip-text);
        font-size: .7rem;
        font-weight: 700;
    }

    .um-unread-div::before,
    .um-unread-div::after {
        content: "";
        flex: 1;
        height: 1px;
        background: currentColor;
        opacity: .25;
    }

    .um-row {
        display: flex;
        align-items: flex-start;
        gap: .2rem;
        margin-top: 2px;
        border-radius: .8rem;
    }

    .um-row.gap {
        margin-top: 11px;
    }

    .um-row.mine {
        justify-content: flex-end;
    }

    .um-wrap {
        display: flex;
        flex-direction: column;
        min-width: 0;
        max-width: 82%;
    }

    .um-row.mine .um-wrap {
        align-items: flex-end;
    }

    .um-row:not(.mine) .um-wrap {
        align-items: flex-start;
    }

    .um-bubble {
        padding: .5rem .8rem;
        border-radius: 1.15rem;
        font-size: .9rem;
        line-height: 1.45;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
        max-width: 100%;
    }

    .um-bubble.in {
        background: var(--um-in);
        color: var(--um-in-text);
        border: 1px solid var(--um-line);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .um-bubble.out {
        color: #fff;
        background: linear-gradient(135deg, var(--um-out-a), var(--um-out-b));
        box-shadow: 0 4px 12px -4px rgba(79, 70, 229, .45);
    }

    .um-bubble.in.tail {
        border-bottom-left-radius: .3rem;
    }

    .um-bubble.out.tail {
        border-bottom-right-radius: .3rem;
    }

    .um-bubble.unsent {
        font-style: italic;
        opacity: .65;
    }

    .um-bubble.editing {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .4);
    }

    .um-bubble.emo {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        color: inherit;
        font-size: 2.3rem;
        line-height: 1.15;
        padding: .05rem .15rem;
    }

    .um-bubble a {
        color: inherit;
        text-decoration: underline;
        text-underline-offset: 2px;
        word-break: break-all;
    }

    .um-bubble.in a {
        color: var(--um-acc);
    }

    .um-quote {
        display: block;
        width: 100%;
        text-align: left;
        margin-bottom: .35rem;
        padding: .28rem .55rem;
        border-left: 3px solid;
        border-radius: .55rem;
        font-size: .75rem;
        white-space: normal;
        overflow: hidden;
    }

    .um-quote .qn {
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .um-quote .qt {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        opacity: .9;
    }

    .um-quote.q-out {
        background: rgba(255, 255, 255, .16);
        border-color: rgba(255, 255, 255, .8);
        color: #eef2ff;
    }

    .um-quote.q-out .qn {
        color: #fff;
    }

    .um-quote.q-in {
        background: var(--um-bg2);
        border-color: var(--um-acc);
        color: var(--um-sub);
    }

    .um-quote.q-in .qn {
        color: var(--um-acc);
    }

    .um-quote.q-solo {
        background: var(--um-in);
        border-color: var(--um-acc);
        color: var(--um-sub);
        margin-bottom: .3rem;
    }

    .um-quote.q-solo .qn {
        color: var(--um-acc);
    }

    .um-media-gif-box {
        overflow: hidden;
        border-radius: 1.1rem;
    }

    .um-media-gif-box.in {
        border: 1px solid var(--um-line);
    }

    .um-media-gif {
        display: block;
        max-width: 13rem;
        max-height: 14rem;
        cursor: zoom-in;
    }

    .um-media-sticker {
        display: block;
        width: 8rem;
        height: 8rem;
        object-fit: contain;
        cursor: zoom-in;
    }

    .um-media-err {
        display: block;
        padding: .5rem .8rem;
        font-size: .75rem;
        color: var(--um-sub);
    }

    .um-meta {
        display: flex;
        align-items: center;
        gap: .3rem;
        margin-top: .15rem;
        padding: 0 .25rem;
        font-size: .66rem;
        color: var(--um-sub);
    }

    .um-tick {
        display: block;
    }

    .um-tick.read {
        color: #0ea5e9;
    }

    .um-actions {
        display: flex;
        align-items: center;
        margin-top: .2rem;
        opacity: 0;
        transition: opacity .15s;
    }

    .um-row:hover .um-actions,
    .um-actions:focus-within {
        opacity: 1;
    }

    .um-act {
        width: 1.6rem;
        height: 1.6rem;
        border-radius: 999px;
        color: var(--um-sub);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .um-act:hover {
        background: var(--um-line);
        color: var(--um-text);
    }

    @media (hover: none) {
        .um-actions {
            opacity: .55;
        }

        .um-act-reply {
            display: none;
        }
    }

    .um-flash {
        animation: um-flash 1.2s ease;
    }

    .um-empty {
        margin: auto;
        text-align: center;
        padding: 2rem 1rem;
    }

    .um-empty-ic {
        font-size: 2.6rem;
        margin-bottom: .4rem;
    }

    .um-empty-t {
        font-weight: 700;
        font-size: .95rem;
        color: var(--um-text);
    }

    .um-empty-s {
        margin-top: .2rem;
        font-size: .8rem;
        color: var(--um-sub);
    }

    .um-empty-btn {
        margin-top: .8rem;
        padding: .4rem .9rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
        background: var(--um-chip);
        color: var(--um-chip-text);
    }

    .um-skels {
        display: flex;
        flex-direction: column;
        gap: .6rem;
    }

    .um-skel {
        height: 2.4rem;
        border-radius: 1rem;
        background: linear-gradient(90deg, var(--um-line), var(--um-bg), var(--um-line));
        background-size: 200% 100%;
        animation: um-shimmer 1.3s linear infinite;
    }

    .um-skel.l {
        align-self: flex-start;
    }

    .um-skel.r {
        align-self: flex-end;
    }

    .um-skel-row {
        display: flex;
        align-items: center;
        gap: .8rem;
        padding: .75rem 1rem;
    }

    .um-skel-row .c {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .um-skel-row .ls {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: .45rem;
    }

    .um-skel-row .ls i {
        display: block;
        height: .7rem;
        border-radius: .4rem;
    }

    .um-fab {
        position: absolute;
        right: .9rem;
        bottom: .8rem;
        width: 2.4rem;
        height: 2.4rem;
        border-radius: 50%;
        background: var(--um-bg);
        color: var(--um-text);
        box-shadow: 0 6px 18px -4px rgba(0, 0, 0, .3), 0 0 0 1px var(--um-line);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform .15s;
    }

    .um-fab:hover {
        transform: translateY(-1px);
    }

    .um-fab-n {
        position: absolute;
        top: -.45rem;
        right: -.2rem;
        min-width: 1.15rem;
        height: 1.15rem;
        padding: 0 .3rem;
        border-radius: 999px;
        background: var(--um-acc);
        color: #fff;
        font-size: .64rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ============ Bar edit / balas ============ */
    .um-bar {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .5rem .9rem;
        font-size: .76rem;
        background: var(--um-bg);
        border-top: 1px solid var(--um-line);
        flex-shrink: 0;
    }

    .um-bar .accent {
        width: 3px;
        align-self: stretch;
        border-radius: 3px;
        background: var(--um-acc);
    }

    .um-bar .bt {
        flex: 1;
        min-width: 0;
    }

    .um-bar .bn {
        font-weight: 700;
        color: var(--um-acc);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .um-bar .bs {
        color: var(--um-sub);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .um-bar .bx {
        flex-shrink: 0;
        width: 1.7rem;
        height: 1.7rem;
        border-radius: 50%;
        color: var(--um-sub);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .um-bar .bx:hover {
        background: var(--um-line);
    }

    /* ============ Composer ============ */
    .um-composer {
        position: relative;
        flex-shrink: 0;
        padding: .6rem .75rem calc(.6rem + env(safe-area-inset-bottom));
        background: var(--um-bg);
        border-top: 1px solid var(--um-line);
    }

    .um-box {
        display: flex;
        align-items: flex-end;
        gap: .2rem;
        padding: .3rem;
        border-radius: 1.5rem;
        background: var(--um-bg2);
        border: 1px solid transparent;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }

    .um-box:focus-within {
        background: var(--um-bg);
        border-color: var(--um-acc);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .16);
    }

    .um-box .um-tool {
        flex-shrink: 0;
        width: 2.3rem;
        height: 2.3rem;
        border-radius: 50%;
        color: var(--um-sub);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .15s, color .15s;
    }

    .um-box .um-tool:hover,
    .um-box .um-tool.on {
        color: var(--um-acc);
        background: var(--um-chip);
    }

    .um-input {
        flex: 1;
        min-width: 0;
        resize: none;
        border: 0;
        outline: 0;
        background: transparent;
        padding: .55rem .35rem;
        font-size: .9rem;
        line-height: 1.35;
        max-height: 120px;
        color: var(--um-text);
    }

    .um-input::placeholder {
        color: var(--um-sub);
        opacity: .8;
    }

    @media (max-width: 639px) {
        .um-input {
            font-size: 16px;
        }
    }

    .um-send {
        flex-shrink: 0;
        width: 2.3rem;
        height: 2.3rem;
        border-radius: 50%;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        box-shadow: 0 6px 14px -4px rgba(79, 70, 229, .6);
        transition: transform .15s, opacity .15s, box-shadow .15s;
    }

    .um-send:hover:not(:disabled) {
        transform: scale(1.06);
    }

    .um-send:active:not(:disabled) {
        transform: scale(.92);
    }

    .um-send:disabled {
        opacity: .4;
        cursor: not-allowed;
        box-shadow: none;
    }

    .um-count {
        position: absolute;
        right: 1.1rem;
        top: -.2rem;
        transform: translateY(-100%);
        padding: .1rem .45rem;
        border-radius: 999px;
        font-size: .66rem;
        font-weight: 600;
        background: var(--um-bg);
        color: var(--um-sub);
        box-shadow: 0 0 0 1px var(--um-line);
    }

    .um-count.warn {
        color: #e11d48;
    }

    /* ============ Picker ============ */
    .um-picker {
        display: flex;
        flex-direction: column;
        height: 15.5rem;
        flex-shrink: 0;
        border-top: 1px solid var(--um-line);
        background: var(--um-bg);
    }

    .um-tabs {
        display: flex;
        gap: .25rem;
        padding: .45rem .55rem 0;
    }

    .um-tab {
        flex: 1;
        padding: .38rem;
        border-radius: .75rem;
        font-size: .78rem;
        font-weight: 600;
        color: var(--um-sub);
        transition: background .15s, color .15s;
    }

    .um-tab:hover {
        color: var(--um-text);
    }

    .um-tab.on {
        background: var(--um-chip);
        color: var(--um-chip-text);
    }

    .um-pk-sub {
        padding: .45rem .55rem 0;
    }

    .um-pk-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: .5rem .55rem .7rem;
    }

    .um-cats {
        display: flex;
        gap: .15rem;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .um-cat {
        padding: .2rem .45rem;
        border-radius: .6rem;
        font-size: 1.1rem;
        line-height: 1.3;
    }

    .um-cat:hover,
    .um-cat.on {
        background: var(--um-bg2);
    }

    .um-egrid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: .15rem;
    }

    .um-egrid button {
        font-size: 1.45rem;
        line-height: 1;
        padding: .25rem;
        border-radius: .6rem;
        transition: transform .1s, background .15s;
    }

    .um-egrid button:hover {
        background: var(--um-bg2);
    }

    .um-egrid button:active {
        transform: scale(.85);
    }

    .um-label {
        margin: 0 0 .35rem;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--um-sub);
    }

    .um-sgrid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .5rem;
    }

    .um-sgrid .st {
        position: relative;
        aspect-ratio: 1;
    }

    .um-sgrid .st>button.pick {
        width: 100%;
        height: 100%;
        padding: .25rem;
        border-radius: .8rem;
        transition: background .15s, transform .1s;
    }

    .um-sgrid .st>button.pick:hover {
        background: var(--um-bg2);
    }

    .um-sgrid .st>button.pick:active {
        transform: scale(.93);
    }

    .um-sgrid img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .um-sdel {
        position: absolute;
        top: -.2rem;
        right: -.2rem;
        width: 1.2rem;
        height: 1.2rem;
        border-radius: 50%;
        background: rgba(15, 23, 42, .78);
        color: #fff;
        font-size: .8rem;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .um-sdel:hover {
        background: #e11d48;
    }

    .um-sadd {
        aspect-ratio: 1;
        width: 100%;
        border-radius: .8rem;
        border: 2px dashed var(--um-line);
        color: var(--um-sub);
        font-size: .68rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .1rem;
        transition: color .15s, border-color .15s;
    }

    .um-sadd:hover {
        color: var(--um-acc);
        border-color: var(--um-acc);
    }

    .um-sadd b {
        font-size: 1.4rem;
        font-weight: 400;
        line-height: 1;
    }

    .um-grow {
        display: flex;
        gap: .35rem;
    }

    .um-grow input {
        flex: 1;
        min-width: 0;
        padding: .45rem .75rem;
        border-radius: .8rem;
        border: 1px solid transparent;
        background: var(--um-bg2);
        font-size: .85rem;
        outline: none;
    }

    .um-grow input:focus {
        border-color: var(--um-acc);
        background: var(--um-bg);
    }

    .um-grow button {
        flex-shrink: 0;
        padding: 0 .8rem;
        border-radius: .8rem;
        font-size: .76rem;
        font-weight: 600;
        background: var(--um-chip);
        color: var(--um-chip-text);
    }

    .um-gcols {
        columns: 2;
        column-gap: .5rem;
    }

    .um-gcols button {
        display: block;
        width: 100%;
        margin-bottom: .5rem;
        overflow: hidden;
        border-radius: .8rem;
        background: var(--um-bg2);
        transition: opacity .15s;
    }

    .um-gcols button:hover {
        opacity: .8;
    }

    .um-gcols img {
        display: block;
        width: 100%;
    }

    .um-note {
        padding: 2rem 0;
        text-align: center;
        font-size: .82rem;
        color: var(--um-sub);
    }

    .um-note.err {
        color: #e11d48;
    }

    .um-powered {
        padding-top: .2rem;
        text-align: center;
        font-size: .62rem;
        color: var(--um-sub);
    }

    /* ============ Menu aksi ============ */
    .um-menu {
        position: absolute;
        left: 0;
        top: 0;
        z-index: 20;
        width: 13.5rem;
        padding: .3rem;
        border-radius: 1rem;
        background: var(--um-bg);
        box-shadow: 0 16px 36px -10px rgba(0, 0, 0, .4), 0 0 0 1px var(--um-line);
        animation: um-pop .14s ease-out;
    }

    .um-menu button {
        display: flex;
        align-items: center;
        gap: .65rem;
        width: 100%;
        padding: .5rem .65rem;
        border-radius: .65rem;
        font-size: .84rem;
        text-align: left;
        color: var(--um-text);
    }

    .um-menu button:hover {
        background: var(--um-bg2);
    }

    .um-menu button.danger {
        color: #e11d48;
    }

    /* ============ Dialog & toast ============ */
    .um-dialog {
        position: absolute;
        inset: 0;
        z-index: 40;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        background: rgba(15, 23, 42, .5);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
    }

    .um-dialog-card {
        width: 100%;
        max-width: 19rem;
        padding: 1.2rem;
        border-radius: 1.2rem;
        background: var(--um-bg);
        color: var(--um-text);
        box-shadow: 0 24px 60px -12px rgba(0, 0, 0, .5);
        animation: um-pop .18s ease-out;
    }

    .um-dialog-title {
        font-size: 1rem;
        font-weight: 700;
    }

    .um-dialog-text {
        margin-top: .4rem;
        font-size: .85rem;
        line-height: 1.45;
        color: var(--um-sub);
    }

    .um-dialog-preview {
        margin-top: .7rem;
        padding: .5rem .75rem;
        border-radius: .7rem;
        background: var(--um-bg2);
        font-size: .82rem;
        word-break: break-word;
    }

    .um-dialog-actions {
        display: flex;
        gap: .5rem;
        margin-top: 1.1rem;
    }

    .um-dialog-actions button {
        flex: 1;
        padding: .6rem;
        border-radius: .8rem;
        font-size: .85rem;
        font-weight: 700;
    }

    .um-btn-ghost {
        background: var(--um-bg2);
        color: var(--um-text);
    }

    .um-btn-danger {
        background: #e11d48;
        color: #fff;
    }

    .um-toast {
        position: absolute;
        top: .8rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 50;
        max-width: calc(100% - 2rem);
        padding: .5rem .95rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
        color: #fff;
        background: #0f172a;
        box-shadow: 0 10px 24px -8px rgba(0, 0, 0, .5);
        animation: um-drop .22s ease-out;
        text-align: center;
    }

    .um-toast[data-kind="err"] {
        background: #e11d48;
    }

    .um-toast[data-kind="ok"]::before {
        content: "✓ ";
        color: #34d399;
    }

    /* ============ Zoom ============ */
    #um-zoom {
        position: absolute;
        inset: 0;
        z-index: 30;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: .75rem;
        background: rgba(15, 23, 42, .94);
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

    #um-zoom-src {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    #um-zoom-close {
        position: absolute;
        top: .75rem;
        right: .75rem;
        width: 2.3rem;
        height: 2.3rem;
        border-radius: 50%;
        background: rgba(255, 255, 255, .15);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #um-zoom-close:hover {
        background: rgba(255, 255, 255, .28);
    }

    #um-zoom-name {
        font-size: .88rem;
        font-weight: 600;
        color: #fff;
    }

    /* ============ Lampiran foto ============ */
    .um-media-photo {
        display: block;
        max-width: 15rem;
        max-height: 16rem;
        width: auto;
        height: auto;
        cursor: zoom-in;
    }

    .um-attach-menu {
        left: .75rem;
        top: auto;
        bottom: calc(4.4rem + env(safe-area-inset-bottom));
        z-index: 25;
    }

    .um-overlay {
        position: absolute;
        inset: 0;
        z-index: 35;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(15, 23, 42, .96);
        color: #fff;
    }

    .um-overlay img,
    .um-overlay video {
        max-width: 100%;
        max-height: 68%;
        border-radius: 1rem;
        background: #000;
        object-fit: contain;
    }

    #um-cam-video {
        transform: scaleX(-1);
        /* pratinjau seperti cermin, hasil foto tidak terbalik */
    }

    /* ============ Kartu dokumen ============ */
    .um-bubble a.um-file,
    .um-bubble.in a.um-file {
        display: flex;
        align-items: center;
        gap: .65rem;
        min-width: 11rem;
        max-width: 15rem;
        color: inherit;
        text-decoration: none;
        word-break: normal;
    }

    .um-file-ic {
        flex-shrink: 0;
        width: 2.4rem;
        height: 2.4rem;
        border-radius: .7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(127, 127, 127, .2);
    }

    .um-file-t {
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .um-file-n {
        font-weight: 600;
        font-size: .84rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .um-file-s {
        font-size: .7rem;
        opacity: .75;
    }

    .um-overlay-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .6rem;
        width: 100%;
        max-width: 19rem;
    }

    .um-overlay-actions button {
        flex: 1;
        padding: .65rem;
        border-radius: .9rem;
        font-size: .88rem;
        font-weight: 700;
    }

    .um-btn-light {
        background: rgba(255, 255, 255, .15);
        color: #fff;
    }

    .um-btn-primary {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
    }

    .um-btn-primary:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .um-overlay-actions button.um-shot {
        flex: none;
        width: 3.7rem;
        height: 3.7rem;
        padding: 0;
        border-radius: 50%;
        border: 4px solid #fff;
        background: rgba(255, 255, 255, .25);
    }

    /* ============ Animasi ============ */
    @keyframes um-pop {
        from {
            opacity: 0;
            transform: translateY(14px) scale(.97);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    @keyframes um-drop {
        from {
            opacity: 0;
            transform: translate(-50%, -8px);
        }

        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }

    @keyframes um-ring {
        0% {
            transform: scale(1);
            opacity: .8;
        }

        100% {
            transform: scale(1.5);
            opacity: 0;
        }
    }

    @keyframes um-pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(52, 211, 153, .6);
        }

        70% {
            box-shadow: 0 0 0 6px rgba(52, 211, 153, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(52, 211, 153, 0);
        }
    }

    @keyframes um-shimmer {
        from {
            background-position: 200% 0;
        }

        to {
            background-position: -200% 0;
        }
    }

    @keyframes um-flash {

        0%,
        100% {
            background: transparent;
        }

        30% {
            background: rgba(99, 102, 241, .22);
        }
    }

    @media (prefers-reduced-motion: reduce) {

        #um-panel.um-open,
        .um-menu,
        .um-toast,
        .um-dialog-card,
        #um-toggle.um-ring::after,
        .um-skel,
        .um-h2 .um-pulse {
            animation: none !important;
        }
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
    data-theme="light"
    style="--um-right: {{ $right }}; --um-right-sm: {{ $rightSm }};">

    {{-- ================= PANEL ================= --}}
    <div id="um-panel" role="dialog" aria-label="Pesan">

        {{-- ===== VIEW 1: DAFTAR KONTAK ===== --}}
        <section id="um-list-view" class="um-view">
            <header class="um-head">
                <div class="um-head-title">
                    <div class="um-h1">Pesan</div>
                    <div class="um-h2"><span id="um-online-count">Memuat…</span></div>
                </div>
                <button id="um-sound-btn" type="button" class="um-icon-btn" aria-label="Suara notifikasi"></button>
                <button id="um-theme-btn" type="button" class="um-icon-btn" aria-label="Ganti tema"></button>
                <button type="button" class="um-close um-icon-btn" aria-label="Tutup" data-ic="close"></button>
            </header>

            <div class="um-search-wrap">
                <span class="um-search-ic" data-ic="search" data-s="16"></span>
                <input id="um-search" class="um-search" type="search" placeholder="Cari nama…" autocomplete="off" aria-label="Cari kontak">
                <button id="um-search-clear" type="button" class="um-search-clear um-hide" aria-label="Hapus pencarian" data-ic="close" data-s="14"></button>
            </div>

            <div class="um-chips" role="tablist" aria-label="Filter kontak">
                <button type="button" class="um-chip on" data-f="all" role="tab">Semua</button>
                <button type="button" class="um-chip" data-f="unread" role="tab">Belum dibaca <span id="um-chip-unread-n" class="um-chip-n um-hide"></span></button>
                <button type="button" class="um-chip" data-f="online" role="tab">Online <span id="um-chip-online-n" class="um-chip-n um-hide"></span></button>
            </div>

            <div id="um-contacts" class="um-scroll"></div>
        </section>

        {{-- ===== VIEW 2: PERCAKAPAN ===== --}}
        <section id="um-chat-view" class="um-view um-hide">
            <header class="um-head um-head-chat">
                <button id="um-back" type="button" aria-label="Kembali" class="um-icon-btn" data-ic="back" data-s="20"></button>
                <div id="um-chat-avatar" style="flex-shrink:0"></div>
                <div class="um-head-title">
                    <div id="um-chat-name" class="um-h1 um-trunc"></div>
                    <div id="um-chat-status" class="um-h2"></div>
                </div>
                <button type="button" class="um-close um-icon-btn" aria-label="Tutup" data-ic="close"></button>
            </header>

            <div class="um-msgs-wrap">
                <div id="um-messages" class="um-msgs" role="log" aria-live="polite"></div>
                <button id="um-fab" type="button" class="um-fab um-hide" aria-label="Ke pesan terbaru" data-ic="down" data-s="18">
                    <span id="um-fab-n" class="um-fab-n um-hide"></span>
                </button>
            </div>

            {{-- bar mode edit --}}
            <div id="um-edit-bar" class="um-bar um-hide">
                <span style="color:var(--um-acc);display:flex" data-ic="edit" data-s="15"></span>
                <span class="bt" style="font-weight:700;color:var(--um-acc)">Mengedit pesan</span>
                <button id="um-edit-cancel" type="button" class="bx" aria-label="Batal edit" data-ic="close" data-s="14"></button>
            </div>
            {{-- bar balas pesan --}}
            <div id="um-reply-bar" class="um-bar um-hide">
                <div class="accent"></div>
                <div class="bt">
                    <div id="um-reply-name" class="bn"></div>
                    <div id="um-reply-text" class="bs"></div>
                </div>
                <button id="um-reply-cancel" type="button" class="bx" aria-label="Batal balas" data-ic="close" data-s="14"></button>
            </div>

            {{-- picker emoji / GIF / stiker --}}
            <div id="um-picker" class="um-picker um-hide">
                <div class="um-tabs">
                    <button type="button" data-tab="emoji" class="um-tab">😀 Emoji</button>
                    <button type="button" data-tab="gif" class="um-tab">GIF</button>
                    <button type="button" data-tab="sticker" class="um-tab">Stiker</button>
                </div>
                <div id="um-pk-sub" class="um-pk-sub"></div>
                <div id="um-pk-body" class="um-pk-body"></div>
                <input type="file" id="um-file-sticker" class="um-hide" accept="image/png,image/jpeg,image/webp,image/gif">
                <input type="file" id="um-file-gif" class="um-hide" accept="image/gif">
            </div>

            <div class="um-composer">
                <span id="um-count" class="um-count um-hide"></span>
                <div class="um-box">
                    <button id="um-emoji-btn" type="button" class="um-tool" aria-label="Emoji, GIF, stiker" data-ic="smile" data-s="21"></button>
                    <button id="um-attach-btn" type="button" class="um-tool" aria-label="Lampirkan foto" aria-haspopup="menu" data-ic="clip" data-s="20"></button>
                    <textarea id="um-input" class="um-input" rows="1" maxlength="2000" placeholder="Ketik pesan…" autocomplete="off" aria-label="Pesan"></textarea>
                    <button id="um-send" type="button" class="um-send" aria-label="Kirim" data-ic="send" data-s="18"></button>
                </div>
            </div>
        </section>

        {{-- menu aksi pesan --}}
        <div id="um-menu" role="menu" class="um-menu um-hide"></div>

        {{-- dialog konfirmasi --}}
        <div id="um-dialog" class="um-dialog um-hide" role="alertdialog" aria-labelledby="um-dialog-title">
            <div class="um-dialog-card">
                <div id="um-dialog-title" class="um-dialog-title"></div>
                <div id="um-dialog-text" class="um-dialog-text"></div>
                <div id="um-dialog-preview" class="um-dialog-preview um-hide"></div>
                <div class="um-dialog-actions">
                    <button id="um-dialog-cancel" type="button" class="um-btn-ghost">Batal</button>
                    <button id="um-dialog-ok" type="button" class="um-btn-danger">Ya</button>
                </div>
            </div>
        </div>

        {{-- toast --}}
        <div id="um-toast" class="um-toast um-hide" role="status" aria-live="polite"></div>
        {{-- tooltip pesan lengkap saat hover kontak --}}
        <div id="um-tip" class="um-tip um-hide" role="tooltip"></div>
        {{-- modal zoom (foto profil / stiker / GIF) — hanya lihat --}}
        <div id="um-zoom" role="dialog" aria-label="Pratinjau gambar" oncontextmenu="return false">
            <button id="um-zoom-close" type="button" aria-label="Tutup" data-ic="close"></button>
            <canvas id="um-zoom-canvas"></canvas>
            <div id="um-zoom-name"></div>
        </div>
        {{-- menu lampiran --}}
        <div id="um-attach-menu" role="menu" class="um-menu um-attach-menu um-hide">
            <button type="button" data-a="gallery" role="menuitem"><span data-ic="image" data-s="16"></span>Foto dari galeri</button>
            <button type="button" data-a="camera" role="menuitem"><span data-ic="camera" data-s="16"></span>Ambil foto</button>
            <button type="button" data-a="doc" role="menuitem"><span data-ic="file" data-s="16"></span>Dokumen (maks 2 MB)</button>
        </div>
        <input type="file" id="um-file-photo" class="um-hide" accept="image/jpeg,image/png,image/webp">
        <input type="file" id="um-file-camera" class="um-hide" accept="image/*" capture="environment">
        <input type="file" id="um-file-doc" class="um-hide" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp,.txt,.csv,.rtf,.zip">
        {{-- pratinjau foto sebelum dikirim --}}
        <div id="um-photo-prev" class="um-overlay um-hide" role="dialog" aria-label="Pratinjau foto">
            <img id="um-photo-img" alt="">
            <div class="um-overlay-actions">
                <button id="um-photo-cancel" type="button" class="um-btn-light">Batal</button>
                <button id="um-photo-send" type="button" class="um-btn-primary">Kirim</button>
            </div>
        </div>

        {{-- kamera (desktop) --}}
        <div id="um-camera" class="um-overlay um-hide" role="dialog" aria-label="Kamera">
            <video id="um-cam-video" autoplay playsinline muted></video>
            <div class="um-overlay-actions">
                <button id="um-cam-cancel" type="button" class="um-btn-light">Batal</button>
                <button id="um-cam-shot" type="button" class="um-shot" aria-label="Ambil foto"></button>
                <span style="flex:1"></span>
            </div>
        </div>
    </div>

    {{-- ================= TOMBOL ================= --}}
    <div style="display:flex;justify-content:flex-end">
        <button id="um-toggle" type="button" title="Pesan" aria-label="Buka pesan" aria-expanded="false">
            <span id="um-icon-open" style="display:flex" data-ic="chat" data-s="26"></span>
            <span id="um-icon-close" class="um-hide" style="display:flex" data-ic="down" data-s="26"></span>
            <span id="um-badge" class="um-badge um-hide"></span>
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
        const GROUP_MS = 5 * 60 * 1000; // pesan beruntun dari orang yang sama dalam 5 menit digabung

        // ---------- Penyimpanan preferensi ----------
        const store = {
            get(k, d) {
                try {
                    const v = localStorage.getItem(k);
                    return v === null ? d : v;
                } catch (e) {
                    return d;
                }
            },
            set(k, v) {
                try {
                    localStorage.setItem(k, v);
                } catch (e) {}
            }
        };

        // ---------- Ikon (SVG) ----------
        const svg = (d, s) => '<svg width="' + (s || 18) + '" height="' + (s || 18) + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + d + '</svg>';
        const IC = {
            close: '<path d="M6 6l12 12M18 6L6 18"/>',
            back: '<path d="M15 18l-6-6 6-6"/>',
            down: '<path d="M6 9l6 6 6-6"/>',
            send: '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/>',
            smile: '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01M15 9h.01"/>',
            reply: '<path d="M9 14L4 9l5-5"/><path d="M20 20v-7a4 4 0 00-4-4H4"/>',
            more: '<circle cx="12" cy="5" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/>',
            copy: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>',
            edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>',
            trash: '<path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>',
            undo: '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 00-15-6.7L3 13"/>',
            star: '<path d="M12 2l3 7 7 .6-5.3 4.6 1.7 7.3L12 17.8 5.6 21.5l1.7-7.3L2 9.6 9 9z"/>',
            sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
            moon: '<path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/>',
            bell: '<path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/>',
            bellOff: '<path d="M13.7 21a2 2 0 01-3.4 0"/><path d="M18.6 13A17 17 0 0118 8a6 6 0 00-9.3-5"/><path d="M6.3 6.3A6 6 0 006 8c0 7-3 9-3 9h14"/><path d="M2 2l20 20"/>',
            search: '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
            chat: '<path d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-1m10-5V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l4-4h4a2 2 0 002-2z"/>',
            clip: '<path d="M21.4 11.1l-9.2 9.2a6 6 0 01-8.5-8.5l9.2-9.2a4 4 0 015.7 5.7l-9.2 9.2a2 2 0 01-2.8-2.8l8.5-8.5"/>',
            image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
            camera: '<path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/>',
            file: '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
        };
        const tick = read => '<svg class="um-tick' + (read ? ' read' : '') + '" width="17" height="12" viewBox="0 0 26 16" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8.5l5 5L17 3"/>' + (read ? '<path d="M12 12.5l1.5 1.5L24 3"/>' : '') + '</svg>';

        function hydrateIcons() {
            $w.find('[data-ic]:not([data-ok])').each(function() {
                const $e = $(this);
                $e.prepend(svg(IC[$e.attr('data-ic')] || '', parseInt($e.attr('data-s'), 10) || 18)).attr('data-ok', '1');
            });
        }

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
            $fab = $('#um-fab');
        const $menu = $('#um-menu'),
            $editBar = $('#um-edit-bar'),
            $replyBar = $('#um-reply-bar');
        const $picker = $('#um-picker'),
            $pkBody = $('#um-pk-body'),
            $pkSub = $('#um-pk-sub');
        const $dlg = $('#um-dialog');
        const isMobile = () => window.matchMedia('(max-width: 639px)').matches;

        // ---------- State ----------
        const st = {
            contacts: new Map(), // id -> {id,name,avatar,unread,last}
            online: new Set(),
            active: null,
            msgs: [],
            hasMore: false,
            busy: false,
            open: false,
            sig: null,
            since: null,
            editing: null,
            rtConn: false,
            rtSub: false,
            replying: null,
            filter: 'all',
            divider: null, // id pesan pertama yang belum dibaca saat percakapan dibuka
            newBelow: 0 // jumlah pesan baru saat pengguna sedang menggulir ke atas
        };

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': CSRF
            }
        });

        // ---------- Tema & suara ----------
        let theme = store.get('um-theme', (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light');
        let muted = store.get('um-mute', '0') === '1';

        function applyTheme() {
            $w.attr('data-theme', theme);
            $('#um-theme-btn').html(svg(theme === 'dark' ? IC.sun : IC.moon, 18)).attr('title', theme === 'dark' ? 'Mode terang' : 'Mode gelap');
        }

        function applySound() {
            $('#um-sound-btn').html(svg(muted ? IC.bellOff : IC.bell, 18)).attr('title', muted ? 'Aktifkan suara' : 'Senyapkan suara');
        }
        $('#um-theme-btn').on('click', () => {
            theme = theme === 'dark' ? 'light' : 'dark';
            store.set('um-theme', theme);
            applyTheme();
        });
        $('#um-sound-btn').on('click', () => {
            muted = !muted;
            store.set('um-mute', muted ? '1' : '0');
            applySound();
            toast(muted ? 'Suara notifikasi dimatikan' : 'Suara notifikasi aktif');
        });

        // ---------- Draf per percakapan ----------
        const drafts = (() => {
            try {
                return JSON.parse(sessionStorage.getItem('um-drafts') || '{}') || {};
            } catch (e) {
                return {};
            }
        })();

        function saveDraft() {
            if (!st.active || st.editing) return;
            const v = $input.val();
            if (v.trim()) drafts[st.active] = v;
            else delete drafts[st.active];
            try {
                sessionStorage.setItem('um-drafts', JSON.stringify(drafts));
            } catch (e) {}
        }

        // ---------- Cache foto yang 404 ----------
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

        const labelOf = m => m.unsent ? 'Pesan ditarik' :
            m.type === 'sticker' ? '🖼️ Stiker' :
            m.type === 'gif' ? '🎞️ GIF' :
            m.type === 'image' ? '📷 Foto' :
            m.type === 'file' ? '📎 ' + fileInfo(m).name : m.body;

        function fileInfo(m) {
            try {
                const d = JSON.parse(m.body);
                return {
                    url: /^https?:\/\//i.test(d.url || '') ? d.url : '',
                    name: String(d.name || 'Dokumen'),
                    size: parseInt(d.size, 10) || 0
                };
            } catch (e) {
                return {
                    url: '',
                    name: 'Dokumen',
                    size: 0
                };
            }
        }
        const fmtSize = b => b < 1024 ? b + ' B' : (b < 1048576 ? Math.round(b / 1024) + ' KB' : (b / 1048576).toFixed(1) + ' MB');

        function fileCard(m) {
            const f = fileInfo(m);
            const ext = (f.name.split('.').pop() || '').slice(0, 5).toUpperCase();
            return $('<a class="um-file" target="_blank" rel="noopener noreferrer">')
                .attr({
                    href: f.url || '#',
                    download: f.name
                })
                .on('click', e => e.stopPropagation())
                .append(
                    $('<span class="um-file-ic">').html(svg(IC.file, 22)),
                    $('<span class="um-file-t">').append(
                        $('<span class="um-file-n">').text(f.name),
                        $('<span class="um-file-s">').text(ext + (f.size ? ' · ' + fmtSize(f.size) : ''))
                    )
                );
        }

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

        function updateCount() {
            const n = $input.val().length;
            $('#um-count').toggleClass('um-hide', n < 1800).toggleClass('warn', n >= 1980).text(n + '/2000');
        }

        function updateSend() {
            $send.prop('disabled', st.busy || !$input.val().trim());
            updateCount();
        }

        function replaceMsg(m) {
            const i = st.msgs.findIndex(x => x.id === m.id);
            if (i >= 0) st.msgs[i] = m;
        }

        // link otomatis bisa diklik (aman: hanya http/https, dibuat lewat DOM)
        const URL_RE = /(https?:\/\/[^\s<>"]+[^\s<>"'.,;:!?)\]}])/g;

        function fillText($el, text) {
            let last = 0,
                m;
            URL_RE.lastIndex = 0;
            while ((m = URL_RE.exec(text))) {
                if (m.index > last) $el.append(document.createTextNode(text.slice(last, m.index)));
                $('<a target="_blank" rel="noopener noreferrer nofollow">').attr('href', m[0]).text(m[0])
                    .on('click', e => e.stopPropagation()).appendTo($el);
                last = m.index + m[0].length;
            }
            if (last < text.length) $el.append(document.createTextNode(text.slice(last)));
        }

        // pesan yang isinya hanya 1-3 emoji tampil besar tanpa gelembung
        let EMO_RE = null;
        try {
            EMO_RE = new RegExp('\\p{Extended_Pictographic}', 'u');
        } catch (e) {}

        function isEmojiOnly(t) {
            if (!EMO_RE || !t) return false;
            const chars = [...t.replace(/[\s\uFE0F\u200D]/g, '')];
            if (!chars.length) return false;
            const pict = chars.filter(c => EMO_RE.test(c));
            return pict.length > 0 && pict.length <= 3 && pict.length === chars.length;
        }

        // ---------- Toast & dialog ----------
        let toastT = null;

        function toast(msg, kind) {
            const $t = $('#um-toast');
            $t.text(msg).attr('data-kind', kind || 'ok').removeClass('um-hide');
            clearTimeout(toastT);
            toastT = setTimeout(() => $t.addClass('um-hide'), kind === 'err' ? 4000 : 1800);
        }
        const flashError = msg => toast(msg, 'err');

        let dlgResolve = null;

        function confirmDialog(o) {
            return new Promise(res => {
                if (dlgResolve) dlgResolve(false);
                dlgResolve = res;
                $('#um-dialog-title').text(o.title || '');
                $('#um-dialog-text').text(o.text || '');
                $('#um-dialog-preview').text(o.preview || '').toggleClass('um-hide', !o.preview);
                $('#um-dialog-ok').text(o.confirmText || 'Ya');
                $dlg.removeClass('um-hide');
                setTimeout(() => $('#um-dialog-cancel').trigger('focus'), 20);
            });
        }

        function closeDialog(v) {
            if (!dlgResolve) return;
            $dlg.addClass('um-hide');
            const r = dlgResolve;
            dlgResolve = null;
            r(v);
        }
        $('#um-dialog-ok').on('click', () => closeDialog(true));
        $('#um-dialog-cancel').on('click', () => closeDialog(false));
        $dlg.on('click', e => {
            if (e.target === $dlg[0]) closeDialog(false);
        });

        const previewText = m => {
            const t = (labelOf(m) || '').replace(/\s+/g, ' ').trim();
            return t.length > 90 ? t.slice(0, 90) + '…' : t;
        };

        // ---------- Avatar (foto / inisial) + titik status ----------
        function avatar(u, px, withDot, zoomable) {
            const $a = $('<div class="um-av">').css({
                width: px,
                height: px
            });
            const h = hue(u.name);
            const $c = $('<div class="um-av-in">').css({
                background: 'linear-gradient(135deg,hsl(' + h + ' 70% 56%),hsl(' + ((h + 40) % 360) + ' 65% 42%))',
                fontSize: Math.round(px * 0.38)
            });

            let hasPhoto = false;
            if (u.avatar && !badAvatars.has(u.avatar)) {
                hasPhoto = true;
                $('<img alt="">').attr('src', u.avatar)
                    .on('error', function() {
                        markBadAvatar(u.avatar);
                        $(this).remove();
                        $c.text(initials(u.name));
                        $a.removeClass('um-zoomable').off('click.zoom');
                    })
                    .appendTo($c);
            } else {
                $c.text(initials(u.name));
            }
            $a.append($c);

            if (withDot) {
                $a.append($('<span class="um-dot">').toggleClass('on', st.online.has(u.id)));
            }

            if (zoomable && hasPhoto) {
                $a.addClass('um-zoomable').attr('title', 'Klik untuk memperbesar foto')
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
            zoomToken = 0;

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
            if (e.target === $zoom[0]) closeZoom();
        });
        $zoom.on('contextmenu dragstart selectstart', e => e.preventDefault());

        // ---------- Title halaman + suara notifikasi ----------
        let prevUnread = null;
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
            if (muted) return;
            const now = Date.now();
            if (now - lastDing < 1000) return;
            lastDing = now;
            try {
                if (!audioCtx || audioCtx.state !== 'running') return;
                const t = audioCtx.currentTime;
                const comp = audioCtx.createDynamicsCompressor();
                comp.threshold.value = -12;
                comp.ratio.value = 6;
                comp.connect(audioCtx.destination);

                [
                    [880, 0],
                    [1175, 0.14],
                    [1568, 0.28]
                ].forEach(([freq, delay]) => {
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.0001, t + delay);
                    gain.gain.exponentialRampToValueAtTime(0.9, t + delay + 0.02);
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
            $badge.text(total > 99 ? '99+' : total).toggleClass('um-hide', total <= 0);
            $toggle.toggleClass('um-ring', total > 0 && !st.open);
        }
        // ---------- Tooltip pesan lengkap saat hover kontak ----------
        const $tip = $('#um-tip');
        let tipCid = null;

        function hideTip() {
            tipCid = null;
            $tip.addClass('um-hide');
        }

        function showContactTip($btn) {
            if (window.matchMedia('(hover: none)').matches) return; // layar sentuh: tidak perlu

            const $t = $btn.find('.um-cprev .t').first();
            const full = ($t.attr('data-full') || '').trim();
            if (!$t.length || !full) return hideTip();

            // tampilkan hanya jika teks memang terpotong atau punya baris baru
            const cut = $t[0].scrollWidth > $t[0].clientWidth + 1 || /\n/.test(full);
            if (!cut) return hideTip();

            tipCid = $btn.attr('data-cid');
            $tip.empty().append(
                $('<div class="tn">').text($btn.find('.um-cname').first().text()),
                $('<div class="tt">').text(full.length > 600 ? full.slice(0, 600) + '…' : full)
            ).removeClass('um-hide');

            const p = $panel[0].getBoundingClientRect();
            const b = $btn[0].getBoundingClientRect();
            $tip.css('max-width', Math.min(272, p.width - 20));
            const h = $tip.outerHeight();
            const w = $tip.outerWidth();

            let top = b.bottom - p.top + 2;
            if (top + h > p.height - 8) top = Math.max(8, b.top - p.top - h - 2);
            const left = Math.max(10, Math.min(b.left - p.left + 56, p.width - w - 10));
            $tip.css({
                left: left,
                top: top
            });
        }

        $contacts.on('mouseenter', '.um-contact', function() {
            showContactTip($(this));
        });
        $contacts.on('mouseleave click', '.um-contact', hideTip);
        $contacts.on('scroll', hideTip);
        // ---------- Render daftar kontak ----------
        const contactSkel = () => '<div>' + [1, 2, 3, 4, 5].map(() =>
            '<div class="um-skel-row"><div class="um-skel c"></div><div class="ls"><i class="um-skel" style="width:45%"></i><i class="um-skel" style="width:75%"></i></div></div>').join('') + '</div>';

        function renderContacts() {
            const q = $search.val().trim().toLowerCase();
            const all = [...st.contacts.values()];
            const unreadN = all.filter(c => c.unread > 0).length;
            const onlineN = all.filter(c => st.online.has(c.id)).length;

            $('#um-chip-unread-n').text(unreadN).toggleClass('um-hide', !unreadN);
            $('#um-chip-online-n').text(onlineN).toggleClass('um-hide', !onlineN);
            $('.um-chip').each(function() {
                $(this).toggleClass('on', $(this).attr('data-f') === st.filter);
            });
            $('#um-search-clear').toggleClass('um-hide', !q);

            let list = all.filter(c => !q || c.name.toLowerCase().includes(q));
            if (st.filter === 'unread') list = list.filter(c => c.unread > 0);
            if (st.filter === 'online') list = list.filter(c => st.online.has(c.id));
            list.sort((a, b) => {
                const ta = a.last ? Date.parse(a.last.created_at) : 0;
                const tb = b.last ? Date.parse(b.last.created_at) : 0;
                if (tb !== ta) return tb - ta;
                const oa = st.online.has(a.id),
                    ob = st.online.has(b.id);
                if (oa !== ob) return ob - oa;
                return a.name.localeCompare(b.name);
            });

            const keepScroll = $contacts[0].scrollTop;
            $contacts.empty();

            // strip "Sedang online"
            if (!q && st.filter === 'all') {
                const onl = all.filter(c => st.online.has(c.id)).slice(0, 14);
                if (onl.length) {
                    $contacts.append($('<div class="um-strip-title">').text('Sedang online'));
                    const $strip = $('<div class="um-strip">').appendTo($contacts);
                    onl.forEach(c => {
                        $('<button type="button" class="um-strip-item">')
                            .append(avatar(c, 46, true), $('<span class="n">').text(c.name.split(' ')[0]))
                            .attr('title', c.name)
                            .on('click', () => openChat(c.id))
                            .appendTo($strip);
                    });
                }
            }

            if (!list.length) {
                const msg = q ? ['🔍', 'Tidak ditemukan', 'Coba kata kunci lain.'] :
                    st.filter === 'unread' ? ['🎉', 'Semua sudah dibaca', 'Tidak ada pesan baru.'] :
                    st.filter === 'online' ? ['😴', 'Belum ada yang online', 'Coba lagi beberapa saat.'] : ['👥', 'Tidak ada pengguna', 'Daftar kontak kosong.'];
                const $e = $('<div class="um-empty">').append(
                    $('<div class="um-empty-ic">').text(msg[0]),
                    $('<div class="um-empty-t">').text(msg[1]),
                    $('<div class="um-empty-s">').text(msg[2]));
                if (st.filter !== 'all' && !q) {
                    $e.append($('<button type="button" class="um-empty-btn">').text('Lihat semua').on('click', () => {
                        st.filter = 'all';
                        renderContacts();
                    }));
                }
                $contacts.append($e);
                return;
            }

            list.forEach(c => {
                const draft = drafts[c.id] && c.id !== st.active ? drafts[c.id].replace(/\s+/g, ' ').trim() : '';
                const $prev = $('<div class="um-cprev">').toggleClass('new', c.unread > 0);
                if (draft) {
                    $prev.append(
                        $('<span class="draft">').text('Draf:'),
                        $('<span class="t">').attr('data-full', drafts[c.id]).text(draft)
                    );
                } else if (c.last) {
                    if (c.last.sender_id === ME && !c.last.unsent) {
                        $prev.append($('<span style="display:flex;flex-shrink:0">').html(tick(!!c.last.read_at)));
                    }
                    $prev.append(
                        $('<span class="t">')
                        .toggleClass('it', !!c.last.unsent)
                        .attr('data-full', labelOf(c.last))
                        .text(labelOf(c.last))
                    );
                } else {
                    $prev.append($('<span class="t it">').text('Mulai percakapan'));
                }
                const $top = $('<div class="um-row2">').append(
                    $('<span class="um-cname um-trunc">').text(c.name),
                    $('<span class="um-ctime">').toggleClass('new', c.unread > 0).text(c.last ? shortTime(c.last.created_at) : '')
                );
                const $bottom = $('<div class="um-row2">').append($prev);
                if (c.unread) {
                    $bottom.append($('<span class="um-unread">').text(c.unread > 99 ? '99+' : c.unread));
                }

                $('<button type="button" class="um-contact">')
                    .attr('data-cid', c.id)
                    .append(avatar(c, 46, true), $('<div class="um-contact-body">').append($top, $bottom))
                    .on('click', () => openChat(c.id))
                    .appendTo($contacts);
            });

            // daftar dirender ulang oleh polling: pertahankan tooltip jika kursor masih di atas kontak yang sama
            if (tipCid) {
                const $h = $contacts.find('.um-contact[data-cid="' + tipCid + '"]');
                if ($h.length && $h.is(':hover')) showContactTip($h);
                else hideTip();
            }

            $contacts[0].scrollTop = keepScroll;
        }

        $('.um-chip').on('click', function() {
            st.filter = $(this).attr('data-f');
            renderContacts();
        });
        $('#um-search-clear').on('click', () => {
            $search.val('').trigger('focus');
            renderContacts();
        });

        // ---------- Header percakapan ----------
        function renderHeader() {
            const c = st.contacts.get(st.active);
            if (!c) return;
            $('#um-chat-avatar').empty().append(avatar(c, 40, true, true));
            $('#um-chat-name').text(c.name);
            const on = st.online.has(c.id);
            $('#um-chat-status').empty().append(on ? [$('<span class="um-pulse">'), document.createTextNode('Online')] : document.createTextNode('Offline'));
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
            $menu.addClass('um-hide').empty().removeData('mid');
        }

        function copyText(t) {
            const fallback = () => {
                const ta = document.createElement('textarea');
                ta.value = t;
                ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    toast('Teks disalin');
                } catch (e) {
                    flashError('Gagal menyalin teks.');
                }
                ta.remove();
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(t).then(() => toast('Teks disalin'), fallback);
            } else {
                fallback();
            }
        }

        function openMenu(m, $anchor) {
            if (!$menu.hasClass('um-hide') && $menu.data('mid') === m.id) return closeMenu();

            const mine = m.sender_id === ME;
            const age = Date.now() - Date.parse(m.created_at);
            const isText = (m.type || 'text') === 'text';
            const items = [];
            if (!m.unsent) items.push({
                label: 'Balas',
                ic: 'reply',
                fn: () => startReply(m)
            });
            if (!m.unsent && isText) items.push({
                label: 'Salin teks',
                ic: 'copy',
                fn: () => copyText(m.body)
            });
            if (!mine && !m.unsent && isUserSticker(m)) items.push({
                label: 'Simpan ke stiker saya',
                ic: 'star',
                fn: () => saveSticker(m)
            });
            if (mine && !m.unsent && age <= EDIT_MS && isText) items.push({
                label: 'Edit pesan',
                ic: 'edit',
                fn: () => startEdit(m)
            });
            if (mine && !m.unsent && age <= UNSEND_MS) items.push({
                label: 'Tarik pesan (untuk semua)',
                ic: 'undo',
                fn: () => unsendMsg(m),
                danger: true
            });
            items.push({
                label: 'Hapus untuk saya',
                ic: 'trash',
                fn: () => deleteMsg(m),
                danger: true
            });

            $menu.empty();
            items.forEach(it => {
                $('<button type="button" role="menuitem">').toggleClass('danger', !!it.danger)
                    .html(svg(IC[it.ic], 16))
                    .append(document.createTextNode(it.label))
                    .on('click', () => {
                        closeMenu();
                        it.fn();
                    })
                    .appendTo($menu);
            });

            $menu.removeClass('um-hide').data('mid', m.id);

            const p = $panel[0].getBoundingClientRect(),
                b = $anchor[0].getBoundingClientRect();
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
        const skel = () => '<div class="um-skels">' + [
            ['l', '55%'],
            ['r', '40%'],
            ['l', '70%'],
            ['r', '48%'],
            ['l', '35%']
        ].map(a => '<div class="um-skel ' + a[0] + '" style="width:' + a[1] + '"></div>').join('') + '</div>';

        const nearBottom = () => {
            const e = $msgs[0];
            return e.scrollHeight - e.scrollTop - e.clientHeight < 80;
        };

        function updateFab() {
            const show = st.active && !nearBottom();
            $fab.toggleClass('um-hide', !show);
            $('#um-fab-n').text(st.newBelow > 99 ? '99+' : st.newBelow).toggleClass('um-hide', !st.newBelow || !show);
        }

        function scrollBottom(smooth) {
            const el = $msgs[0];
            if (smooth && el.scrollTo) el.scrollTo({
                top: el.scrollHeight,
                behavior: 'smooth'
            });
            else el.scrollTop = el.scrollHeight;
            st.newBelow = 0;
        }
        $fab.on('click', () => {
            scrollBottom(true);
            setTimeout(updateFab, 350);
        });

        function renderMessages(mode) {
            closeMenu();
            const el = $msgs[0],
                prevTop = el.scrollTop,
                prevH = el.scrollHeight;
            $msgs.empty();

            if (st.hasMore) {
                $('<button type="button" class="um-older">').text('Muat pesan sebelumnya').on('click', loadOlder).appendTo($msgs);
            }

            if (!st.msgs.length) {
                const c = st.contacts.get(st.active);
                $msgs.append($('<div class="um-empty">').append(
                    $('<div class="um-empty-ic">').text('👋'),
                    $('<div class="um-empty-t">').text('Belum ada pesan'),
                    $('<div class="um-empty-s">').text('Sapa ' + (c ? c.name : 'dia') + ' untuk memulai percakapan')));
            }

            let lastDay = '',
                prev = null;
            st.msgs.forEach((m, i) => {
                const t = Date.parse(m.created_at),
                    d = new Date(t),
                    day = d.toDateString();
                let brk = false;

                if (day !== lastDay) {
                    lastDay = day;
                    brk = true;
                    $msgs.append($('<div class="um-day">').append($('<span>').text(dayLabel(d))));
                }
                if (st.divider === m.id) {
                    brk = true;
                    $msgs.append($('<div class="um-unread-div">').append($('<span>').text('Pesan belum dibaca')));
                }

                const mine = m.sender_id === ME;
                const grouped = !brk && prev && prev.sender_id === m.sender_id && (t - Date.parse(prev.created_at)) < GROUP_MS;
                const nx = st.msgs[i + 1];
                const contNext = nx && nx.sender_id === m.sender_id && (Date.parse(nx.created_at) - t) < GROUP_MS &&
                    new Date(nx.created_at).toDateString() === day && st.divider !== nx.id;
                const last = !contNext;
                prev = m;

                const isMedia = !m.unsent && (m.type === 'sticker' || m.type === 'gif' || m.type === 'image');
                const $wrap = $('<div class="um-wrap">');
                let $b;

                if (isMedia) {
                    const $img = $('<img alt="">')
                        .addClass(m.type === 'sticker' ? 'um-media-sticker' : (m.type === 'image' ? 'um-media-photo' : 'um-media-gif'))
                        .attr('src', m.body)
                        .attr('title', 'Klik untuk memperbesar')
                        .on('click', () => openZoom(m.body, senderName(m.sender_id)))
                        .on('load', () => {
                            if (mode === 'bottom') el.scrollTop = el.scrollHeight;
                        })
                        .on('error', function() {
                            $(this).replaceWith('<span class="um-media-err">Media tidak dapat dimuat</span>');
                        });
                    $b = $('<div>');
                    // if (m.type === 'gif') $b.addClass('um-media-gif-box ' + (mine ? 'out' : 'in'));
                    if (m.type !== 'sticker') $b.addClass('um-media-gif-box ' + (mine ? 'out' : 'in'));
                    $b.append($img);
                    $b.on('contextmenu', function(e) {
                        e.preventDefault();
                        if (Date.now() - lastLP < 800) return;
                        openMenu(m, $b);
                    });
                    if (m.reply) $wrap.append(quoteEl(m.reply, mine, true));
                } else {
                    const emo = !m.unsent && !m.reply && (m.type || 'text') === 'text' && isEmojiOnly(m.body);
                    $b = $('<div class="um-bubble">')
                        .addClass(mine ? 'out' : 'in')
                        .toggleClass('tail', last)
                        .toggleClass('emo', emo)
                        .toggleClass('unsent', !!m.unsent)
                        .toggleClass('editing', !!(st.editing && st.editing.id === m.id));
                    if (m.reply && !m.unsent) $b.append(quoteEl(m.reply, mine, false));
                    const $t = $('<div>');
                    if (m.unsent) $t.text('🚫 Pesan ini telah ditarik');
                    else if (m.type === 'file') $t.append(fileCard(m));
                    else fillText($t, m.body || '');
                    $b.append($t);
                }
                $wrap.append($b);

                if (last || (m.edited_at && !m.unsent)) {
                    const $meta = $('<div class="um-meta">').append($('<span>').text((m.edited_at && !m.unsent ? 'diedit · ' : '') + fmtTime(m.created_at)));
                    if (mine && !m.unsent) {
                        $meta.append($('<span style="display:flex">').attr('title', m.read_at ? 'Dibaca' : 'Terkirim').html(tick(!!m.read_at)));
                    }
                    $wrap.append($meta);
                }

                const $more = $('<button type="button" class="um-act um-more" aria-label="Opsi pesan" aria-haspopup="menu">')
                    .html(svg(IC.more, 16))
                    .on('click', function(e) {
                        e.stopPropagation();
                        openMenu(m, $(this));
                    });
                const $rep = m.unsent ? null : $('<button type="button" class="um-act um-act-reply" aria-label="Balas" title="Balas">')
                    .html(svg(IC.reply, 15))
                    .on('click', e => {
                        e.stopPropagation();
                        startReply(m);
                    });
                const $acts = $('<div class="um-actions">').append($rep, $more);

                const $row = $('<div class="um-row">').toggleClass('mine', mine).toggleClass('gap', !grouped && i > 0).attr('data-mid', m.id);
                if (mine) $row.append($acts, $wrap);
                else $row.append($wrap, $acts);
                $msgs.append($row);
            });

            if (mode === 'bottom') el.scrollTop = el.scrollHeight;
            else if (mode === 'divider') {
                const dv = el.querySelector('.um-unread-div');
                el.scrollTop = dv ? Math.max(0, dv.offsetTop - 56) : el.scrollHeight;
            } else if (mode === 'older') el.scrollTop = el.scrollHeight - prevH + prevTop;
            else el.scrollTop = prevTop;
            updateFab();
        }

        $msgs.on('scroll', () => {
            closeMenu();
            if (nearBottom()) st.newBelow = 0;
            updateFab();
        });

        // ---------- Pindah view ----------
        function showView(list) {
            $listView.toggleClass('um-hide', !list);
            $chatView.toggleClass('um-hide', list);
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
            }).fail(() => {
                if (!st.contacts.size) {
                    $contacts.empty().append($('<div class="um-empty">').append(
                        $('<div class="um-empty-ic">').text('⚠️'),
                        $('<div class="um-empty-t">').text('Gagal memuat kontak'),
                        $('<button type="button" class="um-empty-btn">').text('Coba lagi').on('click', () => {
                            $contacts.html(contactSkel());
                            loadContacts();
                        })));
                }
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

        function syncRead() {
            const c = st.contacts.get(st.active);
            if (st.open && st.active && c && c.unread > 0 && document.visibilityState === 'visible') markRead(st.active);
        }

        function openChat(id) {
            const c = st.contacts.get(id);
            if (!c) return;
            saveDraft();
            cancelEdit();
            cancelReply();
            setPicker(false);
            st.active = id;
            st.msgs = [];
            st.hasMore = false;
            st.since = null;
            st.divider = null;
            st.newBelow = 0;
            showView(false);
            renderHeader();
            $msgs.html(skel());
            $fab.addClass('um-hide');
            $input.val(drafts[id] || '');
            autoResize();
            updateSend();

            const unreadAtOpen = c.unread || 0;
            $.get(BASE + '/' + id).done(res => {
                if (st.active !== id) return;
                st.msgs = res.messages;
                st.hasMore = res.has_more;
                st.since = res.now || null;
                if (unreadAtOpen > 0) {
                    const inc = st.msgs.filter(m => m.sender_id !== ME);
                    if (inc.length) st.divider = inc[Math.max(0, inc.length - unreadAtOpen)].id;
                }
                renderMessages(st.divider ? 'divider' : 'bottom');
                if (c.unread > 0) markRead(id);
            }).fail(() => {
                $msgs.empty().append($('<div class="um-empty">').append(
                    $('<div class="um-empty-ic">').text('⚠️'),
                    $('<div class="um-empty-t">').text('Gagal memuat pesan'),
                    $('<button type="button" class="um-empty-btn">').text('Coba lagi').on('click', () => openChat(id))));
            });

            if (!isMobile()) setTimeout(() => $input.trigger('focus'), 60);
        }

        function closeChat() {
            closeAttach();
            closeCamera();
            closePhotoPreview(); // <-- tambahan
            saveDraft();
            cancelEdit();
            cancelReply();
            closeZoom();
            closeMenu();
            setPicker(false);
            st.active = null;
            st.since = null;
            st.divider = null;
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
            $replyBar.removeClass('um-hide');
            if (!isMobile()) $input.trigger('focus');
        }

        function cancelReply() {
            if (!st.replying) return;
            st.replying = null;
            $replyBar.addClass('um-hide');
        }

        // kutipan pesan yang dibalas (di dalam gelembung, atau berdiri sendiri untuk stiker/GIF)
        function quoteEl(r, mine, solo) {
            const label = r.unsent ? '🚫 Pesan ditarik' :
                r.type === 'sticker' ? '🖼️ Stiker' :
                r.type === 'gif' ? '🎞️ GIF' :
                r.type === 'image' ? '📷 Foto' :
                r.type === 'file' ? '📎 Dokumen' : (r.body || '');
            return $('<button type="button" class="um-quote">')
                .addClass(solo ? 'q-solo' : (mine ? 'q-out' : 'q-in'))
                .append($('<div class="qn">').text(senderName(r.sender_id)), $('<div class="qt">').text(label))
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
            saveDraft();
            st.editing = m;
            $editBar.removeClass('um-hide');
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
            $editBar.addClass('um-hide');
            $input.val(st.active ? (drafts[st.active] || '') : '');
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
                    $editBar.addClass('um-hide');
                    $input.val(drafts[st.active] || '');
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
                text: 'Pesan akan ditarik untuk semua orang dan tidak dapat dikembalikan.',
                preview: previewText(m),
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
                text: 'Pesan hanya dihapus untuk Anda. Lawan bicara tetap dapat melihatnya.',
                preview: m.unsent ? '' : previewText(m),
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
        const REC = '🕘';
        const getRecent = () => {
            try {
                return JSON.parse(store.get('um-recent-emoji', '[]')) || [];
            } catch (e) {
                return [];
            }
        };

        function pushRecent(e) {
            const r = getRecent().filter(x => x !== e);
            r.unshift(e);
            store.set('um-recent-emoji', JSON.stringify(r.slice(0, 24)));
        }

        const pk = {
            open: false,
            tab: 'emoji',
            cat: Object.keys(EMOJI)[0],
            gifT: null,
            gifQ: '',
            mine: null,
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
            saveDraft();
            if (!isMobile()) el.focus();
        }

        function setPicker(open) {
            pk.open = !!open && !!st.active;
            $picker.toggleClass('um-hide', !pk.open);
            $('#um-emoji-btn').toggleClass('on', pk.open);
            if (pk.open) renderPicker();
        }

        const imgTile = (url, cls) => $('<img alt="" loading="lazy">').addClass(cls || '').attr('src', url);

        function renderPicker() {
            $('.um-tab').each(function() {
                $(this).toggleClass('on', $(this).attr('data-tab') === pk.tab);
            });
            $pkSub.empty();
            $pkBody.empty();

            // ----- EMOJI -----
            if (pk.tab === 'emoji') {
                const recent = getRecent();
                if (pk.cat === REC && !recent.length) pk.cat = Object.keys(EMOJI)[0];
                const cats = (recent.length ? [REC] : []).concat(Object.keys(EMOJI));
                const $cats = $('<div class="um-cats">').appendTo($pkSub);
                cats.forEach(k => {
                    $('<button type="button" class="um-cat">').toggleClass('on', k === pk.cat).text(k)
                        .attr('title', k === REC ? 'Terakhir dipakai' : '')
                        .on('click', () => {
                            pk.cat = k;
                            renderPicker();
                        })
                        .appendTo($cats);
                });
                const $g = $('<div class="um-egrid">').appendTo($pkBody);
                const list = pk.cat === REC ? recent : EMOJI[pk.cat].split(' ');
                list.forEach(e => {
                    $('<button type="button">').text(e)
                        .on('click', () => {
                            insertAtCursor(e);
                            pushRecent(e);
                        })
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

                $pkBody.append('<p class="um-label">Stiker saya</p>');
                const $g = $('<div class="um-sgrid">').appendTo($pkBody);

                $('<button type="button" class="um-sadd" title="Tambah stiker dari gambar" aria-label="Tambah stiker">')
                    .html('<b>+</b>Tambah')
                    .on('click', () => $('#um-file-sticker').trigger('click'))
                    .appendTo($g);

                (pk.mine || []).forEach(s => {
                    const $t = $('<div class="st">').appendTo($g);
                    $('<button type="button" class="pick">')
                        .append(imgTile(s.url))
                        .on('click', () => send({
                            type: 'sticker',
                            url: s.url
                        }))
                        .appendTo($t);
                    $('<button type="button" class="um-sdel" aria-label="Hapus stiker" title="Hapus stiker">')
                        .text('×')
                        .on('click', e => {
                            e.stopPropagation();
                            deleteMySticker(s);
                        })
                        .appendTo($t);
                });

                if (pk.mine === null) $pkBody.append('<p class="um-note" style="padding:.6rem 0">Memuat…</p>');

                if (STICKERS.length) {
                    $pkBody.append('<p class="um-label" style="margin-top:.8rem">Bawaan</p>');
                    const $g2 = $('<div class="um-sgrid">').appendTo($pkBody);
                    STICKERS.forEach(u => {
                        $('<div class="st">').append(
                            $('<button type="button" class="pick">').append(imgTile(u))
                            .on('click', () => send({
                                type: 'sticker',
                                url: u
                            }))
                        ).appendTo($g2);
                    });
                }
            }

            // ----- GIF (Giphy + unggah sendiri) -----
            if (pk.tab === 'gif') {
                const $row = $('<div class="um-grow">').appendTo($pkSub);
                $('<input type="search" placeholder="Cari GIF…" autocomplete="off" aria-label="Cari GIF">')
                    .val(pk.gifQ)
                    .on('input', function() {
                        const q = this.value.trim();
                        clearTimeout(pk.gifT);
                        pk.gifT = setTimeout(() => loadGifs(q), 400);
                    })
                    .appendTo($row);
                $('<button type="button" title="Unggah file GIF dari perangkat">').text('Unggah')
                    .on('click', () => $('#um-file-gif').trigger('click'))
                    .appendTo($row);
                loadGifs(pk.gifQ);
            }
        }

        function loadGifs(q) {
            pk.gifQ = q;
            $pkBody.html('<div class="um-gcols">' + [90, 60, 75, 110].map(h => '<div class="um-skel" style="height:' + h + 'px;margin-bottom:.5rem"></div>').join('') + '</div>');
            $.get(BASE + '/gifs', {
                q: q
            }).done(res => {
                if (pk.tab !== 'gif' || pk.gifQ !== q) return;
                $pkBody.empty();
                if (!res.gifs.length) {
                    $pkBody.html('<p class="um-note">GIF tidak ditemukan.</p>');
                    return;
                }
                const $g = $('<div class="um-gcols">').appendTo($pkBody);
                res.gifs.forEach(g => {
                    $('<button type="button">')
                        .append(imgTile(g.preview))
                        .on('click', () => send({
                            type: 'gif',
                            url: g.url
                        }))
                        .appendTo($g);
                });
                $pkBody.append('<p class="um-powered">Powered by GIPHY</p>');
            }).fail(() => {
                if (pk.tab === 'gif' && pk.gifQ === q) {
                    $pkBody.html('<p class="um-note err">Gagal memuat GIF.</p>');
                }
            });
        }

        // ---------- Stiker buatan sendiri ----------
        function addMySticker(s) {
            if (!pk.mine) return;
            if (!pk.mine.some(x => x.id === s.id)) pk.mine.unshift(s);
        }

        function uploadSticker(file) {
            if (pk.uploading) return;
            if (!/^image\/(png|jpeg|webp|gif)$/.test(file.type)) return flashError('Format harus PNG, JPG, WebP, atau GIF.');
            if (file.size > MAX_UPLOAD) return flashError('Ukuran gambar maksimal 5 MB.');

            const fd = new FormData();
            fd.append('file', file);
            pk.uploading = true;
            toast('Mengunggah stiker…');

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
            toast('Mengunggah GIF…');

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
            pk.tab = $(this).attr('data-tab');
            renderPicker();
        });
        // ---------- Lampiran foto (galeri / kamera / paste) ----------
        const PHOTO_MAX_SIDE = 1600;
        const $attachMenu = $('#um-attach-menu'),
            $photoPrev = $('#um-photo-prev'),
            $camera = $('#um-camera');
        let photo = null,
            camStream = null;

        function closeAttach() {
            $attachMenu.addClass('um-hide');
        }

        // perkecil + koreksi orientasi EXIF + encode JPEG, supaya foto HP 8 MB tidak melewati batas upload
        async function compressImage(file) {
            let bmp;
            try {
                bmp = await createImageBitmap(file, {
                    imageOrientation: 'from-image'
                });
            } catch (e) {
                bmp = await new Promise((res, rej) => {
                    const u = URL.createObjectURL(file),
                        i = new Image();
                    i.onload = () => {
                        URL.revokeObjectURL(u);
                        res(i);
                    };
                    i.onerror = () => {
                        URL.revokeObjectURL(u);
                        rej(new Error('decode'));
                    };
                    i.src = u;
                });
            }
            const w0 = bmp.width || bmp.naturalWidth,
                h0 = bmp.height || bmp.naturalHeight;
            const s = Math.min(1, PHOTO_MAX_SIDE / Math.max(w0, h0));
            const cv = document.createElement('canvas');
            cv.width = Math.max(1, Math.round(w0 * s));
            cv.height = Math.max(1, Math.round(h0 * s));
            const ctx = cv.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, cv.width, cv.height);
            ctx.drawImage(bmp, 0, 0, cv.width, cv.height);
            if (bmp.close) bmp.close();
            return new Promise((res, rej) => cv.toBlob(b => b ? res(b) : rej(new Error('blob')), 'image/jpeg', 0.85));
        }

        function closePhotoPreview() {
            if (photo) {
                URL.revokeObjectURL(photo.url);
                photo = null;
            }
            $('#um-photo-img').removeAttr('src');
            $photoPrev.addClass('um-hide');
        }

        function handlePhotoFile(file) {
            if (!st.active) return;
            if (!file || !/^image\//.test(file.type)) return flashError('File harus berupa gambar.');
            compressImage(file).then(blob => {
                if (blob.size > MAX_UPLOAD) return flashError('Ukuran foto maksimal 5 MB.');
                closePhotoPreview();
                photo = {
                    blob: blob,
                    url: URL.createObjectURL(blob)
                };
                $('#um-photo-img').attr('src', photo.url);
                $photoPrev.removeClass('um-hide');
            }).catch(() => flashError('Foto tidak dapat diproses. Coba format JPG atau PNG.'));
        }

        function sendPhoto() {
            if (!photo || pk.uploading || st.busy) return;
            const fd = new FormData();
            fd.append('file', photo.blob, 'foto.jpg');
            pk.uploading = true;
            $('#um-photo-send').prop('disabled', true).text('Mengunggah…');

            $.ajax({
                    url: BASE + '/image-upload',
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                })
                .done(r => {
                    closePhotoPreview();
                    send({
                        type: 'image',
                        url: r.url
                    });
                })
                .fail(xhr => flashError(errMsg(xhr, 'Gagal mengunggah foto. Coba lagi.')))
                .always(() => {
                    pk.uploading = false;
                    $('#um-photo-send').prop('disabled', false).text('Kirim');
                });
        }

        // kamera: HP memakai aplikasi kamera bawaan (input capture), desktop memakai getUserMedia
        function stopCam() {
            if (camStream) {
                camStream.getTracks().forEach(t => t.stop());
                camStream = null;
            }
            $('#um-cam-video')[0].srcObject = null;
        }

        function closeCamera() {
            stopCam();
            $camera.addClass('um-hide');
        }

        function openCamera() {
            if (!st.active) return;
            const coarse = window.matchMedia('(pointer: coarse)').matches;
            const canStream = navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.isSecureContext;
            if (coarse || !canStream) return $('#um-file-camera').trigger('click');

            navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: {
                            ideal: 1920
                        }
                    },
                    audio: false
                })
                .then(s => {
                    camStream = s;
                    $('#um-cam-video')[0].srcObject = s;
                    $camera.removeClass('um-hide');
                })
                .catch(() => flashError('Kamera tidak dapat diakses. Periksa izin kamera di browser.'));
        }

        $('#um-attach-btn').on('click', e => {
            e.stopPropagation();
            if (!st.active) return;
            setPicker(false);
            $attachMenu.toggleClass('um-hide');
        });
        $attachMenu.on('click', 'button', function() {
            const a = $(this).attr('data-a');
            closeAttach();
            if (a === 'gallery') $('#um-file-photo').trigger('click');
            if (a === 'camera') openCamera();
            if (a === 'doc') $('#um-file-doc').trigger('click');
        });

        // ---------- Dokumen (maks 2 MB) ----------
        const DOC_MAX = 2 * 1024 * 1024;
        const DOC_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'txt', 'csv', 'rtf', 'zip'];

        function uploadDoc(file) {
            if (!st.active || pk.uploading || st.busy) return;
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!DOC_EXT.includes(ext)) return flashError('Format dokumen tidak didukung.');
            if (file.size > DOC_MAX) return flashError('Ukuran dokumen maksimal 2 MB.');

            const fd = new FormData();
            fd.append('file', file);
            pk.uploading = true;
            toast('Mengunggah dokumen…');

            $.ajax({
                    url: BASE + '/file-upload',
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                })
                .done(r => send({
                    type: 'file',
                    url: JSON.stringify({
                        url: r.url,
                        name: r.name,
                        size: r.size
                    }) // body pesan file berupa JSON
                }))
                .fail(xhr => flashError(errMsg(xhr, 'Gagal mengunggah dokumen. Coba lagi.')))
                .always(() => {
                    pk.uploading = false;
                });
        }

        $('#um-file-doc').on('change', function() {
            const f = this.files && this.files[0];
            this.value = '';
            if (f) uploadDoc(f);
        });
        $(document).on('click', e => {
            if (!$(e.target).closest('#um-attach-menu, #um-attach-btn').length) closeAttach();
        });

        $('#um-file-photo, #um-file-camera').on('change', function() {
            const f = this.files && this.files[0];
            this.value = '';
            if (f) handlePhotoFile(f);
        });
        $('#um-photo-send').on('click', sendPhoto);
        $('#um-photo-cancel').on('click', closePhotoPreview);
        $('#um-cam-cancel').on('click', closeCamera);
        $('#um-cam-shot').on('click', () => {
            const v = $('#um-cam-video')[0];
            if (!v.videoWidth) return;
            const cv = document.createElement('canvas');
            cv.width = v.videoWidth;
            cv.height = v.videoHeight;
            cv.getContext('2d').drawImage(v, 0, 0);
            cv.toBlob(b => {
                if (!b) return;
                closeCamera();
                handlePhotoFile(new File([b], 'kamera.jpg', {
                    type: 'image/jpeg'
                }));
            }, 'image/jpeg', 0.92);
        });

        // tempel gambar dari clipboard (Ctrl+V)
        $input.on('paste', e => {
            const items = (e.originalEvent.clipboardData || {}).items || [];
            for (const it of items) {
                if (it.kind === 'file' && it.type.startsWith('image/')) {
                    e.preventDefault();
                    handlePhotoFile(it.getAsFile());
                    return;
                }
            }
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
                        delete drafts[to];
                        try {
                            sessionStorage.setItem('um-drafts', JSON.stringify(drafts));
                        } catch (e) {}
                    } else {
                        setPicker(false);
                    }
                    const c = st.contacts.get(to);
                    if (c) c.last = m;
                    if (st.active === to && !st.msgs.some(x => x.id === m.id)) {
                        st.msgs.push(m);
                        st.divider = null;
                        renderMessages('bottom');
                    }
                    renderContacts();
                })
                .fail(xhr => flashError(errMsg(xhr, 'Gagal mengirim pesan. Coba lagi.')))
                .always(() => {
                    st.busy = false;
                    updateSend();
                    if (!media && !isMobile()) $input.trigger('focus');
                });
        }

        // ---------- Buka / tutup panel ----------
        function openPanel() {
            st.open = true;
            $panel.addClass('um-open');
            $toggle.attr('aria-expanded', 'true').removeClass('um-ring');
            $('#um-icon-open').addClass('um-hide');
            $('#um-icon-close').removeClass('um-hide');
            if (isMobile()) $toggle.addClass('um-hide');
            document.body.classList.toggle('overflow-hidden', isMobile());
            if (st.active) {
                renderMessages('bottom');
                syncRead();
            } else if (!isMobile()) {
                setTimeout(() => $search.trigger('focus'), 80);
            }
            pollNow();
        }

        function closePanel() {
            closeAttach();
            closeCamera();
            closePhotoPreview(); // <-- tambahan
            closeMenu();
            closeZoom();
            closeDialog(false);
            setPicker(false);
            saveDraft();
            st.open = false;
            $panel.removeClass('um-open');
            $toggle.removeClass('um-hide').attr('aria-expanded', 'false');
            $('#um-icon-open').removeClass('um-hide');
            $('#um-icon-close').addClass('um-hide');
            document.body.classList.remove('overflow-hidden');
            updateBadge();
        }

        $toggle.on('click', () => st.open ? closePanel() : openPanel());
        $('.um-close').on('click', closePanel);
        $('#um-back').on('click', closeChat);
        $('#um-edit-cancel').on('click', cancelEdit);
        $('#um-reply-cancel').on('click', cancelReply);

        // Escape: (popup Swal) -> dialog -> zoom -> menu -> picker -> batal edit -> batal balas -> tutup panel
        $(document).on('keydown', e => {
            if (e.key !== 'Escape' || !st.open) return;
            if (document.body.classList.contains('swal2-shown')) return;
            if (dlgResolve) return closeDialog(false);
            if (!$camera.hasClass('um-hide')) return closeCamera();
            if (!$photoPrev.hasClass('um-hide')) return closePhotoPreview();
            if (!$attachMenu.hasClass('um-hide')) return closeAttach();
            if ($zoom.hasClass('um-zoom-open')) return closeZoom();
            if (!$menu.hasClass('um-hide')) return closeMenu();
            if (pk.open) return setPicker(false);
            if (st.editing) return cancelEdit();
            if (st.replying) return cancelReply();
            closePanel();
        });

        // klik di luar menu -> tutup menu
        $(document).on('click', e => {
            if (!$(e.target).closest('#um-menu, .um-more').length) closeMenu();
        });
        $w.on('contextmenu', 'img', e => e.preventDefault());
        $w.on('dragstart', 'img', e => e.preventDefault());
        $search.on('input', renderContacts);
        $input.on('input', () => {
            autoResize();
            updateSend();
            saveDraft();
        });
        $input.on('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey && !e.originalEvent.isComposing) {
                e.preventDefault();
                send();
                return;
            }
            // panah atas pada kolom kosong -> edit pesan teks terakhir milik sendiri
            if (e.key === 'ArrowUp' && !$input.val() && !st.editing) {
                const m = [...st.msgs].reverse().find(x => x.sender_id === ME && !x.unsent && (x.type || 'text') === 'text' && (Date.now() - Date.parse(x.created_at)) <= EDIT_MS);
                if (m) {
                    e.preventDefault();
                    startEdit(m);
                }
            }
        });
        $send.on('click', () => send());

        // ---------- Gestur sentuh: tekan lama = menu, geser kanan = balas ----------
        let lastLP = 0;
        (function() {
            const el = $msgs[0];
            let sx = 0,
                sy = 0,
                row = null,
                dx = 0,
                lp = null,
                moved = false;

            const msgOf = r => st.msgs.find(x => x.id === parseInt(r.getAttribute('data-mid'), 10));

            el.addEventListener('touchstart', e => {
                const r = e.target.closest ? e.target.closest('.um-row') : null;
                if (!r || e.touches.length !== 1) return;
                row = r;
                sx = e.touches[0].clientX;
                sy = e.touches[0].clientY;
                dx = 0;
                moved = false;
                clearTimeout(lp);
                lp = setTimeout(() => {
                    if (!row || moved) return;
                    const r2 = row,
                        m = msgOf(r2);
                    row = null;
                    if (!m) return;
                    lastLP = Date.now();
                    if (navigator.vibrate) navigator.vibrate(12);
                    openMenu(m, $(r2).find('.um-wrap').first());
                }, 480);
            }, {
                passive: true
            });

            el.addEventListener('touchmove', e => {
                if (!row) return;
                const t = e.touches[0],
                    mx = t.clientX - sx,
                    my = t.clientY - sy;
                if (Math.abs(mx) > 8 || Math.abs(my) > 8) {
                    moved = true;
                    clearTimeout(lp);
                }
                if (Math.abs(mx) > Math.abs(my) && mx > 0) {
                    dx = Math.min(mx, 80);
                    row.style.transform = 'translateX(' + dx + 'px)';
                }
            }, {
                passive: true
            });

            const end = reply => {
                clearTimeout(lp);
                if (!row) return;
                const r = row;
                r.style.transition = 'transform .2s';
                r.style.transform = '';
                setTimeout(() => {
                    r.style.transition = '';
                }, 220);
                if (reply && dx >= 64) {
                    const m = msgOf(r);
                    if (m && !m.unsent) startReply(m);
                }
                row = null;
                dx = 0;
            };
            el.addEventListener('touchend', () => end(true), {
                passive: true
            });
            el.addEventListener('touchcancel', () => end(false), {
                passive: true
            });

            // klik susulan setelah tekan-lama tidak boleh membuka link/zoom atau menutup menu
            el.addEventListener('click', e => {
                if (Date.now() - lastLP < 700) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);
        })();

        // ---------- Polling (+ dipicu sinyal realtime) ----------
        const lastMsgId = () => st.msgs.length ? st.msgs[st.msgs.length - 1].id : 0;
        const rtOn = () => st.rtConn && st.rtSub;

        let pollTimer = null,
            polling = false,
            pollAgain = false;

        function pollDelay() {
            if (document.visibilityState !== 'visible') return rtOn() ? 60000 : 20000;
            if (rtOn()) return 30000;
            return st.open ? 3000 : 8000;
        }

        function schedulePoll(ms) {
            clearTimeout(pollTimer);
            pollTimer = setTimeout(poll, ms == null ? pollDelay() : ms);
        }

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
                    st.online = new Set(res.online || []);
                    refreshPresence();

                    // 2. ada perubahan pesan (baru/edit/tarik) -> muat ulang daftar kontak
                    if (res.sig !== st.sig) {
                        const first = st.sig === null;
                        st.sig = res.sig;
                        if (!first) loadContacts();
                    }

                    if (active && st.active === active) {
                        let dirty = false,
                            incoming = 0;
                        const stick = nearBottom();

                        // 3. pesan baru di percakapan yang sedang dibuka
                        const fresh = res.messages || [];
                        if (fresh.length) {
                            fresh.forEach(m => {
                                if (!st.msgs.some(x => x.id === m.id)) {
                                    st.msgs.push(m);
                                    if (m.sender_id !== ME) incoming++;
                                    dirty = true;
                                }
                            });
                            if (incoming) playDing();
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
                        const readSet = new Set(res.read || []);
                        const now = new Date().toISOString();
                        st.msgs.forEach(m => {
                            if (m.sender_id === ME && !m.read_at && readSet.has(m.id)) {
                                m.read_at = now;
                                dirty = true;
                            }
                        });

                        if (dirty) {
                            if (incoming && !stick) st.newBelow += incoming;
                            renderMessages(stick && fresh.length ? 'bottom' : 'keep');
                        }
                    }
                })
                .always(() => {
                    polling = false;
                    schedulePoll(pollAgain ? 0 : pollDelay());
                });
        }

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                pollNow();
                syncRead();
            }
        });

        // ---------- Realtime (Laravel Echo + Pusher, channel privat) ----------
        function initRealtime() {
            try {
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
                    if (st.rtConn) pollNow();
                    else schedulePoll();
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
                    st.rtSub = false;
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
        hydrateIcons();
        applyTheme();
        applySound();
        $contacts.html(contactSkel());
        loadContacts();
        updateSend();
        initRealtime();
        poll();
    })();
</script>
@endonce