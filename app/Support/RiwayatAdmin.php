<?php

namespace App\Support;

use App\Enums\StatusPengaduan;
use App\Models\Pengaduan;
use App\Models\RiwayatStatusPengaduan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Riwayat global aksi admin humas.
 *
 * Menggabungkan riwayat perubahan tahap (RiwayatStatusPengaduan)
 * dengan aksi lain yang tidak mengubah status (nudge, kasus berat, disposisi, dll)
 * supaya admin bisa melihat jejak penuh di satu halaman.
 */
final class RiwayatAdmin
{
    public function __construct(
        public readonly LengthAwarePaginator $riwayat,
        public readonly array $ringkas,
        public readonly array $filterAktif,
        public readonly string $ruteRiwayat,
    ) {}

    /**
     * Susun halaman riwayat dari parameter query.
     */
    public static function dariRequest(Request $request): self
    {
        $query = RiwayatStatusPengaduan::query()
            ->with(['pengaduan.masterUnit', 'admin'])
            ->latest('created_at');

        // Filter status asal
        if ($request->filled('dari') && $statusDari = StatusPengaduan::tryFrom($request->input('dari'))) {
            $query->where('dari', $statusDari);
        }

        // Filter status tujuan
        if ($request->filled('ke') && $statusKe = StatusPengaduan::tryFrom($request->input('ke'))) {
            $query->where('ke', $statusKe);
        }

        // Filter admin (belum ada auth, jadi semua null; simpan untuk nanti)
        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->input('admin_id'));
        }

        // Filter rentang tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('created_at', '<=', $request->input('tanggal_selesai'));
        }

        // Filter pencarian kode tiket / nama pelapor
        if ($request->filled('cari')) {
            $cari = $request->input('cari');
            $query->whereHas('pengaduan', function (Builder $q) use ($cari): void {
                $q->where('kode_tiket', 'like', "%{$cari}%")
                    ->orWhere('nama_lengkap', 'like', "%{$cari}%")
                    ->orWhere('nrm', 'like', "%{$cari}%");
            });
        }

        // Filter kasus berat
        if ($request->filled('kasus_berat')) {
            $query->where(function (Builder $q) use ($request): void {
                if ($request->boolean('kasus_berat')) {
                    $q->whereIn('catatan', [
                        Pengaduan::CATATAN_KASUS_BERAT_AKTIF,
                        Pengaduan::CATATAN_KASUS_BERAT_LEPAS,
                    ]);
                } else {
                    $q->whereNotIn('catatan', [
                        Pengaduan::CATATAN_KASUS_BERAT_AKTIF,
                        Pengaduan::CATATAN_KASUS_BERAT_LEPAS,
                    ]);
                }
            });
        }

        // Filter hanya yang punya admin_id (sudah login) vs belum
        if ($request->filled('ada_admin')) {
            if ($request->boolean('ada_admin')) {
                $query->whereNotNull('admin_id');
            } else {
                $query->whereNull('admin_id');
            }
        }

        $perHalaman = $request->filled('per_halaman') && in_array($request->integer('per_halaman'), [10, 25, 50, 100], true)
            ? $request->integer('per_halaman')
            : 25;

        $riwayat = $query->paginate($perHalaman)->withQueryString();

        $ringkas = [
            'total' => $riwayat->total(),
            'perubahan_tahap' => $riwayat->getCollection()->filter(fn ($r) => ! $r->perubahanKasusBerat())->count(),
            'kasus_berat' => $riwayat->getCollection()->filter(fn ($r) => $r->perubahanKasusBerat())->count(),
            'dengan_admin' => $riwayat->getCollection()->filter(fn ($r) => $r->admin_id !== null)->count(),
            'tanpa_admin' => $riwayat->getCollection()->filter(fn ($r) => $r->admin_id === null)->count(),
        ];

        $filterAktif = [
            'dari' => $request->input('dari'),
            'ke' => $request->input('ke'),
            'admin_id' => $request->input('admin_id'),
            'tanggal_mulai' => $request->input('tanggal_mulai'),
            'tanggal_selesai' => $request->input('tanggal_selesai'),
            'cari' => $request->input('cari'),
            'kasus_berat' => $request->input('kasus_berat'),
            'ada_admin' => $request->input('ada_admin'),
            'per_halaman' => $perHalaman,
        ];

        return new self(
            riwayat: $riwayat,
            ringkas: $ringkas,
            filterAktif: $filterAktif,
            ruteRiwayat: 'admin.riwayat.index',
        );
    }

    /**
     * Opsi filter untuk dropdown status.
     *
     * @return array<string, string>
     */
    public static function pilihanStatus(): array
    {
        $pilihan = ['' => 'Semua Status'];
        foreach (StatusPengaduan::cases() as $status) {
            $pilihan[$status->value] = $status->label();
        }

        return $pilihan;
    }

    /**
     * URL dengan parameter filter yang diubah/dihapus.
     */
    public function urlTanpa(string $kunci, ?string $nilai = null): string
    {
        $params = $this->filterAktif;
        if ($nilai === null) {
            unset($params[$kunci]);
        } else {
            $params[$kunci] = $nilai;
        }

        return route($this->ruteRiwayat, $params);
    }

    /**
     * Format baris riwayat untuk tampilan tabel.
     *
     * @return array<string, mixed>
     */
    public function baris(RiwayatStatusPengaduan $item): array
    {
        $pengaduan = $item->pengaduan;
        $adalahKasusBerat = $item->perubahanKasusBerat();

        if ($adalahKasusBerat) {
            $jenis = 'Kasus Berat';
            $labelJenis = 'bg-error-container text-error';
            $ikonJenis = 'warning';
        } elseif ($item->dariAwal()) {
            $jenis = 'Dibuat';
            $labelJenis = 'bg-primary-container text-primary';
            $ikonJenis = 'add_circle';
        } elseif ($item->dari === $item->ke) {
            $jenis = 'Disposisi / Lainnya';
            $labelJenis = 'bg-secondary-container text-secondary';
            $ikonJenis = 'swap_horiz';
        } else {
            $jenis = 'Pindah Tahap';
            $labelJenis = 'bg-brand-100 text-brand-800';
            $ikonJenis = 'swap_vert';
        }

        $adminNama = $item->admin?->name ?? '— (belum login)';

        return [
            'id' => $item->id,
            'waktu' => $item->created_at->translatedFormat('d M Y, H:i'),
            'kode_tiket' => $pengaduan->kode_tiket,
            'pelapor' => $pengaduan->nama_lengkap,
            'nrm' => $pengaduan->nrm,
            'unit' => $pengaduan->masterUnit?->namaLengkap() ?? $pengaduan->unit,
            'jenis' => $jenis,
            'label_jenis' => $labelJenis,
            'ikon_jenis' => $ikonJenis,
            'dari' => $item->dariAwal() ? '—' : $item->dari?->label() ?? '—',
            'ke' => $item->ke->label() ?? '—',
            'catatan' => $item->catatan ?? '—',
            'admin' => $adminNama,
            'url_tiket' => route('admin.pengaduan.show', $pengaduan->kode_tiket),
        ];
    }

    /**
     * Statistik ringkas untuk kartu header.
     *
     * @return array<string, mixed>
     */
    public static function statistikGlobal(): array
    {
        return [
            'total_riwayat' => RiwayatStatusPengaduan::query()->count(),
            'pengaduan_unik' => RiwayatStatusPengaduan::query()->distinct('pengaduan_id')->count('pengaduan_id'),
            'perubahan_tahap' => RiwayatStatusPengaduan::query()
                ->whereNotIn('catatan', [Pengaduan::CATATAN_KASUS_BERAT_AKTIF, Pengaduan::CATATAN_KASUS_BERAT_LEPAS])
                ->count(),
            'kasus_berat' => RiwayatStatusPengaduan::query()
                ->whereIn('catatan', [Pengaduan::CATATAN_KASUS_BERAT_AKTIF, Pengaduan::CATATAN_KASUS_BERAT_LEPAS])
                ->count(),
            'dengan_admin' => RiwayatStatusPengaduan::query()->whereNotNull('admin_id')->count(),
        ];
    }
}
