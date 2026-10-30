@props([
    'formId' => 'form-bulk-action',
    'toolbarId' => 'toolbar-bulk',
    'selectAllId' => 'select-all',
    'rowCheckboxClass' => '.row-checkbox',
    'bulkCountId' => 'bulk-count',
    'btnClearId' => 'btn-clear-selection',
    'aksiSelectName' => 'aksi',
    'disposisiFieldsId' => 'bulk-disposisi-fields',
    'kasusBeratFieldsId' => 'bulk-kasus-berat-fields',
])

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('{{ $formId }}');
        const toolbar = document.getElementById('{{ $toolbarId }}');
        const selectAll = document.getElementById('{{ $selectAllId }}');
        const rowCheckboxes = document.querySelectorAll('{{ $rowCheckboxClass }}');
        const bulkCount = document.getElementById('{{ $bulkCountId }}');
        const btnClear = document.getElementById('{{ $btnClearId }}');
        const submitBtn = toolbar?.querySelector('button[type="submit"]');
        const aksiSelect = toolbar?.querySelector('select[name="{{ $aksiSelectName }}"]');
        const disposisiFields = document.getElementById('{{ $disposisiFieldsId }}');
        const kasusBeratFields = document.getElementById('{{ $kasusBeratFieldsId }}');

        if (!form || !toolbar || !selectAll || !rowCheckboxes.length) {
            return;
        }

        function updateToolbar() {
            const checked = document.querySelectorAll('{{ $rowCheckboxClass }}:checked');
            const count = checked.length;

            if (count > 0) {
                toolbar.classList.remove('hidden');
                bulkCount && (bulkCount.textContent = count);
                submitBtn && (submitBtn.disabled = false);
            } else {
                toolbar.classList.add('hidden');
                submitBtn && (submitBtn.disabled = true);
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
        }

        function updateSelectAll() {
            const total = rowCheckboxes.length;
            const checked = document.querySelectorAll('{{ $rowCheckboxClass }}:checked').length;

            if (checked === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (checked === total) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }

        function showAksiFields() {
            if (!aksiSelect) return;
            const aksi = aksiSelect.value;
            disposisiFields?.classList.toggle('hidden', aksi !== 'disposisi');
            kasusBeratFields?.classList.toggle('hidden', aksi !== 'kasus_berat');
        }

        // Pilih semua tiket pada halaman ini
        selectAll.addEventListener('change', function () {
            rowCheckboxes.forEach(cb => {
                cb.checked = this.checked;
                cb.closest('tr')?.classList.toggle('ring-2', this.checked);
                cb.closest('tr')?.classList.toggle('ring-primary', this.checked);
            });
            updateToolbar();
        });

        // Individual checkboxes
        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                updateToolbar();
                updateSelectAll();
                this.closest('tr')?.classList.toggle('ring-2', this.checked);
                this.closest('tr')?.classList.toggle('ring-primary', this.checked);
            });
        });

        // Aksi select change
        aksiSelect?.addEventListener('change', showAksiFields);

        // Clear selection
        btnClear?.addEventListener('click', function () {
            rowCheckboxes.forEach(cb => {
                cb.checked = false;
                cb.closest('tr')?.classList.remove('ring-2', 'ring-primary');
            });
            selectAll.checked = false;
            selectAll.indeterminate = false;
            if (aksiSelect) aksiSelect.value = '';
            disposisiFields?.classList.add('hidden');
            kasusBeratFields?.classList.add('hidden');
            updateToolbar();
        });

        // Keadaan awal toolbar saat halaman dibuka
        updateToolbar();
    });
</script>
@endpush