# Database Schema & ERD — Sistem Pengaduan RSUD Dr. Soetomo

> **Versi**: 1.0 | **Terakhir Update**: 2026-10-21
> **Database**: SQLite (dev) / MySQL 8.0+ / PostgreSQL 14+ (production)

---

## 📊 Entity Relationship Diagram (Mermaid)

```mermaid
erDiagram
    USERS ||--o{ MASTER_UNIT_USER : "has"
    MASTER_UNITS ||--o{ MASTER_UNIT_USER : "has"
    MASTER_UNITS ||--o{ PENGADUAN : "assigned"
    PENGADUAN ||--o{ PESAN_PENGADUAN : "has"
    PENGADUAN ||--o{ RIWAYAT_STATUS_PENGADUAN : "audit"
    USERS ||--o{ RIWAYAT_STATUS_PENGADUAN : "admin"
    PESAN_PENGADUAN }|--|| PENGADUAN : "belongs"
    RIWAYAT_STATUS_PENGADUAN }|--|| PENGADUAN : "belongs"
    RIWAYAT_STATUS_PENGADUAN }|--|| USERS : "admin"
    MASTER_UNIT_USER }|--|| USERS : "user"
    MASTER_UNIT_USER }|--|| MASTER_UNITS : "unit"

    USERS {
        id BIGINT PK
        name VARCHAR
        email VARCHAR UNIQUE
        peran ENUM[admin, unit]
        password VARCHAR
        email_verified_at TIMESTAMP
        remember_token VARCHAR
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    MASTER_UNITS {
        id BIGINT PK
        kode VARCHAR(20) UNIQUE
        nama VARCHAR
        kategori ENUM
        pic VARCHAR
        jabatan_pic VARCHAR
        nip VARCHAR
        kontak_wa VARCHAR
        email VARCHAR
        undangan_token VARCHAR
        undangan_berlaku TIMESTAMP
        ekstensi VARCHAR
        jam_layanan VARCHAR
        akun_simrs VARCHAR
        peran_akses ENUM
        status_akses ENUM
        aktif BOOLEAN
        koneksi_simrs BOOLEAN
        koneksi_simrs_terakhir TIMESTAMP
        disposisi ENUM
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    MASTER_UNIT_USER {
        id BIGINT PK
        master_unit_id BIGINT FK
        user_id BIGINT FK
        peran_akses ENUM
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    PENGADUAN {
        id BIGINT PK
        kode_tiket VARCHAR UNIQUE
        kategori ENUM[fasilitas, medis]
        nama_lengkap VARCHAR
        nrm VARCHAR
        no_wa VARCHAR
        email VARCHAR
        email_verified_at TIMESTAMP
        email_otp VARCHAR
        email_otp_expires_at TIMESTAMP
        alamat TEXT
        waktu_kejadian DATETIME
        unit VARCHAR
        subjek VARCHAR
        deskripsi TEXT
        lampiran JSON
        status ENUM[diterima, diproses, revisi, selesai]
        master_unit_id BIGINT FK NULL
        kasus_berat BOOLEAN
        kasus_berat_at TIMESTAMP
        draf_jawaban TEXT
        akses_token VARCHAR UNIQUE NULL
        akses_token_expires_at TIMESTAMP NULL
        selesai_at TIMESTAMP NULL
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    PESAN_PENGADUAN {
        id BIGINT PK
        pengaduan_id BIGINT FK
        kanal ENUM[pelapor, unit]
        peran ENUM[pelapor, admin, unit, pembuka]
        isi TEXT
        lampiran JSON
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    RIWAYAT_STATUS_PENGADUAN {
        id BIGINT PK
        pengaduan_id BIGINT FK
        dari ENUM[diterima, diproses, revisi, selesai] NULL
        ke ENUM[diterima, diproses, revisi, selesai]
        catatan TEXT NULL
        admin_id BIGINT FK NULL
        is_nudge BOOLEAN DEFAULT FALSE
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    NOTIFICATIONS {
        id CHAR(36) PK
        type VARCHAR
        notifiable_type VARCHAR
        notifiable_id BIGINT
        data JSON
        read_at TIMESTAMP NULL
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    HARI_LIBUR {
        id BIGINT PK
        tanggal DATE UNIQUE
        nama VARCHAR
        nasional BOOLEAN
        keterangan TEXT NULL
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    CACHE {
        key VARCHAR PK
        value MEDIUMTEXT
        expiration INT
    }
```

---

## 🔗 Relasi Kunci (Key Relationships)

| Parent | Child | Type | Foreign Key | On Delete |
|--------|-------|------|-------------|-----------|
| `master_units` | `pengaduan` | 1:N | `pengaduan.master_unit_id` | SET NULL |
| `pengaduan` | `pesan_pengaduan` | 1:N | `pesan_pengaduan.pengaduan_id` | CASCADE |
| `pengaduan` | `riwayat_status_pengaduan` | 1:N | `riwayat_status_pengaduan.pengaduan_id` | CASCADE |
| `users` | `riwayat_status_pengaduan` | 1:N | `riwayat_status_pengaduan.admin_id` | SET NULL |
| `master_units` | `master_unit_user` | 1:N | `master_unit_user.master_unit_id` | CASCADE |
| `users` | `master_unit_user` | 1:N | `master_unit_user.user_id` | CASCADE |
| `users` | `notifications` | Polymorphic 1:N | `notifications.notifiable_id` + `notifiable_type` | CASCADE |

---

## 📋 Tabel Detail

### 1. `users` — Admin Humas & PIC Unit
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    peran ENUM('admin','unit') NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX users_email_index (email)
);
```
- **`peran`**: `admin` = konsol humas, `unit` = PIC unit
- Password: bcrypt (Laravel default)
- No self-registration — admin dibuat manual, PIC via undangan aktivasi

---

### 2. `master_units` — Data Instalasi/Unit Pelayanan
```sql
CREATE TABLE master_units (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(255) NOT NULL,
    kategori ENUM('instalasi','unit','lainnya') NOT NULL,
    pic VARCHAR(255) NULL,
    jabatan_pic VARCHAR(255) NULL,
    nip VARCHAR(50) NULL,
    kontak_wa VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    undangan_token VARCHAR(64) NULL,
    undangan_berlaku TIMESTAMP NULL,
    ekstensi VARCHAR(20) NULL,
    jam_layanan VARCHAR(100) NULL,
    akun_simrs VARCHAR(50) NULL,
    peran_akses ENUM('pic','anggota') NOT NULL DEFAULT 'pic',
    status_akses ENUM('aktif','nonaktif','pending') NOT NULL DEFAULT 'pending',
    aktif BOOLEAN NOT NULL DEFAULT TRUE,
    koneksi_simrs BOOLEAN NOT NULL DEFAULT TRUE,
    koneksi_simrs_terakhir TIMESTAMP NULL,
    disposisi ENUM('terhubung','menunggu_investigasi','menunggu_info_tambahan','terputus') NOT NULL DEFAULT 'terhubung',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX master_units_kode_nama_index (kode, nama)
);
```
- **Route Key**: `kode` (bukan `id`) — `MasterUnit::getRouteKeyName()` return `'kode'`
- **`disposisi`**: status kesiapan menerima tiket (enum `DisposisiUnit`)
- **`koneksi_simrs_terakhir`**: heartbeat SIMRS (updated via external cron/API)
- **`bisaDitugaskan()`** logic: `aktif && disposisi->siap() && pic && kontak_wa`

---

### 3. `master_unit_user` — Pivot User ↔ Unit (Many-to-Many)
```sql
CREATE TABLE master_unit_user (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    master_unit_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    peran_akses ENUM('pic','anggota') NOT NULL DEFAULT 'anggota',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (master_unit_id) REFERENCES master_units(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY master_unit_user_unique (master_unit_id, user_id)
);
```
- **Satu user bisa handle multiple unit** (default: 1 PIC = 1 unit)
- **`peran_akses`**: `pic` = primary contact, `anggota` = additional staff
- **Query**: `User::unit()` / `MasterUnit::pengguna()` → `BelongsToMany`

---

### 4. `pengaduan` — Tiket Utama
```sql
CREATE TABLE pengaduan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_tiket VARCHAR(64) NOT NULL UNIQUE,           -- ADUAN-YYYYMMDD-XXXXX
    kategori ENUM('fasilitas','medis') NOT NULL,
    nama_lengkap VARCHAR(255) NOT NULL,
    nrm VARCHAR(64) NOT NULL,                         -- NRM pasien (raw)
    no_wa VARCHAR(20) NOT NULL,                       -- WhatsApp
    email VARCHAR(255) NOT NULL,                      -- Email pelapor
    email_verified_at TIMESTAMP NULL,                 -- Verified via OTP
    email_otp VARCHAR(6) NULL,                        -- 6 digit OTP
    email_otp_expires_at TIMESTAMP NULL,
    alamat TEXT NOT NULL,
    waktu_kejadian DATETIME NOT NULL,
    unit VARCHAR(255) NOT NULL,                       -- Unit yang dipilih pelapor (label)
    subjek VARCHAR(255) NOT NULL,
    deskripsi TEXT NOT NULL,
    lampiran JSON NULL,                               -- ["lampiran/xxx.jpg", ...]
    status ENUM('diterima','diproses','revisi','selesai') NOT NULL DEFAULT 'diterima',
    master_unit_id BIGINT UNSIGNED NULL,              -- FK ke master_units
    kasus_berat BOOLEAN NOT NULL DEFAULT FALSE,
    kasus_berat_at TIMESTAMP NULL,
    draf_jawaban TEXT NULL,
    akses_token VARCHAR(64) NULL UNIQUE,              -- 32 byte hex, 30 hari
    akses_token_expires_at TIMESTAMP NULL,
    selesai_at TIMESTAMP NULL,                        -- Diisi saat status=selesai
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX pengaduan_status_index (status),
    INDEX pengaduan_master_unit_id_status_index (master_unit_id, status),
    INDEX pengaduan_akses_token_unique (akses_token)
);
```
- **`kode_tiket`**: `ADUAN-YYYYMMDD-XXXXX` (5 huruf acak) — unique, generated di `PengaduanMasuk::kodeTiketBaru()`
- **`master_unit_id`**: NULLABLE — diisi saat pelapor pilih unit yang sudah di-petakan ke `master_units` DAN unit aktif/koneksi OK
- **`unit`**: Label unit yang diketik pelapor (bisa beda dari `master_units.nama`)
- **`kasus_berat`**: Extends SLA +8 hari kerja, target = `Sla::target(created_at, true)`
- **`akses_token`**: Token permanen 30 hari untuk "remember me" pelapor (HttpOnly cookie)

---

### 5. `pesan_pengaduan` — Percakapan Dual-Channel ⭐
```sql
CREATE TABLE pesan_pengaduan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengaduan_id BIGINT UNSIGNED NOT NULL,
    kanal ENUM('pelapor','unit') NOT NULL,
    peran ENUM('pelapor','admin','unit','pembuka') NOT NULL,
    isi TEXT NOT NULL,
    lampiran JSON NULL,                               -- ["lampiran/xxx.pdf", ...]
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (pengaduan_id) REFERENCES pengaduan(id) ON DELETE CASCADE,
    INDEX pesan_pengaduan_pengaduan_id_index (pengaduan_id)
);
```

#### 🔀 Dual Channel Architecture

| Channel | Visible To | Use Case |
|---------|------------|----------|
| **`pelapor`** | Pelapor + Admin Humas | Balasan pelapor, balasan admin ke pelapor, sapaan pembuka otomatis (`peran=pembuka`) |
| **`unit`** | Admin Humas + PIC Unit | Koordinasi internal: jawaban PIC unit (`peran=unit`), catatan admin (`peran=admin`) |

#### Peran (`peran`) Mapping
| Peran | Channel | Pengirim | Visible To |
|-------|---------|----------|------------|
| `pembuka` | `pelapor` | System (auto) | Pelapor + Admin |
| `pelapor` | `pelapor` | Pelapor | Pelapor + Admin |
| `admin` | `pelapor` | Admin Humas | Pelapor + Admin |
| `admin` | `unit` | Admin Humas | Admin + PIC Unit |
| `unit` | `unit` | PIC Unit | Admin + PIC Unit |

#### Query Scopes (di `PesanPengaduan` model)
```php
// Pesan yang boleh dibaca pelapor di halaman lacak
scopeJalurPelapor() → where('kanal', 'pelapor')

// Koordinasi internal humas ↔ unit
scopeJalurUnit() → where('kanal', 'unit')

// Jawaban PIC unit (untuk status investigasi & telaah)
scopeJawabanUnit() → where('peran', 'unit')
```

---

### 6. `riwayat_status_pengaduan` — Audit Trail
```sql
CREATE TABLE riwayat_status_pengaduan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengaduan_id BIGINT UNSIGNED NOT NULL,
    dari ENUM('diterima','diproses','revisi','selesai') NULL,  -- NULL = awal
    ke ENUM('diterima','diproses','revisi','selesai') NOT NULL,
    catatan TEXT NULL,
    admin_id BIGINT UNSIGNED NULL,
    is_nudge BOOLEAN NOT NULL DEFAULT FALSE,          -- ⭐ Baru: mark nudge manual
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (pengaduan_id) REFERENCES pengaduan(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX riwayat_nudge_lookup (pengaduan_id, is_nudge, created_at)
);
```
- **Baris pertama**: `dari = NULL`, `ke = 'diterima'` (saat `Pengaduan::created`)
- **Penandaan kasus berat**: `dari = ke` (sama), `catatan` = konstanta `Pengaduan::CATATAN_KASUS_BERAT_AKTIF/LEPAS`
- **Nudge manual**: `is_nudge = true`, `catatan = 'Nudge manual ke unit: ...'`
- **Helper**: `RiwayatStatusPengaduan::dariAwal()`, `::perubahanKasusBerat()`

---

### 7. `notifications` — Laravel Notifications
```sql
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,                          -- UUID
    type VARCHAR(255) NOT NULL,                       -- FQCN class
    notifiable_type VARCHAR(255) NOT NULL,            -- Morph: User::class
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data JSON NOT NULL,                               -- Payload custom
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX notifications_notifiable_index (notifiable_type, notifiable_id)
);
```
- **Channels**: `database` + `mail` (semua notification implements `ShouldQueue`)
- **Types**: `PengaduanDibuat`, `PengaduanDisposisi`, `PengaduanTahapBerubah`, `PengaduanBalasanAdmin`, `PengaduanSlaMendekati`, `PengaduanSlaLewat`, `PengaduanNudgeUnit`, `PengaduanEscalasiSla`, `PengaduanEscalasiNudge`, `PengaduanKasusBerat`, `PengaduanJawabanUnit`, `UndanganAktivasiPic`, `PengaduanEmailVerifikasi`

---

### 8. `hari_libur` — Holiday Calendar (Batch 5, pending)
```sql
CREATE TABLE hari_libur (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL UNIQUE,
    nama VARCHAR(255) NOT NULL,                       -- "Idul Fitri", "Cuti Bersama"
    nasional BOOLEAN NOT NULL DEFAULT TRUE,
    keterangan TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```
- Digunakan `Sla::hariKerjaLewat()` & `Sla::target()` untuk exclude hari libur
- Seeder: `HariLiburSeeder` (2024-2027, keputusan Bersama Menteri)

---

### 9. `cache` — Database Cache Store
```sql
CREATE TABLE cache (
    key VARCHAR(255) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
);
```
- Default cache driver: `database` (production: `redis` via `CACHE_STORE=redis`)
- Prefix: `pengaduan-rsud-cache-`

---

## 📈 Index Strategy

| Table | Index | Columns | Purpose |
|-------|-------|---------|---------|
| `pengaduan` | `pengaduan_status_index` | `status` | Filter tab Lapis 1 |
| `pengaduan` | `pengaduan_master_unit_id_status_index` | `(master_unit_id, status)` | Filter daftar per unit + status |
| `pengaduan` | `pengaduan_akses_token_unique` | `akses_token` | Token lookup (unique) |
| `pesan_pengaduan` | `pesan_pengaduan_pengaduan_id_index` | `pengaduan_id` | Join chat |
| `riwayat_status_pengaduan` | `riwayat_nudge_lookup` | `(pengaduan_id, is_nudge, created_at)` | Nudge cooldown query |
| `notifications` | `notifications_notifiable_index` | `(notifiable_type, notifiable_id)` | Polymorphic lookup |
| `master_units` | `master_units_kode_nama_index` | `(kode, nama)` | Search unit |

---

## 🔐 Security Notes

1. **`kode_tiket` NOT in `$fillable`** — generated server-side (`PengaduanMasuk::kodeTiketBaru()`)
2. **`akses_token`** — 32 byte hex, HttpOnly cookie 30 hari, rotasi saat email verified
3. **`email_otp`** — 6 digit, 15 min expiry, `hash_equals()` timing-safe
4. **File upload** — validated mimes (`jpg,jpeg,png,pdf`), max 4MB, stored to `DISK_LAMPIRAN` (S3 di prod)
5. **Rate limiting** — per-endpoint via `ThrottleKeys` constants
6. **Middleware layering** — `auth` → `PastikanAdmin/Pic` → `PastikanUnitSendiri` → `PastikanUnitAktif`

---

## 📦 Migration History (Key)

| Migration | Description |
|-----------|-------------|
| `0001_01_01_000000_create_users_table.php` | Users + `peran` enum |
| `2026_09_25_035943_create_pengaduan_table.php` | Tiket utama |
| `2026_09_25_090000_create_pesan_pengaduan_table.php` | Dual-channel chat |
| `2026_09_25_120000_add_lampiran_to_pesan_pengaduan_table.php` | Lampiran JSON |
| `2026_09_28_010000_add_selesai_at_and_riwayat_status_to_pengaduan_table.php` | Audit trail |
| `2026_09_28_020000_create_master_units_table.php` | Master units + FK |
| `2026_10_09_000000_add_peran_to_users_and_create_master_unit_user_table.php` | Pivot + peran |
| `2026_10_15_234602_add_akses_token_to_pengaduan_table.php` | Permanent access token |
| `2026_10_15_235940_add_email_verified_at_to_pengaduan_table.php` | Email verification |
| `2026_10_16_000246_add_email_otp_to_pengaduan_table.php` | OTP columns |
| `2026_10_19_225232_add_index_master_unit_id_status_to_pengaduan_table.php` | Composite index |
| `2026_10_21_134341_add_is_nudge_to_riwayat_status_pengaduan_table.php` | Nudge optimization |

---

## 📝 Migration Commands

```bash
# Run all migrations
php artisan migrate

# Fresh dengan seeder
php artisan migrate:fresh --seed

# Rollback last batch
php artisan migrate:rollback

# Check status
php artisan migrate:status

# Create migration
php artisan make:migration add_nama_kolom_to_tabel --table=tabel
```

---

## 📚 Referensi Kode

| File | Deskripsi |
|------|-----------|
| `app/Models/Pengaduan.php` | Model utama + factory method + scope + accessor |
| `app/Models/MasterUnit.php` | Unit model + scopes (terhubung/terputus/belum_ditugaskan) |
| `app/Models/PesanPengaduan.php` | Dual-channel + scopes |
| `app/Models/RiwayatStatusPengaduan.php` | Audit trail + helper |
| `app/Models/User.php` | Admin/PIC + pivot |
| `app/Support/Sla.php` | SLA calculation |
| `config/pengaduan.php` | Single source of truth SLA, units, prosedur |

---

*Dokumen ini di-generate dari codebase Laravel 13.x. Untuk update, edit file ini setelah migration/schema changes.*