@extends('unit.layout')

@section('title', 'Ruang Kerja Tiket '.$detail->kode())

@section('content')

    @php
        // Shorthand supaya template tidak mengulang nama variabel panjang
        // di setiap blok yang sama.
        $tiket = $detail->pengaduan;
        $unitTiket = $detail->unit;
        $sla = $detail->sla;
        $kode = $detail->kode();
        $instruksi = $detail->instruksiDisposisi();
        $tuntas = $tiket->status->selesai();

        // Log unit sudah disaring di support: percakapan pelapor dan
        // perubahan keparahan memang bukan urusan unit.
        $kegiatanUnit = $detail->kegiatanUnit();

        // Tombol di panel kanan: dua yang pertama sudah punya halaman tujuan,
        // sisanya masih ditampilkan mati, bukan disembunyikan. Petugas unit
        // perlu tahu bahwa ekspor Excel, eskalasi, dan TTD digital masih
        // menjadi pekerjaan lanjutan supaya tidak menunggu fitur yang tidak
        // akan muncul.
        $tombolAksi = [
            ['ikon' => 'print', 'label' => 'Cetak Lembar Disposisi', 'url' => route('unit.disposisi.ekspor.disposisi-pdf', ['unit' => $unit->kode, 'kode' => $kode])],
            ['ikon' => 'picture_as_pdf', 'label' => 'Unduh PDF', 'url' => route('unit.disposisi.ekspor.disposisi-pdf', ['unit' => $unit->kode, 'kode' => $kode])],
            ['ikon' => 'file_download', 'label' => 'Unduh Excel', 'url' => null],
            ['ikon' => 'warning', 'label' => 'Eskalasi ke Pimpinan', 'url' => null],
            ['ikon' => 'stylus_note', 'label' => 'TTD Digital', 'url' => null],
            ['ikon' => 'sync', 'label' => 'Refresh SIMRS', 'url' => null],
        ];
    @endphp

    <div class="w-full px-margin py-space-md flex flex-col gap-space-lg">

        {{-- Hasil tindakan: muncul setelah unit menekan kirim jawaban. --}}
        @if (session('sukses'))
            <div
                role="status"
                class="w-full bg-secondary-container text-on-secondary-container rounded-lg px-space-md py-space-sm flex items-start gap-space-sm"
            >
                <x-symbol nama="check_circle" class="text-[20px] shrink-0" />
                <span class="text-body-sm">{{ session('sukses') }}</span>
            </div>
        @endif

        <!-- Remah Roti: kembali ke daftar disposisi unit ini dan posisi SLA singkat. -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-sm">
            <div class="flex flex-wrap items-center gap-space-sm text-label-md text-on-surface-variant">
                <a
                    href="{{ route('unit.disposisi.index', $unit) }}"
                    class="flex items-center gap-1 hover:text-secondary transition-colors"
                >
                    <x-symbol nama="arrow_back" class="text-[16px]" />
                    Daftar Disposisi Masuk
                </a>
                <span aria-hidden="true">/</span>
                <span class="font-mono font-bold text-on-surface">#{{ $kode }}</span>
            </div>

            <div class="flex items-center gap-space-xs">
                <span class="px-space-xs py-0.5 rounded-xl {{ $tiket->status->badgeAdmin() }} font-label-sm text-label-sm font-bold">
                    {{ $tiket->status->label() }}
                </span>
                <span class="px-space-xs py-0.5 rounded-xl {{ $sla['nadaZona'] }} font-label-sm text-label-sm font-bold flex items-center gap-1">
                    <x-symbol nama="schedule" class="text-[13px]" />
                    {{ $sla['zona'] }}
                </span>
            </div>
        </div>
        <!-- End of Remah Roti -->

        <x-tiket-identitas
            :kode="$kode"
            :tiket="$tiket"
            :unit="$unitTiket"
            :judul-unit="'Status Investigasi Unit'"
            :show-kategori="true"
            :show-no-wa="false"
        />

        <x-stepper-tahap
            :pilar="$detail->pilar"
            :sla="$sla"
            :ringkas-sla="$tiket->ringkasSla()"
        />

        <!-- Instruksi Disposisi: arahan humas yang terakhir tertulis pada tiket. -->
        <section class="w-full bg-secondary-container/60 rounded-lg p-space-md flex flex-col gap-space-sm">
            <div class="flex items-center gap-space-sm">
                <x-symbol nama="assignment" class="text-secondary text-[22px]" />
                <h2 class="font-title-lg text-title-lg text-on-surface font-bold">Arahan Disposisi dari Humas</h2>
            </div>

            @if ($instruksi !== null)
                <p class="text-on-surface leading-relaxed">{{ $instruksi['catatan'] }}</p>

                <div class="flex flex-wrap items-center gap-space-sm text-label-sm text-label-sm text-on-surface-variant">
                    <span>Ditulis oleh <strong class="text-on-surface">{{ $instruksi['aktor'] }}</strong></span>
                    <span aria-hidden="true">&bull;</span>
                    <span>{{ $instruksi['waktu'] }}</span>
                    <span aria-hidden="true">&bull;</span>
                    <span>Tahap: {{ $instruksi['judul'] }}</span>
                </div>
            @else
                <p class="text-on-surface leading-relaxed">
                    Humas belum menulis catatan khusus untuk tiket ini. Bekerjalah sesuai
                    pokok keluhan di bawah, lalu kirimkan jawaban lewat formulir di samping.
                </p>
            @endif
        </section>
        <!-- End of Instruksi Disposisi -->

        <!-- Kolom Kerja: satu kolom vertikal — kronologi, audit, chat, formulir. -->
        <div class="w-full flex flex-col gap-space-lg">

                <!-- Kartu 1: isi pengaduan dan berkas lampiran dari pelapor -->
                <article class="bg-surface-container-lowest rounded-lg shadow-sm p-space-md flex flex-col gap-space-md">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm pb-space-sm border-b border-outline-variant/30">
                        <div class="flex items-center gap-space-sm">
                            <x-symbol nama="description" class="text-secondary text-[22px]" />
                            <div>
                                <h2 class="font-title-lg text-title-lg text-on-surface font-bold">Kronologi Pengaduan Pelapor</h2>

                                <p class="font-body-sm text-body-sm text-on-surface-variant">
                                    Isi persis seperti dikirim pelapor, sebelum dirumuskan humas.
                                </p>
                            </div>
                        </div>

                        <span class="px-space-sm py-0.5 rounded-full bg-surface-container text-on-surface font-label-sm text-label-sm font-semibold">
                            {{ $tiket->judulKategori() }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm text-on-surface font-body-sm text-body-sm">
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Nama Pelapor</span>
                            <strong>{{ $tiket->nama_lengkap }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Unit yang pelapor sebut</span>
                            <strong>{{ $tiket->unit }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Surel</span>
                            <strong>{{ $tiket->email }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Kejadian</span>
                            <strong>{{ $tiket->waktu_kejadian->translatedFormat('d M Y') }}</strong>
                        </div>
                    </div>

                    <div class="bg-surface-container-low/50 p-space-md rounded-lg flex flex-col gap-1">
                        <span class="font-label-sm text-label-sm text-on-surface-variant uppercase font-bold">Isi Pengaduan</span>
                        <p class="text-on-surface leading-relaxed whitespace-pre-line">{{ $tiket->deskripsi }}</p>
                    </div>

                    @if (count($detail->lampiran) > 0)
                        <div class="flex flex-col gap-space-xs">
                            <span class="font-label-sm text-label-sm uppercase font-bold text-on-surface-variant">
                                Lampiran Dari Pelapor ({{ count($detail->lampiran) }} Berkas)
                            </span>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm">
                                @foreach ($detail->lampiran as $berkas)

                                    {{-- Foto pelapor ditampilkan sebagai pratinjau, berkas lain
                                         tetap berupa baris nama supaya daftar tidak terlalu tinggi. --}}
                                    @if ($berkas['gambar'])
                                        <div class="flex flex-col gap-space-xs p-space-sm rounded-lg bg-surface-container">
                                            <a href="{{ $berkas['url'] }}" target="_blank" rel="noopener">
                                                <img
                                                    src="{{ $berkas['url'] }}"
                                                    alt="Lampiran {{ $berkas['nama'] }}"
                                                    class="w-full h-32 object-cover rounded-sm"
                                                >
                                            </a>

                                            <span class="font-label-md text-label-md font-bold text-on-surface truncate">{{ $berkas['nama'] }}</span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-space-sm p-space-sm rounded-lg bg-surface-container">
                                            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-surface-container-highest text-error">
                                                <x-symbol nama="picture_as_pdf" class="text-[24px]" />
                                            </span>

                                            <div class="flex flex-col min-w-0">
                                                <span class="font-label-md text-label-md font-bold text-on-surface truncate">{{ $berkas['nama'] }}</span>
                                                <span class="font-label-sm text-label-sm text-on-surface-variant">Diunggah bersama pengaduan</span>
                                            </div>

                                            <a
                                                href="{{ $berkas['url'] }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="ml-auto shrink-0 px-2 py-1 rounded-sm bg-surface-container-highest text-on-surface font-label-sm text-label-sm font-semibold flex items-center gap-1 hover:bg-surface-container-high transition-colors"
                                            >
                                                <x-symbol nama="visibility" class="text-[16px]" />
                                                Buka
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-body-sm text-on-surface-variant">Pelapor tidak melampirkan berkas pada pengaduan ini.</p>
                    @endif
                </article>
                <!-- End of Kartu 1 -->

                <!-- Kartu Percakapan: jawaban unit, catatan humas, dan log
                     kegiatan duduk dalam satu kartu bertab supaya halaman
                     tidak memanjang saat riwayat tiketnya panjang. -->
                <article class="bg-surface-container-lowest rounded-lg shadow-sm flex flex-col overflow-hidden">

                    <!-- Tab Kanal: koordinasi humas dan log kegiatan tiket. -->
                    <div class="flex items-center bg-surface-container-low p-space-xs gap-space-sm">
                        <button
                            type="button"
                            data-tab="koordinasi"
                            class="flex-1 py-3 px-space-md rounded-lg font-title-sm text-title-sm font-bold flex items-center justify-center gap-1.5 bg-surface-container-lowest text-on-surface shadow-sm"
                        >
                            <x-symbol nama="forum" class="text-[18px] text-secondary" />
                            Koordinasi dengan Humas

                            @if (count($detail->koordinasi) > 0)
                                <span class="w-2 h-2 rounded-full bg-secondary" aria-hidden="true"></span>
                            @endif
                        </button>

                        <button
                            type="button"
                            data-tab="log"
                            class="flex-1 py-3 px-space-md rounded-lg font-title-sm text-title-sm font-semibold text-on-surface-variant hover:text-on-surface flex items-center justify-center gap-1.5"
                        >
                            <x-symbol nama="history" class="text-[18px]" />
                            Log Kegiatan
                        </button>
                    </div>
                    <!-- End of Tab Kanal -->

                    <!-- Tab Koordinasi: jawaban unit dan catatan humas di satu kanal. -->
                    <div class="p-space-md flex flex-col gap-space-md" data-panel="koordinasi">
                        <div class="flex items-center justify-between gap-space-sm pb-space-xs text-on-surface-variant font-label-sm text-label-sm bg-surface-container-low p-space-sm rounded-lg">
                            <span class="flex items-center gap-1 font-semibold text-on-surface">
                                <x-symbol nama="forum" class="text-[16px] text-secondary" />
                                Koordinasi dengan Humas
                            </span>
                            <span class="text-secondary font-bold">{{ count($detail->koordinasi) }} Pesan</span>
                        </div>

                        <div class="flex flex-col gap-space-sm max-h-[360px] overflow-y-auto pr-1">
                            @forelse ($detail->koordinasi as $pesan)

                                {{-- Jawaban unit digambar di sisi kanan, catatan humas di
                                     kiri, supaya arah percakapan terbaca tanpa label berulang. --}}
                                <div @class([
                                    'flex flex-col max-w-[85%]',
                                    'items-end self-end' => $pesan['dariUnit'],
                                    'items-start self-start' => ! $pesan['dariUnit'],
                                ])>
                                    <span @class([
                                        'font-label-sm text-label-sm text-on-surface-variant mb-0.5',
                                        'mr-1' => $pesan['dariUnit'],
                                        'ml-1' => ! $pesan['dariUnit'],
                                    ])>
                                        {{ $pesan['dariUnit'] ? 'PIC Unit' : 'Admin Humas' }} &bull; {{ $pesan['waktu'] }}
                                    </span>

                                    <div @class([
                                        'p-space-sm rounded-lg font-body-sm text-body-sm',
                                        'rounded-tr-xs bg-secondary text-on-secondary' => $pesan['dariUnit'],
                                        'rounded-tl-xs bg-tertiary-container text-on-tertiary-container' => ! $pesan['dariUnit'],
                                    ])>
                                        {{ $pesan['isi'] }}

                                        @if (count($pesan['lampiran']) > 0)
                                            <div class="mt-3 flex flex-col gap-2">

                                                @foreach ($pesan['lampiran'] as $berkas)

                                                    @if ($berkas['gambar'])
                                                        <a href="{{ $berkas['url'] }}" target="_blank" rel="noopener">
                                                            <img src="{{ $berkas['url'] }}" alt="Lampiran {{ $berkas['nama'] }}" class="w-full max-w-[240px] rounded-lg">
                                                        </a>
                                                    @else
                                                        <a href="{{ $berkas['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-white/70 px-3 py-2 text-[11px] font-semibold text-on-surface">
                                                            <x-symbol nama="attach_file" class="text-[14px]" />
                                                            {{ $berkas['nama'] }}
                                                        </a>
                                                    @endif

                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-body-sm text-on-surface-variant">
                                    Belum ada percakapan antara humas dan unit pada tiket ini.
                                </p>
                            @endforelse
                        </div>

                        @if ($tuntas)
                            <div
                                role="status"
                                class="w-full bg-surface-container text-on-surface-variant rounded-lg px-space-sm py-space-sm text-body-sm leading-relaxed"
                            >
                                Tiket ini sudah berstatus <strong>{{ $tiket->status->label() }}</strong>,
                                sehingga formulir jawaban unit ditutup. Bila ada sanggahan baru,
                                hubungi humas agar tiket dibuka kembali.
                            </div>
                        @else
                            <form
                                method="POST"
                                action="{{ route('unit.disposisi.kirim', ['unit' => $unit, 'kode' => $kode]) }}"
                                enctype="multipart/form-data"
                                class="flex flex-col gap-space-sm"
                            >
                                @csrf

                                <div class="flex flex-col gap-space-xs">
                                    <label for="isiPesan" class="font-label-sm text-label-sm uppercase font-bold text-on-surface-variant">
                                        Tulis Pesan untuk Humas
                                    </label>

                                    <textarea
                                        id="isiPesan"
                                        name="isi"
                                        rows="3"
                                        maxlength="2000"
                                        placeholder="Tuliskan hasil telaah unit, tindakan yang sudah diambil, atau pertanyaan ke humas..."
                                        class="w-full p-space-md rounded-lg bg-surface font-body-md text-body-md text-on-surface outline-none focus:bg-surface-container-lowest transition-all placeholder:text-outline border border-outline-variant/40"
                                    >{{ old('isi') }}</textarea>

                                    @error('isi')
                                        <span class="font-label-sm text-label-sm text-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-space-md">
                                    {{-- Label "Lampiran" diganti nama berkas lewat elemen di sebelahnya,
                                         persis seperti form balasan admin konsol humas. --}}
                                    <label class="flex items-center gap-2 px-space-md py-2.5 rounded-lg bg-surface-container text-on-surface font-label-md text-label-md font-semibold cursor-pointer">
                                        <x-symbol nama="attach_file" class="text-[16px]" />
                                        <span>Lampiran</span>
                                        <input
                                            type="file"
                                            name="lampiran[]"
                                            accept=".jpg,.jpeg,.png,.pdf"
                                            multiple
                                            class="sr-only"
                                            onchange="this.previousElementSibling.textContent = this.files.length ? this.files[0].name + (this.files.length > 1 ? ' + ' + (this.files.length - 1) + ' berkas lain' : '') : 'Lampiran'"
                                        >
                                    </label>

                                    <span class="font-label-sm text-label-sm text-on-surface-variant">
                                        Maksimal 5 berkas JPG, PNG, atau PDF, masing-masing 4 MB.
                                    </span>
                                </div>

                                @error('lampiran')
                                    <span class="font-label-sm text-label-sm text-error">{{ $message }}</span>
                                @enderror

                                @error('lampiran.*')
                                    <span class="font-label-sm text-label-sm text-error">{{ $message }}</span>
                                @enderror

                                <div class="flex flex-wrap items-center justify-between gap-space-md">
                                    <span class="font-label-sm text-label-sm text-on-surface-variant">
                                        Setiap pesan unit menandai tiket menunggu racikan humas.
                                    </span>

                                    <button
                                        type="submit"
                                        class="py-3 px-space-xl rounded-lg bg-primary text-on-primary font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 transition-opacity hover:opacity-90"
                                    >
                                        <x-symbol nama="send" class="text-[18px]" />
                                        Kirim Pesan
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                    <!-- End of Tab Koordinasi -->

                    <!-- Tab Log Kegiatan: versi tersaring untuk unit, tanpa
                         percakapan pelapor dan tanpa perubahan keparahan. -->
                    <div class="hidden p-space-md flex flex-col gap-space-md" data-panel="log">
                        <div class="flex items-center justify-between gap-space-sm pb-space-xs text-on-surface-variant font-label-sm text-label-sm bg-surface-container-low p-space-sm rounded-lg">
                            <span class="flex items-center gap-1 font-semibold text-on-surface">
                                <x-symbol nama="history" class="text-[16px] text-secondary" />
                                Log Kegiatan Tiket
                            </span>
                            <span class="text-secondary font-bold">{{ count($kegiatanUnit) }} Kejadian</span>
                        </div>

                        <div class="max-h-[480px] overflow-y-auto pr-1">
                            <x-log-kegiatan :kegiatan="$kegiatanUnit" />
                        </div>
                    </div>
                    <!-- End of Tab Log Kegiatan -->
                </article>
                <!-- End of Kartu Percakapan -->

                <!-- Panel Tindakan Mati: pekerjaan yang belum dibangun. -->
                <article class="bg-surface-container-lowest rounded-lg shadow-sm p-space-md flex flex-col gap-space-sm">
                    <div class="flex items-center gap-space-sm pb-space-sm border-b border-outline-variant/30">
                        <x-symbol nama="construction" class="text-secondary text-[22px]" />
                        <div>
                            <h2 class="font-title-lg text-title-lg text-on-surface font-bold">Tindakan Pendukung</h2>

                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                Cetak & PDF sudah tersedia. Ekspor Excel, Eskalasi, TTD Digital, dan Refresh SIMRS masih dalam pembangunan.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-space-sm">
                        @foreach ($tombolAksi as $tombol)
                            @php $method = $tombol['method'] ?? 'GET'; @endphp
                            @if ($tombol['url'])
                                @if ($method === 'POST')
                                    <form method="POST" action="{{ $tombol['url'] }}" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1.5 px-space-sm py-2 rounded-lg bg-secondary text-on-secondary font-label-md text-label-md font-semibold hover:bg-secondary/90 transition-colors"
                                        >
                                            <x-symbol :nama="$tombol['ikon']" class="text-[16px]" />
                                            {{ $tombol['label'] }}
                                        </button>
                                    </form>
                                @else
                                    <a
                                        href="{{ $tombol['url'] }}"
                                        class="inline-flex items-center gap-1.5 px-space-sm py-2 rounded-lg bg-secondary text-on-secondary font-label-md text-label-md font-semibold hover:bg-secondary/90 transition-colors"
                                    >
                                        <x-symbol :nama="$tombol['ikon']" class="text-[16px]" />
                                        {{ $tombol['label'] }}
                                    </a>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1.5 px-space-sm py-2 rounded-lg bg-surface-container text-outline font-label-md text-label-md font-semibold cursor-not-allowed">
                                    <x-symbol :nama="$tombol['ikon']" class="text-[16px]" />
                                    {{ $tombol['label'] }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </article>
                <!-- End of Panel Tindakan Mati -->
        </div>
        
        <!-- End of Kolom Kerja -->
    </div>
@endsection

<x-script-tab-kartu />
