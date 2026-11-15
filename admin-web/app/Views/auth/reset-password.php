<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$email = is_string($_GET['email'] ?? null) ? $_GET['email'] : '';
$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur Kata Sandi Admin | E-PKL</title>
    <link rel="stylesheet" href="<?= $escape($assetBaseUrl) ?>/css/sb-admin-2.min.css">
</head>
<body class="bg-light">
    <main class="container py-5" style="max-width:520px">
        <section class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-3">Atur Kata Sandi Admin</h1>
                <form method="post" action="<?= $escape($routerUrl) ?>?page=reset-password">
                    <input type="hidden" name="action" value="reset-password">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="email" value="<?= $escape($email) ?>">
                    <input type="hidden" name="token" value="<?= $escape($token) ?>">
                    <div class="form-group">
                        <label for="password_baru">Kata sandi baru</label>
                        <input class="form-control" id="password_baru" name="password_baru" type="password" minlength="8" autocomplete="new-password" required>
                        <small class="form-text text-muted">Minimal 8 karakter dan mengandung huruf serta angka.</small>
                    </div>
                    <div class="form-group">
                        <label for="password_baru_confirmation">Konfirmasi kata sandi baru</label>
                        <input class="form-control" id="password_baru_confirmation" name="password_baru_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Simpan kata sandi</button>
                </form>
                <p class="text-center mt-3 mb-0"><a href="<?= $escape($routerUrl) ?>?page=login">Kembali ke Login</a></p>
            </div>
        </section>
    </main>
</body>
</html>
