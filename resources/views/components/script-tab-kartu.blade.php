{{--
    Perpindahan tab pada kartu percakapan.

    Dipakai konsol humas dan halaman disposisi unit. Tombol dan panel dicari
    lewat atribut data-tab dan data-panel, jadi menambah tab cukup menambah
    pasangan atribut itu di template tanpa menyentuh skrip ini.
--}}

@push('scripts')
    <script>
        (function () {
            const tombol = document.querySelectorAll('[data-tab]');
            const panel = document.querySelectorAll('[data-panel]');

            tombol.forEach(function (tombolTab) {
                tombolTab.addEventListener('click', function () {
                    const aktif = tombolTab.dataset.tab;

                    tombol.forEach(function (lain) {
                        const dipilih = lain === tombolTab;

                        lain.className = dipilih
                            ? 'flex-1 py-3 px-space-md rounded-lg font-title-sm text-title-sm font-bold flex items-center justify-center gap-1.5 bg-surface-container-lowest text-on-surface shadow-sm'
                            : 'flex-1 py-3 px-space-md rounded-lg font-title-sm text-title-sm font-semibold text-on-surface-variant hover:text-on-surface flex items-center justify-center gap-1.5';
                    });

                    panel.forEach(function (isi) {
                        isi.classList.toggle('hidden', isi.dataset.panel !== aktif);
                    });
                });
            });
        })();
    </script>
@endpush
