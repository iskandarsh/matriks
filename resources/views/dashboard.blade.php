<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white tracking-tight" data-aos="fade-down">
            🚀 Dashboard
        </h2>
    </x-slot>


    <!-- Modal -->
    <div id="modal-kontrak" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-auto p-8 relative ring-1 ring-gray-300 dark:ring-gray-700">
            <!-- Close Button -->
            <button id="close-modal" aria-label="Close modal" class="absolute top-4 right-5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors text-3xl font-extrabold focus:outline-none">
                &times;
            </button>

            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">📋 Daftar Kontrak Akan Habis / Lewat</h2>

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
                        <th class="border border-yellow-300 dark:border-yellow-600 px-6 py-3 text-left text-yellow-900 dark:text-yellow-100 font-semibold">Nama</th>
                        <th class="border border-yellow-300 dark:border-yellow-600 px-6 py-3 text-left text-yellow-900 dark:text-yellow-100 font-semibold">Posisi</th>
                        <th class="border border-yellow-300 dark:border-yellow-600 px-6 py-3 text-left text-yellow-900 dark:text-yellow-100 font-semibold">Departemen</th>
                        <th class="border border-yellow-300 dark:border-yellow-600 px-6 py-3 text-left text-yellow-900 dark:text-yellow-100 font-semibold">Akhir Kontrak</th>
                        <th class="border border-yellow-300 dark:border-yellow-600 px-6 py-3 text-left text-yellow-900 dark:text-yellow-100 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"></tbody>
            </table>
        </div>
    </div>


    <div class="py-16 bg-gray-50 dark:bg-gray-900 min-h-screen">
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
                    <img src="{{ Auth::user()->employee?->applicant?->appPhoto 
                            ? env('APP_URL') . 'interbat/' . Auth::user()->employee->applicant->appPhoto
                            : asset('images/default-avatar.png') }}"
                        alt="Foto Profil" class="object-cover w-24 h-32" />
                </div>

                {{-- Tengah: Teks --}}
                <div class="flex-1 transition-colors duration-300 ease-in-out hover:text-indigo-200">
                    <p class="text-2xl font-semibold tracking-wide transition-transform duration-300 ease-in-out hover:translate-x-2">
                        Hi, {{ Auth::user()->name }} 👋
                    </p>
                    <p class="text-base opacity-90 mt-1 transition-opacity duration-300 ease-in-out hover:opacity-100">
                        Selamat datang kembali! Semangat kerja ya 💪
                    </p>

                    {{-- Total Cuti --}}
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div class="bg-white/20 px-4 py-2 rounded-xl shadow-md backdrop-blur-md text-center">
                            🗓️ <span class="font-semibold">Cuti Tahunan</span><br>
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

            {{-- Info Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                {{-- Jabatan --}}
                <div data-intro="Menampilkan jabatan kamu di perusahaan." data-step="2"
                    data-aos="zoom-in" data-aos-duration="800" data-aos-easing="ease-out-cubic"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-md
               transition-transform duration-300 ease-in-out hover:shadow-xl hover:scale-[1.05] hover:bg-indigo-50 dark:hover:bg-indigo-900 cursor-pointer
               hover:translate-y-[-5px] hover:brightness-105">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2 transition-colors duration-300 hover:text-indigo-600">💼
                        <span>Jabatan</span>
                    </p>
                    <h3 class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 transition-colors duration-300 hover:text-indigo-700">
                        {{ Auth::user()->position->posiNama ?? '-' }}
                    </h3>
                </div>

                {{-- Departemen --}}
                <div data-aos="zoom-in" data-aos-delay="150" data-aos-duration="800"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-md
               transition-transform duration-300 ease-in-out hover:shadow-xl hover:scale-[1.05] hover:bg-indigo-50 dark:hover:bg-indigo-900 cursor-pointer
               hover:translate-y-[-5px] hover:brightness-105">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2 transition-colors duration-300 hover:text-indigo-600">🏢
                        <span>Departemen</span>
                    </p>
                    <div class="mt-3 space-y-2">
                        @if (Auth::user()->employee)
                        {{-- Departemen --}}
                        @if (Auth::user()->employee->department)
                        <span
                            class="inline-block bg-indigo-100 dark:bg-indigo-700 text-indigo-700 dark:text-indigo-100 text-sm px-4 py-1 rounded-full font-medium transition-colors duration-300 hover:bg-indigo-200 dark:hover:bg-indigo-600">
                            {{ Auth::user()->employee->department->depNama }}
                        </span>
                        @else
                        <span class="text-gray-400 italic">Tidak ada departemen</span>
                        @endif
                        <span class="text-gray-300 dark:text-gray-600">-</span>
                        {{-- Workunit --}}
                        @if (Auth::user()->employee->workunit)
                        <span
                            class="inline-block bg-green-100 dark:bg-green-700 text-green-700 dark:text-green-100 text-sm px-4 py-1 rounded-full font-medium transition-colors duration-300 hover:bg-green-200 dark:hover:bg-green-600">
                            {{ Auth::user()->employee->workunit->woruNama }}
                        </span>
                        @else
                        <span class="text-gray-400 italic">Tidak ada workunit</span>
                        @endif
                        @else
                        <span class="text-gray-400 italic">Tidak memiliki relasi employee</span>
                        @endif
                    </div>
                </div>

                {{-- Terakhir Login --}}
                <div data-aos="zoom-in" data-aos-delay="300" data-aos-duration="800"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-md
               transition-transform duration-300 ease-in-out hover:shadow-xl hover:scale-[1.05] hover:bg-indigo-50 dark:hover:bg-indigo-900 cursor-pointer
               hover:translate-y-[-5px] hover:brightness-105">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2 transition-colors duration-300 hover:text-indigo-600">⏰
                        <span>Terakhir Login</span>
                    </p>
                    <h3 class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 transition-colors duration-300 hover:text-indigo-700">
                        {{ Auth::user()->last_login_at ? \Carbon\Carbon::parse(Auth::user()->last_login_at)->translatedFormat('d M Y H:i') : '-' }}
                    </h3>
                </div>

            </div>

            {{-- Akses Cepat --}}
            <div data-intro="Tombol cepat untuk akses." data-step="3" data-aos="fade-up" data-aos-duration="800"
                class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">⚡ Akses Cepat</h3>
                <div class="flex flex-wrap gap-4">

                    <a href="{{ route('profile.edit') }}"
                        class="inline-flex items-center gap-2 bg-indigo-600 text-white font-semibold px-5 py-2 rounded-full
                   shadow-md hover:bg-indigo-500 hover:text-indigo-100 transition duration-300 ease-in-out
                   focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1 active:scale-95">
                        ✏️ Edit Profil
                    </a>




                    <button id="btn-quotes"
                        class="inline-flex items-center gap-2 bg-purple-600 text-white font-semibold px-5 py-2 rounded-full
                            shadow-md hover:bg-purple-500 hover:text-purple-100 transition duration-300 ease-in-out
                            focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-1 active:scale-95">
                        ✨ Quote Hari Ini
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
                        🗓️ Cuti Departemen
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
            <h2 class="text-2xl font-bold mb-4 text-purple-700 dark:text-purple-300 text-center">
                ✨ Quote of The Day
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
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Sisa Cuti Pegawai</h2>
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

            //         $('#cuti-tahunan').text(`${total}/${terpakai} Hari`);
            //     },
            //     error: function(xhr, status, error) {
            //         console.log(error);
            //         $('#cuti-tahunan').text(`-`);
            //     }
            // });


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
                            $('#cutiList').html('<p class="text-gray-500 dark:text-gray-400 text-center py-4">Tidak ada data pegawai.</p>');
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
                        $('#cutiList').html('<p class="text-red-500 text-center py-4">Gagal memuat data.</p>');
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
                        $('#quote-translation').text(data.quoteTranslation); // <= TAMBAH INI
                        $('#quote-author').text("— " + data.quoteAuthor);

                        $('#loading-quote').addClass('hidden');
                        $('#quote-content').removeClass('hidden');
                    },

                    error: function() {
                        $('#quote-text').text("Gagal memuat quote.");
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