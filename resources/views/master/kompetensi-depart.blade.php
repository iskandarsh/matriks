<x-app-layout>
    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <h2 class="text-2xl font-extrabold mb-5 text-blue-500 flex items-center space-x-2 drop-shadow-sm">
                        <i class="fas fa-sitemap text-blue-600 animate-pulse"></i>
                        <span data-id="Mapping Kompetensi - Depart" data-en="Competency - Department Mapping">Mapping Kompetensi - Depart</span>
                    </h2>

                    <div class="mb-4 flex gap-2">
                        @can('create', [App\Models\MasterKompetensi::class, session('active_menu_id')])
                        <button id="btnMapping"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm flex items-center gap-2">
                            <i class="fas fa-link"></i> <span data-id="Mapping" data-en="Mapping">Mapping</span>
                        </button>
                        @endcan


                    </div>

                    <div id="grid"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL MAPPING -->
    <div id="modalMapping"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-full max-w-lg">

            <h2 id="mappingTitle" class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                <span id="mappingIcon">🔗</span>
                <span id="mappingTitleText" data-id="Mapping Kompetensi - Depart" data-en="Competency - Department Mapping">Mapping Kompetensi - Depart</span>
            </h2>

            <form id="formMapping">
                @csrf
                <input type="hidden" id="edit_mapping_id" value="">

                <!-- Kompetensi -->
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        <span data-id="Kompetensi" data-en="Competency">Kompetensi</span> <span class="text-red-500">*</span>
                    </label>

                    <select id="map_kompetensi_ids" name="kompetensi_ids[]" class="w-full" style="width: 100%;" multiple required></select>
                </div>

                <!-- Depart -->
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        <span data-id="Depart" data-en="Department">Depart</span> <span class="text-red-500">*</span>
                    </label>

                    <select id="map_depart_ids" name="depart_ids[]" class="w-full" style="width: 100%;" multiple required></select>
                </div>

                <div class="flex justify-end gap-4 pt-6 border-t mt-6 border-gray-200 dark:border-gray-700">
                    <button type="button" id="btnCancelMapping"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-300">
                        ❌ <span data-id="Batal" data-en="Cancel">Batal</span>
                    </button>

                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md">
                        💾 <span data-id="Simpan" data-en="Save">Simpan</span>
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

        // Teks bawaan Select2 (tidak ada hasil / mencari)
        function select2Lang() {
            return {
                noResults: () => t('Data tidak ditemukan', 'No results found'),
                searching: () => t('Mencari...', 'Searching...')
            };
        }

        // Judul modal (mode tambah / edit) - diberi data-id / data-en supaya ikut berganti saat toggle bahasa
        function setMappingTitle(isEdit) {
            const idText = isEdit ? 'Edit Mapping Kompetensi - Depart' : 'Mapping Kompetensi - Depart';
            const enText = isEdit ? 'Edit Competency - Department Mapping' : 'Competency - Department Mapping';

            $('#mappingIcon').text(isEdit ? '✏️' : '🔗');
            $('#mappingTitleText')
                .attr('data-id', idText)
                .attr('data-en', enText)
                .text(t(idText, enText));
        }

        let gridInstance = null;

        $(document).ready(function() {

            // Saat bahasa diganti lewat tombol 🌐, bangun ulang grid & select2 supaya teks ikut berganti
            if (typeof window.applyLanguage === 'function') {
                const originalApplyLanguage = window.applyLanguage;
                window.applyLanguage = function(lang) {
                    originalApplyLanguage(lang);
                    initSelect2();
                    if ($('#grid').length && typeof loadTable === 'function') loadTable();
                };
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            initSelect2();
            loadTable();

            $('#btnMapping').on('click', function() {
                openMappingModal();
            });

            $('#btnCancelMapping').on('click', function() {
                closeMappingModal();
            });

            $('#formMapping').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const originalText = $btn.html();
                const id = $('#edit_mapping_id').val();

                let url = id ?
                    "{{ route('ikompetensi_depart.update', ':id') }}".replace(':id', id) :
                    "{{ route('ikompetensi_depart.store') }}";

                let data = $form.serialize();
                if (id) {
                    data += '&_method=PUT';
                }

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> ' + t('Menyimpan...', 'Saving...'));

                $.ajax({
                    url: url,
                    type: "POST",
                    data: data,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: t('Sukses!', 'Success!'),
                            text: res.message
                        }).then(() => {
                            closeMappingModal();
                            loadTable();
                        });
                    },
                    error: function(xhr) {
                        let error = xhr.responseJSON?.message ?? t('Gagal menyimpan mapping', 'Failed to save mapping');
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
        });

        // Select2 dibuat ulang saat bahasa berganti supaya placeholder ikut berganti
        // (opsi yang sudah terpilih tetap terjaga karena <option> tidak dihapus)
        function initSelect2() {
            const $kompetensi = $('#map_kompetensi_ids');
            const $depart = $('#map_depart_ids');

            if ($kompetensi.hasClass('select2-hidden-accessible')) $kompetensi.select2('destroy');
            if ($depart.hasClass('select2-hidden-accessible')) $depart.select2('destroy');

            $kompetensi.select2({
                theme: 'bootstrap-5',
                placeholder: t('Pilih kompetensi...', 'Select competency...'),
                language: select2Lang(),
                allowClear: true,
                dropdownParent: $('#modalMapping'),
                ajax: {
                    url: "{{ route('kompetensi.select') }}",
                    type: 'GET',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term
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

            $depart.select2({
                theme: 'bootstrap-5',
                placeholder: t('Pilih depart...', 'Select department...'),
                language: select2Lang(),
                allowClear: true,
                dropdownParent: $('#modalMapping'),
                ajax: {
                    url: "{{ route('depart.select') }}",
                    type: 'GET',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.depNama
                            }))
                        };
                    },
                    cache: true
                }
            });
        }

        function openMappingModal(id = null) {
            $('#formMapping')[0].reset();
            $('#edit_mapping_id').val('');

            $('#map_kompetensi_ids').empty().trigger('change');
            $('#map_depart_ids').empty().trigger('change');

            if (id) {
                setMappingTitle(true);

                $.ajax({
                    url: "{{ route('ikompetensi_depart.show', ':id') }}".replace(':id', id),
                    type: 'GET',
                    success: function(data) {
                        $('#edit_mapping_id').val(data.id);

                        if (Array.isArray(data.kompetensi_list) && data.kompetensi_list.length > 0) {
                            data.kompetensi_list.forEach(function(k) {
                                const kompetensiOption = new Option(k.nama, k.id, true, true);
                                $('#map_kompetensi_ids').append(kompetensiOption);
                            });
                            $('#map_kompetensi_ids').trigger('change');
                        } else if (data.id && data.nama) {
                            const kompetensiOption = new Option(data.nama, data.id, true, true);
                            $('#map_kompetensi_ids').append(kompetensiOption).trigger('change');
                        }

                        if (Array.isArray(data.departs) && data.departs.length > 0) {
                            data.departs.forEach(function(dep) {
                                const departOption = new Option(dep.nama, dep.id, true, true);
                                $('#map_depart_ids').append(departOption);
                            });
                            $('#map_depart_ids').trigger('change');
                        }

                        $('#modalMapping').removeClass('hidden').addClass('flex');
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: t('Gagal', 'Failed'),
                            text: t('Gagal mengambil data mapping.', 'Failed to fetch mapping data.')
                        });
                    }
                });
            } else {
                setMappingTitle(false);
                $('#modalMapping').removeClass('hidden').addClass('flex');
            }
        }

        function closeMappingModal() {
            $('#formMapping')[0].reset();
            $('#map_kompetensi_ids').val(null).trigger('change');
            $('#map_depart_ids').val(null).trigger('change');
            $('#edit_mapping_id').val('');
            $('#modalMapping').addClass('hidden').removeClass('flex');
            setMappingTitle(false);
        }

        function loadTable() {
            const $container = $('#grid');

            fetch("{{ route('kompetensi_depart.data') }}")
                .then(res => res.json())
                .then(data => {

                    /*
                    |--------------------------------------------------------------------------
                    | FLATTEN DATA PER DEPARTMENT
                    |--------------------------------------------------------------------------
                    */
                    const kompetensis = data.kompetensi || [];

                    const userPermissions = data.permissions || {};

                    /*
                    |--------------------------------------------------------------------------
                    | DESTROY OLD GRID
                    |--------------------------------------------------------------------------
                    */
                    if (gridInstance) {
                        $container.dxDataGrid('dispose');
                        $container.empty();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | INIT GRID
                    |--------------------------------------------------------------------------
                    */
                    gridInstance = $container.dxDataGrid({

                        dataSource: kompetensis,

                        // unique key karena data sudah di flatten
                        keyExpr: function(row) {
                            return row.id + '_' + row.depart_group;
                        },

                        rowAlternationEnabled: true,
                        columnAutoWidth: true,
                        columnHidingEnabled: true,
                        wordWrapEnabled: true,
                        noDataText: t('Tidak ada data', 'No data'),

                        allowColumnReordering: true,

                        /*
                        |--------------------------------------------------------------------------
                        | GROUP
                        |--------------------------------------------------------------------------
                        */
                        groupPanel: {
                            visible: true,
                            emptyPanelText: t(
                                'Drag kolom ke sini untuk grouping',
                                'Drag a column here to group'
                            )
                        },

                        grouping: {
                            autoExpandAll: true,
                            contextMenuEnabled: true
                        },

                        /*
                        |--------------------------------------------------------------------------
                        | SEARCH
                        |--------------------------------------------------------------------------
                        */
                        searchPanel: {
                            visible: true,
                            width: 240,
                            placeholder: t('Cari...', 'Search...')
                        },

                        /*
                        |--------------------------------------------------------------------------
                        | PAGING
                        |--------------------------------------------------------------------------
                        */
                        paging: {
                            pageSize: 10
                        },

                        pager: {
                            showPageSizeSelector: true,
                            allowedPageSizes: [10, 25, 50],
                            showInfo: true,
                            infoText: t('Halaman {0} dari {1} ({2} data)', 'Page {0} of {1} ({2} items)')
                        },

                        /*
                        |--------------------------------------------------------------------------
                        | STYLE
                        |--------------------------------------------------------------------------
                        */
                        onCellPrepared(e) {

                            if (e.rowType === "header") {
                                $(e.cellElement)
                                    .addClass("bg-gray-100 text-gray-800");
                            }

                            if (e.rowType === "data") {
                                $(e.cellElement)
                                    .addClass("bg-white text-gray-900");
                            }
                        },

                        /*
                        |--------------------------------------------------------------------------
                        | COLUMNS
                        |--------------------------------------------------------------------------
                        */
                        columns: [

                            /*
                            |--------------------------------------------------------------------------
                            | NO
                            |--------------------------------------------------------------------------
                            */
                            {
                                caption: t('No', 'No'),
                                width: 60,
                                alignment: 'center',

                                cellTemplate(container, options) {

                                    const visibleRows = gridInstance.getVisibleRows();

                                    const visibleIndex = visibleRows.findIndex(
                                        row => row.key === options.key
                                    );

                                    container.text(visibleIndex + 1);
                                }
                            },

                            /*
                            |--------------------------------------------------------------------------
                            | KOMPETENSI
                            |--------------------------------------------------------------------------
                            */
                            {
                                dataField: 'nama',
                                caption: t('Kompetensi', 'Competency'),
                                alignment: 'left'
                            },

                            /*
                            |--------------------------------------------------------------------------
                            | KATEGORI
                            |--------------------------------------------------------------------------
                            */
                            {
                                dataField: 'kategori.nama',
                                caption: t('Kategori', 'Category'),
                                alignment: 'center',
                                width: 180,

                                cellTemplate(container, options) {

                                    const kategori =
                                        options.data.kategori?.nama ?? '-';

                                    $('<span>')
                                        .addClass(
                                            'inline-block rounded-full px-3 py-1 text-xs font-semibold bg-blue-100 text-blue-700'
                                        )
                                        .text(kategori)
                                        .appendTo(container);
                                }
                            },

                            /*
                            |--------------------------------------------------------------------------
                            | DEPART
                            |--------------------------------------------------------------------------
                            */
                            {
                                caption: t('Depart', 'Department'),
                                alignment: 'left',

                                calculateCellValue(rowData) {
                                    return (rowData.departs || [])
                                        .map(d => d.depNama)
                                        .join(', ');
                                },

                                cellTemplate(container, options) {

                                    const departs = options.data.departs || [];

                                    if (!departs.length) {
                                        container.text('-');
                                        return;
                                    }

                                    departs.forEach(dep => {

                                        $('<span>')
                                            .addClass(
                                                'inline-block rounded-full px-3 py-1 text-xs font-semibold bg-green-100 text-green-700 mr-1 mb-1'
                                            )
                                            .text(dep.depNama)
                                            .appendTo(container);

                                    });
                                }
                            },

                            /*
                            |--------------------------------------------------------------------------
                            | ACTIONS
                            |--------------------------------------------------------------------------
                            */
                            {
                                caption: t('Aksi', 'Actions'),
                                alignment: 'center',
                                width: 130,

                                cellTemplate(container, options) {

                                    container.addClass(
                                        "text-center align-middle"
                                    );

                                    const wrapper = $('<div>')
                                        .addClass('inline-block');

                                    const id = options.data.id;

                                    /*
                                    |--------------------------------------------------------------------------
                                    | EDIT
                                    |--------------------------------------------------------------------------
                                    */
                                    if (userPermissions.edit) {

                                        $('<button>')
                                            .addClass(
                                                'p-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded me-2 transition duration-200'
                                            )
                                            .html(
                                                '<i class="fas fa-edit"></i>'
                                            )
                                            .attr(
                                                'title',
                                                t('Ubah Mapping', 'Edit Mapping')
                                            )
                                            .on('click', function() {

                                                openMappingModal(id);

                                            })
                                            .appendTo(wrapper);
                                    }

                                    /*
                                    |--------------------------------------------------------------------------
                                    | DELETE
                                    |--------------------------------------------------------------------------
                                    */
                                    if (userPermissions.delete) {

                                        $('<button>')
                                            .addClass(
                                                'p-2 bg-red-600 hover:bg-red-700 text-white rounded transition duration-200'
                                            )
                                            .html(
                                                '<i class="fas fa-trash"></i>'
                                            )
                                            .attr(
                                                'title',
                                                t('Hapus Mapping', 'Delete Mapping')
                                            )
                                            .on('click', function() {

                                                Swal.fire({
                                                    title: t('Hapus mapping?', 'Delete mapping?'),
                                                    text: t(
                                                        'Semua relasi depart pada kompetensi ini akan dihapus.',
                                                        'All department relations for this competency will be deleted.'
                                                    ),
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonText: t('Ya, Hapus', 'Yes, Delete'),
                                                    cancelButtonText: t('Batal', 'Cancel'),
                                                    confirmButtonColor: '#dc2626'
                                                }).then(result => {

                                                    if (result.isConfirmed) {

                                                        $.ajax({
                                                            url: "{{ route('ikompetensi_depart.destroy', ':id') }}"
                                                                .replace(
                                                                    ':id',
                                                                    id
                                                                ),

                                                            type: "POST",

                                                            data: {
                                                                _token: $(
                                                                    'meta[name="csrf-token"]'
                                                                ).attr(
                                                                    'content'
                                                                ),

                                                                _method: 'DELETE'
                                                            },

                                                            success: function(
                                                                res
                                                            ) {

                                                                Swal.fire(
                                                                    t('Berhasil', 'Success'),
                                                                    res.message,
                                                                    'success'
                                                                );

                                                                loadTable();
                                                            },

                                                            error: function() {

                                                                Swal.fire(
                                                                    t('Gagal', 'Failed'),
                                                                    t(
                                                                        'Terjadi kesalahan saat menghapus data.',
                                                                        'An error occurred while deleting the data.'
                                                                    ),
                                                                    'error'
                                                                );
                                                            }
                                                        });
                                                    }
                                                });
                                            })
                                            .appendTo(wrapper);
                                    }

                                    wrapper.appendTo(container);
                                }
                            }
                        ]

                    }).dxDataGrid('instance');

                })
                .catch(err => console.error(err));
        }
    </script>
</x-app-layout>