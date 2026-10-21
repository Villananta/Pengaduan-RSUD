# Sistem Pengaduan RSUD Dr. Soetomo

Aplikasi manajemen pengaduan pelayanan rumah sakit berbasis Laravel 13.x untuk RSUD Dr. Soetomo Surabaya.

---

## 🚀 Quick Start

### Prasyarat
- PHP 8.4+
- Composer 2.x
- Node.js 20+ & npm
- SQLite (dev) / MySQL 8.0+ / PostgreSQL 14+ (production)

### Instalasi

```bash
# 1. Clone & install dependencies
composer install
npm install

# 2. Environment setup
cp .env.example .env
php artisan key:generate

# 3. Database (SQLite untuk development)
touch database/database.sqlite
php artisan migrate --seed

# 4. Build assets
npm run build

# 5. Jalankan server development
composer run dev
# Atau terpisah:
# php artisan serve
# npm run dev
```

### Akses Aplikasi

| Halaman | URL | Catatan |
|---------|-----|---------|
| Publik (Buat Aduan) | `http://localhost:8000/buat-aduan` | Pelapor umum |
| Lacak Tiket | `http://localhost:8000/lacak-tiket` | Pelopor cek status |
| Masuk Petugas | `http://localhost:8000/petugas/masuk` | Konfigurasi `RUTE_MASUK` di .env |
| Admin Konsol | `http://localhost:8000/admin` | Perlu login admin |
| Unit Konsol | `http://localhost:8000/unit` | Perlu login PIC unit |

---

## 🧪 Testing

```bash
# Jalankan semua test
php artisan test

# Jalankan test spesifik
php artisan test --filter=PengaduanTest
php artisan test --filter=SlaTest
php artisan test --filter=MiddlewareTest

# Dengan output compact
php artisan test --compact

# Coverage (memerlukan Xdebug/PCOV)
php artisan test --coverage
```

### Test Database
- Menggunakan `RefreshDatabase` trait (SQLite in-memory)
- Factory & Seeder: `database/factories/`, `database/seeders/`
- Helper test: `tests/TestCase.php` → `masukSebagaiAdmin()`, `masukSebagaiPic()`, `masukSebagaiPicUntuk()`

---

## 📦 Deployment (Production)

### Environment Variables Penting

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pengaduan.rsud-soetomo.go.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pengaduan_rsud
DB_USERNAME=...
DB_PASSWORD=...

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="pengaduan@rsud-soetomo.go.id"
MAIL_FROM_NAME="Pengaduan RSUD Dr. Soetomo"

DISK_LAMPIRAN=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=pengaduan-lampiran

RUTE_MASUK=petugas/masuk-aman-xyz
```

### Deploy Checklist

- [ ] `composer install --optimize-autoloader --no-dev`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan migrate --force`
- [ ] `npm run build`
- [ ] `php artisan queue:work --daemon` (supervisor)
- [ ] `php artisan schedule:work` (cron: `* * * * * php artisan schedule:run`)

### Laravel Cloud (Recommended)

```bash
# Install cloud CLI
composer global require laravel/cloud-cli

# Login & deploy
cloud login
cloud deploy
```

---

## 🏗️ Arsitektur Kode

### Layer Utama

```
app/
├── Enums/           # Domain constants (Status, Kategori, Zona SLA, dll)
├── Http/
│   ├── Controllers/ # Admin, Unit, Publik controllers
│   └── Middleware/  # PastikanAdmin, PastikanPic, PastikanUnitSendiri, PastikanUnitAktif, ThrottleKeys
├── Models/          # Eloquent models + factory methods
├── Notifications/   # Notification classes (ShouldQueue)
├── Services/        # Business logic (Disposisi, Tahap, KasusBerat, Komunikasi)
├── Support/         # Data Transfer Objects (DTO) - query & kalkulasi
└── Providers/       # AppServiceProvider
```

### Prinsip Desain

| Prinsip | Implementasi |
|---------|--------------|
| **Single Source of Truth** | `config/pengaduan.php` — semua SLA, unit, prosedur, SPM |
| **Enum-Driven** | Semua status, warna, ikon, transisi di enum dengan helper method |
| **Support Classes as DTO** | `final class` + `public readonly` — template hanya receive data |
| **Layered Middleware** | `auth` → `PastikanAdmin/Pic` → `PastikanUnitSendiri` → `PastikanUnitAktif` |
| **No Query di Blade** | Semua query di `App\Support\*` |
| **Kode Tiket Secure** | Tidak di URL, verifikasi POST + session, token HttpOnly cookie 30 hari |

---

## 🎨 UI & Styling

### Design Tokens (Tailwind CSS v4)
Semua warna, spacing, typography di `resources/css/app.css` → `@theme`

| Kategori | Token Contoh |
|----------|--------------|
| Brand (Publik) | `bg-brand-800`, `bg-brand-light`, `text-brand-800` |
| Material 3 Surface (Admin) | `bg-surface`, `bg-surface-container`, `text-on-surface` |
| Primary/Secondary | `bg-primary`, `bg-secondary-container` |
| Spacing | `p-space-md` (1rem), `px-margin` (2rem), `gap-space-lg` (1.5rem) |
| Typography | `font-body-md text-body-md`, `font-label-sm text-label-sm` |

### Komponen Blade
- `x-symbol` — Material Symbols Outlined (`<x-symbol nama="home" />`)
- `x-brand-logo` — Logo RSUD
- `x-stepper-tahap` — Stepper 4 tahap pengaduan
- `x-tiket-identitas` — Kartu identitas tiket
- `x-bulk-action-scripts` — JS bulk action toolbar

### Aksesibilitas
- `aria-label`, `aria-current`, `role="alert"/"status"`, `scope="col"`
- Widget aksesibilitas publik: perbesar/kecilkan teks, kontras tinggi, TTS (Web Speech API)
- `scroll-smooth` di `<html>`

---

## 🔐 Keamanan

| Layer | Implementasi |
|-------|--------------|
| Rate Limiting | `ThrottleKeys` class — prefix unik per endpoint |
| Middleware | Layered: auth → role → unit ownership → unit aktif |
| Kode Tiket | Tidak di URL, verifikasi POST + session, token HttpOnly 30 hari |
| File Upload | Validasi mimes/ukuran, disk terpisah (`DISK_LAMPIRAN`) |
| OTP Email | 6 digit, 15 menit expiry, `hash_equals` timing-safe |
| CSRF | Semua form POST pakai `@csrf` |

---

## ⚙️ Konfigurasi Utama

### `config/pengaduan.php`
Sumber kebenaran tunggal:
- `sla` — hari kerja, respons awal, investigasi, kasus berat
- `units` — daftar unit pilihan pelapor
- `peta_unit` — mapping label → kode MASTER_UNITS
- `prosedur` — 4 tahap penanganan
- `spm` — 4 tahap Standar Pelayanan Minimal
- `siklus_investigasi` — stepper sidebar admin
- `disk_lampiran` — storage disk (default: public, production: s3)
- `rute_masuk` — URL halaman login petugas

### SLA Calculation (`app/Support/Sla.php`)
- Konvensi inklusif: hari masuk = hari ke-1
- `hariKe()`, `sisaHariKerja()`, `target()`, `zona()`, `lewatInvestigasi()`
- Hari libur nasional: **belum diimplementasi** (lihat Batch 5 di `revisian.md`)

---

## 📁 Struktur Database Utama

| Tabel | Deskripsi |
|-------|-----------|
| `pengaduan` | Tiket utama (kode_tiket, status, master_unit_id, kasus_berat, dll) |
| `master_units` | Data unit (kode, nama, kategori, PIC, koneksi SIMRS, disposisi status) |
| `master_unit_user` | Pivot user ↔ unit (peran_akses) |
| `pesan_pengaduan` | Percakapan (kanal: pelapor/unit, peran: pelapor/admin/unit/pembuka) |
| `riwayat_status_pengaduan` | Audit trail perpindahan tahap + penandaan kasus berat |
| `notifications` | Notifikasi database (admin & unit) |
| `users` | Admin humas + PIC unit (peran: admin/unit) |

---

## 📚 Dokumentasi Tambahan

| File | Isi |
|------|-----|
| `STYLE_GUIDE.md` | Panduan gaya kode PHP + Blade (510 baris) |
| `STYLING_GUIDE.md` | Panduan styling/UI Tailwind (530 baris) |
| `revisian.md` | Batch planning, audit log, bug tracking |
| `CLAUDE.md` | Context untuk AI assistant |

---

## 🛠️ Perintah Berguna

```bash
# Code style
./vendor/bin/pint --dirty --format agent

# Cache
php artisan config:clear && php artisan route:clear && php artisan view:clear

# Database
php artisan migrate:fresh --seed

# Queue
php artisan queue:work --tries=3 --backoff=5

# Schedule
php artisan schedule:work

# IDE Helper
php artisan ide-helper:generate
php artisan ide-helper:models -N
```

---

## 📝 Lisensi

Hak Cipta © 2026 RSUD Dr. Soetomo Surabaya. Seluruh hak cipta dilindungi.
Sistem Aduan Publik Terintegrasi — Terintegrasi SIMRS & Kemenkes RI.