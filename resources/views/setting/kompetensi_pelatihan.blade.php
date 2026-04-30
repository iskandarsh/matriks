<x-app-layout>
    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-extrabold mb-5 text-blue-500 flex items-center space-x-2 drop-shadow-sm">
                        <i class="fas fa-database text-blue-600 animate-pulse"></i>
                        <span>Master Kompetensi Pelatihan</span>

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
    <!-- <div id="modalCreate"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">

        <div class="bg-white dark:bg-gray-800 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">

        
            <div class="px-6 py-4 border-b dark:border-gray-700 flex justify-between items-center">

                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Tambah Kompetensi Pelatihan
                </h2>

                <button onclick="$('#modalCreate').addClass('hidden')"
                    class="text-gray-400 hover:text-red-500 text-xl">
                    ✕
                </button>

            </div>

         
            <form id="formCreate" class="p-6 space-y-5">

                @csrf



              
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Kategori <span class="text-red-500">*</span>
                    </label>

                    <select name="skema" id="skema" required class="w-full border rounded-lg p-2">
                        <option value="">-- Pilih Skema --</option>
                        <option value="umum">Umum</option>
                        <option value="departement">Departement</option>
                        <option value="jabatan">Jabatan</option>
                        <option value="posisi">Posisi</option>
                        <option value="workunit">Workunit</option>
                    </select>
                </div>

                <div id="fieldJabatan" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Jabatan <span class="text-red-500">*</span>
                    </label>

                    <select name="id_jabatan[]" id="selectJabatan" class="w-full" multiple></select>
                </div>



                <div id="fieldPosisi" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Posisi <span class="text-red-500">*</span>
                    </label>

                    <select name="id_posisi[]" id="selectPosisi" class="w-full" multiple></select>
                </div>

  
                <div id="fieldWorkunit" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Workunit <span class="text-red-500">*</span>
                    </label>

                    <select name="id_workunit[]" id="selectWorkunit" class="w-full" multiple></select>
                </div>
        
                <div id="fieldKategori" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Kategori <span class="text-red-500">*</span>
                    </label>

                    <select name="id_kategori"
                        id="selectKategori"
                        class="w-full"></select>

                    <p class="text-xs text-gray-400 mt-1">
                        Pilih kategori kompetensi
                    </p>
                </div>
        
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Kompetensi
                    </label>

                    <select name="id_kompetensi"
                        id="selectKompetensi"

                        class="w-full"></select>

                    <p class="text-xs text-gray-400 mt-1">
                        Kompetensi otomatis sesuai kategori
                    </p>
                </div>


           
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Materi Pelatihan <span class="text-red-500">*</span>
                    </label>

                    <select name="id_materi[]"
                        id="selectMateri"
                        multiple
                        required
                        class="w-full"></select>

                    <p class="text-xs text-gray-400 mt-1">
                        Bisa pilih lebih dari satu materi
                    </p>
                </div>



                <div class="flex justify-end gap-3 pt-4 border-t dark:border-gray-700">

                    <button type="button"
                        onclick="$('#modalCreate').addClass('hidden')"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600
                       text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">

                        Batal
                    </button>

                    <button type="submit"
                        class="px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700
                       text-white font-medium shadow">

                        Simpan Data

                    </button>

                </div>

            </form>

        </div>
    </div> -->

    <div id="modalCreate"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-2 sm:p-4">

        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl
                max-h-[95vh] flex flex-col">

            <!-- HEADER (FIXED) -->
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b flex justify-between items-center shrink-0">
                <h2 class="text-base sm:text-lg font-semibold">Tambah Kompetensi</h2>
                <button onclick="$('#modalCreate').addClass('hidden')"
                    class="text-gray-400 hover:text-red-500 text-lg">
                    ✕
                </button>
            </div>

            <!-- BODY (SCROLLABLE) -->
            <div class="overflow-y-auto px-4 sm:px-6 py-4 space-y-5">

                <form id="formCreate" class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label class="text-sm font-medium">Jabatan</label>
                            <select id="selectJabatan" name="id_jabatan"
                                class="w-full border rounded-lg p-2.5 mt-1"></select>
                        </div>

                        <div>
                            <label class="text-sm font-medium">Posisi</label>
                            <select id="selectPosisi" name="id_posisi"
                                class="w-full border rounded-lg p-2.5 mt-1"></select>
                        </div>

                        <div>
                            <label class="text-sm font-medium">Work Unit</label>
                            <select id="selectWorkunit" name="id_workunit"
                                class="w-full border rounded-lg p-2.5 mt-1"></select>
                        </div>

                        <div>
                            <label class="text-sm font-medium">Kategori</label>
                            <select id="selectKategori" name="id_kategori"
                                class="w-full border rounded-lg p-2.5 mt-1"></select>
                        </div>

                    </div>

                    <!-- AUTO KOMPETENSI -->
                    <div id="kompetensiWrapper" class="mt-4 hidden">
                        <label class="font-semibold text-gray-700">Kompetensi & Penilaian</label>

                        <div id="kompetensiLoading" class="hidden text-sm text-gray-500 mt-2">
                            Loading kompetensi...
                        </div>

                        <div id="kompetensiList" class="space-y-3 mt-3"></div>
                    </div>

                </form>
            </div>

            <!-- FOOTER (FIXED) -->
            <div class="px-4 sm:px-6 py-4 border-t flex flex-col sm:flex-row justify-end gap-3 shrink-0 bg-white">
                <button type="button"
                    onclick="$('#modalCreate').addClass('hidden')"
                    class="w-full sm:w-auto border px-4 py-2 rounded-lg">
                    Batal
                </button>

                <button type="submit" form="formCreate"
                    class="w-full sm:w-auto bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Simpan
                </button>
            </div>

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

                initSelectTraining();

            }, 200);


            // JABATAN
            $('#selectJabatan').select2({
                dropdownParent: $('#modalCreate'),
                placeholder: "Pilih Jabatan",
                theme: "bootstrap-5",
                width: '100%',

                ajax: {
                    url: 'dropdown/jabatan',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });


            // POSISI
            $('#selectPosisi').select2({
                dropdownParent: $('#modalCreate'),
                placeholder: "Pilih Posisi",
                theme: "bootstrap-5",
                width: '100%',


                ajax: {
                    url: 'dropdown/posisi',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });

            // WORKUNIT
            $('#selectWorkunit').select2({
                dropdownParent: $('#modalCreate'),
                placeholder: "Pilih Workunit",
                theme: "bootstrap-5",
                width: '100%',

                ajax: {
                    url: 'dropdown/workunit',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });
            $('#selectKategori').on('change', function() {
                loadKompetensi();
            });



        });


        function loadKompetensi() {
            let kategoriId = $('#selectKategori').val();

            if (!kategoriId) {
                $('#kompetensiWrapper').addClass('hidden');
                $('#kompetensiList').html('');
                return;
            }

            $('#kompetensiWrapper').removeClass('hidden');
            $('#kompetensiLoading').removeClass('hidden');
            $('#kompetensiList').html('');

            $.ajax({
                url: 'ajax/kompetensi',
                method: 'GET',
                data: {
                    kategori_id: kategoriId
                },
                success: function(res) {

                    $('#kompetensiLoading').addClass('hidden');

                    let html = '';

                    if (!res.length) {
                        html = `
                    <div class="text-sm text-gray-500 italic">
                        Tidak ada kompetensi pada kategori ini
                    </div>
                `;
                    }

                    res.forEach(item => {

                        let options = `<option value="">-- Pilih Nilai --</option>`;

                        item.details.forEach(d => {
                            let desc = d.deskripsi ?? '';
                            let shortDesc = desc.length > 40 ? desc.substring(0, 40) + '...' : desc;

                            options += `
                            <option value="${d.id}" title="${desc}">
                                ${d.skala} - ${shortDesc}
                            </option>
                            `;
                        });

                        html += `
                            <div class="p-3 sm:p-4 rounded-xl border bg-white shadow-sm hover:shadow transition">

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                                    <div>
                                        <input type="hidden" name="kompetensi_id[]" value="${item.id}">
                                        <input type="text" value="${item.nama}" readonly
                                            class="w-full border rounded-lg p-2 bg-gray-50 text-sm sm:text-base">
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1 sm:hidden">
                                            Pilih Nilai
                                        </label>

                                        <select name="detail_kompetensi_id[]" required
                                            class="w-full border rounded-lg p-2.5 text-sm sm:text-base
                                                focus:ring-2 focus:ring-blue-500 focus:outline-none
                                                bg-white min-h-[42px]">
                                            ${options}
                                        </select>
                                    </div>

                                </div>

                            </div>
`;
                    });

                    $('#kompetensiList').html(html);
                },
                error: function() {
                    $('#kompetensiLoading').addClass('hidden');
                    $('#kompetensiList').html(`
                <div class="text-red-500 text-sm">
                    Gagal mengambil data kompetensi
                </div>
            `);
                }
            });
        }

        function initSelectTraining() {

            // =========================
            // KATEGORI
            // =========================
            $('#selectKategori').select2({

                theme: "bootstrap-5",
                width: '100%',
                dropdownParent: $('#modalCreate'),
                placeholder: "Pilih kategori",

                ajax: {
                    url: "{{ route('kategori.search') }}",
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


            // =========================
            // KOMPETENSI (TAGGING)
            // =========================
            $('#selectKompetensi').select2({

                theme: "bootstrap-5",
                width: '100%',
                dropdownParent: $('#modalCreate'),
                placeholder: "Pilih / ketik kompetensi",
                tags: true,

                ajax: {
                    url: "{{ route('kompetensi.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term,
                        kategori: $('#selectKategori').val()
                    }),
                    processResults: data => ({
                        results: data
                    })
                }

            });



            // =========================
            // MATERI MULTIPLE
            // =========================
            $('#selectMateri').select2({

                theme: "bootstrap-5",
                width: '100%',
                dropdownParent: $('#modalCreate'),
                placeholder: "Pilih materi pelatihan",
                multiple: true,

                ajax: {
                    url: "{{ route('materi.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term,
                        kompetensi: $('#selectKompetensi').val()
                    }),
                    processResults: data => ({
                        results: data
                    })
                }

            });


            // =========================
            // RESET CHAIN LOGIC
            // =========================

            // kategori berubah → reset kompetensi + materi
            $('#selectKategori').on('change', function() {

                $('#selectKompetensi').val(null).trigger('change');
                $('#selectMateri').val(null).trigger('change');

            });

            // kompetensi berubah → reset materi
            $('#selectKompetensi').on('change', function() {

                $('#selectMateri').val(null).trigger('change');

            });

        }


        let gridInstance = null;
        const badgeClass = "inline-block rounded px-2 py-1 text-xs font-semibold";

        function loadTable() {
            const gridId = 'grid';
            const $container = $('#' + gridId);

            fetch("{{ route('kompetensi_pelatihan.data') }}")
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

                        columns: [{
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
                                        if (visibleRows[i].rowType === "data") {
                                            index++;
                                        }
                                        if (visibleRows[i].key === options.key) {
                                            container.text(index);
                                            break;
                                        }
                                    }
                                }
                            },
                            {
                                caption: 'Kategori',
                                dataField: 'kategori.nama',
                                calculateCellValue: row => row.kategori?.nama ?? '-',
                                groupIndex: 0
                            },
                            {
                                caption: 'Kompetensi',
                                dataField: 'kompetensi.nama',
                                calculateCellValue: row => row.kompetensi?.nama ?? '-',
                                groupIndex: 1
                            },
                            {
                                caption: 'Departement',
                                dataField: 'departement.depNama', // Simplifies mapping
                                calculateCellValue: row => row.departement?.depNama ?? '-',
                                // groupIndex: 0 // Uncomment to group by default
                            }, {
                                caption: 'Jabatan',
                                dataField: 'peran.nama',
                                calculateCellValue: row => row.posisi?.posiNama ?? '-'
                            },
                            {
                                caption: 'Posisi',
                                dataField: 'posisi.nama',
                                calculateCellValue: row => row.peran?.name ?? '-'
                            },
                            {
                                caption: 'Workunit',
                                dataField: 'workunit.nama',
                                calculateCellValue: row => row.workunit?.woruNama ?? '-'
                            },
                            {
                                caption: 'Materi',
                                dataField: 'materi.title',
                                calculateCellValue: row => row.materi?.title ?? '-'
                            },
                            {
                                caption: 'Actions',
                                alignment: 'center',
                                width: 120,
                                allowGrouping: false,
                                allowSearch: false, // Prevents searching HTML buttons
                                cellTemplate(container, options) {
                                    const id = options.data.id;
                                    const $wrapper = $('<div>').addClass("flex gap-2 justify-center");

                                    // if (userPermissions.edit) {
                                    //     $('<button>')
                                    //         .addClass('p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition')
                                    //         .html('<i class="fas fa-edit"></i>')
                                    //         .on('click', (e) => {
                                    //             e.stopPropagation(); // Prevents row selection
                                    //             openEditModal(id);
                                    //         })
                                    //         .appendTo($wrapper);
                                    // }

                                    if (userPermissions.delete) {

                                        $('<button>')
                                            .addClass('p-2 bg-red-600 text-white rounded hover:bg-red-700 transition')
                                            .attr('title', 'Delete')
                                            .html('<i class="fas fa-trash"></i>')
                                            .on('click', e => {

                                                e.stopPropagation();


                                                const $btn = $(e.currentTarget);
                                                const oldHtml = $btn.html();

                                                // loading state
                                                $btn.prop('disabled', true)
                                                    .html('<i class="fas fa-spinner fa-spin"></i>');

                                                deleteData(id)
                                                    .finally(() => {
                                                        $btn.prop('disabled', false).html(oldHtml);
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

            Swal.fire({
                title: 'Hapus data?',
                text: 'Data yang sudah dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6b7280'
            }).then((result) => {

                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Menghapus...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`ikompetensi_pelatihan/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(res => {

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message ?? 'Data berhasil dihapus',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        loadTable(); // reload grid
                    })
                    .catch(() => {

                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Gagal menghapus data'
                        });

                    });

            });

        }

        $('#formCreate').submit(function(e) {

            e.preventDefault();

            const form = this;

            Swal.fire({
                title: 'Simpan data?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal'
            }).then((result) => {

                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({

                    url: "ikompetensi_pelatihan",
                    type: "POST",
                    data: $(form).serialize(),

                    success: function(res) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message ?? 'Data berhasil disimpan',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        $('#modalCreate').addClass('hidden');

                        form.reset();

                        $('#selectKategori').val(null).trigger('change');
                        $('#selectKompetensi').val(null).trigger('change');
                        $('#selectMateri').val(null).trigger('change');
                        $('#fieldJabatan').addClass('hidden');
                        $('#selectJabatan').val(null).trigger('change');
                        $('#fieldPosisi').addClass('hidden');
                        $('#selectPosisi').val(null).trigger('change');

                        $('#fieldWorkunit').addClass('hidden');
                        $('#selectWorkunit').val(null).trigger('change');
                        if (typeof loadTable === 'function') {
                            loadTable();
                        }

                    },

                    error: function(xhr) {

                        let msg = 'Gagal menyimpan';

                        if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }

                        if (xhr.responseJSON?.errors) {

                            msg = Object.values(xhr.responseJSON.errors)
                                .flat()
                                .join('\n');
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: msg
                        });

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

        $('#skema').change(function() {

            let skema = $(this).val();

            $('#fieldJabatan').addClass('hidden');
            $('#fieldPosisi').addClass('hidden');
            $('#fieldWorkunit').addClass('hidden');
            $('#fieldKategori').addClass('hidden');
            if (skema != 'umum') {
                $('#fieldKategori').removeClass('hidden');
            }
            if (skema == 'jabatan') {
                $('#fieldJabatan').removeClass('hidden');
            }

            if (skema == 'posisi') {
                $('#fieldPosisi').removeClass('hidden');
            }

            if (skema == 'workunit') {
                $('#fieldWorkunit').removeClass('hidden');
            }

        });
    </script>



</x-app-layout>