<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Email E-PKL</title>
</head>
<body style="margin:0;padding:24px;background:#f3f6fa;font-family:Arial,sans-serif;color:#243247">
    <main style="max-width:560px;margin:0 auto;padding:32px;background:#fff;border-radius:12px">
        <h1 style="font-size:22px">Verifikasi Email E-PKL</h1>
        <p>Gunakan tombol berikut untuk memverifikasi email akun {{ $roleLabel }}.</p>
        <p>Tautan berlaku selama {{ $expiresInMinutes }} menit dan hanya dapat digunakan satu kali.</p>
        <p>
            <a href="{{ $verificationUrl }}" style="display:inline-block;padding:12px 20px;background:#1677ff;color:#fff;text-decoration:none;border-radius:8px">
                Verifikasi Email
            </a>
        </p>
        <p>Jika Anda tidak membuat akun, abaikan email ini.</p>
    </main>
</body>
</html>
