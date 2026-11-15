<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Kata Sandi Guru | E-PKL</title>
</head>
<body style="max-width:520px;margin:48px auto;padding:24px;font-family:Arial,sans-serif;color:#243247">
    <h1>Reset Kata Sandi Guru</h1>
    <p>Atur kata sandi baru. Tautan reset hanya dapat digunakan satu kali.</p>
    <form id="reset-form">
        <label>Email <input name="email" type="email" value="{{ $email }}" required></label><br><br>
        <input name="token" type="hidden" value="{{ $token }}">
        <label>Kata sandi baru <input name="password_baru" type="password" minlength="8" required></label><br><br>
        <label>Konfirmasi kata sandi <input name="password_baru_confirmation" type="password" minlength="8" required></label><br><br>
        <button type="submit">Simpan kata sandi</button>
    </form>
    <p id="result" role="status"></p>
    <script>
        document.getElementById('reset-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            const form = new FormData(event.currentTarget);
            const result = document.getElementById('result');
            result.textContent = 'Memproses...';

            try {
                const response = await fetch(@json($apiUrl), {
                    method: 'POST',
                    headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
                    body: JSON.stringify(Object.fromEntries(form.entries()))
                });
                const body = await response.json();
                result.textContent = body.message || 'Permintaan reset tidak dapat diproses.';
                result.style.color = response.ok ? '#167747' : '#b42318';
            } catch (_) {
                result.textContent = 'Tidak dapat terhubung ke server. Coba lagi.';
                result.style.color = '#b42318';
            }
        });
    </script>
</body>
</html>
