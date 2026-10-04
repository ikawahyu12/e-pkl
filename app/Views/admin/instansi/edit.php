<?php
// Dummy Data awal yang akan di-edit
$mitra = [
    'id' => 1,
    'nama_instansi' => 'PT Telkom Indonesia (Persero) Tbk',
    'alamat' => 'Jl. Pahlawan No. 12, Surabaya',
    'pembimbing_lapangan' => 'Budi Santoso, S.Kom.',
    'telepon' => '081234567890',
    'kuota' => 5
];

$pesan = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pesan = "Data dummy instansi berhasil diperbarui! (Simulasi)";
    $mitra['nama_instansi'] = $_POST['nama_instansi'];
    $mitra['alamat'] = $_POST['alamat'];
    $mitra['pembimbing_lapangan'] = $_POST['pembimbing_lapangan'];
    $mitra['telepon'] = $_POST['telepon'];
    $mitra['kuota'] = $_POST['kuota'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Instansi Mitra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-4">
        <div class="card shadow-sm col-md-8 mx-auto">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">Edit Instansi Mitra</h5>
            </div>
            <div class="card-body">
                <?php if ($pesan): ?>
                    <div class="alert alert-success"><?= $pesan; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Nama Instansi / Perusahaan</label>
                        <input type="text" name="nama_instansi" class="form-control" value="<?= htmlspecialchars($mitra['nama_instansi']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($mitra['alamat']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penanggung Jawab / Pembimbing</label>
                        <input type="text" name="pembimbing_lapangan" class="form-control" value="<?= htmlspecialchars($mitra['pembimbing_lapangan']); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. Telepon / HP</label>
                            <input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($mitra['telepon']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kuota Siswa</label>
                            <input type="number" name="kuota" class="form-control" value="<?= htmlspecialchars($mitra['kuota']); ?>" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-warning text-white">Perbarui Data Dummy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>