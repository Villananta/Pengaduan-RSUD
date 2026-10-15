@component('mail::layout')
@slot('subject', 'PERINGATAN KRITIS: Investigasi Lewat Batas — {{ $pengaduan->kode_tiket }}')

# 🔴 PERINGATAN KRITIS: Investigasi Lewat Batas

Halo **{{ $unit->pic ?? 'PIC Unit' }}**,

**Tiket berikut telah MELEWATI batas waktu investigasi** dan memerlukan tindakan segera!

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

## Status SLA KRITIS
| Item | Detail |
|------|--------|
| **Batas Investigasi** | {{ config('pengaduan.sla.investigasi_hari_kerja') }} hari kerja |
| **Hari ke-** | {{ \App\Support\Sla::hariKe($pengaduan->created_at) }} |
| **Lewat Batas** | **{{ $lewatHari }} hari kerja** |
| **Zona SLA** | {{ $pengaduan->zonaSla()->label() }} |

> 🔴 **Tiket ini sudah {{ $lewatHari }} hari kerja MELEWATI batas investigasi!**

---

## Tindakan SEGERA Diperlukan
1. **Segera buka tiket** di konsol unit
2. **Lanjutkan investigasi** atau kirim jawaban ke humas
3. Tiket ini akan diekskalasi ke **Kepala Unit & Wadir Pelayanan Medik** jika tidak ditindaklanjuti dalam 24 jam

[**BUKA TIKET SEKARANG**]({{ route('unit.disposisi.show', ['unit' => $unit->kode, 'kode' => $pengaduan->kode_tiket]) }})

---

Salam,
**Sistem Pengaduan RSUD Dr. Soetomo**  
*Email ini dikirim otomatis, mohon tidak dibalas.*
@endcomponent