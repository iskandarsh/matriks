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
                            {{-- BUTTON IMPORT --}}
                            @can('create', [App\Models\MasterKompetensi::class, session('active_menu_id')])
                            <form action="{{ route('master-kompetensi.import') }}"
                                method="POST"
                                enctype="multipart/form-data"
                                class="flex items-center gap-2">

                                @csrf

                                <input type="file"
                                    name="file"
                                    id="fileImport"
                                    accept=".xlsx,.xls,.csv"
                                    class="hidden"
                                    onchange="this.form.submit()">

                                <button type="button"
                                    onclick="document.getElementById('fileImport').click()"
                                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm flex items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Import Excel
                                </button>
                            </form>
                            @endcan
                        </div>

                        <!-- Hanya satu container grid dengan id tetap -->
                        <div id="grid"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ================= TEMPLATE 1 BLOK KATEGORI (dipakai create & edit) ================= --}}
    <template id="tplKategoriBlock">
        <div class="kategori-block border rounded-xl p-4 bg-gray-50 relative mb-3">
            <button type="button"
                class="btnRemoveKategoriBlock absolute top-3 right-3 text-red-500 text-sm hover:text-red-700">
                <i class="fas fa-trash"></i>
            </button>

            <div>
                <label class="text-sm font-medium">Kategori</label>
                <select class="selectKategoriBlock w-full border rounded-lg p-2.5 mt-1"></select>
            </div>

            <div class="kompetensiWrapperBlock mt-4 hidden">
                <label class="font-semibold text-gray-700 text-sm">Kompetensi &amp; Penilaian</label>

                <div class="kompetensiLoadingBlock hidden text-sm text-gray-500 mt-2">
                    Loading kompetensi...
                </div>

                <div class="kompetensiListBlock space-y-3 mt-3"></div>
            </div>
        </div>
    </template>

    <div id="modalCreate"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-2 sm:p-4">

        <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl
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

                        <div id="departmentWrapper" class="hidden">
                            <label class="text-sm font-medium">Department</label>
                            <select id="selectDepartment" name="department_id"
                                class="w-full border rounded-lg p-2.5 mt-1">
                            </select>
                        </div>

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

                    </div>

                    <!-- KATEGORI (CLONEABLE) -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between">
                            <label class="font-semibold text-gray-700">Kategori &amp; Kompetensi</label>
                            <button type="button" id="btnAddKategoriCreate"
                                class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus"></i> Tambah Kategori
                            </button>
                        </div>

                        <div id="kategoriBlockContainerCreate" class="mt-3"></div>
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


    <div id="modalEdit"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-2 sm:p-4">

        <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl
            max-h-[95vh] flex flex-col">

            <!-- HEADER -->
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b flex justify-between items-center shrink-0">
                <h2 class="text-base sm:text-lg font-semibold">
                    Edit Kompetensi
                </h2>

                <button type="button"
                    onclick="$('#modalEdit').addClass('hidden')"
                    class="text-gray-400 hover:text-red-500 text-lg">
                    ✕
                </button>
            </div>

            <!-- BODY -->
            <div class="overflow-y-auto px-4 sm:px-6 py-4 space-y-5">

                <form id="formEdit" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <input type="hidden" id="edit_id">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div id="editDepartmentWrapper" class="hidden">
                            <label class="text-sm font-medium">
                                Department
                            </label>

                            <select id="editDepartment"
                                name="department_id"
                                class="w-full border rounded-lg p-2.5 mt-1">
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-medium">
                                Jabatan
                            </label>

                            <select id="editJabatan"
                                name="id_jabatan"
                                class="w-full border rounded-lg p-2.5 mt-1">
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-medium">
                                Posisi
                            </label>

                            <select id="editPosisi"
                                name="id_posisi"
                                class="w-full border rounded-lg p-2.5 mt-1">
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-medium">
                                Work Unit
                            </label>

                            <select id="editWorkunit"
                                name="id_workunit"
                                class="w-full border rounded-lg p-2.5 mt-1">
                            </select>
                        </div>

                    </div>

                    <!-- KATEGORI (CLONEABLE) -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between">
                            <label class="font-semibold text-gray-700">Kategori &amp; Kompetensi</label>
                            <button type="button" id="btnAddKategoriEdit"
                                class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus"></i> Tambah Kategori
                            </button>
                        </div>

                        <div id="kategoriBlockContainerEdit" class="mt-3"></div>
                    </div>

                </form>

            </div>

            <!-- FOOTER -->
            <div class="px-4 sm:px-6 py-4 border-t flex flex-col sm:flex-row justify-end gap-3 shrink-0 bg-white">

                <button type="button"
                    onclick="$('#modalEdit').addClass('hidden')"
                    class="w-full sm:w-auto border px-4 py-2 rounded-lg">
                    Batal
                </button>

                <button type="submit"
                    form="formEdit"
                    class="w-full sm:w-auto bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Update
                </button>

            </div>

        </div>
    </div>

    @php
    $isSuperDepart = auth()->user()
    ->departments
    ->pluck('id')
    ->intersect([5, 6])
    ->isNotEmpty();
    @endphp

    <script>
        const isSuperDepart = @json($isSuperDepart);
    </script>

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

            // ============================================================
            // HIGHLIGHT KARTU NILAI YANG DIPILIH (radio card Kompetensi & Penilaian)
            // ============================================================
            $(document).on('change', '.selectNilaiRadio', function() {
                const $label = $(this).closest('.nilaiOptionLabel');
                const $group = $label.closest('.nilaiOptionsGroup');

                $group.find('.nilaiOptionLabel')
                    .removeClass('border-blue-500 bg-blue-50 ring-1 ring-blue-500')
                    .addClass('border-gray-200 bg-white');

                $label
                    .removeClass('border-gray-200 bg-white')
                    .addClass('border-blue-500 bg-blue-50 ring-1 ring-blue-500');
            });

            // ============================================================
            // DEPARTMENT (khusus super depart) - CREATE
            // ============================================================
            if (isSuperDepart) {
                $('#departmentWrapper').removeClass('hidden');

                $('#selectDepartment').select2({
                    dropdownParent: $('#modalCreate'),
                    width: '100%',
                    placeholder: '-- Pilih Department --',
                    allowClear: true,
                    ajax: {
                        url: '{{ route("depart.select") }}',
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

            $('#btnCreate').on('click', function() {
                $('#modalCreate').removeClass('hidden').addClass('flex');
            });

            $('#btnCancel').on('click', function() {
                $('#modalCreate').addClass('hidden').removeClass('flex');
                $('#formCreate')[0].reset();
                $('#kategoriBlockContainerCreate').empty();
                addKategoriBlock($('#kategoriBlockContainerCreate'), '#modalCreate');
            });

            loadTable();

            // JABATAN (CREATE)
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

            // POSISI (CREATE)
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

            // WORKUNIT (CREATE)
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

            // kalau department berubah -> reload kompetensi di SEMUA blok kategori (create)
            $('#selectDepartment').on('change', function() {
                const departmentId = $(this).val();
                $('#kategoriBlockContainerCreate .kategori-block').each(function() {
                    const $block = $(this);
                    const kategoriId = $block.find('.selectKategoriBlock').val();
                    renderKompetensiForBlock($block, kategoriId, departmentId);
                });
            });

            // ============================================================
            // DEPARTMENT (khusus super depart) - EDIT
            // ============================================================
            if (isSuperDepart) {
                $('#editDepartmentWrapper').removeClass('hidden');

                $('#editDepartment').select2({
                    dropdownParent: $('#modalEdit'),
                    width: '100%',
                    placeholder: '-- Pilih Department --',
                    allowClear: true,
                    ajax: {
                        url: '{{ route("depart.select") }}',
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

            $('#editJabatan').select2({
                dropdownParent: $('#modalEdit'),
                width: '100%',
                theme: 'bootstrap-5',
                placeholder: 'Pilih Jabatan',
                ajax: {
                    url: 'dropdown/jabatan',
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

            $('#editPosisi').select2({
                dropdownParent: $('#modalEdit'),
                width: '100%',
                theme: 'bootstrap-5',
                placeholder: 'Pilih Posisi',
                ajax: {
                    url: 'dropdown/posisi',
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

            $('#editWorkunit').select2({
                dropdownParent: $('#modalEdit'),
                width: '100%',
                theme: 'bootstrap-5',
                placeholder: 'Pilih Workunit',
                ajax: {
                    url: 'dropdown/workunit',
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

            // kalau department berubah -> reload kompetensi di SEMUA blok kategori (edit)
            $('#editDepartment').on('change', function() {
                const departmentId = $(this).val();
                $('#kategoriBlockContainerEdit .kategori-block').each(function() {
                    const $block = $(this);
                    const kategoriId = $block.find('.selectKategoriBlock').val();
                    renderKompetensiForBlock($block, kategoriId, departmentId);
                });
            });

            // ============================================================
            // TOMBOL TAMBAH KATEGORI
            // ============================================================
            $('#btnAddKategoriCreate').on('click', function() {
                addKategoriBlock($('#kategoriBlockContainerCreate'), '#modalCreate');
            });

            $('#btnAddKategoriEdit').on('click', function() {
                addKategoriBlock($('#kategoriBlockContainerEdit'), '#modalEdit');
            });

            // sediakan 1 blok kosong begitu modal create pertama kali dibuka / halaman load
            addKategoriBlock($('#kategoriBlockContainerCreate'), '#modalCreate');
        });

        // Ambil semua id kategori yang sudah dipakai di blok lain (kecuali blok yang sedang di-edit)
        function getUsedKategoriIds($container, $excludeBlock) {
            const ids = [];
            $container.find('.kategori-block').each(function() {
                if (this === $excludeBlock[0]) return; // skip blok sendiri
                const val = $(this).find('.selectKategoriBlock').val();
                if (val) ids.push(String(val));
            });
            return ids;
        }

        // ============================================================
        // ADD KATEGORI BLOCK (CLONE) - dipakai create & edit
        // ============================================================
        function addKategoriBlock($container, modalSelector, prefillKategori = null, savedItems = [], departmentIdForPrefill = null) {

            const tpl = document.getElementById('tplKategoriBlock').content.cloneNode(true);
            $container.append(tpl);

            const $blockInDom = $container.children('.kategori-block').last();

            // beri id unik ke tiap blok kategori, supaya radio "nilai" antar blok
            // (dan antar kompetensi yang sama di blok berbeda) tidak saling bentrok
            const blockUid = 'blk' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
            $blockInDom.attr('data-block-uid', blockUid);

            const $selectKategori = $blockInDom.find('.selectKategoriBlock');

            $selectKategori.select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $(modalSelector),
                placeholder: 'Pilih kategori',
                ajax: {
                    url: "{{ route('kategori.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: (data) => {
                        const usedIds = getUsedKategoriIds($container, $blockInDom);
                        const filtered = data.filter(item => !usedIds.includes(String(item.id)));
                        return {
                            results: filtered
                        };
                    }
                }
            });

            $blockInDom.find('.btnRemoveKategoriBlock').on('click', function() {
                $blockInDom.remove();
            });

            function bindChangeHandler() {
                $selectKategori.off('change').on('change', function() {
                    const kategoriId = $(this).val();

                    if (kategoriId) {
                        const usedIds = getUsedKategoriIds($container, $blockInDom);
                        if (usedIds.includes(String(kategoriId))) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Kategori sudah dipilih',
                                text: 'Kategori ini sudah digunakan pada blok lain. Silakan pilih kategori yang berbeda.'
                            });

                            // reset pilihan yang barusan dipilih
                            $(this).val(null).trigger('change.select2');
                            $blockInDom.find('.kompetensiWrapperBlock').addClass('hidden');
                            $blockInDom.find('.kompetensiListBlock').html('');
                            return;
                        }
                    }

                    const departmentId = (modalSelector === '#modalCreate') ?
                        $('#selectDepartment').val() :
                        $('#editDepartment').val();

                    renderKompetensiForBlock($blockInDom, kategoriId, departmentId);
                });
            }
            if (prefillKategori) {
                const opt = new Option(prefillKategori.text, prefillKategori.id, true, true);
                // trigger namespaced 'select2' saja supaya tampilan select2 ter-update
                // tanpa memicu handler 'change' biasa (yang akan fetch ulang tanpa savedItems)
                $selectKategori.append(opt).trigger('change.select2');

                renderKompetensiForBlock($blockInDom, prefillKategori.id, departmentIdForPrefill, savedItems);
            }

            bindChangeHandler();

            return $blockInDom;
        }

        // ============================================================
        // RENDER LIST KOMPETENSI + NILAI UNTUK 1 BLOK KATEGORI
        // Ditampilkan sebagai kartu pilihan (radio card) full-text -- skala &
        // deskripsi lengkap langsung kelihatan, tidak terpotong / butuh hover.
        // ============================================================
        function renderKompetensiForBlock($block, kategoriId, departmentId, savedItems = []) {

            const $wrapper = $block.find('.kompetensiWrapperBlock');
            const $loading = $block.find('.kompetensiLoadingBlock');
            const $list = $block.find('.kompetensiListBlock');

            if (!kategoriId) {
                $wrapper.addClass('hidden');
                $list.html('');
                return;
            }

            if (isSuperDepart && !departmentId) {
                $wrapper.addClass('hidden');
                $list.html('');
                return;
            }

            $wrapper.removeClass('hidden');
            $loading.removeClass('hidden');
            $list.html('');

            const savedMap = {};
            savedItems.forEach(item => {
                savedMap[item.kompetensi_id] = item.nilai;
            });

            const blockUid = $block.attr('data-block-uid') ||
                (() => {
                    const uid = 'blk' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
                    $block.attr('data-block-uid', uid);
                    return uid;
                })();

            $.ajax({
                url: 'ajax/kompetensi',
                type: 'GET',
                data: {
                    kategori_id: kategoriId,
                    department_id: departmentId
                },
                success: function(res) {
                    $loading.addClass('hidden');

                    let html = '';

                    if (!res.length) {
                        html = `
                            <div class="text-sm text-gray-500 italic">
                                Tidak ada kompetensi pada kategori ini
                            </div>
                        `;
                    }

                    res.forEach(item => {

                        const radioName = `nilai_${blockUid}_${item.id}`;
                        const currentVal = String(savedMap[item.id] ?? '');

                        const sortedDetails = [...item.details].sort((a, b) => Number(a.skala) - Number(b.skala));

                        let optionsHtml = '';

                        if (!sortedDetails.length) {
                            optionsHtml = `
                                <div class="text-xs text-gray-400 italic px-1 col-span-full">
                                    Belum ada skala penilaian untuk kompetensi ini
                                </div>
                            `;
                        } else {
                            sortedDetails.forEach(d => {
                                const isChecked = currentVal === String(d.skala);
                                const desc = d.deskripsi ?? '';

                                optionsHtml += `
                                    <label class="nilaiOptionLabel flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition
                                        hover:border-blue-400 hover:bg-blue-50/60
                                        ${isChecked ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-gray-200 bg-white'}">
                                        <input type="radio"
                                            name="${radioName}"
                                            value="${d.skala}"
                                            class="selectNilaiRadio mt-1 accent-blue-600"
                                            ${isChecked ? 'checked' : ''}>
                                        <span class="flex-1">
                                            <span class="block text-sm font-semibold text-gray-800">
                                                Skala ${d.skala}
                                            </span>
                                            ${desc ? `<span class="block text-xs text-gray-500 leading-snug mt-0.5">${desc}</span>` : ''}
                                        </span>
                                    </label>
                                `;
                            });
                        }

                        html += `
                            <div class="kompetensiRow p-3 sm:p-4 rounded-xl border bg-white shadow-sm" data-kompetensi-id="${item.id}">
                                <div class="mb-3">
                                    <span class="block text-sm font-semibold text-gray-800 bg-gray-50 border rounded-lg px-3 py-2">
                                        ${item.nama}
                                    </span>
                                </div>

                                <div class="nilaiOptionsGroup grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    ${optionsHtml}
                                </div>
                            </div>
                        `;
                    });

                    $list.html(html);
                },
                error: function() {
                    $loading.addClass('hidden');
                    $list.html(`
                        <div class="text-red-500 text-sm">
                            Gagal mengambil data kompetensi
                        </div>
                    `);
                }
            });
        }

        // ============================================================
        // KUMPULKAN SEMUA BLOK KATEGORI -> ARRAY groups[]
        // ============================================================
        function buildGroupsPayload($container) {
            const groups = [];

            $container.find('.kategori-block').each(function() {
                const $block = $(this);
                const idKategori = $block.find('.selectKategoriBlock').val();

                if (!idKategori) return; // blok kosong, skip

                const kompetensiIds = [];
                const nilaiIds = [];

                $block.find('.kompetensiRow').each(function() {
                    const kompId = $(this).data('kompetensi-id');
                    const nilai = $(this).find('.selectNilaiRadio:checked').val();

                    kompetensiIds.push(kompId);
                    nilaiIds.push(nilai || '');
                });

                if (!kompetensiIds.length) return; // belum ada kompetensi, skip

                groups.push({
                    id_kategori: idKategori,
                    kompetensi_id: kompetensiIds,
                    detail_kompetensi_id: nilaiIds
                });
            });

            return groups;
        }

        // ============================================================
        // SUBMIT CREATE
        // ============================================================
        $('#formCreate').on('submit', function(e) {
            e.preventDefault();

            const groups = buildGroupsPayload($('#kategoriBlockContainerCreate'));

            if (!groups.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Lengkapi data',
                    text: 'Minimal 1 kategori dengan kompetensi & nilai harus diisi.'
                });
                return;
            }

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

                const payload = {
                    id_jabatan: $('#selectJabatan').val(),
                    id_posisi: $('#selectPosisi').val(),
                    id_workunit: $('#selectWorkunit').val(),
                    department_id: $('#selectDepartment').val(),
                    groups: groups
                };

                $.ajax({
                    url: "ikompetensi_pelatihan",
                    type: "POST",
                    data: payload,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message ?? 'Data berhasil disimpan',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        $('#modalCreate').addClass('hidden').removeClass('flex');
                        $('#formCreate')[0].reset();

                        $('#selectJabatan').val(null).trigger('change');
                        $('#selectPosisi').val(null).trigger('change');
                        $('#selectWorkunit').val(null).trigger('change');
                        $('#selectDepartment').val(null).trigger('change');

                        $('#kategoriBlockContainerCreate').empty();
                        addKategoriBlock($('#kategoriBlockContainerCreate'), '#modalCreate');

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
                            msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
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

        // ============================================================
        // OPEN EDIT MODAL (load groups dari server)
        // ============================================================
        function openEditModal(id) {
            $('#formEdit')[0].reset();
            $('#edit_id').val('');
            $('#kategoriBlockContainerEdit').empty();

            // reset select2 supaya value lama hilang
            $('#editJabatan').empty().trigger('change');
            $('#editPosisi').empty().trigger('change');
            $('#editWorkunit').empty().trigger('change');
            $('#editDepartment').empty().trigger('change');

            $.ajax({
                url: `ikompetensi_pelatihan/${id}`,
                type: 'GET',
                success: function(res) {
                    $('#edit_id').val(res.id);

                    // set jabatan
                    if (res.posisi) {
                        const optJabatan = new Option(res.posisi.posiNama, res.posisi.id, true, true);
                        $('#editJabatan').append(optJabatan).trigger('change');
                    }

                    // set posisi
                    if (res.peran) {
                        const optPosisi = new Option(res.peran.name, res.peran.id, true, true);
                        $('#editPosisi').append(optPosisi).trigger('change');
                    }

                    // set workunit
                    if (res.workunit) {
                        const optWorkunit = new Option(res.workunit.woruNama, res.workunit.id, true, true);
                        $('#editWorkunit').append(optWorkunit).trigger('change');
                    }

                    // set department kalau super depart
                    if (isSuperDepart && res.departement) {
                        const optDept = new Option(res.departement.depNama, res.departement.id, true, true);
                        $('#editDepartment').append(optDept).trigger('change');
                    }

                    const departmentIdForPrefill = isSuperDepart ? res.department_id : null;

                    const groups = res.groups ?? [];

                    if (groups.length) {
                        groups.forEach(group => {
                            const prefillKategori = {
                                id: group.id_kategori,
                                text: group.kategori?.nama ?? ''
                            };

                            addKategoriBlock(
                                $('#kategoriBlockContainerEdit'),
                                '#modalEdit',
                                prefillKategori,
                                group.items ?? [],
                                departmentIdForPrefill
                            );
                        });
                    } else {
                        addKategoriBlock($('#kategoriBlockContainerEdit'), '#modalEdit');
                    }

                    $('#modalEdit').removeClass('hidden').addClass('flex');
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Data tidak ditemukan'
                    });
                }
            });
        }

        // ============================================================
        // SUBMIT EDIT
        // ============================================================
        $('#formEdit').on('submit', function(e) {
            e.preventDefault();

            const groups = buildGroupsPayload($('#kategoriBlockContainerEdit'));

            if (!groups.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Lengkapi data',
                    text: 'Minimal 1 kategori dengan kompetensi & nilai harus diisi.'
                });
                return;
            }

            Swal.fire({
                title: 'Update data?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, update',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const payload = {
                    _method: 'PUT',
                    id_jabatan: $('#editJabatan').val(),
                    id_posisi: $('#editPosisi').val(),
                    id_workunit: $('#editWorkunit').val(),
                    department_id: $('#editDepartment').val(),
                    groups: groups
                };

                $.ajax({
                    url: `ikompetensi_pelatihan/${$('#edit_id').val()}`,
                    type: 'POST',
                    data: payload,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message ?? 'Data berhasil diupdate',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        $('#modalEdit').addClass('hidden').removeClass('flex');
                        $('#formEdit')[0].reset();
                        $('#kategoriBlockContainerEdit').empty();
                        loadTable();
                    },
                    error: function(xhr) {
                        let msg = 'Gagal update data';

                        if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }

                        if (xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: msg
                        });
                    }
                });
            });
        });

        // ============================================================
        // GRID (DevExtreme) - tidak berubah dari sebelumnya
        // ============================================================
        let gridInstance = null;

        function loadTable() {
            const gridId = 'grid';
            const $container = $('#' + gridId);

            fetch("{{ route('kompetensi_pelatihan.data') }}")
                .then(res => res.json())
                .then(data => {
                    const rows = data.data;
                    const userPermissions = data.permissions || {};

                    if (gridInstance) {
                        gridInstance.option('dataSource', rows);
                        return;
                    }

                    gridInstance = $container.dxDataGrid({
                        dataSource: rows,
                        keyExpr: 'id',
                        rowAlternationEnabled: true,
                        columnAutoWidth: true,
                        showBorders: true,

                        groupPanel: {
                            visible: true,
                            emptyPanelText: "Drag a column header here to group by that column"
                        },
                        grouping: {
                            autoExpandAll: true
                        },
                        allowColumnReordering: true,

                        columnChooser: {
                            enabled: true,
                            mode: "select",
                            allowSearch: true
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
                                caption: 'Departement',
                                dataField: 'departement.depNama',
                                calculateCellValue: row => row.departement?.depNama ?? '-',
                                groupIndex: 1
                            },

                            {
                                caption: 'Kompetensi',
                                dataField: 'kompetensi.nama',
                                calculateCellValue: row => row.kompetensi?.nama ?? '-'
                            },

                            {
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
                                caption: 'Nilai',
                                dataField: 'nilai',
                                alignment: 'center',
                                width: 100
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

                                    if (userPermissions.edit) {
                                        $('<button>')
                                            .addClass('p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition')
                                            .attr('title', 'Edit')
                                            .html('<i class="fas fa-edit"></i>')
                                            .on('click', e => {
                                                e.stopPropagation();
                                                openEditModal(id);
                                            })
                                            .appendTo($wrapper);
                                    }
                                    if (userPermissions.edit) {
                                        $('<button>')
                                            .addClass('p-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition')
                                            .attr('title', 'Clone')
                                            .html('<i class="fas fa-copy"></i>')
                                            .on('click', e => {
                                                e.stopPropagation();
                                                openCloneModal(id);
                                            })
                                            .appendTo($wrapper);
                                    }
                                    if (userPermissions.delete) {
                                        $('<button>')
                                            .addClass('p-2 bg-red-600 text-white rounded hover:bg-red-700 transition')
                                            .attr('title', 'Delete')
                                            .html('<i class="fas fa-trash"></i>')
                                            .on('click', e => {
                                                e.stopPropagation();

                                                const $btn = $(e.currentTarget);
                                                const oldHtml = $btn.html();

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
                });
        }

        // ============================================================
        // OPEN CLONE MODAL (buka modal CREATE, prefill department+kategori+nilai)
        // jabatan / posisi / workunit SENGAJA dikosongkan
        // ============================================================
        function openCloneModal(id) {
            $('#formCreate')[0].reset();
            $('#kategoriBlockContainerCreate').empty();

            // kosongkan jabatan/posisi/workunit -> user wajib isi baru
            $('#selectJabatan').empty().trigger('change');
            $('#selectPosisi').empty().trigger('change');
            $('#selectWorkunit').empty().trigger('change');
            $('#selectDepartment').empty().trigger('change');

            $.ajax({
                url: `ikompetensi_pelatihan/${id}`,
                type: 'GET',
                success: function(res) {

                    // Department di-clone (kalau super depart)
                    if (isSuperDepart && res.departement) {
                        const optDept = new Option(res.departement.depNama, res.departement.id, true, true);
                        $('#selectDepartment').append(optDept).trigger('change');
                    }

                    const departmentIdForPrefill = isSuperDepart ? res.department_id : null;
                    const groups = res.groups ?? [];

                    // Kategori & Nilai di-clone dari data lama
                    if (groups.length) {
                        groups.forEach(group => {
                            const prefillKategori = {
                                id: group.id_kategori,
                                text: group.kategori?.nama ?? ''
                            };

                            addKategoriBlock(
                                $('#kategoriBlockContainerCreate'),
                                '#modalCreate',
                                prefillKategori,
                                group.items ?? [],
                                departmentIdForPrefill
                            );
                        });
                    } else {
                        addKategoriBlock($('#kategoriBlockContainerCreate'), '#modalCreate');
                    }

                    // Jabatan, Posisi, Workunit sengaja TIDAK di-set -> user isi manual
                    $('#modalCreate').removeClass('hidden').addClass('flex');
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Data tidak ditemukan'
                    });
                }
            });
        }

        function deleteData(id) {

            return Swal.fire({
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

                return fetch(`ikompetensi_pelatihan/${id}`, {
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

                        loadTable();
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
    </script>
</x-app-layout>