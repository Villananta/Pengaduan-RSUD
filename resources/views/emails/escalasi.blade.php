@component('mail::layout')
@slot('subject', $jenisEscalasi === 'nudge_abaikan' 
    ? 'ESKALASI: Nudge Diabaikan > 24 Jam — {{ $pengaduan->kode_tiket }}' 
    : 'ESKALASI KRITIS: SLA Lewat Batas — {{ $pengaduan->kode_tiket }}')

# {{ $jenisEscalasi === 'nudge_abaikan' 
    ? '🔴 ESKALASI: Nudge Diabaikan > 24 Jam' 
    : '🔴 ESKALASI KRITIS: SLA Lewat Batas' }}

Halo **{{ $unit->pic ?? 'Atasan Unit' }}**,

**Tiket berikut diedukasikan ke Anda karena tidak ada tindak lanjut dari PIC unit dalam waktu yang ditentukan.**

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

@if ($jenisEscalasi === 'nudge_abaikan')
## Alasan Eskalasi
**Nudge manual dari Admin Humas diabaikan > 24 jam.**

- **Waktu nudge dikirim:** {{ $durasiJam }} jam yang lalu
- **Catatan nudge:** {{ $catatanNudge ?? 'Tidak ada catatan' }}
- **Status saat ini:** Tidak ada jawaban dari unit

> ⚠️ **PIC unit tidak merespons follow up manual dari Admin Humas dalam 24 jam.**
@else
## Alasan Eskalasi
**SLA investigasi sudah lewat batas.**

- **Batas investigasi:** {{ config('pengaduan.sla.investigasi_hari_kerja') }} hari kerja
- **Hari ke-:** {{ \App\Support\Sla::hariKe($pengaduan->created_at) }}
- **Lewat batas:** **{{ $lewatHari ?? ($durasiJam / 24) }} hari kerja**
- **Zona SLA:** {{ $pengaduan->zonaSla()->label() }}

> 🔴 **Tiket ini sudah {{ $lewatHari ?? ($durasiJam / 24) }} hari kerja MELEWATI batas investigasi.**
@endif

---

## Detail SLA
- **Hari ke-:** {{ \App\Support\Sla::hariKe($pengaduan->created_at) }}
- **Sisa/Jumlah Hari:** {{ $jenisEscalasi === 'nudge_abaikan' ? $durasiJam.' jam sejak nudge' : $lewatHari.' hari lewat batas' }}
- **Zona SLA:** {{ $pengaduan->zonaSla()->label() }}

---

## Tindakan Diperlukan
Segera lakukan **salah satu** dari berikut:

1. **Buka tiket** dan lanjutkan investigasi / kirim jawaban ke humas
2. **Koordinasi dengan PIC unit** untuk memastikan tiket dikerjakan
3. **Perbarui status tiket** sesuai kemajuan penanganan

[**BUKA TIKET DI KONSOL UNIT**]({{ route('unit.disposisi.show', ['unit' => $unit->kode, 'kode' => $pengaduan->kode_tiket]) }})

---

Salam,
**Sistem Pengaduan RSUD Dr. Soetomo**  
*Email ini dikirim otomatis sebagai eskalasi otomatis, mohon tidak dibalas.*
@endcomponent