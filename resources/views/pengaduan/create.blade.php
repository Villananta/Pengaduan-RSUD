@extends('layouts.guest')

@section('title', 'Buat Aduan')

@section('content')
@php
    $input = 'w-full rounded-lg bg-brand-light px-4 py-3.5 text-sm text-ink placeholder-placeholder outline-none ring-1 ring-transparent transition focus:ring-2 focus:ring-brand-800';
    $label = 'block text-sm font-semibold text-ink';
    $errorClass = 'mt-1.5 text-[11px] font-medium text-alert';
    $stepBadge = 'flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-800 text-[11px] font-bold text-white';
    $units = ['Instalasi Rawat Jalan (Poliklinik)', 'Instalasi Rawat Inap', 'Instalasi Gawat Darurat (IGD)', 'Instalasi Farmasi', 'Instalasi Radiologi', 'Instalasi Laboratorium Patologi', 'Instalasi Kamar Operasi (OK)', 'Instalasi Rekam Medis', 'Unit Kasir / Admisi', 'Unit Pelayanan Gizi (Dapur)', 'Unit Ruang Ibu & Anak', 'Layanan Humas & Informasi'];
@endphp

<div class="flex min-h-[720px] flex-col">

    {{-- Aside - Pemberitahuan Regulasi Pelayanan --}}
    <div class="w-full bg-brand-100 px-8 py-2.5">
        <div class="mx-auto flex w-full max-w-[1280px] flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-800">
                    <svg class="h-2.5 w-2.5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 11v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="7.5" r=".1" fill="currentColor"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold tracking-[0.33px] text-brand-800">Pengaduan Terdaftar</p>
                <p class="text-[11px] font-medium tracking-[0.33px] text-brand-900">Registrasi Resmi Sesuai Regulasi</p>
                <span class="text-[11px] text-brand-600">·</span>
                <p class="text-[11px] font-medium tracking-[0.33px] text-brand-600">Sesuai UU Pelayanan Publik &amp; PP 53/2014</p>
            </div>
            <div class="flex items-center gap-4 text-[11px]">
                <a href="#" class="flex items-center gap-1.5 font-medium text-ink-muted">
                    <svg class="h-2.5 w-2.5 text-alert" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.5 8.25V10a1 1 0 0 1-1.09 1A12.02 12.02 0 0 1 1 2.59 1 1 0 0 1 2 1.5h1.75a1 1 0 0 1 1 .85c.06.46.16.9.31 1.33a1 1 0 0 1-.23 1.05l-.74.74a9.6 9.6 0 0 0 4.43 4.43l.74-.74a1 1 0 0 1 1.05-.23c.43.15.87.25 1.33.31a1 1 0 0 1 .85 1z" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Call Center (031) 1500995
                </a>
                <a href="#" class="flex items-center gap-1.5 font-medium text-ink-muted">
                    <svg class="h-3 w-3 text-brand-800" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.41 2H6.6A4.6 4.6 0 0 0 2 6.6v4.72a4.6 4.6 0 0 0 2.17 3.92 6.37 6.37 0 0 0 1.68 1.55v2.13a1.6 1.6 0 0 0 2.42 1.36l2.15-1.28a4.6 4.6 0 0 0 .45.02 4.6 4.6 0 0 0 4.6-4.6V6.6A4.6 4.6 0 0 0 17.41 2Zm1.41 10.88A2.53 2.53 0 0 1 16.3 15.4c-.52.12-1.64.3-3.27-.63a11.42 11.42 0 0 1-4.3-4.24c-.87-1.51-.66-2.9-.62-3.18.04-.28.16-.55.28-.79.16-.31.35-.58.6-.8A.87.87 0 0 1 9.86 5.4c.27.05.5.02.72.59.22.57.77 1.88.84 2.02.07.14.1.3 0 .49-.1.19-.15.3-.3.47l-.18.2c-.1.11-.2.24-.08.45.84 1.5 1.77 2.44 3.15 3.17.12.07.27.03.37-.06l.43-.5c.08-.1.18-.17.32-.2.13-.03.27 0 .37.08.28.18.87.85 1.03 1.05.16.2.25.35.18.57Z"/>
                    </svg>
                    WhatsApp Resmi Humas
                </a>
            </div>
        </div>
    </div>

    {{-- Hero Section --}}
    <section class="w-full bg-gradient-to-b from-white via-brand-light to-brand-section px-8 pb-12 pt-8">
        <div class="mx-auto flex w-full max-w-[1216px] flex-col items-center">

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

            <div class="flex max-w-[768px] items-start justify-center gap-3 pb-8">
                @php
                    $badges = [
                        ['label' => 'Terjamin Amanah', 'sub' => 'Data dilindungi kebijakan privasi', 'ikon' => 'shield'],
                        ['label' => 'Dikonfirmasi Resmi', 'sub' => 'Kode tiket terverifikasi sistem', 'ikon' => 'check'],
                        ['label' => 'Tertangani Tuntas', 'sub' => 'SLA internal maks 12 hari kerja', 'ikon' => 'clock'],
                    ];
                @endphp
                @foreach ($badges as $b)
                    <div class="flex flex-1 items-center justify-center gap-2.5 rounded-xl bg-white px-4 py-7 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50">
                            @if ($b['ikon'] === 'shield')
                                <svg class="h-4 w-4 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 3 4.5 6v5c0 4.5 3 9 7.5 10.5C16.5 20 19.5 15.5 19.5 11V6L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @elseif ($b['ikon'] === 'check')
                                <svg class="h-4 w-4 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="m8.5 12 2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @else
                                <svg class="h-4 w-4 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @endif
                        </span>
                        <span>
                            <p class="text-base font-semibold leading-[22px] text-ink">{{ $b['label'] }}</p>
                            <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $b['sub'] }}</p>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Main Content: Asymmetric Split Grid --}}
    <section class="w-full flex-1 bg-brand-light px-8 py-8">
        <div class="mx-auto grid w-full max-w-[1216px] grid-cols-12 items-start gap-6">

            {{-- LEFT: Form 8 Kolom --}}
            <div class="col-span-12 flex flex-col gap-6 lg:col-span-8">

                {{-- Mandatory Legal Disclaimer Card --}}
                <div class="flex items-start gap-4 rounded-xl bg-brand-section p-5 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100">
                        <svg class="h-[18px] w-[18px] text-brand-900" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22s8-3.5 8-10V5l-8-3-8 3v7c0 6.5 8 10 8 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 8v4m0 3.5v.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-ink">Implikasi Hukum &amp; Etika Pelaporan</h2>
                        <p class="mt-1 text-[13px] leading-[21px] text-ink-muted">Seluruh aduan yang Anda sampaikan terikat pada ketentuan perlindungan data dan UU Pelayanan Publik. Penyampaian informasi yang tidak benar dapat menghambat proses verifikasi; setiap pelapor bertanggung jawab atas kebenaran keterangan yang diberikan. Tim verifikator akan menghubungi Anda maksimal 1x24 jam kerja untuk konfirmasi data.</p>
                    </div>
                </div>

                {{-- Form Canvas Card --}}
                <form action="{{ route('pengaduan.store') }}" method="POST" enctype="multipart/form-data" id="form-aduan" class="flex flex-col gap-8 rounded-2xl bg-white p-8 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    @csrf

                    {{-- Form Header --}}
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-brand-200/40 pb-4">
                        <div>
                            <h2 class="text-2xl font-semibold leading-8 text-ink">Buat Aduan Baru</h2>
                            <p class="text-[13px] leading-[18px] text-ink-muted">Lengkapi seluruh isian bertanda bintang (*) untuk mempercepat proses verifikasi.</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold tracking-[0.24px] text-ink">
                            <svg class="h-3 w-3 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="4" y="11" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                            Riwayat Aman &amp; Kerahasiaan Dijamin
                        </span>
                    </div>

                    {{-- STEP 1: Pilihan Kategori --}}
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-2">
                            <span class="{{ $stepBadge }}">1</span>
                            <h3 class="text-base font-semibold text-ink">Pilihan Kategori Masalah</h3>
                        </div>
                        <p class="text-[13px] text-ink-muted">Tentukan ruang lingkup persoalan yang Anda alami untuk alur penanganan tepat sasaran.</p>

                        <div class="grid gap-4 pt-1 sm:grid-cols-2">
                            @foreach ([
                                ['v' => 'fasilitas', 'judul' => 'Fasilitas Umum & Sarana Prasarana', 'desk' => 'Antrean pendaftaran, kebersihan toilet/ruangan, AC, parkir, fasilitas lift, rambu petunjuk arah, kasir, dll.', 'ikon' => 'bangunan'],
                                ['v' => 'medis', 'judul' => 'Pelayanan Medis & Tenaga Kesehatan', 'desk' => 'Tindakan dokter/perawat, komunikasi DPJP, keterlambatan visitasi, dispensing obat farmasi, edukasi terapi medis.', 'ikon' => 'medis'],
                            ] as $kat)
                                <label class="category-card group cursor-pointer rounded-xl bg-brand-light p-4 ring-1 ring-transparent transition">
                                    <input type="radio" name="kategori" value="{{ $kat['v'] }}" class="sr-only category-input" @checked(old('kategori') === $kat['v']) required>
                                    <div class="flex items-start justify-between">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50">
                                            @if ($kat['ikon'] === 'bangunan')
                                                <svg class="h-4 w-4 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M3 21h18M5 21V5l7-3 7 3v16M9 9h.01M15 9h.01M9 13h.01M15 13h.01M12 5v.01M12 9v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            @else
                                                <svg class="h-4 w-4 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-4 8c0-2.5 1.8-4 4-4s4 1.5 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            @endif
                                        </span>
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-50 ring-1 ring-brand-200 transition category-radio">
                                            <span class="h-2 w-2 rounded-full bg-white transition opacity-0 category-dot"></span>
                                        </span>
                                    </div>
                                    <p class="mt-2 text-base font-semibold text-ink">{{ $kat['judul'] }}</p>
                                    <p class="mt-1 text-[13px] leading-[18px] text-ink-muted">{{ $kat['desk'] }}</p>
                                </label>
                            @endforeach
                        </div>
                        @error('kategori')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- STEP 2: Identitas Pelapor --}}
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
                                <div class="mt-1.5 flex items-center gap-0 rounded-lg bg-brand-light ring-1 ring-transparent focus-within:ring-2 focus-within:ring-brand-800">
                                    <span class="border-r border-brand-200 px-3 text-xs font-semibold text-ink-muted">+62</span>
                                    <input id="no_wa" type="tel" name="no_wa" value="{{ old('no_wa') }}" placeholder="812-3456-7890" class="w-full rounded-r-lg bg-brand-light px-3 py-3.5 text-sm text-ink placeholder-placeholder outline-none" required>
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

                    {{-- STEP 3: Detail Kejadian --}}
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

                    {{-- STEP 4: Unggah Lampiran --}}
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
                            <span class="text-center text-[11px] font-medium tracking-[0.33px] text-ink-muted">JPG, PNG, atau PDF — maksimal 5 MB per berkas</span>
                        </label>
                        <input type="file" id="lampiran" name="lampiran[]" accept=".jpg,.jpeg,.png,.pdf" multiple class="hidden">
                        <div id="lampiran-preview" class="grid gap-2 sm:grid-cols-2"></div>
                        @error('lampiran')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        @error('lampiran.*')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    {{-- STEP 5: Persetujuan & Aksi --}}
                    <div class="flex flex-col gap-6 pt-4">
                        <label for="persetujuan" class="flex items-start gap-3">
                            <input id="persetujuan" type="checkbox" name="persetujuan" value="1" class="mt-1 h-[13px] w-[13px] shrink-0 rounded-sm accent-brand-800" {{ old('persetujuan') ? 'checked' : '' }} required>
                            <span class="text-[13px] leading-[18px] text-ink">
                                Saya menyatakan bahwa seluruh data dan keterangan yang saya berikan adalah <strong>benar dan dapat dipertanggungjawabkan</strong>. Dengan mengirimkan formulir ini saya <strong>memberikan persetujuan pengolahan data</strong> sesuai regulasi internal rumah sakit dan <strong>bersedia dihubungi tim verifikator</strong> untuk klarifikasi lebih lanjut.
                            </span>
                        </label>
                        @error('persetujuan')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="{{ route('pengaduan.create') }}" class="flex items-center justify-center rounded-lg px-6 py-3 text-sm font-semibold text-ink-muted transition hover:bg-brand-50">Batalkan</a>
                            <button type="submit" class="flex items-center gap-2 rounded-lg bg-brand-800 px-8 py-3.5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition hover:bg-brand-700">
                                <svg class="h-3 w-4 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Kirim Aduan &amp; Terima Nomor Tiket
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- RIGHT: Aside Informasi SOP & Metrik --}}
            <div class="col-span-12 flex flex-col gap-6 lg:col-span-4">

                {{-- Live SOP Workflow Stepper --}}
                <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center justify-between pb-2">
                        <h3 class="text-base font-bold text-ink">Alur Penanganan Terverifikasi</h3>
                        <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-semibold tracking-[0.33px] text-white" style="color:#26500F">SOP Baku</span>
                    </div>

                    <div class="flex flex-col">
                        @php
                            $steps = [
                                [
                                    'judul' => 'Registrasi & Validasi',
                                    'badge' => 'Saat ini',
                                    'desk' => 'Penerimaan tiket, validasi kelengkapan berkas, dan registrasi sistem komplain pusat.',
                                    'aktif' => true,
                                ],
                                [
                                    'judul' => 'Verifikasi Internal',
                                    'badge' => 'H+1',
                                    'desk' => 'Pelimpahan langsung ke Kepala Instalasi / Departemen Terkait untuk konfirmasi data.',
                                    'tautan' => 'Involving: Direksi & Komite Etik',
                                ],
                                [
                                    'judul' => 'Investigasi Mendalam',
                                    'badge' => 'H+3',
                                    'desk' => 'Pengecekan CCTV, catatan log rekam medis, audit internal Komite Etik & Keperawatan.',
                                ],
                                [
                                    'judul' => 'Mediasi & Penyelesaian',
                                    'badge' => 'Selesai',
                                    'desk' => 'Penerbitan surat tanggapan direksi, mediasi tatap muka, dan penutupan tiket perkara.',
                                ],
                            ];
                        @endphp
                        @foreach ($steps as $i => $s)
                            <div class="flex items-start gap-3 @if (!$loop->last) pb-6 @endif">
                                <div class="flex flex-col items-center">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full shadow-[0_1px_2px_rgba(0,0,0,0.05)]
                                        {{ $s['aktif'] ?? false ? 'bg-brand-800' : ($i === 3 ? 'bg-brand-700' : 'bg-brand-100') }}">
                                        @if ($s['aktif'] ?? false)
                                            <svg class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @elseif ($i === 3)
                                            <svg class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @else
                                            <svg class="h-3 w-3 text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                            </svg>
                                        @endif
                                    </span>
                                    @if (!$loop->last)
                                        <span class="my-1 w-0.5 flex-1 bg-brand-200" style="height: 56px"></span>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-base font-semibold text-ink">{{ $s['judul'] }}</p>
                                        <span class="rounded bg-brand-50 px-2 py-0.5 text-[11px] font-semibold tracking-[0.33px] text-brand-800">{{ $s['badge'] }}</span>
                                    </div>
                                    <p class="mt-1 text-[13px] leading-[18px] text-ink-muted">{{ $s['desk'] }}</p>
                                    @if (!empty($s['tautan']))
                                        <p class="mt-2 inline-flex items-center gap-1.5 rounded bg-brand-100 px-2.5 py-1 text-[11px] font-medium tracking-[0.33px] text-brand-600">
                                            <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            {{ $s['tautan'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Performance Transparency Metrics Bento --}}
                <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-ink">Transparansi Kinerja</h3>
                        <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Periode Q3 2026</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($metrik as $m)
                            <div class="flex flex-col rounded-xl bg-brand-light p-3.5">
                                <p class="text-xl font-bold text-brand-800">{{ $m['nilai'] }}</p>
                                <p class="text-xs font-semibold tracking-[0.24px] text-ink">{{ $m['label'] }}</p>
                                <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">{{ $m['sub'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-4 rounded-xl bg-brand-50 p-4">
                        <div class="relative flex h-16 w-16 shrink-0 items-center justify-center">
                            <svg class="h-16 w-16 -rotate-90" viewBox="0 0 64 64">
                                <circle cx="32" cy="32" r="26" fill="none" stroke="rgba(194,201,185,0.4)" stroke-width="6"/>
                                <circle cx="32" cy="32" r="26" fill="none" stroke="#26500F" stroke-width="6" stroke-linecap="round" stroke-dasharray="163.4" stroke-dashoffset="6.5"/>
                            </svg>
                            <span class="absolute text-xs font-bold tracking-[0.24px] text-brand-800">96%</span>
                        </div>
                        <div>
                            <p class="text-base font-semibold text-ink">Kecepatan Tindak Lanjut</p>
                            <p class="mt-1 text-[13px] leading-4 text-ink-muted">Aduan mendapatkan respons penanganan dalam 1x24 jam kerja.</p>
                        </div>
                    </div>
                </div>

                {{-- Mediation Desk Card --}}
                <div class="flex flex-col rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-100">
                            <svg class="h-[11px] w-[22px] text-brand-800" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 12a8.5 8.5 0 0 0-15.5-5M3 12a8.5 8.5 0 0 0 15.5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M3 6v3h3M21 18v-3h-3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-base font-semibold text-ink">Mediation Desk</p>
                            <p class="text-[11px] font-medium tracking-[0.33px] text-ink-muted">Fasilitas mediasi langsung</p>
                        </div>
                    </div>
                    <p class="mt-5 text-[13px] leading-[21px] text-ink-muted">Apabila aduan Anda tidak kunjung mendapat tanggapan dalam 5 hari kerja, Mediation Desk Direksi siap menjembatani komunikasi antara Anda dan unit terkait secara tatap muka di RSUD Dr. Soetomo.</p>
                    <p class="mt-4 flex items-center gap-2 text-sm font-semibold text-brand-800">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 3l7 3v5c0 4.5-3 8.5-7 9.5-4-1-7-5-7-9.5V6l7-3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                            <path d="m9 11 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Hubungi Mediation Desk
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.category-card').forEach(function (card) {
        const input = card.querySelector('.category-input');
        function refresh() {
            const checked = input.checked;
            card.classList.toggle('ring-2', checked);
            card.classList.toggle('ring-brand-800', checked);
            card.querySelector('.category-radio').classList.toggle('bg-brand-800', checked);
            card.querySelector('.category-radio').classList.toggle('ring-brand-800', checked);
            card.querySelector('.category-dot').classList.toggle('opacity-0', !checked);
            card.querySelector('.category-dot').classList.toggle('opacity-100', checked);
        }
        input.addEventListener('change', function () {
            document.querySelectorAll('.category-card').forEach(function (c) {
                c.classList.remove('ring-2', 'ring-brand-800');
                c.querySelector('.category-radio').classList.remove('bg-brand-800', 'ring-brand-800');
                c.querySelector('.category-dot').classList.add('opacity-0');
                c.querySelector('.category-dot').classList.remove('opacity-100');
            });
            refresh();
        });
        refresh();
    });

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