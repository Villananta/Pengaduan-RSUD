# Panduan Styling Code - Sistem Pengaduan RSUD Dr. Soetomo

Dokumen ini merupakan konsolidasi lengkap dari semua pola styling dan konvensi kode yang digunakan di project ini. Isinya sudah dicocokkan langsung dengan kode di `app/`, `resources/views/`, `resources/css/app.css`, dan `tests/` — bukan hanya disalin dari dokumen lain.

Versi ringkas untuk pekerjaan sehari-hari ada di `STYLE.md`. Kalau keduanya berbeda, yang berlaku adalah kode yang ada di repository; perbarui dokumen ini setelah polanya berubah.

Stack yang menjadi acuan: Laravel 13.38, PHP 8.5 (composer mewajibkan `^8.4`), Tailwind CSS 4 (`@theme` di `resources/css/app.css`), Blade + vanilla JS, dan PHPUnit 12.

## Daftar Isi

- [1. Desain Token](#1-desain-token)
- [2. Tipografi](#2-tipografi)
- [3. Struktur Layout Blade](#3-struktur-layout-blade)
- [4. Komponen UI](#4-komponen-ui)
- [5. Ikon (Material Symbols)](#5-ikon-material-symbols)
- [6. Konvensi PHP](#6-konvensi-php)
- [7. Konvensi Blade](#7-konvensi-blade)
- [8. JavaScript Inline](#8-javascript-inline)
- [9. Halaman Publik vs Admin](#9-halaman-publik-vs-admin)
- [10. Test](#10-test)
- [11. Checklist](#11-checklist)
- [12. Larangan](#12-larangan)

---

## 1. Desain Token

Semua warna didefinisikan di `resources/css/app.css` dalam blok `@theme`. **Jangan menulis warna hex/RGB langsung di template** - pakai token.

### 1.1 Palet Merek (Halaman Publik)

| Token | Nilai | Fungsi |
|-------|-------|--------|
| `brand-950` | `#073b1f` | Hijau sangat gelap |
| `brand-900` | `#0a4a27` | Hijau gelap |
| `brand-800` | `#0f7a3c` | **Warna merek utama** - tombol, ikon, tautan |
| `brand-700` | `#158a4a` | Hover tombol utama |
| `brand-600` | `#146c3e` | Teks kecil yang tetap terbaca |
| `brand-500` | `#17804c` | Teks aksen kecil |
| `brand-300` / `brand-250` / `brand-200` | `#86dcab` / `#a9e7c2` / `#c3efd5` | Garis dan latar mint bertingkat |
| `brand-150` / `brand-light` | `#d7f5e3` / `#f7fcf9` | Latar kartu & panel |
| `brand-100` | `#e3f9ec` | Pil navigasi aktif, latar kartu ringan |
| `brand-50` | `#effaf4` | Hover paling ringan |
| `brand-section` / `brand-deep` | `#e3f9ec` / `#0a4a27` | Latar section dan teks hijau pekat |
| `gold-500` | `#d4af37` | Emas - **hanya** untuk lencana penghargaan & badge SLA |
| `ink` | `#1a1a2e` | Teks utama |
| `ink-muted` | `#6b7280` | Teks sekunder |
| `placeholder` | `#9ca3af` | Placeholder form publik |
| `chat-admin` / `chat-accent` | `#e3f9ec` / `#8ee7b4` | Gelembung chat humas dan aksennya |
| `info` / `info-light` | `#003ea8` / `#dbeafe` | Pesan informasi |
| `alert` / `alert-dark` / `alert-light` | `#ba1a1a` / `#93000a` / `#ffdad6` | Error/peringatan |

> **Aturan emas**: Emas tidak dipakai untuk UI biasa. Hanya untuk piagam, medali, badge standar pencapaian.

### 1.2 Palet Konsol Admin (Material 3 - Skema Hijau)

| Kelompok | Token | Keterangan |
|----------|-------|------------|
| **Surface** | `surface`, `surface-container`, `surface-container-low`, `surface-container-high`, `surface-container-highest`, `surface-container-lowest` | Latar bertingkat (putih → abu-hijau muda) |
| **On-Surface** | `on-surface`, `on-surface-variant` | Teks utama & sekunder |
| **Primary** | `primary` (`#0f7a3c`), `primary-container` (`#146c3e`), `on-primary`, `on-primary-container` | Aksi utama |
| **Secondary** | `secondary` (`#146c3e`), `secondary-container` (`#d7f5e3`), `on-secondary`, `on-secondary-container` | Navigasi aktif, filter aktif |
| **Tertiary** | `tertiary` (`#8a6a12`), `tertiary-container` (`#f5d876`), `on-tertiary`, `on-tertiary-container` | Aksen peringatan/emas - badge SLA mendekati |
| **Error** | `error` (`#ba1a1a`), `error-container` (`#ffdad6`), `on-error`, `on-error-container` | Kegagalan, lewat SLA, kasus berat |
| **Outline** | `outline` (`#6b7280`), `outline-variant` (`#d3ddd6`) | Garis batas, placeholder, ikon non-aktif |

### 1.3 Spacing Tokens

| Token | Nilai | Dipakai untuk |
|-------|-------|---------------|
| `space-xs` | `0.25rem` (4px) | Gap ikon-teks, padding chip kecil |
| `space-sm` | `0.5rem` (8px) | Gap internal komponen |
| `space-md` | `1rem` (16px) | Gap antar blok, padding kartu |
| `space-lg` | `1.5rem` (24px) | Gap section besar |
| `space-xl` | `2rem` (32px) | Padding halaman, margin footer |
| `margin` / `margin-mobile` | `2rem` / `1rem` | Padding kiri-kanan kontainer utama |
| `gutter` / `gutter-mobile` | `1.5rem` / `1rem` | Grid internal |

Di template: `px-margin`, `py-space-md`, `gap-space-lg`, dll.

### 1.4 Border Radius

Tidak ada override radius di `@theme`; yang dipakai adalah skala bawaan Tailwind v4. Design konsol memakai nama radius yang satu tingkat lebih kecil, jadi padanannya ditulis seperti ini:

| Di design | Class yang ditulis | Nilai |
|-----------|--------------------|-------|
| `rounded` | `rounded-xs` | `0.125rem` |
| `rounded-lg` | `rounded-sm` | `0.25rem` |
| `rounded-xl` | `rounded-lg` | `0.5rem` |
| `rounded-full` | `rounded-xl` | `0.75rem` |
| pil / bulat penuh | `rounded-full` | `9999px` |

Contoh nyata: chip status memakai `rounded-xl` (0.75rem), navigasi pill memakai `rounded-full`, dan kartu memakai `rounded-lg` (0.5rem). Jangan menambah token radius baru hanya untuk satu komponen.

---

## 2. Tipografi

Gunakan kelas `font-*` dan `text-*` semantik yang sudah terdaftar di `@theme`, **bukan** `text-base`, `text-lg`, dll.

**Font**: `Plus Jakarta Sans`

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

**Contoh penggunaan**: `font-title-md text-title-md`, `font-body-sm text-body-sm`, `font-label-sm text-label-sm`.

---

## 3. Struktur Layout Blade

### 3.1 Layout Konsol Admin (`admin/layout.blade.php`)

```blade
<!DOCTYPE html>

<html lang="id" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda Utama') — Portal Pengaduan Humas | RSUD Dr. Soetomo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-surface font-body-md text-on-surface min-h-screen">
    <!-- Topbar (fixed) -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-[0_2px_14px_rgba(26,26,46,0.08)]">
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
- `pt-28` di `<main>` = tinggi topbar (2 baris × 16 + 12 = 28)
- Selalu sertai komentar penutup: `<!-- End of Nama Bagian -->`
- Blank line setelah `<!DOCTYPE html>`, sebelum `<head>`, setelah `</head>`, sebelum `<body>`, sebelum `</body>`

### 3.2 Layout Publik/Guest (`layouts/guest.blade.php`)

```blade
<!DOCTYPE html>

<html lang="id" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>@yield('title', 'Buat Aduan') — Sistem Pengaduan RSUD Dr. Soetomo</title>
    @yield('robots')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-brand-light text-ink antialiased">
    <!-- Header -->
    <header class="fixed inset-x-0 top-0 z-20 bg-white shadow-[0_2px_14px_rgba(26,26,46,0.07)]">
        ...
    </header>
    <!-- End of Header -->

    <!-- Konten Halaman -->
    <main class="pt-20">
        @yield('content')
    </main>
    <!-- End of Konten Halaman -->

    <!-- Footer -->
    <footer class="relative z-10 w-full bg-white py-10 border-t border-brand-200/60 shadow-[0_-2px_14px_rgba(26,26,46,0.05)]">
        ...
    </footer>
    <!-- End of Footer -->

    <!-- Widget Aksesibilitas -->
    <!-- Widget Pilih Suara -->
    
    <script>...</script>
    @stack('scripts')
</body>

</html>
```

**Perbedaan:** Topbar single bar `h-20`, navigasi pill mint, footer 4 kolom, widget aksesibilitas & TTS.

### 3.3 Layout Konsol Unit (`unit/layout.blade.php`)

Bentuknya mengikuti konsol admin, tetapi warna dan penanda kondisinya milik
unit: lonceng notifikasi, titik nudge, dan bilah status unit. Yang perlu dijaga:

- Notifikasi unit selalu dibaca dari **akun petugas**, bukan dari `MasterUnit`.
  Parameter `{unit}` di URL hanya menjaga konteks halaman. Karena itu data
  lonceng dikirim controller sebagai `NotifikasiUnit`, bukan dihitung di Blade.
- Status "ada nudge" dan "jumlah belum dibaca" datang dari respons server
  (`ada_nudge`, `total_belum_dibaca`), bukan dari tebakan JavaScript.
- Polling memakai `next_since` dari respons sebelumnya. Jangan menghitung
  jendela waktu sendiri di browser; server sudah mengonversi ISO8601 ke zona
  aplikasi.
- Halaman unit tidak boleh memuat tautan ke konsol admin. Kalau sebuah aksi
  belum punya halaman unit, tampilkan `span` nonaktif, bukan tautan yang
  berakhir 403.

---

## 4. Komponen UI

### 4.1 Kartu (Card)

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
- Radius: `rounded-lg`
- Padding: `p-space-md`
- Border bawah header: `border-b border-outline-variant/30`

### 4.2 Tabel Data

```blade
<div class="w-full bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden flex flex-col">
    <div class="bg-surface-container px-space-md py-space-sm flex flex-wrap items-center justify-between gap-space-sm text-on-surface-variant font-label-sm text-label-sm">
        <!-- Header info tabel -->
    </div>

    <div class="overflow-x-auto w-full">
        <table class="w-full min-w-[1180px] text-left border-collapse">
            <caption class="sr-only">Deskripsi tabel</caption>
            <colgroup>
                <col class="w-[13%]">
                <col class="w-[16%]">
                ...
            </colgroup>
            <thead>
                <tr class="bg-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider border-b border-outline-variant/20">
                    <th scope="col" class="py-space-sm px-space-md">Judul Kolom</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20 text-body-sm text-on-surface">
                @forelse ($data as $item)
                    <tr @class(['align-top transition-colors', 'bg-surface-container-low/50' => $item->redup, 'hover:bg-surface-container-low' => ! $item->redup])>
                        <td class="py-space-sm px-space-md align-top">...</td>
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
- `min-w-[...]` + `overflow-x-auto` → scroll horizontal di mobile. Lebarnya mengikuti jumlah kolom: `min-w-[900px]` untuk tabel unit ringkas, `min-w-[1000px]`/`min-w-[1180px]` untuk daftar pengaduan, `min-w-[1200px]` untuk riwayat admin
- `colgroup` dengan `w-[%]` → lebar kolom terkunci
- Baris selesai (`redup`): `bg-surface-container-low/50` + tidak hover
- Baris aktif: hover `bg-surface-container-low`
- Gunakan `scope="col"` di `<th>`, `caption` untuk aksesibilitas

### 4.3 Badge Status (Chip)

```blade
<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl {{ $status->badgeAdmin() }} font-label-md text-label-md font-bold w-fit shadow-xs">
    <x-symbol :nama="$status->ikon()" class="text-[14px]" />
    {{ $status->label() }}
</span>
```

Warna dari enum `StatusPengaduan::badgeAdmin()` (kondisi per Oktober 2026):

- `Diterima` → `bg-secondary-container text-on-secondary-container`
- `Diproses` → `bg-secondary-container text-on-secondary-container`
- `Revisi` → `bg-error text-on-error`
- `Selesai` → `bg-secondary-container text-on-secondary-container`

Tiga tahap memakai mint yang sama; yang membedakan tahapnya adalah label dan
ikon, bukan warnanya. `Revisi` memakai `bg-error` karena satu-satunya tahap yang
menuntut tindakan pelapor.

> Token `warning-*` **tidak ada** di `app.css`. Jangan mengarang nama token; kalau
> butuh nada peringatan, pakai `tertiary-*` (emas) atau `error-*`.

Ukuran chip mengikuti tempatnya: `px-2.5 py-1` untuk kolom tabel, dan
`px-space-xs py-0.5` untuk badge ringkas di kartu/dashboard.

### 4.4 Tombol (Button)

**Tombol utama:**
```blade
<button type="submit" class="px-space-md py-2 rounded-lg bg-primary text-on-primary font-label-lg text-label-lg hover:bg-primary/90 transition-colors flex items-center gap-1.5 shadow-sm">
    <x-symbol nama="save" class="text-[18px]" />
    Simpan
</button>
```

**Tombol sekunder/outline:**
```blade
<a href="{{ route('...') }}" class="px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-label-lg text-label-lg hover:bg-surface-container-high transition-colors">
    Batal
</a>
```

**Tombol ghost/non-aktif (fitur belum jadi):**
```blade
<button type="button" disabled title="Fitur sedang dirancang" class="px-2 py-1.5 rounded-sm bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-semibold flex items-center justify-center gap-1 cursor-not-allowed opacity-70 whitespace-nowrap">
    <x-symbol nama="print" class="text-[14px] shrink-0" />
    Cetak Lembar Disposisi
</button>
```

> **Larang:** `href="#"` untuk fitur belum ada. Pakai `<button disabled>` atau `<span class="cursor-not-allowed">`.

### 4.5 Form Input

Simpan class dasar di variabel `@php`:

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

**Halaman publik memakai variabel sendiri.** Form di `pengaduan/create.blade.php`
tidak memakai token konsol, melainkan palet brand dan ukuran teks biasa:

```blade
@php
    $input = 'w-full rounded-xl bg-brand-light px-4 py-3.5 text-sm text-ink placeholder-placeholder outline-none ring-1 ring-transparent transition focus:ring-2 focus:ring-brand-800';
    $label = 'block text-sm font-semibold text-ink';
    $errorClass = 'mt-1.5 text-[11px] font-medium text-alert';
    $stepBadge = 'flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-800 text-[11px] font-bold text-white';
@endphp
```

Nama variabelnya memang tidak seragam: form admin memakai `$galat`, form publik
memakai `$errorClass`. Ikuti nama yang sudah ada di file yang sedang disunting
supaya diff tetap kecil.

### 4.6 Alert / Notifikasi

**Error:**
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

**Sukses:**
```blade
<div role="status" class="w-full bg-secondary-container text-on-secondary-container rounded-lg px-space-md py-space-sm flex items-start gap-space-sm">
    <x-symbol nama="check_circle" class="text-[20px] shrink-0" />
    <span class="text-body-sm">{{ session('sukses') }}</span>
</div>
```

### 4.7 Dropdown Notifikasi (`<details>`)

Lonceng notifikasi di kedua konsol memakai `<details>`/`<summary>`, bukan
`div` yang dibuka lewat JavaScript. Dengan begitu dropdown tetap bisa dibuka
kalau skrip gagal dimuat, dan JavaScript hanya bertugas menyegarkan isi serta
menyelaraskan atribut aksesibilitas.

```blade
<div class="relative" id="admin-notifikasi-wrapper">
    <details id="admin-detail-notifikasi" class="static">
        <summary
            id="admin-btn-notifikasi"
            data-daftar-url="{{ route('admin.notifikasi.daftar') }}"
            aria-label="Notifikasi"
            aria-expanded="false"
            aria-haspopup="true"
            class="relative p-space-xs cursor-pointer list-none [&::-webkit-details-marker]:hidden"
        >
            <x-symbol nama="notifications" class="text-[24px]" />

            @if ($jumlahNotifAdmin > 0)
                <span
                    id="admin-badge-notifikasi"
                    class="absolute -top-0.5 -right-0.5 w-5 h-5 min-w-5 rounded-full bg-error text-on-error text-[10px] font-bold flex items-center justify-center"
                    aria-label="{{ $jumlahNotifAdmin }} notifikasi belum dibaca"
                >{{ $jumlahNotifAdmin > 9 ? '9+' : $jumlahNotifAdmin }}</span>
            @endif
        </summary>

        <div class="absolute right-0 top-full mt-2 w-96 max-h-[420px] overflow-y-auto bg-white rounded-lg shadow-lg border border-outline-variant z-[60]" role="menu">
            {{-- Tombol ini tetap <form> supaya "tandai semua" berfungsi
                 tanpa JavaScript; skrip hanya menambahkan jalur AJAX. --}}
            <form method="POST" action="{{ route('admin.notifikasi.baca-semua') }}" class="inline">
                @csrf
                <button type="submit">Tandai semua dibaca</button>
            </form>

            @forelse ($notifikasiAdmin as $notif)
                <x-item-notifikasi :notif="$notif" />
            @empty
                <div class="p-4 text-center text-on-surface-variant text-body-sm">Tidak ada notifikasi baru</div>
            @endforelse
        </div>
    </details>
</div>
```

**Aturan yang mengikat pola ini:**

- `class="static"` pada `<details>` supaya `absolute` di dalamnya menempel ke
  pembungkus `relative`, bukan ke `<details>` itu sendiri.
- `list-none` dan `[&::-webkit-details-marker]:hidden` menghapus panah bawaan
  `<summary>`; jangan menggantinya dengan `div` yang butuh `onclick`.
- `aria-expanded` diubah di listener `toggle`, bukan di `onclick` summary.
- Item notifikasi dirender server lewat komponen `x-item-notifikasi` **dan**
  dibangun ulang oleh JavaScript dengan markup yang sama. Kalau salah satu
  sisi berubah, `data-notif-id`/`data-notif-type` di sisi lain ikut patah.
- "Tandai semua dibaca" tetap berupa `<form method="POST">` dengan `@csrf`.
  Skrip boleh mencegat submit, tetapi tanpa skrip tombolnya harus tetap jalan.
- `role="menu"` pada wadah dan `role="menuitem" tabindex="0"` pada item.
  Enter/Space membuka item, Escape menutup dropdown, klik di luar menutup.
- Angka lencana dipotong menjadi `9+`; nilai penuhnya tetap disimpan di
  `aria-label`.
- Kalau notifikasi tidak punya `url`, render `div`, bukan `<a href="#">`.
- `$jumlahNotifAdmin` dan `$notifikasiAdmin` dikirim controller atau support
  class, bukan dihitung lewat query di dalam layout. Contoh di atas hanya
  menunjukkan bentuk markup-nya.

### 4.8 Status Turunan Server

Keadaan yang dipakai untuk menandai UI (jumlah belum dibaca, ada nudge,
`next_since`, status eskalasi) selalu berasal dari respons server. Template
dan JavaScript tidak menghitung ulang dari data yang kebetulan sudah tampil.

- Endpoint daftar/baca mengembalikan `total_belum_dibaca` dan `ada_nudge`
  setiap kali, walau aksinya hanya menandai satu notifikasi.
- Setelah aksi berhasil, badge dan titik nudge diperbarui dari angka respons,
  bukan dari `+1`/`-1` lokal.
- Notifikasi yang menandai "perlu follow up" hanya tipe `pengaduan_nudge_unit`.
  Tipe lain tidak boleh menyalakan titik nudge.
- Data notifikasi yang dikirim ke browser sudah berupa array siap tampil
  (`title`, `message`, `url`, `kode_tiket`), bukan model Eloquent.

---

## 5. Ikon (Material Symbols)

- Font dimuat di `<head>` layout admin/unit: `Material Symbols Outlined` (variable font)
- Komponen `x-symbol` di `resources/views/components/symbol.blade.php`:

```blade
@props(['nama', 'class' => 'text-[20px]'])

<span {{ $attributes->merge(['class' => 'material-symbols-outlined '.$class]) }} aria-hidden="true">{{ $nama }}</span>
```

**Penggunaan:**
```blade
<x-symbol nama="person" class="text-[18px] text-on-primary" />
<x-symbol :nama="$status->ikon()" class="text-[14px]" />
```

**Aturan:**
- Ukuran default: `text-[20px]`
- Selalu `aria-hidden="true"` (ikon dekoratif)
- Nama ikon = ligature Material Symbols (snake_case): `crisis_alert`, `verified_user`, `schedule`, `support_agent`, `arrow_back`, dll.

---

## 6. Konvensi PHP

### 6.1 Class & Method

- Class di `app/Support/` dan `app/Services/` ditulis `final`
- Enum, model, controller, middleware, command, mail, dan notification **tidak** memakai `final` (enum memang tidak bisa diwariskan)
- Model Eloquent tidak `final`
- Semua query dan perhitungan tampilan di `App\Support\*`, bukan di Blade
- Proses tulis multi-langkah (disposisi, tahap, komunikasi, kasus berat) ada di `app/Services/*`, dibungkus `DB::transaction`, dan melempar `RuntimeException` untuk aturan yang dilanggar
- Controller hanya memvalidasi, memanggil Support/Service, lalu return view/redirect. Query database tidak ditulis di controller
- Method publik dengan perilaku tidak jelas pakai docblock penuh
- Method yang namanya jelas cukup satu baris `/** ... */`

```php
/**
 * Perhitungan Standar Pelayanan Minimal dalam hari kerja.
 *
 * Semua angka SLA pada halaman publik berasal dari sini, bukan dari
 * teks hardcoded, sehingga batas waktu dan tampilan selalu konsisten.
 */
final class Sla
{
    /** Batas respons awal dalam jam. */
    public static function responsAwalJam(): int
    {
        return (int) config('pengaduan.sla.respons_awal_jam', 24);
    }
}
```

### 6.2 Naming

| Tipe | Format | Contoh |
|------|--------|--------|
| Class/Enum | `StudlyCase` | `Pengaduan`, `StatusInvestigasi`, `Sla` |
| Method/Variabel | `camelCase` | `dariRequest`, `hariKerjaLewat`, `$jumlahTahap` |
| Kolom DB/Model atribut | `snake_case` | `kode_tiket`, `selesai_at`, `master_unit_id` |
| Konstanta | `UPPER_SNAKE_CASE` | `BATAS_KONEKSI`, `UKURAN_HALAMAN` |
| Scope query | `scope` prefix | `scopeAktif`, `scopeTerhubung` |

### 6.3 Penulisan

- Indent 4 spasi, mengikuti Pint/PSR-12
- **Jangan** pakai `declare(strict_types=1)`
- Pisahkan bagian dengan baris kosong, beri label `// Nama Bagian` jika perlu
- Gunakan `match` expression (termasuk `match (true)`)
- Kelas data di `App\Support\` pakai constructor property promotion dengan `readonly`
- Factory method bernama bahasa Indonesia: `dariRequest()`, `buat()`, dll.
- Import class/enum di Blade pakai `@use()` di baris atas, bukan `use` di `@php`

```blade
@use('App\Enums\StatusPengaduan')
@use('Illuminate\Support\Str')
```

Bentuk tanpa kutip juga diterima: `@use(App\Support\Sla)`. FQCN langsung seperti
`{{ \App\Support\Sla::hariKerja() }}` masih ada di layout lama; view baru
sebaiknya memakai `@use()` supaya isi template tetap bersih.

### 6.4 Model

```php
protected $fillable = [
    'kategori',
    'nama_lengkap',
    // ...
];

protected $casts = [
    'waktu_kejadian' => 'datetime',
    'selesai_at' => 'datetime',
    'kasus_berat' => 'boolean',
    'kategori' => KategoriPengaduan::class,
    'status' => StatusPengaduan::class,
];

/**
 * Buat pengaduan baru dengan kode tiket yang dibuat di dalam sistem.
 * Kode tiket sengaja tidak ada di $fillable sehingga tidak bisa
 * diisi massal dari input pengguna; kode dibuat di dalam sistem.
 */
public static function buat(array $data, string $kodeTiket, ?StatusPengaduan $status = null): self
{
    $pengaduan = new self;
    $pengaduan->fill($data);
    $pengaduan->kode_tiket = $kodeTiket;
    $pengaduan->status = $status ?? StatusPengaduan::Diterima;
    $pengaduan->save();
    return $pengaduan;
}
```

### 6.5 Enum

```php
enum StatusPengaduan: string
{
    case Diterima = 'diterima';
    case Diproses = 'diproses';
    case Revisi = 'revisi';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Diterima => 'Diterima',
            self::Diproses => 'Diproses',
            self::Revisi => 'Perlu Revisi',
            self::Selesai => 'Selesai',
        };
    }

    /** Warna badge pada daftar dan detail tiket. */
    public function badgeAdmin(): string
    {
        return match ($this) {
            self::Diterima => 'bg-secondary-container text-on-secondary-container',
            self::Diproses => 'bg-secondary-container text-on-secondary-container',
            self::Revisi => 'bg-error text-on-error',
            self::Selesai => 'bg-secondary-container text-on-secondary-container',
        };
    }

    public function ikon(): string
    {
        return match ($this) {
            self::Diterima => 'inbox',
            self::Diproses => 'autorenew',
            self::Revisi => 'rate_review',
            self::Selesai => 'task_alt',
        };
    }
}
```

Method bantu enum yang sudah dipakai dan sebaiknya ditambah di sini, bukan di
template: `label()`, `badge()` (halaman publik), `badgeAdmin()` (konsol),
`ikon()`, `subStatus()`, `warnaAngka()`, `warnaIkon()`, `chip()`, `tahap()`,
`tujuanBerikutnya()`, `bisaBerpindahKe()`, dan `perluAksi()`.

### 6.6 Config

- Semua angka SLA, daftar unit, peta unit di `config/pengaduan.php`
- Komentar menjelaskan **kenapa** nilai dipakai
- Jangan hardcode angka SLA di luar config/enum/support

```php
'sla' => [
    'hari_kerja' => 12,
    'respons_awal_jam' => 24,
    'peringatan_hari_kerja' => 2,
    'investigasi_hari_kerja' => 5,
    'kasus_berat_hari_kerja' => 8,
],
```

---

## 7. Konvensi Blade

### 7.1 Struktur & Spasi

- Satu baris kosong setelah `<!DOCTYPE html>`, sebelum `<head>`, setelah `</head>`, sebelum `<body>`, sebelum `</body>`
- Komentar pembuka/penutup berpasangan untuk section besar (huruf kapital di awal, bahasa Indonesia):
  ```blade
  <!-- Sidebar -->
  @include('layout.sidebar')
  <!-- End of Sidebar -->
  ```
- Bagian tanpa pasangan: `<!-- Scroll to Top Button -->` atau `<!-- Logout Modal-->`
- Konten halaman: `<!-- Begin Page Content -->` ... `<!-- End of Page Content -->`
- Tag pendek (1 elemen, 1 baris): satu baris
- Tag panjang (banyak atribut/class): multi-baris, satu atribut per baris, indent 4 spasi
- Komentar Blade: `{{-- ... --}}` (tidak terlihat di output)
- Komentar HTML: `<!-- ... -->` (penanda bagian)

### 7.2 Directive

```blade
@php
    // Komentar di sini
    $variabel = 'nilai';
@endphp

@foreach ($items as $item)
    ...
@endforeach

@if ($kondisi)
    ...
@endif
```

- Blank line sebelum `@php`, `@foreach`, `@if`
- Blank line setelah `@endphp`, `@endforeach`, `@endif`
- `@stack('scripts')` tetap diletakkan sebelum `</body>`

### 7.3 Section & Stack

```blade
@extends('admin.layout')

@section('title', 'Judul Halaman')

@section('content')
    ...
@endsection

@push('scripts')
    <script>...</script>
@endpush
```

Script yang sudah menjadi bagian layout (misalnya dropdown notifikasi di
`admin/layout.blade.php`) ditulis langsung di layout sebelum `@stack('scripts')`.
Script milik satu halaman tetap masuk `@push('scripts')` di view halaman itu.
Jangan menaruh script halaman di tengah HTML.

### 7.4 Kondisional Class

Gunakan `@class()` untuk kondisional class:

```blade
<a
    href="{{ route($item['route']) }}"
    @class([
        'flex items-center rounded-full px-3 py-1.5 transition-colors',
        'bg-secondary-container text-on-secondary-container font-bold' => $aktif,
        'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' => ! $aktif,
    ])
    @if ($aktif) aria-current="page" @endif
>{{ $item['label'] }}</a>
```

**Jangan** gunakan `class="{{ $kondisi ? 'a' : 'b' }}"` bersarang.

### 7.5 Navigasi

Menu disimpan di array PHP di awal file:

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
                @class([...])
                @if ($aktif) aria-current="page" @endif
            >{{ $item['label'] }}</a>
        @endif
    @endforeach
</nav>
```

- Menu aktif: pill mint (`bg-secondary-container text-on-secondary-container`)
- Menu pasif: hanya berwarna saat hover
- Pakai `aria-current="page"` pada item aktif

---

## 8. JavaScript Inline

- Script khusus halaman ditaruh di akhir view, dalam `@push('scripts')`
- Script yang melekat pada layout ditulis langsung di layout sebelum `@stack('scripts')`
- Pakai `DOMContentLoaded` untuk script halaman; script layout boleh memakai IIFE `(function () { ... })();`
- Variabel `const` untuk elemen DOM
- Fungsi kecil, nama deskriptif
- Komentar singkat di atas blok logika kompleks
- **Tidak** memuat library eksternal - hanya vanilla JS
- Interaksi buka/tutup memakai `<details>`; skrip hanya menyelaraskan
  `aria-expanded`, memuat isi, dan menangani Escape/klik di luar
- Setiap teks dari server yang disisipkan lewat `innerHTML` wajib melewati
  `escapeHtml()`; jangan menempel `notif.title` mentah ke template string
- Item yang dapat diklik diberi `role="menuitem"` dan `tabindex="0"`, lalu
  dengar `keydown` untuk Enter/Space. Jangan mengandalkan klik tetikus saja

```blade
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form-bulk-action');
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const toolbar = document.getElementById('bulk-toolbar');

        function updateToolbar() {
            const checked = Array.from(checkboxes).filter(cb => cb.checked);
            if (checked.length > 0) {
                toolbar.classList.remove('hidden');
            } else {
                toolbar.classList.add('hidden');
            }
        }

        // Event listeners
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateToolbar();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateToolbar);
        });
    });
</script>
@endpush
```

---

## 9. Halaman Publik vs Admin

| Aspek | Halaman Publik (Guest) | Konsol Admin/Unit |
|-------|------------------------|-------------------|
| **Layout** | `layouts.guest` | `admin.layout` / `unit.layout` |
| **Body class** | `font-sans bg-brand-light text-ink antialiased` | `bg-surface font-body-md text-on-surface min-h-screen` |
| **Topbar** | Single bar `h-20`, fixed | Double bar (identitas + navigasi), fixed `pt-28` di main |
| **Warna** | Brand tokens (`brand-*`, `ink`, `gold-*`) | Material 3 tokens (`surface-*`, `primary`, `secondary`) |
| **Navigasi** | Pill brand (`bg-brand-100 text-brand-800`) untuk aktif | Pill mint (`bg-secondary-container`) untuk aktif |
| **Footer** | 4 kolom, kaya informasi | Minimal, info SLA |
| **Widget** | Aksesibilitas + TTS (Web Speech API) | Notifikasi dropdown |
| **Script** | Inline di bawah konten + `@stack('scripts')` | Script layout di dalam layout + `@push('scripts')` per halaman |

---

## 10. Test

- Class test di `tests/Feature/` mengikuti fitur yang diuji; perhitungan murni seperti `Sla::` boleh di `tests/Unit/`
- Nama method: `test_<fitur>_<kondisi>_<hasil>` (bahasa Indonesia)
- Gunakan `RefreshDatabase`
- Untuk halaman admin, mulai dari helper `tests/TestCase.php`: `masukSebagaiAdmin()`, bukan `actingAs(User::factory()->create())` manual
- Untuk konsol unit, pakai `masukSebagaiPicUntuk($unit)` atau `picUntuk($unit)` supaya akun benar-benar terhubung ke unit
- Helper method `form()` dipakai kalau payload form yang sama diulang di banyak test
- Factory dipakai untuk menyiapkan data, termasuk state seperti `->status(StatusPengaduan::Diproses)` dan `->admin()`
- Waktu dibekukan dengan `Carbon::setTestNow()`, reset di `tearDown()`
- Assert database state, bukan hanya response

```php
class DaftarPengaduanAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterUnitSeeder::class);
        StatistikDashboard::lupaCache();

        $this->admin = $this->masukSebagaiAdmin();
    }

    public function test_halaman_daftar_pengaduan_butuh_login(): void
    {
        auth()->logout();

        $this->get(route('admin.pengaduan.index'))->assertRedirect(route('masuk'));
    }
}
```

Test formulir publik tetap memakai helper `form()` di dalam class-nya sendiri:

```php
public function test_pengaduan_dapat_disimpan(): void
{
    $this->post(route('pengaduan.store'), $this->form())
        ->assertRedirect(route('pengaduan.sukses'));

    $this->assertDatabaseCount('pengaduan', 1);

    $pengaduan = Pengaduan::first();

    $this->assertSame(KategoriPengaduan::Fasilitas, $pengaduan->kategori);
    $this->assertStringStartsWith('ADUAN-', $pengaduan->kode_tiket);
}
```

**Perintah:**
```bash
php artisan test
vendor/bin/pint
```

---

## 11. Checklist

### Sebelum Commit
- [ ] Kode mengikuti indent 4 spasi (Pint/PSR-12)
- [ ] Komentar bahasa Indonesia, menjelaskan "kenapa" bukan "apa"
- [ ] Tidak ada query di Blade
- [ ] Class baru di `app/Support/` atau `app/Services/` ditulis `final`
- [ ] Enum dipakai untuk status, warna, ikon, label
- [ ] Config SLA tidak di-hardcode
- [ ] Kode tiket tidak di-$fillable
- [ ] Rate limiting & validasi ada
- [ ] Script halaman ada di `@push('scripts')`, bukan di tengah HTML
- [ ] Dropdown memakai `<details>`/`<summary>` dan tetap berguna tanpa JavaScript
- [ ] Badge, titik nudge, dan hitungan lain diambil dari respons server
- [ ] Test halaman admin memakai helper `masukSebagaiAdmin()` / `masukSebagaiPicUntuk()`
- [ ] Test lulus (`php artisan test --compact`)
- [ ] Pint bersih (`vendor/bin/pint --dirty --format agent`)

### Menambah Halaman Baru
- [ ] Extend layout yang benar: `admin.layout`, `unit.layout`, atau `layouts.guest`
- [ ] Set `@section('title', '...')`
- [ ] Struktur: strip konteks, filter/toolbar, kartu/tabel/form, footer tabel (jika ada)
- [ ] Warna hanya pakai token yang benar-benar ada di `@theme`
- [ ] Spasi pakai `space-*`, `margin`, `gutter`
- [ ] Tipografi konsol pakai `font-* text-*` semantik
- [ ] Ikon pakai `<x-symbol nama="..." />`
- [ ] Aksesibilitas: `aria-label`, `role="alert"/"status"`, `scope="col"`, `aria-current="page"`
- [ ] Script masuk `@push('scripts')`, bukan inline di tengah HTML
- [ ] Komentar blok: `<!-- Nama Bagian -->` ... `<!-- End of Nama Bagian -->`
- [ ] Import pakai `@use()` di baris atas
- [ ] Blank line sebelum/sesudah directive Blade

---

## 12. Larangan

| Larangan | Alasan |
|----------|--------|
| **Tambah komentar bahasa Inggris** | Konsistensi project |
| **Tambah dependensi baru tanpa diminta** | Hindari bloat |
| **Ubah skema database tanpa migrasi** | Integritas data |
| **Tambah route tanpa diminta** | Konsistensi routing |
| **Ubah angka SLA di config/pengaduan.php** | Sumber kebenaran tunggal |
| **Query langsung di Blade** | Separation of concerns |
| **Tautan palsu `href="#"` untuk fitur belum ada** | Test memeriksa hal ini; pakai `span.cursor-not-allowed` atau `button disabled` |
| **Mengembalikan `'#'` dari method PHP saat URL gagal dibentuk** | Sama dengan tautan palsu; kembalikan `null` dan biarkan template menonaktifkan tautannya |
| **Menghitung jumlah/badge notifikasi di JavaScript** | Server sudah mengirim `total_belum_dibaca`; hitungan lokal akan melenceng setelah aksi lain |
| **Menulis peran sebagai string lepas (`'admin'`, `'unit'`)** | Pakai `PeranPengguna::Admin->value` supaya nilai berubah di satu tempat |
| **Menaruh query notifikasi di layout** | Layout hanya merender data; query ada di controller atau `App\Support\*` |
| **Jadikan `kode_tiket` mass assignable** | Keamanan: kode dibuat sistem |
| **Tulis angka SLA, nama unit secara lepas** | Hanya di `config/pengaduan.php` dan `App\Support\Sla` |
| **Pakai `declare(strict_types=1)`** | Project tidak memakainya |
| **Pakai `href="javascript:void(0)"`** | Gunakan `button` atau `span` |
| **Campur gaya atribut PHP `#[Fillable]` dengan `$fillable`** | Hanya `User` yang boleh pakai atribut PHP |
| **Pakai `text-base`, `text-lg` di konsol admin** | Gunakan tipografi semantik; halaman publik yang lebih bebas tetap boleh memakai ukuran biasa |
| **Mengarang nama token warna (mis. `warning-*`)** | Token hanya yang ada di `@theme`; pakai `tertiary-*` atau `error-*` |
| **Memakai `final` di enum, model, atau controller** | Tidak sesuai pola project; `final` hanya untuk Support/Service |
| **Tulis hex/RGB langsung** | Gunakan design token |

---

## Referensi Cepat

**Class sering dipakai:**
| Kebutuhan | Class |
|-----------|-------|
| Kontainer halaman | `w-full px-margin py-space-lg` |
| Grid responsif | `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg` |
| Kartu putih | `bg-surface-container-lowest rounded-lg shadow-sm p-space-md` |
| Tombol utama | `px-space-md py-2 rounded-lg bg-primary text-on-primary font-label-lg text-label-lg hover:bg-primary/90 transition-colors flex items-center gap-1.5 shadow-sm` |
| Tombol sekunder | `px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-label-lg text-label-lg hover:bg-surface-container-high transition-colors` |
| Input form | `w-full py-2.5 px-space-md rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/40` |
| Badge aktif (pill) | `inline-flex items-center gap-1 px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container font-bold` |
| Chip status tabel | `inline-flex items-center gap-1 px-2.5 py-1 rounded-xl {{ $status->badgeAdmin() }} font-label-md text-label-md font-bold w-fit shadow-xs` |
| Tabel scroll | `overflow-x-auto w-full` + `table min-w-[900px]` sampai `min-w-[1200px]` sesuai jumlah kolom |

---

*Dokumen ini dikonsolidasi dari `STYLING_GUIDE.md`, `STYLE_GUIDE.md`, `STYLE.md`, dan analisis kodebase. Terakhir dicocokkan dengan kode: 28 Oktober 2026.*