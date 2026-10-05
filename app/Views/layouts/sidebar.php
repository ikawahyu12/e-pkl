<?php
// URL default tiap menu
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

$defaultMenuUrls = [
    'dashboard'       => $base . '/',
    'data-siswa'      => $base . '/?page=data-siswa',
    'guru-pembimbing' => $base . '/?page=data-guru',
    'instansi-mitra'  => $base . '/?page=data-mitra',

    'manajemen-user'  => '#',

    'manajemen-admin' => $base . '/?page=manajemen-admin',
    'manajemen-siswa' => $base . '/?page=manajemen-siswa',
    'manajemen-guru'  => $base . '/?page=manajemen-guru',

    'laporan-rekap'   => '#',
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

        <img
            src="<?= $base ?>/assets/img/logo_e-pkl.png"
            alt="Logo E-PKL"
            style="
                width: 220px;
                height: 120px;
                object-fit: contain;
                display: block;
            "
        >
        
    </a>
    <hr class="sidebar-divider my-2">

    <?php foreach ($menuItems as $item): ?>

    <?php
        // ==========================================
        // KHUSUS DROPDOWN MANAJEMEN USER
        // ==========================================
        if ($item['key'] === 'manajemen-user'):

            $isUserMenuActive = in_array(
                $activePage,
                ['manajemen-admin', 'manajemen-siswa', 'manajemen-guru'],
                true
            );
    ?>

        <li class="nav-item <?= $isUserMenuActive ? 'active' : '' ?>">

            <!-- Tombol Manajemen User -->
            <a class="nav-link d-flex align-items-center"
               href="#"
               data-toggle="collapse"
               data-target="#collapseManajemenUser"
               aria-expanded="<?= $isUserMenuActive ? 'true' : 'false' ?>"
               aria-controls="collapseManajemenUser">

                <i class="<?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>

                <span>Manajemen User</span>

                <i class="fas fa-chevron-down ml-auto small"></i>
            </a>

            <!-- Dropdown -->
            <div id="collapseManajemenUser"
                 class="collapse <?= $isUserMenuActive ? 'show' : '' ?>"
                 aria-labelledby="headingManajemenUser"
                 data-parent="#accordionSidebar">

                <div class="bg-white py-2 collapse-inner rounded">

                    <a class="collapse-item <?= $activePage === 'manajemen-admin' ? 'active' : '' ?>"
                       href="<?= htmlspecialchars($adminMenuUrls['manajemen-admin']) ?>">
                        <i class="fas fa-user-shield fa-sm fa-fw mr-2 text-gray-400"></i>
                        Manajemen Admin
                    </a>

                    <a class="collapse-item <?= $activePage === 'manajemen-siswa' ? 'active' : '' ?>"
                       href="<?= htmlspecialchars($adminMenuUrls['manajemen-siswa']) ?>">
                        <i class="fas fa-user-graduate fa-sm fa-fw mr-2 text-gray-400"></i>
                        Manajemen Siswa
                    </a>

                    <a class="collapse-item <?= $activePage === 'manajemen-guru' ? 'active' : '' ?>"
                       href="<?= htmlspecialchars($adminMenuUrls['manajemen-guru']) ?>">
                        <i class="fas fa-chalkboard-teacher fa-sm fa-fw mr-2 text-gray-400"></i>
                        Manajemen Guru
                    </a>

                </div>
            </div>

        </li>

    <?php
        // ==========================================
        // MENU BIASA
        // ==========================================
        else:

            if ($item['key'] === 'guru-pembimbing') {
                $isActive = in_array(
                    $activePage,
                    ['guru-pembimbing', 'data-guru', 'guru'],
                    true
                );
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

    <?php endif; ?>

<?php endforeach; ?>
        <li class="nav-item<?= $isActive ? ' active' : '' ?>">
            <a class="nav-link<?= $isActive ? ' active' : '' ?>"
                href="<?= htmlspecialchars($menuUrl) ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>>
                <i class="<?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        </li>

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