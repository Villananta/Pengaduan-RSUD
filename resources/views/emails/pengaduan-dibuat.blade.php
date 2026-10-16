@component('mail::layout')
@slot('subject', 'Pengaduan Baru Dibuat — {{ $kodeTiket }}')

# Pengaduan Baru Dibuat

Halo **Admin Humas**,

Pengaduan baru telah dicatat di sistem:

---

## Detail Tiket

| Item | Detail |
|------|--------|
| **Kode Tiket** | {{ $kodeTiket }} |
| **Pelapor** | {{ $pengaduan->nama_lengkap }} (NRM: {{ $pengaduan->nrm }}) |
| **No. WhatsApp** | {{ $pengaduan->no_wa }} |
| **Email** | {{ $pengaduan->email }} |
| **Alamat** | {{ $pengaduan->alamat }} |
| **Kategori** | {{ $pengaduan->kategori->label() }} |
| **Unit Terkait** | {{ $pengaduan->unit }} |
| **Subjek** | {{ $pengaduan->subjek }} |
| **Waktu Kejadian** | {{ $pengaduan->waktu_kejadian->format('d/m/Y H:i') }} |

---

## Uraian Kronologi

{{ $pengaduan->deskripsi }}

---

## Lampiran

@if ($pengaduan->lampiran && count($pengaduan->lampiran) > 0)
@foreach ($pengaduan->lampiran as $file)
- {{ basename($file) }}
@endforeach
@else
Tidak ada lampiran.
@endif

---

## Estimasi SLA

- **Standar Penyelesaian:** {{ config('pengaduan.sla.hari_kerja') }} hari kerja
- **Batas Investigasi Unit:** {{ config('pengaduan.sla.investigasi_hari_kerja') }} hari kerja
- **Target Selesai:** {{ \App\Support\Sla::target($pengaduan->created_at, $pengaduan->kasus_berat)->format('d/m/Y') }}

---

[**Buka Tiket di Konsol Admin**]({{ route('admin.pengaduan.show', $kodeTiket) }})

---

Salam,
**Sistem Pengaduan RSUD Dr. Soetomo**  
*Email ini dikirim otomatis, mohon tidak dibalas.*

@endcomponent