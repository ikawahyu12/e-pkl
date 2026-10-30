<?php
// ===== 1. BLOK PHP ATAS =====
$activePage   = 'instansi-mitra';
$pageTitle    = 'Detail Instansi Mitra';
$pageSubtitle = 'Rincian data instansi dan daftar siswa penempatan PKL.';

$pageHeaderActions = '
    <a href="?page=data-mitra" class="btn btn-sm sl-btn-back">
        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar
    </a>';

$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';
$e            = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Dummy Data Instansi (Nanti diganti query DB berdasarkan $_GET['id'])
$id = (int)($_GET['id'] ?? 1);
$mitra = [
    'id'                  => $id,
    'nama_instansi'       => 'PT Telkom Indonesia (Persero) Tbk',
    'sektor'              => 'Teknologi Informasi',
    'pembimbing_lapangan' => 'Budi Santoso, S.Kom.',
    'telepon'             => '081234567890',
    'kuota'               => 10,
    'terisi'              => 8,
    'alamat'              => 'Jl. Pahlawan No. 12, Surabaya, Jawa Timur'
];

// Dummy Data Siswa Magang di Instansi Ini
$siswaList = [
    ['id' => 1, 'nisn' => '0061829102', 'nama' => 'Ahmad Rizky Pratama', 'kelas' => 'XII RPL 1', 'guru' => 'Budi Santoso, S.Kom.', 'status' => 'Aktif'],
    ['id' => 2, 'nisn' => '0069382012', 'nama' => 'Nabila Putri',         'kelas' => 'XII RPL 2', 'guru' => 'Budi Santoso, S.Kom.', 'status' => 'Aktif'],
];

ob_start();
?>
<style>
    .sl-btn-back { background: #dbe7f6; color: #1e293b; border: 0; border-radius: 8px; padding: .5rem .9rem; font-size: .78rem; font-weight: 600; }
    .sl-btn-back:hover { background: #cddff2; color: #1e293b; }
    .sl-card-detail { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(16, 24, 40, .07); padding: 1.8rem; }
    
    .sl-detail-title { font-size: 1.1rem; font-weight: 800; color: #0f1d3a; margin-bottom: 1.2rem; display: flex; align-items: center; gap: .6rem; }
    .sl-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.2rem; }
    .sl-detail-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; }
    .sl-detail-label { font-size: .68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .03em; margin-bottom: .3rem; display: flex; align-items: center; gap: .4rem; }
    .sl-detail-value { font-size: .88rem; font-weight: 700; color: #0f1d3a; }
    
    /* Tabel Siswa */
    .sl-table { width: 100%; border-collapse: collapse; }
    .sl-table th { background: #f1f5fb; color: #3b4a5e; font-size: .66rem; font-weight: 600; text-transform: uppercase; padding: .8rem .6rem; text-align: left; }
    .sl-table td { padding: .85rem .6rem; font-size: .75rem; color: #4b586b; border-bottom: 1px solid #f0f3f8; }
    .sl-badge-aktif { background: #dbe8fb; color: #2a5aa0; padding: .12rem .6rem; border-radius: 999px; font-size: .62rem; font-weight: 500; }
</style>

<div class="instansi-detail">
    <!-- Breadcrumb -->
    <div class="mb-2" style="font-size: .78rem; color: #64748b;">
        <a href="?page=data-mitra" style="color: #64748b; text-decoration: none;">Instansi Mitra</a> 
        <span class="mx-1">&gt;</span> 
        <span style="color: #2563eb; font-weight: 600;">Detail Instansi</span>
    </div>

    <!-- Info Rincian Instansi -->
    <div class="sl-card-detail mt-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sl-detail-title mb-0">
                <i class="fas fa-building text-primary"></i> <?= $e($mitra['nama_instansi']) ?>
            </div>
            <a href="?page=edit-mitra&id=<?= $mitra['id'] ?>" class="btn btn-sm btn-primary border-0 rounded-lg px-3">
                <i class="fas fa-edit mr-1"></i> Edit Data
            </a>
        </div>

        <div class="sl-detail-grid mb-3">
            <div class="sl-detail-item">
                <div class="sl-detail-label"><i class="fas fa-briefcase"></i> Sektor / Bidang</div>
                <div class="sl-detail-value"><?= $e($mitra['sektor']) ?></div>
            </div>
            <div class="sl-detail-item">
                <div class="sl-detail-label"><i class="fas fa-user-tie"></i> Penanggung Jawab</div>
                <div class="sl-detail-value"><?= $e($mitra['pembimbing_lapangan']) ?></div>
            </div>
            <div class="sl-detail-item">
                <div class="sl-detail-label"><i class="fas fa-phone-alt"></i> Kontak / WhatsApp</div>
                <div class="sl-detail-value"><?= $e($mitra['telepon']) ?></div>
            </div>
            <div class="sl-detail-item">
                <div class="sl-detail-label"><i class="fas fa-users"></i> Status Kuota Tempat</div>
                <div class="sl-detail-value text-primary"><?= $e($mitra['terisi']) ?> / <?= $e($mitra['kuota']) ?> Siswa</div>
            </div>
        </div>

        <div class="sl-detail-item">
            <div class="sl-detail-label"><i class="fas fa-map-marker-alt"></i> Alamat Lengkap</div>
            <div class="sl-detail-value" style="font-weight: 500;"><?= $e($mitra['alamat']) ?></div>
        </div>
    </div>

    <!-- Daftar Siswa Magang di Instansi Ini -->
    <div class="sl-card-detail">
        <h6 class="font-weight-bold mb-3" style="color: #0f1d3a;"><i class="fas fa-graduation-cap text-primary mr-2"></i>Daftar Siswa Magang di Instansi Ini</h6>
        <div class="table-responsive">
            <table class="sl-table">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>NISN</th>
                        <th>Nama Siswa</th>
                        <th>Kelas & Jurusan</th>
                        <th>Guru Pembimbing</th>
                        <th>Status PKL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($siswaList as $idx => $s): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td class="font-weight-bold"><?= $e($s['nisn']) ?></td>
                            <td class="font-weight-bold text-dark"><?= $e($s['nama']) ?></td>
                            <td><?= $e($s['kelas']) ?></td>
                            <td><?= $e($s['guru']) ?></td>
                            <td><span class="sl-badge-aktif"><?= $e($s['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';