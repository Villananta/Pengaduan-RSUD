<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# Gaya Kode yang Diminta Pengguna

Aturan ini berlaku untuk semua file yang ditulis atau disunting. Gaya kode harus
terasa seperti ditulis tangan manusia, bukan hasil generator. Fungsi, logika,
dan tampilan tidak boleh berubah — yang diubah hanya cara kode itu ditulis,
ditata, dan diberi komentar.

## Prinsip Utama

1. **Fungsi, logika, dan style tidak boleh berubah.** Kalau diminta "merapikan",
   yang dirapikan hanya bentuk, spasi, urutan baris, dan komentar. Jangan
   mengganti class CSS, nama route, nama method, struktur query, atau isi teks.
2. **Tulis seperti manusia.** Jangan memampatkan semua kode jadi baris sependek
   mungkin, dan jangan memaksakan format yang exceedingly rapi. Manusia juga
   menulis dengan ritme yang tidak rata.
3. **Pakai Bahasa Indonesia** untuk nama variabel, method, komentar, isi
   `$fillable`, pesan validasi, dan teks di layar. Ini sudah jadi konvensi
   project ini.
4. **Jelaskan alasan, bukan instruksi.** Komentar tidak boleh mengulang kode.
   Tuliskan kenapa sebuah keputusan diambil dan apa yang akan rusak kalau
   diubah. Kalau memang tidak ada alasan, lebih baik tidak dikomentari.

## Aturan Blade

- Beri spasi kosong (satu baris kosong) setelah `<!DOCTYPE html>`, sebelum
  `<head>`, setelah `</head>`, sebelum `<body>`, dan sebelum `</body>`.
- Setiap bagian besar diberi komentar pembuka dan penutup berpasangan, ditulis
  dalam bahasa Indonesia dengan huruf kapital di awal:

  ```blade
      <!-- Sidebar -->
      @include('layout.sidebar')
      <!-- End of Sidebar -->
  ```

  Untuk halaman tanpa pasangan, pakai penanda satu baris seperti
  `<!-- Scroll to Top Button -->` atau `<!-- Logout Modal-->`.
- Gunakan juga penanda rentang untuk konten halaman, misalnya
  `<!-- Begin Page Content -->` dan `<!-- End of Topbar -->`.
- Tag yang isinya pendek (satu elemen, satu baris isi) ditulis satu baris.
  Tag yang punya banyak atribut atau class panjang ditulis multi baris dengan
  satu atribut per baris, menjorok 4 spasi dari tag induknya.
- Blank line sebelum `@php`, `@foreach`, `@if`, dan setelah blok `@endphp`,
  `@endforeach`, `@endif` supaya blok directive mudah dilacak mata.
- `@section`/`@yield` dan `@stack`/`@push` diberi komentar singkat kalau
  fungsinya tidak langsung jelas dari nama.
- Definisi `@stack('scripts')` tetap diletakkan sebelum penutup `</body>`.

## Aturan PHP

- Setiap kelas dibuka docblock satu atau beberapa baris yang menjelaskan
  tanggung jawabnya. Method publik yang perilakunya tidak langsung jelas
  memakai docblock penuh; method yang namanya sudah menjelaskan cukup pakai
  satu baris `/** ... */`.
- Pisahkan bagian yang berbeda dengan satu baris kosong, dan kalau memang
  perlu, beri nama bagian dengan `// Nama Bagian` di atasnya.
- Jangan memakai `declare(strict_types=1)`. Project ini tidak memakainya, jadi
  jangan ditambahkan.
- Ikuti Pint/PSR-12 untuk indent 4 spasi, dan biarkan detail formatting
  dianggap opsional: bila formatting manual terasa lebih enak dibaca dan Pint
  tidak mengubahnya secara bermakna, formatting manual boleh dipertahankan.

## Yang Dilarang

- Menambah komentar dalam bahasa Inggris.
- Menambah dependensi baru, mengubah skema database, atau menambah route tanpa
  diminta.
- Mengubah angka SLA, daftar unit, atau teks SPM yang sudah berada di
  `config/pengaduan.php` dan `App\Support\Sla`.
- Menaruh query langsung di dalam template Blade. Semua query dan perhitungan
  tetap di `App\Support\*` atau di controller.
- Membuat tautan palsu `href="#"` untuk fitur yang belum ada. Gunakan
  `span` dengan `cursor-not-allowed` atau tombol yang dinonaktifkan, karena ada
  test yang memeriksa hal ini.
- Mengubah `kode_tiket` agar bisa diisi massal. Kode tiket dibuat di dalam
  sistem.

