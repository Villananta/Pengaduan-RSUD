@component('mail::layout')
@slot('subject', 'Disposisi Tiket: {{ $pengaduan->kode_tiket }} → {{ $unitTujuan->kode }}')

# Disposisi Tiket ke Unit

Halo **{{ $unitTujuan->pic ?? 'PIC Unit' }}**,

Tiket berikut telah didisposisikan ke unit Anda oleh Admin Humas:

---

## Detail Tiket

| Item | Detail |
|------|--------|
| **Kode Tiket** | {{ $pengaduan->kode_tiket }} |
| **Pelapor** | {{ $pengaduan->nama_lengkap }} (NRM: {{ $pengaduan->nrm }}) |
| **Subjek** | {{ $pengaduan->subjek }} |
| **Unit Tujuan** | {{ $unitTujuan->namaLengkap() }} |
| **Status** | {{ $pengaduan->status->label() }} |
| **Tahap Investigasi** | {{ \App\Enums\StatusInvestigasi::dariPengaduan($pengaduan, false)->label() }} |

---

@if ($catatan)
## Catatan dari Admin Humas

> {{ $catatan }}

---
@endif

## SLA & Prioritas

- **Batas Investigasi:** {{ config('pengaduan.sla.investigasi_hari_kerja') }} hari kerja
- **Hari ke-:** {{ \App\Support\Sla::hariKe($pengaduan->created_at) }}
- **Sisa Hari:** {{ $pengaduan->sisaHariSla() }} hari kerja
- **Zona SLA:** {{ $pengaduan->zonaSla()->label() }}

---

## Tindakan Diperlukan

Silakan segera:
1. Buka tiket di konsol unit
2. Mulai investigasi / telaah medis
3. Kirim jawaban ke humas sebelum batas investigasi

[**Buka Tiket di Konsol Unit**]({{ route('unit.disposisi.show', ['unit' => $unitTujuan->kode, 'kode' => $pengaduan->kode_tiket]) }})

---

Salam,
**Sistem Pengaduan RSUD Dr. Soetomo**  
*Email ini dikirim otomatis, mohon tidak dibalas.*

@endcomponent