<?php
// 1. Panggil koneksi
require_once __DIR__ . '/../../../../../config/koneksi.php';

$urlDaftar = '?page=data-siswa';
$urlEdit   = '?page=edit&id=';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { echo "<script>alert('ID tidak valid'); window.location='$urlDaftar';</script>"; exit; }

$sql = "SELECT
            s.*,
            s.nama_lengkap AS nama_siswa,
            s.status_siswa AS status,
            k.nama AS kelas,
            (
                SELECT i.nama
                FROM penempatan_pkl p
                INNER JOIN instansi i ON i.id = p.instansi_id
                WHERE p.siswa_id = s.id
                ORDER BY p.tanggal_mulai DESC
                LIMIT 1
            ) AS nama_instansi
        FROM siswa s
        LEFT JOIN kelas k ON k.id = s.kelas_id
        WHERE s.id = ?
        LIMIT 1";
$statement = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($statement, 'i', $id);
mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);
if (!$result || mysqli_num_rows($result) === 0) {
    echo "<script>alert('Data siswa tidak ditemukan'); window.location='$urlDaftar';</script>"; exit;
}
$s = mysqli_fetch_assoc($result);
mysqli_stmt_close($statement);

$e   = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$val = static fn ($v): string => trim((string) $v) === '' ? '-' : htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$inisial = '';
foreach (array_slice(preg_split('/\s+/', trim($s['nama_siswa'])), 0, 2) as $kata) { $inisial .= mb_strtoupper(mb_substr($kata, 0, 1)); }
$statusSiswa = $s['status'] ?? '';
$selesai = $statusSiswa === 'lulus';
$keluar = $statusSiswa === 'keluar';
$statusLabel = [
    'aktif' => 'Aktif',
    'lulus' => 'Lulus',
    'keluar' => 'Keluar',
][$statusSiswa] ?? 'Tidak diketahui';

$pageTitle  = 'Detail Data Siswa';
$activePage = 'data-siswa';

ob_start();
?>
<style>
    .page-heading { display: none !important; }
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
    .dt-btn-del { background: #fff; border-color: #fecdd3; color: #e11d48; }
    .dt-btn-del:hover { background: #fff1f2; }
    .dt-card { background: #fff; border: 1px solid #eef2f7; border-radius: 16px; box-shadow: 0 1px 3px rgba(16,24,40,.06); padding: 1.4rem 1.6rem; height: 100%; }
    .dt-profile { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .dt-avatar { width: 60px; height: 60px; border-radius: 50%; background: #e6effc; color: #1d56c9; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; font-weight: 800; flex-shrink: 0; }
    .dt-name { font-size: 1.1rem; font-weight: 800; color: #0f1d3a; margin: 0; }
    .dt-meta { font-size: .78rem; color: #64748b; margin-top: .15rem; }
    .dt-pill { margin-left: auto; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; border-radius: 999px; font-size: .68rem; font-weight: 700; padding: .25rem .9rem; }
    .dt-pill.selesai { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }
    .dt-pill.nonaktif { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
    .dt-sec { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; margin: 0 0 .5rem; }
    .dt-row { display: flex; gap: 1rem; padding: .65rem 0; border-bottom: 1px solid #f1f5f9; font-size: .8rem; }
    .dt-row:last-child { border-bottom: 0; }
    .dt-row .k { width: 170px; flex-shrink: 0; color: #64748b; }
    .dt-row .v { color: #1e293b; font-weight: 600; word-break: break-word; }
</style>

<div class="dt">
    <div class="dt-top">
        <div>
            <div class="dt-crumb">
                <a href="<?= $e($urlDaftar) ?>">Data Siswa</a><span class="sep">/</span>Detail<span class="sep">/</span><span class="now"><?= $e($s['nama_siswa']) ?></span>
            </div>
            <h1 class="dt-title">Detail Data Siswa</h1>
            <p class="dt-sub">Informasi lengkap biodata siswa, penempatan PKL, dan status bimbingan magang</p>
        </div>
        <div class="dt-actions">
            <a href="<?= $e($urlDaftar) ?>" class="dt-btn dt-btn-back"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
            <a href="<?= $e($urlEdit . $id) ?>" class="dt-btn dt-btn-edit"><i class="fas fa-pen mr-2"></i>Edit</a>
        </div>
    </div>

    <div class="dt-card mb-4" style="height:auto;">
        <div class="dt-profile">
            <div class="dt-avatar"><?= $e($inisial) ?></div>
            <div>
                <h2 class="dt-name"><?= $e($s['nama_siswa']) ?></h2>
                <div class="dt-meta">NIS: <?= $e($s['nis'] ?? '-') ?> &middot; <?= $e($s['kelas'] ?? '-') ?></div>
            </div>
            <span class="dt-pill<?= $selesai ? ' selesai' : ($keluar ? ' nonaktif' : '') ?>"><?= $e($statusLabel) ?></span>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="dt-card">
                <div class="dt-sec">1. Informasi Pribadi &amp; Akademik Siswa</div>
                <div class="dt-row"><div class="k">Nama Lengkap</div><div class="v"><?= $val($s['nama_siswa']) ?></div></div>
                <div class="dt-row"><div class="k">NIS</div><div class="v"><?= $val($s['nis'] ?? '-') ?></div></div>
                <div class="dt-row"><div class="k">Kelas &amp; Keahlian</div><div class="v"><?= $val($s['kelas'] ?? '-') ?></div></div>
                <div class="dt-row"><div class="k">Jenis Kelamin</div><div class="v"><?= $val($s['jenis_kelamin'] ?? '-') ?></div></div>
                <div class="dt-row"><div class="k">WhatsApp Siswa</div><div class="v"><?= $val($s['no_hp'] ?? '-') ?></div></div>
                <div class="dt-row"><div class="k">Alamat</div><div class="v"><?= $val($s['alamat'] ?? '-') ?></div></div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="dt-card">
                <div class="dt-sec">2. Penempatan Instansi &amp; Pembimbing</div>
                <div class="dt-row"><div class="k">Instansi Mitra DU-DI</div><div class="v"><?= $val($s['nama_instansi'] ?? '-') ?></div></div>
                
                <div class="dt-sec" style="margin-top:1.25rem;">3. Status Siswa</div>
                <div class="dt-row"><div class="k">Status</div><div class="v"><?= $e($statusLabel) ?></div></div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';