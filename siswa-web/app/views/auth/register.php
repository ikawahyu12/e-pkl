<?php
$message = is_string($message ?? null) ? $message : null;
$messageType = ($messageType ?? '') === 'success' ? 'success' : 'error';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun Siswa | E-PKL</title>
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
        <h1>Daftar Akun Siswa</h1>
        <p>Gunakan identitas yang tercatat pada data master sekolah. Akun dapat digunakan setelah email diverifikasi.</p>
        <?php if ($message !== null): ?><p class="message" role="status"><?= e($message) ?></p><?php endif; ?>
        <form method="post" action="<?= e(url('auth/register')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <label for="nisn">NISN</label>
            <input id="nisn" name="nisn" type="text" inputmode="numeric" autocomplete="off" maxlength="20" required>
            <label for="nama_lengkap">Nama lengkap sesuai data sekolah</label>
            <input id="nama_lengkap" name="nama_lengkap" type="text" autocomplete="name" maxlength="120" required>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" maxlength="150" required>
            <label for="password">Kata sandi (minimal 12 karakter)</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required>
            <label for="password_confirmation">Ulangi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required>
            <button type="submit">Daftar dan kirim verifikasi</button>
        </form>
        <p><a href="<?= e(url('auth/resendVerification')) ?>">Kirim ulang email verifikasi</a></p>
        <p><a href="<?= e(url('login')) ?>">Kembali ke Login</a></p>
    </main>
</body>
</html>
