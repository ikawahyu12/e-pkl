<?php
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$flash = is_array($passwordResetFlash ?? null) ? $passwordResetFlash : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Kata Sandi Admin | E-PKL</title>
    <link rel="stylesheet" href="<?= $escape($assetBaseUrl) ?>/css/sb-admin-2.min.css">
</head>
<body class="bg-light">
    <main class="container py-5" style="max-width:520px">
        <section class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-3">Lupa Kata Sandi Admin</h1>
                <p class="text-muted">Masukkan email akun Admin. Jika terdaftar, kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'danger' ?>" role="status">
                        <?= $escape($flash['message'] ?? '') ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="<?= $escape($routerUrl) ?>?page=forgot-password">
                    <input type="hidden" name="action" value="request-password-reset">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="form-group">
                        <label for="email">Email terdaftar</label>
                        <input class="form-control" id="email" name="email" type="email" autocomplete="email" required>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Kirim tautan reset</button>
                </form>
                <p class="text-center mt-3 mb-0"><a href="<?= $escape($routerUrl) ?>?page=login">Kembali ke Login</a></p>
            </div>
        </section>
    </main>
</body>
</html>
