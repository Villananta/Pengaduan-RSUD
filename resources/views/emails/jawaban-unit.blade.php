@component('mail::layout')
@slot('subject', 'Jawaban Unit Masuk — {{ $pengaduan->kode_tiket }}')

# Jawaban Unit Masuk

Halo **Admin Humas**,

Unit **{{ $unitPengirim->namaLengkap() }}** telah mengirim jawaban untuk tiket berikut:

---

## Detail Tiket

| Item | Detail |
|------|--------|
| **Kode Tiket** | {{ $pengaduan->kode_tiket }} |
| **Pelapor** | {{ $pengaduan->nama_lengkap }} (NRM: {{ $pengaduan->nrm }}) |
| **Subjek** | {{ $pengaduan->subjek }} |
| **Unit** | {{ $unitPengirim->namaLengkap() }} |
| **Status** | {{ $pengaduan->status->label() }} |

---

## Status SLA

- **Batas Investigasi:** {{ config('pengaduan.sla.investigasi_hari_kerja') }} hari kerja
- **Hari ke-:** {{ \App\Support\Sla::hariKe($pengaduan->created_at) }}
- **Sisa Hari:** {{ $pengaduan->sisaHariSla() }} hari kerja
- **Zona SLA:** {{ $pengaduan->zonaSla()->label() }}

---

## Tindakan Diperlukan

Silakan tinjau jawaban unit dan racik balasan resmi ke pelapor.

[**Buka Tiket & Racik Balasan**]({{ route('admin.pengaduan.show', $pengaduan->kode_tiket) }})

---

Salam,
**Sistem Pengaduan RSUD Dr. Soetomo**  
*Email ini dikirim otomatis, mohon tidak dibalas.*

@endcomponent