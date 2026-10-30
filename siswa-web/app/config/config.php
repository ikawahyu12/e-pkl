<?php
define('APP_NAME', 'Pantau PKL');
define('APP_PATH', dirname(__DIR__));

// DEV_MODE = true -> halaman bisa dibuka tanpa login (untuk tahap pengembangan UI).
// Ubah ke false saat fitur login dari anggota lain sudah terhubung.
define('DEV_MODE', true);

// Deteksi base URL otomatis (XAMPP/Laragon di subfolder maupun php -S)
$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$basePath  = preg_replace('#/public$#', '', $scriptDir);

define('BASE_PATH', $basePath);
define('BASE_URL',  $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath);
define('ASSET_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . $scriptDir . '/assets');

// Konfigurasi database (diisi anggota yang mengerjakan model)
define('DB_HOST', 'localhost');
define('DB_NAME', 'epkl');
define('DB_USER', 'root');
define('DB_PASS', '');
