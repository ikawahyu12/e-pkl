<?php
$page = $_GET['page'] ?? 'dashboard';

switch ($page) {

    // ==========================================
    // MANAJEMEN USER (ADMIN, GURU, SISWA)
    // ==========================================
    // --- Admin ---
    case 'manajemen-admin':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/admin/index.php';
        break;

    case 'create-admin':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/admin/create.php';
        break;

    case 'edit-admin':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/admin/edit.php';
        break;

    case 'detail-admin':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/admin/detail.php';
        break;

    // --- Guru ---
    case 'manajemen-guru':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/guru/index.php';
        break;

    case 'create-guru':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/guru/create.php';
        break;

    case 'edit-guru':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/guru/edit.php';
        break;

    case 'detail-guru':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/guru/detail.php';
        break;

    // --- Siswa ---
    case 'manajemen-siswa':
    case 'manajemen-user': // Alias jika dipanggil generik
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/siswa/index.php';
        break;

    case 'create-siswa':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/siswa/create.php';
        break;

    case 'edit-siswa':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/siswa/edit.php';
        break;

    case 'detail-siswa':
        require dirname(__DIR__) . '/app/Views/admin/manajemen_user/siswa/detail.php';
        break;

    // ==========================================
    // DATA LAINNYA
    // ==========================================
    case 'data-mitra':
        require dirname(__DIR__) . '/app/Views/admin/instansi/index.php';
        break;

    case 'data-guru':
        require dirname(__DIR__) . '/app/Views/admin/guru/index.php';
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

        // ==========================================
    // DATA INSTANSI MITRA
    // ==========================================

    case 'create-mitra':
        require dirname(__DIR__) . '/app/Views/admin/instansi/create.php';
        break;

    case 'edit-mitra':
        require dirname(__DIR__) . '/app/Views/admin/instansi/edit.php';
        break;

    case 'detail-mitra':
        require dirname(__DIR__) . '/app/Views/admin/instansi/detail.php';
        break;
}