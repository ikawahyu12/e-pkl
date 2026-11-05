<?php
require_once __DIR__ . '/../../../../../config/koneksi.php';

$urlDaftar = '?page=data-mitra';
// Menyesuaikan dengan name="delete_id" dari form modal
$id = (int) ($_POST['delete_id'] ?? 0);

if ($id > 0) {
    mysqli_query($koneksi, "DELETE FROM instansi WHERE id_instansi = $id");
}

header("Location: $urlDaftar");
exit;