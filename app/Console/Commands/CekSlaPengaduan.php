<?php

namespace App\Console\Commands;

use App\Models\Pengaduan;
use App\Notifications\PengaduanEscalasiNudge;
use App\Notifications\PengaduanEscalasiSla;
use App\Notifications\PengaduanSlaLewat;
use App\Notifications\PengaduanSlaMendekati;
use App\Support\Sla;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('pengaduan:cek-sla {--force : Jalankan tanpa konfirmasi} {--kirim-notifikasi : Kirim notifikasi ke PIC unit} {--eskalasi : Cek dan kirim eskalasi untuk nudge diabaikan & SLA lewat}')]
#[Description('Cek SLA pengaduan dan kirim notifikasi nudge/escalation ke unit')]
class CekSlaPengaduan extends Command
{
    public function handle(): int
    {
        $kirimNotifikasi = $this->option('kirim-notifikasi');
        $cekEskalasi = $this->option('eskalasi');

        if (! $kirimNotifikasi && ! $this->option('force')) {
            if (! $this->confirm('Perintah ini akan memeriksa SLA semua tiket aktif. Lanjutkan?')) {
                $this->warn('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        $this->info('Memeriksa SLA pengaduan aktif...');

        $pengaduanAktif = Pengaduan::query()
            ->aktif()
            ->whereNotNull('master_unit_id')
            ->with(['masterUnit.pengguna'])
            ->get();

        if ($pengaduanAktif->isEmpty()) {
            $this->info('Tidak ada pengaduan aktif yang ditugaskan ke unit.');

            return self::SUCCESS;
        }

        $mendekatiCount = 0;
        $lewatCount = 0;
        $notifMendekati = 0;
        $notifLewat = 0;
        $eskalasiNudgeCount = 0;
        $eskalasiSlaCount = 0;
        $notifEskalasiNudge = 0;
        $notifEskalasiSla = 0;

        foreach ($pengaduanAktif as $pengaduan) {
            $unit = $pengaduan->masterUnit;

            if ($unit === null || ! $unit->aktif || $unit->pengguna->isEmpty()) {
                continue;
            }

            $hariKe = Sla::hariKe($pengaduan->created_at);
            $sisaHari = Sla::sisaHariKerja($pengaduan->created_at);
            $lewatInvestigasi = Sla::lewatInvestigasi($pengaduan->created_at);

            // Peringatan mendekati batas (sisa 2 hari kerja)
            if (! $lewatInvestigasi && $sisaHari === 2) {
                $mendekatiCount++;

                if ($kirimNotifikasi) {
                    Notification::send($unit->pengguna, new PengaduanSlaMendekati($pengaduan, $unit, $sisaHari));
                    $notifMendekati++;
                }

                $this->line("  ⚠️  {$pengaduan->kode_tiket} | {$unit->namaLengkap()} | Sisa {$sisaHari} hari (Mendekati)");
            }

            // Lewat batas investigasi
            if ($lewatInvestigasi) {
                $lewatHari = Sla::hariKerjaLewat($pengaduan->created_at);
                $lewatCount++;

                if ($kirimNotifikasi) {
                    Notification::send($unit->pengguna, new PengaduanSlaLewat($pengaduan, $unit, $lewatHari));
                    $notifLewat++;
                }

                $this->line("  🔴 {$pengaduan->kode_tiket} | {$unit->namaLengkap()} | Lewat {$lewatHari} hari (LEWAT BATAS)");
            }

            // === ESKALASI ===
            if ($cekEskalasi) {
                // 1. Cek nudge yang diabaikan > 24 jam
                $nudgeTerakhir = $pengaduan->riwayatStatus()
                    ->whereRaw("catatan LIKE 'Nudge manual ke unit:%'")
                    ->latest('created_at')
                    ->first();

                if ($nudgeTerakhir !== null) {
                    $jamSejakNudge = $nudgeTerakhir->created_at->diffInHours(now());

                    if ($jamSejakNudge >= 24) {
                        $eskalasiNudgeCount++;

                        // Cek apakah sudah pernah diekskalasi untuk nudge ini
                        $sudahEskalasi = $pengaduan->riwayatStatus()
                            ->whereRaw("catatan LIKE '%eskalasi nudge%'")
                            ->where('created_at', '>=', $nudgeTerakhir->created_at)
                            ->exists();

                        if (! $sudahEskalasi && $kirimNotifikasi) {
                            $catatanNudge = str_replace('Nudge manual ke unit: ', '', $nudgeTerakhir->catatan);

                            Notification::send($unit->pengguna, new PengaduanEscalasiNudge(
                                $pengaduan,
                                $unit,
                                $jamSejakNudge,
                                $catatanNudge
                            ));
                            $notifEskalasiNudge++;

                            // Catat eskalasi ke riwayat
                            $pengaduan->riwayatStatus()->create([
                                'dari' => $pengaduan->status,
                                'ke' => $pengaduan->status,
                                'catatan' => 'Eskalasi nudge diabaikan > 24 jam ('.$jamSejakNudge.' jam). Diedukasikan ke atasan unit.',
                                'admin_id' => null,
                            ]);

                            $this->line("  🚨 Eskalasi NUDGE: {$pengaduan->kode_tiket} | {$unit->namaLengkap()} | Nudge diabaikan {$jamSejakNudge} jam");
                        }
                    }
                }

                // 2. Cek SLA lewat batas yang belum diekskalasi
                if ($lewatInvestigasi) {
                    $lewatHari = Sla::hariKerjaLewat($pengaduan->created_at);

                    // Cek apakah sudah pernah diekskalasi untuk SLA lewat
                    $sudahEskalasiSla = $pengaduan->riwayatStatus()
                        ->whereRaw("catatan LIKE '%eskalasi sla lewat%'")
                        ->latest('created_at')
                        ->first();

                    // Hanya eskalasi sekali per kelipatan 5 hari lewat (5, 10, 15, dst)
                    $harusEskalasi = $lewatHari % 5 === 0;

                    if ($harusEskalasi && ($sudahEskalasiSla === null || $sudahEskalasiSla->created_at->lt(now()->subDays(5)))) {
                        $eskalasiSlaCount++;

                        if ($kirimNotifikasi) {
                            Notification::send($unit->pengguna, new PengaduanEscalasiSla(
                                $pengaduan,
                                $unit,
                                $lewatHari
                            ));
                            $notifEskalasiSla++;

                            // Catat eskalasi ke riwayat
                            $pengaduan->riwayatStatus()->create([
                                'dari' => $pengaduan->status,
                                'ke' => $pengaduan->status,
                                'catatan' => 'Eskalasi SLA lewat batas ('.$lewatHari.' hari lewat). Diedukasikan ke atasan unit.',
                                'admin_id' => null,
                            ]);

                            $this->line("  🚨 Eskalasi SLA: {$pengaduan->kode_tiket} | {$unit->namaLengkap()} | Lewat {$lewatHari} hari (diedukasikan)");
                        }
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Selesai memeriksa {$pengaduanAktif->count()} tiket aktif.");
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Tiket mendekati batas (sisa 2 hari)', $mendekatiCount],
                ['Tiket lewat batas investigasi', $lewatCount],
                ['Notifikasi mendekati terkirim', $notifMendekati],
                ['Notifikasi lewat batas terkirim', $notifLewat],
                ['Eskalasi nudge diabaikan > 24 jam', $eskalasiNudgeCount],
                ['Notifikasi eskalasi nudge terkirim', $notifEskalasiNudge],
                ['Eskalasi SLA lewat (kelipatan 5 hari)', $eskalasiSlaCount],
                ['Notifikasi eskalasi SLA terkirim', $notifEskalasiSla],
            ]
        );

        return self::SUCCESS;
    }
}
