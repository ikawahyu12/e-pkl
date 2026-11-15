<?php
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';
$csrfToken = AdminCsrf::token();
// ===== 1. BLOK PHP ATAS =====
require_once __DIR__ . '/../../../../../config/koneksi.php';

$activePage   = 'instansi-mitra';
$pageTitle    = 'Data Instansi Mitra';
$pageSubtitle = 'Kelola data instansi/perusahaan mitra dan kuota penempatan PKL.';

$pageHeaderActions = '
    <button type="button" class="btn btn-sm sl-btn-export mr-2"><i class="fas fa-download mr-2"></i>Export Data</button>
    <a href="?page=create-mitra" class="btn btn-sm sl-btn-add"><i class="fas fa-plus mr-2"></i>Tambah Instansi</a>';

$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';
$e            = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ==========================================
// 1. KONFIGURASI PAGINATION
// ==========================================
$limit = 10;
$page  = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ==========================================
// 2. Hitung total data dan statistik dengan skema database epkl.
// ==========================================
$totalQuery = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM instansi");
if (!$totalQuery) {
    error_log('Gagal menghitung data instansi: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Data instansi tidak dapat dimuat.');
}
$totalMitra = (int) mysqli_fetch_assoc($totalQuery)['total'];

$statQuery = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(kuota_siswa), 0) AS total_kuota
    FROM instansi
    WHERE status = 'aktif'
");
if (!$statQuery) {
    error_log('Gagal memuat statistik instansi: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Statistik instansi tidak dapat dimuat.');
}
$totalKuota = (int) mysqli_fetch_assoc($statQuery)['total_kuota'];

$terisiQuery = mysqli_query($koneksi, "
    SELECT COUNT(*) AS jml
    FROM penempatan_pkl
    WHERE status = 'aktif'
");
if (!$terisiQuery) {
    error_log('Gagal menghitung penempatan aktif: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Statistik penempatan tidak dapat dimuat.');
}
$terisi = (int) mysqli_fetch_assoc($terisiQuery)['jml'];
$totalPages = max(1, (int) ceil($totalMitra / $limit));

// 3. Ambil instansi aktif pada halaman yang diminta.
$sql = "SELECT
            i.id AS id_instansi,
            i.nama AS nama_instansi,
            i.alamat,
            i.telepon AS no_telepon,
            i.nama_penanggung_jawab AS nama_kontak,
            i.email,
            i.jenis AS jenis_instansi,
            i.status,
            i.kuota_siswa AS kuota,
            COUNT(p.id) AS terisi
        FROM instansi i
        LEFT JOIN penempatan_pkl p
            ON p.instansi_id = i.id
            AND p.status = 'aktif'
        GROUP BY
            i.id,
            i.nama,
            i.alamat,
            i.telepon,
            i.nama_penanggung_jawab,
            i.email,
            i.jenis,
            i.status,
            i.kuota_siswa
        ORDER BY i.nama ASC
        LIMIT $limit OFFSET $offset";

$result = mysqli_query($koneksi, $sql);

if (!$result) {
    error_log('Gagal memuat daftar instansi: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Daftar instansi tidak dapat dimuat.');
}

$mitraList = [];

while ($row = mysqli_fetch_assoc($result)) {
    $mitraList[] = $row;
}

// 4. Daftar jenis instansi untuk filter.
$jenisList = [];
$jenisQuery = mysqli_query($koneksi, "
    SELECT DISTINCT jenis
    FROM instansi
    WHERE jenis IS NOT NULL AND jenis <> ''
    ORDER BY jenis ASC
");

if (!$jenisQuery) {
    error_log('Gagal memuat jenis instansi: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Filter instansi tidak dapat dimuat.');
}

while ($j = mysqli_fetch_assoc($jenisQuery)) {
    $jenisList[] = $j['jenis'];
}

ob_start();
?>
<style>
    /* ---- Tombol Header ---- */
    .sl-btn-export, .sl-btn-add { border: 0; border-radius: 8px; padding: .5rem .9rem; font-size: .78rem; font-weight: 600; }
    .sl-btn-export { background: #dbe7f6; color: #1e293b; }
    .sl-btn-export:hover { background: #cddff2; color: #1e293b; }
    .sl-btn-add { background: #2563eb; color: #fff; }
    .sl-btn-add:hover { background: #1d4ed8; color: #fff; }

    /* ---- Kartu Statistik ---- */
    .sl-stat { display: flex; align-items: center; gap: .9rem; background: #fff; border-radius: 12px; padding: .9rem 1rem; box-shadow: 0 1px 3px rgba(16,24,40,.07); height: 100%; }
    .sl-stat-icon { width: 46px; height: 46px; border-radius: 10px; background: #eaf1fb; color: #0b5f8a; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; }
    .sl-stat-label { font-size: .68rem; color: #475569; line-height: 1.2; }
    .sl-stat-value { font-size: 1.45rem; font-weight: 800; line-height: 1.15; }

    /* ---- Toolbar & Filter ---- */
    .sl-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .6rem; background: #fff; border-radius: 12px; padding: .55rem; box-shadow: 0 1px 3px rgba(16,24,40,.07); }
    .sl-search { position: relative; flex: 0 1 230px; }
    .sl-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: .78rem; }
    .sl-field { height: 34px; border: 0; border-radius: 8px; background: #eaf1fb; font-size: .75rem; color: #3b4a5e; outline: 0; }
    .sl-search .sl-field { width: 100%; padding: 0 .75rem 0 34px; }
    .sl-filters { display: flex; gap: .4rem; }
    .sl-filters select.sl-field { appearance: none; -webkit-appearance: none; padding: 0 .9rem; cursor: pointer; }
    .sl-filters .sl-sektor { width: 200px; }

    /* ---- Tabel ---- */
    .sl-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(16,24,40,.07); overflow: hidden; }
    .sl-table { width: 100%; min-width: 940px; table-layout: fixed; border-collapse: collapse; }
    .sl-table th { background: #f1f5fb; color: #3b4a5e; font-size: .66rem; font-weight: 600; text-transform: uppercase; padding: .8rem .6rem; text-align: left; border: 0; }
    .sl-table td { padding: .85rem .6rem; font-size: .72rem; color: #4b586b; vertical-align: middle; border: 0; border-bottom: 1px solid #f0f3f8; }
    .sl-table .c-no { text-align: center; }
    .sl-nama { font-weight: 700; color: #0f1d3a; font-size: .82rem; }
    .sl-nama small { display: block; font-weight: 400; color: #94a3b8; font-size: .65rem; margin-top: 2px; }

    .sl-badge { display: inline-block; padding: .12rem .6rem; border-radius: 999px; font-size: .62rem; font-weight: 500; }
    .sl-badge-tersedia { background: #dbe8fb; color: #2a5aa0; }
    .sl-badge-penuh { background: #fee2e2; color: #dc2626; }
    .sl-badge-internal { background: #fef3c7; color: #b45309; }
    .sl-badge-eksternal { background: #e0f2fe; color: #0369a1; }

    .sl-aksi { white-space: nowrap; }
    .sl-aksi a, .sl-aksi button { font-size: .66rem; margin-right: .45rem; padding: 0; border: 0; background: none; text-decoration: none; cursor: pointer; }
    .sl-aksi .a-detail { color: #1a2438; }
    .sl-aksi .a-edit { color: #2563eb; }
    .sl-aksi .a-hapus { color: #e0264a; }

    /* ---- Footer & Pagination ---- */
    .sl-foot { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; padding: .85rem 1rem; }
    .sl-info { font-size: .7rem; color: #6b7787; }
    .sl-pager { display: flex; align-items: center; gap: .35rem; margin: 0; padding: 0; list-style: none; }
    .sl-pager a { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 .35rem; border-radius: 5px; font-size: .7rem; color: #3b4a5e; text-decoration: none; }
    .sl-pager .active a { background: #0b5f8a; color: #fff; }
    .sl-pager .disabled a { color: #3b4a5e; pointer-events: none; opacity: 0.6; }

    /* ---- Style Modal Hapus ---- */
    .sl-modal .modal-content { border: 0; border-radius: 16px; }
    .sl-modal .sl-trash { width: 44px; height: 44px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .sl-modal .sl-ringkas { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px; padding: .7rem 1rem; font-size: .78rem; }
    .sl-modal .sl-ringkas div { display: flex; justify-content: space-between; gap: 1rem; padding: .2rem 0; }
    .sl-modal .sl-ringkas div span:first-child { color: #64748b; }
    .sl-modal .sl-ringkas div span:last-child { font-weight: 600; text-align: right; }
    .sl-modal .sl-warn { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; border-radius: 10px; padding: .6rem .85rem; font-size: .76rem; }
</style>

<div class="mitra-list">

    <!-- Statistik -->
    <div class="row mb-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-building"></i></span>
                <div>
                    <div class="sl-stat-label">Total Instansi Mitra</div>
                    <div class="sl-stat-value" style="color:#0f1d3a;"><?= $e($totalMitra) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-users"></i></span>
                <div>
                    <div class="sl-stat-label">Total Kuota Tempat</div>
                    <div class="sl-stat-value" style="color:#0c6aa6;"><?= $e($totalKuota) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-user-check"></i></span>
                <div>
                    <div class="sl-stat-label">Siswa Terisi</div>
                    <div class="sl-stat-value" style="color:#0a5d7a;"><?= $e($terisi) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pencarian & Filter -->
    <div class="sl-toolbar mb-3">
        <div class="sl-search">
            <i class="fas fa-search"></i>
            <input type="search" id="slCari" class="sl-field" placeholder="Cari instansi atau alamat...">
        </div>
        <div class="sl-filters">
            <select id="slJenis" class="sl-field sl-sektor">
                <option value="">Semua Jenis Instansi</option>
                <?php foreach ($jenisList as $jenis): ?>
                    <option value="<?= $e($jenis) ?>"><?= $e($jenis) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="sl-card mb-4">
        <div class="table-responsive">
            <table class="sl-table" id="slTabel">
                <thead>
                    <tr>
                        <th class="c-no" style="width:5%;">No</th>
                        <th style="width:22%;">Nama Instansi</th>
                        <th style="width:22%;">Alamat Lengkap</th>
                        <th style="width:14%;">Penanggung Jawab</th>
                        <th style="width:12%;">No. Telepon</th>
                        <th style="width:12%;">Kuota PKL</th>
                        <th style="width:13%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mitraList)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada data instansi mitra.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mitraList as $i => $m):
                            // Cek apakah kuota sudah penuh
                            $terisiSaatIni = (int)$m['terisi'];
                            $kuotaMax = (int)$m['kuota'];
                            $isPenuh = $kuotaMax > 0 && $terisiSaatIni >= $kuotaMax;
                        ?>
                            <tr data-jenis="<?= $e($m['jenis_instansi'] ?? '') ?>">
                                <td class="c-no"><?= $offset + $i + 1 ?></td>
                                <td class="sl-nama">
                                    <?= $e($m['nama_instansi']) ?>
                                    <small>
                                        <span class="sl-badge sl-badge-<?= $e($m['jenis_instansi'] ?? 'eksternal') ?>">
                                            <?= ucfirst($e($m['jenis_instansi'] ?? 'eksternal')) ?>
                                        </span>
                                        <?php if (($m['status'] ?? '') === 'nonaktif'): ?>
                                            <span class="sl-badge sl-badge-penuh">Nonaktif</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td><?= $e($m['alamat']) ?></td>
                                <td><?= $e($m['nama_kontak'] ?: '-') ?></td>
                                <td><?= $e($m['no_telepon'] ?: '-') ?></td>
                                <td>
                                    <!-- TAMPILAN KUOTA DINAMIS -->
                                    <span class="sl-badge <?= $isPenuh ? 'sl-badge-penuh' : 'sl-badge-tersedia' ?>">
                                        <?= $terisiSaatIni ?> / <?= $kuotaMax ?> Siswa
                                    </span>
                                </td>
                                <td class="sl-aksi">
                                    <a class="a-detail" href="?page=detail-mitra&id=<?= (int)$m['id_instansi'] ?>">Detail</a>
                                    <a class="a-edit" href="?page=edit-mitra&id=<?= (int)$m['id_instansi'] ?>">Edit</a>
                                    <!-- TOMBOL DIUBAH MENJADI HAPUS -->
                                    <button type="button" class="a-hapus sl-hapus"
                                            data-id="<?= (int)$m['id_instansi'] ?>"
                                            data-nama="<?= $e($m['nama_instansi']) ?>"
                                            data-pembimbing="<?= $e($m['nama_kontak'] ?: '-') ?>"
                                            data-telepon="<?= $e($m['no_telepon'] ?: '-') ?>"
                                            data-kuota="<?= $kuotaMax ?>"
                                            data-terisi="<?= $terisiSaatIni ?>">Hapus</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr id="slKosong" style="display:none;">
                        <td colspan="7" class="text-center py-4 text-muted">Tidak ada data instansi mitra yang sesuai.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sl-foot">
            <div class="sl-info" id="slInfo">
                Menampilkan <?= ($offset + 1) ?>-<?= ($offset + count($mitraList)) ?> dari <?= $e($totalMitra) ?> instansi
            </div>
            
            <ul class="sl-pager">
                <?php
                // Tombol Sebelumnya
                $prevClass = ($page <= 1) ? 'disabled' : '';
                $prevHal   = max(1, $page - 1);
                ?>
                <li class="<?= $prevClass ?>"><a href="?page=data-mitra&hal=<?= $prevHal ?>">Sebelumnya</a></li>

                <?php
                // Tampilkan maksimal 5 halaman
                $start = max(1, $page - 2);
                $end   = min($totalPages, $start + 4);
                $start = max(1, $end - 4);

                for ($p = $start; $p <= $end; $p++):
                    $activeClass = ($p == $page) ? 'active' : '';
                ?>
                    <li class="<?= $activeClass ?>"><a href="?page=data-mitra&hal=<?= $p ?>"><?= $p ?></a></li>
                <?php endfor; ?>

                <?php
                // Tombol Selanjutnya
                $nextClass = ($page >= $totalPages) ? 'disabled' : '';
                $nextHal   = min($totalPages, $page + 1);
                ?>
                <li class="<?= $nextClass ?>"><a href="?page=data-mitra&hal=<?= $nextHal ?>">Selanjutnya</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Instansi Mitra -->
<div class="modal fade sl-modal" id="slModalHapus" tabindex="-1" role="dialog" aria-labelledby="slModalHapusJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-2">
            <form method="POST" action="?page=delete-mitra">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="delete_id" id="slHId">

                    <!-- Header Modal -->
                    <div class="d-flex mb-3" style="gap:1rem;">
                        <div class="sl-trash"><i class="fas fa-trash-alt"></i></div>
                        <div>
                            <h5 class="font-weight-bold mb-1 text-danger" id="slModalHapusJudul">Hapus Permanen Instansi?</h5>
                            <p class="small text-muted mb-0">
                                Apakah Anda yakin ingin menghapus permanen instansi
                                <strong id="slHNama">-</strong>? 
                                <span class="text-danger font-weight-bold">Data yang dihapus tidak dapat dikembalikan.</span>
                            </p>
                        </div>
                    </div>

                    <!-- Ringkasan Data -->
                    <div class="sl-ringkas mb-3">
                        <div><span>Nama Instansi:</span><span id="slHNama2">-</span></div>
                        <div><span>Penanggung Jawab:</span><span id="slHPembimbing">-</span></div>
                        <div><span>Kontak / Telepon:</span><span id="slHTelepon">-</span></div>
                        <div><span>Status Kuota:</span><span id="slHKuota" class="text-primary">-</span></div>
                    </div>

                    <!-- Alert Peringatan -->
                    <div class="sl-warn d-flex mb-3" style="gap:.5rem;">
                        <i class="fas fa-exclamation-triangle mt-1"></i>
                        <span>Menghapus instansi akan menghapus seluruh data terkait secara permanen. Pastikan tidak ada siswa PKL aktif di instansi ini.</span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex justify-content-end" style="gap:.5rem;">
                        <button type="button" class="btn btn-light border btn-sm px-4" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-sm px-3" id="slKonfirmasiHapus">
                            <i class="fas fa-trash-alt mr-1"></i> Ya, Hapus Permanen
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        var $ = window.jQuery;
        var totalHalaman = <?= (int) count($mitraList) ?>;
        
        // Filter berlaku untuk 10 data yang tampil saat ini
        function terapkan() {
            var q = $('#slCari').val().toLowerCase().trim();
            var jenis = $('#slJenis').val();
            var tampil = 0;

            $('#slTabel tbody tr[data-jenis]').each(function () {
                var $r = $(this);

                var cocok = (!q || $r.text().toLowerCase().indexOf(q) > -1)
                    && (!jenis || $r.data('jenis') === jenis);

                $r.toggle(cocok);

                if (cocok) {
                    tampil++;
                }
            });

            $('#slKosong').toggle(tampil === 0);
            $('#slInfo').text('Menampilkan ' + tampil + ' dari ' + totalHalaman + ' instansi di halaman ini');
        }

        $('#slCari').on('input', terapkan);
        $('#slJenis').on('change', terapkan);

        // Modal Hapus
        $(document).on('click', '.sl-hapus', function () {
            var d = $(this).data();
            $('#slHId').val(d.id);
            $('#slHNama, #slHNama2').text(d.nama);
            $('#slHPembimbing').text(d.pembimbing);
            $('#slHTelepon').text(d.telepon);
            $('#slHKuota').text(d.terisi + ' / ' + d.kuota + ' Siswa');
            $('#slModalHapus').modal('show');
        });
    });
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';