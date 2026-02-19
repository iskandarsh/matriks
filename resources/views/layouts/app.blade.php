<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ env('APP_URL') }}/images/favicon.ico">

    <!-- Fonts -->
    <!-- <link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" /> -->

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Include jQuery -->
    <script src="{{ env('APP_URL') }}/assets/js/jquery-3.6.0.min.js"></script>
    <script src="{{ env('APP_URL') }}/assets/js/html2pdf.bundle.min.js"></script>

    <!-- Cropper CSS -->
    <link href="{{ env('APP_URL') }}/assets/css/cropper.min.css" rel="stylesheet" />

    <!-- Cropper JS -->
    <script src="{{ env('APP_URL') }}/assets/js/cropper.min.js"></script>


    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/fontawesome.min.css">

    <script src="{{ env('APP_URL') }}/assets/js/jquery.dataTables.min.js"></script>

    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/select2bootstrap.min.css">
    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/select2rtlbootstrap.min.css">

    <script src="{{ env('APP_URL') }}/assets/js/select2.min.js"></script>

    <script src="{{ env('APP_URL') }}/assets/js/sweetalert2@11.js"></script>

    <link id="dx-theme-light" rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/dx.material.blue.light.css" disabled>
    <link id="dx-theme-dark" rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/dx.material.blue.dark.css" disabled>

    <!-- CSS -->
    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/summernote-lite.min.css">
    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/aos.css">

    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/bootstrap-clockpicker.min.css">

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/flatpickr.min.css">


</head>

<style>
    .menu-disabled {
        pointer-events: none;
        /* tidak bisa diklik */
        cursor: not-allowed !important;
        opacity: 0.5;
        /* warnanya pudar */
    }
</style>

<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">

    <div class="min-h-screen flex">

        <!-- Sidebar (utama) - ubah id supaya tidak duplikat -->
        @php
        $user = Auth::user();

        // Cek last password change > 6 bulan atau belum pernah ganti password
        $mustChangePassword = !$user->last_password_change ||
        \Carbon\Carbon::parse($user->last_password_change)->lt(now()->subMonths(6));

        // Menu disabled jika: belum ganti password ATAU lebih dari 6 bulan
        $menuDisabled = $mustChangePassword;
        @endphp

        <aside
            x-data="{ collapsed: false }"
            :class="collapsed ? 'w-10 px-0' : 'w-80 px-4'"
            class="bg-yellow-200 dark:bg-gray-800 shadow-md py-6 fixed z-40 min-h-screen transition-all duration-300 ease-in-out overflow-hidden flex flex-col justify-start relative">


            <!-- Logo + Collapse Button -->
            <div class="flex items-center justify-between" x-show="!collapsed">
                <a href="{{ route('dashboard') }}" class="transition-all duration-300">
                    <img src="{{ env('APP_URL') }}/images/Logo-Interbat.png" alt="Logo" class="h-20 w-auto">

                </a>

                <!-- Tombol collapse sidebar -->
                <button @click="collapsed = true"
                    class="p-2 rounded-lg border border-gray-300 dark:border-gray-600
                        bg-gray-200 dark:bg-gray-700
                        hover:bg-gray-300 dark:hover:bg-gray-600
                        transition-all duration-300 shadow-sm">
                    <svg class="w-4 h-4 text-gray-700 dark:text-gray-200 transition-transform"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

            </div>
            <div class="flex-1 overflow-auto">

                <!-- App Name -->
                <div x-show="!collapsed" class="mb-5 select-none">

                    <!-- Wrapper -->
                    <div class="space-y-1">
                        <h2 class="text-[16px] font-semibold tracking-tight 
        text-gray-900/90 dark:text-white/90">
                            Matriks Competency
                        </h2>

                        <p class="text-[11px] leading-snug 
        text-gray-500/80 dark:text-gray-400/70">
                            Development & Trainig Management System
                        </p>
                    </div>

                    <!-- Modern Divider -->
                    <div class="mt-4 h-px 
        bg-gradient-to-r from-gray-300/60 via-gray-300/20 to-transparent
        dark:from-gray-600/40 dark:via-gray-600/20 dark:to-transparent">
                    </div>
                </div>


                <!-- Search Box -->
                <input x-show="!collapsed" type="text" id="menuSearch" placeholder="Cari menu..."
                    class="w-full px-3 py-2 mb-4 border rounded-md text-sm text-gray-800 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    {{ $menuDisabled ? 'disabled' : '' }} />

                @php
                function menuClickScript($menuId, $route = null, $menuDisabled = false) {
                if ($menuDisabled) return '';
                $url = route('menu.set-active');
                $csrf = csrf_token();
                $fetch = "fetch('{$url}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{$csrf}'
                },
                body: JSON.stringify({ menu_id: {$menuId} })
                }).then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
                })";
                if ($route) {
                return $fetch . ".then(() => window.location.href = '" . route($route) . "').catch(err => console.error(err));";
                } else {
                return $fetch . ".then(() => window.location.reload()).catch(err => console.error(err));";
                }
                }
                @endphp

                <!-- Menu Navigation -->
                <nav x-show="!collapsed" class="space-y-2">
                    @foreach($menus as $menu)
                    @php
                    $currentRoute = request()->route()?->getName(); // contoh: training-materials.questions
                    $isActive = false;

                    // --- Aktif berdasarkan session ---
                    if ($menu->id === session('active_menu_id')) {
                    $isActive = true;
                    }

                    // --- Aktif jika child sedang active via session ---
                    if ($menu->children->contains(fn($child) => $child->id === session('active_menu_id'))) {
                    $isActive = true;
                    }

                    // --- Aktif berdasarkan prefix route parent ---
                    if ($menu->route) {
                    $prefix = \Illuminate\Support\Str::before($menu->route, '.'); // contoh: training-materials
                    if ($currentRoute && \Illuminate\Support\Str::startsWith($currentRoute, $prefix . '.')) {
                    $isActive = true;
                    }
                    }

                    // --- Cek child prefix ---
                    foreach ($menu->children as $child) {
                    if ($child->route) {
                    $childPrefix = \Illuminate\Support\Str::before($child->route, '.');

                    if ($currentRoute && \Illuminate\Support\Str::startsWith($currentRoute, $childPrefix . '.')) {
                    $isActive = true;
                    break;
                    }
                    }
                    }
                    $hasChildren = $menu->children->isNotEmpty();
                    $menuClick = $hasChildren ? "open = !open" : menuClickScript($menu->id, $menu->route, $menuDisabled);
                    $menuHref = $menu->route ? route($menu->route) : '#';

                    // === HITUNG TOTAL BADGE PARENT ===
                    $totalChildBadge = 0;

                    $parentBadge = 0;

                    // === TOTAL BADGE FINAL ===
                    $totalBadge = $totalChildBadge + $parentBadge;
                    @endphp

                    <div class="menu-wrapper" x-data="{ open: @json($isActive) }">

                        <x-nav-link href="{{ $menuHref }}"
                            @click.prevent="{{ $menuClick }}"
                            :active="$isActive"
                            class="flex items-center justify-between text-gray-800 dark:text-white hover:bg-blue-200 dark:hover:bg-gray-600 {{ $menuDisabled ? 'menu-disabled' : '' }}">

                            <span>{{ __($menu->name) }}</span>

                            <div class="flex items-center gap-2">
                                {{-- Badge Parent --}}
                                @if($totalBadge > 0)
                                <span class="inline-block bg-red-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full">
                                    {{ $totalBadge }}
                                </span>
                                @endif

                                {{-- Arrow --}}
                                @if($hasChildren)
                                <svg :class="{ 'rotate-180': open }"
                                    class="w-4 h-4 transition-transform duration-200 transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                                @endif
                            </div>
                        </x-nav-link>

                        @if($hasChildren)
                        <div x-show="open" x-transition class="space-y-1 ml-4 mt-2">
                            @foreach($menu->children as $child)
                            @php
                            $childClick = menuClickScript($child->id, $child->route, $menuDisabled);
                            $childHref = $child->route ? route($child->route) : '#';
                            $childActive =
                            ($child->id === session('active_menu_id'))
                            ||
                            (
                            $child->route
                            && request()->routeIs(\Illuminate\Support\Str::before($child->route, '.') . '*')
                            );


                            $childBadge = 0;
                            @endphp

                            <x-nav-link href="{{ $childHref }}"
                                @click.prevent="{{ $childClick }}"
                                :active="$childActive"
                                class="flex items-center justify-between text-gray-800 dark:text-white hover:bg-blue-200 dark:hover:bg-gray-600 {{ $menuDisabled ? 'menu-disabled' : '' }}">

                                <span>{{ __($child->name) }}</span>

                                @if($childBadge > 0)
                                <span class="inline-block bg-red-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full">
                                    {{ $childBadge }}
                                </span>
                                @endif

                            </x-nav-link>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </nav>

                <!-- Tombol panah tengah saat collapsed -->
                <div x-show="collapsed" class="absolute top-1/2 -right-4 transform -translate-y-1/2 w-16 flex justify-center">
                    <button @click="collapsed = false"
                        class="p-2 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>


        <!-- Ghost bar (letakkan tepat setelah main-sidebar) -->
        <div id="sidebar-ghost" class="sidebar-ghost hidden" role="button" aria-label="Buka sidebar" tabindex="0">
            <div id="sidebar-ghost-line"></div>
        </div>

        <!-- Content Area -->
        <div class="w-full  shadow-lg rounded-lg overflow-hidden">
            <!-- Header -->
            <header class="bg-yellow-200 dark:bg-gray-800 shadow">
                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">


                    @php
                    $quotes = [
                    "Stay positive, work hard, make it happen.",
                    "Every day is a new beginning.",
                    "Push yourself, because no one else is going to do it for you.",
                    "Success is not for the lazy.",
                    "Keep going, you're getting there.",
                    "Don't stop until you're proud.",
                    "Be stronger than your excuses.",
                    "Believe in yourself and all that you are.",
                    "Your only limit is your mind.",
                    "Do something today that your future self will thank you for.",
                    "Progress, not perfection.",
                    "Great things never come from comfort zones.",
                    "Dream big. Work hard. Stay focused.",
                    "Small steps every day.",
                    "Discipline is doing it even when you don’t feel like it.",
                    "Make each day your masterpiece.",
                    "Success doesn’t come to you. You go to it.",
                    "If not now, when?",
                    "Work in silence, let success make the noise.",
                    "Wake up with determination, go to bed with satisfaction.",
                    ];

                    $dayOfYear = date('z'); // 0 - 365
                    srand($dayOfYear);
                    shuffle($quotes);
                    $quoteToday = $quotes[0];
                    srand();
                    @endphp


                    <!-- LEFT: Welcome + Quote -->
                    <div class="flex items-center space-x-4">
                        <img src="{{ Auth::user()->employee?->applicant?->appPhoto
                                ? env('APP_URL') . 'interbat/' . Auth::user()->employee->applicant->appPhoto
                                : env('APP_URL') . '/images/default-avatar.png'
                            }}"
                            alt="Avatar"
                            class="w-11 h-11 rounded-full object-cover border border-gray-300 dark:border-gray-600 shadow-sm">

                        <div class="leading-tight">
                            <p class="text-gray-900 dark:text-white font-semibold">Welcome, {{ Auth::user()->name }} 👋</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $quoteToday }}</p>
                        </div>
                    </div>


                    <!-- Right Side: User Profile & Dropdown -->
                    <div class="relative z-50" x-data="{ open: false }">
                        <!-- Avatar Button -->
                        <button @click="open = !open" class="flex items-center gap-2 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <!-- <img src="{{ Auth::user()->employee?->applicant?->appPhoto 
                                ? asset('' . Auth::user()->employee->applicant->appPhoto)
                                : asset('images/default-avatar.png') }}"
                                alt="Avatar"
                                class="w-8 h-8 rounded-full object-cover border border-gray-300 dark:border-gray-600 shadow-sm"> -->
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="open" @click.away="open = false"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-3 w-52 bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700">

                            <div class="px-4 py-3 border-b dark:border-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-300">Signed in as</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ Auth::user()->email }}
                                </p>
                            </div>

                            <a href="{{ route('profile.edit') }}"
                                class="block px-4 py-3 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                Ganti Password
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="block w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-white hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>


                </div>
            </header>



            <!-- Main Content -->
            <main class="p-2 text-gray-800 dark:text-white">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="bg-white dark:bg-gray-800 shadow p-2 z-10  text-sm text-gray-600 dark:text-gray-300 fixed bottom-0 w-full">
                &copy; {{ date('Y') }} PT. Interbat. All rights reserved.
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Version {{ config('app.version', '1.1') }} • Build IT
                </div>
            </footer>
        </div>
    </div>



    <!-- Flowise embed (tetap) -->
    <script type="module">
        import Chatbot from "https://cdn.jsdelivr.net/npm/flowise-embed/dist/web.js"
        Chatbot.init({
            chatflowid: "926a4038-c109-45f5-88c0-975c67c9963e",
            apiHost: "http://10.10.10.26:3000",
            chatflowConfig: {
                // topK: 2
            },
            observersConfig: {
                observeUserInput: (userInput) => {
                    console.log({
                        userInput
                    });
                },
                observeMessages: (messages) => {
                    console.log({
                        messages
                    });
                },
                observeLoading: (loading) => {
                    console.log({
                        loading
                    });
                },
            },
            theme: {
                button: {
                    backgroundColor: '#3B81F6',
                    right: 20,
                    bottom: 20,
                    size: 48,
                    dragAndDrop: true,
                    iconColor: 'white',
                    customIconSrc: 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/svg/google-messages.svg',
                    autoWindowOpen: {
                        autoOpen: false,
                        openDelay: 2,
                        autoOpenOnMobile: false,
                    },
                },
                disclaimer: {
                    title: 'Disclaimer',
                    message: 'By using this chatbot, you agree to the <a target="_blank" href="https://flowiseai.com/terms">Terms & Condition</a>',
                    textColor: 'black',
                    buttonColor: '#3b82f6',
                    buttonText: 'Start Chatting',
                    buttonTextColor: 'white',
                    blurredBackgroundColor: 'rgba(0, 0, 0, 0.4)',
                    backgroundColor: 'white',
                    denyButtonText: 'Cancel',
                    denyButtonBgColor: '#ef4444',
                },
                form: {
                    backgroundColor: 'white',
                    textColor: 'black',
                },
                chatWindow: {
                    showTitle: true,
                    showAgentMessages: true,
                    title: 'InterBot',
                    titleAvatarSrc: 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/svg/google-messages.svg',
                    titleBackgroundColor: '#3B81F6',
                    titleTextColor: '#ffffff',
                    welcomeMessage: 'Hello! Ada yang bisa dibantu?',
                    errorMessage: 'Server belum aktif',
                    backgroundColor: '#ffffff',
                    backgroundImage: 'enter image path or link',
                    height: 700,
                    width: 400,
                    fontSize: 16,
                    starterPrompts: ['cara penggunaan aplikasi?', 'Kontak It?', 'Pengembang?'],
                    starterPromptFontSize: 15,
                    clearChatOnReload: false,
                    sourceDocsTitle: 'Sources:',
                    renderHTML: true,
                    botMessage: {
                        backgroundColor: '#f7f8ff',
                        textColor: '#303235',
                        showAvatar: true,
                        avatarSrc: 'https://cdn-icons-png.flaticon.com/512/4203/4203951.png',
                    },
                    userMessage: {
                        backgroundColor: '#3B81F6',
                        textColor: '#ffffff',
                        showAvatar: true,
                        avatarSrc: 'https://raw.githubusercontent.com/zahidkhawaja/langchain-chat-nextjs/main/public/usericon.png',
                    },
                    textInput: {
                        placeholder: 'Type your question',
                        backgroundColor: '#ffffff',
                        textColor: '#303235',
                        sendButtonColor: '#3B81F6',
                        maxChars: 50,
                        maxCharsWarningMessage: 'You exceeded the characters limit. Please input less than 50 characters.',
                        autoFocus: false,
                        sendMessageSound: true,
                        receiveMessageSound: true,
                    },
                    feedback: {
                        color: '#303235',
                    },
                    dateTimeToggle: {
                        date: true,
                        time: true,
                    },
                    footer: {
                        textColor: '#303235',
                        text: 'Powered by',
                        company: 'Interbat',
                        companyLink: 'https://www.interbat.co.id/',
                    },
                },
            },
        });
    </script>

    <!-- ClockPicker JS -->
    <script src="{{ env('APP_URL') }}/assets/js/bootstrap-clockpicker.min.js"></script>

    <!-- jsPDF & AutoTable -->
    <script src="{{ env('APP_URL') }}/assets/js/jspdf.umd.min.js"></script>
    <script src="{{ env('APP_URL') }}/assets/js/jspdf.plugin.autotable.min.js"></script>

    <!-- XLSX, JSZip, FileSaver -->
    <script src="{{ env('APP_URL') }}/assets/js/xlsx.full.min.js"></script>
    <script src="{{ env('APP_URL') }}/assets/js/jszip.min.js"></script>
    <script src="{{ env('APP_URL') }}/assets/js/FileSaver.min.js"></script>

    <script src="{{ env('APP_URL') }}/assets/js/exceljs.min.js"></script>
    <!-- Flatpickr -->
    <script src="{{ env('APP_URL') }}/assets/js/flatpickr.min.js"></script>
    <script src="{{ env('APP_URL') }}/assets/js/monthSelect.min.js"></script>
    <!-- Flatpickr MonthSelect Plugin (belum ada file lokal — tetap CDN) -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script> -->

    <!-- Summernote -->
    <script src="{{ env('APP_URL') }}/assets/js/summernote-lite.min.js"></script>

    <!-- DevExtreme -->
    <script src="{{ env('APP_URL') }}/assets/js/dx.all.js"></script>

    <!-- AOS -->
    <script src="{{ env('APP_URL') }}/assets/js/aos.js"></script>

    <script>
        $(document).ready(function() {
            // const sidebar = $("#main-sidebar");
            // const collapseBtn = $("#collapseSidebarBtn");
            // const collapseIcon = $("#collapseIcon");
            // const collapseText = $("#collapseText");
            // const ghost = $("#sidebar-ghost");

            // function collapseSidebar() {
            //     sidebar.toggleClass("w-72 w-12"); // width change
            //     sidebar.toggleClass("overflow-hidden");
            //     collapseIcon.toggleClass("rotate-180");
            //     collapseText.text(sidebar.hasClass("w-16") ? "Buka" : "Tutup");

            //     // Ghost bar muncul saat collapsed
            //     ghost.toggleClass("hidden", !sidebar.hasClass("w-16"));
            // }

            // // Desktop collapse button
            // collapseBtn.on("click", collapseSidebar);

            // // Ghost bar click (desktop)
            // ghost.on("click", collapseSidebar);





            // Toggle user dropdown
            $("#user-menu").click(function(e) {
                e.stopPropagation();
                $("#dropdown-menu").toggleClass("hidden");
            });

            // Hide dropdown when clicked outside
            $(document).click(function(e) {
                if (!$(e.target).closest('#user-menu').length) {
                    $("#dropdown-menu").addClass("hidden");
                }
            });

            // Menu search
            $('#menuSearch').on('input', function() {
                const query = $(this).val().toLowerCase();
                $(".menu-wrapper").each(function() {
                    let text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(query));
                });
            });

            // Initialize AOS
            AOS.init();
        });
    </script>


</body>

</html>