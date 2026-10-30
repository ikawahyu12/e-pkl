<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="bg-deco" aria-hidden="true"></div>
<div class="shell">
    <?php require APP_PATH . '/views/components/header.php'; ?>

    <main class="container<?= $wide ? '' : ' narrow' ?>">
        <?php if ($showBack): ?>
            <div class="page-head reveal">
                <a class="back" href="<?= e($backUrl) ?>"><?= icon('back', 18) ?><span>Kembali</span></a>
                <h1><?= e($title) ?></h1>
            </div>
        <?php endif; ?>

        <?= flash_render() ?>
        <?= $content ?>
    </main>

    <footer class="footer">Direktorat Pembinaan SMK &copy; <?= date('Y') ?></footer>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
