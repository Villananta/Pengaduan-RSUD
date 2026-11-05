# Style Guide — Pengaduan RSUD (Ringkas)

## PHP

```php
<?php
namespace App\Models;
use App\Enums\StatusPengaduan;
use Illuminate\Database\Eloquent\Model;

class Pengaduan extends Model
{
    use HasFactory;

    /** Kenapa konstanta ini ada: jadi penanda baris riwayat kasus berat. */
    public const CATATAN_KASUS_BERAT_AKTIF = 'Pengaduan ditandai sebagai kasus berat oleh admin humas.';

    protected $fillable = ['kategori', 'nama_lengkap', 'nrm', 'email', 'status', 'kasus_berat', 'akses_token'];
    protected $casts  = ['waktu_kejadian' => 'datetime', 'kasus_berat' => 'boolean', 'status' => StatusPengaduan::class];

    public static function buat(array $data, string $kodeTiket, ?StatusPengaduan $status = null): self { ... }

    public function pindahTahap(StatusPengaduan $tujuan, ?string $catatan = null, ?int $adminId = null): void
    {
        DB::transaction(function () use ($tujuan, $catatan, $adminId) {
            $sebelumnya = $this->status;
            $this->status = $tujuan;
            $this->selesai_at = $tujuan->selesai() ? now() : null;
            $this->save();
            $this->riwayatStatus()->create(['dari' => $sebelumnya, 'ke' => $tujuan, 'catatan' => $catatan, 'admin_id' => $adminId]);
        });
    }

    public function scopeSudahDiteruskanHumas(Builder $q): Builder { return $q->where('status', '!=', StatusPengaduan::Diterima->value); }
}
```

**Aturan:**
- `snake_case` Indonesia untuk variabel: `$kodeTiket`, `$jumlahNotifAdmin`
- Docblock jelaskan *kenapa*, bukan *apa*
- `DB::transaction` untuk operasi atomik
- Rate limit per tiket (pakai `kode_tiket`), bukan per IP
- Token akses: hash SHA-256 di DB, cookie HttpOnly 30 hari, rotasi saat verifikasi email
- Enum key `TitleCase`, value `snake_case`

---

## Blade

```blade
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Portal Pengaduan Humas | RSUD Dr. Soetomo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-body-md text-on-surface min-h-screen">

    <!-- Topbar -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-[0_2px_14px_rgba(26,26,46,0.08)]">
        <div class="bg-white h-16 w-full px-margin flex items-center justify-between">
            <!-- Baris Identitas -->
        </div>
        <!-- End of Baris Identitas -->
        <nav class="bg-white border-b border-outline-variant h-12 w-full px-margin flex items-center">...</nav>
    </header>
    <!-- End of Topbar -->

    <main class="w-full pt-28 bg-surface flex-1">@yield('content')</main>

    <footer class="w-full bg-white border-t border-outline-variant mt-space-xl py-space-lg">...</footer>

    <script>...</script>
    @stack('scripts')
</body>
</html>
```

**Aturan:**
- Blank line: setelah `<!DOCTYPE html>`, sebelum `<head>`, setelah `</head>`, sebelum `<body>`, sebelum `</body>`
- Komentar berpasangan: `<!-- Topbar -->` ... `<!-- End of Topbar -->`
- Class reusable di `@php` block atas form: `$input`, `$label`, `$errorClass`, `$stepBadge`
- Pakai `<x-symbol nama="..." />` untuk ikon Material Symbols
- **No** `href="#"` untuk fitur belum ada → pakai `span cursor-not-allowed` atau `button disabled`

---

## CSS / Tailwind (app.css)

```css
@import 'tailwindcss';

@theme {
    /* Brand (Hijau Hutan) */
    --color-brand-800: #0f7a3c;   /* tombol, link utama */
    --color-brand-700: #158a4a;   /* hover */
    --color-brand-600: #146c3e;   /* teks kecil */
    --color-brand-150: #d7f5e3;   /* kartu, panel (mint) */
    --color-brand-light: #f7fcf9; /* bg halaman */

    /* Emas (penghargaan/peringatan) */
    --color-gold-500: #d4af37;
    --color-tertiary: #8a6a12;        /* badge peringatan */
    --color-tertiary-container: #f5d876;

    /* Surface (Material 3) */
    --color-surface: #f7fbf9;
    --color-on-surface: #1a1a2e;
    --color-surface-container-low: #f2f8f5;
    --color-outline-variant: #d3ddd6;

    /* Primary/Secondary */
    --color-primary: #0f7a3c;
    --color-primary-container: #146c3e;
    --color-secondary-container: #d7f5e3;

    /* Status */
    --color-error: #ba1a1a;
    --color-warning: #d97706;
    --color-info: #003ea8;

    /* Spacing */
    --spacing-space-xs: 0.25rem;  --spacing-space-md: 1rem;   --spacing-margin: 2rem;
    --spacing-space-sm: 0.5rem;   --spacing-space-lg: 1.5rem; --spacing-gutter: 1.5rem;

    /* Tipografi (Plus Jakarta Sans) */
    --font-title-md: 'Plus Jakarta Sans', ...;  --text-title-md: 1rem;      --text-title-md--line-height: 1.375rem;
    --font-body-md:  'Plus Jakarta Sans', ...;  --text-body-md: 0.875rem;   --text-body-md--line-height: 1.25rem;
    --font-label-md: 'Plus Jakarta Sans', ...;  --text-label-md: 0.75rem;   --text-label-md--line-height: 1rem; --text-label-md--letter-spacing: 0.02em;
}

@layer base {
    .material-symbols-outlined {
        font-family: 'Material Symbols Outlined';
        font-feature-settings: 'liga';  /* wajib untuk ligature */
        line-height: 1;
    }
}
```

**Pemakaian:**
- Warna: `bg-brand-800`, `text-on-surface-variant`, `border-outline-variant`, `bg-tertiary-container text-on-tertiary-container`
- Spacing: `px-margin`, `gap-space-md`, `mt-space-xl`, `py-space-lg`
- Tipografi: `font-title-md text-title-md`, `font-body-sm text-body-sm`, `font-label-sm text-label-sm`
- Radius: `rounded-xl` (0.5rem), `rounded-full`, `rounded-sm`
- Ikon: `<x-symbol nama="notifications" class="text-[24px]" />`

---

## Config SLA (config/pengaduan.php)

```php
'sla' => [
    'hari_kerja' => 12,
    'respons_awal_jam' => 24,
    'peringatan_hari_kerja' => 2,
    'investigasi_hari_kerja' => 5,
    'kasus_berat_hari_kerja' => 8,  // tambahan untuk kasus berat
],
'prosedur' => [
    ['batas' => 2, 'judul' => 'Verifikasi Administratif'],
    ['batas' => 5, 'judul' => 'Investigasi Internal'],
    ['batas' => 9, 'judul' => 'Klarifikasi'],
    ['batas' => 12, 'judul' => 'Jawaban Resmi'],
],
```

**App\Support\Sla** ambil semua angka dari config — **no hardcode**.

---

## Larangan (Jangan)

1. Komentar bahasa Inggris
2. Tambah dependensi tanpa approval
3. Ubah skema DB tanpa migrasi
4. Tambah route tanpa diminta
5. Ubah angka SLA / daftar unit / teks SPM di config
6. Query di Blade
7. `href="#"` untuk fitur belum ada
8. `kode_tiket` di `$fillable` (mass assignment)
9. Ganti nama token warna (`brand-800`, dll)
10. `declare(strict_types=1)`

---

## Verifikasi

```bash
vendor/bin/pint --dirty --format agent   # format PHP
php artisan test --compact --filter=...  # test
php artisan route:list --except-vendor   # cek route
php artisan config:show pengaduan.sla    # cek nilai SLA
```