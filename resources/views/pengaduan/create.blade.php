@extends('layouts.guest')

@use(App\Support\Sla)

@section('title', 'Buat Aduan')

@section('content')

@php
    // Class dasar dipegang di sini supaya tiap isian form konsisten
    // tanpa harus mengulang rantai class yang panjang.
    $input = 'w-full rounded-xl bg-brand-light px-4 py-3.5 text-sm text-ink placeholder-placeholder outline-none ring-1 ring-transparent transition focus:ring-2 focus:ring-brand-800';
    $label = 'block text-sm font-semibold text-ink';
    $errorClass = 'mt-1.5 text-[11px] font-medium text-alert';
    $stepBadge = 'flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-800 text-[11px] font-bold text-white';
@endphp

<div class="flex min-h-[720px] flex-col">

    <!-- Hero Section -->
    <section class="w-full bg-gradient-to-b from-white via-brand-light to-brand-section px-8 pb-12 pt-8">
        <div class="flex w-full flex-col items-center">

            <div class="flex flex-col items-center gap-4 pb-4">
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-100 px-4 py-1.5 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <svg class="h-3 w-3 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2 3 7v10l9 5 9-5V7l-9-5Zm-1 9V6h2v5h5v2h-5v5h-2v-5H6v-2h5Z" fill="currentColor"/>
                    </svg>
                    <span class="text-xs font-semibold tracking-[0.24px] text-brand-500">Sistem Aduan Resmi RSUD Dr. Soetomo</span>
                </span>
            </div>

            <div class="flex max-w-[896px] flex-col items-center pb-3">
                <h1 class="text-center text-[32px] font-bold leading-10 tracking-[-0.8px] text-ink">Sampaikan Pengaduan Layanan Secara Terbuka &amp; Bertanggung Jawab</h1>
            </div>

            <div class="flex max-w-[672px] flex-col items-center pb-8">
                <p class="text-center text-base leading-[26px] text-ink-muted">Setiap masukan yang Anda sampaikan melalui kanal ini menjadi bagian dari evaluasi mutu layanan. Data pribadi Anda dilindungi dan hanya digunakan untuk keperluan investigasi.</p>
            </div>
        </div>
    </section>
    <!-- End of Hero Section -->

    <!-- Main Content: Asymmetric Split Grid -->
    <section class="w-full flex-1 bg-brand-light px-8 py-8">
        <div class="grid w-full grid-cols-12 items-start gap-6">

            <!-- Kiri: Form 8 Kolom -->
            <div class="col-span-12 flex flex-full gap-6 lg:full-span-8">

                <!-- Form Canvas Card -->
                <form action="{{ route('pengaduan.store') }}" method="POST" enctype="multipart/form-data" id="form-aduan" class="flex flex-col gap-8 rounded-2xl bg-white p-8 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    @csrf

                    <!-- Step 1: Pilihan Kategori -->
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-2">
                            <span class="{{ $stepBadge }}">1</span>
                            <h3 class="text-base font-semibold text-ink">Pilihan Kategori Masalah</h3>
                        </div>
                        <p class="text-[13px] text-ink-muted">Tentukan ruang lingkup persoalan yang Anda alami untuk alur penanganan tepat sasaran.</p>

                        <div class="relative pt-1">
                            <select id="kategori" name="kategori" class="{{ $input }} appearance-none pr-10" required>
                                <option value="" disabled @selected(! old('kategori'))>Pilih Kategori Masalah...</option>
                                @foreach ($kategori as $kat)
                                    <option value="{{ $kat->value }}" @selected(old('kategori') === $kat->value)>{{ $kat->label() }}</option>
                                @endforeach
                            </select>
                            <svg class="pointer-events-none absolute right-3 top-1/2 h-2 w-3 -translate-y-1/2 text-placeholder" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="m1 1 5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p id="kategori-deskripsi" class="text-[13px] leading-[18px] text-ink-muted">
                            @foreach ($kategori as $kat)
                                <span class="hidden" data-kategori-deskripsi="{{ $kat->value }}">{{ $kat->deskripsi() }}</span>
                            @endforeach
                        </p>
                        @error('kategori')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Step 2: Identitas Pelapor -->
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <span class="{{ $stepBadge }}">2</span>
                            <h3 class="text-base font-semibold text-ink">Identitas Pelapor</h3>
                        </div>

                        <div class="grid gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <label for="nama_lengkap" class="{{ $label }}">Nama Lengkap Sesuai KTP *</label>
                                <input id="nama_lengkap" type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" placeholder="Masukkan nama lengkap Anda" class="{{ $input }} mt-1.5" required>
                                @error('nama_lengkap')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label for="nrm" class="{{ $label }}">Nomor Rekam Medis (NRM) Pasien *</label>
                                </div>
                                <input id="nrm" type="text" name="nrm" value="{{ old('nrm') }}" placeholder="Contoh: 12-34-56-78" class="{{ $input }} mt-1.5" required>
                                <p class="mt-1 flex items-center gap-1 text-[11px] font-medium leading-4 tracking-[0.33px] text-brand-600">
                                    <svg class="h-2.5 w-2.5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                        <path d="M12 11v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <circle cx="12" cy="7.5" r=".1" fill="currentColor"/>
                                    </svg>
                                    NRM dapat dilihat pada kartu berobat atau SIM-RS setelah 30 hari.
                                </p>
                                @error('nrm')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="no_wa" class="{{ $label }}">Nomor WhatsApp Aktif *</label>
                                <div class="mt-1.5 flex items-center gap-0 rounded-xl bg-brand-light ring-1 ring-transparent focus-within:ring-2 focus-within:ring-brand-800">
                                    <span class="border-r border-brand-200 px-3 text-xs font-semibold text-ink-muted">+62</span>
                                    <input id="no_wa" type="tel" name="no_wa" value="{{ old('no_wa') }}" placeholder="812-3456-7890" class="w-full rounded-r-xl bg-brand-light px-3 py-3.5 text-sm text-ink placeholder-placeholder outline-none" required>
                                </div>
                                <p class="mt-1 text-[11px] font-medium tracking-[0.33px] text-ink-muted">Pemberitahuan progress tiket akan dikirimkan otomatis via WA.</p>
                                @error('no_wa')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="{{ $label }}">Alamat Email Aktif *</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="namaanda@domain.com" class="{{ $input }} mt-1.5" required>
                                @error('email')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="alamat" class="{{ $label }}">Alamat Domisili / Tempat Tinggal Pelapor *</label>
                                <input id="alamat" type="text" name="alamat" value="{{ old('alamat') }}" placeholder="Contoh: Jl. Dharmawangsa No. 12, RT 02/RW 04, Gubeng, Surabaya" class="{{ $input }} mt-1.5" required>
                                @error('alamat')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Detail Kejadian -->
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <span class="{{ $stepBadge }}">3</span>
                            <h3 class="text-base font-semibold text-ink">Detail Kejadian</h3>
                        </div>

                        <div class="grid gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <label for="waktu_kejadian" class="{{ $label }}">Waktu &amp; Tanggal Kejadian *</label>
                                <input id="waktu_kejadian" type="datetime-local" name="waktu_kejadian" value="{{ old('waktu_kejadian') }}" class="{{ $input }} mt-1.5" required>
                                @error('waktu_kejadian')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="unit" class="{{ $label }}">Unit / Instalasi Terkait *</label>
                                <div class="relative mt-1.5">
                                    <select id="unit" name="unit" class="{{ $input }} appearance-none pr-10" required>
                                        <option value="" disabled selected>Pilih Unit / Ruang Layanan...</option>
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit }}" @selected(old('unit') === $unit)>{{ $unit }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="pointer-events-none absolute right-3 top-1/2 h-2 w-3 -translate-y-1/2 text-placeholder" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="m1 1 5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                @error('unit')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="subjek" class="{{ $label }}">Ringkasan Inti Masalah (Subjek) *</label>
                                <input id="subjek" type="text" name="subjek" value="{{ old('subjek') }}" placeholder="Contoh: Keterlambatan Pengambilan Resep Obat Kronis lebih dari 4 Jam" class="{{ $input }} mt-1.5" required>
                                @error('subjek')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="deskripsi" class="{{ $label }}">Uraian Runtut Kronologi Kejadian *</label>
                                <textarea id="deskripsi" name="deskripsi" rows="4" placeholder="Jelaskan secara kronologis: kapan Anda tiba, pihak/nama petugas yang berinteraksi (bila diketahui), apa kendala spesifik yang dialami, dan apa harapan penanganan Anda..." class="{{ $input }} resize-none" required>{{ old('deskripsi') }}</textarea>
                                @error('deskripsi')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Unggah Lampiran -->
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="{{ $stepBadge }}">4</span>
                                <h3 class="text-base font-semibold text-ink">Unggah Lampiran Pendukung</h3>
                            </div>
                            <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Opsional</p>
                        </div>

                        <p class="text-[13px] text-ink-muted">Lampirkan foto nomor antrean, resep, kwitansi, foto kondisi fasilitas fisik, atau resume medis yang relevan.</p>

                        <label for="lampiran" class="upload-zone flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-brand-200 bg-brand-light px-6 py-6 transition hover:bg-brand-50">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                                <svg class="h-[18px] w-[25px] text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 8l5-5 5 5M12 3v12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="text-center text-base font-semibold text-ink">Tarik &amp; letakkan berkas, atau klik untuk memilih</span>
                            <span class="text-center text-[11px] font-medium tracking-[0.33px] text-ink-muted">JPG, PNG, atau PDF — maksimal 4 MB per berkas</span>
                        </label>
                        <input type="file" id="lampiran" name="lampiran[]" accept=".jpg,.jpeg,.png,.pdf" multiple class="hidden">
                        <div id="lampiran-preview" class="grid gap-2 sm:grid-cols-2"></div>
                        @error('lampiran')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        @error('lampiran.*')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    <!-- Step 5: Persetujuan dan Aksi -->
                    <div class="flex flex-col gap-6 pt-4">
                        <label for="persetujuan" class="flex items-start gap-3">
                            <input id="persetujuan" type="checkbox" name="persetujuan" value="1" class="mt-1 h-[13px] w-[13px] shrink-0 rounded-sm accent-brand-800" {{ old('persetujuan') ? 'checked' : '' }} required>
                            <span class="text-[13px] leading-[18px] text-ink">
                                Saya menyatakan bahwa seluruh data dan keterangan yang saya berikan adalah <strong>benar dan dapat dipertanggungjawabkan</strong>. Dengan mengirimkan formulir ini saya <strong>memberikan persetujuan pengolahan data</strong> sesuai regulasi internal rumah sakit dan <strong>bersedia dihubungi tim verifikator</strong> untuk klarifikasi lebih lanjut.
                            </span>
                        </label>
                        @error('persetujuan')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="{{ route('pengaduan.create') }}" class="flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold text-ink-muted transition hover:bg-brand-50">Batalkan</a>
                            <button type="submit" class="flex items-center gap-2 rounded-full bg-brand-800 px-8 py-3.5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition hover:bg-brand-700">
                                <svg class="h-3 w-4 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Kirim Aduan &amp; Terima Nomor Tiket
                            </button>
                        </div>
                    </div>
                </form>
                <!-- End of Form Canvas Card -->
            </div>
            <!-- End of Kiri: Form 8 Kolom -->
        </div>
    </section>
    <!-- End of Main Content: Asymmetric Split Grid -->
</div>
<!-- End of Wrapper Form -->

@endsection

<!-- Deskripsi kategori dan pratinjau lampiran ditangani di sisi klien. -->
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
    const zone = document.querySelector('.upload-zone');

    if (inputFile) {
        function render(files) {
            preview.innerHTML = '';
            Array.from(files).forEach(function (file, i) {
                const el = document.createElement('div');
                el.className = 'flex items-center gap-2 rounded-lg bg-brand-light px-3 py-2 text-[13px]';
                const size = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                el.innerHTML = '<span class="max-w-[220px] truncate font-medium text-ink">' + file.name.replace(/"/g, '&quot;') + '</span>' +
                    '<span class="ml-auto shrink-0 text-[11px] text-ink-muted">' + size + '</span>' +
                    '<button type="button" class="shrink-0 text-alert" data-i="' + i + '" aria-label="Hapus">' +
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
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                zone.classList.toggle('border-brand-800', evt === 'dragover');
            });
        });
        zone.addEventListener('drop', function (e) {
            const dt = new DataTransfer();
            Array.from(e.dataTransfer.files).forEach(function (f) { dt.items.add(f); });
            Array.from(inputFile.files).forEach(function (f) { dt.items.add(f); });
            inputFile.files = dt.files;
            render(inputFile.files);
        });
    }
</script>

@endpush