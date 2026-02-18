<x-app-layout>

    <div class="py-12 max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 p-6 rounded shadow-md">
            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">Create User dari Employee</h2>

            <form id="createUserForm" class="mb-6">
                <label for="employeeSelect" class="block mb-2 font-semibold text-gray-700 dark:text-gray-300">
                    Pilih Employee (belum punya user):
                </label>
                <div class="flex space-x-4">
                    <select id="employeeSelect" name="empToken" class="flex-grow rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        {{-- options akan di-load AJAX --}}
                    </select>

                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Create
                    </button>
                </div>
            </form>



            <div id="message" class="mt-4 text-red-600"></div>
        </div>

        <div class="mt-12 bg-white dark:bg-gray-800 p-6 rounded shadow-md">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">Employee yang sudah punya User</h3>
            <div id="employeesGrid"></div>
        </div>

    </div>

    <!-- Modal -->
    <div id="departemenModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl p-6 shadow-xl border dark:border-gray-700 relative animate-fade-in-up">

                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">
                    Setting Level User
                </h2>

                <form id="departemenForm" class="space-y-4">
                    <input type="hidden" id="modalUserId" name="user_id">

                    <!-- Input Level User -->
                    <div>
                        <label for="userLevel" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Level User
                        </label>
                        <input
                            type="number"
                            id="userLevel"
                            name="level"
                            min="1"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition"
                            placeholder="Masukkan level user (contoh: 1, 2, 3)">
                    </div>

                    <!-- Daftar Departemen -->
                    <div id="departemenCheckboxList" class="space-y-3 text-sm">
                        <!-- Checkbox akan disisipkan di sini -->
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t dark:border-gray-700">
                        <button
                            type="button"
                            onclick="$('#departemenModal').addClass('hidden')"
                            class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Tutup
                        </button>

                        <button
                            type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>





    <!-- THEME SCRIPT -->
    <script>
        const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const lightTheme = document.getElementById('dx-theme-light');
        const darkTheme = document.getElementById('dx-theme-dark');
        const education_levels = <?= json_encode($educationLevels) ?>;
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

            // Inisialisasi Select2 dengan tema Bootstrap 5
            $('#employeeSelect').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Pilih Employee --',
                // allowClear: true,
                width: '100%'
            });

            // Load employee tanpa user
            function loadEmployeesWithoutUser() {
                $.getJSON("{{ url('/users/employees-without-user') }}", function(data) {
                    let options = '<option value=""></option>'; // kosong untuk placeholder
                    $.each(data, function(i, emp) {
                        let dept = emp.department ? ` (${emp.department})` : '';
                        options += `<option value="${emp.empToken}">${emp.name}${dept}</option>`;
                    });
                    $('#employeeSelect').html(options).trigger('change');
                });
            }



            $(function() {
                $("#employeesGrid").dxDataGrid({
                    dataSource: new DevExpress.data.CustomStore({
                        key: "user_id",
                        load: function(loadOptions) {
                            const params = {};

                            // Kirim parameter paging, sorting, filter jika perlu
                            [
                                "skip", "take", "sort", "filter"
                            ].forEach(function(param) {
                                if (loadOptions[param] !== undefined && loadOptions[param] !== null) {
                                    params[param] = JSON.stringify(loadOptions[param]);
                                }
                            });

                            return $.ajax({
                                url: "{{ url('/users/employees-with-user') }}",
                                dataType: "json",
                                method: "GET",
                                data: params
                            });
                        }
                    }),
                    keyExpr: "user_id",
                    paging: {
                        pageSize: 10
                    },
                    pager: {
                        showPageSizeSelector: true,
                        allowedPageSizes: [5, 10, 20],
                        showInfo: true
                    },
                    // filterRow: {
                    //     visible: true,
                    //     applyFilter: "auto"
                    // },
                    groupPanel: {
                        visible: true
                    },
                    grouping: {
                        autoExpandAll: false
                    },
                    searchPanel: {
                        visible: true,
                        width: 240,
                        placeholder: "Search..."
                    },
                    sorting: {
                        mode: "multiple"
                    },
                    columns: [{
                            dataField: "user_id",
                            caption: "No",
                            width: 50,
                            allowFiltering: false,
                            allowSorting: false,
                            cellTemplate: function(container, options) {
                                container.text(options.component.pageIndex() * options.component.pageSize() + options.rowIndex + 1);
                            }
                        },
                        {
                            dataField: "name",
                            caption: "Nama"
                        },
                        {
                            dataField: "email",
                            caption: "Email"
                        },
                        {
                            dataField: "work_unit",
                            caption: "Work Unit"
                        },
                        {
                            dataField: "departments",
                            caption: "Departemen",
                            calculateCellValue: function(data) {
                                return Array.isArray(data.departments) ? data.departments.join(', ') : "";
                            }
                        },
                        {
                            dataField: "position",
                            caption: "Posisi"
                        },
                        {
                            dataField: "status",
                            caption: "Status",
                            cellTemplate: function(container, options) {
                                container.text(options.data.status == 1 ? "Aktif" : "Inaktif");
                            }
                        },
                        {
                            caption: "Action",
                            width: 80,
                            alignment: "center",
                            cellTemplate: function(container, options) {
                                $("<button>")
                                    .addClass("text-blue-600 hover:text-blue-800 text-lg")
                                    .attr("title", "Setting Departemen")
                                    .html('<i class="fas fa-cog"></i>') // Font Awesome gear icon
                                    .on("click", function() {
                                        const userId = options.data.user_id;
                                        openSettingDepartemenModal(userId);
                                    })
                                    .appendTo(container);
                            }
                        }


                    ],
                    // showBorders: true,
                    rowAlternationEnabled: true,
                    height: 500
                });
            });


            function loadEmployeesWithUser() {
                const grid = $("#employeesGrid").dxDataGrid("instance");
                if (grid) {
                    grid.refresh(); // reload datanya dari server
                }
            }


            loadEmployeesWithoutUser();
            // loadEmployeesWithUser();

            // Submit create user
            $('#createUserForm').submit(function(e) {
                e.preventDefault();

                const empToken = $('#employeeSelect').val();
                if (!empToken) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Oops!',
                        text: 'Pilih employee dulu!',
                    });
                    return;
                }

                Swal.showLoading(); // Opsional: tampilkan loading sementara

                $.ajax({
                    url: "{{ url('/users/store-user') }}",
                    method: 'POST',
                    data: {
                        empToken: empToken,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'User berhasil dibuat.',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // Reset & reload
                            loadEmployeesWithoutUser();
                            loadEmployeesWithUser();
                            $('#employeeSelect').val(null).trigger('change');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: res.message ?? 'Terjadi kesalahan saat menyimpan user.',
                            });
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.error ?? 'Terjadi error saat menghubungi server.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: msg
                        });
                    }
                });
            });


            function openSettingDepartemenModal(userId) {
                $('#modalUserId').val(userId);
                $('#departemenCheckboxList').empty();

                const url = "{{ route('api.user.departments', ['id' => ':id']) }}".replace(':id', userId);

                $.ajax({
                    url: url,
                    method: "GET",
                    success: function(response) {
                        $('#userLevel').val(response.level ?? '');
                        response.departments.forEach(function(dep) {
                            const checked = dep.assigned ? 'checked' : '';
                            $('#departemenCheckboxList').append(`
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="departments[]" value="${dep.id}" ${checked}>
                            <span>${dep.name}</span>
                        </label>
                    `);
                        });
                        $('#departemenModal').removeClass('hidden');
                    }
                });
            }



            $('#departemenForm').on('submit', function(e) {
                e.preventDefault();

                const userId = $('#modalUserId').val();
                const level = $('#userLevel').val(); // ambil nilai level user
                const selectedDepartments = $(this).serialize(); // serialize semua input checkbox dan hidden

                const $submitBtn = $('#departemenForm button[type="submit"]');

                // Buat URL dari route name
                const url = "{{ route('api.user.departments.update', ['id' => ':id']) }}".replace(':id', userId);

                // Tampilkan loading
                $submitBtn.prop('disabled', true).html('⏳ Menyimpan...');

                // Gabungkan data level + data checkbox
                const dataToSend = selectedDepartments + '&level=' + encodeURIComponent(level);

                $.ajax({
                    url: url,
                    method: "POST",
                    data: dataToSend,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Level user & departemen berhasil diupdate!',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Tutup modal
                        $('#departemenModal').addClass('hidden');

                        // Refresh list kalau ada
                        loadEmployeesWithoutUser?.();
                        loadEmployeesWithUser?.();
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal menyimpan data. Silakan coba lagi.',
                        });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).html('Simpan');
                    }
                });
            });




        });
    </script>
</x-app-layout>