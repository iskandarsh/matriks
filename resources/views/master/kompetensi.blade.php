<x-app-layout>
    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-extrabold mb-5 text-blue-500 flex items-center space-x-2 drop-shadow-sm">
                        <i class="fas fa-database text-blue-600 animate-pulse"></i>
                        <span data-id="Master Kompetensi" data-en="Competency Master">Master Kompetensi</span>

                    </h2>

                    <div id="applicantTabs" x-data="{
                        loadTable() {
                            this.$nextTick(() => {
                                window.loadTable();
                            });
                        }
                    }">

                        <div class="mb-4 flex gap-2">
                            @can('create', [App\Models\MasterKompetensi::class, session('active_menu_id')])
                            <button
                                id="btnCreate"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm flex items-center gap-2">
                                <i class="fas fa-plus"></i> <span data-id="Buat" data-en="Create">Buat</span>
                            </button>
                            @endcan
                        </div>

                        <!-- Hanya satu container grid dengan id tetap -->
                        <div id="grid"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal Create Kompetensi -->
    <div
        id="modalCreate"
        class="hidden fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm p-4 dark:bg-gray-900/90">

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-full max-w-md">

            <form id="formCreate" method="POST" class="space-y-6">
                @csrf

                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    📂 <span data-id="Tambah Master Kompetensi" data-en="Add Competency Master">Tambah Master Kompetensi</span>
                </h2>


                <!-- Nama kompetensi -->
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        <span data-id="Nama Kompetensi" data-en="Competency Name">Nama Kompetensi</span> <span class="text-red-500">*</span>
                    </label>


                    <input
                        name="nama"
                        type="text"
                        required
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                        placeholder="Contoh: Leadership"
                        data-id-placeholder="Contoh: Leadership"
                        data-en-placeholder="Example: Leadership">
                </div>
                <!-- Initial -->
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        <span data-id="Initial" data-en="Initial">Initial</span> <span class="text-red-500">*</span>
                    </label>

                    <input
                        name="initial"
                        type="text"
                        required
                        maxlength="10"
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                        placeholder="Contoh: LD"
                        data-id-placeholder="Contoh: LD"
                        data-en-placeholder="Example: LD">
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        <span data-id="Deskripsi" data-en="Description">Deskripsi</span>
                    </label>

                    <textarea
                        name="deskripsi"
                        rows="3"
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                        placeholder="Deskripsi kompetensi..."
                        data-id-placeholder="Deskripsi kompetensi..."
                        data-en-placeholder="Competency description..."></textarea>
                </div>

                <!-- Kategori -->
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        <span data-id="Kategori" data-en="Category">Kategori</span> <span class="text-red-500">*</span>
                    </label>

                    <select name="kategori_id" id="kategori_id"
                        class="w-full"
                        required>
                    </select>
                </div>
                <!-- Footer -->
                <div class="flex justify-end gap-4 pt-6 border-t mt-6 border-gray-200 dark:border-gray-700">

                    <button
                        type="button"
                        onclick="document.getElementById('modalCreate').classList.add('hidden')"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-300">
                        ❌ <span data-id="Batal" data-en="Cancel">Batal</span>
                    </button>

                    <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md">
                        💾 <span data-id="Simpan" data-en="Save">Simpan</span>
                    </button>

                </div>

            </form>
        </div>
    </div>


    <!-- Modal Edit Kompetensi -->
    <div
        id="modalEdit"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-blue-950/60 backdrop-blur-sm p-4">

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-full max-w-md">

            <form id="formEditKategori">
                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    ✏️ <span data-id="Edit Master Kompetensi" data-en="Edit Competency Master">Edit Master Kompetensi</span>
                </h2>


                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        <span data-id="Nama Kompetensi" data-en="Competency Name">Nama Kompetensi</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="edit_nama"
                        name="nama"
                        type="text"
                        required
                        class="w-full border border-gray-300 rounded-xl p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                </div>
                <!-- Initial -->
                <div class="mt-4">
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        <span data-id="Initial" data-en="Initial">Initial</span> <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="edit_initial"
                        name="initial"
                        type="text"
                        required
                        maxlength="10"
                        class="w-full border border-gray-300 rounded-xl p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                </div>

                <!-- Deskripsi -->
                <div class="mt-4">
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        <span data-id="Deskripsi" data-en="Description">Deskripsi</span>
                    </label>

                    <textarea
                        id="edit_deskripsi"
                        name="deskripsi"
                        rows="3"
                        class="w-full border border-gray-300 rounded-xl p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>

                <!-- Kategori -->
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        <span data-id="Kategori" data-en="Category">Kategori</span> <span class="text-red-500">*</span>
                    </label>

                    <select name="kategori_id" id="edit_kategori_id" class="w-full" required></select>
                </div>

                <div class="flex justify-end gap-4 pt-6 border-t mt-6">
                    <button type="button"
                        onclick="document.getElementById('modalEdit').classList.add('hidden')"
                        class="text-gray-600">
                        ❌ <span data-id="Batal" data-en="Cancel">Batal</span>
                    </button>

                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                        💾 <span data-id="Simpan" data-en="Save">Simpan</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <div id="modalProses"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-2xl">

            <h2 class="text-xl font-semibold mb-4">
                ⚙️ <span data-id="Proses Skala Kompetensi" data-en="Competency Scale Process">Proses Skala Kompetensi</span>
            </h2>

            <input type="hidden" id="proses_kompetensi_id">

            <div id="detailContainer" class="space-y-3"></div>

            <button id="btnAddRow"
                class="mt-3 bg-blue-500 text-white px-4 py-2 rounded">
                ➕ <span data-id="Tambah Skala" data-en="Add Scale">Tambah Skala</span>
            </button>

            <div class="flex justify-end gap-3 mt-6">
                <button onclick="$('#modalProses').addClass('hidden')"
                    class="text-gray-600">
                    ❌ <span data-id="Batal" data-en="Cancel">Batal</span>
                </button>

                <button id="btnSaveDetail"
                    class="bg-green-600 text-white px-6 py-2 rounded">
                    💾 <span data-id="Simpan" data-en="Save">Simpan</span>
                </button>
            </div>

        </div>
    </div>

    <!-- THEME SCRIPT -->
    <script>
        const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const lightTheme = document.getElementById('dx-theme-light');
        const darkTheme = document.getElementById('dx-theme-dark');

        if (isDarkMode) {
            darkTheme?.removeAttribute('disabled');
        } else {
            lightTheme?.removeAttribute('disabled');
        }

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            location.reload();
        });
    </script>
    <script>
        // ===== Helper terjemahan (mengikuti bahasa di localStorage 'app_lang') =====
        function t(id, en) {
            let lang = window.appLang;
            if (!lang) {
                try {
                    lang = localStorage.getItem('app_lang');
                } catch (e) {}
            }
            return lang === 'en' ? en : id;
        }

        // ===== Select2 kategori (dibuat ulang saat bahasa berganti supaya placeholder ikut berganti) =====
        function initKategoriSelects() {
            const configs = [{
                    el: '#kategori_id',
                    parent: '#modalCreate'
                },
                {
                    el: '#edit_kategori_id',
                    parent: '#modalEdit'
                }
            ];

            configs.forEach(cfg => {
                const $el = $(cfg.el);

                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }

                $el.select2({
                    theme: 'bootstrap-5',
                    placeholder: t('Pilih kategori...', 'Select category...'),
                    language: {
                        noResults: () => t('Data tidak ditemukan', 'No results found'),
                        searching: () => t('Mencari...', 'Searching...')
                    },
                    dropdownParent: $(cfg.parent), // biar muncul di dalam modal
                    ajax: {
                        url: 'kategori-select/select', // endpoint
                        type: 'GET',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                search: params.term // keyword
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.map(item => ({
                                    id: item.id,
                                    text: item.nama
                                }))
                            };
                        },
                        cache: true
                    }
                });
            });
        }

        $(document).ready(function() {

            // Saat bahasa diganti lewat tombol 🌐, bangun ulang grid & select2 supaya teks ikut berganti
            if (typeof window.applyLanguage === 'function') {
                const originalApplyLanguage = window.applyLanguage;
                window.applyLanguage = function(lang) {
                    originalApplyLanguage(lang);
                    initKategoriSelects();
                    if ($('#grid').length && typeof loadTable === 'function') loadTable();
                };
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#btnCreate').on('click', function() {
                $('#modalCreate').removeClass('hidden').addClass('flex');
            });
            $('#btnCancel').on('click', function() {
                $('#modalCreate').addClass('hidden').removeClass('flex');
                $('#formCreate')[0].reset();
            });

            initKategoriSelects();

            loadTable();
        });

        function createRow(data = {}) {

            return `
                <div class="flex gap-3 items-start detail-row border p-3 rounded">
                    
                    <input type="hidden" name="detail_id[]" value="${data.id ?? ''}">

                    <input type="number"
                        name="skala[]"
                        placeholder="${t('Skala', 'Scale')}"
                        class="w-24 border rounded p-2"
                        value="${data.skala ?? ''}" />

                    <input type="text"
                        name="deskripsi[]"
                        placeholder="${t('Pengertian', 'Definition')}"
                        class="flex-1 border rounded p-2"
                        value="${data.deskripsi ?? ''}" />

                    <button type="button"
                        class="btnDeleteRow bg-red-500 text-white px-3 py-1 rounded">
                        🗑
                    </button>

                </div>
            `;
        }

        function openProsesModal(id) {

            $('#proses_kompetensi_id').val(id);
            $('#detailContainer').html('');

            $.get("{{ route('kompetensi.detail', ':id') }}".replace(':id', id), function(res) {

                if (res.details.length > 0) {
                    res.details.forEach(item => {
                        $('#detailContainer').append(createRow(item));
                    });
                } else {
                    $('#detailContainer').append(createRow());
                }

                $('#modalProses').removeClass('hidden').addClass('flex');
            });
        }

        $('#btnAddRow').on('click', function() {
            $('#detailContainer').append(createRow());
        });

        $(document).on('click', '.btnDeleteRow', function() {
            $(this).closest('.detail-row').remove();
        });

        $('#btnSaveDetail').on('click', function() {

            let id = $('#proses_kompetensi_id').val();

            $.ajax({
                url: "{{ route('kompetensi.detail.save', ':id') }}".replace(':id', id),
                type: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    detail_id: $('input[name="detail_id[]"]').map(function() {
                        return $(this).val();
                    }).get(),
                    skala: $('input[name="skala[]"]').map(function() {
                        return $(this).val();
                    }).get(),
                    deskripsi: $('input[name="deskripsi[]"]').map(function() {
                        return $(this).val();
                    }).get(),
                },
                success: function(res) {

                    Swal.fire(t('Berhasil', 'Success'), res.message, 'success');
                    $('#modalProses').addClass('hidden');
                }
            });
        });

        let gridInstance = null;
        const badgeClass = "inline-block rounded px-2 py-1 text-xs font-semibold";

        function loadTable() {
            const gridId = 'grid';
            const $container = $('#' + gridId);

            fetch("{{ route('kompetensi.data') }}") // ✅ ganti route
                .then(res => res.json())
                .then(data => {

                    const kompetensis = data.kompetensi; // backend masih return 'kategori'
                    const userPermissions = data.permissions || {};

                    if (gridInstance) {
                        $container.dxDataGrid('dispose');
                        $container.empty();
                    }

                    gridInstance = $container.dxDataGrid({
                        dataSource: kompetensis,
                        keyExpr: 'id',
                        rowAlternationEnabled: true,
                        columnAutoWidth: true,
                        columnHidingEnabled: true,
                        wordWrapEnabled: true,
                        noDataText: t('Tidak ada data', 'No data'),

                        searchPanel: {
                            visible: true,
                            width: 240,
                            placeholder: t('Cari...', 'Search...')
                        },

                        paging: {
                            pageSize: 10
                        },

                        pager: {
                            showPageSizeSelector: true,
                            allowedPageSizes: [10, 25, 50],
                            showInfo: true,
                            infoText: t('Halaman {0} dari {1} ({2} data)', 'Page {0} of {1} ({2} items)')
                        },

                        onCellPrepared(e) {
                            if (e.rowType === "header")
                                $(e.cellElement).addClass("bg-gray-100 text-gray-800");

                            if (e.rowType === "data")
                                $(e.cellElement).addClass("bg-white text-gray-900");
                        },

                        onRowPrepared(e) {
                            if (e.rowType === "data" && e.data.deleted_at) {
                                $(e.rowElement).addClass("bg-red-200 text-red-800");
                            }
                        },

                        columns: [{
                                caption: t('No', 'No'),
                                width: 50,
                                alignment: 'center',
                                cellTemplate(container, options) {

                                    const pageIndex = gridInstance.pageIndex();
                                    const pageSize = gridInstance.pageSize();
                                    const index = options.rowIndex + 1 + (pageIndex * pageSize);

                                    container.text(index);
                                }
                            },
                            {
                                dataField: 'nama',
                                caption: t('Nama Kompetensi', 'Competency Name'), // ✅ ganti label
                                alignment: 'left'
                            },
                            {
                                dataField: 'initial',
                                caption: t('Initial', 'Initial'),
                                alignment: 'center',
                                width: 120
                            },
                            {
                                dataField: 'deskripsi',
                                caption: t('Deskripsi', 'Description'),
                                alignment: 'left'
                            },
                            {
                                dataField: 'kategori.nama', // 🔥 ambil dari relasi
                                caption: t('Kategori', 'Category'),
                                alignment: 'center',
                                width: 180,
                                cellTemplate(container, options) {

                                    const kategori = options.data.kategori?.nama ?? '-';

                                    // Badge biar keren 😎
                                    const badge = $('<span>')
                                        .addClass('px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700')
                                        .text(kategori);

                                    container.append(badge);
                                }
                            },
                            {
                                caption: t('Aksi', 'Actions'),
                                alignment: 'center',
                                width: 150,
                                cellTemplate(container, options) {

                                    // Center isi cell tanpa flex
                                    container.addClass("text-center align-middle");

                                    // Wrapper inline-block supaya tombol tetap sejajar & rapi
                                    const wrapper = $('<div>')
                                        .addClass('inline-block');

                                    const id = options.data.id;

                                    // ===== EDIT =====
                                    if (userPermissions.edit) {
                                        $('<button>')
                                            .addClass('p-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded me-2 transition duration-200')
                                            .html('<i class="fas fa-edit"></i>')
                                            .attr('title', t('Ubah', 'Edit'))
                                            .on('click', function() {
                                                openEditModal(id);
                                            })
                                            .appendTo(wrapper);
                                    }

                                    // ===== PROSES =====
                                    if (userPermissions.proses) {
                                        $('<button>')
                                            .addClass('p-2 bg-green-600 hover:bg-green-700 text-white rounded me-2 transition duration-200')
                                            .html('<i class="fas fa-cogs"></i>')
                                            .attr('title', t('Proses', 'Process'))
                                            .on('click', function() {
                                                openProsesModal(id);
                                            })
                                            .appendTo(wrapper);
                                    }

                                    // ===== DELETE =====
                                    if (userPermissions.delete) {
                                        $('<button>')
                                            .addClass('p-2 bg-red-600 hover:bg-red-700 text-white rounded me-2 transition duration-200')
                                            .html('<i class="fas fa-trash"></i>')
                                            .attr('title', t('Hapus', 'Delete'))
                                            .on('click', function() {

                                                Swal.fire({
                                                    title: t('Hapus kompetensi?', 'Delete competency?'),
                                                    text: t('Data yang dihapus tidak dapat dikembalikan.', 'Deleted data cannot be restored.'),
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonText: t('Ya, Hapus', 'Yes, Delete'),
                                                    cancelButtonText: t('Batal', 'Cancel'),
                                                    confirmButtonColor: '#dc2626'
                                                }).then(result => {

                                                    if (result.isConfirmed) {

                                                        $.ajax({
                                                            url: "{{ route('kompetensi.destroy', ':id') }}".replace(':id', id),
                                                            type: 'DELETE',
                                                            data: {
                                                                _token: $('meta[name="csrf-token"]').attr('content')
                                                            },
                                                            success(res) {
                                                                Swal.fire(t('Berhasil', 'Success'), res.message, 'success');
                                                                loadTable();
                                                            },
                                                            error() {
                                                                Swal.fire(
                                                                    t('Gagal', 'Failed'),
                                                                    t('Terjadi kesalahan saat menghapus data.', 'An error occurred while deleting the data.'),
                                                                    'error'
                                                                );
                                                            }
                                                        });

                                                    }
                                                });

                                            })
                                            .appendTo(wrapper);
                                    }

                                    // Masukkan wrapper ke container
                                    wrapper.appendTo(container);
                                }
                            }
                        ]

                    }).dxDataGrid('instance');

                })
                .catch(err => console.error(err));
        }



        function openEditModal(id) {

            $.ajax({
                url: "{{ route('kompetensi.show', ':id') }}".replace(':id', id),
                type: 'GET',

                success: function(data) {

                    $('#edit_id').val(data.id);
                    $('#edit_nama').val(data.nama);
                    $('#edit_initial').val(data.initial);
                    $('#edit_deskripsi').val(data.deskripsi);

                    // 🔥 SET SELECT2 VALUE
                    if (data.kategori_id) {

                        let option = new Option(data.kategori?.nama ?? t('Kategori', 'Category'), data.kategori_id, true, true);
                        $('#edit_kategori_id').append(option).trigger('change');

                    } else {
                        $('#edit_kategori_id').val(null).trigger('change');
                    }

                    $('#modalEdit').removeClass('hidden').addClass('flex');
                },

                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: t('Gagal', 'Failed'),
                        text: t('Gagal mengambil data kompetensi.', 'Failed to fetch competency data.')
                    });
                }
            });

        }


        $('#formCreate').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const originalText = $btn.html();

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> ' + t('Menyimpan...', 'Saving...'));

            $.ajax({
                url: "{{ route('kompetensi.store') }}",
                method: "POST",
                data: $form.serialize(),

                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: t('Sukses!', 'Success!'),
                        text: res.message,
                    }).then(() => {

                        $('#modalCreate').addClass('hidden');
                        $form[0].reset();

                        if (typeof loadTable === 'function') {
                            loadTable();
                        }
                    });
                },

                error: function(xhr) {

                    let error = xhr.responseJSON?.message ?? t('Gagal menyimpan', 'Failed to save');

                    Swal.fire({
                        icon: 'error',
                        title: t('Gagal!', 'Failed!'),
                        text: error
                    });
                },

                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });


        $('#formEditKategori').on('submit', function(e) {

            e.preventDefault();

            let id = $('#edit_id').val();

            $.ajax({
                url: "{{ route('kompetensi.update', ':id') }}".replace(':id', id),
                type: "POST",
                data: $(this).serialize(),

                success: function(res) {

                    Swal.fire({
                        icon: 'success',
                        title: t('Sukses', 'Success'),
                        text: res.message
                    });

                    $('#modalEdit').addClass('hidden');

                    loadTable();
                },

                error: function(xhr) {

                    Swal.fire({
                        icon: 'error',
                        title: t('Gagal', 'Failed'),
                        text: xhr.responseJSON?.message ?? t('Gagal update', 'Failed to update')
                    });

                }

            });

        });
    </script>



</x-app-layout>