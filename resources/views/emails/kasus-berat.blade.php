@component('mail::layout')
@slot('subject', $aktif
    ? 'Tiket Ditandai Kasus Berat — {{ $pengaduan->kode_tiket }}'
    : 'Penandaan Kasus Berat Dilepas — {{ $pengaduan->kode_tiket }}')

# {{ $aktif ? '🔴 Tiket Ditandai Kasus Berat' : '✅ Penandaan Kasus Berat Dilepas' }}

Halo,

{{ $aktif
    ? 'Tiket berikut telah ditandai sebagai **kasus berat** oleh Admin Humas.'
    : 'Penandaan kasus berat pada tiket berikut telah **dilepas** oleh Admin Humas.'
}}

---

## Detail Tiket

| Item | Detail |
|------|--------|
| **Kode Tiket** | {{ $pengaduan->kode_tiket }} |
| **Pelapor** | {{ $pengaduan->nama_lengkap }} (NRM: {{ $pengaduan->nrm }}) |
| **Subjek** | {{ $pengaduan->subjek }} |
| **Unit** | {{ $pengaduan->namaUnit() }} |
| **Status** | {{ $pengaduan->status->label() }} |

---

@if ($aktif)
## Dampak pada SLA

- **Tambahan Hari Kerja:** {{ config('pengaduan.sla.kasus_berat_hari_kerja') }} hari kerja
- **Total Baru:** {{ \App\Support\Sla::totalHariKerja(true) }} hari kerja
- **Target Selesai Baru:** {{ \App\Support\Sla::target($pengaduan->created_at, true)->format('d/m/Y') }}

> ⚠️ **Kasus berat memerlukan telaah komite etik/medik. Investigasi unit tetap dibatasi {{ config('pengaduan.sla.investigasi_hari_kerja') }} hari kerja.**

@else
## SLA Kembali Standar

- **Total Hari Kerja:** {{ \App\Support\Sla::totalHariKerja(false) }} hari kerja
- **Target Selesai:** {{ \App\Support\Sla::target($pengaduan->created_at, false)->format('d/m/Y') }}

> ✅ Penyelesaian kembali mengikuti standar biasa {{ config('pengaduan.sla.hari_kerja') }} hari kerja.
@endif

---

[**Buka Tiket di Konsol Admin**]({{ route('admin.pengaduan.show', $pengaduan->kode_tiket) }})

---

Salam,
**Sistem Pengaduan RSUD Dr. Soetomo**  
*Email ini dikirim otomatis, mohon tidak dibalas.*

@endcomponent