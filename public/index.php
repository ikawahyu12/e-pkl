<?php
$page = $_GET['page'] ?? 'dashboard';

switch ($page) {

    // ==========================================
    // MANAJEMEN USER (ADMIN, GURU, SISWA)
    // ==========================================
    case 'manajemen-admin':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/manajemen_admin.php';
        break;

    case 'manajemen-guru':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/manajemen_guru.php';
        break;

    case 'manajemen-siswa':
    case 'manajemen-user': // Alias jika dipanggil generik
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/manajemen_siswa.php';
        break;

    // ==========================================
    // DATA LAINNYA
    // ==========================================
    case 'data-mitra':
        require dirname(__DIR__) . '/app/Views/admin/instansi/index.php';
        break;

    case 'data-siswa':
        require dirname(__DIR__) . '/app/Views/admin/siswa/index.php';
        break;

    case 'dashboard':
    default:
        require dirname(__DIR__) . '/app/Views/admin/dashboard.php';
        break;

    case 'create':
        require dirname(__DIR__) . '/app/Views/admin/siswa/create.php';
        break;

    case 'edit':
        require dirname(__DIR__) . '/app/Views/admin/siswa/edit.php';
        break;

    case 'detail':
        require dirname(__DIR__) . '/app/Views/admin/siswa/detail.php';
        break;
}