<x-app-layout>
    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-extrabold mb-5 text-blue-500 flex items-center space-x-2 drop-shadow-sm">
                        <i class="fas fa-database text-blue-600 animate-pulse"></i>
                        <span>Master Kompetensi Jabatan</span>

                    </h2>

                    <div id="applicantTabs" x-data="{
                        loadTable() {
                            this.$nextTick(() => {
                                window.loadTable();
                            });
                        }
                    }">

                        <div class="mb-4 flex gap-2">
                            @can('create', [App\Models\MasterKompetensiPelatihan::class, session('active_menu_id')])
                            <button
                                id="btnCreate"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm flex items-center gap-2">
                                <i class="fas fa-plus"></i> Create
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

    <!-- Modal Create Position -->
    <div id="modalCreate"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">

        <div class="bg-white dark:bg-gray-800 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">

            <div class="px-6 py-4 border-b dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Tambah Kompetensi Jabatan
                </h2>

                <button onclick="$('#modalCreate').addClass('hidden')"
                    class="text-gray-400 hover:text-red-500 text-xl">✕</button>
            </div>

            <form id="formCreate" class="p-6 space-y-5">
                @csrf

                <!-- JABATAN -->
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Jabatan <span class="text-red-500">*</span>
                    </label>

                    <select name="id_jabatan"
                        id="selectJabatan"
                        required
                        class="w-full"></select>
                </div>

                <!-- KOMPETENSI -->
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Kompetensi <span class="text-red-500">*</span>
                    </label>

                    <select name="id_kompetensi"
                        id="selectKompetensi"
                        required
                        class="w-full"></select>
                </div>

                <!-- SKALA -->
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Skala (1-5) <span class="text-red-500">*</span>
                    </label>

                    <input type="number"
                        name="skala"
                        min="1"
                        max="5"
                        required
                        class="w-full border rounded-lg p-2 dark:bg-gray-700">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button"
                        onclick="$('#modalCreate').addClass('hidden')"
                        class="px-4 py-2 border rounded-lg">
                        Batal
                    </button>

                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg">
                        Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>



    <!-- Modal Edit Position -->
    <div
        id="modalEdit"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-blue-950/60 backdrop-blur-sm p-4">

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-full max-w-md">

            <form id="formEditKategori">
                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    ✏️ Edit Master Kompetensi
                </h2>


                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        Nama Kompetensi
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="edit_nama"
                        name="nama"
                        type="text"
                        required
                        class="w-full border border-gray-300 rounded-xl p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                </div>

                <div class="flex justify-end gap-4 pt-6 border-t mt-6">
                    <button type="button"
                        onclick="document.getElementById('modalEdit').classList.add('hidden')"
                        class="text-gray-600">
                        ❌ Batal
                    </button>

                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                        💾 Simpan
                    </button>
                </div>

            </form>
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
        $(document).ready(function() {

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

            loadTable();


            setTimeout(() => {

                initSelectKompetensiJabatan();

            }, 200);

        });

        function initSelectKompetensiJabatan() {

            // JABATAN
            $('#selectJabatan').select2({
                theme: "bootstrap-5",
                width: '100%',
                dropdownParent: $('#modalCreate'),
                placeholder: "Pilih jabatan",

                ajax: {
                    url: "{{ route('jabatan.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data
                    })
                }
            });

            // KOMPETENSI
            $('#selectKompetensi').select2({
                theme: "bootstrap-5",
                width: '100%',
                dropdownParent: $('#modalCreate'),
                placeholder: "Pilih kompetensi",

                ajax: {
                    url: "{{ route('kompetensi.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data
                    })
                }
            });
        }


        let gridInstance = null;
        const badgeClass = "inline-block rounded px-2 py-1 text-xs font-semibold";

        function loadTable() {
            const gridId = 'grid';
            const $container = $('#' + gridId);

            fetch("{{ route('kompetensi_jabatan.data') }}")
                .then(res => res.json())
                .then(data => {
                    const rows = data.data;
                    const userPermissions = data.permissions || {};

                    // ✅ OPTIMIZED: If grid exists, just update data instead of destroying/recreating
                    if (gridInstance) {
                        gridInstance.option('dataSource', rows);
                        return;
                    }

                    // ✅ INITIALIZE GRID
                    gridInstance = $container.dxDataGrid({
                        dataSource: rows,
                        keyExpr: 'id',
                        rowAlternationEnabled: true,
                        columnAutoWidth: true,
                        showBorders: true, // Optional: better UI

                        // ✅ GROUPING CONFIG
                        groupPanel: {
                            visible: true,
                            emptyPanelText: "Drag a column header here to group by that column"
                        },
                        grouping: {
                            autoExpandAll: true
                        },
                        allowColumnReordering: true,

                        // ✅ UTILITIES
                        columnChooser: {
                            enabled: true
                        },
                        searchPanel: {
                            visible: true,
                            width: 240
                        },
                        paging: {
                            pageSize: 10
                        },
                        pager: {
                            showPageSizeSelector: true,
                            allowedPageSizes: [10, 25, 50],
                            showInfo: true
                        },

                        columnChooser: {
                            enabled: true,
                            mode: "select",
                            allowSearch: true
                        },

                        columnHidingEnabled: false,

                        columnFixing: {
                            enabled: true
                        },

                        headerFilter: {
                            visible: true
                        },

                        filterRow: {
                            visible: true
                        },

                        columns: [

                            {
                                caption: 'No',
                                width: 60,
                                alignment: 'center',
                                allowGrouping: false,
                                allowHiding: false,
                                cellTemplate(container, options) {

                                    if (options.rowType !== "data") return;

                                    const visibleRows = options.component.getVisibleRows();

                                    let index = 0;
                                    for (let i = 0; i < visibleRows.length; i++) {
                                        if (visibleRows[i].rowType === "data") index++;
                                        if (visibleRows[i].key === options.key) {
                                            container.text(index);
                                            break;
                                        }
                                    }
                                }
                            },

                            {
                                caption: 'Departement',
                                dataField: 'departement.depNama',
                                calculateCellValue: row => row.departement?.depNama ?? '-',
                                groupIndex: 0
                            },

                            {
                                caption: 'Jabatan',
                                dataField: 'jabatan.nama',
                                calculateCellValue: row => row.jabatan?.nama ?? '-',
                                groupIndex: 1
                            },

                            {
                                caption: 'Kompetensi',
                                dataField: 'kompetensi.nama',
                                calculateCellValue: row => row.kompetensi?.nama ?? '-'
                            },

                            {
                                caption: 'Skala',
                                dataField: 'skala',
                                alignment: 'center'
                            },

                            {
                                caption: 'Actions',
                                alignment: 'center',
                                width: 120,
                                allowGrouping: false,
                                allowSearch: false,

                                cellTemplate(container, options) {

                                    const id = options.data.id;

                                    const $wrapper = $('<div>').addClass("flex gap-2 justify-center");

                                    if (userPermissions.delete) {

                                        $('<button>')
                                            .addClass('p-2 bg-red-600 text-white rounded hover:bg-red-700 transition')
                                            .html('<i class="fas fa-trash"></i>')
                                            .on('click', e => {

                                                e.stopPropagation();

                                                Swal.fire({
                                                    title: "Yakin hapus?",
                                                    text: "Data tidak bisa dikembalikan",
                                                    icon: "warning",
                                                    showCancelButton: true,
                                                    confirmButtonText: "Ya hapus",
                                                    cancelButtonText: "Batal"
                                                }).then(result => {

                                                    if (!result.isConfirmed) return;

                                                    const $btn = $(e.currentTarget);
                                                    const oldHtml = $btn.html();

                                                    $btn.prop('disabled', true)
                                                        .html('<i class="fas fa-spinner fa-spin"></i>');

                                                    deleteData(id)
                                                        .finally(() => {
                                                            $btn.prop('disabled', false).html(oldHtml);
                                                        });

                                                });

                                            })
                                            .appendTo($wrapper);
                                    }

                                    $wrapper.appendTo(container);
                                }
                            }

                        ]
                    }).dxDataGrid('instance');
                })
                .catch(err => {
                    console.error("Load Table Error:", err);
                    // Optional: Show alert if fetch fails
                });
        }


        function openEditModal(id) {

            $.ajax({
                url: "{{ route('kompetensi.show', ':id') }}".replace(':id', id),
                type: 'GET',

                success: function(data) {

                    $('#edit_id').val(data.id);
                    $('#edit_nama').val(data.nama);

                    $('#modalEdit').removeClass('hidden').addClass('flex');
                },

                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal mengambil data kategori.'
                    });
                }
            });

        }

        function deleteData(id) {

            return fetch(`kompetensi_jabatan/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                })
                .then(r => r.json())
                .then(() => {
                    loadTable(); // reload grid
                });

        }

        $('#formCreate').submit(function(e) {

            e.preventDefault();
            const form = this;

            Swal.fire({
                title: 'Simpan data?',
                icon: 'question',
                showCancelButton: true
            }).then(result => {

                if (!result.isConfirmed) return;

                Swal.showLoading();

                $.ajax({
                    url: "{{ route('kompetensi_jabatan.store') }}",
                    type: "POST",
                    data: $(form).serialize(),

                    success: function(res) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });

                        $('#modalCreate').addClass('hidden');
                        form.reset();

                        $('#selectJabatan').val(null).trigger('change');
                        $('#selectKompetensi').val(null).trigger('change');

                        loadTable();
                    },

                    error: function(xhr) {

                        let msg = xhr.responseJSON?.message ?? 'Gagal menyimpan';

                        if (xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                        }

                        Swal.fire('Error', msg, 'error');
                    }
                });
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
                        title: 'Sukses',
                        text: res.message
                    });

                    $('#modalEdit').addClass('hidden');

                    loadTable();
                },

                error: function(xhr) {

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON?.message ?? 'Gagal update'
                    });

                }

            });

        });
    </script>



</x-app-layout>