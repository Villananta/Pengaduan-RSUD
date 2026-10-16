# Panduan Gaya Kode Project Pengaduan RSUD

Dokumen ini merangkum konvensi penulisan kode yang digunakan di project ini. Semua perubahan kode baru harus mengikuti pola ini.

---

## 1. Prinsip Umum

| Aturan | Penjelasan |
|--------|------------|
| **Fungsi & logika tidak berubah** | Hanya format, spasi, urutan baris, dan komentar yang dirapikan |
| **Bahasa Indonesia** | Nama variabel, method, komentar, `$fillable`, pesan validasi, teks UI |
| **Komentar menjelaskan "kenapa", bukan "apa"** | Jangan ulang kode; tuliskan alasan keputusan dan risiko kalau diubah |
| **Tulis seperti manusia** | Jangan memampatkan jadi satu baris; biarkan ritme natural |

---

## 2. Blade Template

### Struktur & Spasi
```blade
<!DOCTYPE html>

<html lang="id" class="scroll-smooth">

<head>
    ...
</head>

<body class="font-sans bg-brand-light text-ink antialiased">

    <!-- Header -->
    <header>...</header>
    <!-- End of Header -->

    <!-- Konten Halaman -->
    <main>...</main>
    <!-- End of Konten Halaman -->

    <!-- Footer -->
    <footer>...</footer>
    <!-- End of Footer -->

    @stack('scripts')

</body>

</html>
```

- Blank line setelah `<!DOCTYPE html>`, sebelum `<head>`, setelah `</head>`, sebelum `<body>`, sebelum `</body>`
- Komentar pembuka/penutup pasangan untuk section besar:
  ```blade
  <!-- Sidebar -->
  @include('layout.sidebar')
  <!-- End of Sidebar -->
  ```
- Komentar satu baris untuk elemen tanpa pasangan: `<!-- Scroll to Top Button -->`
- Tag pendek (1 elemen, 1 baris isi) → satu baris
- Tag panjang (banyak atribut/class) → multi-baris, indent 4 spasi

### Directive Blade
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

### Section & Stack
```blade
@section('title', 'Judul Halaman')

@section('content')
    ...
@endsection

@push('scripts')
    <script>...</script>
@endpush
```
- `@stack('scripts')` tetap diletakkan sebelum `</body>`

---

## 3. PHP Class & Method

### Docblock Kelas
```php
/**
 * Perhitungan Standar Pelayanan Minimal dalam hari kerja.
 *
 * Semua angka SLA pada halaman publik berasal dari sini, bukan dari
 * teks hardcoded, sehingga batas waktu dan tampilan selalu konsisten.
 */
final class Sla
{
    ...
}
```

### Docblock Method Publik
```php
/**
 * Nomor hari kerja yang sedang berjalan, dihitung inklusif.
 *
 * Tiket yang baru masuk pada hari kerja ini sudah berstatus hari ke-1,
 * belum hari ke-0. Angka inilah yang dipakai semua tampilan "Hari ke-N",
 * dan sisa hari kerja diturunkan dari angka yang sama supaya
 * "hari ke-N" ditambah "sisa M hari kerja" selalu sama dengan standar.
 */
public static function hariKe(CarbonInterface $mulai, ?CarbonInterface $sekarang = null): int
{
    return self::hariKerjaLewat($mulai, $sekarang) + 1;
}
```

### Method Sederhana (Nama Sudah Jelas)
```php
/** Batas respons awal dalam jam. */
public static function responsAwalJam(): int
{
    return (int) config('pengaduan.sla.respons_awal_jam', 24);
}
```

### Pisah Bagian dengan Blank Line + Label
```php
// Validasi Input
$validated = $request->validate(...);

// Proses Simpan
$pengaduan = Pengaduan::buat(...);

// Notifikasi
$admins = User::where('peran', 'admin')->get();
```

---

## 4. Enum (PHP 8.1+)

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
    public function badge(): string
    {
        return match ($this) {
            self::Diterima => 'bg-brand-100 text-brand-800',
            self::Diproses => 'bg-brand-700 text-white',
            self::Revisi => 'bg-alert-light text-alert',
            self::Selesai => 'bg-brand-800 text-white',
        };
    }
}
```
- Semua label, warna, ikon, logika transisi ada di enum
- Gunakan `match` expression
- Method bernama jelas: `label()`, `badge()`, `ikon()`, `tahap()`, `bisaBerpindahKe()`

---

## 5. Support Class (App\Support\*)

- Semua query dan perhitungan di sini, **bukan di Blade**
- Class `final` dengan property `public readonly` untuk data transfer
- Method static untuk kalkulasi murni (Sla, Statistik)
- Constructor dengan docblock parameter array shape

```php
final class Dashboard
{
    public function __construct(
        public readonly array $perTahap,
        public readonly ?float $kepatuhan,
        public readonly LengthAwarePaginator $perluTindakan,
        ...
    ) {}
}
```

---

## 6. Controller

- Gunakan FormRequest atau inline validation dengan class Support
- Redirect dengan `route()` helper
- Rate limiting manual via `RateLimiter` facade
- Session untuk state verifikasi (bukan URL)

```php
public function store(Request $request): RedirectResponse
{
    $validated = $request->validate(
        PengaduanMasuk::aturan(),
        PengaduanMasuk::pesanValidasi(),
    );

    $pengaduan = PengaduanMasuk::simpan($validated, $request->file('lampiran', []) ?? []);

    // Pesan pembuka otomatis...
    $pengaduan->pesan()->create([...]);

    StatistikDashboard::lupaCache();

    $request->session()->put(self::SESI_TERVERIFIKASI, ['kode' => $pengaduan->kode_tiket]);

    return redirect()->route('pengaduan.sukses');
}
```

---

## 7. Model Eloquent

### $fillable & $casts
```php
protected $fillable = [
    'kategori',
    'nama_lengkap',
    'nrm',
    'no_wa',
    'email',
    // ...
];

protected $casts = [
    'waktu_kejadian' => 'datetime',
    'selesai_at' => 'datetime',
    'kasus_berat' => 'boolean',
    'kategori' => KategoriPengaduan::class,
    'status' => StatusPengaduan::class,
];
```

### Method Factory (Kode Tiket Dibuat Sistem)
```php
/**
 * Buat pengaduan baru dengan kode tiket yang dibuat di dalam sistem.
 *
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

### Scope Query
```php
public function scopeSudahDiteruskanHumas(Builder $query): Builder
{
    return $query->where('status', '!=', StatusPengaduan::Diterima->value);
}
```

### Accessor/Method Domain
```php
public function namaUnit(): string
{
    return $this->masterUnit?->namaLengkap() ?? $this->unit;
}

public function zonaSla(): ZonaSla
{
    return Sla::zona($this->created_at, $this->selesai_at, $this->kasus_berat);
}
```

---

## 8. Config (config/pengaduan.php)

- Semua angka SLA, daftar unit, peta unit, prosedur, SPM di sini
- Komentar menjelaskan **kenapa** nilai tersebut dipakai
- Array asosiatif dengan key deskriptif

```php
'sla' => [
    'hari_kerja' => 12,
    'respons_awal_jam' => 24,
    'peringatan_hari_kerja' => 2,
    'investigasi_hari_kerja' => 5,
    'kasus_berat_hari_kerja' => 8,
],

'units' => [
    'Instalasi Rawat Jalan (Poliklinik)',
    'Instalasi Rawat Inap',
    ...
],

'peta_unit' => [
    'Instalasi Rawat Jalan (Poliklinik)' => 'IRJ-05',
    ...
],
```

---

## 9. Tailwind CSS & Design Tokens (resources/css/app.css)

### Palet Warna (CSS Custom Properties)
```css
@theme {
    --color-brand-800: #0f7a3c;   /* Hijau merek utama */
    --color-brand-150: #d7f5e3;   /* Mint untuk kartu/panel */
    --color-brand-light: #f7fcf9; /* Background halaman publik */

    /* Material 3 Surface tokens untuk admin console */
    --color-surface: #f7fbf9;
    --color-surface-container-lowest: #ffffff;
    --color-surface-container: #ecf4ef;
    ...

    --color-primary: #0f7a3c;
    --color-secondary: #146c3e;
    --color-tertiary: #8a6a12;    /* Emas untuk badge SLA */
    --color-error: #ba1a1a;

    /* Spacing tokens */
    --spacing-space-md: 1rem;
    --spacing-margin: 2rem;
    --spacing-gutter: 1.5rem;

    /* Typography tokens */
    --font-display-lg: 'Plus Jakarta Sans', ...;
    --text-body-md: 0.875rem;
    --text-label-md: 0.75rem;
}
```

### Class Utility Khusus
```css
.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-feature-settings: 'liga';
    ...
}

[x-cloak] { display: none !important; }

html.kontras-tinggi { filter: contrast(1.3); }
```

### Penggunaan di Blade
```blade
<!-- Warna brand -->
<button class="bg-brand-800 text-white hover:bg-brand-700">Kirim</button>

<!-- Material 3 surface -->
<div class="bg-surface-container-lowest p-space-md shadow-sm">

<!-- Badge status dari enum -->
<span class="{{ $pengaduan->status->badge() }}">{{ $pengaduan->status->label() }}</span>

<!-- Ikon Material Symbols -->
<x-symbol nama="notifications" class="text-[24px]" />
```

---

## 10. Komponen Blade (resources/views/components)

```blade
<!-- components/brand-logo.blade.php -->
<svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ $class ?? '' }}">
    <rect width="32" height="32" rx="6" fill="#0F7A3C"/>
    <path d="M8 16L14 22L24 10" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

Penggunaan:
```blade
<x-brand-logo class="h-9 w-9 object-contain rounded-sm bg-surface-container-low p-0.5" />
```

---

## 11. JavaScript (Inline di Blade @push('scripts'))

```blade
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
</script>
@endpush
```

- Vanilla JS, tidak pakai framework
- `const` untuk referensi DOM
- Function declaration (bukan arrow) untuk readability
- Event listener pasang setelah cek element exists

---

## 12. Test (tests/Feature/)

```php
class PengaduanTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $ubah = []): array
    {
        return array_merge([
            'kategori' => 'fasilitas',
            'nama_lengkap' => 'Budi Santoso',
            'nrm' => '12-34-56-78',
            ...
        ], $ubah);
    }

    public function test_pengaduan_dapat_disimpan(): void
    {
        $this->post(route('pengaduan.store'), $this->form())
            ->assertRedirect();

        $this->assertDatabaseCount('pengaduan', 1);
        $pengaduan = Pengaduan::first();
        $this->assertSame(KategoriPengaduan::Fasilitas, $pengaduan->kategori);
        $this->assertStringStartsWith('ADUAN-', $pengaduan->kode_tiket);
    }
}
```

- Helper method `form()` untuk data valid default
- Nama test: `test_<fitur>_<kondisi>_<hasil>`
- Assert database state, bukan hanya response
- Gunakan `Carbon::setTestNow()` untuk test waktu

---

## 13. Larangan (Yang Tidak Boleh Dilakukan)

| Larangan | Alasan |
|----------|--------|
| Tambah komentar bahasa Indonesia | Konsistensi project |
| Tambah dependensi baru tanpa diminta | Hindari bloat |
| Ubah skema database tanpa migrasi | Integritas data |
| Tambah route tanpa diminta | Konsistensi routing |
| Ubah angka SLA di config/pengaduan.php | Sumber kebenaran tunggal |
| Query langsung di Blade | Separation of concerns |
| Tautan palsu `href="#"` untuk fitur belum ada | Test memeriksa hal ini; pakai `span.cursor-not-allowed` |
| Jadikan `kode_tiket` mass assignable | Keamanan: kode dibuat sistem |

---

## 14. Checklist Sebelum Commit

- [ ] Kode mengikuti indent 4 spasi (Pint/PSR-12)
- [ ] Komentar bahasa Indonesia, menjelaskan "kenapa"
- [ ] Tidak ada query di Blade
- [ ] Enum dipakai untuk status, warna, ikon, label
- [ ] Config SLA tidak di-hardcode
- [ ] Kode tiket tidak di-$fillable
- [ ] Rate limiting & validasi ada
- [ ] Test lulus (`php artisan test`)
- [ ] Pint bersih (`./vendor/bin/pint`)

---

*Dokumen ini dihasilkan dari analisis codebase pada tanggal 16 Oktober 2026.*