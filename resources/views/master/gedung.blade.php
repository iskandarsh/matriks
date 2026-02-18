<x-app-layout>
    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-extrabold mb-5 text-blue-500 flex items-center space-x-2 drop-shadow-sm">
                        <i class="fas fa-database text-blue-600 animate-pulse"></i>
                        <span>Master Gedung</span>
                    </h2>

                    <div id="applicantTabs" x-data="{
                        loadTable() {
                            this.$nextTick(() => {
                                window.loadTable();
                            });
                        }
                    }">

                        <div class="mb-4 flex gap-2">
                            @can('create', [App\Models\Gedung::class, session('active_menu_id')])
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

    <!-- Modal Create Gedung -->
    <div
        id="modalCreate"
        class="hidden fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm p-4 dark:bg-gray-900/90">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-full max-w-2xl">
            <form id="formCreate" method="POST" class="space-y-6">
                @csrf
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    🧩 Tambah Gedung Baru
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- geduKode -->
                    <div>
                        <label for="geduKode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Kode Gedung <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="geduKode"
                            name="geduKode"
                            type="text"
                            maxlength="5"
                            oninput="this.value = this.value.toUpperCase()"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            placeholder="Contoh: GD01"
                            required>
                    </div>

                    <!-- geduNama -->
                    <div>
                        <label for="geduNama" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nama Gedung <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="geduNama"
                            name="geduNama"
                            type="text"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            placeholder="Contoh: Gedung Administrasi"
                            required>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex justify-end items-center gap-4 pt-6 border-t mt-6 border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        onclick="document.getElementById('modalCreate').classList.add('hidden')"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition">
                        ❌ Batal
                    </button>
                    <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition focus:outline-none focus:ring-2 focus:ring-blue-500">
                        💾 Simpan Gedung
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Position -->
    <div id="modalEdit" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-blue-950/60 backdrop-blur-sm p-4 dark:bg-gray-900/90">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-full max-w-xl">
            <form id="formEditGedung" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id" name="id">

                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    ✏️ Edit Gedung
                </h2>

                <div class="space-y-4">
                    <!-- geduKode -->
                    <div>
                        <label for="edit_geduKode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Kode Gedung <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="edit_geduKode"
                            name="geduKode"
                            type="text"
                            maxlength="5"
                            oninput="this.value = this.value.toUpperCase()"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            placeholder="Misal: G01, GD2, BLT"
                            required>
                    </div>

                    <!-- geduNama -->
                    <div>
                        <label for="edit_geduNama" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nama Gedung <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="edit_geduNama"
                            name="geduNama"
                            type="text"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            placeholder="Misal: Gedung Serbaguna, Lab Komputer"
                            required>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-4 pt-6 border-t mt-6 border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        onclick="document.getElementById('modalEdit').classList.add('hidden')"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition">
                        ❌ Batal
                    </button>
                    <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition">
                        💾 Simpan Perubahan
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

    <!-- LOAD TABLE SCRIPT -->
    <script>
        $(document).ready(function() {





            // Show modal
            $('#btnCreate').on('click', function() {
                $('#modalCreate').removeClass('hidden').addClass('flex');
            });

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });


            // Hide modal
            $('#btnCancel').on('click', function() {
                $('#modalCreate').addClass('hidden').removeClass('flex');
                $('#formCreate')[0].reset();
            });

            // Submit form via AJAX
            $('#formCreate').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const originalText = $btn.html();

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');

                $.ajax({
                    url: "{{ route('gedungs.store') }}", // ✅ ganti ke route gedungs
                    method: "POST",
                    data: $form.serialize(),
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sukses!',
                            text: 'Data Gedung berhasil disimpan.',
                            confirmButtonColor: '#2563eb',
                            customClass: {
                                popup: 'rounded-xl',
                                confirmButton: 'px-6 py-2',
                            }
                        }).then(() => {
                            $('#modalCreate').addClass('hidden').removeClass('flex');
                            $form[0].reset();

                            if (typeof loadTable === 'function') {
                                loadTable(window.currentTab ?? null); // ✅ reload datatable
                            }
                        });
                    },
                    error: function(xhr) {
                        let error = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: error,
                            confirmButtonColor: '#dc2626',
                            customClass: {
                                popup: 'rounded-xl',
                                confirmButton: 'px-6 py-2',
                            }
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });


            $('#edit_depid').select2({
                placeholder: 'Pilih Departemen',
                theme: 'bootstrap-5',
                ajax: {
                    url: "{{ route('dropdown.departemen') }}", // Ganti sesuai route departemen kamu
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term
                    }),
                    processResults: data => ({
                        results: data
                    }),
                    cache: true
                },
                dropdownParent: $('#modalEdit')
            });

            $('#formEditGedung').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const id = $('#edit_id').val();
                const $btn = $form.find('button[type="submit"]');
                const originalText = $btn.html();

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Updating...');

                $.ajax({
                    url: "{{ route('gedungs.update', ['gedung' => ':id']) }}".replace(':id', id),
                    method: 'POST',
                    data: $form.serialize(),
                    headers: {
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data Gedung berhasil diperbarui.',
                            confirmButtonColor: '#2563eb',
                        }).then(() => {
                            $('#modalEdit').addClass('hidden');
                            $form[0].reset();
                            if (typeof loadTable === 'function') loadTable();
                        });
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.message ?? 'Gagal memperbarui data!';
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops!',
                            text: error,
                            confirmButtonColor: '#dc2626',
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });





        });

        let gridInstance = null;
        const badgeClass = "inline-block rounded px-2 py-1 text-xs font-semibold";

        function loadTable() {
            const gridId = 'grid';
            const $container = $('#' + gridId);

            fetch("{{ route('gedungs.data') }}")
                .then(res => res.json())
                .then(data => {
                    const gedungs = data.gedungs;
                    const userPermissions = data.permissions || {};

                    if (gridInstance) {
                        $container.dxDataGrid('dispose');
                        $container.empty();
                    }

                    gridInstance = $container.dxDataGrid({
                        dataSource: gedungs,
                        keyExpr: 'id',
                        // showBorders: true,
                        rowAlternationEnabled: true,
                        columnAutoWidth: true,
                        // wordWrapEnabled: true,
                        columnHidingEnabled: true,
                        allowColumnReordering: false,

                        searchPanel: {
                            visible: true,
                            highlightCaseSensitive: false,
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

                        columns: [{
                                caption: 'No',
                                width: 50,
                                alignment: 'center',
                                cellTemplate: function(container, options) {
                                    const pageIndex = gridInstance.pageIndex ? gridInstance.pageIndex() : 0;
                                    const pageSize = gridInstance.pageSize ? gridInstance.pageSize() : 10;
                                    const index = options.rowIndex + 1 + (pageIndex * pageSize);
                                    container
                                        .addClass("px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 text-center border border-gray-200 dark:border-gray-600")
                                        .text(index);
                                }
                            },
                            {
                                dataField: 'geduKode',
                                caption: 'Kode',
                                alignment: 'center',
                                cellTemplate: function(container, options) {
                                    container
                                        .addClass("px-3 py-2 text-sm text-gray-800 dark:text-gray-100 text-center border border-gray-200 dark:border-gray-600")
                                        .text(options.value || '-');
                                }
                            },
                            {
                                dataField: 'geduNama',
                                caption: 'Nama Gedung',
                                alignment: 'left',
                                cellTemplate: function(container, options) {
                                    container
                                        .addClass("px-3 py-2 text-sm text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600")
                                        .text(options.value || '-');
                                }
                            },
                            {
                                caption: 'Actions',
                                alignment: 'center',
                                width: 180,
                                cellTemplate: function(container, options) {
                                    container.addClass("flex space-x-2 justify-center items-center");

                                    const id = options.data.id;

                                    if (userPermissions.edit) {
                                        $('<button>')
                                            .attr('data-id', id)
                                            .attr('title', 'Edit')
                                            .addClass('p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 dark:hover:bg-yellow-700 transition')
                                            .html('<i class="fas fa-edit"></i>')
                                            .on('click', function() {
                                                openEditModal(id);
                                            })
                                            .appendTo(container);
                                    }

                                    if (userPermissions.delete) {
                                        $('<button>')
                                            .attr('data-id', id)
                                            .attr('title', 'Delete')
                                            .addClass('p-2 bg-red-600 text-white rounded hover:bg-red-700 transition')
                                            .html('<i class="fas fa-trash-alt"></i>')
                                            .on('click', function() {
                                                Swal.fire({
                                                    title: 'Yakin hapus?',
                                                    text: 'Data tidak bisa dikembalikan!',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#d33',
                                                    cancelButtonColor: '#3085d6',
                                                    confirmButtonText: 'Ya, hapus!'
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $.ajax({
                                                            url: "{{ route('gedungs.destroy', ['gedung' => ':id']) }}".replace(':id', id),
                                                            type: 'DELETE',
                                                            data: {
                                                                _token: $('meta[name="csrf-token"]').attr('content')
                                                            },
                                                            success: function(response) {
                                                                Swal.fire('Berhasil!', response.message, 'success');
                                                                loadTable();
                                                            },
                                                            error: function() {
                                                                Swal.fire('Gagal!', 'Gagal menghapus data.', 'error');
                                                            }
                                                        });
                                                    }
                                                });
                                            })
                                            .appendTo(container);
                                    }
                                }
                            }
                        ],

                        columnChooser: {
                            enabled: true,
                            mode: 'select',
                            title: 'Pilih Kolom',
                            height: 400
                        },

                    }).dxDataGrid('instance');
                })
                .catch(err => {
                    console.error('Failed to load gedungs:', err);
                });
        }



        function openEditModal(id) {
            $.ajax({
                url: "{{ route('gedungs.show', ['gedung' => ':id']) }}".replace(':id', id),
                type: 'GET',
                success: function(data) {
                    $('#edit_id').val(data.id);
                    $('#edit_geduKode').val(data.geduKode);
                    $('#edit_geduNama').val(data.geduNama);
                    $('#modalEdit').removeClass('hidden').addClass('flex'); // pastikan modal terlihat
                },
                error: function() {
                    alert('Gagal mengambil data Gedung.');
                }
            });
        }






        window.addEventListener('load', () => {
            //  const container = document.getElementById('applicantTabs');
            //     if (container && container.__x) {
            //         alert('Current tab is: ' + container.__x.$data.tab);
            //     } else {
            //         alert('Alpine instance not found');
            //     }
            loadTable('0'); // default tab "Baru"

        });
    </script>
</x-app-layout>