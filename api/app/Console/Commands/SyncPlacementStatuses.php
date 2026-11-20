<?php

namespace App\Console\Commands;

use App\Models\PenempatanPkl;
use App\Models\RiwayatStatusPenempatan;
use App\Services\PenempatanPklService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPlacementStatuses extends Command
{
    protected $signature = 'epkl:sync-placement-statuses';

    protected $description = 'Update placement statuses based on their scheduled dates.';

    public function handle(): int
    {
        $today = CarbonImmutable::today('Asia/Jakarta')->toDateString();
        $updated = 0;

        PenempatanPkl::query()
            ->whereIn('status', [
                PenempatanPklService::STATUS_BELUM_MULAI,
                PenempatanPklService::STATUS_AKTIF,
            ])
            ->where(function ($query) use ($today): void {
                $query->whereDate('tanggal_selesai', '<', $today)
                    ->orWhere(function ($startQuery) use ($today): void {
                        $startQuery->where('status', PenempatanPklService::STATUS_BELUM_MULAI)
                            ->whereDate('tanggal_mulai', '<=', $today)
                            ->whereDate('tanggal_selesai', '>=', $today);
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function ($placements) use ($today, &$updated): void {
                foreach ($placements as $candidate) {
                    $changed = DB::transaction(function () use ($candidate, $today): bool {
                        $placement = PenempatanPkl::query()
                            ->lockForUpdate()
                            ->find($candidate->id);

                        if (
                            ! $placement
                            || ! in_array($placement->status, [
                                PenempatanPklService::STATUS_BELUM_MULAI,
                                PenempatanPklService::STATUS_AKTIF,
                            ], true)
                        ) {
                            return false;
                        }

                        $oldStatus = $placement->status;
                        if (CarbonImmutable::parse($placement->tanggal_selesai)->toDateString() < $today) {
                            $newStatus = PenempatanPklService::STATUS_SELESAI;
                            $reasonCode = 'otomatis_selesai';
                        } elseif (
                            $oldStatus === PenempatanPklService::STATUS_BELUM_MULAI
                            && CarbonImmutable::parse($placement->tanggal_mulai)->toDateString() <= $today
                        ) {
                            $newStatus = PenempatanPklService::STATUS_AKTIF;
                            $reasonCode = 'otomatis_mulai';
                        } else {
                            return false;
                        }

                        $placement->status = $newStatus;
                        $placement->save();

                        RiwayatStatusPenempatan::query()->create([
                            'penempatan_id' => $placement->id,
                            'status_lama' => $oldStatus,
                            'status_baru' => $newStatus,
                            'alasan_kode' => $reasonCode,
                            'alasan_detail' => null,
                            'admin_user_id' => null,
                            'created_at' => now(),
                        ]);

                        return true;
                    }, 3);

                    if ($changed) {
                        $updated++;
                    }
                }
            });

        $this->info("Updated placement statuses: $updated.");

        return self::SUCCESS;
    }
}
