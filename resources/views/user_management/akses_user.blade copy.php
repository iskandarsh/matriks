<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('User Permission Table') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <!-- Menu Filter Dropdown -->
            <div>
                <label for="menu-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter by Menu</label>
                <select id="menu-filter" class="w-full select2-menu bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-700 rounded-md">
                    @foreach($menus_akses as $menu)
                        <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 overflow-x-auto">
                <table id="user-table" class="min-w-full table-auto border-separate border-spacing-0 text-sm">
                    <thead class="bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <tr>
                            <th class="px-4 py-2 border">No</th>
                            <th class="px-4 py-2 border">User Name</th>
                            @foreach($permissions as $permission)
                                <th class="px-4 py-2 border text-center">{{ $permission->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $i => $user)
                            <tr data-user-id="{{ $user->id }}">
                                <td class="px-4 py-2 text-center">{{ $i + 1 }}</td>
                                <td class="px-4 py-2">{{ $user->name }}</td>
                                @foreach($permissions as $permission)
                                    <td class="px-4 py-2 text-center">
                                        <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 permission-checkbox"
                                            data-user-id="{{ $user->id }}"
                                            data-permission-id="{{ $permission->id }}"
                                            {{ $user->permissions->contains('id', $permission->id) ? 'checked' : '' }}>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Include Select2 CSS & JS (if not already included) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            // Inisialisasi Select2
            $('#menu-filter').select2();

            // Inisialisasi DataTable
            let table = $('#user-table').DataTable({
                destroy: true,
                responsive: true,
                language: {
                    paginate: {
                        next: '<button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded">Next</button>',
                        previous: '<button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded">Previous</button>'
                    }
                },
                lengthMenu: [[10, 25, 50, -1], ['10', '25', '50', 'All']],
                initComplete: function () {
                    const lengthSelect = $('.dataTables_length select');
                    lengthSelect.addClass('w-16 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2');
                    $('.dataTables_length select option').addClass('bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100');
                    $('.dataTable thead th').addClass('bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100');
                    $('.dataTable tbody tr td').addClass('bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100');
      
                }
            });

            // Saat ganti menu
            $('#menu-filter').on('change', function () {
                const menuId = $(this).val();

                $.ajax({
                    url: "{{ route('user-management.permission.filter-by-menu') }}", // Buat route ini
                    method: 'GET',
                    data: {
                        menu_id: menuId
                    },
                    success: function (response) {
                        table.clear().draw(); // Kosongkan datanya

                        // Append row baru
                        response.users.forEach(function (user, index) {
                            let row = `
                                <tr data-user-id="${user.id}">
                                    <td class="px-4 py-2 text-center">${index + 1}</td>
                                    <td class="px-4 py-2">${user.name}</td>
                            `;

                            response.permissions.forEach(function (permission) {
                                const isChecked = user.permissions.includes(permission.id) ? 'checked' : '';
                                row += `
                                    <td class="px-4 py-2 text-center">
                                        <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 permission-checkbox"
                                            data-user-id="${user.id}"
                                            data-permission-id="${permission.id}"
                                            ${isChecked}>
                                    </td>
                                `;
                            });

                            row += `</tr>`;
                            table.row.add($(row));
                        });

                        table.draw();
                    },
                    error: function () {
                        alert("Gagal memuat data berdasarkan menu.");
                    }
                });
            });

            // Checkbox logic
            $(document).on('change', '.permission-checkbox', function () {
                const checkbox = $(this);
                const userId = checkbox.data('user-id');
                const permissionId = checkbox.data('permission-id');
                const status = checkbox.is(':checked') ? 1 : 0;

                $.ajax({
                    url: "{{ route('user-management.permission.update') }}",
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        user_id: userId,
                        permission_id: permissionId,
                        status: status
                    },
                    success: function (res) {
                        console.log('Permission updated:', res.message);
                    },
                    error: function () {
                        checkbox.prop('checked', !status);
                        alert('Gagal mengupdate permission!');
                    }
                });
            });
        });
    </script>
</x-app-layout>
