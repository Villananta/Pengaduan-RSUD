@extends('admin.layout')

@section('title', 'Detail Tiket '.$detail->kode())

@use('App\Enums\KategoriPengaduan')
@use('App\Enums\TingkatKeparahan')

@section('content')

    @php
        // Shorthand supaya template tidak mengulang nama variabel panjang
        // di setiap blok yang sama.
        $tiket = $detail->pengaduan;
        $unit = $detail->unit;
        $sla = $detail->sla;
        $kode = $detail->kode();
    @endphp

    <div class="w-full px-margin py-space-md flex flex-col gap-space-lg">

        {{-- Hasil tindakan: muncul setelah admin memindahkan tahap
             pengaduan atau mengubah penandaan kasus berat. --}}
        @if (session('sukses'))
            <div
                role="status"
                class="w-full bg-secondary-container text-on-secondary-container rounded-lg px-space-md py-space-sm flex items-start gap-space-sm"
            >
                <x-symbol nama="check_circle" class="text-[20px] shrink-0" />
                <span class="text-body-sm">{{ session('sukses') }}</span>
            </div>
        @endif

        <!-- Remah Roti: kembali ke daftar, kode tiket, dan posisi SLA singkat. -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-sm">
            <div class="flex flex-wrap items-center gap-space-sm text-label-md text-label-md text-on-surface-variant">
                <a
                    href="{{ route('admin.pengaduan.index') }}"
                    class="flex items-center gap-1 hover:text-secondary transition-colors"
                >
                    <x-symbol nama="arrow_back" class="text-[16px]" />
                    Daftar Pengaduan
                </a>
                <span aria-hidden="true">/</span>
                <span class="font-mono font-bold text-on-surface">#{{ $detail->kode() }}</span>
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
            :kode="$detail->kode()"
            :tiket="$tiket"
            :unit="$unit"
        />

        <x-stepper-tahap
            :pilar="$detail->pilar"
            :sla="$sla"
            :ringkas-sla="$tiket->ringkasSla()"
        />

        <!-- Panel Kategori: ruang lingkup tiket ditentukan humas. -->
        {{-- Tiket dari portal publik masuk tanpa kategori, jadi panel ini
             satu-satunya tempat menetapkannya. Kategori tidak mengubah SLA;
             nilainya hanya memilih alur penanganan dan filter di daftar. --}}

        <div class="w-full rounded-lg shadow-sm bg-surface-container-lowest px-space-md py-space-sm flex flex-col md:flex-row md:items-center justify-between gap-space-md">
            <div class="flex items-start gap-space-sm">
                <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 bg-surface-container-highest text-on-surface-variant">
                    <x-symbol nama="category" class="text-[20px]" />
                </span>

                <div>
                    <h2 class="font-title-md text-title-md text-on-surface font-bold">
                        Kategori Pengaduan
                    </h2>

                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        @if ($tiket->kategori)
                            Tiket ini masuk kategori <strong>{{ $tiket->kategori->label() }}</strong>.
                        @else
                            Belum ditentukan. Pilih kategori supaya tiket masuk alur penanganan yang tepat.
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                @foreach (KategoriPengaduan::cases() as $pilihan)
                    <form method="POST" action="{{ route('admin.pengaduan.kategori', $kode) }}">
                        @csrf

                        <input type="hidden" name="kategori" value="{{ $pilihan->value }}">

                        <button
                            type="submit"
                            aria-pressed="{{ $tiket->kategori === $pilihan ? 'true' : 'false' }}"
                            @class([
                                'px-space-md py-2 rounded-full font-label-md text-label-md font-bold transition-colors',
                                'bg-brand-100 text-brand-800 hover:opacity-90' => $tiket->kategori === $pilihan,
                                'bg-surface-container text-on-surface-variant hover:bg-surface-container-highest' => $tiket->kategori !== $pilihan,
                            ])
                        >
                            {{ $pilihan->label() }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
        <!-- End of Panel Kategori -->

        <!-- Panel Tingkat Keparahan: penanda warna yang ditetapkan humas. -->
        {{-- Keparahan hanya penanda warna dan filter di konsol. Target SLA
             tetap dihitung dari App\Support\Sla supaya keputusan admin tidak
             menggeser tenggat yang sudah dijanjikan ke pelapor. --}}

        <div class="w-full rounded-lg shadow-sm bg-surface-container-lowest px-space-md py-space-sm flex flex-col md:flex-row md:items-center justify-between gap-space-md">
            <div class="flex items-start gap-space-sm">
                <span @class([
                    'w-9 h-9 rounded-lg flex items-center justify-center shrink-0',
                    $sla['keparahan']?->badge() ?? 'bg-surface-container-highest text-on-surface-variant',
                ])>
                    <x-symbol :nama="$sla['keparahan']?->ikon() ?? 'flag'" class="text-[20px]" />
                </span>

                <div>
                    <h2 class="font-title-md text-title-md text-on-surface font-bold">
                        Tingkat Keparahan
                    </h2>

                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        @if ($sla['keparahan'])
                            Tiket ini ditandai <strong>{{ $sla['keparahan']->label() }}</strong>.
                        @else
                            Belum ditetapkan. Pilih satu warna supaya tiket mudah dikenali di daftar.
                        @endif

                        Penanda ini tidak mengubah target SLA.
                    </p>
                </div>
            </div>

            {{-- Tiga tombol mengirim nilai yang berbeda ke rute yang sama.
                 Tombol yang sedang aktif tetap bisa ditekan, tetapi model
                 mengabaikannya karena nilainya tidak berubah. --}}

            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                @foreach (TingkatKeparahan::cases() as $tingkat)
                    <form method="POST" action="{{ route('admin.pengaduan.keparahan', $kode) }}">
                        @csrf

                        <input type="hidden" name="tingkat" value="{{ $tingkat->value }}">

                        <button
                            type="submit"
                            aria-pressed="{{ $sla['keparahan'] === $tingkat ? 'true' : 'false' }}"
                            @class([
                                'px-space-md py-2 rounded-full font-label-md text-label-md font-bold flex items-center gap-1.5 transition-colors',
                                $tingkat->badge().' hover:opacity-90' => $sla['keparahan'] === $tingkat,
                                'bg-surface-container text-on-surface-variant hover:bg-surface-container-highest' => $sla['keparahan'] !== $tingkat,
                            ])
                        >
                            <span class="w-2 h-2 rounded-full {{ $tingkat->titik() }}" aria-hidden="true"></span>
                            {{ $tingkat->label() }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
        <!-- End of Panel Tingkat Keparahan -->

        <!-- Kolom Kerja: kiri kronologi dan formulasi, kanan keputusan,
             bawahnya baris penuh buat panel percakapan -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">

            <!-- Kolom Kiri: Kronologi Pengaduan -->
            <div class="lg:col-span-7 flex flex-col gap-space-lg">

                <!-- Kartu 1: isi pengaduan dan berkas lampiran dari pelapor -->
                <article class="bg-surface-container-lowest rounded-lg shadow-sm p-space-md flex flex-col gap-space-md">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm pb-space-sm border-b border-outline-variant/30">
                        <div class="flex items-center gap-space-sm">
                            <x-symbol nama="description" class="text-secondary text-[22px]" />
                            <div>
                                <h2 class="font-title-lg text-title-lg text-on-surface font-bold">Kronologi Pengaduan Pelapor</h2>

                                <p class="font-body-sm text-body-sm text-on-surface-variant">
                                    Isi persis seperti dikirim pelapor melalui portal publik, belum melalui telaah humas.
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
                            <span class="text-on-surface-variant text-[11px] block">Alamat</span>
                            <strong>{{ $tiket->alamat }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Surel</span>
                            <strong>{{ $tiket->email }}</strong>
                        </div>
                        <div>
                            <span class="text-on-surface-variant text-[11px] block">Unit yang pelapor sebut</span>
                            <strong>{{ $tiket->unit }}</strong>
                        </div>
                    </div>

                    <div class="bg-surface-container-low/50 p-space-md rounded-lg flex flex-col gap-1">
                        <span class="font-label-sm text-label-sm text-on-surface-variant uppercase font-bold">Isi Pengaduan</span>
                        <p class="text-on-surface leading-relaxed whitespace-pre-line">{{ $tiket->deskripsi }}</p>
                    </div>

                    {{-- Berkas lampiran. Foto langsung dipratinjau, berkas lain hanya nama. --}}

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

                                            <div class="flex items-center justify-between gap-space-sm">
                                                <span class="font-label-md text-label-md font-bold text-on-surface truncate">{{ $berkas['nama'] }}</span>
                                                <a
                                                    href="{{ $berkas['url'] }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="shrink-0 px-2 py-1 rounded-sm bg-surface-container-highest text-on-surface font-label-sm text-label-sm font-semibold flex items-center gap-1 hover:bg-surface-container-high transition-colors"
                                                >
                                                    <x-symbol nama="visibility" class="text-[16px]" />
                                                    Buka
                                                </a>
                                            </div>
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
                                                <x-symbol nama="download" class="text-[16px]" />
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
            </div>
            <!-- End of Kolom Kiri -->

            <!-- Kolom Kanan: Keputusan Penanganan Pengaduan -->
            <div class="lg:col-span-5 flex flex-col gap-space-lg">

                <!-- Kartu 3: Jalur penanganan dan status disposisi unit -->
                <article class="bg-surface-container-lowest rounded-lg shadow-sm p-space-md flex flex-col gap-space-md">
                    <div class="flex items-center justify-between gap-space-sm pb-space-xs border-b border-outline-variant/20">
                        <div class="flex items-center gap-2">
                            <span class="w-9 h-9 rounded-lg bg-primary-container text-on-primary flex items-center justify-center shrink-0">
                                <x-symbol nama="alt_route" class="text-[20px]" />
                            </span>
                            <div>
                                <h2 class="font-title-md text-title-md text-on-surface font-bold">Keputusan Penanganan Pengaduan</h2>

                                <p class="font-body-sm text-body-sm text-on-surface-variant">Pilih jalur investigasi Lapis 2 atau penanganan langsung</p>
                            </div>
                        </div>

                        <span class="px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-bold">
                            {{ $unit['tertaut'] ? 'Terdisposisi' : 'Tanpa Unit' }}
                        </span>
                    </div>

                    {{-- Ringkasan unit yang sedang ditugaskan. --}}
                    <div class="p-space-sm rounded-lg bg-surface-container-low border border-outline-variant/30 flex flex-col gap-space-sm">
                        <div class="flex items-center justify-between gap-space-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ $unit['tertaut'] ? 'bg-secondary' : 'bg-outline' }}" aria-hidden="true"></span>
                                <span class="font-label-sm text-label-sm font-bold text-on-surface uppercase tracking-wider">
                                    {{ $unit['tertaut'] ? 'Unit Ditugaskan' : 'Belum Ditugaskan' }}
                                </span>
                            </div>

                            @if ($unit['disposisi'])
                                <span class="px-2 py-0.5 rounded-full {{ $unit['disposisi']->badge() }} font-label-sm text-label-sm font-bold">
                                    {{ $unit['disposisi']->label() }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 gap-space-sm text-on-surface font-body-sm text-body-sm pt-1 border-t border-outline-variant/20">
                            <div>
                                <span class="text-on-surface-variant text-[11px] block">Unit Tujuan</span>
                                <strong>{{ $unit['nama'] }}</strong>
                            </div>
                        </div>
                    </div>

                    {{-- Jalur 1: disposisi ke unit teknis. Tidak ditampilkan saat
                         tiket sudah selesai karena penugasan unit tak lagi relevan. --}}
                    @if (! $tiket->status->selesai())
                        <form
                            method="POST"
                            action="{{ route('admin.pengaduan.disposisi', $kode) }}"
                            class="flex flex-col gap-space-sm pt-space-sm border-t border-outline-variant/30"
                        >
                            @csrf

                            <span class="font-title-sm text-title-sm text-on-surface font-bold flex items-center gap-1.5">
                                <x-symbol nama="forward_to_inbox" class="text-[18px] text-secondary" />
                                Jalur 1: Disposisi ke Unit
                            </span>

                            <label for="pilihanUnit" class="font-label-sm text-label-sm font-bold text-on-surface flex items-center justify-between gap-space-sm">
                                <span>Pilih Instalasi / Unit Tujuan (Master Data)</span>
                                <span class="text-secondary text-[11px] font-semibold">Hanya Unit Aktif</span>
                            </label>

                            <select
                                id="pilihanUnit"
                                name="unit"
                                required
                                class="w-full py-2.5 px-space-md rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface border border-outline-variant outline-none transition-colors focus:border-secondary focus:ring-1 focus:ring-secondary/40"
                            >
                                <option value="" disabled @selected(! $unit['tertaut'])>
                                    Pilih unit tujuan disposisi
                                </option>

                                @foreach ($detail->pilihanUnit as $pilihan)
                                    <option value="{{ $pilihan->kode }}" @selected($tiket->master_unit_id === $pilihan->id)>
                                        {{ $pilihan->namaLengkap() }}
                                    </option>
                                @endforeach
                            </select>

                            @error('unit')
                                <span class="font-label-sm text-label-sm text-error">{{ $message }}</span>
                            @enderror

                            <button
                                type="submit"
                                class="py-3 px-space-md rounded-lg bg-primary text-on-primary font-title-sm text-title-sm font-bold flex items-center justify-center gap-1.5 transition-opacity hover:opacity-90"
                            >
                                <x-symbol nama="send" class="text-[18px]" />
                                Disposisi ke Unit
                            </button>
                        </form>
                    @endif

                    {{-- Jalur 2: humas menangani tiket sendiri. Tiketnya dilepas
                         dari unit supaya tidak muncul lagi di konsol unit. --}}
                    <div class="flex flex-col gap-space-sm pt-space-sm border-t border-outline-variant/30">
                        <span class="font-title-sm text-title-sm text-on-surface font-bold flex items-center gap-1.5">
                            <x-symbol nama="support_agent" class="text-[18px] text-secondary" />
                            Jalur 2: Tangani Langsung
                        </span>

                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            Klarifikasi dikerjakan Customer Care humas tanpa melibatkan unit teknis.
                        </p>

                        @if ($detail->bisaTanganiLangsung())
                            <form method="POST" action="{{ route('admin.pengaduan.tangani-langsung', $kode) }}">
                                @csrf

                                <button
                                    type="submit"
                                    class="w-full py-3 px-space-md rounded-lg bg-surface-container-high text-on-surface font-title-sm text-title-sm font-bold flex items-center justify-center gap-1.5 transition-colors hover:bg-surface-container"
                                >
                                    <x-symbol nama="play_arrow" class="text-[18px]" />
                                    Tangani Langsung
                                </button>
                            </form>

                            @error('catatan')
                                <span class="font-label-sm text-label-sm text-error">{{ $message }}</span>
                            @enderror
                        @else
                            <span class="font-label-sm text-label-sm text-on-surface-variant">
                                {{ $tiket->status->selesai()
                                    ? 'Tiket sudah selesai, penanganan langsung tidak diperlukan lagi.'
                                    : 'Jalur ini hanya untuk tiket yang masih di tahap Diterima.' }}
                            </span>
                        @endif
                    </div>
                </article>
                <!-- End of Kartu 3 -->
            </div>
            <!-- End of Kolom Kanan -->

            <!-- Kartu 4: percakapan pelapor, kanal koordinasi unit, dan log
                 kegiatan tiket dibuat selebar body supaya jadi tempat utama
                 mengetik balasan -->
            <article class="lg:col-span-12 bg-surface-container-lowest rounded-lg shadow-sm flex flex-col overflow-hidden">

                <!-- Tab Kanal: penanda kanal aktif dan judul tiga kanal di bawahnya. -->
                <div class="flex items-center bg-surface-container-low p-space-xs gap-space-sm">
                    <button
                        type="button"
                        data-tab="pelapor"
                        class="flex-1 py-3 px-space-md rounded-lg font-title-sm text-title-sm font-bold flex items-center justify-center gap-1.5 bg-surface-container-lowest text-on-surface shadow-sm"
                    >
                        <x-symbol nama="chat" class="text-[18px] text-secondary" />
                        Percakapan Pelapor

                        @if (count($detail->percakapan) > 0)
                            <span class="w-2 h-2 rounded-full bg-secondary" aria-hidden="true"></span>
                        @endif
                    </button>

                    <button
                        type="button"
                        data-tab="unit"
                        class="flex-1 py-3 px-space-md rounded-lg font-title-sm text-title-sm font-semibold text-on-surface-variant hover:text-on-surface flex items-center justify-center gap-1.5"
                    >
                        <x-symbol nama="sync_alt" class="text-[18px]" />
                        Koordinasi Unit
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

                <!-- Tab 1: percakapan pelapor dengan admin humas -->
                <div class="p-space-md flex flex-col gap-space-md" data-panel="pelapor">

                    <div class="flex items-center justify-between gap-space-sm pb-space-xs text-on-surface-variant font-label-sm text-label-sm bg-surface-container-low p-space-sm rounded-lg">
                        <span class="flex items-center gap-1">
                            <x-symbol nama="smartphone" class="text-[16px] text-secondary" />
                            Kanal Balasan Pelapor
                        </span>
                        <span class="text-secondary font-bold">{{ count($detail->percakapan) }} Pesan</span>
                    </div>

                    <div class="flex flex-col gap-space-sm max-h-[360px] overflow-y-auto pr-1">
                        @forelse ($detail->percakapan as $pesan)
                            <div @class([
                                'flex flex-col max-w-[85%]',
                                'items-start self-start' => ! $pesan['dariHumas'],
                                'items-end self-end' => $pesan['dariHumas'],
                            ])>
                                <span @class([
                                    'font-label-sm text-label-sm text-on-surface-variant mb-0.5',
                                    'ml-1' => ! $pesan['dariHumas'],
                                    'mr-1' => $pesan['dariHumas'],
                                ])>
                                    {{ $pesan['dariUnit'] ? 'PIC Unit' : ($pesan['dariHumas'] ? 'Admin Humas' : $tiket->nama_lengkap) }} &bull; {{ $pesan['waktu'] }}
                                </span>

                                <div @class([
                                    'p-space-sm rounded-lg font-body-sm text-body-sm',
                                    'rounded-tl-xs bg-secondary text-on-secondary' => $pesan['dariUnit'],
                                    'rounded-tl-xs bg-surface-container text-on-surface' => ! $pesan['dariHumas'] && ! $pesan['dariUnit'],
                                    'rounded-tr-xs bg-primary text-on-primary' => $pesan['dariHumas'] && ! $pesan['dariUnit'],
                                ])>
                                    {{ $pesan['isi'] }}

                                    @if (count($pesan['lampiran']) > 0)
                                        <div class="mt-3 flex flex-col gap-2">

                                            @foreach ($pesan['lampiran'] as $berkas)

                                                {{-- Lampiran dibungkus gelembung yang sama seperti di halaman
                                                     pelapor supaya tetap ringkas. Foto dibungkus tautan supaya
                                                     bisa dibuka ukuran penuh di tab baru. --}}
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
                                Belum ada pesan tambahan dari pelapor maupun balasan admin humas.
                            </p>
                        @endforelse
                    </div>

                    {{-- Form Balasan Admin: kolom chat yang sama dengan halaman
                         lacak pelapor, jadi keduanya tinggal dibaca sebagai
                         percakapan satu arah dua. --}}
                    <form
                        method="POST"
                        action="{{ route('admin.pengaduan.balas', $kode) }}"
                        enctype="multipart/form-data"
                        class="flex flex-col gap-space-sm bg-surface-container-low p-space-md rounded-lg"
                    >
                        @csrf

                        <div class="flex flex-col gap-space-xs">
                            <label for="isiBalasan" class="font-label-sm text-label-sm uppercase font-bold text-on-surface-variant">
                                Tulis Balasan untuk Pelapor
                            </label>

                            <textarea
                                id="isiBalasan"
                                name="isi"
                                rows="3"
                                placeholder="Tuliskan tanggapan atau informasi yang perlu disampaikan ke pelapor..."
                                class="w-full p-space-md rounded-lg bg-surface font-body-md text-body-md text-on-surface outline-none focus:bg-surface-container-lowest transition-all placeholder:text-outline border border-outline-variant/40"
                            >{{ old('isi') }}</textarea>

                            @error('isi')
                                <span class="font-label-sm text-label-sm text-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-space-md">
                            {{-- previousElementSibling menunjuk teks "Lampiran",
                                 bukan ikon x-symbol di sebelahnya, supaya ikon
                                 paperclip tidak hilang saat berkas dipilih. --}}
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
                                Balasan ini muncul di halaman lacak tiket milik pelapor.
                            </span>

                            <button
                                type="submit"
                                class="py-3 px-space-xl rounded-lg bg-primary text-on-primary font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 transition-opacity hover:opacity-90"
                            >
                                <x-symbol nama="send" class="text-[18px]" />
                                Kirim Balasan
                            </button>
                        </div>
                    </form>
                    <!-- End of Form Balasan Admin -->
                </div>
                <!-- End of Tab 1 -->

                <!-- Tab 2: kanal koordinasi dua arah humas dan unit -->
                <div class="hidden p-space-md flex flex-col gap-space-md" data-panel="unit">
                    <div class="flex items-center justify-between gap-space-sm pb-space-xs text-on-surface-variant font-label-sm text-label-sm bg-surface-container-low p-space-sm rounded-lg">
                        <span class="flex items-center gap-1 font-semibold text-on-surface">
                            <x-symbol nama="forum" class="text-[16px] text-secondary" />
                            Kanal Disposisi SIMRS: Humas &harr; Unit
                        </span>
                        <span class="text-secondary font-bold">{{ count($detail->koordinasi) }} Pesan</span>
                    </div>

                    <div class="flex flex-col gap-space-sm max-h-[360px] overflow-y-auto pr-1">
                        @forelse ($detail->koordinasi as $pesan)
                            <div @class([
                                'flex flex-col max-w-[85%]',
                                'items-start self-start' => $pesan['dariUnit'],
                                'items-end self-end' => ! $pesan['dariUnit'],
                            ])>
                                <span @class([
                                    'font-label-sm text-label-sm text-on-surface-variant mb-0.5',
                                    'ml-1' => $pesan['dariUnit'],
                                    'mr-1' => ! $pesan['dariUnit'],
                                ])>
                                    {{ $pesan['dariUnit'] ? 'PIC Unit' : 'Admin Humas' }} &bull; {{ $pesan['waktu'] }}
                                </span>

                                <div @class([
                                    'p-space-sm rounded-lg font-body-sm text-body-sm',
                                    'rounded-tl-xs bg-secondary text-on-secondary' => $pesan['dariUnit'],
                                    'rounded-tr-xs bg-primary text-on-primary' => ! $pesan['dariUnit'],
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
                                Belum ada koordinasi antara humas dan unit pada tiket ini.
                            </p>
                        @endforelse
                    </div>

                    @if ($unit['tertaut'])
                        {{-- Form Koordinasi Humas: pesan ke PIC unit, tidak bocor ke pelapor. --}}
                        <form
                            method="POST"
                            action="{{ route('admin.pengaduan.koordinasi', $kode) }}"
                            enctype="multipart/form-data"
                            class="flex flex-col gap-space-sm bg-surface-container-low p-space-md rounded-lg"
                        >
                            @csrf

                            <div class="flex flex-col gap-space-xs">
                                <label for="isiKoordinasi" class="font-label-sm text-label-sm uppercase font-bold text-on-surface-variant">
                                    Tulis Pesan untuk Unit
                                </label>

                                <textarea
                                    id="isiKoordinasi"
                                    name="isi"
                                    rows="3"
                                    maxlength="2000"
                                    placeholder="Tuliskan petunjuk, pertanyaan klarifikasi, atau catatan kerja untuk PIC unit..."
                                    class="w-full p-space-md rounded-lg bg-surface font-body-md text-body-md text-on-surface outline-none focus:bg-surface-container-lowest transition-all placeholder:text-outline border border-outline-variant/40"
                                >{{ old('isi') }}</textarea>

                                @error('isi')
                                    <span class="font-label-sm text-label-sm text-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-space-md">
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
                                    Pesan ini hanya terlihat PIC unit, tidak masuk ke percakapan pelapor.
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
                        <!-- End of Form Koordinasi Humas -->
                    @else
                        <p class="text-body-sm text-on-surface-variant leading-relaxed">
                            Tiket ini belum ditugaskan ke unit, jadi kanal koordinasi unit masih kosong.
                        </p>
                    @endif
                </div>
                <!-- End of Tab 2 -->

                <!-- Tab 3: log kronologis seluruh kegiatan tiket -->
                <div class="hidden p-space-md flex flex-col gap-space-md" data-panel="log">
                    <div class="flex items-center justify-between gap-space-sm pb-space-xs text-on-surface-variant font-label-sm text-label-sm bg-surface-container-low p-space-sm rounded-lg">
                        <span class="flex items-center gap-1 font-semibold text-on-surface">
                            <x-symbol nama="history" class="text-[16px] text-secondary" />
                            Log Kegiatan Tiket
                        </span>
                        <span class="text-secondary font-bold">{{ count($detail->kegiatan) }} Kejadian</span>
                    </div>

                    <div class="max-h-[480px] overflow-y-auto pr-1">
                        <x-log-kegiatan :kegiatan="$detail->kegiatan" />
                    </div>
                </div>
                <!-- End of Tab 3 -->
            </article>
            <!-- End of Kartu 4 -->

            <!-- Panel Tahap: status berjalan di kiri, tombol tujuan di kanan. -->
            <div class="lg:col-span-12 bg-surface-container-lowest rounded-lg shadow-sm p-space-md">
                <div class="flex flex-wrap items-center justify-between gap-space-md">
                    <div class="flex items-center gap-space-sm">
                        <div>
                            <span class="font-label-sm text-label-sm uppercase font-bold text-on-surface-variant">
                                Tahap Pengaduan: {{ $tiket->status->label() }}
                            </span>
                            <p class="font-body-sm text-body-sm text-on-surface">{{ $tiket->status->subStatus() }}</p>
                        </div>
                    </div>

                    {{-- Satu tombol untuk Diterima, dua tombol untuk Diproses,
                         satu tombol untuk Revisi, dan catatan saja kalau
                         tiketnya sudah selesai. --}}
                    <div class="flex flex-wrap items-center gap-space-md">
                        @forelse ($detail->tujuanTersedia() as $tujuan)

                            @php $tombol = $detail->tombolTujuan($tujuan); @endphp

                            <form method="POST" action="{{ route('admin.pengaduan.tahap', $kode) }}">
                                @csrf
                                <input type="hidden" name="tujuan" value="{{ $tujuan->value }}">

                                <button
                                    type="submit"
                                    class="py-3 px-space-xl rounded-lg {{ $tombol['warna'] }} font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 transition-opacity hover:opacity-90"
                                >
                                    <x-symbol :nama="$tombol['ikon']" class="text-[18px]" />
                                    {{ $tombol['label'] }}
                                </button>
                            </form>
                        @empty
                            <span class="font-body-sm text-body-sm text-on-surface-variant">
                                {{ $detail->alasanTutup() }}
                            </span>
                        @endforelse
                    </div>
                </div>

                @error('tujuan')
                    <span class="mt-space-sm block font-label-sm text-label-sm text-error">{{ $message }}</span>
                @enderror
            </div>
            <!-- End of Panel Tahap -->
        </div>
        <!-- End of Kolom Kerja -->
    </div>
@endsection

<x-script-tab-kartu />
