<?php

namespace App\Console\Commands;

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPendingAccounts extends Command
{
    protected $signature = 'epkl:cleanup-pending-accounts';

    protected $description = 'Clean up unverified pending accounts older than seven days.';

    public function handle(): int
    {
        $cutoff = now()->subDays(7);
        $cleaned = 0;
        $skipped = 0;

        DB::table('pending_account_verifications')
            ->select('id')
            ->where(function ($query) use ($cutoff): void {
                $query->whereNull('created_at')
                    ->orWhere('created_at', '<=', $cutoff);
            })
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use ($cutoff, &$cleaned, &$skipped): void {
                foreach ($reservations as $candidate) {
                    $wasCleaned = DB::transaction(function () use ($candidate, $cutoff): bool {
                        $reservation = DB::table('pending_account_verifications')
                            ->where('id', $candidate->id)
                            ->whereNotNull('created_at')
                            ->where('created_at', '<=', $cutoff)
                            ->lockForUpdate()
                            ->first();

                        if (! $reservation) {
                            return false;
                        }

                        $user = User::query()->lockForUpdate()->find($reservation->user_id);
                        if (
                            ! $user
                            || $user->status !== 'pending'
                            || $user->email_terverifikasi_at !== null
                        ) {
                            return false;
                        }

                        if ($reservation->siswa_id !== null && $reservation->guru_id === null) {
                            $profile = Siswa::query()->lockForUpdate()->find($reservation->siswa_id);
                            if ($profile && (int) $profile->user_id === (int) $user->getKey()) {
                                $profile->user_id = null;
                                $profile->save();
                            }
                        } elseif ($reservation->guru_id !== null && $reservation->siswa_id === null) {
                            $profile = Guru::query()->lockForUpdate()->find($reservation->guru_id);
                            if ($profile && (int) $profile->user_id === (int) $user->getKey()) {
                                $profile->user_id = null;
                                $profile->save();
                            }
                        } else {
                            return false;
                        }

                        DB::table('pending_account_verifications')
                            ->where('id', $reservation->id)
                            ->delete();

                        $deleted = User::query()
                            ->whereKey($user->getKey())
                            ->where('status', 'pending')
                            ->whereNull('email_terverifikasi_at')
                            ->delete();

                        if ($deleted !== 1) {
                            throw new \RuntimeException(
                                'Akun pending berubah saat proses cleanup; transaksi dibatalkan.'
                            );
                        }

                        return true;
                    }, attempts: 3);

                    if ($wasCleaned) {
                        $cleaned++;
                    } else {
                        $skipped++;
                    }
                }
            });

        $this->info("Cleaned: $cleaned; skipped: $skipped.");

        return self::SUCCESS;
    }
}
