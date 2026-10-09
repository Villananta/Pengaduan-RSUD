@extends('admin.layout')

@section('title', $unit ? 'Ubah Unit '.$unit->kode : 'Tambah Unit')

@section('content')

    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        {{-- Kode unit ikut ditampilkan di judul supaya admin tidak salah
             membuka halaman ubah ketika sedang berpindah antar unit. --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
            <div class="flex items-start gap-space-md">
                <span class="w-12 h-12 rounded-lg bg-primary-container text-on-primary flex items-center justify-center shadow-sm shrink-0">
                    <x-symbol :nama="$unit ? 'edit' : 'add'" class="text-[26px]" />
                </span>

                <div class="flex flex-col">
                    <span class="font-label-sm text-label-sm uppercase tracking-wider font-bold bg-primary-container/60 text-on-primary px-space-xs py-0.5 rounded-xl w-fit">
                        {{ $unit ? 'Perbarui Master Data Unit' : 'Unit Baru di Master Data' }}
                    </span>

                    <h1 class="font-headline-md text-headline-md text-on-surface font-bold tracking-tight mt-0.5">
                        {{ $unit ? $unit->namaLengkap() : 'Tambah Unit Baru' }}
                    </h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        Unit yang aktif bisa dipilih sebagai tujuan disposisi pengaduan. Data kontak dipakai untuk menagih PIC saat Investigation Unit lewat hari ke-{{ $hariInvestigasi }}.
                    </p>
                </div>
            </div>

            <a
                href="{{ route('admin.unit.index') }}"
                class="px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-label-lg text-label-lg hover:bg-surface-container-high transition-colors flex items-center gap-1.5 self-end lg:self-center"
            >
                <x-symbol nama="arrow_back" class="text-[18px]" />
                Kembali ke Daftar
            </a>
        </div>

        {{-- Ringkasan unit yang sedang diubah, supaya admin bisa membandingkan
             isi formulir dengan data yang tersimpan tanpa membuka daftar.
             Indikator koneksi SIMRS tidak dicantumkan karena heartbeat-nya
             belum pernah ditulis aplikasi ini. --}}
        @if ($unit)
            <div class="p-space-sm rounded-lg bg-surface-container-low border border-outline-variant/30 flex flex-wrap items-center gap-x-space-lg gap-y-space-sm">
                <div class="flex items-center gap-2">
                    <x-symbol nama="badge" class="text-[16px] text-outline" />
                    <span class="font-label-sm text-label-sm text-on-surface-variant">
                        {{ $unit->kategori->label() }} &middot; {{ $unit->status_akses->label() }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <x-symbol nama="assignment" class="text-[16px] text-outline" />
                    <span class="font-label-sm text-label-sm text-on-surface-variant">
                        {{ number_format($bebanAktif, 0, ',', '.') }} pengaduan aktif, {{ number_format($bebanSelesai, 0, ',', '.') }} selesai
                    </span>
                </div>
            </div>
        @endif

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

        <!-- Formulir Unit -->
        <form
            method="POST"
            action="{{ $unit ? route('admin.unit.update', ['unit' => $unit->kode]) : route('admin.unit.store') }}"
            class="flex flex-col gap-space-md"
        >
            @csrf

            <!-- Bagian 1: Identitas Unit -->
            <section class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col gap-space-md">
                <div class="flex items-center gap-space-xs">
                    <x-symbol nama="badge" class="text-primary-container text-[24px]" />
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Identitas Unit</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
                    <div class="flex flex-col gap-1.5">
                        <label for="kode" class="font-label-md text-label-md font-bold text-on-surface">
                            Kode Unit <span class="text-error">*</span>
                        </label>
                        <input
                            id="kode"
                            type="text"
                            name="kode"
                            value="{{ old('kode', $unit?->kode) }}"
                            placeholder="Contoh: IFP-01"
                            maxlength="10"
                            required
                            @disabled($unit !== null)
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md uppercase focus:outline-none focus:ring-2 focus:ring-secondary/40 {{ $unit ? 'opacity-70 cursor-not-allowed' : '' }}"
                        >
                        <span class="font-label-sm text-label-sm text-outline">
                            Kode tidak bisa diubah setelah unit dibuat. Huruf kapital, angka, dan tanda hubung saja.
                        </span>
                    </div>

                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <label for="nama" class="font-label-md text-label-md font-bold text-on-surface">
                            Nama Lengkap Unit <span class="text-error">*</span>
                        </label>
                        <input
                            id="nama"
                            type="text"
                            name="nama"
                            value="{{ old('nama', $unit?->nama) }}"
                            placeholder="Contoh: Instalasi Farmasi Pusat"
                            maxlength="120"
                            required
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                        <span class="font-label-sm text-label-sm text-outline">
                            Nama ini yang muncul di halaman lacak tiket dan di laporan bulanan.
                        </span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="kategori" class="font-label-md text-label-md font-bold text-on-surface">
                            Kategori Layanan <span class="text-error">*</span>
                        </label>
                        <select
                            id="kategori"
                            name="kategori"
                            required
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            @foreach ($kategori as $opsi)
                                <option value="{{ $opsi->value }}" @selected(old('kategori', $unit?->kategori?->value) === $opsi->value)>
                                    {{ $opsi->label() }}
                                </option>
                            @endforeach
                        </select>
                        <span class="font-label-sm text-label-sm text-outline">
                            Kategori membantu mengelompokkan unit saat telaah internal.
                        </span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="jam_layanan" class="font-label-md text-label-md font-bold text-on-surface">
                            Jam Layanan
                        </label>
                        <input
                            id="jam_layanan"
                            type="text"
                            name="jam_layanan"
                            value="{{ old('jam_layanan', $unit?->jam_layanan ?? '24 Jam') }}"
                            placeholder="Contoh: 24 Jam atau 07:00-17:00"
                            maxlength="30"
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                    </div>

                    <div class="flex items-end gap-2 pb-1">
                        <input
                            id="aktif"
                            type="checkbox"
                            name="aktif"
                            value="1"
                            @checked(old('aktif', $unit?->aktif ?? true))
                            class="mt-1 h-[18px] w-[18px] shrink-0 rounded-sm accent-brand-800"
                        >
                        <label for="aktif" class="flex flex-col">
                            <span class="font-label-md text-label-md font-bold text-on-surface">Unit Aktif</span>
                            <span class="font-label-sm text-label-sm text-outline">Unit nonaktif tidak muncul sebagai pilihan tujuan disposisi</span>
                        </label>
                    </div>
                </div>
            </section>
            <!-- End of Bagian 1: Identitas Unit -->

            <!-- Bagian 2: Kontak PIC -->
            <section class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col gap-space-md">
                <div class="flex items-center justify-between gap-space-sm flex-wrap">
                    <div class="flex items-center gap-space-xs">
                        <x-symbol nama="contact_phone" class="text-primary-container text-[24px]" />
                        <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Kontak PIC Unit</h2>
                    </div>

                    <span class="font-label-sm text-label-sm text-outline">
                        Semua kolom boleh dikosongkan. Unit yang belum punya PIC tetap bisa didaftarkan supaya tidak tercatat di luar sistem.
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-space-md">
                    <div class="flex flex-col gap-1.5">
                        <label for="pic" class="font-label-md text-label-md font-bold text-on-surface">Nama PIC</label>
                        <input
                            id="pic"
                            type="text"
                            name="pic"
                            value="{{ old('pic', $unit?->pic) }}"
                            placeholder="Contoh: dr. Danang, Sp.Rad"
                            maxlength="120"
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="jabatan_pic" class="font-label-md text-label-md font-bold text-on-surface">Jabatan</label>
                        <input
                            id="jabatan_pic"
                            type="text"
                            name="jabatan_pic"
                            value="{{ old('jabatan_pic', $unit?->jabatan_pic) }}"
                            placeholder="Contoh: Kepala Instalasi Radiologi"
                            maxlength="120"
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="nip" class="font-label-md text-label-md font-bold text-on-surface">NIP / NIP Wajib</label>
                        <input
                            id="nip"
                            type="text"
                            name="nip"
                            value="{{ old('nip', $unit?->nip) }}"
                            placeholder="Contoh: 19850412 199312 1 001"
                            maxlength="30"
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="kontak_wa" class="font-label-md text-label-md font-bold text-on-surface">Nomor WhatsApp</label>
                        <input
                            id="kontak_wa"
                            type="text"
                            name="kontak_wa"
                            value="{{ old('kontak_wa', $unit?->kontak_wa) }}"
                            placeholder="Contoh: 0811-3344-01"
                            maxlength="25"
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="ekstensi" class="font-label-md text-label-md font-bold text-on-surface">Ekstensi Internal</label>
                        <input
                            id="ekstensi"
                            type="text"
                            name="ekstensi"
                            value="{{ old('ekstensi', $unit?->ekstensi) }}"
                            placeholder="Contoh: 4210"
                            maxlength="15"
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="akun_simrs" class="font-label-md text-label-md font-bold text-on-surface">Email / Akun SIMRS</label>
                        <input
                            id="akun_simrs"
                            type="email"
                            name="akun_simrs"
                            value="{{ old('akun_simrs', $unit?->akun_simrs) }}"
                            placeholder="Contoh: danang.radiologi@rsudsoetomo.go.id"
                            maxlength="120"
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                    </div>
                </div>
            </section>
            <!-- End of Bagian 2: Kontak PIC -->

            <!-- Bagian 3: Hak Akses Dashboard Unit -->
            <section class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col gap-space-md">
                <div class="flex items-center justify-between gap-space-sm flex-wrap">
                    <div class="flex items-center gap-space-xs">
                        <x-symbol nama="shield_person" class="text-primary-container text-[24px]" />
                        <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Hak Akses Dashboard Unit</h2>
                    </div>

                    <span class="font-label-sm text-label-sm text-outline">
                        Halaman dashboard milik PIC unit belum dibangun, jadi status akses dicatat manual sambil menunggu alur undangan otomatis selesai dibangun.
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
                    <div class="flex flex-col gap-1.5">
                        <label for="peran_akses" class="font-label-md text-label-md font-bold text-on-surface">
                            Peran Akses <span class="text-error">*</span>
                        </label>
                        <select
                            id="peran_akses"
                            name="peran_akses"
                            required
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            @foreach ($peran as $nilai => $label)
                                <option value="{{ $nilai }}" @selected(old('peran_akses', $unit?->peran_akses?->value) === $nilai)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="status_akses" class="font-label-md text-label-md font-bold text-on-surface">
                            Kondisi Akun PIC <span class="text-error">*</span>
                        </label>
                        <select
                            id="status_akses"
                            name="status_akses"
                            required
                            class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            @foreach ($akses as $opsi)
                                <option value="{{ $opsi->value }}" @selected(old('status_akses', $unit?->status_akses?->value) === $opsi->value)>
                                    {{ $opsi->label() }}
                                </option>
                            @endforeach
                        </select>
                        <span class="font-label-sm text-label-sm text-outline">
                            {{ $unit?->status_akses?->catatan() ?? 'Pilih kondisi sesuai apa yang sudah tercatat di luar aplikasi ini.' }}
                        </span>
                    </div>
                </div>
            </section>
            <!-- End of Bagian 3: Hak Akses Dashboard Unit -->

            <!-- Aksi Formulir -->
            <div class="flex items-center justify-between gap-space-sm flex-wrap bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
                <span class="font-body-sm text-body-sm text-outline">
                    @if ($unit)
                        Kode unit <strong class="text-on-surface">{{ $unit->kode }}</strong> tidak bisa diubah.
                    @else
                        Kode unit maksimal 10 karakter, boleh huruf/angka dan tanda hubung, serta tidak boleh sama dengan milik unit lain.
                    @endif
                </span>

                <div class="flex items-center gap-space-sm">
                    <a
                        href="{{ route('admin.unit.index') }}"
                        class="px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-label-lg text-label-lg hover:bg-surface-container-high transition-colors"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="px-space-md py-2 rounded-lg bg-primary text-on-primary font-label-lg text-label-lg hover:bg-primary/90 transition-colors flex items-center gap-1.5 shadow-sm"
                    >
                        <x-symbol nama="save" class="text-[18px]" />
                        {{ $unit ? 'Simpan Perubahan' : 'Simpan Unit Baru' }}
                    </button>
                </div>
            </div>
            <!-- End of Aksi Formulir -->
        </form>
        <!-- End of Formulir Unit -->

    </div>

@endsection
