<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Notifikasi;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebaseNotificationService
{
    /**
     * Membuat notifikasi di database sekaligus mengirim push notification.
     */
    public function sendToUser(
        int $userId,
        string $judul,
        string $pesan,
        ?string $tipe = null,
        ?string $tautan = null
    ): Notifikasi {
        // Simpan notifikasi ke database.
        $notifikasi = Notifikasi::create([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe,
            'tautan' => $tautan,
            'dibaca_at' => null,
            'created_at' => now(),
        ]);

        // Ambil seluruh token perangkat milik user.
        $tokens = DeviceToken::where('user_id', $userId)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Jika user belum memiliki token, notifikasi tetap tersimpan.
        if (empty($tokens)) {
            return $notifikasi;
        }

        // Data tambahan untuk Flutter.
        $data = [
            'notification_id' => (string) $notifikasi->id,
            'tipe' => (string) ($tipe ?? ''),
            'tautan' => (string) ($tautan ?? ''),
        ];

        $message = CloudMessage::new()
            ->withNotification(
                FirebaseNotification::create($judul, $pesan)
            )
            ->withData($data);

        try {
            // Kirim ke seluruh perangkat user.
            app('firebase.messaging')->sendMulticast(
                $message,
                $tokens
            );
        } catch (Throwable $e) {
            // Kegagalan FCM tidak menghapus notifikasi database.
            Log::error('Gagal mengirim Firebase Notification', [
                'user_id' => $userId,
                'notification_id' => $notifikasi->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $notifikasi;
    }
}
