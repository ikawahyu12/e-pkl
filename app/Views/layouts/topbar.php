<?php
$academicYearLabel = isset($academicYearLabel) && is_string($academicYearLabel)
    ? $academicYearLabel
    : 'TA 2026/2027 Genap';
$adminUserName = isset($adminUserName) && is_string($adminUserName) ? $adminUserName : 'Admin Utama';
$adminUserRole = isset($adminUserRole) && is_string($adminUserRole) ? $adminUserRole : 'Super Admin';
?>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow-sm">
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" aria-label="Buka atau ciutkan sidebar">
        <i class="fa fa-bars" aria-hidden="true"></i>
    </button>

    <form class="form-inline d-none d-sm-inline-block" role="search">
        <div class="input-group">
            <input class="form-control bg-light border-0 small"
                   type="search"
                   placeholder="Cari siswa, instansi, NISN..."
                   aria-label="Cari siswa, instansi, atau NISN">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit" aria-label="Cari">
                    <i class="fas fa-search fa-sm" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </form>

    <ul class="navbar-nav ml-auto align-items-center">
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="academicYearDropdown" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="far fa-calendar-alt fa-fw text-info mr-1" aria-hidden="true"></i>
                <span class="small d-none d-sm-inline"><?= $e($academicYearLabel) ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow" aria-labelledby="academicYearDropdown">
                <span class="dropdown-item-text small text-muted">Tahun ajaran aktif</span>
                <span class="dropdown-item-text font-weight-bold"><?= ($academicYearLabel) ?></span>
            </div>
        </li>

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Notifikasi">
                <i class="fas fa-bell fa-fw" aria-hidden="true"></i>
                <span class="badge badge-danger badge-counter" aria-hidden="true">&nbsp;</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow" aria-labelledby="alertsDropdown">
                <span class="dropdown-item-text small text-muted">Notifikasi</span>
                <span class="dropdown-item-text small">Daftar notifikasi akan ditampilkan di sini.</span>
            </div>
        </li>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="adminUserDropdown" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small text-right">
                    <strong class="d-block"><?= $e($adminUserName) ?></strong>
                    <span><?= $e($adminUserRole) ?></span>
                </span>
                <span class="img-profile rounded-circle bg-light text-info d-inline-flex align-items-center justify-content-center"
                      aria-hidden="true">
                    <i class="fas fa-user"></i>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow" aria-labelledby="adminUserDropdown">
                <span class="dropdown-item-text small font-weight-bold"><?= $e($adminUserName) ?></span>
                <span class="dropdown-item-text small text-muted"><?= $e($adminUserRole) ?></span>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?= $e($adminMenuUrls['logout'] ?? '#') ?>">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400" aria-hidden="true"></i>
                    Logout
                </a>
            </div>
        </li>
    </ul>
</nav>
