@extends('admin.layout')

@section('title', 'Input Aduan')

@section('content')

    @php
        // Class dasar dipegang di sini supaya tiap isian form konsisten
        // tanpa mengulang rantai class yang panjang di setiap kolom.
        $input = 'w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40';
        $label = 'font-label-md text-label-md font-bold text-on-surface';
        $bantuan = 'font-label-sm text-label-sm text-outline';
        $galat = 'font-label-sm text-label-sm text-error';
    @endphp

    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        <!-- Kepala Halaman -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
            <div class="flex items-start gap-space-md">
                <span class="w-12 h-12 rounded-lg bg-primary-container text-on-primary flex items-center justify-center shadow-sm shrink-0">
                    <x-symbol nama="support_agent" class="text-[26px]" />
                </span>

                <div class="flex flex-col">
                    <span class="font-label-sm text-label-sm uppercase tracking-wider font-bold bg-primary-container/60 text-on-primary px-space-xs py-0.5 rounded-xl w-fit">
                        Pencatatan Atas Nama Pelapor
                    </span>

                    <h1 class="font-headline-md text-headline-md text-on-surface font-bold tracking-tight mt-0.5">
                        Input Aduan dari Kanal Non-Web
                    </h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        Isi formulir ini dengan data yang sudah dikonfirmasi langsung dari pelapor. Tiket baru dibuat dengan nomor resmi dan bisa dilacak pelapor begitu kode tiket dan NRM-nya diberikan.
                    </p>
                </div>
            </div>

            <a
                href="{{ route('admin.pengaduan.index') }}"
                class="px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-label-lg text-label-lg hover:bg-surface-container-high transition-colors flex items-center gap-1.5 self-end lg:self-center"
            >
                <x-symbol nama="arrow_back" class="text-[18px]" />
                Kembali ke Daftar
            </a>
        </div>
        <!-- End of Kepala Halaman -->

        <!-- Penjelasan Alur -->
        <div class="p-space-sm rounded-lg bg-surface-container-low border border-outline-variant/30 flex flex-wrap items-center gap-x-space-lg gap-y-space-sm">
            <div class="flex items-center gap-2">
                <x-symbol nama="schedule" class="text-[16px] text-outline" />
                <span class="font-label-sm text-label-sm text-on-surface-variant">
                    Target penyelesaian <strong class="text-on-surface">{{ $hariKerja }} hari kerja</strong>
                </span>
            </div>

            <div class="flex items-center gap-2">
                <x-symbol nama="timer" class="text-[16px] text-outline" />
                <span class="font-label-sm text-label-sm text-on-surface-variant">
                    Batas investigasi unit <strong class="text-on-surface">{{ $hariInvestigasi }} hari kerja</strong>
                </span>
            </div>

            <div class="flex items-center gap-2">
                <x-symbol nama="lock" class="text-[16px] text-outline" />
                <span class="font-label-sm text-label-sm text-on-surface-variant">
                    Nomor tiket dibuat oleh sistem, tidak bisa diisi sendiri
                </span>
            </div>
        </div>
        <!-- End of Penjelasan Alur -->

        <!-- Pesan Kesalahan Validasi -->
        @if ($errors->any())
            <div
                role="alert"
                class="w-full bg-error-container text-on-error-container rounded-lg px-space-md py-space-sm flex items-start gap-space-sm"
            >
                <x-symbol nama="error" class="text-[20px] shrink-0" />
                <div class="flex flex-col gap-0.5">
                    <span class="font-label-lg text-label-lg font-bold">Periksa lagi isian formulir ini</span>

                    @foreach ($errors->all() as $pesan)
                        <span class="text-body-sm">{{ $pesan }}</span>
                    @endforeach
                </div>
            </div>
        @endif
        <!-- End of Pesan Kesalahan Validasi -->

        <!-- Formulir Input Aduan -->
        <form
            method="POST"
            action="{{ route('admin.pengaduan.store') }}"
            enctype="multipart/form-data"
            class="flex flex-col gap-space-md"
        >
            @csrf

            <!-- Bagian 1: Kanal Penerimaan -->
            <section class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col gap-space-md">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="phone_in_talk" class="text-primary-container text-[24px]" />
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Kanal Penerimaan</h2>
                </div>

                {{-- Kanal dicatat di dalam tiket lewat pesan pembuka, bukan lewat kolom
                     baru di tabel pengaduan, supaya skema pengaduan yang sudah ada
                     tidak berubah hanya untuk satu label tambahan. --}}
                <div class="flex flex-col gap-1.5 md:max-w-md">
                    <label for="kanal" class="{{ $label }}">Diterima Lewat Kanal <span class="text-error">*</span></label>
                    <select
                        id="kanal"
                        name="kanal"
                        required
                        class="{{ $input }} appearance-none"
                    >
                        <option value="" disabled @selected(! old('kanal'))>Pilih kanal...</option>
                        @foreach ($kanal as $opsi)
                            <option value="{{ $opsi->value }}" @selected(old('kanal') === $opsi->value)>
                                {{ $opsi->label() }}
                            </option>
                        @endforeach
                    </select>
                    <span class="{{ $bantuan }}">
                        Kanal ini ikut tertulis pada pesan pembuka tiket supaya jelas keluhan ini berasal dari luar portal.
                    </span>
                    @error('kanal')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                </div>
            </section>
            <!-- End of Bagian 1: Kanal Penerimaan -->

            <!-- Bagian 2: Kategori Masalah -->
            <section class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col gap-space-md">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="category" class="text-primary-container text-[24px]" />
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Kategori Masalah</h2>
                </div>

                <div class="flex flex-col gap-1.5 md:max-w-md">
                    <label for="kategori" class="{{ $label }}">Ruang Lingkup Persoalan <span class="text-error">*</span></label>
                    <select
                        id="kategori"
                        name="kategori"
                        required
                        class="{{ $input }} appearance-none"
                    >
                        <option value="" disabled @selected(! old('kategori'))>Pilih Kategori Masalah...</option>
                        @foreach ($kategori as $kat)
                            <option value="{{ $kat->value }}" @selected(old('kategori') === $kat->value)>
                                {{ $kat->judul() }}
                            </option>
                        @endforeach
                    </select>
                    <span id="kategori-deskripsi" class="{{ $bantuan }}">
                        @foreach ($kategori as $kat)
                            <span class="hidden" data-kategori-deskripsi="{{ $kat->value }}">{{ $kat->deskripsi() }}</span>
                        @endforeach
                    </span>
                    @error('kategori')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                </div>
            </section>
            <!-- End of Bagian 2: Kategori Masalah -->

            <!-- Bagian 3: Identitas Pelapor -->
            <section class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col gap-space-md">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="person" class="text-primary-container text-[24px]" />
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Identitas Pelapor</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-space-md">
                    <div class="flex flex-col gap-1.5">
                        <label for="nama_lengkap" class="{{ $label }}">Nama Lengkap Sesuai KTP <span class="text-error">*</span></label>
                        <input
                            id="nama_lengkap"
                            type="text"
                            name="nama_lengkap"
                            value="{{ old('nama_lengkap') }}"
                            placeholder="Nama lengkap pelapor"
                            maxlength="255"
                            required
                            class="{{ $input }}"
                        >
                        @error('nama_lengkap')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="nrm" class="{{ $label }}">Nomor Rekam Medis (NRM) <span class="text-error">*</span></label>
                        <input
                            id="nrm"
                            type="text"
                            name="nrm"
                            value="{{ old('nrm') }}"
                            placeholder="Contoh: 12-34-56-78"
                            required
                            class="{{ $input }}"
                        >
                        {{-- NRM dipakai sebagai kunci verifikasi pelapor saat melacak
                             tiket, jadi keliru di sini berarti pelapor tidak akan
                             pernah bisa membaca tiketnya sendiri. --}}
                        <span class="{{ $bantuan }}">
                            Wajib dicatat sesuai kartu berobat. NRM ini yang dipakai pelapor untuk memverifikasi tiketnya.
                        </span>
                        @error('nrm')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="no_wa" class="{{ $label }}">Nomor WhatsApp Aktif <span class="text-error">*</span></label>
                        <input
                            id="no_wa"
                            type="tel"
                            name="no_wa"
                            value="{{ old('no_wa') }}"
                            placeholder="081234567890"
                            required
                            class="{{ $input }}"
                        >
                        <span class="{{ $bantuan }}">Hanya angka 9 sampai 15 digit, tanpa spasi dan tanda hubung.</span>
                        @error('no_wa')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="email" class="{{ $label }}">Alamat Email <span class="text-error">*</span></label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="nama@domain.com"
                            required
                            class="{{ $input }}"
                        >
                        @error('email')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                    </div>

                    <div class="md:col-span-2 xl:col-span-3 flex flex-col gap-1.5">
                        <label for="alamat" class="{{ $label }}">Alamat Domisili Pelapor <span class="text-error">*</span></label>
                        <input
                            id="alamat"
                            type="text"
                            name="alamat"
                            value="{{ old('alamat') }}"
                            placeholder="Contoh: Jl. Dharmawangsa No. 12, RT 02/RW 04, Gubeng, Surabaya"
                            required
                            class="{{ $input }}"
                        >
                        @error('alamat')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>
            <!-- End of Bagian 3: Identitas Pelapor -->

            <!-- Bagian 4: Detail Kejadian -->
            <section class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col gap-space-md">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="assignment" class="text-primary-container text-[24px]" />
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Detail Kejadian</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
                    <div class="flex flex-col gap-1.5">
                        <label for="waktu_kejadian" class="{{ $label }}">Waktu &amp; Tanggal Kejadian <span class="text-error">*</span></label>
                        <input
                            id="waktu_kejadian"
                            type="datetime-local"
                            name="waktu_kejadian"
                            value="{{ old('waktu_kejadian') }}"
                            required
                            class="{{ $input }}"
                        >
                        @error('waktu_kejadian')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="unit" class="{{ $label }}">Unit / Instalasi Terkait <span class="text-error">*</span></label>
                        <select
                            id="unit"
                            name="unit"
                            required
                            class="{{ $input }} appearance-none"
                        >
                            <option value="" disabled @selected(! old('unit'))>Pilih Unit / Ruang Layanan...</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit }}" @selected(old('unit') === $unit)>{{ $unit }}</option>
                            @endforeach
                        </select>
                        @error('unit')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                    </div>

                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <label for="subjek" class="{{ $label }}">Ringkasan Inti Masalah (Subjek) <span class="text-error">*</span></label>
                        <input
                            id="subjek"
                            type="text"
                            name="subjek"
                            value="{{ old('subjek') }}"
                            placeholder="Contoh: Penyerahan obat tidak disertai penjelasan pemakaian"
                            maxlength="255"
                            required
                            class="{{ $input }}"
                        >
                        @error('subjek')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                    </div>

                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <label for="deskripsi" class="{{ $label }}">Uraian Runtut Kronologi Kejadian <span class="text-error">*</span></label>
                        <textarea
                            id="deskripsi"
                            name="deskripsi"
                            rows="5"
                            placeholder="Tuliskan kronologi sesuai urutan waktu dari pelapor: kapan kejadiannya, pihak atau petugas yang berinteraksi bila diketahui, kendala spesifik yang dialami, dan harapan penanganannya..."
                            required
                            class="{{ $input }} resize-none"
                        >{{ old('deskripsi') }}</textarea>
                        {{-- Uraian ini jadi bahan utama investigasi unit, jadi admin
                             diminta menuliskan kronologi lengkap, bukan meringkas
                             keluhan supaya Investigation Unit tidak perlu
                                             menghubungi pelapor berulang kali. --}}
                        @error('deskripsi')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>
            <!-- End of Bagian 4: Detail Kejadian -->

            <!-- Bagian 5: Lampiran -->
            <section class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col gap-space-md">
                <div class="flex items-center justify-between gap-space-sm flex-wrap">
                    <div class="flex items-center gap-space-xs">
                        <x-symbol nama="attach_file" class="text-primary-container text-[24px]" />
                        <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Lampiran Pendukung</h2>
                    </div>

                    <span class="{{ $bantuan }}">Opsional, maksimal 5 berkas</span>
                </div>

                {{-- Lampiran yang diunggah admin sering berupa foto yang dikirim
                     pelapor lewat WhatsApp, jadi berkas tetap dibaca lewat disk
                     lampiran yang sama seperti unggahan dari portal. --}}
                <div class="flex flex-col gap-1.5">
                    <label
                        for="lampiran"
                        class="cursor-pointer flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-outline-variant/50 bg-surface-container-low px-6 py-6 transition hover:border-secondary/60 hover:bg-surface-container"
                    >
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-surface-container-lowest shadow-sm">
                            <x-symbol nama="upload" class="text-[22px] text-primary-container" />
                        </span>
                        <span class="font-label-lg text-label-lg font-bold text-on-surface">Tarik &amp; letakkan berkas, atau klik untuk memilih</span>
                        <span class="{{ $bantuan }}">JPG, PNG, atau PDF — maksimal 4 MB per berkas</span>
                    </label>
                    <input
                        type="file"
                        id="lampiran"
                        name="lampiran[]"
                        accept=".jpg,.jpeg,.png,.pdf"
                        multiple
                        class="hidden"
                    >
                    <div id="lampiran-preview" class="grid gap-2 sm:grid-cols-2"></div>
                    @error('lampiran')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                    @error('lampiran.*')<span class="{{ $galat }}">{{ $message }}</span>@enderror
                </div>
            </section>
            <!-- End of Bagian 5: Lampiran -->

            <!-- Bagian 6: Pernyataan dan Aksi -->
            <div class="flex flex-col gap-space-md bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
                <div class="flex items-start gap-2">
                    <input
                        id="persetujuan"
                        type="checkbox"
                        name="persetujuan"
                        value="1"
                        @checked(old('persetujuan'))
                        required
                        class="mt-0.5 h-[18px] w-[18px] shrink-0 rounded-sm accent-brand-800"
                    >
                    <label for="persetujuan" class="flex flex-col gap-1">
                        <span class="font-label-lg text-label-lg font-bold text-on-surface">
                            Data pelapor sudah dikonfirmasi langsung <span class="text-error">*</span>
                        </span>
                        <span class="font-body-sm text-body-sm text-on-surface-variant">
                            Saya menyatakan data di atas sudah dikonfirmasi langsung kepada pelapor melalui kanal yang dipilih, termasuk kesepersetujuan pengolahan datanya. Pelapor akan diberi tahu nomor tiket ini agar bisa melacaknya sendiri.
                        </span>
                    </label>
                </div>
                @error('persetujuan')<span class="{{ $galat }}">{{ $message }}</span>@enderror

                <div class="flex items-center justify-between gap-space-sm flex-wrap pt-space-xs">
                    <span class="font-body-sm text-body-sm text-outline">
                        Tiket baru langsung berstatus diterima dan masuk ke daftar pengaduan dengan batas {{ $hariKerja }} hari kerja.
                    </span>

                    <div class="flex items-center gap-space-sm">
                        <a
                            href="{{ route('admin.pengaduan.index') }}"
                            class="px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-label-lg text-label-lg hover:bg-surface-container-high transition-colors"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="px-space-md py-2 rounded-lg bg-primary text-on-primary font-label-lg text-label-lg hover:bg-primary/90 transition-colors flex items-center gap-1.5 shadow-sm"
                        >
                            <x-symbol nama="save" class="text-[18px]" />
                            Simpan &amp; Buka Tiket
                        </button>
                    </div>
                </div>
            </div>
            <!-- End of Bagian 6: Pernyataan dan Aksi -->
        </form>
        <!-- End of Formulir Input Aduan -->

    </div>

@endsection

{{-- Deskripsi kategori dan pratinjau lampiran ditangani di sisi klien. --}}
@push('scripts')

<script>
    const selectKategori = document.getElementById('kategori');
    const wrapDeskripsi = document.getElementById('kategori-deskripsi');

    if (selectKategori && wrapDeskripsi) {
        const deskripsi = wrapDeskripsi.querySelectorAll('[data-kategori-deskripsi]');

        function refreshDeskripsi() {
            deskripsi.forEach(function (item) {
                item.classList.toggle('hidden', item.dataset.kategoriDeskripsi !== selectKategori.value);
            });
        }

        selectKategori.addEventListener('change', refreshDeskripsi);
        refreshDeskripsi();
    }

    const inputFile = document.getElementById('lampiran');
    const preview = document.getElementById('lampiran-preview');

    if (inputFile) {
        function render(files) {
            preview.innerHTML = '';
            Array.from(files).forEach(function (file, i) {
                const el = document.createElement('div');
                el.className = 'flex items-center gap-2 rounded-lg bg-surface-container-low px-3 py-2 text-label-md';
                const size = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                el.innerHTML = '<span class="max-w-[220px] truncate font-bold text-on-surface">' + file.name.replace(/"/g, '&quot;') + '</span>' +
                    '<span class="ml-auto shrink-0 text-label-sm text-outline">' + size + '</span>' +
                    '<button type="button" class="shrink-0 text-error" data-i="' + i + '" aria-label="Hapus">' +
                    '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>';
                el.querySelector('button').addEventListener('click', function () {
                    const dt = new DataTransfer();
                    Array.from(inputFile.files).forEach(function (f, fi) { if (fi !== i) dt.items.add(f); });
                    inputFile.files = dt.files;
                    render(inputFile.files);
                });
                preview.appendChild(el);
            });
        }
        inputFile.addEventListener('change', function () { render(inputFile.files); });

        ['dragover', 'dragleave', 'drop'].forEach(function (evt) {
            inputFile.closest('label').addEventListener(evt, function (e) {
                e.preventDefault();
                e.currentTarget.classList.toggle('border-secondary/60', evt === 'dragover');
            });
        });
        inputFile.closest('label').addEventListener('drop', function (e) {
            const dt = new DataTransfer();
            Array.from(e.dataTransfer.files).forEach(function (f) { dt.items.add(f); });
            Array.from(inputFile.files).forEach(function (f) { dt.items.add(f); });
            inputFile.files = dt.files;
            render(inputFile.files);
        });
    }
</script>

@endpush