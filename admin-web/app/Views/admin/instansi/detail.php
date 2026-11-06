<?php
/**
 * Admin > Data Instansi > Detail Instansi Mitra
 * Letak file: app/Views/admin/instansi/detail.php
 */

// 1. Panggil koneksi database
require_once __DIR__ . '/../../../../../config/koneksi.php';

$urlDaftar = '?page=data-mitra';
$id_instansi = (int) ($_GET['id'] ?? 0);

if ($id_instansi <= 0) {
    echo "<script>alert('ID Instansi tidak valid!'); window.location='$urlDaftar';</script>";
    exit;
}

// ==========================================
// 2. AMBIL DATA INSTANSI
// ==========================================
$stmtInstansi = mysqli_prepare(
    $koneksi,
    'SELECT
        id,
        nama AS nama_instansi,
        jenis AS jenis_instansi,
        alamat,
        telepon,
        email,
        nama_penanggung_jawab AS penanggung_jawab,
        kuota_siswa AS kuota,
        status
     FROM instansi
     WHERE id = ?
     LIMIT 1'
);
if ($stmtInstansi === false) {
    error_log('Gagal menyiapkan detail instansi: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Detail instansi tidak dapat dimuat.');
}
mysqli_stmt_bind_param($stmtInstansi, 'i', $id_instansi);
$instansiQueryExecuted = mysqli_stmt_execute($stmtInstansi);
$queryInstansi = $instansiQueryExecuted ? mysqli_stmt_get_result($stmtInstansi) : false;
if (!$instansiQueryExecuted || !$queryInstansi) {
    error_log('Gagal memuat detail instansi: ' . mysqli_stmt_error($stmtInstansi));
    mysqli_stmt_close($stmtInstansi);
    http_response_code(500);
    exit('Detail instansi tidak dapat dimuat.');
}
if (mysqli_num_rows($queryInstansi) === 0) {
    mysqli_stmt_close($stmtInstansi);
    echo "<script>alert('Data instansi tidak ditemukan!'); window.location='$urlDaftar';</script>";
    exit;
}
$instansi = mysqli_fetch_assoc($queryInstansi);
mysqli_stmt_close($stmtInstansi);

// ==========================================
// 3. AMBIL DATA SISWA MAGANG DI INSTANSI INI
// ==========================================
$siswaList = [];
$sqlSiswa = "SELECT
                s.id AS id_siswa,
                COALESCE(s.nis, s.nisn) AS nis,
                s.nama_lengkap AS nama_siswa,
                k.nama AS kelas,
                s.no_hp,
                s.status_siswa AS status
            FROM penempatan_pkl p
            INNER JOIN siswa s ON s.id = p.siswa_id
            LEFT JOIN kelas k ON k.id = s.kelas_id
            WHERE p.instansi_id = ?
                AND p.status = 'aktif'
            ORDER BY s.nama_lengkap ASC";

$stmtSiswa = mysqli_prepare($koneksi, $sqlSiswa);
if ($stmtSiswa === false) {
    error_log('Gagal menyiapkan daftar siswa instansi: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Daftar siswa instansi tidak dapat dimuat.');
}
mysqli_stmt_bind_param($stmtSiswa, 'i', $id_instansi);
if (!mysqli_stmt_execute($stmtSiswa)) {
    error_log('Gagal memuat daftar siswa instansi: ' . mysqli_stmt_error($stmtSiswa));
    mysqli_stmt_close($stmtSiswa);
    http_response_code(500);
    exit('Daftar siswa instansi tidak dapat dimuat.');
}
$resultSiswa = mysqli_stmt_get_result($stmtSiswa);
while ($row = mysqli_fetch_assoc($resultSiswa)) {
    $siswaList[] = $row;
}
mysqli_stmt_close($stmtSiswa);

// Hitung jumlah siswa terisi
$terisi = count($siswaList);
$kuota  = (int) ($instansi['kuota'] ?? 0);
$isPenuh = $kuota > 0 && $terisi >= $kuota;

$e   = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$val = static fn ($v): string => trim((string) $v) === '' ? '-' : htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$pageTitle  = 'Detail Instansi Mitra';
$activePage = 'instansi-mitra';

ob_start();
?>
<style>
    .page-heading { display: none !important; }
    
    /* CSS Tombol & Header */
    .dt-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .dt-crumb { font-size: .72rem; color: #64748b; margin-bottom: .45rem; }
    .dt-crumb a { color: #64748b; text-decoration: none; }
    .dt-crumb .sep { margin: 0 .3rem; }
    .dt-crumb .now { color: #1e293b; }
    .dt-title { font-size: 1.45rem; font-weight: 800; color: #0f1d3a; margin: 0; }
    .dt-sub { font-size: .8rem; color: #64748b; margin: .25rem 0 0; }
    .dt-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .dt-btn { display: inline-flex; align-items: center; height: 36px; padding: 0 1rem; border-radius: 9px; font-size: .78rem; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; background: none; }
    .dt-btn-back { background: #fff; border-color: #e2e8f0; color: #334155; font-weight: 500; }
    .dt-btn-back:hover { background: #f8fafc; color: #334155; text-decoration: none; }
    .dt-btn-edit { background: #1e56a0; color: #fff; }
    .dt-btn-edit:hover { background: #184685; color: #fff; text-decoration: none; }

    /* CSS Card */
    .dt-card { background: #fff; border: 1px solid #eef2f7; border-radius: 16px; box-shadow: 0 1px 3px rgba(16,24,40,.06); padding: 1.4rem 1.6rem; height: 100%; margin-bottom: 1.5rem;}
    .dt-profile { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .dt-avatar { width: 60px; height: 60px; border-radius: 10px; background: #e6effc; color: #1d56c9; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
    .dt-name { font-size: 1.2rem; font-weight: 800; color: #0f1d3a; margin: 0; }
    .dt-meta { font-size: .8rem; color: #64748b; margin-top: .15rem; }
    .dt-pill { margin-left: auto; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; border-radius: 999px; font-size: .68rem; font-weight: 700; padding: .25rem .9rem; }
    .dt-pill.nonaktif { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
    
    /* Badge Kuota */
    .dt-badge-kuota { margin-left: auto; background: #dbe8fb; color: #2a5aa0; border-radius: 8px; padding: .4rem .8rem; font-size: .75rem; font-weight: 700; }
    .dt-badge-kuota.penuh { background: #fee2e2; color: #dc2626; }

    .dt-sec { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; margin: 0 0 .5rem; }
    .dt-row { display: flex; gap: 1rem; padding: .65rem 0; border-bottom: 1px solid #f1f5f9; font-size: .8rem; }
    .dt-row:last-child { border-bottom: 0; }
    .dt-row .k { width: 170px; flex-shrink: 0; color: #64748b; }
    .dt-row .v { color: #1e293b; font-weight: 600; word-break: break-word; }

    /* CSS Tabel Siswa */
    .dt-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .dt-table th { text-align: left; padding: .75rem; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
    .dt-table td { padding: .75rem; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
    .dt-table tr:last-child td { border-bottom: none; }
    .dt-badge { padding: .2rem .6rem; border-radius: 99px; font-size: .65rem; font-weight: 600; }
    .dt-badge-aktif { background: #dcfce7; color: #15803d; }
    .dt-badge-selesai { background: #dbeafe; color: #1d4ed8; }
</style>

<div class="dt">
    <div class="dt-top">
        <div>
            <div class="dt-crumb">
                <a href="<?= $e($urlDaftar) ?>">Data Instansi</a><span class="sep">/</span>Detail<span class="sep">/</span><span class="now"><?= $e($instansi['nama_instansi']) ?></span>
            </div>
            <h1 class="dt-title">Detail Instansi Mitra</h1>
            <p class="dt-sub">Informasi profil instansi dan daftar siswa yang sedang PKL</p>
        </div>
        <div class="dt-actions">
            <a href="<?= $e($urlDaftar) ?>" class="dt-btn dt-btn-back"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
            <a href="?page=edit-mitra&id=<?= $id_instansi ?>" class="dt-btn dt-btn-edit"><i class="fas fa-pen mr-2"></i>Edit Instansi</a>
        </div>
    </div>

    <!-- Kartu Profil Instansi -->
    <div class="dt-card" style="height:auto;">
        <div class="dt-profile">
            <div class="dt-avatar"><i class="fas fa-building"></i></div>
            <div>
                <h2 class="dt-name"><?= $e($instansi['nama_instansi']) ?></h2>
                <div class="dt-meta">
                    Jenis: <?= $val($instansi['jenis_instansi']) ?>
                </div>
            </div>
            
            <!-- TAMPILAN KUOTA DINAMIS -->
            <div class="dt-badge-kuota <?= $isPenuh ? 'penuh' : '' ?>">
                <i class="fas fa-users mr-1"></i> <?= $terisi ?> / <?= $kuota ?> Siswa
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informasi Instansi -->
        <div class="col-lg-5 mb-4">
            <div class="dt-card" style="height:auto;">
                <div class="dt-sec">Informasi Perusahaan</div>
                <div class="dt-row"><div class="k">Nama Instansi</div><div class="v"><?= $val($instansi['nama_instansi']) ?></div></div>
                <div class="dt-row"><div class="k">Jenis Instansi</div><div class="v"><?= ucfirst($val($instansi['jenis_instansi'])) ?></div></div>
                <div class="dt-row"><div class="k">Status Instansi</div><div class="v"><?= $val($instansi['status']) ?></div></div>
                <div class="dt-row"><div class="k">Penanggung Jawab</div><div class="v"><?= $val($instansi['penanggung_jawab']) ?></div></div>
                <div class="dt-row"><div class="k">No. Telepon / WA</div><div class="v"><?= $val($instansi['telepon']) ?></div></div>
                <div class="dt-row"><div class="k">Email</div><div class="v"><?= $val($instansi['email']) ?></div></div>
                <div class="dt-row"><div class="k">Alamat Lengkap</div><div class="v"><?= $val($instansi['alamat']) ?></div></div>
            </div>
        </div>

        <!-- Daftar Siswa PKL -->
        <div class="col-lg-7 mb-4">
            <div class="dt-card" style="height:auto;">
                <div class="dt-sec mb-3" style="font-size:.75rem;">Daftar Siswa PKL di Instansi Ini</div>
                
                <div class="table-responsive">
                    <table class="dt-table">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>No. HP</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($siswaList)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle mb-2" style="font-size:1.5rem; color:#cbd5e1;"></i><br>
                                        Belum ada siswa yang ditempatkan di instansi ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($siswaList as $i => $s): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= $val($s['nis']) ?></td>
                                        <td style="font-weight:600;"><?= $val($s['nama_siswa']) ?></td>
                                        <td><?= $val($s['kelas']) ?></td>
                                        <td><?= $val($s['no_hp']) ?></td>
                                        <td>
                                            <span class="dt-badge <?= $s['status'] === 'Selesai' ? 'dt-badge-selesai' : 'dt-badge-aktif' ?>">
                                                <?= $val($s['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';