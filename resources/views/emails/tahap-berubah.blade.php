@component('mail::layout')
@slot('subject', 'Status Tiket Berubah: {{ $pengaduan->kode_tiket }} ({{ $dari->label() }} → {{ $ke->label() }})')

# Status Tiket Berubah

Halo,

Tiket berikut mengalami perubahan tahap:

---

## Detail Tiket

| Item | Detail |
|------|--------|
| **Kode Tiket** | {{ $pengaduan->kode_tiket }} |
| **Pelapor** | {{ $pengaduan->nama_lengkap }} (NRM: {{ $pengaduan->nrm }}) |
| **Subjek** | {{ $pengaduan->subjek }} |
| **Unit** | {{ $pengaduan->namaUnit() }} |

---

## Perubahan Tahap

| Dari | Ke |
|------|-----|
| **{{ $dari->label() }}** | **{{ $ke->label() }}** |

---

## Status SLA

- **Batas Investigasi:** {{ config('pengaduan.sla.investigasi_hari_kerja') }} hari kerja
- **Hari ke-:** {{ \App\Support\Sla::hariKe($pengaduan->created_at) }}
- **Sisa Hari:** {{ $pengaduan->sisaHariSla() }} hari kerja
- **Zona SLA:** {{ $pengaduan->zonaSla()->label() }}

---

[**Buka Tiket**]({{ $url }})

---

Salam,
**Sistem Pengaduan RSUD Dr. Soetomo**  
*Email ini dikirim otomatis, mohon tidak dibalas.*

@endcomponent