<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Akun E-PKL</title>
</head>
<body style="margin:0;padding:24px;background:#f3f6fa;font-family:Arial,sans-serif;color:#243247">
    <main style="max-width:560px;margin:0 auto;padding:32px;background:#fff;border-radius:12px">
        <h1>Verifikasi Email</h1>
        <p>Konfirmasi email untuk mengaktifkan akun {{ $role === 'siswa' ? 'Siswa' : 'Guru' }} E-PKL.</p>
        <form method="post" action="{{ $verifyUrl }}">
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" style="padding:12px 20px;background:#1677ff;color:#fff;border:0;border-radius:8px;cursor:pointer">
                Verifikasi dan aktifkan akun
            </button>
        </form>
    </main>
</body>
</html>
