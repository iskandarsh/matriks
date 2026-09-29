<x-app-layout>
    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-extrabold mb-5 text-blue-500 flex items-center space-x-2 drop-shadow-sm">
                        <i class="fas fa-database text-blue-600 animate-pulse"></i>
                        <span data-id="Master Kompetensi Pelatihan" data-en="Training Competency Master">Master Kompetensi Pelatihan</span>

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
                                <i class="fas fa-plus"></i> <span data-id="Buat" data-en="Create">Buat</span>
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
                                    <i class="fas fa-file-excel"></i> <span data-id="Import Excel" data-en="Import Excel">Import Excel</span>
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
                <label class="text-sm font-medium" data-id="Kategori" data-en="Category">Kategori</label>
                <select class="selectKategoriBlock w-full border rounded-lg p-2.5 mt-1"></select>
            </div>

            <div class="kompetensiWrapperBlock mt-4 hidden">
                <label class="font-semibold text-gray-700 text-sm"
                    data-id="Kompetensi &amp; Penilaian" data-en="Competency &amp; Assessment">Kompetensi &amp; Penilaian</label>

                <div class="kompetensiLoadingBlock hidden text-sm text-gray-500 mt-2"
                    data-id="Memuat kompetensi..." data-en="Loading competencies...">
                    Memuat kompetensi...
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
                <h2 class="text-base sm:text-lg font-semibold" data-id="Tambah Kompetensi" data-en="Add Competency">Tambah Kompetensi</h2>
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
                            <label class="text-sm font-medium" data-id="Jabatan" data-en="Job Title">Jabatan</label>
                            <select id="selectJabatan" name="id_jabatan"
                                class="w-full border rounded-lg p-2.5 mt-1"></select>
                        </div>

                        <div>
                            <label class="text-sm font-medium" data-id="Posisi" data-en="Position">Posisi</label>
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
                            <label class="font-semibold text-gray-700"
                                data-id="Kategori &amp; Kompetensi" data-en="Category &amp; Competency">Kategori &amp; Kompetensi</label>
                            <button type="button" id="btnAddKategoriCreate"
                                class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus"></i> <span data-id="Tambah Kategori" data-en="Add Category">Tambah Kategori</span>
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
                    class="w-full sm:w-auto border px-4 py-2 rounded-lg"
                    data-id="Batal" data-en="Cancel">
                    Batal
                </button>

                <button type="submit" form="formCreate"
                    class="w-full sm:w-auto bg-blue-600 text-white px-6 py-2 rounded-lg"
                    data-id="Simpan" data-en="Save">
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
                <h2 class="text-base sm:text-lg font-semibold" data-id="Edit Kompetensi" data-en="Edit Competency">
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
                            <label class="text-sm font-medium" data-id="Jabatan" data-en="Job Title">
                                Jabatan
                            </label>

                            <select id="editJabatan"
                                name="id_jabatan"
                                class="w-full border rounded-lg p-2.5 mt-1">
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-medium" data-id="Posisi" data-en="Position">
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
                            <label class="font-semibold text-gray-700"
                                data-id="Kategori &amp; Kompetensi" data-en="Category &amp; Competency">Kategori &amp; Kompetensi</label>
                            <button type="button" id="btnAddKategoriEdit"
                                class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus"></i> <span data-id="Tambah Kategori" data-en="Add Category">Tambah Kategori</span>
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
                    class="w-full sm:w-auto border px-4 py-2 rounded-lg"
                    data-id="Batal" data-en="Cancel">
                    Batal
                </button>

                <button type="submit"
                    form="formEdit"
                    class="w-full sm:w-auto bg-blue-600 text-white px-6 py-2 rounded-lg"
                    data-id="Update" data-en="Update">
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
        // ============================================================
        // HELPER TERJEMAHAN (mengikuti bahasa di localStorage 'app_lang')
        // ============================================================
        function currentLang() {
            let lang = window.appLang;
            if (!lang) {
                try {
                    lang = localStorage.getItem('app_lang');
                } catch (e) {}
            }
            return lang === 'en' ? 'en' : 'id';
        }

        function t(id, en) {
            return currentLang() === 'en' ? en : id;
        }

        // Terjemahkan elemen [data-id][data-en] di dalam root tertentu
        // (dipakai untuk isi <template> yang di-clone, karena tidak ikut diterjemahkan oleh applyLanguage saat halaman dibuka)
        function translateEl($root) {
            const lang = currentLang();
            $root.find('[data-id][data-en]').each(function() {
                this.textContent = this.getAttribute('data-' + lang);
            });
        }

        // Teks bawaan Select2 (tidak ada hasil / mencari)
        function select2Lang() {
            return {
                noResults: () => t('Data tidak ditemukan', 'No results found'),
                searching: () => t('Mencari...', 'Searching...')
            };
        }

        // ============================================================
        // SELECT2 STATIS (department, jabatan, posisi, workunit) - create & edit
        // Dibuat ulang saat bahasa berganti supaya placeholder ikut berganti
        // ============================================================
        function initStaticSelects() {

            const resetSelect2 = $el => {
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
            };

            const departmentSelect = (selector, parent) => {
                const $el = $(selector);
                resetSelect2($el);

                $el.select2({
                    dropdownParent: $(parent),
                    width: '100%',
                    placeholder: t('-- Pilih Department --', '-- Select Department --'),
                    language: select2Lang(),
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
            };

            const dropdownSelect = (selector, parent, placeholder, url) => {
                const $el = $(selector);
                resetSelect2($el);

                $el.select2({
                    dropdownParent: $(parent),
                    placeholder: placeholder,
                    language: select2Lang(),
                    theme: 'bootstrap-5',
                    width: '100%',
                    ajax: {
                        url: url,
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
            };

            if (isSuperDepart) {
                departmentSelect('#selectDepartment', '#modalCreate');
                departmentSelect('#editDepartment', '#modalEdit');
            }

            // CREATE
            dropdownSelect('#selectJabatan', '#modalCreate', t('Pilih Jabatan', 'Select Job Title'), 'dropdown/jabatan');
            dropdownSelect('#selectPosisi', '#modalCreate', t('Pilih Posisi', 'Select Position'), 'dropdown/posisi');
            dropdownSelect('#selectWorkunit', '#modalCreate', t('Pilih Workunit', 'Select Work Unit'), 'dropdown/workunit');

            // EDIT
            dropdownSelect('#editJabatan', '#modalEdit', t('Pilih Jabatan', 'Select Job Title'), 'dropdown/jabatan');
            dropdownSelect('#editPosisi', '#modalEdit', t('Pilih Posisi', 'Select Position'), 'dropdown/posisi');
            dropdownSelect('#editWorkunit', '#modalEdit', t('Pilih Workunit', 'Select Work Unit'), 'dropdown/workunit');
        }

        // ============================================================
        // SELECT2 KATEGORI (per blok) - dibuat ulang saat bahasa berganti
        // ============================================================
        function initKategoriSelect($select, $container, $block, modalSelector) {

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $(modalSelector),
                placeholder: t('Pilih kategori', 'Select category'),
                language: select2Lang(),
                ajax: {
                    url: "{{ route('kategori.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: (data) => {
                        const usedIds = getUsedKategoriIds($container, $block);
                        const filtered = data.filter(item => !usedIds.includes(String(item.id)));
                        return {
                            results: filtered
                        };
                    }
                }
            });
        }

        // Re-init semua select2 kategori yang sudah ada di kedua modal
        function reinitAllKategoriSelects() {
            [{
                    container: '#kategoriBlockContainerCreate',
                    modal: '#modalCreate'
                },
                {
                    container: '#kategoriBlockContainerEdit',
                    modal: '#modalEdit'
                }
            ].forEach(cfg => {
                const $container = $(cfg.container);
                $container.find('.kategori-block').each(function() {
                    const $block = $(this);
                    initKategoriSelect($block.find('.selectKategoriBlock'), $container, $block, cfg.modal);
                });
            });
        }

        $(document).ready(function() {

            // Saat bahasa diganti lewat tombol 🌐, bangun ulang grid & select2 supaya teks ikut berganti
            if (typeof window.applyLanguage === 'function') {
                const originalApplyLanguage = window.applyLanguage;
                window.applyLanguage = function(lang) {
                    originalApplyLanguage(lang);
                    initStaticSelects();
                    reinitAllKategoriSelects();
                    if ($('#grid').length && typeof loadTable === 'function') loadTable(true);
                };
            }

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
            // DEPARTMENT (khusus super depart) - wrapper tampil
            // ============================================================
            if (isSuperDepart) {
                $('#departmentWrapper').removeClass('hidden');
                $('#editDepartmentWrapper').removeClass('hidden');
            }

            // ============================================================
            // SELECT2 STATIS (create & edit)
            // ============================================================
            initStaticSelects();

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

            // kalau department berubah -> reload kompetensi di SEMUA blok kategori (create)
            $('#selectDepartment').on('change', function() {
                const departmentId = $(this).val();
                $('#kategoriBlockContainerCreate .kategori-block').each(function() {
                    const $block = $(this);
                    const kategoriId = $block.find('.selectKategoriBlock').val();
                    renderKompetensiForBlock($block, kategoriId, departmentId);
                });
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

            // isi template yang di-clone perlu diterjemahkan manual sesuai bahasa aktif
            translateEl($blockInDom);

            // beri id unik ke tiap blok kategori, supaya radio "nilai" antar blok
            // (dan antar kompetensi yang sama di blok berbeda) tidak saling bentrok
            const blockUid = 'blk' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
            $blockInDom.attr('data-block-uid', blockUid);

            const $selectKategori = $blockInDom.find('.selectKategoriBlock');

            initKategoriSelect($selectKategori, $container, $blockInDom, modalSelector);

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
                                title: t('Kategori sudah dipilih', 'Category already selected'),
                                text: t(
                                    'Kategori ini sudah digunakan pada blok lain. Silakan pilih kategori yang berbeda.',
                                    'This category is already used in another block. Please choose a different category.'
                                )
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
        // Teks statis diberi data-id / data-en supaya ikut berganti saat toggle bahasa.
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
                            <div class="text-sm text-gray-500 italic"
                                data-id="Tidak ada kompetensi pada kategori ini"
                                data-en="No competencies in this category">
                                ${t('Tidak ada kompetensi pada kategori ini', 'No competencies in this category')}
                            </div>
                        `;
                    }

                    res.forEach(item => {

                        const radioName = `nilai_${blockUid}_${item.id}`;
                        const currentVal = String(savedMap[item.id] ?? '');

                        const sortedDetails = [...item.details].sort((a, b) => Number(a.skala) - Number(b.skala));

                        // Opsi "Tidak Ada" selalu tersedia, karena tidak semua kompetensi perlu skala
                        const isNoneChecked = currentVal === '';

                        let optionsHtml = `
        <label class="nilaiOptionLabel flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition
            hover:border-gray-400 hover:bg-gray-50
            ${isNoneChecked ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-gray-200 bg-white'}">
            <input type="radio"
                name="${radioName}"
                value=""
                class="selectNilaiRadio mt-1 accent-gray-500"
                ${isNoneChecked ? 'checked' : ''}>
            <span class="flex-1">
                <span class="block text-sm font-semibold text-gray-600"
                    data-id="Tidak Ada" data-en="None">${t('Tidak Ada', 'None')}</span>
                <span class="block text-xs text-gray-400 leading-snug mt-0.5"
                    data-id="Kompetensi ini tidak memerlukan penilaian skala"
                    data-en="This competency does not require a scale rating">${t('Kompetensi ini tidak memerlukan penilaian skala', 'This competency does not require a scale rating')}</span>
            </span>
        </label>
    `;

                        if (sortedDetails.length) {
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
                        <span class="block text-sm font-semibold text-gray-800"
                            data-id="Skala ${d.skala}" data-en="Scale ${d.skala}">${t('Skala', 'Scale')} ${d.skala}</span>
                        ${desc ? `<span class="block text-xs text-gray-500 leading-snug mt-0.5">${desc}</span>` : ''}
                    </span>
                </label>
            `;
                            });
                        } else {
                            optionsHtml += `
            <div class="text-xs text-gray-400 italic px-1 col-span-full"
                data-id="Belum ada skala penilaian untuk kompetensi ini"
                data-en="No rating scale available for this competency">
                ${t('Belum ada skala penilaian untuk kompetensi ini', 'No rating scale available for this competency')}
            </div>
        `;
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
                        <div class="text-red-500 text-sm"
                            data-id="Gagal mengambil data kompetensi"
                            data-en="Failed to fetch competency data">
                            ${t('Gagal mengambil data kompetensi', 'Failed to fetch competency data')}
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
                    title: t('Lengkapi data', 'Complete the data'),
                    text: t(
                        'Minimal 1 kategori dengan kompetensi & nilai harus diisi.',
                        'At least 1 category with competencies & scores must be filled in.'
                    )
                });
                return;
            }

            Swal.fire({
                title: t('Simpan data?', 'Save data?'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: t('Ya, simpan', 'Yes, save'),
                cancelButtonText: t('Batal', 'Cancel')
            }).then((result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: t('Menyimpan...', 'Saving...'),
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
                            title: t('Berhasil', 'Success'),
                            text: res.message ?? t('Data berhasil disimpan', 'Data saved successfully'),
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
                        let msg = t('Gagal menyimpan', 'Failed to save');

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
                        title: t('Gagal', 'Failed'),
                        text: t('Data tidak ditemukan', 'Data not found')
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
                    title: t('Lengkapi data', 'Complete the data'),
                    text: t(
                        'Minimal 1 kategori dengan kompetensi & nilai harus diisi.',
                        'At least 1 category with competencies & scores must be filled in.'
                    )
                });
                return;
            }

            Swal.fire({
                title: t('Update data?', 'Update data?'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: t('Ya, update', 'Yes, update'),
                cancelButtonText: t('Batal', 'Cancel')
            }).then((result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: t('Menyimpan...', 'Saving...'),
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
                            title: t('Berhasil', 'Success'),
                            text: res.message ?? t('Data berhasil diupdate', 'Data updated successfully'),
                            timer: 1500,
                            showConfirmButton: false
                        });

                        $('#modalEdit').addClass('hidden').removeClass('flex');
                        $('#formEdit')[0].reset();
                        $('#kategoriBlockContainerEdit').empty();
                        loadTable();
                    },
                    error: function(xhr) {
                        let msg = t('Gagal update data', 'Failed to update data');

                        if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }

                        if (xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                        }

                        Swal.fire({
                            icon: 'error',
                            title: t('Gagal', 'Failed'),
                            text: msg
                        });
                    }
                });
            });
        });

        // ============================================================
        // GRID (DevExtreme)
        // loadTable(true) = bangun ulang grid (dipakai saat bahasa berganti)
        // loadTable()     = cukup update data, state grid (grouping, dll) tetap
        // ============================================================
        let gridInstance = null;

        function loadTable(rebuild = false) {
            const gridId = 'grid';
            const $container = $('#' + gridId);

            fetch("{{ route('kompetensi_pelatihan.data') }}")
                .then(res => res.json())
                .then(data => {
                    const rows = data.data;
                    const userPermissions = data.permissions || {};

                    if (gridInstance && rebuild) {
                        $container.dxDataGrid('dispose');
                        $container.empty();
                        gridInstance = null;
                    }

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
                        noDataText: t('Tidak ada data', 'No data'),

                        groupPanel: {
                            visible: true,
                            emptyPanelText: t(
                                'Tarik header kolom ke sini untuk mengelompokkan',
                                'Drag a column header here to group by that column'
                            )
                        },
                        grouping: {
                            autoExpandAll: true
                        },
                        allowColumnReordering: true,

                        columnChooser: {
                            enabled: true,
                            mode: "select",
                            allowSearch: true,
                            title: t('Pemilih Kolom', 'Column Chooser')
                        },
                        searchPanel: {
                            visible: true,
                            width: 240,
                            placeholder: t('Cari...', 'Search...')
                        },
                        paging: {
                            pageSize: 10
                        },
                        pager: {
                            showPageSizeSelector: true,
                            allowedPageSizes: [10, 25, 50],
                            showInfo: true,
                            infoText: t('Halaman {0} dari {1} ({2} data)', 'Page {0} of {1} ({2} items)')
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
                                caption: t('No', 'No'),
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
                                caption: t('Kategori', 'Category'),
                                dataField: 'kategori.nama',
                                calculateCellValue: row => row.kategori?.nama ?? '-',
                                groupIndex: 0
                            },

                            {
                                caption: t('Departement', 'Department'),
                                dataField: 'departement.depNama',
                                calculateCellValue: row => row.departement?.depNama ?? '-',
                                groupIndex: 1
                            },

                            {
                                caption: t('Kompetensi', 'Competency'),
                                dataField: 'kompetensi.nama',
                                calculateCellValue: row => row.kompetensi?.nama ?? '-'
                            },

                            {
                                caption: t('Jabatan', 'Job Title'),
                                dataField: 'peran.nama',
                                calculateCellValue: row => row.posisi?.posiNama ?? '-'
                            },
                            {
                                caption: t('Posisi', 'Position'),
                                dataField: 'posisi.nama',
                                calculateCellValue: row => row.peran?.name ?? '-'
                            },
                            {
                                caption: t('Workunit', 'Work Unit'),
                                dataField: 'workunit.nama',
                                calculateCellValue: row => row.workunit?.woruNama ?? '-'
                            },

                            {
                                caption: t('Nilai', 'Score'),
                                dataField: 'nilai',
                                alignment: 'center',
                                width: 100
                            },

                            {
                                caption: t('Aksi', 'Actions'),
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
                                            .attr('title', t('Ubah', 'Edit'))
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
                                            .attr('title', t('Duplikat', 'Clone'))
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
                                            .attr('title', t('Hapus', 'Delete'))
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
                        title: t('Gagal', 'Failed'),
                        text: t('Data tidak ditemukan', 'Data not found')
                    });
                }
            });
        }

        function deleteData(id) {

            return Swal.fire({
                title: t('Hapus data?', 'Delete data?'),
                text: t('Data yang sudah dihapus tidak bisa dikembalikan.', 'Deleted data cannot be restored.'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: t('Ya, hapus', 'Yes, delete'),
                cancelButtonText: t('Batal', 'Cancel'),
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6b7280'
            }).then((result) => {

                if (!result.isConfirmed) return;

                Swal.fire({
                    title: t('Menghapus...', 'Deleting...'),
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
                            title: t('Berhasil', 'Success'),
                            text: res.message ?? t('Data berhasil dihapus', 'Data deleted successfully'),
                            timer: 1500,
                            showConfirmButton: false
                        });

                        loadTable();
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: t('Gagal menghapus data', 'Failed to delete data')
                        });
                    });
            });
        }
    </script>
</x-app-layout>