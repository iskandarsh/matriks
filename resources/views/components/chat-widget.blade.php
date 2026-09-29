{{-- ============================================================
     Chat Widget Popup — Asisten AI
     Kebutuhan: jQuery, Tailwind CSS, meta csrf-token
     Endpoint : POST /interbat/chat        -> { reply }
                POST /interbat/chat/reset
     ============================================================ --}}

<style>
    /* Fallback: tetap tampil walau class Tailwind belum ter-compile */
    #chat-widget {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 50;
    }

    @media (min-width: 640px) {
        #chat-widget {
            right: 1.5rem;
            bottom: 1.5rem;
        }
    }

    #chat-toggle {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 9999px;
        border: 0;
        cursor: pointer;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, .4);
    }

    #chat-toggle svg {
        width: 1.75rem;
        height: 1.75rem;
    }

    #chat-toggle svg.hidden {
        display: none;
    }

    #chat-panel.hidden {
        display: none;
    }

    #chat-panel .cw-header {
        background: linear-gradient(90deg, #4f46e5, #7c3aed);
        color: #fff;
    }

    #chat-widget {
        --cw-brand: #4f46e5;
        --cw-brand-dark: #4338ca;
        --cw-brand-soft: #eef2ff;
    }

    /* Animasi buka panel */
    @keyframes cw-pop {
        from {
            opacity: 0;
            transform: translateY(12px) scale(.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    #chat-panel.cw-open {
        animation: cw-pop .22s cubic-bezier(.2, .8, .2, 1);
    }

    /* Animasi pesan masuk */
    @keyframes cw-msg {
        from {
            opacity: 0;
            transform: translateY(6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .cw-msg {
        animation: cw-msg .18s ease-out;
    }

    /* Indikator mengetik */
    @keyframes cw-dot {

        0%,
        80%,
        100% {
            transform: translateY(0);
            opacity: .35;
        }

        40% {
            transform: translateY(-3px);
            opacity: 1;
        }
    }

    .cw-dot {
        animation: cw-dot 1.2s infinite ease-in-out;
    }

    .cw-dot:nth-child(2) {
        animation-delay: .15s;
    }

    .cw-dot:nth-child(3) {
        animation-delay: .30s;
    }

    /* Scrollbar tipis */
    #chat-box {
        scrollbar-width: thin;
        scrollbar-color: #d1d5db transparent;
        overscroll-behavior: contain;
    }

    #chat-box::-webkit-scrollbar {
        width: 6px;
    }

    #chat-box::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 9999px;
    }

    /* Link di dalam bubble */
    .cw-bubble a {
        text-decoration: underline;
        text-underline-offset: 2px;
        word-break: break-all;
    }

    .cw-bubble code {
        background: rgba(0, 0, 0, .08);
        padding: 1px 5px;
        border-radius: 4px;
        font-size: .85em;
    }

    @media (prefers-reduced-motion: reduce) {

        #chat-panel.cw-open,
        .cw-msg,
        .cw-dot {
            animation: none !important;
        }
    }
</style>

<div id="chat-widget" data-user-name="{{ Auth::user()->name }}"
    class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 font-sans">

    {{-- ================= PANEL CHAT ================= --}}
    <div id="chat-panel" role="dialog" aria-label="Asisten AI" aria-modal="false"
        class="hidden fixed inset-0 sm:absolute sm:inset-auto sm:bottom-[4.5rem] sm:right-0
               sm:w-[24rem] sm:h-[36rem] sm:max-h-[calc(100dvh-7rem)]
               bg-white flex flex-col overflow-hidden
               sm:rounded-2xl sm:shadow-2xl sm:ring-1 sm:ring-black/5">

        {{-- Header --}}
        <header class="cw-header relative flex items-center gap-3 px-4 py-3.5 text-white">
            <div class="relative shrink-0">
                <div class="w-10 h-10 rounded-full bg-white/20 backdrop-blur flex items-center justify-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3z" />
                        <path d="M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z" />
                    </svg>
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 ring-2 ring-indigo-600"></span>
            </div>

            <div class="min-w-0 flex-1">
                <div class="font-semibold text-[15px] leading-tight">Asisten AI</div>
                <div class="text-xs text-indigo-100 truncate">Online · Halo, {{ Auth::user()->name }}</div>
            </div>

            <div class="flex items-center gap-1">
                <button id="chat-reset" type="button" title="Mulai percakapan baru" aria-label="Mulai percakapan baru"
                    class="p-2 rounded-lg text-indigo-100 hover:text-white hover:bg-white/15 transition
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 3-6.7" />
                        <path d="M3 4v5h5" />
                    </svg>
                </button>
                <button id="chat-close" type="button" title="Tutup" aria-label="Tutup chat"
                    class="p-2 rounded-lg text-indigo-100 hover:text-white hover:bg-white/15 transition
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </header>

        {{-- Area pesan --}}
        <div id="chat-box" role="log" aria-live="polite"
            class="flex-1 overflow-y-auto px-4 py-4 space-y-4 bg-slate-50"></div>

        {{-- Saran cepat --}}
        <div id="chat-suggestions" class="px-4 pb-3 bg-slate-50 flex flex-wrap gap-2"></div>

        {{-- Input --}}
        <div class="bg-white border-t border-gray-200 px-3 pt-3 pb-3"
            style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
            <div class="flex items-end gap-2 rounded-2xl border border-gray-300 bg-white pl-3.5 pr-1.5 py-1.5
                        focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20 transition">
                <textarea id="message-input" rows="1" maxlength="1000" placeholder="Ketik pesan…"
                    autocomplete="off" aria-label="Pesan"
                    class="flex-1 resize-none bg-transparent py-2 text-[15px] sm:text-sm text-gray-800
                           placeholder-gray-400 focus:outline-none max-h-[120px] leading-snug"></textarea>
                <button id="send-btn" type="button" aria-label="Kirim pesan"
                    class="shrink-0 w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center
                           hover:bg-indigo-700 active:scale-95 transition
                           disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 2L11 13" />
                        <path d="M22 2l-7 20-4-9-9-4 20-7z" />
                    </svg>
                </button>
            </div>
            <p class="mt-1.5 text-[11px] text-gray-400 text-center hidden sm:block">
                Enter untuk kirim · Shift + Enter untuk baris baru
            </p>
        </div>
    </div>

    {{-- ================= TOMBOL PEMBUKA ================= --}}
    <div class="flex justify-end">
        <button id="chat-toggle" type="button" title="Buka chat" aria-label="Buka chat" aria-expanded="false"
            class="relative w-14 h-14 rounded-full text-white shadow-lg shadow-indigo-500/30
                   bg-gradient-to-br from-indigo-600 to-violet-600 hover:scale-105 active:scale-95 transition
                   flex items-center justify-center
                   focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-300">
            <svg id="icon-chat" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.9A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <svg id="icon-down" class="w-7 h-7 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
            </svg>
            <span id="chat-badge"
                class="hidden absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-rose-500 ring-2 ring-white"></span>
        </button>
    </div>
</div>

<script>
    (function() {
        // ---------- Konfigurasi ----------
        const USER_NAME = $('#chat-widget').attr('data-user-name') || '';
        const CHAT_URL = '../interbat/chat';
        const RESET_URL = '../interbat/chat/reset';
        const SUGGESTIONS = [
            'Apa yang bisa kamu bantu?',
            'Cara menggunakan sistem ini',
            'Hubungi admin',
        ];

        // ---------- Elemen ----------
        const $panel = $('#chat-panel');
        const $box = $('#chat-box');
        const $input = $('#message-input');
        const $sendBtn = $('#send-btn');
        const $toggle = $('#chat-toggle');
        const $badge = $('#chat-badge');
        const $suggest = $('#chat-suggestions');
        const isMobile = () => window.matchMedia('(max-width: 639px)').matches;

        let busy = false;
        let lastMessage = '';

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // ---------- Util ----------
        function escapeHtml(str) {
            return str.replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }

        // Format ringan & aman: escape dulu, baru **tebal**, `kode`, dan link
        function formatText(text) {
            let html = escapeHtml(text);
            html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/`([^`\n]+)`/g, '<code>$1</code>');
            html = html.replace(/(https?:\/\/[^\s<]+)/g,
                '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
            return html;
        }

        function timeNow() {
            return new Date().toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit'
                })
                .replace('.', ':');
        }

        function scrollToBottom(smooth) {
            $box[0].scrollTo({
                top: $box[0].scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            });
        }

        function autoResize() {
            const el = $input[0];
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        }

        function updateSendState() {
            $sendBtn.prop('disabled', busy || !$input.val().trim());
        }

        // ---------- Render pesan ----------
        const botAvatar = `
            <div class="shrink-0 w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3z"/>
                </svg>
            </div>`;

        function appendBubble(text, type, opts) {
            opts = opts || {};
            const isUser = type === 'user';
            const isError = type === 'error';

            const bubbleClass = isUser ?
                'bg-indigo-600 text-white rounded-2xl rounded-br-md' :
                isError ?
                'bg-red-50 text-red-700 border border-red-200 rounded-2xl rounded-bl-md' :
                'bg-white text-gray-800 border border-gray-200 shadow-sm rounded-2xl rounded-bl-md';

            const $bubble = $('<div>')
                .addClass('cw-bubble px-3.5 py-2.5 text-[15px] sm:text-sm leading-relaxed whitespace-pre-wrap break-words ' + bubbleClass)
                .html(formatText(text));

            const $meta = $('<div>')
                .addClass('mt-1 text-[11px] text-gray-400 ' + (isUser ? 'text-right' : 'text-left'))
                .text(opts.noTime ? '' : timeNow());

            const $col = $('<div>')
                .addClass('flex flex-col min-w-0 max-w-[82%]')
                .append($bubble, $meta);

            if (isError && opts.retry) {
                const $retry = $('<button type="button">')
                    .addClass('mt-1.5 self-start text-xs font-medium text-indigo-600 hover:text-indigo-800 underline underline-offset-2')
                    .text('Coba lagi')
                    .on('click', function() {
                        $row.remove();
                        sendMessage(lastMessage, true);
                    });
                $col.append($retry);
            }

            const $row = $('<div>')
                .addClass('cw-msg flex items-end gap-2 ' + (isUser ? 'justify-end' : 'justify-start'));

            if (!isUser) $row.append(botAvatar);
            $row.append($col);

            $box.append($row);
            scrollToBottom(true);
            return $row;
        }

        function appendTyping() {
            const $row = $('<div>').addClass('cw-msg flex items-end gap-2 justify-start').html(`
                ${botAvatar}
                <div class="bg-white border border-gray-200 shadow-sm rounded-2xl rounded-bl-md px-4 py-3.5 flex gap-1.5"
                     aria-label="Asisten sedang mengetik">
                    <span class="cw-dot w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                    <span class="cw-dot w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                    <span class="cw-dot w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                </div>`);
            $box.append($row);
            scrollToBottom(true);
            return $row;
        }

        function renderGreeting() {
            $box.empty();
            appendBubble('Halo ' + USER_NAME + '! Ada yang bisa saya bantu hari ini?', 'bot');
            renderSuggestions();
        }

        function renderSuggestions() {
            $suggest.empty().removeClass('hidden');
            SUGGESTIONS.forEach(function(s) {
                $('<button type="button">')
                    .addClass('px-3 py-1.5 rounded-full text-xs font-medium text-indigo-700 bg-white border border-indigo-200 ' +
                        'hover:bg-indigo-50 hover:border-indigo-300 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500')
                    .text(s)
                    .on('click', function() {
                        sendMessage(s);
                    })
                    .appendTo($suggest);
            });
        }

        // ---------- Buka / tutup ----------
        function openPanel() {
            $panel.removeClass('hidden').addClass('cw-open');
            $toggle.attr('aria-expanded', 'true').attr('title', 'Tutup chat');
            $('#icon-chat').addClass('hidden');
            $('#icon-down').removeClass('hidden');
            $badge.addClass('hidden');
            if (isMobile()) $toggle.addClass('hidden');
            document.body.classList.toggle('overflow-hidden', isMobile());
            scrollToBottom(false);
            setTimeout(() => $input.trigger('focus'), 50);
        }

        function closePanel() {
            $panel.addClass('hidden').removeClass('cw-open');
            $toggle.removeClass('hidden').attr('aria-expanded', 'false').attr('title', 'Buka chat');
            $('#icon-chat').removeClass('hidden');
            $('#icon-down').addClass('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        $toggle.on('click', function() {
            $panel.hasClass('hidden') ? openPanel() : closePanel();
        });
        $('#chat-close').on('click', closePanel);
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && !$panel.hasClass('hidden')) {
                closePanel();
                $toggle.trigger('focus');
            }
        });

        // ---------- Input ----------
        $input.on('input', function() {
            autoResize();
            updateSendState();
        });
        $input.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey && !e.originalEvent.isComposing) {
                e.preventDefault();
                sendMessage();
            }
        });
        $sendBtn.on('click', function() {
            sendMessage();
        });

        // ---------- Reset ----------
        $('#chat-reset').on('click', function() {
            if (busy) return;
            $.post(RESET_URL).always(function() {
                renderGreeting();
                $input.val('').trigger('input').trigger('focus');
            });
        });

        // ---------- Kirim pesan ----------
        function sendMessage(text, isRetry) {
            if (busy) return;
            const message = (text !== undefined ? text : $input.val()).trim();
            if (!message) return;

            lastMessage = message;
            busy = true;
            $suggest.addClass('hidden').empty();

            if (!isRetry) appendBubble(message, 'user');
            if (text === undefined) {
                $input.val('');
                autoResize();
            }
            $input.prop('disabled', true);
            updateSendState();

            const $typing = appendTyping();

            $.post(CHAT_URL, {
                    message: message
                })
                .done(function(res) {
                    $typing.remove();
                    appendBubble(res.reply, 'bot');
                    if ($panel.hasClass('hidden')) $badge.removeClass('hidden');
                })
                .fail(function(xhr) {
                    $typing.remove();
                    let msg = xhr.responseJSON && xhr.responseJSON.reply;
                    if (!msg) {
                        msg = xhr.status === 419 ?
                            'Sesi berakhir. Muat ulang halaman lalu coba lagi.' :
                            xhr.status === 429 ?
                            'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.' :
                            'Gagal terhubung ke server. Periksa koneksi Anda.';
                    }
                    appendBubble(msg, 'error', {
                        retry: xhr.status !== 419,
                        noTime: true
                    });
                })
                .always(function() {
                    busy = false;
                    $input.prop('disabled', false);
                    updateSendState();
                    if (!$panel.hasClass('hidden')) $input.trigger('focus');
                });
        }

        // ---------- Init ----------
        renderGreeting();
        updateSendState();
    })();
</script>