<?php
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Data Dummy Rekap Presensi Siswa
$dataPresensi = [
    ['nis' => '22231001', 'nama' => 'Ahmad Rizky', 'kelas' => 'XII RPL 1', 'mitra' => 'PT Telkom Indonesia', 'hadir' => 42, 'izin' => 1, 'sakit' => 0, 'alpa' => 0, 'persen' => '97.6%'],
    ['nis' => '22231002', 'nama' => 'Budi Santoso', 'kelas' => 'XII RPL 1', 'mitra' => 'PT Telkom Indonesia', 'hadir' => 43, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'persen' => '100%'],
    ['nis' => '22231005', 'nama' => 'Citra Dewi', 'kelas' => 'XII RPL 2', 'mitra' => 'CV Digital Creative', 'hadir' => 40, 'izin' => 2, 'sakit' => 1, 'alpa' => 0, 'persen' => '93.0%'],
    ['nis' => '22231012', 'nama' => 'Dwi Prasetyo', 'kelas' => 'XII TKJ 1', 'mitra' => 'PT Network Solution', 'hadir' => 38, 'izin' => 1, 'sakit' => 2, 'alpa' => 2, 'persen' => '88.3%'],
    ['nis' => '22231018', 'nama' => 'Eka Putri', 'kelas' => 'XII DKV 1', 'mitra' => 'Studio Animasi 3D', 'hadir' => 43, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'persen' => '100%'],
];
?>

<style>
    .page-card { background: #ffffff; border-radius: 20px; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
    .tbl-data th { background: #f8fafc; color: #64748b; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; padding: 1rem 1.25rem; border: 0; }
    .tbl-data td { padding: 1rem 1.25rem; vertical-align: middle; border-top: 1px solid #f1f5f9; font-size: .8rem; }
</style>

<div class="container-fluid px-4 py-3">

    <!-- Header & Navigasi Kembali -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="index.php" class="btn btn-sm btn-light font-weight-bold mb-2 text-muted" style="border-radius: 8px;">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Laporan
            </a>
            <h3 class="font-weight-bold text-dark mb-1">Detail Rekap Presensi Siswa</h3>
            <p class="text-muted small mb-0">Laporan kehadiran harian, izin, sakit, dan akumulasi persentase siswa PKL.</p>
        </div>
        <div class="d-flex gap-2" style="gap: .5rem;">
            <button type="button" class="btn btn-success btn-sm font-weight-bold px-3" style="border-radius: 8px;" onclick="downloadExcel()">
                <i class="fas fa-file-excel mr-1"></i> Unduh Excel
            </button>
            <button type="button" class="btn btn-primary btn-sm font-weight-bold px-3" style="border-radius: 8px;" onclick="window.print()">
                <i class="fas fa-print mr-1"></i> Cetak PDF
            </button>
        </div>
    </div>

    <!-- Tabel Data Presensi -->
    <div class="page-card overflow-hidden">
        <div class="p-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-user-check text-primary mr-2"></i> Data Kehadiran Siswa</h6>
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="form-control form-control-sm border-0 bg-light" placeholder="Cari nama / NIS..." style="border-radius: 8px; width: 200px;">
                <select class="form-control form-control-sm border-0 bg-light font-weight-bold" style="border-radius: 8px; width: 130px;">
                    <option value="">Semua Kelas</option>
                    <option>XII RPL 1</option>
                    <option>XII RPL 2</option>
                    <option>XII TKJ 1</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table tbl-data mb-0">
                <thead>
                    <tr>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Instansi Mitra (DU/DI)</th>
                        <th class="text-center text-success">Hadir</th>
                        <th class="text-center text-info">Izin</th>
                        <th class="text-center text-warning">Sakit</th>
                        <th class="text-center text-danger">Alpa</th>
                        <th class="text-right">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataPresensi as $p): ?>
                        <tr>
                            <td class="text-muted font-weight-bold"><?= $e($p['nis']) ?></td>
                            <td><strong class="text-dark"><?= $e($p['nama']) ?></strong></td>
                            <td><span class="badge badge-light text-dark font-weight-bold"><?= $e($p['kelas']) ?></span></td>
                            <td class="text-muted"><?= $e($p['mitra']) ?></td>
                            <td class="text-center font-weight-bold text-success"><?= $p['hadir'] ?></td>
                            <td class="text-center font-weight-bold text-info"><?= $p['izin'] ?></td>
                            <td class="text-center font-weight-bold text-warning"><?= $p['sakit'] ?></td>
                            <td class="text-center font-weight-bold text-danger"><?= $p['alpa'] ?></td>
                            <td class="text-right"><span class="badge badge-success px-2 py-1"><?= $p['persen'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function downloadExcel() {
        let content = "NIS\tNama Siswa\tKelas\tInstansi\tHadir\tIzin\tSakit\tAlpa\tPersentase\n";
        <?php foreach ($dataPresensi as $p): ?>
            content += "<?= $p['nis'] ?>\t<?= $p['nama'] ?>\t<?= $p['kelas'] ?>\t<?= $p['mitra'] ?>\t<?= $p['hadir'] ?>\t<?= $p['izin'] ?>\t<?= $p['sakit'] ?>\t<?= $p['alpa'] ?>\t<?= $p['persen'] ?>\n";
        <?php endforeach; ?>

        let blob = new Blob([content], { type: 'application/vnd.ms-excel' });
        let link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = 'Rekap_Presensi_Siswa_PKL.xls';
        link.click();
    }
</script>