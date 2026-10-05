<?php
// URL default tiap menu
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

$defaultMenuUrls = [
    'dashboard'       => $base . '/',
    'data-siswa'      => $base . '/?page=data-siswa',
    'guru-pembimbing' => $base . '/?page=data-guru',
    'instansi-mitra'  => $base . '/?page=data-mitra',
    'manajemen-user'  => $base . '/?page=manajemen-admin',
    'laporan-rekap'   => $base . '/?page=laporan-rekap',
];

$adminMenuUrls = isset($adminMenuUrls) && is_array($adminMenuUrls) ? $adminMenuUrls : [];
$adminMenuUrls = array_merge($defaultMenuUrls, $adminMenuUrls);

$dashboardUrl = $adminMenuUrls['dashboard'];
$dashboardUrl = is_string($dashboardUrl) && $dashboardUrl !== '' ? $dashboardUrl : '/';

$activePage = $activePage ?? '';

$menuItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fas fa-fw fa-chart-line'],
    ['key' => 'data-siswa', 'label' => 'Data Siswa', 'icon' => 'fas fa-fw fa-user-graduate'],
    ['key' => 'guru-pembimbing', 'label' => 'Guru Pembimbing', 'icon' => 'fas fa-fw fa-chalkboard-teacher'],
    ['key' => 'instansi-mitra', 'label' => 'Instansi Mitra', 'icon' => 'fas fa-fw fa-building'],
    ['key' => 'manajemen-user', 'label' => 'Manajemen User', 'icon' => 'fas fa-fw fa-users-cog'],
    ['key' => 'laporan-rekap', 'label' => 'Laporan Rekap', 'icon' => 'fas fa-fw fa-chart-bar'],
];
?>
<ul class="navbar-nav sidebar sidebar-light accordion sidebar-epkl" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center py-3"
        href="<?= htmlspecialchars($dashboardUrl) ?>">
        <span class="sidebar-brand-icon">
            <i class="fas fa-graduation-cap" aria-hidden="true"></i>
        </span>
        <span class="sidebar-brand-text mx-2 text-left">
            <strong>EPKL</strong><br>
            SMK Al Fattah Nglawak
        </span>
    </a>

    <hr class="sidebar-divider my-2">

    <?php foreach ($menuItems as $item): ?>
        <?php 
            // Cek keaktifan menu (mendukung 'guru-pembimbing', 'data-guru', dan 'guru')
            if ($item['key'] === 'guru-pembimbing') {
                $isActive = in_array($activePage, ['guru-pembimbing', 'data-guru', 'guru'], true);
            } else {
                $isActive = $activePage === $item['key'];
            }
            $menuUrl = $adminMenuUrls[$item['key']] ?? '#'; 
        ?>
        <li class="nav-item<?= $isActive ? ' active' : '' ?>">
            <a class="nav-link<?= $isActive ? ' active' : '' ?>"
                href="<?= htmlspecialchars($menuUrl) ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>>
                <i class="<?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        </li>
    <?php endforeach; ?>

    <hr class="sidebar-divider d-none d-md-block">

    <li class="nav-item">
        <a class="nav-link text-danger" href="<?= htmlspecialchars($adminMenuUrls['logout'] ?? '#') ?>">
            <i class="fas fa-fw fa-sign-out-alt text-danger" aria-hidden="true"></i>
            <span>Logout</span>
        </a>
    </li>

    <li class="nav-item text-center d-none d-md-inline mt-auto mb-3">
        <button class="rounded-circle border-0" id="sidebarToggle" aria-label="Ciutkan sidebar"></button>
    </li>
</ul>