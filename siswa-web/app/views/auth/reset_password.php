<?php
$message = is_string($message ?? null) ? $message : null;
$messageType = ($messageType ?? '') === 'success' ? 'success' : 'error';
$completed = ($completed ?? false) === true;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur Ulang Kata Sandi | E-PKL</title>
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
        <h1>Atur Ulang Kata Sandi</h1>
        <?php if ($message !== null): ?><p class="message" role="status"><?= e($message) ?></p><?php endif; ?>
        <?php if (!$completed): ?>
            <form method="post" action="<?= e(url('auth/resetPassword')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="email" value="<?= e($email) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <label for="password_baru">Kata sandi baru</label>
                <input id="password_baru" name="password_baru" type="password" minlength="8" autocomplete="new-password" required>
                <label for="password_baru_confirmation">Konfirmasi kata sandi baru</label>
                <input id="password_baru_confirmation" name="password_baru_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                <button type="submit">Simpan kata sandi</button>
            </form>
        <?php endif; ?>
        <a class="button" href="<?= e(url('login')) ?>">Kembali ke Login</a>
    </main>
</body>
</html>
