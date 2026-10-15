@component('mail::layout')
@slot('subject', 'Follow Up Manual dari Admin Humas — {{ $pengaduan->kode_tiket }}')

# Follow Up Manual dari Admin Humas

Halo **{{ $unit->pic ?? 'PIC Unit' }}**,

Admin Humas RSUD Dr. Soetomo telah mengirim **follow up manual (nudge)** untuk tiket berikut:

---

## Detail Tiket
| Item | Detail |
|------|--------|
| **Kode Tiket** | {{ $pengaduan->kode_tiket }} |
| **Pelapor** | {{ $pengaduan->nama_lengkap }} (NRM: {{ $pengaduan->nrm }}) |
| **Subjek** | {{ $pengaduan->subjek }} |
| **Unit** | {{ $unit->namaLengkap() }} |
| **Status** | {{ $pengaduan->status->label() }} |
| **Tahap Investigasi** | {{ \App\Enums\StatusInvestigasi::dariPengaduan($pengaduan, (bool) $pengaduan->unit_menjawab)->label() }} |

---

## Catatan dari Admin Humas
> {{ $catatan }}

---

## SLA & Prioritas
- **Batas Investigasi:** {{ config('pengaduan.sla.investigasi_hari_kerja') }} hari kerja
- **Hari ke-:** {{ \App\Support\Sla::hariKe($pengaduan->created_at) }}
- **Sisa Hari:** {{ $pengaduan->sisaHariSla() }} hari kerja
- **Zona SLA:** {{ $pengaduan->zonaSla()->label() }}

---

## Tindakan Diperlukan
Silakan segera:
1. Buka tiket di konsol unit
2. Tinjau catatan admin humas di atas
3. Lanjutkan investigasi / kirim jawaban ke humas

[**Buka Tiket di Konsol Unit**]({{ route('unit.disposisi.show', ['unit' => $unit->kode, 'kode' => $pengaduan->kode_tiket]) }})

---

Salam,
**Sistem Pengaduan RSUD Dr. Soetomo**  
*Email ini dikirim otomatis, mohon tidak dibalas.*
@endcomponent