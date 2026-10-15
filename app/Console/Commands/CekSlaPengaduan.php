<?php

namespace App\Console\Commands;

use App\Models\Pengaduan;
use App\Notifications\PengaduanSlaLewat;
use App\Notifications\PengaduanSlaMendekati;
use App\Support\Sla;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('pengaduan:cek-sla {--force : Jalankan tanpa konfirmasi} {--kirim-notifikasi : Kirim notifikasi ke PIC unit}')]
#[Description('Cek SLA pengaduan dan kirim notifikasi nudge/escalation ke unit')]
class CekSlaPengaduan extends Command
{
    public function handle(): int
    {
        $kirimNotifikasi = $this->option('kirim-notifikasi');

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
            ]
        );

        return self::SUCCESS;
    }
}
