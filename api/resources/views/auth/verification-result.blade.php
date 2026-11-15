<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Akun E-PKL</title>
</head>
<body style="margin:0;padding:24px;background:#f3f6fa;font-family:Arial,sans-serif;color:#243247">
    <main style="max-width:560px;margin:0 auto;padding:32px;background:#fff;border-radius:12px">
        <h1>{{ $verified ? 'Email Terverifikasi' : 'Verifikasi Belum Berhasil' }}</h1>
        <p>{{ $message }}</p>
    </main>
</body>
</html>
