<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Kata Sandi E-PKL</title>
</head>
<body style="margin:0;padding:24px;background:#f3f6fa;font-family:Arial,sans-serif;color:#243247">
    <main style="max-width:560px;margin:0 auto;padding:32px;background:#fff;border-radius:12px">
        <h1 style="font-size:22px">Reset Kata Sandi E-PKL</h1>
        <p>Kami menerima permintaan untuk mengatur ulang kata sandi akun E-PKL Anda.</p>
        <p>Tautan reset berlaku selama {{ $expiresInMinutes }} menit dan hanya dapat digunakan satu kali.</p>
        <p>
            <a href="{{ $resetUrl }}" style="display:inline-block;padding:12px 20px;background:#1677ff;color:#fff;text-decoration:none;border-radius:8px">
                Atur Ulang Kata Sandi
            </a>
        </p>
        @if ($browserFallbackUrl)
            <p>Jika aplikasi Guru tidak terbuka, gunakan halaman reset melalui browser:</p>
            <p><a href="{{ $browserFallbackUrl }}">Buka halaman reset di browser</a></p>
        @endif
        <p>Jika Anda tidak meminta reset, abaikan email ini. Kata sandi Anda tidak akan berubah.</p>
        <p style="color:#667085;font-size:13px">E-PKL</p>
    </main>
</body>
</html>
