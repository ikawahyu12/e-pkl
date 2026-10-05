<?php
/**
 * Admin > Data Guru > Detail (UI saja, data dummy)
 *
 * File ini berdiri sendiri. Satu-satunya yang dibutuhkan: app/Views/layouts/admin.php
 * Letak file: app/Views/admin/guru/detail.php
 * Data guru diambil dari array dummy berdasarkan ?id= (contoh: ?page=detail-guru&id=1)
 */

// 1. Alamat asset (dipakai hanya jika halaman dibuka langsung dari folder app/Views)
if (!isset($assetBaseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#^(.*?)/app/Views(?:/.*)?$#', $scriptDir, $m)) {
        $assetBaseUrl = $m[1] . '/public/assets';
    }
}

// 2. Alamat tombol (DIPERBAIKI)
$urlDaftar = '?page=data-guru';
$urlEdit   = '?page=edit-guru&id=';
$urlDelete = '?page=delete-guru&id=';

// 3. Data dummy Guru Pembimbing
$guruList = [
    1 => [
        'nip'     => '198203152008011003',
        'nama'    => 'Budi Santoso, S.Kom.',
        'jurusan' => 'RPL (Rekayasa Perangkat Lunak)',
        'email'   => 'budi.santoso@smk.sch.id',
        'kontak'  => '0812-3456-7890',
        'alamat'  => 'Jl. Merdeka No. 45, Kertosono, Nganjuk',
        'kuota'   => 15,
        'terisi'  => 12,
        'status'  => 'Aktif'
    ],
    2 => [
        'nip'     => '197911042005012001',
        'nama'    => 'Dewi Sartika, M.Sn.',
        'jurusan' => 'DKV (Desain Komunikasi Visual)',
        'email'   => 'dewi.sartika@smk.sch.id',
        'kontak'  => '0813-9876-5432',
        'alamat'  => 'Jl. Pahlawan No. 12, Nganjuk',
        'kuota'   => 12,
        'terisi'  => 12,
        'status'  => 'Aktif'
    ],
    3 => [
        'nip'     => '198506222010011002',
        'nama'    => 'Eko Prasetyo, S.T.',
        'jurusan' => 'TKJ (Teknik Komputer & Jaringan)',
        'email'   => 'eko.prasetyo@smk.sch.id',
        'kontak'  => '0857-1122-3344',
        'alamat'  => 'Jl. Raya Baron No. 88, Nganjuk',
        'kuota'   => 15,
        'terisi'  => 10,
        'status'  => 'Aktif'
    ]
];

$id = (int) ($_GET['id'] ?? 1);
$g  = $guruList[$id] ?? $guruList[1];
$id = isset($guruList[$id]) ? $id : 1;

// Data Siswa Bimbingan Guru Terkait
$siswaBimbingan = [
    ['id' => 1, 'nisn' => '0061829102', 'nama' => 'Ahmad Rizky Pratama', 'kelas' => 'XII RPL 1', 'instansi' => 'PT Telkom Indonesia', 'status' => 'Aktif'],
    ['id' => 4, 'nisn' => '0069382012', 'nama' => 'Nabila Putri',         'kelas' => 'XII RPL 2', 'instansi' => 'CV Digital Optima',    'status' => 'Selesai'],
    ['id' => 7, 'nisn' => '0063920194', 'nama' => 'Yoga Pratama',         'kelas' => 'XII RPL 1', 'instansi' => 'PT Finnet Indonesia',   'status' => 'Aktif'],
];

$e   = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$val = static fn ($v): string => trim((string) $v) === '' ? '-' : htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$inisial = '';
foreach (array_slice(preg_split('/\s+/', trim($g['nama'])), 0, 2) as $kata) { 
    $inisial .= mb_strtoupper(mb_substr($kata, 0, 1)); 
}

// 4. Variabel layouts/admin.php
$pageTitle  = 'Detail Data Guru';
$activePage = 'data-guru'; // DIPERBAIKI: Disamakan dengan kunci menu "Data Guru"

ob_start();
?>
<style>
    /* Judul + breadcrumb dibuat sendiri di halaman ini, jadi header bawaan layout disembunyikan */
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
    .dt-pill.nonaktif { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }

    .dt-sec { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; margin: 0 0 .5rem; }
    .dt-row { display: flex; gap: 1rem; padding: .65rem 0; border-bottom: 1px solid #f1f5f9; font-size: .8rem; }
    .dt-row:last-child { border-bottom: 0; }
    .dt-row .k { width: 170px; flex-shrink: 0; color: #64748b; }
    .dt-row .v { color: #1e293b; font-weight: 600; word-break: break-word; }

    /* Indicator Kuota */
    .dt-progress-bg { background: #e2e8f0; border-radius: 999px; height: 8px; overflow: hidden; width: 100%; }
    .dt-progress-fill { background: #1e56a0; height: 100%; border-radius: 999px; }

    /* Tabel Bimbingan */
    .dt-table { width: 100%; border-collapse: collapse; margin-top: .5rem; }
    .dt-table th { background: #f8fafc; color: #475569; font-size: .68rem; font-weight: 700; text-transform: uppercase; padding: .65rem .75rem; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .dt-table td { padding: .75rem; font-size: .78rem; color: #334155; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .dt-table tr:last-child td { border-bottom: 0; }
    .dt-status-tag { display: inline-block; padding: .15rem .6rem; border-radius: 999px; font-size: .65rem; font-weight: 700; }
    .dt-status-tag.aktif { background: #dcfce7; color: #15803d; }
    .dt-status-tag.selesai { background: #dbeafe; color: #1d4ed8; }

    /* Dialog hapus */
    .dt-modal .modal-content { border: 0; border-radius: 16px; }
    .dt-modal .dt-trash { width: 44px; height: 44px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .dt-modal .dt-ringkas { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px; padding: .7rem 1rem; font-size: .78rem; }
    .dt-modal .dt-ringkas div { display: flex; justify-content: space-between; gap: 1rem; padding: .2rem 0; }
    .dt-modal .dt-ringkas div span:first-child { color: #64748b; }
    .dt-modal .dt-ringkas div span:last-child { font-weight: 600; text-align: right; }
    .dt-modal .dt-warn { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; border-radius: 10px; padding: .6rem .85rem; font-size: .76rem; }

    @media (max-width: 575.98px) {
        .dt-card { padding: 1.1rem; }
        .dt-row { flex-direction: column; gap: .15rem; }
        .dt-row .k { width: auto; font-size: .72rem; }
        .dt-pill { margin-left: 0; }
        .dt-actions { width: 100%; }
        .dt-actions .dt-btn { flex: 1; justify-content: center; }
    }
</style>

<div class="dt">
    <div class="dt-top">
        <div>
            <div class="dt-crumb">
                <a href="<?= $e($urlDaftar) ?>">Data Guru</a><span class="sep">/</span>Detail<span class="sep">/</span><span class="now"><?= $e($g['nama']) ?></span>
            </div>
            <h1 class="dt-title">Detail Data Guru</h1>
            <p class="dt-sub">Informasi profil guru, beban kuota bimbingan, dan daftar siswa magang yang didampingi</p>
        </div>
        <div class="dt-actions">
            <a href="<?= $e($urlDaftar) ?>" class="dt-btn dt-btn-back"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
            <a href="<?= $e($urlEdit . $id) ?>" class="dt-btn dt-btn-edit"><i class="fas fa-pen mr-2"></i>Edit</a>
            <button type="button" class="dt-btn dt-btn-del" id="dtHapus"><i class="far fa-trash-alt mr-2"></i>Hapus</button>
        </div>
    </div>

    <div class="dt-card mb-4" style="height:auto;">
        <div class="dt-profile">
            <div class="dt-avatar"><?= $e($inisial) ?></div>
            <div>
                <h2 class="dt-name"><?= $e($g['nama']) ?></h2>
                <div class="dt-meta">NIP: <?= $e($g['nip']) ?> &middot; <?= $e($g['jurusan']) ?></div>
            </div>
            <span class="dt-pill<?= $g['status'] !== 'Aktif' ? ' nonaktif' : '' ?>"><?= $e($g['status']) ?></span>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="dt-card mb-4">
                <div class="dt-sec">1. Profil &amp; Kontak Guru</div>
                <div class="dt-row"><div class="k">Nama Lengkap</div><div class="v"><?= $val($g['nama']) ?></div></div>
                <div class="dt-row"><div class="k">NIP / NUPTK</div><div class="v"><?= $val($g['nip']) ?></div></div>
                <div class="dt-row"><div class="k">Program Keahlian</div><div class="v"><?= $val($g['jurusan']) ?></div></div>
                <div class="dt-row"><div class="k">WhatsApp / No. HP</div><div class="v"><?= $val($g['kontak']) ?></div></div>
                <div class="dt-row"><div class="k">Email</div><div class="v"><?= $val($g['email']) ?></div></div>
                <div class="dt-row"><div class="k">Alamat</div><div class="v"><?= $val($g['alamat']) ?></div></div>
            </div>

            <div class="dt-card">
                <div class="dt-sec">2. Kapasitas &amp; Beban Bimbingan</div>
                <div class="d-flex justify-content-between align-items-center my-2">
                    <span class="text-muted" style="font-size: .78rem;">Kuota Terpakai</span>
                    <span class="font-weight-bold text-dark" style="font-size: .85rem;">
                        <?= $e($g['terisi']) ?> / <?= $e($g['kuota']) ?> Siswa
                    </span>
                </div>
                <?php $persen = round(($g['terisi'] / $g['kuota']) * 100); ?>
                <div class="dt-progress-bg mb-2">
                    <div class="dt-progress-fill" style="width: <?= $persen ?>%;"></div>
                </div>
                <div class="text-muted" style="font-size: .72rem;">
                    Tersisa kuota untuk <strong><?= $g['kuota'] - $g['terisi'] ?></strong> siswa bimbingan lagi.
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="dt-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="dt-sec mb-0">3. Daftar Siswa Bimbingan</div>
                    <span class="badge badge-light border px-2 py-1" style="font-size: .7rem; font-weight:600;"><?= count($siswaBimbingan) ?> Siswa</span>
                </div>
                <div class="table-responsive">
                    <table class="dt-table">
                        <thead>
                            <tr>
                                <th style="width: 6%;">No</th>
                                <th style="width: 22%;">NISN</th>
                                <th style="width: 32%;">Nama Siswa</th>
                                <th style="width: 20%;">Kelas</th>
                                <th style="width: 20%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($siswaBimbingan as $idx => $sb): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td class="font-weight-bold"><?= $e($sb['nisn']) ?></td>
                                    <td class="font-weight-bold"><?= $e($sb['nama']) ?></td>
                                    <td><?= $e($sb['kelas']) ?></td>
                                    <td>
                                        <span class="dt-status-tag <?= $sb['status'] === 'Selesai' ? 'selesai' : 'aktif' ?>">
                                            <?= $e($sb['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dialog konfirmasi hapus -->
<div class="modal fade dt-modal" id="dtModalHapus" tabindex="-1" role="dialog" aria-labelledby="dtModalJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-2">
            <div class="modal-body">
                <div class="d-flex mb-3" style="gap:1rem;">
                    <div class="dt-trash"><i class="fas fa-trash-alt"></i></div>
                    <div>
                        <h5 class="font-weight-bold mb-1" id="dtModalJudul">Hapus Data Guru?</h5>
                        <p class="small text-muted mb-0">
                            Apakah Anda yakin ingin menghapus data guru <strong><?= $e($g['nama']) ?></strong> (NIP: <?= $e($g['nip']) ?>)?
                            Tindakan ini dapat berpengaruh pada data penempatan siswa bimbingan terkait.
                        </p>
                    </div>
                </div>
                <div class="dt-ringkas mb-3">
                    <div><span>Nama Guru:</span><span><?= $e($g['nama']) ?></span></div>
                    <div><span>Program Keahlian:</span><span><?= $e($g['jurusan']) ?></span></div>
                    <div><span>Siswa Dibimbing:</span><span class="text-primary"><?= $e($g['terisi']) ?> Siswa</span></div>
                </div>
                <div class="dt-warn d-flex mb-3" style="gap:.5rem;">
                    <i class="fas fa-exclamation-triangle mt-1"></i>
                    <span>Pastikan Anda telah memindahkan plot siswa bimbingan ke guru lain sebelum menghapus.</span>
                </div>
                <div class="d-flex justify-content-end" style="gap:.5rem;">
                    <button type="button" class="btn btn-light border btn-sm px-4" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm px-3" id="dtKonfirmasi"><i class="fas fa-trash-alt mr-1"></i> Ya, Hapus Data</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        var $ = window.jQuery;
        $('#dtHapus').on('click', function () { $('#dtModalHapus').modal('show'); });
        
        // DIPERBAIKI: Mengarahkan eksekusi hapus ke modul guru
        $('#dtKonfirmasi').on('click', function () {
            window.location.href = '<?= $urlDelete . $id ?>';
        });
    });
</script>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/layouts/admin.php';