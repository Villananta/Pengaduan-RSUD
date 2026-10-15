@component('mail::layout')
@slot('subject', 'Peringatan: Batas Investigasi Mendekati — {{ $pengaduan->kode_tiket }}')

# Peringatan: Batas Investigasi Mendekati

Halo **{{ $unit->pic ?? 'PIC Unit' }}**,

Tiket berikut mendekati batas waktu investigasi dan memerlukan perhatian segera:

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

## Status SLA
- **Batas Investigasi:** {{ config('pengaduan.sla.investigasi_hari_kerja') }} hari kerja
- **Hari ke-:** {{ \App\Support\Sla::hariKe($pengaduan->created_at) }}
- **Sisa Hari Kerja:** **{{ $sisaHari }} hari**
- **Zona SLA:** {{ $pengaduan->zonaSla()->label() }}

> ⚠️ **Tiket ini memasuki dua hari kerja terakhir sebelum batas investigasi.**

---

## Tindakan Diperlukan
Segera lanjutkan investigasi / kirim jawaban ke humas untuk menghindari pelanggaran SLA.

[**Buka Tiket di Konsol Unit**]({{ route('unit.disposisi.show', ['unit' => $unit->kode, 'kode' => $pengaduan->kode_tiket]) }})

---

Salam,
**Sistem Pengaduan RSUD Dr. Soetomo**  
*Email ini dikirim otomatis, mohon tidak dibalas.*
@endcomponent