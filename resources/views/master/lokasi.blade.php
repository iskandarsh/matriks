<x-app-layout>
    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-extrabold mb-5 text-blue-500 flex items-center space-x-2 drop-shadow-sm">
                        <i class="fas fa-database text-blue-600 animate-pulse"></i>
                        <span>Master Lokasi</span>
                    </h2>

                    <div id="applicantTabs" x-data="{
                        loadTable() {
                            this.$nextTick(() => {
                                window.loadTable();
                            });
                        }
                    }">

                        <div class="mb-4 flex gap-2">
                            @can('create', [App\Models\Lokasi::class, session('active_menu_id')])
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

    <!-- Modal Create Lokasi -->
    <div id="modalCreate" class="hidden fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm p-4 dark:bg-gray-900/90">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-full max-w-2xl">
            <form id="formCreateLokasi" method="POST" class="space-y-6">
                @csrf
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    🧭 Tambah Lokasi Baru
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- lokaNomor -->
                    <div>
                        <label for="lokaNomor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nomor Lokasi <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="lokaNomor"
                            name="lokaNomor"
                            type="text"
                            maxlength="10"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            placeholder="Contoh: LOC001"
                            required>
                    </div>

                    <!-- lokaNama -->
                    <div>
                        <label for="lokaNama" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nama Lokasi <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="lokaNama"
                            name="lokaNama"
                            type="text"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            placeholder="Contoh: Ruang Admin Lantai 2"
                            required>
                    </div>

                    <!-- lokaLevel -->
                    <div>
                        <label for="lokaLevel" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Level / Lantai
                        </label>
                        <input
                            id="lokaLevel"
                            name="lokaLevel"
                            type="number"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            placeholder="Contoh: Lantai 2">
                    </div>

                    <!-- gedung_id -->
                    <div>
                        <label for="gedung_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Gedung <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="gedung_id"
                            name="gedung_id"
                            class="select2 w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            required>
                        </select>

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
                        💾 Simpan Lokasi
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal Edit Position -->
    <div id="modalEdit" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-blue-950/60 backdrop-blur-sm p-4 dark:bg-gray-900/90">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-full max-w-xl">
            <form id="formEditLokasi" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id" name="id">

                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    ✏️ Edit Lokasi
                </h2>

                <div class="space-y-4">
                    <!-- Nomor Lokasi -->
                    <div>
                        <label for="edit_lokaNomor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nomor Lokasi <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="edit_lokaNomor"
                            name="lokaNomor"
                            type="text"
                            maxlength="10"
                            oninput="this.value = this.value.toUpperCase()"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            placeholder="Contoh: LOC001"
                            required>
                    </div>

                    <!-- Nama Lokasi -->
                    <div>
                        <label for="edit_lokaNama" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nama Lokasi <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="edit_lokaNama"
                            name="lokaNama"
                            type="text"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            placeholder="Misal: Lantai 1, Ruang IT"
                            required>
                    </div>

                    <!-- Level -->
                    <div>
                        <label for="edit_lokaLevel" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Level (Opsional)
                        </label>
                        <input
                            id="edit_lokaLevel"
                            name="lokaLevel"
                            type="number"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            placeholder="Misal: 1, 2, 3">
                    </div>

                    <!-- Gedung -->
                    <div>
                        <label for="edit_gedung_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Gedung <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="edit_gedung_id"
                            name="gedung_id"
                            class="select2 w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 text-gray-800 dark:text-gray-100"
                            required>
                            <option value="">Pilih Gedung</option>
                            <!-- Ajax Select2 -->
                        </select>
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

    <!-- Modal Proses -->
    <div id="processModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-[400px] p-5">
            <!-- Title -->
            <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-100">Pilih Departemen</h2>

            <!-- Form -->
            <form id="formProcess" method="POST">
                @csrf

                <!-- Hidden ID Lokasi -->
                <input type="hidden" name="processId" id="processId">

                <!-- Hidden Departemen -->
                <input type="hidden" name="departments" id="selectedDepartId">

                <!-- List Departemen -->
                <div id="departmentList" class="max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 p-2 rounded mb-4 bg-gray-50 dark:bg-gray-700">
                    <p class="text-gray-500 dark:text-gray-300">Loading...</p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2">
                    <button type="button"
                        onclick="$('#processModal').addClass('hidden')"
                        class="px-4 py-2 bg-gray-400 dark:bg-gray-600 rounded hover:bg-gray-500 dark:hover:bg-gray-500 text-white">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 dark:bg-blue-500 rounded hover:bg-blue-700 dark:hover:bg-blue-600 text-white">
                        Proses
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
                $('#formCreateLokasi')[0].reset();
            });

            // Submit form via AJAX
            $('#formCreateLokasi').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const originalText = $btn.html();

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');

                $.ajax({
                    url: "{{ route('lokasis.store') }}", // ✅ Ganti sesuai route store lokasi
                    method: "POST",
                    data: $form.serialize(),
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sukses!',
                            text: 'Data Lokasi berhasil disimpan.',
                            confirmButtonColor: '#2563eb',
                            customClass: {
                                popup: 'rounded-xl',
                                confirmButton: 'px-6 py-2',
                            }
                        }).then(() => {
                            $('#modalCreate').addClass('hidden').removeClass('flex'); // ✅ modal ID lokasi
                            $form[0].reset();

                            if (typeof loadTable === 'function') {
                                loadTable(window.currentTab ?? null); // ✅ reload datatable
                            }

                            // Jika pakai select2 dan ingin reset gedung_id
                            $('#gedung_id').val(null).trigger('change');
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

            $('#formEditLokasi').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const id = $('#edit_id').val();
                const $btn = $form.find('button[type="submit"]');
                const originalText = $btn.html();

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Updating...');

                $.ajax({
                    url: "{{ route('lokasis.update', ['lokasi' => ':id']) }}".replace(':id', id),
                    method: 'POST',
                    data: $form.serialize(),
                    headers: {
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data Lokasi berhasil diperbarui.',
                            confirmButtonColor: '#2563eb',
                        }).then(() => {
                            $('#modalEdit').addClass('hidden');
                            $form[0].reset();

                            // Reset Select2 jika digunakan
                            $('#edit_gedung_id').val(null).trigger('change');

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

            $('#formProcess').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: '{{ route("process.lokasidepar") }}', // ganti sesuai route Laravel
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data berhasil diproses!',
                            showConfirmButton: false,
                            timer: 1500
                        });

                        $('#processModal').addClass('hidden');

                        // refresh table jika pakai DataTables
                        if (typeof table !== 'undefined') table.ajax.reload();
                    },
                    error: function(err) {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Terjadi kesalahan saat memproses data.',
                        });
                    }
                });
            });


            $('#gedung_id').select2({
                theme: 'bootstrap-5', // jika pakai theme, opsional
                placeholder: 'Pilih Gedung',
                ajax: {
                    url: '{{ route("dropdown.gedungs") }}', // pastikan route ini tersedia
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term // keyword pencarian
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: `${item.geduKode} - ${item.geduNama}`
                            }))
                        };
                    },
                    cache: true
                }
            });



        });

        let gridInstance = null;
        const badgeClass = "inline-block rounded px-2 py-1 text-xs font-semibold";

        function loadTable() {
            const gridId = 'grid';
            const $container = $('#' + gridId);

            fetch("{{ route('lokasis.data') }}")
                .then(res => res.json())
                .then(data => {
                    const lokasis = data.lokasis;
                    const userPermissions = data.permissions || {};

                    if (gridInstance) {
                        $container.dxDataGrid('dispose');
                        $container.empty();
                    }

                    gridInstance = $container.dxDataGrid({
                        dataSource: lokasis,
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
                                dataField: 'lokaNomor',
                                caption: 'Nomor Lokasi',
                                alignment: 'center',
                                cellTemplate: function(container, options) {
                                    container
                                        .addClass("px-3 py-2 text-sm text-gray-800 dark:text-gray-100 text-center border border-gray-200 dark:border-gray-600")
                                        .text(options.value || '-');
                                }
                            },
                            {
                                dataField: 'lokaNama',
                                caption: 'Nama Lokasi',
                                alignment: 'left',
                                cellTemplate: function(container, options) {
                                    container
                                        .addClass("px-3 py-2 text-sm text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600")
                                        .text(options.value || '-');
                                }
                            },
                            {
                                dataField: 'lokaLevel',
                                caption: 'Level',
                                alignment: 'center',
                                cellTemplate: function(container, options) {
                                    container
                                        .addClass("px-3 py-2 text-sm text-gray-800 dark:text-gray-100 text-center border border-gray-200 dark:border-gray-600")
                                        .text(options.value || '-');
                                }
                            },
                            {
                                dataField: 'gedung.geduNama',
                                caption: 'Gedung',
                                alignment: 'left',
                                cellTemplate: function(container, options) {
                                    container
                                        .addClass("px-3 py-2 text-sm text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600")
                                        .text(options.data.gedung?.geduNama || '-');
                                }
                            },
                            {
                                caption: 'Actions',
                                alignment: 'center',
                                width: 180,
                                cellTemplate: function(container, options) {
                                    container.addClass("flex space-x-2 justify-center items-center");
                                    const id = options.data.id;

                                    if (userPermissions.process) {
                                        $('<button>')
                                            .attr('data-id', id)
                                            .attr('title', 'Proses')
                                            .addClass('p-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition')
                                            .html('<i class="fas fa-cogs"></i>')
                                            .on('click', function() {
                                                const dataId = $(this).data('id');
                                                $('#processId').val(dataId);

                                                $('#departmentList').html('<p class="text-gray-500">Loading...</p>');

                                                // Ambil data department + selected
                                                $.get('{{ route("dropdown.departemenlokasi") }}', {
                                                    lokasi_id: dataId
                                                }, function(res) {
                                                    let html = '<div class="grid grid-cols-3 gap-2">';
                                                    res.departments.forEach(dep => {
                                                        let checked = res.selected.includes(dep.id) ? 'checked' : '';
                                                        html += `
                                                            <label class="flex items-center gap-2">
                                                                <input type="checkbox" 
                                                                    name="departments[]" 
                                                                    value="${dep.id}" 
                                                                    class="form-checkbox"
                                                                    ${checked}>
                                                                <span>${dep.text}</span>
                                                            </label>
                                                        `;
                                                    });
                                                    html += '</div>';
                                                    $('#departmentList').html(html);
                                                });

                                                $('#processModal').removeClass('hidden');
                                            })
                                            .appendTo(container);
                                    }



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
                                                            url: `/lokasis/${id}`,
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
                    console.error('Gagal mengambil data lokasis:', err);
                });
        }


        function openEditModal(id) {
            $.ajax({
                url: "{{ route('lokasis.show', ['lokasi' => ':id']) }}".replace(':id', id),
                type: 'GET',
                success: function(data) {
                    $('#edit_id').val(data.id);
                    $('#edit_lokaNomor').val(data.lokaNomor);
                    $('#edit_lokaNama').val(data.lokaNama);
                    $('#edit_lokaLevel').val(data.lokaLevel);

                    $('#edit_gedung_id').select2({
                        ajax: {
                            url: "{{ route('dropdown.gedungs') }}",
                            dataType: 'json',
                            delay: 250,
                            processResults: function(data) {
                                return {
                                    results: data.map(item => ({
                                        id: item.id,
                                        text: `${item.geduKode} - ${item.geduNama}`
                                    }))
                                };
                            },
                        },
                        theme: 'bootstrap-5',
                        placeholder: 'Pilih Gedung',
                        dropdownParent: $('#modalEdit')
                    });
                    // untuk select2 ajax
                    if (data.gedung) {
                        const option = new Option(`${data.gedung.geduKode} - ${data.gedung.geduNama}`, data.gedung.id, true, true);
                        $('#edit_gedung_id').append(option).trigger('change');
                    }

                    $('#modalEdit').removeClass('hidden').addClass('flex');
                },
                error: function() {
                    alert('Gagal mengambil data Lokasi.');
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