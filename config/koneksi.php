<?php
// ==========================================
// KONEKSI DATABASE - db_mitra_sekolah
// PHP Native + MySQLi + Laragon
// ==========================================

$host     = "localhost";
$user     = "root";
$password = "";                     // Laragon default kosong
$database = "db_mitra_sekolah";     // sesuai file SQL Anda

// Buat koneksi
$koneksi = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$koneksi) {
    die("❌ Koneksi gagal: " . mysqli_connect_error());
}

// Set charset UTF8MB4 (sesuai database Anda)
mysqli_set_charset($koneksi, "utf8mb4");

// Set timezone (opsional)
date_default_timezone_set('Asia/Jakarta');

// ==========================================
// (Opsional) Test koneksi — hapus jika sudah OK
// echo "✅ Terhubung ke database db_mitra_sekolah";
// ==========================================
?>