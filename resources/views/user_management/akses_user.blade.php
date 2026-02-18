<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('User Permission Table') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <!-- Menu Filter Dropdown -->
            <div>
                <label for="menu-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter by Menu</label>
                <select id="menu-filter" class="w-full select2-menu bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-700 rounded-md">
                    @foreach($menus_akses as $menu)
                    <option value="{{ $menu->id }}">
                        {{ $menu->parent ? $menu->parent->name . ' - ' : '' }}{{ $menu->name }}
                    </option>
                    @endforeach

                </select>
            </div>

            <div id="user-grid-container" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 overflow-x-auto">
                <div id="userGridContainer"></div>

            </div>


        </div>
    </div>

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
            // Initialize Select2
            $('#menu-filter').select2({
                theme: 'bootstrap-5'
            });




            let cachedPermissions = []; // cache global untuk simpan permissions

            function initializeDevExtremeGrid(menuId = '') {
                $("#userGridContainer").dxDataGrid({
                    dataSource: new DevExpress.data.CustomStore({
                        load: function(loadOptions) {
                            let params = {
                                menu_id: $('#menu-filter').val() || '',
                                draw: 1,
                                start: loadOptions.skip || 0,
                                length: loadOptions.take || 10,
                                'search.value': loadOptions.searchValue || ''
                            };

                            return $.getJSON('{{ route("user-management.listuser") }}', params)
                                .then(function(response) {
                                    cachedPermissions = response.permissions; // ⬅️ cache permissions di luar
                                    return {
                                        data: response.data,
                                        totalCount: response.recordsTotal
                                    };
                                });
                        }
                    }),
                    columnAutoWidth: true,
                    // ✅ Enable paging
                    paging: {
                        pageSize: 10 // atau jumlah baris per halaman yang kamu mau
                    },
                    pager: {
                        showPageSizeSelector: true,
                        allowedPageSizes: [10, 20, 50],
                        showInfo: true,
                        showNavigationButtons: true
                    },

                    // ❌ Ganti virtual scroll ke standard untuk paging
                    scrolling: {
                        mode: "standard",
                        showScrollbar: "always",
                        scrollByContent: true
                    },
                    searchPanel: {
                        visible: true,
                        highlightCaseSensitive: false,
                        width: 240
                    },

                    groupPanel: {
                        visible: true
                    },
                    grouping: {
                        autoExpandAll: false
                    },
                    height: 600,
                    // showBorders: true,
                    rowAlternationEnabled: true,
                    columns: [{
                            caption: "No",
                            width: 60,
                            cellTemplate: function(cellElement, cellInfo) {
                                const index = cellInfo.rowIndex + (cellInfo.component.pageIndex() * cellInfo.component.pageSize()) + 1;
                                cellElement.text(index);
                            },
                            alignment: "center"
                        },
                        {
                            dataField: "name",
                            caption: "User Name",
                            alignment: "center"
                        },
                        {
                            dataField: "departments",
                            caption: "Department",
                            alignment: "center"
                        },

                        {
                            dataField: "status",
                            caption: "Status",
                            alignment: "center",
                            cellTemplate: function(container, options) {
                                const checkbox = $("<input>")
                                    .attr("type", "checkbox")
                                    .addClass("status-checkbox")
                                    .prop("checked", options.data.status == '1')
                                    .data("user-id", options.data.id);

                                container.append(checkbox);
                            }
                        }
                    ],
                    onContentReady: function(e) {
                        const grid = e.component;
                        const existingColumns = grid.option("columns");

                        if (existingColumns.length <= 5 && cachedPermissions.length > 0) {
                            const columns = existingColumns.slice(0, 4); // tetap ambil yang awal saja

                            cachedPermissions.forEach(function(permission) {
                                columns.push({
                                    caption: permission.name,
                                    alignment: "center",
                                    cellTemplate: function(cellElement, cellInfo) {
                                        const hasPermission = cellInfo.data.permissions.includes(permission.id);
                                        const checked = hasPermission ? "checked" : "";
                                        cellElement.html(`
                                <input type="checkbox" class="permission-checkbox" 
                                    data-user-id="${cellInfo.data.id}" 
                                    data-permission-id="${permission.id}" 
                                    ${checked}>
                            `);
                                    }
                                });
                            });

                            grid.option("columns", columns);
                        }
                    }
                });
            }


            // Inisialisasi grid pertama kali
            initializeDevExtremeGrid();

            // Filter berdasarkan menu_id
            $('#menu-filter').on('change', function() {
                initializeDevExtremeGrid();
            });


            // Handle checkbox permission change
            $(document).on('change', '.permission-checkbox', function() {
                const checkbox = $(this);
                const userId = checkbox.data('user-id');
                const permissionId = checkbox.data('permission-id');
                const status = checkbox.is(':checked') ? 1 : 0;
                const menuId = $('#menu-filter').val(); // ✅ Pastikan menu-filter adalah select input untuk menu_id

                $.ajax({
                    url: "{{ route('user-management.permission.update') }}",
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        user_id: userId,
                        permission_id: permissionId,
                        menu_id: menuId, // ✅ Tambahkan ini
                        status: status
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message || 'Permission berhasil diperbarui.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function() {
                        checkbox.prop('checked', !status); // Revert checkbox
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Gagal memperbarui permission.',
                            confirmButtonText: 'Tutup'
                        });
                    }
                });
            });

            // Handle checkbox status change
            $(document).on('change', '.status-checkbox', function() {
                const checkbox = $(this);
                const userId = checkbox.data('user-id');
                const status = checkbox.is(':checked') ? 1 : 0;

                $.ajax({
                    url: "{{ route('user-management.status.update') }}", // <-- pastikan route ini dibuat
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        user_id: userId,
                        status: status
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message || 'Status berhasil diperbarui.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function() {
                        checkbox.prop('checked', !status); // Balikkan jika gagal
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Gagal memperbarui status user.',
                            confirmButtonText: 'Tutup'
                        });
                    }
                });
            });

        });
    </script>
</x-app-layout>