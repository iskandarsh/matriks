<x-app-layout>
    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-extrabold mb-5 text-blue-500 flex items-center space-x-2 drop-shadow-sm">
                        <i class="fas fa-database text-blue-600 animate-pulse"></i>
                        <span>Employee Setting</span>
                    </h2>

                    <div id="applicantTabs" x-data="{
                        loadTable() {
                            this.$nextTick(() => {
                                window.loadTable();
                            });
                        }
                    }">

                        <div class="mb-4 flex gap-2">
                            @can('create', [App\Models\MasterJabatan::class, session('active_menu_id')])
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
        class="hidden fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm p-4 dark:bg-gray-900/90">

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-full max-w-md">

            <form id="formCreate" method="POST" class="space-y-6">
                @csrf

                <h2 class="text-xl font-semibold mb-4">
                    📂 Tambah Employee Setting
                </h2>

                <!-- EMPLOYEE -->
                <div>
                    <label class="block text-sm mb-1">Employee *</label>
                    <select name="id_employee" id="selectEmployee" required class="w-full"></select>
                </div>

                <!-- JABATAN -->
                <div>
                    <label class="block text-sm mb-1">Jabatan *</label>
                    <select name="id_jabatan" id="selectJabatan" required class="w-full"></select>
                </div>

                <!-- TYPE -->
                <div>
                    <label class="block text-sm mb-1">Type *</label>
                    <select name="type" required class="w-full rounded border p-3">
                        <option value="">Pilih type</option>
                        <option value="ITP">Individual Training</option>
                        <option value="PP">Program Training</option>
                    </select>
                </div>

                <!-- TAHUN -->
                <div>
                    <label class="block text-sm mb-1">Tahun Berlaku *</label>
                    <input name="tahun_berlaku"
                        type="number"
                        value="{{ date('Y') }}"
                        required
                        class="w-full rounded border p-3">
                </div>
                <div class="flex justify-end gap-4 pt-6 border-t mt-6 border-gray-200 dark:border-gray-700">

                    <button type="button"
                        onclick="document.getElementById('modalCreate').classList.add('hidden')"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-300">
                        ❌ Batal
                    </button>

                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md">
                        💾 Simpan
                    </button>

                </div>

            </form>
        </div>
    </div>


    <!-- Modal Edit Position -->
    <div id="modalEdit"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-blue-950/60 backdrop-blur-sm p-4">

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-full max-w-md">

            <form id="formEditKategori">
                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id" name="id">

                <h2 class="text-xl font-semibold mb-4">
                    ✏️ Edit Master Jabatan
                </h2>

                <div>
                    <label class="block text-sm font-medium mb-1">
                        Nama Jabatan <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="edit_nama"
                        name="nama"
                        type="text"
                        required
                        class="w-full border rounded-xl p-3" />
                </div>

                <div class="flex justify-end gap-4 pt-6 border-t mt-6">

                    <button type="button"
                        onclick="document.getElementById('modalEdit').classList.add('hidden')">
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
            initSelectEmployeeSetting();
            loadTable();
        });


        function initSelectEmployeeSetting() {

            // =====================
            // EMPLOYEE
            // =====================
            $('#selectEmployee').select2({

                dropdownParent: $('#modalCreate'),
                width: '100%',
                placeholder: 'Pilih employee',

                ajax: {
                    url: "{{ route('employee.search') }}",
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


            // =====================
            // JABATAN (FILTER DEPARTMENT LOGIN)
            // =====================
            $('#selectJabatan').select2({

                dropdownParent: $('#modalCreate'),
                width: '100%',
                placeholder: 'Pilih jabatan',

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

        }

        let gridInstance = null;
        const badgeClass = "inline-block rounded px-2 py-1 text-xs font-semibold";

        function loadTable() {

            fetch("{{ route('employee_setting.data') }}")
                .then(res => res.json())
                .then(res => {

                    const rows = res.data ?? [];
                    const userPermissions = res.permissions ?? {};

                    // kalau grid sudah ada → update saja (anti flicker)
                    if (gridInstance) {
                        gridInstance.option('dataSource', rows);
                        return;
                    }

                    gridInstance = $('#grid').dxDataGrid({

                        dataSource: rows,
                        keyExpr: 'id',

                        showBorders: true,
                        rowAlternationEnabled: true,
                        columnAutoWidth: true,
                        columnHidingEnabled: true,

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

                        // highlight kalau soft delete
                        onRowPrepared: e => {
                            if (e.rowType === "data" && e.data.deleted_at) {
                                $(e.rowElement).addClass("bg-red-200 text-red-800");
                            }
                        },

                        columns: [

                            {
                                caption: 'No',
                                width: 60,
                                alignment: 'center',
                                allowSorting: false,
                                cellTemplate: (c, o) => {
                                    if (o.rowType === "data") {
                                        c.text(o.component.getRowIndexByKey(o.key) + 1);
                                    }
                                }
                            },

                            // ========================
                            // EMPLOYEE
                            // ========================
                            {
                                caption: 'Employee',
                                calculateCellValue: r => r.employee?.empName ?? '-'
                            },

                            // ========================
                            // DEPARTEMENT
                            // ========================
                            {
                                caption: 'Departement',
                                calculateCellValue: r => r.employee?.department?.depNama ?? '-'
                            },

                            // ========================
                            // JABATAN
                            // ========================
                            {
                                caption: 'Jabatan',
                                calculateCellValue: r => r.jabatan?.nama ?? '-'
                            },

                            // ========================
                            // TYPE
                            // ========================
                            {
                                dataField: 'type',
                                caption: 'Type'
                            },

                            // ========================
                            // TAHUN
                            // ========================
                            {
                                dataField: 'tahun_berlaku',
                                caption: 'Tahun'
                            },

                            // ========================
                            // ACTION BUTTON
                            // ========================
                            {
                                caption: 'Actions',
                                alignment: 'center',
                                width: 160,
                                allowSearch: false,

                                cellTemplate: (container, options) => {

                                    const id = options.data.id;
                                    const wrap = $('<div class="flex gap-2 justify-center">');

                                    if (userPermissions.edit) {
                                        $('<button>')
                                            .addClass('p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600')
                                            .html('<i class="fas fa-edit"></i>')
                                            .click(() => openEditModal(id))
                                            .appendTo(wrap);
                                    }

                                    if (userPermissions.delete) {

                                        $('<button>')
                                            .addClass('p-2 bg-red-600 text-white rounded hover:bg-red-700')
                                            .html('<i class="fas fa-trash"></i>')
                                            .click(() => {

                                                Swal.fire({
                                                        title: 'Hapus setting?',
                                                        text: 'Data tidak bisa dikembalikan',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonText: 'Ya hapus'
                                                    })
                                                    .then(r => {

                                                        if (!r.isConfirmed) return;

                                                        $.ajax({

                                                            url: "{{ route('employee_setting.destroy',':id') }}".replace(':id', id),

                                                            type: 'DELETE',

                                                            headers: {
                                                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                                            },

                                                            success: (res) => {
                                                                Swal.fire('Berhasil', res.message, 'success');
                                                                loadTable();
                                                            },

                                                            error: () => {
                                                                Swal.fire('Error', 'Gagal hapus data', 'error');
                                                            }

                                                        });

                                                    });

                                            })
                                            .appendTo(wrap);
                                    }

                                    wrap.appendTo(container);
                                }
                            }

                        ]

                    }).dxDataGrid('instance');

                })
                .catch(err => {
                    console.error("GRID LOAD ERROR", err);
                    Swal.fire('Error', 'Tidak bisa load data', 'error');
                });

        }

        function openEditModal(id) {

            $.ajax({
                url: "{{ route('jabatan.show',':id') }}".replace(':id', id),
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
                        text: 'Gagal mengambil data jabatan.'
                    });

                }

            });

        }



        $('#formCreate').on('submit', function(e) {

            e.preventDefault();

            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const originalText = $btn.html();

            $btn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');

            $.ajax({

                url: "{{ route('employee_setting.store') }}",
                method: "POST",
                data: $form.serialize(),

                success: function(res) {

                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses!',
                        text: res.message
                    }).then(() => {

                        $('#modalCreate').addClass('hidden');
                        $form[0].reset();

                        if (typeof loadTable === 'function') {
                            loadTable();
                        }

                    });

                },

                error: function(xhr) {

                    let error = xhr.responseJSON?.message ?? 'Gagal menyimpan';

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
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

                url: "{{ route('jabatan.update',':id') }}".replace(':id', id),

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