<?php
$page = $_GET['page'] ?? 'dashboard';

switch ($page) {


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
}
