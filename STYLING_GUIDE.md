# Panduan Gaya Kode — Sistem Pengaduan RSUD Dr. Soetomo

Dokumen ini merangkum pola gaya (styling) yang dipakai di seluruh project. Tujuannya supaya kode baru konsisten dengan yang sudah ada, tanpa perlu menebak-nebak token warna, spasi, atau struktur Blade.

---

## 1. Arsitektur Warna (Design Tokens)

Semua warna didefinisikan di `resources/css/app.css` dalam blok `@theme`. **Jangan menulis warna hex/RGB langsung di template** — pakai token.

### 1.1 Palet Merek (Brand / Publik)
| Token | Nilai | Fungsi |
|-------|-------|--------|
| `brand-800` | `#0F7A3C` | Hijau hutan — tombol utama, tautan, ikon brand |
| `brand-700` | `#158A4A` | Hover tombol utama |
| `brand-600` | `#146C3E` | Teks kecil yang tetap terbaca |
| `brand-150` / `brand-light` | `#D7F5E3` / `#F7FCF9` | Latar kartu & panel halaman publik |
| `brand-100` | `#E3F9EC` | Badge "Selesai", latar chat admin |
| `gold-500` | `#D4AF37` | Emas — **hanya** untuk lencana penghargaan & badge SLA |

> **Aturan emas**: Emas tidak dipakai untuk UI biasa. Hanya piagam, medali, badge standar pencapaian.

### 1.2 Palet Konsol Admin (Material 3 — Skema Hijau)
| Kelompok | Token Utama | Keterangan |
|----------|-------------|------------|
| Surface | `surface`, `surface-container`, `surface-container-low`, `surface-container-high`, `surface-container-highest`, `surface-container-lowest` | Latar bertingkat (putih → abu-hijau muda) |
| On-Surface | `on-surface`, `on-surface-variant` | Teks utama & sekunder |
| Primary | `primary` (`#0F7A3C`), `primary-container` (`#146C3E`), `on-primary`, `on-primary-container` | Aksi utama |
| Secondary | `secondary` (`#146C3E`), `secondary-container` (`#D7F5E3`), `on-secondary`, `on-secondary-container` | Navigasi aktif, filter aktif |
| Tertiary | `tertiary` (`#8A6A12`), `tertiary-container` (`#F5D876`), `on-tertiary`, `on-tertiary-container` | **Aksen peringatan/emas** — badge SLA mendekati, chip "Lapis 2" |
| Error | `error` (`#BA1A1A`), `error-container` (`#FFDAD6`), `on-error`, `on-error-container` | Kegagalan, lewat SLA, kasus berat |
| Outline | `outline` (`#6B7280`), `outline-variant` (`#D3DDD6`) | Garis batas, placeholder, ikon non-aktif |

### 1.3 Token Spasi (Spacing)
| Token | Nilai | Dipakai untuk |
|-------|-------|---------------|
| `space-xs` | `0.25rem` (4px) | Gap ikon-teks, padding chip kecil |
| `space-sm` | `0.5rem` (8px) | Gap internal komponen |
| `space-md` | `1rem` (16px) | Gap antar blok, padding kartu |
| `space-lg` | `1.5rem` (24px) | Gap section besar |
| `space-xl` | `2rem` (32px) | Padding halaman, margin footer |
| `margin` / `margin-mobile` | `2rem` / `1rem` | Padding kiri-kanan kontainer utama |
| `gutter` / `gutter-mobile` | `1.5rem` / `1rem` | Grid internal |

> Di template: `px-margin`, `py-space-md`, `gap-space-lg`, dsb.

### 1.4 Tipografi Semantik
Project ini punya dua dialek yang sudah mapan dan tidak boleh dicampur dalam satu halaman:

| Area | Tipografi | Spasi | Pemakaian terukur |
|------|-----------|-------|-------------------|
| `resources/views/admin/**`, `resources/views/unit/**` | token semantik (`font-body-md text-body-md`) | token `space-*`, `margin`, `gutter` | 1.141 pemakaian semantik, 0 ukuran bawaan |
| `resources/views/pengaduan/**`, `resources/views/maklumat/**`, `resources/views/layouts/**` | skala bawaan Tailwind (`text-sm`, `text-[13px]`) | numerik (`px-4`, `gap-2`) | 112 pemakaian ukuran bawaan, 0 token |

Halaman publik mengikuti mock desain yang memakai ukuran tetap, konsol mengikuti skala Material 3. Kalau memperbaiki satu halaman, ikuti dialek halaman itu; jangan menyilang tanpa alasan.

Di konsol, gunakan kelas `font-*` dan `text-*` yang sudah terdaftar di `@theme`, **bukan** `text-base`, `text-lg`, dsb.

| Peran | Font | Size | Line Height | Letter Spacing |
|-------|------|------|-------------|----------------|
| `display-lg` | Plus Jakarta Sans | 3rem | 3.5rem | -0.02em |
| `headline-lg` | Plus Jakarta Sans | 2rem | 2.5rem | -0.01em |
| `headline-md` | Plus Jakarta Sans | 1.5rem | 2rem | -0.005em |
| `headline-sm` | Plus Jakarta Sans | 1.25rem | 1.75rem | — |
| `title-lg` | Plus Jakarta Sans | 1.125rem | 1.5rem | — |
| `title-md` | Plus Jakarta Sans | 1rem | 1.375rem | — |
| `title-sm` | Plus Jakarta Sans | 0.875rem | 1.25rem | — |
| `body-lg` | Plus Jakarta Sans | 1rem | 1.5rem | — |
| `body-md` | Plus Jakarta Sans | 0.875rem | 1.25rem | — |
| `body-sm` | Plus Jakarta Sans | 0.75rem | 1rem | — |
| `label-lg` | Plus Jakarta Sans | 0.875rem | 1.25rem | — |
| `label-md` | Plus Jakarta Sans | 0.75rem | 1rem | 0.02em |
| `label-sm` | Plus Jakarta Sans | 0.6875rem | 0.875rem | 0.04em |

Contoh pakai: `font-title-md text-title-md`, `font-body-sm text-body-sm`, `font-label-sm text-label-sm`.

### 1.5 Kelas yang Tidak Ada di `@theme`

Beberapa kelas beredar di template tetapi tidak pernah didefinisikan, jadi tidak menghasilkan CSS apa pun (sudah dicek ke `app.css` dan berkas build):

| Kelas | Dipakai di | Akibat |
|-------|------------|--------|
| `text-body-xs`, `font-body-xs` | `admin/layout`, `admin/riwayat/index`, `components/item-notifikasi`, `unit/layout` | waktu dan NRM tampil seukuran teks induk |
| `text-display-sm`, `font-display-sm` | `admin/unit/index` | angka beban unit tampil kecil |
| `bg-warning` | `unit/layout`, `unit/partials/notifikasi-dropdown` | titik nudge tidak berwarna |
| `text-brand-400` | `masuk`, `aktivasi/undangan`, `pengaduan/verifikasi-email` | placeholder memakai warna bawaan browser |

Skala `brand` melompat dari `300` ke `500`, `display` hanya punya `lg`, dan `body` berhenti di `sm`. Sebelum memakai kelas token baru, cari namanya di `resources/css/app.css`; kalau tidak ada, pakai token terdekat, jangan menambah `@theme` hanya untuk satu halaman.

### 1.6 Radius, Bayangan, dan Build

- Radius mengikuti Material 3 dan sengaja digeser satu tingkat dari skala Tailwind: `rounded-xs` (0.125rem), `rounded-sm` (0.25rem), `rounded-lg` (0.5rem), `rounded-xl` (0.75rem). Yang paling sering dipakai `rounded-lg` (207 kali), lalu `rounded-full` (150) dan `rounded-sm` (111). `rounded-2xl` dan `rounded-3xl` hanya dipakai di halaman publik untuk kartu besar.
- Bayangan: 44 pemakaian arbitrary seperti `shadow-[0_2px_14px_rgba(26,26,46,0.08)]` untuk topbar dan footer, lalu `shadow-sm` (82) dan `shadow-lg` (2). Arbitrary dipakai saat bayangan perlu tipis dan berwarna; untuk kartu cukup `shadow-sm`.
- `public/build` tidak ikut git. Setiap perubahan Blade atau `app.css` baru terlihat setelah `npm run dev` atau `npm run build`; kalau tampilan tidak berubah, cek dulu umur berkas di `public/build/assets`.

---

## 2. Pola Blade — Struktur Halaman

### 2.1 Tata Letak Dasar
```blade
<!DOCTYPE html>

<html lang="id" class="scroll-smooth">

<head>
    ...
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-surface font-body-md text-on-surface min-h-screen">

    <!-- Topbar (fixed) -->
    <header class="fixed top-0 left-0 right-0 z-50 ...">
        <!-- Baris Identitas -->
        <!-- Navigasi Utama -->
    </header>
    <!-- End of Topbar -->

    <!-- Main Content -->
    <main class="w-full pt-28 bg-surface flex-1">
        @yield('content')
    </main>
    <!-- End of Main Content -->

    <!-- Footer -->
    <footer class="w-full bg-white border-t border-outline-variant mt-space-xl py-space-lg">
        ...
    </footer>
    <!-- End of Footer -->

    @stack('scripts')

</body>

</html>
```

**Poin kunci:**
- `pt-28` di `<main>` = tinggi topbar (2 baris × 16 + 12 = 28).
- Selalu sertai komentar penutup: `<!-- End of Nama Bagian -->`.
- Blank line sebelum `@php`, `@foreach`, `@if`, dan setelah `@endphp`, `@endforeach`, `@endif`.

### 2.2 Navigasi (Topbar)
Menu disimpan di array PHP di awal file, lalu di-loop:

```blade
@php
    $navigasi = [
        ['label' => 'Beranda Utama', 'route' => 'admin.dashboard'],
        ['label' => 'Daftar Pengaduan', 'route' => 'admin.pengaduan.index'],
        ['label' => 'Master Data Unit', 'route' => 'admin.unit.index', 'pola' => 'admin.unit.*'],
    ];
@endphp

<nav class="flex items-center h-full gap-space-sm" aria-label="Navigasi konsol admin">
    @foreach ($navigasi as $item)
        @php
            $penanda = $item['pola'] ?? $item['route'] ?? null;
            $aktif = $penanda !== null && request()->routeIs($penanda);
        @endphp

        @if ($item['route'])
            <a
                href="{{ route($item['route']) }}"
                @class([
                    'flex items-center rounded-full px-3 py-1.5 transition-colors',
                    'bg-secondary-container text-on-secondary-container font-bold' => $aktif,
                    'text-ink-muted hover:text-ink hover:bg-surface-container font-title-sm text-title-sm font-semibold' => ! $aktif,
                ])
                @if ($aktif) aria-current="page" @endif
            >{{ $item['label'] }}</a>
        @endif
    @endforeach
</nav>
```

- Menu aktif: **pill mint** (`bg-secondary-container text-on-secondary-container`).
- Menu pasif: navy, hanya berwarna saat hover.
- Pakai `aria-current="page"` pada item aktif.

### 2.3 Strip Konteks (Context Strip)
Di atas tabel/daftar, selalu ada strip yang berisi:
- Indikator sinkronisasi (pulse hijau)
- Judul halaman (`headline-lg`)
- Deskripsi singkat + chip aturan (mint background)

```blade
<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-space-md">
    <div class="flex flex-col">
        <div class="flex items-center gap-space-xs text-secondary font-label-md text-label-md uppercase tracking-wider">
            <span class="w-2 h-2 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
            Dual Synchronization Gateway &bull; Humas &harr; Unit Pelayanan
        </div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-0.5">
            Daftar Pengaduan Pasien & Disposisi Medis
        </h1>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-4xl">
            Monitoring real-time investigasi {{ $ringkas['unit_total'] }} instalasi...
            <span class="inline-flex items-center gap-1 font-label-sm text-label-sm bg-surface-container px-2 py-0.5 rounded text-secondary font-semibold ml-1 align-middle">
                <x-symbol nama="sync_alt" class="text-[14px]" />
                Aturan: status utama berubah menjadi &ldquo;Diproses&rdquo; tepat saat disposisi diteruskan ke unit
            </span>
        </p>
    </div>
    ...
</div>
```

---

## 3. Komponen UI Utama

### 3.1 Kartu (Card)
```blade
<article class="bg-surface-container-lowest rounded-lg shadow-sm p-space-md flex flex-col gap-space-md">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm pb-space-sm border-b border-outline-variant/30">
        <div class="flex items-center gap-space-sm">
            <x-symbol nama="description" class="text-secondary text-[22px]" />
            <div>
                <h2 class="font-title-lg text-title-lg text-on-surface font-bold">Judul Kartu</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Deskripsi singkat</p>
            </div>
        </div>
        <span class="px-space-sm py-0.5 rounded-full bg-surface-container text-on-surface font-label-sm text-label-sm font-semibold">
            Badge Kategori
        </span>
    </div>
    <!-- Isi kartu -->
</article>
```

- Latar: `bg-surface-container-lowest` (putih)
- Bayangan: `shadow-sm`
- Radius: `rounded-lg` (0.5rem)
- Padding: `p-space-md` (1rem)
- Border bawah header: `border-b border-outline-variant/30`

### 3.2 Tabel Data
```blade
<div class="w-full bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden flex flex-col">
    <div class="bg-surface-container px-space-md py-space-sm flex flex-wrap items-center justify-between gap-space-sm text-on-surface-variant font-label-sm text-label-sm">
        <!-- Header info tabel -->
    </div>

    <div class="overflow-x-auto w-full">
        <table class="w-full min-w-[1180px] text-left border-collapse">
            <colgroup>
                <col class="w-[13%]">
                <col class="w-[16%]">
                ...
            </colgroup>
            <thead>
                <tr class="bg-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider border-b border-outline-variant/20">
                    <th scope="col" class="py-space-sm px-space-md">Judul Kolom</th>
                    ...
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20 text-body-sm text-on-surface">
                @forelse ($data as $item)
                    <tr @class(['align-top transition-colors', 'bg-surface-container-low/50' => $item->redup, 'hover:bg-surface-container-low' => ! $item->redup])>
                        <td class="py-space-sm px-space-md align-top">...</td>
                        ...
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-space-md py-space-xl text-center">...</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-surface-container-lowest px-space-md py-space-sm flex flex-col md:flex-row items-center justify-between gap-space-md">
        <!-- Footer: ukuran halaman, ringkasan, paginasi -->
    </div>
</div>
```

**Aturan tabel:**
- `min-w-[1180px]` + `overflow-x-auto` → scroll horizontal di mobile.
- `colgroup` dengan `w-[...]` → lebar kolom terkunci.
- Baris selesai (`redup`): `bg-surface-container-low/50` + tidak hover.
- Baris aktif: hover `bg-surface-container-low`.

### 3.3 Badge Status (Chip)
Polanya seragam: `inline-flex items-center gap-1 px-2.5 py-1 rounded-xl <token-warna> font-label-md text-label-md font-bold w-fit shadow-xs`

```blade
<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl {{ $status->badgeAdmin() }} font-label-md text-label-md font-bold w-fit shadow-xs">
    <x-symbol :nama="$status->ikon()" class="text-[14px]" />
    {{ $status->label() }}
</span>
```

Token warna per status didefinisikan di enum `StatusPengaduan::badgeAdmin()`:
- `Diterima` → `bg-tertiary-container text-on-tertiary-container`
- `Diproses` → `bg-primary-container text-on-primary`
- `Revisi` → `bg-warning-container text-on-warning-container` (kalau ada)
- `Selesai` → `bg-secondary-container text-on-secondary-container`

### 3.4 Tombol (Button)
**Tombol utama (Primary):**
```blade
<button type="submit" class="px-space-md py-2 rounded-lg bg-primary text-on-primary font-label-lg text-label-lg hover:bg-primary/90 transition-colors flex items-center gap-1.5 shadow-sm">
    <x-symbol nama="save" class="text-[18px]" />
    Simpan
</button>
```

**Tombol sekunder (Secondary / Outline):**
```blade
<a href="{{ route('...') }}" class="px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-label-lg text-label-lg hover:bg-surface-container-high transition-colors">
    Batal
</a>
```

**Tombol ghost / non-aktif (fitur belum jadi):**
```blade
<button type="button" disabled title="Fitur sedang dirancang" class="px-2 py-1.5 rounded-sm bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-semibold flex items-center justify-center gap-1 cursor-not-allowed opacity-70 whitespace-nowrap">
    <x-symbol nama="print" class="text-[14px] shrink-0" />
    Cetak Lembar Disposisi
</button>
```

> **Larang:** `href="#"` untuk fitur belum ada. Pakai `<button disabled>` atau `<span class="cursor-not-allowed">`.

### 3.5 Input Form
Class dasar disimpan di variabel `@php` awal file:

```blade
@php
    $input = 'w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40';
    $label = 'font-label-md text-label-md font-bold text-on-surface';
    $bantuan = 'font-label-sm text-label-sm text-outline';
    $galat = 'font-label-sm text-label-sm text-error';
@endphp

<div class="flex flex-col gap-1.5">
    <label for="nama" class="{{ $label }}">Nama Lengkap <span class="text-error">*</span></label>
    <input id="nama" type="text" name="nama" value="{{ old('nama') }}" required class="{{ $input }}">
    @error('nama')<span class="{{ $galat }}">{{ $message }}</span>@enderror
</div>
```

**Select:**
```blade
<select name="kategori" class="{{ $input }} appearance-none">
    <option value="" disabled @selected(! old('kategori'))>Pilih...</option>
    @foreach ($kategori as $kat)
        <option value="{{ $kat->value }}" @selected(old('kategori') === $kat->value)>{{ $kat->label() }}</option>
    @endforeach
</select>
```

**File upload (drag-drop):**
```blade
<label for="lampiran" class="cursor-pointer flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-outline-variant/50 bg-surface-container-low px-6 py-6 transition hover:border-secondary/60 hover:bg-surface-container">
    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-surface-container-lowest shadow-sm">
        <x-symbol nama="upload" class="text-[22px] text-primary-container" />
    </span>
    <span class="font-label-lg text-label-lg font-bold text-on-surface">Tarik & letakkan berkas, atau klik untuk memilih</span>
    <span class="{{ $bantuan }}">JPG, PNG, atau PDF — maksimal 4 MB per berkas</span>
</label>
<input type="file" id="lampiran" name="lampiran[]" accept=".jpg,.jpeg,.png,.pdf" multiple class="hidden">
```

### 3.6 Alert / Notifikasi
```blade
@if ($errors->any())
    <div role="alert" class="w-full bg-error-container text-on-error-container rounded-lg px-space-md py-space-sm flex items-start gap-space-sm">
        <x-symbol nama="error" class="text-[20px] shrink-0" />
        <div class="flex flex-col gap-0.5">
            <span class="font-label-lg text-label-lg font-bold">Periksa lagi isian formulir ini</span>
            @foreach ($errors->all() as $pesan)
                <span class="text-body-sm">{{ $pesan }}</span>
            @endforeach
        </div>
    </div>
@endif
```

Sukses:
```blade
<div role="status" class="w-full bg-secondary-container text-on-secondary-container rounded-lg px-space-md py-space-sm flex items-start gap-space-sm">
    <x-symbol nama="check_circle" class="text-[20px] shrink-0" />
    <span class="text-body-sm">{{ session('sukses') }}</span>
</div>
```

---

### 3.7 Komponen Blade yang Sudah Ada

Jangan menulis ulang markup yang sudah dibungkus komponen:

| Komponen | Isi | Dipakai di |
|----------|-----|------------|
| `x-brand-logo` | logo RSUD sebagai SVG inline, ukuran lewat `class="h-9 w-9"` | semua layout, footer |
| `x-symbol` | ikon Material Symbols, `aria-hidden` otomatis | seluruh konsol |
| `x-item-notifikasi` | satu baris notifikasi lonceng (`:notif="$notif"`) | dropdown admin |
| `x-stepper-tahap` | stepper empat tahap pengaduan | halaman detail |
| `x-tiket-identitas` | kartu identitas tiket ringkas | detail admin/unit |
| `x-bulk-action-scripts` | JS aksi massal tabel | daftar pengaduan |
| `x-script-tab-kartu` | JS tab kartu status | daftar pengaduan |
| `x-log-kegiatan` | daftar riwayat aksi | detail admin |

Selain itu ada satu partial yang di-include manual: `unit.partials.notifikasi-dropdown`. Badge dan warna status tidak ditulis di template, tetapi diambil dari enum (`StatusPengaduan::badgeAdmin()`, `ZonaSla::badge()`, `StatusInvestigasi::titik()`), jadi satu perubahan warna cukup di enum.

Data tampilan disiapkan lewat direktif `@use('App\Enums\...')` di baris atas view, lalu `@php` untuk variabel turunan. Query tetap di `App\Support\*` atau controller; template tidak boleh memanggil Eloquent.

---

## 4. Ikon — Material Symbols Outlined

- Font dimuat di `<head>` layout admin/unit: `Material Symbols Outlined` (variable font).
- Komponen `x-symbol` di `resources/views/components/symbol.blade.php`:

```blade
@props(['nama', 'class' => 'text-[20px]'])

<span {{ $attributes->merge(['class' => 'material-symbols-outlined '.$class]) }} aria-hidden="true">{{ $nama }}</span>
```

**Pakai:**
```blade
<x-symbol nama="person" class="text-[18px] text-on-primary" />
<x-symbol :nama="$status->ikon()" class="text-[14px]" />
```

- Ukuran default: `text-[20px]`.
- Selalu `aria-hidden="true"` (ikon dekoratif).
- Nama ikon = ligature Material Symbols (snake_case): `crisis_alert`, `verified_user`, `schedule`, `support_agent`, dsb.

---

## 5. Halaman Publik (Guest Layout)

Layout `resources/views/layouts/guest.blade.php` beda dari konsol:
- Font: `font-sans bg-brand-light text-ink antialiased`
- Topbar tidak fixed double-bar, cuma single bar `h-20`.
- Navigasi pakai pill mint untuk aktif, navy untuk pasif.
- Footer kaya (4 kolom: branding, kontak, lokasi, jam).
- Widget aksesibilitas (perbesar/kecilkan teks, kontras tinggi) + TTS (Web Speech API).
- Script inline di layout ditaruh sebelum `@stack('scripts')`; script milik halaman masuk lewat `@push('scripts')`.

**Token warna publik** (lihat `app.css` baris 19–58): `brand-*`, `ink`, `ink-muted`, `gold-*`, `alert-*`, `info-*`.

---

## 6. Konvensi Penamaan & Komentar (PHP)

### 6.1 Class Support (`App\Support\*`)
- `final class` + docblock jelas tanggung jawabnya.
- Method `public static` untuk query/logika murni.
- Property `public readonly` pada DTO hasil konstruktor.
- Komentar **menjelaskan alasannya**, bukan mengulang kode.

```php
/**
 * Data tampilan untuk halaman Daftar Pengaduan di konsol admin.
 *
 * Halaman ini menampilkan seluruh pengaduan dalam bentuk tabel yang bisa
 * disaring, jadi angka dan keterangan pada tabel harus berasal dari query
 * nyata. Class ini menahan query, penyusunan kalimat, dan pemilihan warna
 * supaya template hanya bekerja dengan data yang sudah jadi.
 */
final class DaftarPengaduan
{
    /** Jumlah baris per halaman yang boleh dipilih. */
    public const UKURAN_HALAMAN = [10, 25, 50, 100];

    /**
     * @param  array<string, int>  $jumlahTahap  Indeks 'semua' dan nilai enum tahap.
     * @param  array<string, int>  $jumlahInvestigasi  Indeks nilai enum StatusInvestigasi.
     * @param  array<string, mixed>  $ringkas  Angka untuk strip konteks, kartu kaki, dan paginasi.
     */
    public function __construct(
        public readonly LengthAwarePaginator $daftar,
        public readonly array $jumlahTahap,
        ...
    ) {}
}
```

### 6.2 Enum
Method helper di enum (mis. `StatusPengaduan::badgeAdmin()`, `StatusInvestigasi::titik()`) mengembalikan **string class Tailwind** siap pakai.

---

## 7. JavaScript Inline (Blade)

- Script halaman ditaruh di akhir view dalam `@push('scripts')`; script milik layout (aksesibilitas, pemilih suara, dropdown notifikasi) ditulis langsung di layout sebelum `@stack('scripts')`.
- Sebagian besar script layout berjalan langsung saat elemen sudah ada di DOM; `DOMContentLoaded` dipakai kalau script perlu menunggu markup lain. Dua-duanya ada di project ini, jadi ikuti pola file yang sedang diubah.
- Variabel `const` untuk elemen DOM.
- Fungsi kecil, nama deskriptif (`updateToolbar`, `showAksiFields`).
- Komentar singkat di atas blok logika kompleks.
- **Tidak** memuat library eksternal — hanya vanilla JS.

```blade
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form-bulk-action');
        ...

        function updateToolbar() { ... }
        function updateSelectAll() { ... }

        // Event listeners
        selectAll.addEventListener('change', function () { ... });
        ...
    });
</script>
@endpush
```

---

## 8. Aturan Khusus (Do / Don't)

| ✅ Do | ❌ Don't |
|------|----------|
| Simpan class panjang di `@php` variabel | Ulangi class yang sama di setiap input |
| Komentar alasannya (kenapa, bukan apa) | Komentar yang cuma terjemahan kode |
| `button disabled` untuk fitur belum jadi | `href="#"` atau `href="javascript:void(0)"` |
| `@class([...])` untuk kondisional class | `class="{{ $kondisi ? 'a' : 'b' }}"` bersarang |
| `aria-label` / `aria-current` pada navigasi | Abaikan aksesibilitas |
| `scroll-smooth` di `<html>` | Pakai JS untuk smooth scroll |
| `colgroup` + `w-[%]` untuk tabel | Biarkan kolom melebar/mengecil acak |
| `font-body-md text-body-md` (semantik) di konsol | `text-base` / `text-sm` di halaman konsol |
| Skala bawaan Tailwind di halaman publik | Mencampur token semantik dan skala bawaan dalam satu halaman |
| Cek nama token di `app.css` sebelum dipakai | Memakai `text-body-xs`, `display-sm`, `bg-warning`, `brand-400` yang tidak ada |
| Hex hanya di template ekspor (`exports/*`) dan SVG bendera/logo | Hex/RGB di class Blade halaman biasa |

---

## 9. Checklist Saat Menambah Halaman Baru

1. **Extend layout yang benar**: `admin.layout`, `unit.layout`, atau `layouts.guest`.
2. **Set `@section('title', '...')`** dengan judul yang jelas.
3. **Struktur konten**:
   - Strip konteks (judul + deskripsi + badge)
   - Filter / toolbar (kalau daftar)
   - Kartu / tabel / form
   - Footer tabel (paginasi, ringkasan)
4. **Warna**: hanya pakai token (`bg-surface-container`, `text-on-surface-variant`, dsb).
5. **Spasi**: konsol memakai `space-*`, `margin`, `gutter`; halaman publik memakai skala numerik halaman itu.
6. **Tipografi**: konsol memakai `font-* text-*` semantik; halaman publik memakai `text-sm`/`text-[13px]` sesuai mock.
7. **Ikon**: `<x-symbol nama="..." />`.
8. **Aksesibilitas**: `aria-label`, `role="alert"`/`"status"`, `scope="col"` di `<th>`.
9. **Script**: masuk `@push('scripts')`, jangan inline di tengah HTML.
10. **Komentar blok**: `<!-- Nama Bagian -->` ... `<!-- End of Nama Bagian -->`.

---

## 10. Referensi Cepat — Class Sering Dipakai

| Kebutuhan | Class Tailwind (token) |
|-----------|------------------------|
| Kontainer halaman | `w-full px-margin py-space-lg` |
| Grid responsif | `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg` |
| Kartu putih | `bg-surface-container-lowest rounded-lg shadow-sm p-space-md` |
| Header kartu | `flex items-center gap-space-sm pb-space-sm border-b border-outline-variant/30` |
| Badge aktif (pill) | `inline-flex items-center gap-1 px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container font-bold` |
| Badge pasif | `inline-flex items-center gap-1 px-3 py-1 rounded-full bg-surface-container hover:bg-surface-container-high text-on-surface-variant` |
| Tombol utama | `px-space-md py-2 rounded-lg bg-primary text-on-primary font-label-lg text-label-lg hover:bg-primary/90 transition-colors flex items-center gap-1.5 shadow-sm` |
| Tombol sekunder | `px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-label-lg text-label-lg hover:bg-surface-container-high transition-colors` |
| Input form | `w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40` |
| Label form | `font-label-md text-label-md font-bold text-on-surface` |
| Teks bantuan | `font-label-sm text-label-sm text-outline` |
| Teks error | `font-label-sm text-label-sm text-error` |
| Tabel scroll | `overflow-x-auto w-full` + `table min-w-[1180px]` |
| Header tabel | `bg-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider border-b border-outline-variant/20` |
| Baris tabel | `divide-y divide-outline-variant/20 text-body-sm text-on-surface` |
| Pagination aktif | `w-8 h-8 rounded-sm bg-primary-container text-on-primary font-label-md text-label-md font-bold flex items-center justify-center` |
| Pagination pasif | `w-8 h-8 rounded-sm bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-medium flex items-center justify-center transition-colors` |

---

*Dokumen ini dihasilkan dari analisis kode pada `app/Support/*`, `resources/views/**/*.blade.php`, `resources/css/app.css`, dan CSS hasil build; terakhir dicek ulang 29 Oktober 2026. Dokumen lain (`STYLE.md`, `STYLE_GUIDE.md`, `STYLING_CODE_GUIDE.md`) membahas topik serupa dengan sudut berbeda; kalau ada yang bertentangan, kode di repository yang berlaku dan dokumen ini yang diperbarui.*