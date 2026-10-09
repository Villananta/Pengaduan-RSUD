# Panduan Gaya Kode — Sistem Pengaduan RSUD

Dokumen ini merangkum gaya kode yang dipakai di project ini. Tujuannya supaya
kode yang ditambah atau diubah terasa ditulis satu tangan dengan kode yang
sudah ada. Aturan di bawah disusun dari kebiasaan nyata di `app/`, `routes/`,
`config/`, `resources/views/`, dan `tests/`, bukan dari teori umum.

## Prinsip Umum

1. **Fungsi dan perilaku tidak berubah saat merapikan.** Kalau diminta
   merapikan, yang disentuh hanya bentuk, spasi, urutan baris, dan komentar.
   Jangan mengganti class CSS, nama route, nama method, struktur query, atau
   isi teks yang tampil.
2. **Tulis seperti manusia.** Baris tidak harus seragam. Beri jeda di antara
   blok yang berbeda, dan biarkan satu keputusan terasa punya alasan. Jangan
   memampatkan semua kode jadi baris sependek mungkin.
3. **Bahasa Indonesia** untuk nama variabel, method, komentar, isi `$fillable`,
   pesan validasi, dan teks di layar. Nama teknis dari framework (misalnya
   `firstOrFail`, `where`, `paginate`) tetap apa adanya.
4. **Komentar menjelaskan alasan, bukan mengulang kode.** Tuliskan kenapa
   keputusan itu diambil dan apa yang rusak kalau diubah. Kalau tidak ada
   alasan, lebih baik tidak ada komentar.

## Penamaan

- Class dan enum: `StudlyCase` (`Pengaduan`, `StatusInvestigasi`, `Sla`).
- Method dan variabel: `camelCase` (`dariRequest`, `hariKerjaLewat`,
  `$jumlahTahap`).
- Kolom database dan atribut model: `snake_case` (`kode_tiket`,
  `selesai_at`, `master_unit_id`).
- Konstanta: `UPPER_SNAKE_CASE` (`BATAS_KONEKSI`, `UKURAN_HALAMAN`,
  `SESI_TERVERIFIKASI`, `KANAL_PELAPOR`).
- Nama tabel dinyatakan eksplisit lewat `protected $table` bila tidak jamak
  bahasa Inggris (`pengaduan`, `master_units`).
- Helper query memakai awalan `scope` (`scopeAktif`, `scopeTerhubung`,
  `scopeBelumDitugaskan`).
- Warna dan kelas CSS di tempatkan di enum/support, bukan di template.

## Docblock dan Komentar

- Setiap class dibuka docblock yang menjelaskan tanggung jawabnya, bukan
  mengulang namanya. Docblock kelas biasanya satu paragraf, dan menambahkan
  paragraf kedua hanya kalau ada nuansa penting (misalnya jebakan yang pernah
  terjadi).
- Method publik dengan perilaku yang tidak langsung jelas memakai docblock
  penuh. Method yang namanya sudah menjelaskan cukup satu baris:

  ```php
  /** Batas respons awal dalam jam. */
  public static function responsAwalJam(): int
  ```

- Docblock berisi tag `@param` dan `@return` kalau tipe array sulit dibaca
  dari signature, lengkap dengan deskripsi singkat:

  ```php
  /**
   * @param  array<string, int>  $jumlahTahap  Indeks 'semua' dan nilai enum tahap.
   * @return array<string, mixed>
   */
  ```

- Komentar di dalam method memakai `//` dan ditulis sebagai kalimat lengkap
  berbahasa Indonesia. Panjangkan seperlunya kalau ada keputusan yang mudah
  salah dimengerti orang berikutnya.
- Komentar tidak boleh menjelaskan hal yang sudah terbaca dari kode. Kalau
  tidak ada alasan yang bisa ditulis, kosongkan.
- Komentar berbahasa Inggris tidak dipakai di file aplikasi.

## Konvensi PHP

- Indent 4 spasi, mengikuti Pint/PSR-12. Detail formatting manual boleh
  dipertahankan selama Pint tidak mengubahnya secara bermakna.
- `declare(strict_types=1)` **tidak** dipakai di project ini. Jangan
  ditambahkan.
- Pisahkan bagian yang berbeda dengan satu baris kosong. Kalau perlu, beri
  penanda `// Nama Bagian` tepat di atas bagian itu:

  ```php
  // Kolom chat antara pelapor dan admin humas.
  public function pesan(): HasMany
  ```

- Class di `app/Support/` dan enum bantu yang tidak dimaksudkan untuk
  diwariskan ditulis `final`. Model Eloquent tidak `final`.
- Kelas khusus data (di `app/Support/`) memakai constructor dengan property
  `readonly` dan `promoted`, serta **named argument** saat dibuat:

  ```php
  public function __construct(
      public readonly LengthAwarePaginator $daftar,
      public readonly array $jumlahTahap,
  ) {}
  ```

- Method yang menyusun objek dari input luar memakai static factory bernama
  bahasa Indonesia (`dariRequest`, `dariKode`, `dariPengaduan`).
- Banyak keputusan bercabang ditulis dengan `match`. `match (true)` dipakai
  kalau kondisinya berupa gabungan boolean:

  ```php
  $posisi = match (true) {
      $selesai => 'Tuntas '.$lamaSelesai.' hari kerja',
      $zona === ZonaSla::Terlambat => 'Hari ke-'.$hariKe.' (Lewat SLA)',
      default => 'Hari ke-'.$hariKe.' (Aman)',
  };
  ```

- Closure yang menerima dan mengembalikan `Builder` menuliskan tipe secara
  eksplisit, dan diperiksa lebih dulu dengan `!`:

  ```php
  if (! $tiket) {
      return null;
  }
  ```

- Angka ajaib (angka, label, warna, batas SLA) disimpan di
  `config/pengaduan.php`, enum, atau konstanta class. Jangan menulis angka
  yang sama di dua tempat.

## Peran Tiap Lapisan

- **Controller** (`app/Http/Controllers/`) hanya memvalidasi request,
  memanggil `App\Support\*`, dan mengembalikan view/redirect. Query database
  tidak ditulis di controller.
- **`App\Support\*`** menahan seluruh query, perhitungan, penyusunan kalimat,
  dan pemilihan kelas warna. Template hanya menerima data yang sudah jadi.
- **Model** (`app/Models/`) menyimpan relasi, scope, cast, dan perilaku yang
  erat dengan satu baris data. Perhitungan lintas tiket tetap di `App\Support`.
- **Enum** (`app/Enums/`) menyimpan label, badge, warna, dan pemetaan tahap.
  Semua nilai yang mungkin ditulis di sini, bukan di template.
- **Blade** tidak pernah memanggil model atau query. Data selalu dikirim
  controller/support sebagai array atau objek yang sudah dinamai.

## Aturan Blade

- Satu baris kosong setelah `<!DOCTYPE html>`, sebelum `<head>`, setelah
  `</head>`, sebelum `<body>`, dan sebelum `</body>`.
- Setiap bagian besar diberi komentar pembuka dan penutup berpasangan, huruf
  kapital di awal, dalam bahasa Indonesia:

  ```blade
  <!-- Sidebar -->
  @include('layout.sidebar')
  <!-- End of Sidebar -->
  ```

  Bagian tanpa pasangan memakai penanda satu baris, misalnya
  `<!-- Scroll to Top Button -->` atau `<!-- Logout Modal-->`. Konten halaman
  boleh memakai penanda rentang seperti `<!-- Begin Page Content -->`.
- Komentar Blade di dalam template memakai `{{-- ... --}}`. Komentar HTML
  `<!-- ... -->` dipakai untuk penanda bagian yang tetap terlihat di output.
- Tag yang isinya pendek ditulis satu baris. Tag dengan banyak atribut atau
  class panjang ditulis multi baris, satu atribut per baris, menjorok 4 spasi.
- Beri satu baris kosong sebelum `@php`, `@foreach`, `@if`, dan setelah blok
  penutup `@endphp`, `@endforeach`, `@endif` supaya blok directive mudah
  dilacak.
- `@section`/`@yield` dan `@stack`/`@push` diberi komentar singkat kalau
  fungsinya tidak langsung jelas dari namanya.
- `@stack('scripts')` tetap diletakkan sebelum penutup `</body>`.
- Class CSS panjang ditulis lengkap di template. Jangan menambah helper baru
  hanya demi memendekkan daftar class.

## Test

- Nama class test mengikuti fitur yang diuji (`SlaTest`,
  `DaftarPengaduanAdminTest`, `DisposisiUnitTest`), diletakkan di
  `tests/Feature/`.
- Nama method memakai awalan `test_` dan kalimat bahasa Indonesia yang
  menjelaskan perilaku, bukan nama method yang dipanggil:

  ```php
  public function test_hari_kerja_masuk_dihitung_sebagai_hari_pertama(): void
  ```

- Satu method menguji satu perilaku. Komentar `//` di dalam test menjelaskan
  kenapa kasus itu penting, terutama kalau pernah jadi bug.
- Waktu selalu dibekukan dengan `Carbon::setTestNow()`, dan direset di
  `tearDown()`.
- Factory dipakai untuk menyiapkan data. Jangan menulis insert manual kalau
  factory-nya sudah ada.

## Formatting dan Perintah

- Format otomatis memakai Laravel Pint (dev dependency). Jalankan sebelum
  menganggap pekerjaan selesai:

  ```sh
  vendor/bin/pint
  ```

- Test dijalankan dengan:

  ```sh
  php artisan test
  ```

- Konfigurasi yang tidak boleh diubah tanpa diminta ada di
  `config/pengaduan.php` dan `App\Support\Sla` (angka SLA, daftar unit, teks
  SPM, matriks investigasi).

## Yang Dilarang

- Menambah komentar berbahasa Inggris di file aplikasi.
- Menambah dependensi baru, mengubah skema database, atau menambah route tanpa
  diminta.
- Menaruh query langsung di template Blade; semua query tetap di
  `App\Support\*` atau controller.
- Membuat tautan palsu `href="#"` untuk fitur yang belum ada. Gunakan `span`
  dengan `cursor-not-allowed` atau tombol nonaktif, karena ada test yang
  memeriksa hal ini.
- Mengubah `kode_tiket` agar bisa diisi massal (`$fillable`). Kode tiket
  dibuat di dalam sistem.
- Menulis angka SLA, nama unit, atau batas waktu secara lepas di luar
  `config/pengaduan.php` dan `App\Support\Sla`.
