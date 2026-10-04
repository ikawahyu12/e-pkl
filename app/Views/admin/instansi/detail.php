<?php
// Data Dummy Detail Instansi
$mitra = [
    'id' => 1,
    'nama_instansi' => 'PT Telkom Indonesia (Persero) Tbk',
    'alamat' => 'Jl. Pahlawan No. 12, Surabaya',
    'pembimbing_lapangan' => 'Budi Santoso, S.Kom.',
    'telepon' => '081234567890',
    'kuota' => 5
];

// Data Dummy Siswa Magang di Instansi Ini
$siswa_list = [
    ['nama' => 'Ahmad Rizky', 'nisn' => '0051234567', 'kelas' => 'XII RPL 1'],
    ['nama' => 'Siti Aminah', 'nisn' => '0057654321', 'kelas' => 'XII RPL 2']
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Instansi Mitra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detail Instansi Mitra</h5>
                <a href="index.php" class="btn btn-light btn-sm">Kembali</a>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="200">Nama Instansi</th>
                        <td>: <?= $mitra['nama_instansi']; ?></td>
                    </tr>
                    <tr>
                        <th>Alamat</th>
                        <td>: <?= $mitra['alamat']; ?></td>
                    </tr>
                    <tr>
                        <th>Penanggung Jawab</th>
                        <td>: <?= $mitra['pembimbing_lapangan']; ?></td>
                    </tr>
                    <tr>
                        <th>No. Telepon</th>
                        <td>: <?= $mitra['telepon']; ?></td>
                    </tr>
                    <tr>
                        <th>Kuota Siswa</th>
                        <td>: <?= $mitra['kuota']; ?> Siswa</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">Daftar Siswa PKL di Instansi Ini</h6>
            </div>
            <div class="card-body">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($siswa_list as $i => $s): ?>
                            <tr>
                                <td><?= $i + 1; ?></td>
                                <td><?= $s['nisn']; ?></td>
                                <td><?= $s['nama']; ?></td>
                                <td><?= $s['kelas']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>