<?php
$pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Dashboard Monitoring PKL';
$pageSubtitle = isset($pageSubtitle) && is_string($pageSubtitle) ? $pageSubtitle : '';
$pageStatusLabel = isset($pageStatusLabel) && is_string($pageStatusLabel) ? $pageStatusLabel : '';
$pageHeaderActions = isset($pageHeaderActions) && is_string($pageHeaderActions) ? $pageHeaderActions : '';
$activePage = isset($activePage) && is_string($activePage) ? $activePage : '';
$content = isset($content) && is_string($content) ? $content : '';
$hidePageHeading = isset($hidePageHeading) && $hidePageHeading === true;   // BARU
$extraHead = isset($extraHead) && is_string($extraHead) ? $extraHead : ''; // BARU

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$scriptDirectory = rtrim($scriptDirectory, '/');
$scriptDirectory = $scriptDirectory === '.' ? '' : $scriptDirectory;
$assetBaseUrl = isset($assetBaseUrl) && is_string($assetBaseUrl)
    ? rtrim($assetBaseUrl, '/')
    : $scriptDirectory . '/assets';

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="E-PKL Admin">
    <title><?= $e($pageTitle) ?> | E-PKL</title>
    <link rel="stylesheet" href="<?= $e($assetBaseUrl) ?>/vendor/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="<?= $e($assetBaseUrl) ?>/css/sb-admin-2.min.css">
    <style>
        body {
            color: #344054;
        }

        #content-wrapper {
            background-color: #f7f9fc;
            min-width: 0;
        }

        #content {
            min-width: 0;
        }

        .sidebar-epkl {
            background-color: #fff;
            border-right: 1px solid #e8edf2;
        }

        .sidebar-epkl .sidebar-brand,
        .sidebar-epkl .sidebar-brand:hover {
            color: #176b8b;
        }

        .sidebar-epkl .sidebar-brand-text {
            font-size: .72rem;
            line-height: 1.35;
        }

        .sidebar-epkl .nav-item .nav-link {
            color: #56616e;
            border-radius: .45rem;
            margin: .12rem .65rem;
            padding: .72rem .8rem;
            width: auto;
        }

        .sidebar-epkl .nav-item .nav-link i {
            color: #667788;
        }

        .sidebar-epkl .nav-item .nav-link.active {
            background-color: #e8f1f5;
            color: #176b8b;
            font-weight: 700;
        }

        .sidebar-epkl .nav-item .nav-link.active i {
            color: #176b8b;
        }

        .sidebar-epkl .sidebar-divider {
            border-top-color: #edf0f3;
        }

        .page-heading {
            min-height: 3rem;
        }

        .page-heading h1 {
            color: #253247;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .page-heading .page-subtitle {
            color: #7a8592;
            font-size: .82rem;
        }

        @media (max-width: 575.98px) {
            .page-heading {
                align-items: flex-start !important;
                flex-direction: column;
            }

            .page-heading-actions {
                margin-top: .75rem;
                width: 100%;
            }
        }

        @media (max-width: 767.98px) {
            .topbar {
                min-width: 0;
            }

            .topbar .navbar-nav {
                flex-shrink: 0;
            }
        }
    </style>
    <?= $extraHead ?>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php require __DIR__ . '/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php require __DIR__ . '/topbar.php'; ?>

                <main class="container-fluid">
                    <?php if (!$hidePageHeading): ?>
                    <header class="page-heading d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <div class="d-flex align-items-center flex-wrap">
                                <h1 class="h3 mb-1 mr-2"><?= $e($pageTitle) ?></h1>
                                <?php if ($pageStatusLabel !== ''): ?>
                                    <span class="badge badge-light text-info border">
                                        <i class="fas fa-circle fa-xs mr-1" aria-hidden="true"></i>
                                        <?= $e($pageStatusLabel) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($pageSubtitle !== ''): ?>
                                <p class="page-subtitle mb-0"><?= $e($pageSubtitle) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($pageHeaderActions !== ''): ?>
                            <div class="page-heading-actions d-flex align-items-center">
                                <?= $pageHeaderActions ?>
                            </div>
                        <?php endif; ?>
                    </header>
                    <?php endif; ?>

                    <?= $content ?>
                </main>
            </div>

            <?php require __DIR__ . '/footer.php'; ?>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top" aria-label="Kembali ke atas">
        <i class="fas fa-angle-up" aria-hidden="true"></i>
    </a>

    <script src="<?= $e($assetBaseUrl) ?>/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $e($assetBaseUrl) ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $e($assetBaseUrl) ?>/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= $e($assetBaseUrl) ?>/js/sb-admin-2.min.js"></script>
</body>
</html>