<?php
// 1. Panggil koneksi
require_once __DIR__ . '/../../../../../config/koneksi.php';

$urlDaftar = '?page=data-siswa';

// Ambil ID dari form hidden
$id = (int) ($_POST['id_siswa'] ?? 0);

if ($id > 0) {
    // Jalankan query hapus tanpa menampilkan alert
    $sql = "DELETE FROM siswa WHERE id_siswa = $id";
    mysqli_query($koneksi, $sql);
}

// Langsung kembalikan ke halaman daftar siswa
header("Location: $urlDaftar");
exit;