<?php
$pesan = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pesan = "Data dummy instansi '" . htmlspecialchars($_POST['nama_instansi']) . "' berhasil ditambahkan! (Simulasi)";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Instansi Mitra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-4">
        <div class="card shadow-sm col-md-8 mx-auto">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Tambah Instansi Mitra</h5>
            </div>
            <div class="card-body">
                <?php if ($pesan): ?>
                    <div class="alert alert-success"><?= $pesan; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Nama Instansi / Perusahaan</label>
                        <input type="text" name="nama_instansi" class="form-control" placeholder="Contoh: PT Telkom Indonesia" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="3" placeholder="Jl. Pemuda No. 12..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penanggung Jawab / Pembimbing</label>
                        <input type="text" name="pembimbing_lapangan" class="form-control" placeholder="Contoh: Budi Santoso, S.Kom." required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. Telepon / HP</label>
                            <input type="text" name="telepon" class="form-control" placeholder="08123456789" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kuota Siswa</label>
                            <input type="number" name="kuota" class="form-control" placeholder="5" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Data Dummy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>