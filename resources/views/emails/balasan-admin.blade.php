@component('mail::layout')
@slot('subject', 'Balasan dari Admin Humas — {{ $pengaduan->kode_tiket }}')

# Balasan Baru dari Admin Humas

Halo **{{ $pengaduan->nama_lengkap }}**,

Admin Humas RSUD Dr. Soetomo telah membalas tiket Anda:

---

## Detail Tiket

| Item | Detail |
|------|--------|
| **Kode Tiket** | {{ $pengaduan->kode_tiket }} |
| **Subjek** | {{ $pengaduan->subjek }} |
| **Unit** | {{ $pengaduan->namaUnit() }} |

---

## Balasan Admin Humas

{{ $isiBalasan }}

---

## Status SLA

- **Batas Investigasi:** {{ config('pengaduan.sla.investigasi_hari_kerja') }} hari kerja
- **Hari ke-:** {{ \App\Support\Sla::hariKe($pengaduan->created_at) }}
- **Sisa Hari:** {{ $pengaduan->sisaHariSla() }} hari kerja
- **Zona SLA:** {{ $pengaduan->zonaSla()->label() }}

---

[**Lihat Detail & Balas Tiket**]({{ route('pengaduan.lacak') }})

---

Salam,
**Sistem Pengaduan RSUD Dr. Soetomo**  
*Email ini dikirim otomatis, mohon tidak dibalas.*

@endcomponent