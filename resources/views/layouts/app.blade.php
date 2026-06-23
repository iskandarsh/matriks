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

    <!-- <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script> -->
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
            x-data="{ collapsed: window.innerWidth < 768 }"

            :class="collapsed ? 'w-20 px-2' : 'w-80 px-4'"
            class="fixed z-40 min-h-screen bg-white dark:bg-gray-900/60 backdrop-blur-lg border-r border-white/20 dark:border-gray-800/40 shadow-[4px_0_30px_rgba(0,0,0,0.02)] py-6 transition-all duration-300 ease-in-out overflow-visible flex flex-col justify-start relative">
            <!-- Header Logo + Collapse Button -->
            <div class="flex items-center mb-5" :class="collapsed ? 'justify-center px-0' : 'justify-between px-2'">
                <!-- Logo Text / Image -->
                <a href="{{ route('dashboard') }}" class="transition-all duration-300 transform hover:scale-[1.02]">
                    <!-- Logo Full (Terbuka) -->
                    <img x-show="!collapsed" src="{{ env('APP_URL') }}/images/Logo-Interbat.png" alt="Logo" class="h-16 w-auto mix-blend-multiply dark:mix-blend-normal object-contain" x-transition:enter="transition ease-out duration-200">
                    <!-- Logo Mini (Tertutup) -->
                    <div x-show="collapsed" class="h-10 w-10 flex items-center justify-center bg-blue-600 rounded-xl text-white font-bold text-lg shadow-md" x-transition:enter="transition ease-out duration-200 delay-100">
                        IN
                    </div>
                </a>

                <!-- Tombol untuk menutup sidebar (Kiri) -->
                <button x-show="!collapsed" @click="collapsed = true"
                    class="p-2 rounded-xl bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200/60 dark:border-gray-700/60 text-gray-500 hover:text-gray-800 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 shadow-sm transition-all duration-250">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Main Container Content (Menu & Search) -->
            <div class="flex-1 overflow-y-auto overflow-x-hidden pr-1 space-y-6 custom-scrollbar">

                <!-- App Name -->
                <div x-show="!collapsed" class="mb-5 px-2 select-none" x-transition:enter="transition ease-out duration-200">
                    <div class="space-y-1">
                        <h2 class="text-base font-bold tracking-tight bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                            Training Interbat
                        </h2>
                        <p class="text-[11px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Development & Training Management System
                        </p>
                    </div>
                    <div class="mt-4 h-px bg-gradient-to-r from-gray-200 via-gray-200/30 to-transparent dark:from-gray-700 dark:via-gray-700/30 dark:to-transparent"></div>
                </div>

                <!-- Search Box -->
                <div x-show="!collapsed" class="px-2" x-transition:enter="transition ease-out duration-200">
                    <div class="relative">
                        <input type="text" id="menuSearch" placeholder="Cari menu..."
                            class="w-full pl-9 pr-3 py-2 border rounded-xl text-xs text-gray-800 dark:text-gray-200 bg-white/40 dark:bg-gray-800/40 border-gray-200 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder-gray-400 dark:placeholder-gray-500 shadow-sm"
                            {{ $menuDisabled ? 'disabled' : '' }} />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

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
                <nav :class="collapsed ? 'space-y-3 px-1' : 'space-y-1 px-2'" class="transition-all">
                    @foreach($menus as $menu)
                    @php
                    $isActive = ($menu->id === session('active_menu_id')) || $menu->children->contains(fn($child) => $child->id === session('active_menu_id'));
                    $hasChildren = $menu->children->isNotEmpty();

                    // Logic Auto-Expand: Jika sidebar tertutup, klik menu akan membuka sidebar sekaligus dropdown
                    $menuClick = $hasChildren ? "if(collapsed) { collapsed = false; open = true; } else { open = !open; }" : menuClickScript($menu->id, $menu->route, $menuDisabled);

                    $menuHref = $menu->route ? route($menu->route) : '#';

                    $totalChildBadge = $menu->children->sum(function ($child) use ($badgeCountsmenu) {
                    return
                    ($child->id == 50 ? ($badgeCountsmenu['employeeBaru'] ?? 0) : 0) +
                    ($child->route === 'applicant' ? ($badgeCountsmenu['applicantBaru'] ?? 0) : 0) +
                    ($child->route === 'rekap_recruitment' ? ($badgeCountsmenu['rekap_recruitment'] ?? 0) : 0) +
                    ($child->route === 'recruitment_request' ? ($badgeCountsmenu['recruitment_request'] ?? 0) : 0) +
                    ($child->id == 43 ? ($badgeCountsmenu['cutiIzinPending'] ?? 0) : 0) +
                    ($child->id == 44 ? ($badgeCountsmenu['lemburPending'] ?? 0) : 0);
                    });

                    $parentBadge =
                    ($menu->route === 'applicant' ? ($badgeCountsmenu['applicantBaru'] ?? 0) : 0) +
                    ($menu->route === 'rekap_recruitment' ? ($badgeCountsmenu['rekap_recruitment'] ?? 0) : 0) +
                    ($menu->route === 'recruitment_request' ? ($badgeCountsmenu['recruitment_request'] ?? 0) : 0);

                    $totalBadge = $totalChildBadge + $parentBadge;
                    $menuInitial = strtoupper(substr(__($menu->name), 0, 1));
                    @endphp

                    <div class="menu-wrapper" x-data="{ open: @json($isActive) }">

                        <!-- Parent Nav Link -->
                        <x-nav-link href="{{ $menuHref }}"
                            @click.prevent="{{ $menuClick }}"
                            :active="$isActive"
                            title="{{ __($menu->name) }}"
                            :class="collapsed ? 'justify-center p-2' : 'justify-between px-3 py-2.5'"
                            class="parent-menu flex items-center rounded-xl text-xs font-medium tracking-wide transition-all duration-200 group relative
                    {{ $isActive 
                        ? 'bg-blue-600 text-blue-600 dark:bg-blue-500 dark:text-blue-400 font-semibold' 
                        : 'text-gray-800 dark:text-white hover:bg-blue-200/50 dark:hover:bg-gray-700/60' }} 
                    {{ $menuDisabled ? 'opacity-50 pointer-events-none' : '' }}">

                            <!-- Style saat Terbuka -->
                            <div x-show="!collapsed" class="flex items-center gap-3">
                                <span class="w-1.5 h-1.5 rounded-full transition-colors {{ $isActive ? 'bg-blue-500' : 'bg-gray-400 dark:bg-gray-500 group-hover:bg-gray-600' }}"></span>
                                <span class="truncate text-sm">{{ __($menu->name) }}</span>
                            </div>

                            <!-- Style Modern Saat Tertutup (Menampilkan Inisial Huruf) -->
                            <div x-show="collapsed" class="flex items-center justify-center w-8 h-8 rounded-lg {{ $isActive ? 'bg-blue-500 text-white shadow-md shadow-blue-500/30' : 'bg-transparent text-gray-500 dark:text-gray-400 group-hover:bg-gray-100 dark:group-hover:bg-gray-700' }} transition-all">
                                <span class="font-bold text-[13px]">{{ $menuInitial }}</span>
                            </div>

                            <!-- Indikator Badge / Arrow saat Terbuka -->
                            <div x-show="!collapsed" class="flex items-center gap-2">
                                @if($totalBadge > 0)
                                <span class="inline-flex items-center justify-center bg-red-600 text-white text-[10px] font-bold px-1.5 min-w-[18px] h-[18px] rounded-full shadow-sm">
                                    {{ $totalBadge }}
                                </span>
                                @endif

                                @if($hasChildren)
                                <svg :class="{ 'rotate-180 text-blue-500': open }"
                                    class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 transition-transform duration-200"
                                    fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                                @endif
                            </div>

                            <!-- Ping Dot Badge saat Tertutup -->
                            <span x-show="collapsed && @json($totalBadge > 0)" class="absolute -top-1 -right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-600 text-[9px] text-white font-bold items-center justify-center">{{ $totalBadge }}</span>
                            </span>
                        </x-nav-link>

                        <!-- Children Container -->
                        @if($hasChildren)
                        <div x-show="open && !collapsed" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="pl-4 mt-1 space-y-0.5 border-l-2 border-gray-100 dark:border-gray-800 ml-[15px]">
                            @foreach($menu->children as $child)
                            @php
                            $childClick = menuClickScript($child->id, $child->route, $menuDisabled);
                            $childHref = $child->route ? route($child->route) : '#';
                            $childActive = $child->id === session('active_menu_id');

                            $childBadge =
                            ($child->id == 0 ? ($badgeCountsmenu['employeeBaru'] ?? 0) : 0) +
                            ($child->route === 'applicant' ? ($badgeCountsmenu['applicantBaru'] ?? 0) : 0) +
                            ($child->route === 'rekap_recruitment' ? ($badgeCountsmenu['rekap_recruitment'] ?? 0) : 0) +
                            ($child->route === 'recruitment_request' ? ($badgeCountsmenu['recruitment_request'] ?? 0) : 0) +
                            ($child->id == 43 ? ($badgeCountsmenu['cutiIzinPending'] ?? 0) : 0) +
                            ($child->id == 44 ? ($badgeCountsmenu['lemburPending'] ?? 0) : 0);
                            @endphp

                            <x-nav-link href="{{ $childHref }}"
                                @click.prevent="{{ $childClick }}"
                                :active="$childActive"
                                class="child-menu flex items-center justify-between px-3 py-2 rounded-lg text-[11.5px] font-medium transition-all duration-150
                        {{ $childActive 
                            ? 'text-blue-600 dark:text-blue-400 font-semibold bg-blue-50/40 dark:bg-blue-950/20' 
                            : 'text-gray-800 dark:text-white hover:bg-blue-200 dark:hover:bg-gray-600' }}
                        {{ $menuDisabled ? 'opacity-50 pointer-events-none' : '' }}">

                                <span class="truncate text-sm">{{ __($child->name) }}</span>

                                @if($childBadge > 0)
                                <span class="inline-flex items-center justify-center bg-red-600 text-white text-[9px] font-bold px-1.5 min-w-[16px] h-[16px] rounded-full">
                                    {{ $childBadge }}
                                </span>
                                @endif
                            </x-nav-link>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach

                    <!-- Halaman Utama -->

                </nav>
            </div>

            <!-- TOMBOL PANAH KANAN (Melayang di luar container scroll) -->
            <div x-show="collapsed" x-transition.opacity.duration.300ms class="absolute top-1/2 -right-3.5 transform -translate-y-1/2 z-[60]">
                <button @click="collapsed = false"
                    class="p-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-blue-600 hover:text-white hover:bg-blue-600 dark:hover:bg-blue-500 shadow-[0_4px_12px_rgba(0,0,0,0.1)] hover:scale-110 transition-all duration-200 flex items-center justify-center group outline-none focus:ring-2 focus:ring-blue-500/50">
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Ghost bar -->
        <div id="sidebar-ghost" class="sidebar-ghost hidden fixed top-0 left-0 h-screen w-1 bg-transparent hover:bg-blue-500/20 dark:hover:bg-blue-400/10 transition-colors z-30 cursor-pointer" role="button" aria-label="Buka sidebar" tabindex="0">
            <div id="sidebar-ghost-line" class="h-full w-px bg-gray-200/50 dark:bg-gray-800/40 backdrop-blur-sm"></div>
        </div>
        <!-- Content Area -->
        <!-- <div class="w-full  shadow-lg rounded-lg overflow-hidden"> -->
        <div
            class="flex flex-col min-h-screen flex-1 min-w-0 shadow-lg rounded-lg"
            :class="collapsed ? 'ml-20' : 'ml-80'">
            <!-- Header -->
            <header
                class="sticky top-0 z-40
                bg-white dark:bg-slate-900/70
                backdrop-blur-2xl
                border-b border-slate-200/50 dark:border-slate-700/50
                shadow-sm">
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
                    <div class="flex items-center gap-4">
                        @php
                        $photo = Auth::user()->employee?->applicant?->appPhoto;
                        $path = public_path('appPhoto/' . $photo);
                        @endphp

                        <div class="relative group">

                            <div
                                class="
                                absolute
                                inset-0

                                rounded-2xl

                                bg-gradient-to-br
                                from-indigo-500/20
                                via-cyan-500/20
                                to-purple-500/20

                                blur-xl
                                scale-110

                                opacity-0
                                group-hover:opacity-100

                                transition-all
                                duration-500">
                            </div>

                            <img
                                src="{{ Auth::user()->employee?->applicant?->appPhoto
                                    ? env('APP_URL') . 'interbat/' . Auth::user()->employee->applicant->appPhoto
                                    : env('APP_URL') . '/images/default-avatar.png'
                                }}"
                                alt="Avatar"
                                class="
                                relative

                                w-14 h-14
                                rounded-2xl
                                object-cover

                                ring-4
                                ring-white
                                dark:ring-slate-800

                                shadow-xl">

                            <span
                                class="
                                absolute
                                bottom-0
                                right-0

                                w-4 h-4
                                rounded-full

                                bg-emerald-500
                                border-2
                                border-white
                                dark:border-slate-900
                                animate-pulse">
                            </span>

                        </div>

                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="font-bold text-slate-900 dark:text-white">
                                    Welcome, {{ Auth::user()->name }} 👋
                                </h2>

                                <span class="px-2.5 py-1 rounded-full text-xs font-medium
                        bg-emerald-100 text-emerald-700
                        dark:bg-emerald-500/20 dark:text-emerald-400">
                                    Online
                                </span>
                            </div>

                            <p class="text-sm text-slate-500 dark:text-slate-400 truncate max-w-xl">
                                {{ $quoteToday }}
                            </p>
                        </div>
                    </div>
                    <!-- Right Side: User Profile & Dropdown -->
                    {{-- RIGHT --}}
                    <div class="flex items-center gap-4">

                        {{-- Clock --}}
                        <div class="hidden md:flex flex-col text-right">
                            <span id="liveClock"
                                class="font-semibold text-slate-800 dark:text-white">
                            </span>

                            <span class="text-xs text-slate-500">
                                Asia/Jakarta
                            </span>
                        </div>

                        {{-- Profile Dropdown --}}
                        <div class="relative z-50" x-data="{ open: false }">

                            <button
                                @click="open = !open"
                                class="flex items-center gap-3
                    px-3 py-2 rounded-2xl
                    bg-white/50 dark:bg-slate-800/50
                    backdrop-blur-lg
                    border border-slate-200 dark:border-slate-700
                    hover:bg-white dark:hover:bg-slate-800
                    transition">



                                <svg
                                    class="w-4 h-4 text-slate-600 dark:text-slate-300"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>

                            </button>

                            {{-- Dropdown --}}
                            <div
                                x-show="open"
                                @click.away="open = false"
                                x-transition
                                class="absolute right-0 mt-3 w-72
                                bg-white/90 dark:bg-slate-800/90
                                backdrop-blur-xl
                                rounded-3xl
                                shadow-2xl
                                border border-slate-200 dark:border-slate-700
                                overflow-hidden">

                                <div class="p-5 border-b border-slate-200 dark:border-slate-700">

                                    <div class="flex items-center gap-3">

                                        <img
                                            src="{{ $photo
        ? env('APP_URL') . '/interbat/' . $photo
        : env('APP_URL') . '/images/default-avatar.png'
    }}"
                                            alt="Avatar"
                                            class="w-12 h-12 rounded-xl object-cover" />
                                        <div>
                                            <h4 class="font-semibold text-slate-900 dark:text-white">
                                                {{ Auth::user()->name }}
                                            </h4>

                                            <p class="text-xs text-slate-500 truncate">
                                                {{ Auth::user()->email }}
                                            </p>
                                        </div>

                                    </div>

                                </div>

                                <a href="{{ route('profile.edit') }}"
                                    class="flex items-center gap-3 px-5 py-3 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                    🔐
                                    <span>Ganti Password</span>
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <button type="submit"
                                        class="w-full text-left flex items-center gap-3 px-5 py-3
                            hover:bg-red-50 dark:hover:bg-red-900/20
                            text-red-600 transition">
                                        🚪
                                        <span>Logout</span>
                                    </button>
                                </form>

                            </div>

                        </div>
                    </div>

                </div>
            </header>


            <!-- Main Content -->
            <main class="flex-1  text-gray-800 dark:text-white">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer
                class="
                 border-slate-200/60 dark:border-slate-700/60

                bg-white
                dark:bg-slate-900/70

                backdrop-blur-xl
                ">

                <div class="px-6 py-5">

                    <div class="flex flex-col lg:flex-row items-center justify-between gap-4">

                        <!-- Left -->
                        <div class="flex flex-col sm:flex-row items-center gap-2 text-sm">

                            <span class="text-slate-500 dark:text-slate-400">
                                © {{ date('Y') }}
                            </span>

                            <span class="font-semibold text-slate-800 dark:text-white">
                                PT. Interbat
                            </span>

                            <span class="hidden sm:block text-slate-300">
                                •
                            </span>

                            <span class="text-slate-500 dark:text-slate-400">
                                Human Resource Information System
                            </span>

                        </div>

                        <!-- Center -->
                        <div
                            class="
                flex items-center gap-2
                px-3 py-1.5

                rounded-full

                bg-emerald-50
                dark:bg-emerald-500/10

                border
                border-emerald-200
                dark:border-emerald-500/20">

                            <span
                                class="
                    w-2 h-2
                    rounded-full
                    bg-emerald-500
                    animate-pulse">
                            </span>

                            <span
                                class="
                    text-xs
                    font-medium
                    text-emerald-700
                    dark:text-emerald-400">

                                System Online

                            </span>

                        </div>

                        <!-- Right -->
                        <div class="flex items-center gap-3">

                            <div
                                class="
                    px-3 py-1.5
                    rounded-full

                    bg-slate-100
                    dark:bg-slate-800

                    border
                    border-slate-200
                    dark:border-slate-700">

                                <span
                                    class="
                        text-xs
                        font-mono
                        font-semibold
                        text-slate-700
                        dark:text-slate-300">

                                    v{{ config('app.version', '1.3.2') }}

                                </span>

                            </div>

                            <span
                                class="
                    text-xs
                    tracking-[0.2em]
                    uppercase
                    text-slate-400">

                                Build IT

                            </span>

                        </div>

                    </div>

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
            $('#menuSearch').on('keyup', function() {

                let keyword = $(this).val().toLowerCase().trim();
                if (keyword === '') {

                    $('.menu-wrapper').show();
                    $('.child-menu').show();

                    $('.menu-wrapper').each(function() {

                        const data = Alpine.$data(this);

                        if (data) {
                            data.open = false;
                        }

                    });

                    return;
                }
                $('.menu-wrapper').each(function() {

                    let wrapper = $(this);

                    let parent = wrapper.find('.parent-menu').first();
                    let parentText = parent.text().toLowerCase();

                    let parentMatch = parentText.includes(keyword);

                    let childMatch = false;

                    wrapper.find('.child-menu').hide();

                    wrapper.find('.child-menu').each(function() {

                        let child = $(this);
                        let childText = child.text().toLowerCase();

                        if (childText.includes(keyword)) {
                            child.show();
                            childMatch = true;
                        }
                    });

                    if (parentMatch) {

                        wrapper.show();
                        wrapper.find('.child-menu').show();

                    } else if (childMatch) {

                        wrapper.show();

                        wrapper.attr('x-data-open', 'true');

                        const data = Alpine.$data(wrapper[0]);

                        if (data) {
                            data.open = true;
                        }

                    } else {

                        wrapper.hide();
                    }
                });

            });

            // Initialize AOS
            AOS.init();
        });
    </script>
    <script>
        function updateClock() {
            const now = new Date();

            document.getElementById('liveClock').innerHTML =
                now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
        }

        updateClock();
        setInterval(updateClock, 1000);
    </script>

</body>

</html>