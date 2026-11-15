<?php
$message = is_string($message ?? null) ? $message : null;
$messageType = ($messageType ?? '') === 'success' ? 'success' : 'error';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kirim Ulang Verifikasi | E-PKL</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; color: #1f2937; background: #f3f8fc; font: 16px/1.6 Arial, sans-serif; }
        main { width: min(100%, 520px); padding: 32px; border-radius: 16px; background: #fff; box-shadow: 0 12px 36px #16324f1a; }
        label { display: block; margin: 18px 0 6px; font-weight: 700; }
        input { box-sizing: border-box; width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; }
        button, .button { display: inline-block; margin-top: 16px; border: 0; color: #fff; background: #1677ff; padding: 11px 18px; border-radius: 8px; text-decoration: none; cursor: pointer; }
        .message { padding: 12px; border-radius: 8px; background: <?= $messageType === 'success' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $messageType === 'success' ? '#166534' : '#991b1b' ?>; }
    </style>
</head>
<body>
    <main>
        <h1>Kirim Ulang Verifikasi</h1>
        <p>Jika ada akun yang menunggu verifikasi, tautan baru akan dikirim.</p>
        <?php if ($message !== null): ?><p class="message" role="status"><?= e($message) ?></p><?php endif; ?>
        <form method="post" action="<?= e(url('auth/resendVerification')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" maxlength="150" required>
            <button type="submit">Kirim ulang verifikasi</button>
        </form>
        <p><a class="button" href="<?= e(url('auth/register')) ?>">Kembali ke Registrasi</a></p>
    </main>
</body>
</html>
