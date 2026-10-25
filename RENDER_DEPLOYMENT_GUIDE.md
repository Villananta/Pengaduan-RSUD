# Panduan Deployment ke Render

Dokumen ini mendokumentasikan konfigurasi yang diperlukan untuk mendeploy project **Sistem Pengaduan RSUD Dr. Soetomo** ke [Render](https://render.com). Konfigurasi ini disiapkan berdasarkan `Dockerfile` dan `docker/entrypoint.sh` yang saat ini digunakan untuk Railway, dengan penyesuaian untuk platform Render.

> **Status**: Pending — saat ini project menggunakan Railway (`railway.json` + Dockerfile). File ini dibuat untuk referensi migrasi di masa depan.

---

## 1. Arsitektur Deployment Render

Render mendukung dua mode deployment untuk Laravel:

| Mode | Cocok Untuk | Keuntungan |
|------|-------------|------------|
| **Docker** (Native) | Aplikasi kompleks, butuh ekstensi PHP custom, multi-stage build | Kontrol penuh, konsisten dengan lokal, support background worker |
| **Native PHP** (Build Command) | Aplikasi standar, tidak butuh ekstensi khusus | Lebih simple, auto-deploy dari Git, build lebih cepat |

**Rekomendasi**: Gunakan **Docker** karena project ini butuh ekstensi `gd` (untuk phpspreadsheet/Excel export) dan multi-stage build (Node untuk Vite + Composer untuk PHP).

---

## 2. File yang Dibutuhkan untuk Render

### 2.1 `render.yaml` (Infrastructure as Code)

Buat file `render.yaml` di root project:

```yaml
# render.yaml - Konfigurasi Infrastructure as Code untuk Render
# Deploy: push ke Git -> Render otomatis baca file ini

services:
  # ---------------------------------------------------------
  # Web Service (Laravel App)
  # ---------------------------------------------------------
  - type: web
    name: pengaduan-rsud-web
    runtime: docker
    dockerfilePath: ./Dockerfile
    dockerContext: .
    plan: starter        # ganti ke 'standard' untuk production
    region: singapore    # region terdekat ke Indonesia
    healthCheckPath: /buat-aduan
    autoDeploy: true
    envVars:
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: "false"
      - key: APP_URL
        generateValue: true  # Render otomatis generate URL service
      - key: LOG_CHANNEL
        value: stderr
      - key: LOG_LEVEL
        value: error
      # Database (akan diisi otomatis dari PostgreSQL service di bawah)
      - key: DB_CONNECTION
        value: pgsql
      - key: DB_HOST
        fromDatabase:
          name: pengaduan-rsud-db
          property: host
      - key: DB_PORT
        fromDatabase:
          name: pengaduan-rsud-db
          property: port
      - key: DB_DATABASE
        fromDatabase:
          name: pengaduan-rsud-db
          property: database
      - key: DB_USERNAME
        fromDatabase:
          name: pengaduan-rsud-db
          property: user
      - key: DB_PASSWORD
        fromDatabase:
          name: pengaduan-rsud-db
          property: password
      # Redis (untuk queue/cache/session)
      - key: REDIS_HOST
        fromService:
          type: redis
          name: pengaduan-rsud-redis
          property: host
      - key: REDIS_PORT
        fromService:
          type: redis
          name: pengaduan-rsud-redis
          property: port
      - key: REDIS_PASSWORD
        fromService:
          type: redis
          name: pengaduan-rsud-redis
          property: password
      # Queue connection
      - key: QUEUE_CONNECTION
        value: redis
      # Session & Cache driver
      - key: SESSION_DRIVER
        value: redis
      - key: CACHE_DRIVER
        value: redis
      # Mail (konfigurasi SMTP Render/eksternal)
      - key: MAIL_MAILER
        value: smtp
      - key: MAIL_HOST
        sync: false  # isi manual di dashboard
      - key: MAIL_PORT
        value: "587"
      - key: MAIL_USERNAME
        sync: false
      - key: MAIL_PASSWORD
        sync: false
      - key: MAIL_ENCRYPTION
        value: tls
      - key: MAIL_FROM_ADDRESS
        sync: false
      - key: MAIL_FROM_NAME
        value: "Pengaduan RSUD Dr. Soetomo"

  # ---------------------------------------------------------
  # Background Worker (Queue Worker)
  # ---------------------------------------------------------
  - type: worker
    name: pengaduan-rsud-worker
    runtime: docker
    dockerfilePath: ./Dockerfile
    dockerContext: .
    plan: starter
    region: singapore
    # Worker tidak butuh health check HTTP
    envVars:
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: "false"
      - key: LOG_CHANNEL
        value: stderr
      - key: LOG_LEVEL
        value: error
      - key: DB_CONNECTION
        value: pgsql
      - key: DB_HOST
        fromDatabase:
          name: pengaduan-rsud-db
          property: host
      - key: DB_PORT
        fromDatabase:
          name: pengaduan-rsud-db
          property: port
      - key: DB_DATABASE
        fromDatabase:
          name: pengaduan-rsud-db
          property: database
      - key: DB_USERNAME
        fromDatabase:
          name: pengaduan-rsud-db
          property: user
      - key: DB_PASSWORD
        fromDatabase:
          name: pengaduan-rsud-db
          property: password
      - key: REDIS_HOST
        fromService:
          type: redis
          name: pengaduan-rsud-redis
          property: host
      - key: REDIS_PORT
        fromService:
          type: redis
          name: pengaduan-rsud-redis
          property: port
      - key: REDIS_PASSWORD
        fromService:
          type: redis
          name: pengaduan-rsud-redis
          property: password
      - key: QUEUE_CONNECTION
        value: redis
    # Command untuk menjalankan queue worker
    startCommand: php artisan queue:work --sleep=3 --tries=3 --timeout=90

  # ---------------------------------------------------------
  # Scheduled Task (Cron Job) - untuk SLA monitoring, nudge, dll
  # ---------------------------------------------------------
  - type: cron
    name: pengaduan-rsud-scheduler
    runtime: docker
    dockerfilePath: ./Dockerfile
    dockerContext: .
    plan: starter
    region: singapore
    schedule: "*/5 * * * *"  # setiap 5 menit (sesuai kebutuhan SLA monitoring)
    envVars:
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: "false"
      - key: LOG_CHANNEL
        value: stderr
      - key: DB_CONNECTION
        value: pgsql
      - key: DB_HOST
        fromDatabase:
          name: pengaduan-rsud-db
          property: host
      - key: DB_PORT
        fromDatabase:
          name: pengaduan-rsud-db
          property: port
      - key: DB_DATABASE
        fromDatabase:
          name: pengaduan-rsud-db
          property: database
      - key: DB_USERNAME
        fromDatabase:
          name: pengaduan-rsud-db
          property: user
      - key: DB_PASSWORD
        fromDatabase:
          name: pengaduan-rsud-db
          property: password
      - key: REDIS_HOST
        fromService:
          type: redis
          name: pengaduan-rsud-redis
          property: host
      - key: REDIS_PORT
        fromService:
          type: redis
          name: pengaduan-rsud-redis
          property: port
      - key: REDIS_PASSWORD
        fromService:
          type: redis
          name: pengaduan-rsud-redis
          property: password
      - key: QUEUE_CONNECTION
        value: redis
    # Command untuk menjalankan scheduler
    startCommand: php artisan schedule:run

  # ---------------------------------------------------------
  # Database PostgreSQL
  # ---------------------------------------------------------
  databases:
    - name: pengaduan-rsud-db
      databaseName: pengaduan_rsud
      user: pengaduan_user
      plan: starter
      region: singapore
      ipAllowList: []  # kosong = allow all (atau isi IP Render worker)

  # ---------------------------------------------------------
  # Redis (untuk queue, cache, session)
  # ---------------------------------------------------------
  services:
    - type: redis
      name: pengaduan-rsud-redis
      plan: starter
      region: singapore
      maxmemoryPolicy: allkeys-lru
```

---

### 2.2 `Dockerfile` (Sudah Ada - Kompatibel Render)

File `Dockerfile` saat ini **sudah kompatibel** dengan Render. Render mendukung multi-stage build Docker native.

**Catatan penting untuk Render:**
- Render menyediakan `PORT` environment variable otomatis (biasanya 10000 atau random)
- `ENTRYPOINT` di Dockerfile akan dieksekusi dengan `PORT` tersebut
- `docker/entrypoint.sh` sudah menggunakan `${PORT:-8080}` → kompatibel

**Perbaikan minor opsional** (jika ingin lebih optimal di Render):

```dockerfile
# Di Dockerfile, baris EXPOSE dan ENTRYPOINT sudah benar
# Render akan override PORT via env var, jadi ${PORT:-8080} di entrypoint.sh akan pakai PORT Render

# Opsional: tambahkan label untuk Render
LABEL org.opencontainers.image.source="https://github.com/username/pengaduan-rsud"
LABEL org.opencontainers.image.description="Sistem Pengaduan RSUD Dr. Soetomo"
```

---

### 2.3 `docker/entrypoint.sh` (Sudah Ada - Perlu Sedikit Penyesuaian)

File saat ini sudah bagus. Perbaikan kecil untuk Render:

```bash
#!/bin/sh
set -e

# Paket layanan dibangkitkan saat container hidup
php artisan package:discover --ansi

# Migrasi idempoten
# Render PostgreSQL biasanya sudah siap saat container start,
# tapi tambahkan retry logic untuk keamanan
MAX_RETRIES=30
RETRY_COUNT=0
until php artisan migrate --force 2>/dev/null; do
    RETRY_COUNT=$((RETRY_COUNT+1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        echo "Database migration failed after $MAX_RETRIES attempts"
        exit 1
    fi
    echo "Waiting for database... (attempt $RETRY_COUNT/$MAX_RETRIES)"
    sleep 2
done

# Storage link
php artisan storage:link --force

# Cache konfigurasi, rute, dan view
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Render menyediakan PORT via environment variable
# Fallback 8080 untuk development lokal
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
```

---

## 3. Environment Variables yang Wajib Diisi Manual

Beberapa variabel **tidak bisa** di-generate otomatis dan harus diisi manual di Dashboard Render > Environment:

| Variable | Deskripsi | Contoh |
|----------|-----------|--------|
| `APP_KEY` | Laravel encryption key | `base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx` |
| `MAIL_HOST` | SMTP host (mis. Mailgun, SendGrid, Gmail) | `smtp.mailgun.org` |
| `MAIL_USERNAME` | SMTP username | `postmaster@mg.domain.com` |
| `MAIL_PASSWORD` | SMTP password | `xxxxxxxxxxxx` |
| `MAIL_FROM_ADDRESS` | Email pengirim | `pengaduan@rsudrsoetomo.jatimprov.go.id` |
| `MAIL_FROM_NAME` | Nama pengirim | `Pengaduan RSUD Dr. Soetomo` |

**Generate `APP_KEY` lokal:**
```bash
php artisan key:generate --show
# Copy output ke Render Dashboard > Environment > APP_KEY
```

---

## 4. Konfigurasi Database (PostgreSQL)

Render menyediakan **Managed PostgreSQL** gratis (Starter plan).

**Migrasi otomatis** sudah ditangani di `entrypoint.sh` via `php artisan migrate --force`.

**Seed data awal** (jika diperlukan):
```bash
# Jalankan sekali via Render Shell (SSH) setelah deploy pertama:
php artisan db:seed --force
```

---

## 5. Konfigurasi Redis

Render menyediakan **Managed Redis** (Starter plan).

Digunakan untuk:
- **Queue** (background jobs: email, notifikasi, export)
- **Cache** (statistik dashboard, konfigurasi)
- **Session** (login admin/unit)

Sudah dikonfigurasi di `render.yaml` via `fromService`.

---

## 6. File Storage (Lampiran Pengaduan)

**Penting**: Render filesystem **ephemeral** (hilang saat redeploy).

### Opsi A: Render Disk (Persistent Volume) — Gratis hingga 10GB
```yaml
# Tambahkan di render.yaml > web service > disks:
disks:
  - name: storage
    mountPath: /app/storage/app/public
    sizeGB: 10
```
Lalu update `docker/entrypoint.sh`:
```bash
# Pastikan symlink storage ke disk
php artisan storage:link --force
```

### Opsi B: AWS S3 / Cloudflare R2 (Rekomendasi Production)
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=pengaduan-rsud-lampiran
AWS_ENDPOINT=https://xxx.r2.cloudflarestorage.com  # jika pakai R2
AWS_USE_PATH_STYLE_ENDPOINT=true
```
Update `config/filesystems.php` sesuai dokumentasi Laravel.

---

## 7. Build & Deploy Process

### 7.1 Push ke Git Repository
```bash
git add .
git commit -m "feat: add Render deployment config"
git push origin main
```

### 7.2 Connect ke Render
1. Login ke [dashboard.render.com](https://dashboard.render.com)
2. **New** > **Blueprint** > Connect repository GitHub/GitLab
3. Render akan mendeteksi `render.yaml` dan menampilkan preview resources
4. Klik **Apply** untuk membuat semua service (Web, Worker, Cron, DB, Redis)

### 7.3 Setelah Deploy Pertama
1. Buka URL web service (mis. `https://pengaduan-rsud-web.onrender.com`)
2. Cek log deploy untuk memastikan migrasi berjalan
3. Isi environment variables manual (APP_KEY, MAIL_*, dll)
4. **Manual Deploy** > **Clear build cache & deploy** setelah set env vars

---

## 8. Perbandingan Railway vs Render

| Aspek | Railway (Saat Ini) | Render (Target) |
|-------|-------------------|-----------------|
| **Config** | `railway.json` + Dockerfile | `render.yaml` + Dockerfile |
| **Database** | Railway MySQL/PostgreSQL | Render PostgreSQL (managed) |
| **Redis** | Railway Redis | Render Redis (managed) |
| **Disk** | Railway Volume | Render Disk / S3 |
| **Worker** | Terpisah di `railway.json` | Service type `worker` di `render.yaml` |
| **Cron** | Terpisah / Supervisor | Service type `cron` di `render.yaml` |
| **Auto-deploy** | Ya (Git push) | Ya (Git push via Blueprint) |
| **Region** | Singapore/US/EU | Singapore/US/EU |
| **Free Tier** | Trial $5/kredit | Free tier terbatas (750 jam/bln) |
| **Harga Starter** | ~$5/bulan per service | $7/bulan per service |

---

## 9. Checklist Migrasi ke Render

### Persiapan (Sebelum Migrasi)
- [ ] Backup database Railway (`mysqldump` / `pg_dump`)
- [ ] Export data lampiran ke S3/R2 (jika pakai Opsi B)
- [ ] Generate `APP_KEY` baru untuk production
- [ ] Siapkan SMTP credentials (Mailgun/SendGrid/Gmail)
- [ ] Test Docker build lokal: `docker build -t pengaduan-rsud .`

### Deploy ke Render
- [ ] Buat `render.yaml` (copy dari dokumen ini)
- [ ] Commit & push ke Git
- [ ] Connect repo ke Render via Blueprint
- [ ] Isi environment variables manual di Dashboard
- [ ] Deploy ulang (Clear build cache)
- [ ] Verifikasi migrasi database berjalan
- [ ] Test fitur utama: buat aduan, verifikasi email, login admin/unit

### Post-Deploy
- [ ] Setup custom domain (jika ada)
- [ ] Konfigurasi SSL (otomatis Render)
- [ ] Setup monitoring/alert (Render metrics + external uptime)
- [ ] Dokumentasikan kredensial & akses tim

---

## 10. Troubleshooting Umum

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| `php artisan migrate` timeout | DB belum siap | Tambah retry logic di entrypoint.sh (sudah ada di atas) |
| `gd` extension missing | Dockerfile tidak install gd | Dockerfile sudah include gd + dependencies |
| Vite manifest not found | Frontend build gagal | Pastikan `npm run build` di stage frontend berhasil |
| Queue worker tidak jalan | Redis tidak connect | Cek `REDIS_HOST/PORT/PASSWORD` dari service binding |
| File upload error 413 | `upload_max_filesize` terlalu kecil | Dockerfile sudah set 8M, cek nginx/proxy jika ada |
| Session hilang saat redeploy | Filesystem ephemeral | Pakai Redis session (sudah dikonfigurasi) atau S3 disk |

---

## 11. Estimasi Biaya Render (Starter Plan)

| Service | Biaya/Bulan | Catatan |
|---------|-------------|---------|
| Web Service | $7 | 512MB RAM, 0.5 CPU |
| Worker | $7 | 512MB RAM, 0.5 CPU |
| Cron Job | $7 | 512MB RAM, 0.5 CPU |
| PostgreSQL | $7 | 1GB storage, 1GB RAM |
| Redis | $7 | 25MB RAM |
| **Total** | **$35/bulan** | ~Rp 550.000/bulan (kurs $1 = Rp 15.700) |

> **Catatan**: Render free tier hanya untuk *static sites* dan *web services* dengan batas 750 jam/bulan. Untuk production dengan worker + cron + DB, butuh paid plan.

---

## 12. Referensi

- [Render Blueprint Spec](https://render.com/docs/blueprint-spec)
- [Render Docker Deploy](https://render.com/docs/deploy-docker)
- [Render PostgreSQL](https://render.com/docs/databases)
- [Render Redis](https://render.com/docs/redis)
- [Laravel on Render Guide](https://render.com/docs/deploy-laravel)
- [Laravel Octane di Render](https://render.com/docs/deploy-laravel-octane) (opsional untuk performance)

---

## 13. Catatan Penting untuk Tim

1. **Jangan hapus `Dockerfile` dan `docker/entrypoint.sh`** — file ini dual-purpose (Railway & Render)
2. **`railway.json` tetap dipakai** untuk deployment Railway saat ini
3. Saat siap migrasi, **tambahkan `render.yaml`** dan deploy via Blueprint
4. **Test staging dulu** di Render sebelum switch production
5. **Backup data** sebelum migrasi database

---

*Dokumen ini dibuat pada: 25 Oktober 2026*  
*Berdasarkan: `Dockerfile` (multi-stage), `docker/entrypoint.sh`, `railway.json`*  
*Status: Siap pakai saat tim memutuskan migrasi ke Render*