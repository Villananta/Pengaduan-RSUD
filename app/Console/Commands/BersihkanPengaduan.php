<?php

namespace App\Console\Commands;

use App\Models\Pengaduan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Hapus data pribadi pengaduan yang sudah melewati masa retensi.
 *
 * Data kesehatan tidak boleh disimpan tanpa batas waktu. Command ini
 * menghapus tiket lama beserta lampiran, pesan, dan riwayatnya, lalu
 * melaporkan berapa di antaranya yang belum selesai ditangani.
 */
class BersihkanPengaduan extends Command
{
    protected $signature = 'pengaduan:bersihkan
                            {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Hapus pengaduan yang melewati masa retensi data pribadi';

    public function handle(): int
    {
        $retensi = (int) config('pengaduan.retensi_hari', 730);

        if ($retensi <= 0) {
            $this->error('config pengaduan.retensi_hari harus lebih besar dari nol.');

            return self::FAILURE;
        }

        $batas = now()->subDays($retensi);

        $total = Pengaduan::query()->where('created_at', '<', $batas)->count();

        if ($total === 0) {
            $this->info('Tidak ada pengaduan yang melewati retensi '.$retensi.' hari.');

            return self::SUCCESS;
        }

        $belumSelesai = Pengaduan::query()
            ->where('created_at', '<', $batas)
            ->aktif()
            ->count();

        $this->line(sprintf(
            '%d pengaduan lebih tua dari %s akan dihapus beserta lampirannya (%d di antaranya belum selesai).',
            $total,
            $batas->toDateString(),
            $belumSelesai,
        ));

        if (! $this->option('force') && ! $this->confirm('Lanjutkan penghapusan?')) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        $terhapus = 0;
        $berkas = 0;
        $disk = Storage::disk(config('pengaduan.disk_lampiran', 'public'));

        Pengaduan::query()
            ->where('created_at', '<', $batas)
            ->with('pesan')
            ->chunkById(100, function ($pengaduans) use (&$terhapus, &$berkas, $disk): void {
                foreach ($pengaduans as $pengaduan) {
                    foreach ($pengaduan->daftarLampiran() as $path) {
                        $disk->delete($path);
                        $berkas++;
                    }

                    foreach ($pengaduan->pesan as $pesan) {
                        if ($pesan->adaLampiran()) {
                            $disk->delete($pesan->lampiran);
                            $berkas++;
                        }
                    }

                    $pengaduan->delete();
                    $terhapus++;
                }
            });

        $this->info("Selesai. {$terhapus} pengaduan dan {$berkas} berkas lampiran dihapus.");

        return self::SUCCESS;
    }
}
