<x-app-layout>


    <!-- Modal -->
    <div id="modal-kontrak" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-auto p-8 relative ring-1 ring-gray-300 dark:ring-gray-700">
            <!-- Close Button -->
            <button id="close-modal" aria-label="Close modal" class="absolute top-4 right-5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors text-3xl font-extrabold focus:outline-none">
                &times;
            </button>

            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100"
                data-id="📋 Daftar Kontrak Akan Habis / Lewat"
                data-en="📋 List of Contracts Expiring / Expired">📋 Daftar Kontrak Akan Habis / Lewat</h2>

            <!-- Loading Spinner -->
            <div id="loading-spinner" class="flex justify-center items-center py-16">
                <svg class="animate-spin h-12 w-12 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </div>

            <!-- Table -->
            <table id="table-kontrak" class="min-w-full table-auto border-collapse rounded-lg overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700 hidden">
                <thead>
                    <tr class="bg-yellow-100 dark:bg-yellow-700">
                        <th class="border border-yellow-300 dark:border-yellow-600 px-6 py-3 text-left text-yellow-900 dark:text-yellow-100 font-semibold"
                            data-id="Nama" data-en="Name">Nama</th>
                        <th class="border border-yellow-300 dark:border-yellow-600 px-6 py-3 text-left text-yellow-900 dark:text-yellow-100 font-semibold"
                            data-id="Posisi" data-en="Position">Posisi</th>
                        <th class="border border-yellow-300 dark:border-yellow-600 px-6 py-3 text-left text-yellow-900 dark:text-yellow-100 font-semibold"
                            data-id="Departemen" data-en="Department">Departemen</th>
                        <th class="border border-yellow-300 dark:border-yellow-600 px-6 py-3 text-left text-yellow-900 dark:text-yellow-100 font-semibold"
                            data-id="Akhir Kontrak" data-en="Contract End">Akhir Kontrak</th>
                        <th class="border border-yellow-300 dark:border-yellow-600 px-6 py-3 text-left text-yellow-900 dark:text-yellow-100 font-semibold"
                            data-id="Status" data-en="Status">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"></tbody>
            </table>
        </div>
    </div>


    <div class="pt-16 bg-gray-50 dark:bg-gray-900 ">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

            {{-- Welcome Message --}}
            <div data-intro="Ini adalah pesan selamat datang untuk pengguna." data-step="1"
                data-aos="fade-right" data-aos-duration="1000"
                class="bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 p-6 rounded-3xl shadow-2xl text-white flex items-center justify-between gap-6
             transition-transform duration-300 ease-in-out hover:scale-105 hover:shadow-2xl hover:brightness-110">

                {{-- Kiri: Foto Profile --}}
                <div
                    class="flex-shrink-0 rounded-full overflow-hidden border-4 border-white shadow-lg ring-2 ring-indigo-400
               transition-transform duration-500 ease-in-out hover:scale-110 hover:shadow-xl hover:rotate-[10deg]">
                    @php

                    $photoBaseUrl = 'http://10.1.4.100/interbat';

                    $employeePhoto = Auth::user()->employee?->photo_path;
                    $applicantPhoto = Auth::user()->employee?->applicant?->appPhoto;

                    if ($employeePhoto) {
                    $photoUrl = $photoBaseUrl . '/storage/' . $employeePhoto;
                    } elseif ($applicantPhoto) {
                    $photoUrl = $photoBaseUrl . '/' . $applicantPhoto;
                    } else {
                    $photoUrl = asset('images/default-avatar.png'); // default avatar tetap ambil dari project training
                    }
                    @endphp

                    <img src="{{ $photoUrl 
                ? $photoUrl
                : asset('images/default-avatar.png') }}"
                        alt="Foto Profil" class="object-cover bg-white w-20 h-28 sm:w-24 sm:h-32 mx-auto" />
                </div>

                {{-- Tengah: Teks --}}
                <div class="flex-1 transition-colors duration-300 ease-in-out hover:text-indigo-200">
                    <p class="text-2xl font-semibold tracking-wide transition-transform duration-300 ease-in-out hover:translate-x-2">
                        <span data-id="Hai," data-en="Hi,">Hi,</span> {{ Auth::user()->name }} 👋
                    </p>
                    <p class="text-base opacity-90 mt-1 transition-opacity duration-300 ease-in-out hover:opacity-100"
                        data-id="Selamat datang kembali! Semangat kerja ya 💪"
                        data-en="Welcome back! Keep up the great work 💪">
                        Selamat datang kembali! Semangat kerja ya 💪
                    </p>

                    {{-- Total Cuti --}}
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div class="bg-white/20 px-4 py-2 rounded-xl shadow-md backdrop-blur-md text-center">
                            🗓️ <span class="font-semibold" data-id="Cuti Tahunan" data-en="Annual Leave">Cuti Tahunan</span><br>
                            <span id="cuti-tahunan" class="font-bold">0/0 Hari</span>
                        </div>

                        <!-- <div class="bg-white/20 px-4 py-2 rounded-xl shadow-md backdrop-blur-md text-center">
                            🤒 <span class="font-semibold">Cuti Sakit</span><br>
                            <span class="font-bold">6 Hari</span>
                        </div>
                        <div class="bg-white/20 px-4 py-2 rounded-xl shadow-md backdrop-blur-md text-center">
                            👶 <span class="font-semibold">Cuti Melahirkan</span><br>
                            <span class="font-bold">90 Hari</span>
                        </div> -->

                    </div>
                </div>


                {{-- Kanan: Icon Jam --}}
                <div class="hidden sm:block opacity-40 transition-opacity duration-300 ease-in-out hover:opacity-70 hover:scale-110">
                    <svg class="w-14 h-14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </div>
            </div>

            {{-- Modern Info Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Position --}}
                <div data-intro="Menampilkan jabatan kamu di perusahaan." data-step="2" data-aos="zoom-in" class="group relative overflow-hidden
                bg-white dark:bg-slate-800
                rounded-3xl
                border border-slate-200 dark:border-slate-700
                p-6
                shadow-sm
                hover:shadow-xl
                hover:-translate-y-1
                transition-all duration-300">

                    <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl"></div>

                    <div class="relative z-10 flex items-start justify-between">

                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider"
                                data-id="Jabatan" data-en="Position">
                                Jabatan
                            </p>

                            <h3 class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">
                                {{ Auth::user()->position->posiNama ?? '-' }}
                            </h3>
                        </div>

                        <div
                            class="w-14 h-14 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
                            <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                            </svg>
                        </div>

                    </div>
                </div>

                {{-- Department --}}
                <div data-aos="zoom-in" data-aos-delay="150" class="group relative overflow-hidden
                bg-white dark:bg-slate-800
                rounded-3xl
                border border-slate-200 dark:border-slate-700
                p-6
                shadow-sm
                hover:shadow-xl
                hover:-translate-y-1
                transition-all duration-300">

                    <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl"></div>

                    <div class="relative z-10 flex items-start justify-between">

                        <div>

                            <p class="text-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider"
                                data-id="Departemen" data-en="Department">
                                Departemen
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">

                                @if(Auth::user()->employee?->department)
                                <span
                                    class="px-3 py-1 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 text-sm font-medium">
                                    {{ Auth::user()->employee->department->depNama }}
                                </span>
                                @endif

                                @if(Auth::user()->employee?->workunit)
                                <span
                                    class="px-3 py-1 rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 text-sm font-medium">
                                    {{ Auth::user()->employee->workunit->woruNama }}
                                </span>
                                @endif

                            </div>

                        </div>

                        <div
                            class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                            <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 21h18M5 21V7l8-4 8 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01" />
                            </svg>
                        </div>

                    </div>
                </div>

                {{-- Last Login --}}
                <div data-aos="zoom-in" data-aos-delay="300" class="group relative overflow-hidden
                bg-white dark:bg-slate-800
                rounded-3xl
                border border-slate-200 dark:border-slate-700
                p-6
                shadow-sm
                hover:shadow-xl
                hover:-translate-y-1
                transition-all duration-300">

                    <div class="absolute top-0 right-0 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl"></div>

                    <div class="relative z-10 flex items-start justify-between">

                        <div>

                            <p class="text-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider"
                                data-id="Login Terakhir" data-en="Last Login">
                                Login Terakhir
                            </p>

                            <h3 class="mt-3 text-xl font-bold text-slate-900 dark:text-white">
                                {{ Auth::user()->last_login_at ?
                            \Carbon\Carbon::parse(Auth::user()->last_login_at)->translatedFormat('d M Y') : '-' }}
                            </h3>

                            <p class="text-sm text-slate-500 mt-1">
                                {{ Auth::user()->last_login_at ?
                            \Carbon\Carbon::parse(Auth::user()->last_login_at)->translatedFormat('H:i') : '' }}
                            </p>

                        </div>

                        <div
                            class="w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                            <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2" />
                            </svg>
                        </div>

                    </div>
                </div>

            </div>

            {{-- Akses Cepat --}}
            <div data-intro="Tombol cepat untuk akses." data-step="3" data-aos="fade-up" data-aos-duration="800"
                class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    ⚡ <span data-id="Akses Cepat" data-en="Quick Access">Akses Cepat</span>
                </h3>
                <div class="flex flex-wrap gap-4">

                    <a href="{{ route('profile.edit') }}"
                        class="inline-flex items-center gap-2 bg-indigo-600 text-white font-semibold px-5 py-2 rounded-full
                   shadow-md hover:bg-indigo-500 hover:text-indigo-100 transition duration-300 ease-in-out
                   focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1 active:scale-95">
                        ✏️ <span data-id="Edit Profil" data-en="Edit Profile">Edit Profil</span>
                    </a>




                    <button id="btn-quotes"
                        class="inline-flex items-center gap-2 bg-purple-600 text-white font-semibold px-5 py-2 rounded-full
                            shadow-md hover:bg-purple-500 hover:text-purple-100 transition duration-300 ease-in-out
                            focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-1 active:scale-95">
                        ✨ <span data-id="Kutipan Hari Ini" data-en="Quote of the Day">Kutipan Hari Ini</span>
                    </button>

                    <!-- <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="inline-flex items-center gap-2 bg-red-600 text-white font-semibold px-5 py-2 rounded-full
                        shadow-md hover:bg-red-500 hover:text-red-100 transition duration-300 ease-in-out
                        focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1 active:scale-95">
                        🔒 Logout
                    </a> -->


                    {{-- Sisa Cuti All Departemen --}}
                    <button
                        type="button"
                        class="flex items-center gap-2 bg-yellow-200 backdrop-blur-md text-gray-900 dark:text-white px-5 py-2 rounded-full shadow-md hover:bg-yellow-300 dark:hover:bg-yellow-500 transition-colors"
                        id="btnCutiDepartemen">
                        🗓️ <span data-id="Cuti Departemen" data-en="Department Leave">Cuti Departemen</span>
                    </button>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>

                </div>
            </div>


        </div>
    </div>

    <div id="modal-quotes" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-xl w-full p-8 relative animate-[fadeIn_.3s_ease] ring-1 ring-gray-300 dark:ring-gray-700">

            <!-- Close -->
            <button id="close-quotes" class="absolute top-4 right-5 text-gray-400 hover:text-gray-200 text-3xl font-extrabold">&times;</button>

            <!-- Title -->
            <h2 class="text-2xl font-bold mb-4 text-purple-700 dark:text-purple-300 text-center"
                data-id="✨ Kutipan Hari Ini" data-en="✨ Quote of The Day">
                ✨ Kutipan Hari Ini
            </h2>

            <!-- Loading -->
            <div id="loading-quote" class="flex justify-center py-6">
                <svg class="animate-spin h-10 w-10 text-purple-500" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </div>

            <!-- Content -->
            <div id="quote-content" class="hidden text-center">
                <p id="quote-text" class="text-lg text-gray-800 dark:text-gray-200 italic"></p>
                <p id="quote-translation" class="text-md text-gray-700 dark:text-gray-300 mt-3"></p>
                <p id="quote-author" class="mt-4 text-gray-500 dark:text-gray-400 font-semibold"></p>

            </div>
        </div>
    </div>


    <!-- Modal Background & Container -->
    <div id="cutiModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
        <!-- Modal Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-11/12 md:w-3/4 max-h-[80vh] overflow-y-auto shadow-xl transform transition-transform duration-300 scale-95">

            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white"
                    data-id="Detail Sisa Cuti Pegawai" data-en="Employee Leave Balance Details">Detail Sisa Cuti Pegawai</h2>
                <button id="closeCutiModal" class="text-gray-500 hover:text-gray-900 dark:hover:text-white text-xl font-bold">✕</button>
            </div>

            <!-- Content -->
            <div id="cutiList" class="space-y-2 text-gray-700 dark:text-gray-300">
                <p class="text-gray-500 dark:text-gray-400">Memuat data...</p>
            </div>

        </div>
    </div>

    <!-- AOS Initialization -->
    <!-- Run Intro Once -->
    <script>
        // Helper terjemahan untuk teks dinamis (mengikuti bahasa di localStorage 'app_lang')
        function t(id, en) {
            let lang = window.appLang;
            if (!lang) {
                try {
                    lang = localStorage.getItem('app_lang');
                } catch (e) {}
            }
            return lang === 'en' ? en : id;
        }

        // $(document).ready(function() {
        //     const introKey = "intro_shown_dashboard_v1"; // versi bisa diubah untuk testing ulang
        //     if (!localStorage.getItem(introKey)) {
        //         introJs().start();
        //         localStorage.setItem(introKey, "true");
        //     }
        // });

        $(document).ready(function() {

            // $.ajax({
            //     url: 'api/cuti-tahunan', // endpoint kamu
            //     type: 'GET',
            //     dataType: 'json',
            //     success: function(response) {
            //         // misal response = { total: 12, terpakai: 2 }
            //         let total = response.total ?? 0;
            //         let terpakai = response.terpakai ?? 0;

            //         $('#cuti-tahunan').text(`${total}/${terpakai} ${t('Hari', 'Days')}`);
            //     },
            //     error: function(xhr, status, error) {
            //         console.log(error);
            //         $('#cuti-tahunan').text(`-`);
            //     }
            // });

            // Teks awal untuk cuti tahunan (menyesuaikan bahasa)
            $('#cuti-tahunan').text('0/0 ' + t('Hari', 'Days'));

            $('#btnCutiDepartemen').click(function() {
                $('#cutiModal').removeClass('hidden');

                // Tambahkan spinner loading
                $('#cutiList').html(`
                    <div class="flex justify-center py-8">
                        <svg class="animate-spin h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4l-3 3 3 3h-4z"></path>
                        </svg>
                    </div>
                `);

                $.ajax({
                    url: 'api/cuti/all-dept',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.length === 0) {
                            $('#cutiList').html('<p class="text-gray-500 dark:text-gray-400 text-center py-4">' +
                                t('Tidak ada data pegawai.', 'No employee data.') + '</p>');
                            return;
                        }

                        let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                        data.forEach(emp => {
                            let sisa = emp.total - emp.terpakai;

                            // Badge warna default Tailwind
                            let badgeColor = sisa <= 0 ? 'bg-red-500 text-white' :
                                sisa <= 2 ? 'bg-yellow-400 text-black' :
                                'bg-green-500 text-white';

                            html += `
                                <div class="flex justify-between items-center p-4 bg-blue-100 dark:bg-blue-700 rounded-xl shadow hover:bg-blue-200 dark:hover:bg-blue-600 transition-colors duration-200">
                                    <span class="font-medium text-gray-900 dark:text-white">${emp.name}</span>
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold ${badgeColor}">${emp.terpakai} / ${emp.total}</span>
                                </div>
                            `;
                        });


                        html += '</div>';

                        $('#cutiList').html(html);
                    },
                    error: function() {
                        $('#cutiList').html('<p class="text-red-500 text-center py-4">' +
                            t('Gagal memuat data.', 'Failed to load data.') + '</p>');
                    }
                });
            });
            // Tutup modal
            $('#closeCutiModal').click(function() {
                $('#cutiModal').addClass('hidden');
            });

            $('#btn-kontrak').click(function() {
                $('#modal-kontrak').removeClass('hidden');
                $('#table-kontrak').addClass('hidden');
                $('#loading-spinner').removeClass('hidden');

            });

            $('#close-modal').click(function() {
                $('#modal-kontrak').addClass('hidden');
                if ($.fn.DataTable.isDataTable('#table-kontrak')) {
                    $('#table-kontrak').DataTable().clear().destroy();
                }
                $('#table-kontrak tbody').empty();
            });

            $('#modal-kontrak').click(function(e) {
                if (e.target === this) {
                    $('#close-modal').click();
                }
            });

            $('#btn-quotes').click(function() {
                $('#modal-quotes').removeClass('hidden');
                $('#quote-content').addClass('hidden');
                $('#loading-quote').removeClass('hidden');

                $.ajax({
                    url: "{{ route('quote.get') }}",
                    method: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#quote-text').text(data.quoteText);

                        // Terjemahan quote hanya ditampilkan saat bahasa Indonesia
                        if (t('id', 'en') === 'id' && data.quoteTranslation) {
                            $('#quote-translation').text(data.quoteTranslation).removeClass('hidden');
                        } else {
                            $('#quote-translation').text('').addClass('hidden');
                        }

                        $('#quote-author').text("— " + data.quoteAuthor);

                        $('#loading-quote').addClass('hidden');
                        $('#quote-content').removeClass('hidden');
                    },

                    error: function() {
                        $('#quote-text').text(t('Gagal memuat quote.', 'Failed to load quote.'));
                        $('#quote-translation').text('').addClass('hidden');
                        $('#quote-author').text("");
                        $('#loading-quote').addClass('hidden');
                        $('#quote-content').removeClass('hidden');
                    }
                });
            });

            $('#close-quotes').click(function() {
                $('#modal-quotes').addClass('hidden');
            });

            $('#modal-quotes').click(function(e) {
                if (e.target === this) $('#close-quotes').click();
            });
        });
    </script>
</x-app-layout>