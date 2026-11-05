<?php
// 1. Panggil koneksi
require_once __DIR__ . '/../../../../../config/koneksi.php';
require_once dirname(__DIR__, 3) . '/Support/Csrf.php';
$csrfToken = AdminCsrf::token();

$activePage   = 'data-siswa';
$pageTitle    = 'Data Siswa PKL';
$pageSubtitle = 'Kelola data siswa dan alokasi tempat PKL.';

$pageHeaderActions = '
    <a href="#" class="btn btn-sm sl-btn-export mr-2"><i class="fas fa-download mr-2"></i>Export Data</a>
    <a href="?page=create" class="btn btn-sm sl-btn-add"><i class="fas fa-plus mr-2"></i>Tambah Siswa</a>';

$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ==========================================
// 1. KONFIGURASI PAGINATION
// ==========================================
$limit = 10;
$page  = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ==========================================
// 2. HITUNG TOTAL DATA
// ==========================================
$qTotal = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM siswa");
if (!$qTotal) {
    error_log('Admin Siswa total query failed: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Data Siswa tidak dapat dimuat saat ini.');
}
$totalSiswa = (int) mysqli_fetch_assoc($qTotal)['total'];

$qStat = mysqli_query($koneksi, "
    SELECT 
        SUM(CASE WHEN status_siswa = 'aktif' THEN 1 ELSE 0 END) AS aktif,
        SUM(CASE WHEN status_siswa = 'lulus' THEN 1 ELSE 0 END) AS selesai
    FROM siswa
");
if (!$qStat) {
    error_log('Admin Siswa statistics query failed: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Statistik Siswa tidak dapat dimuat saat ini.');
}
$stat = mysqli_fetch_assoc($qStat);
$siswaAktif   = (int) ($stat['aktif'] ?? 0);
$siswaSelesai = (int) ($stat['selesai'] ?? 0);

$totalPages = max(1, (int) ceil($totalSiswa / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// ==========================================
// 3. AMBIL DATA SISWA
// ==========================================
$sql = "SELECT
            s.id AS id_siswa,
            COALESCE(s.nis, s.nisn) AS nis,
            s.nisn,
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
        ORDER BY s.nama_lengkap ASC
        LIMIT $limit OFFSET $offset";

$result = mysqli_query($koneksi, $sql);
if (!$result) {
    error_log('Admin Siswa index query failed: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Data Siswa tidak dapat dimuat saat ini.');
}

$siswaList = [];
while ($row = mysqli_fetch_assoc($result)) {
    $siswaList[] = $row;
}

ob_start();
?>
<style>
    .sl-btn-export, .sl-btn-add { border: 0; border-radius: 8px; padding: .5rem .9rem; font-size: .78rem; font-weight: 600; }
    .sl-btn-export { background: #dbe7f6; color: #1e293b; }
    .sl-btn-export:hover { background: #cddff2; color: #1e293b; }
    .sl-btn-add { background: #2563eb; color: #fff; }
    .sl-btn-add:hover { background: #1d4ed8; color: #fff; }
    .sl-stat { display: flex; align-items: center; gap: .9rem; background: #fff; border-radius: 12px; padding: .9rem 1rem; box-shadow: 0 1px 3px rgba(16,24,40,.07); height: 100%; }
    .sl-stat-icon { width: 46px; height: 46px; border-radius: 10px; background: #eaf1fb; color: #0b5f8a; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; }
    .sl-stat-label { font-size: .68rem; color: #475569; line-height: 1.2; }
    .sl-stat-value { font-size: 1.45rem; font-weight: 800; line-height: 1.15; }
    .sl-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .6rem; background: #fff; border-radius: 12px; padding: .55rem; box-shadow: 0 1px 3px rgba(16,24,40,.07); }
    .sl-search { position: relative; flex: 0 1 230px; }
    .sl-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: .78rem; }
    .sl-field { height: 34px; border: 0; border-radius: 8px; background: #eaf1fb; font-size: .75rem; color: #3b4a5e; outline: 0; }
    .sl-search .sl-field { width: 100%; padding: 0 .75rem 0 34px; }
    .sl-search .sl-field::placeholder { color: #64748b; }
    .sl-filters { display: flex; gap: .4rem; }
    .sl-filters select.sl-field { -webkit-appearance: none; -moz-appearance: none; appearance: none; padding: 0 .9rem; cursor: pointer; }
    .sl-filters .sl-jurusan { width: 175px; }
    .sl-filters .sl-status { width: 100px; }
    .sl-field:focus { box-shadow: 0 0 0 2px rgba(37,99,235,.25); }
    .sl-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(16,24,40,.07); overflow: hidden; }
    .sl-table { width: 100%; min-width: 940px; table-layout: fixed; border-collapse: collapse; }
    .sl-table th { background: #f1f5fb; color: #3b4a5e; font-size: .66rem; font-weight: 600; text-transform: uppercase; letter-spacing: .02em; padding: .8rem .6rem; text-align: left; border: 0; }
    .sl-table td { padding: .85rem .6rem; font-size: .72rem; color: #4b586b; vertical-align: middle; border: 0; border-bottom: 1px solid #f0f3f8; }
    .sl-table tr:last-child td { border-bottom: 0; }
    .sl-table .c-no { text-align: center; }
    .sl-nisn { font-weight: 700; color: #0f1d3a; }
    .sl-nama { font-weight: 700; color: #0f1d3a; font-size: .82rem; }
    .sl-badge { display: inline-block; padding: .12rem .6rem; border-radius: 999px; font-size: .62rem; font-weight: 500; }
    .sl-badge-aktif { background: #dbe8fb; color: #2a5aa0; }
    .sl-badge-selesai { background: #c7e3f6; color: #0f4c75; }
    .sl-aksi { white-space: nowrap; }
    .sl-aksi a, .sl-aksi button { font-size: .66rem; margin-right: .45rem; padding: 0; border: 0; background: none; text-decoration: none; cursor: pointer; }
    .sl-aksi .a-detail { color: #1a2438; }
    .sl-aksi .a-edit { color: #2563eb; }
    .sl-aksi .a-hapus { color: #e0264a; }
    .sl-foot { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; padding: .85rem 1rem; }
    .sl-info { font-size: .7rem; color: #6b7787; }
    .sl-pager { display: flex; align-items: center; gap: .35rem; margin: 0; padding: 0; list-style: none; }
    .sl-pager a { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 .35rem; border-radius: 5px; font-size: .7rem; color: #3b4a5e; text-decoration: none; }
    .sl-pager a:hover { background: #eef2f8; }
    .sl-pager .active a { background: #0b5f8a; color: #fff; }
    .sl-pager .disabled a { color: #3b4a5e; pointer-events: none; }
    .sl-modal .modal-content { border: 0; border-radius: 16px; }
    .sl-modal .sl-trash { width: 44px; height: 44px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .sl-modal .sl-ringkas { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px; padding: .7rem 1rem; font-size: .78rem; }
    .sl-modal .sl-ringkas div { display: flex; justify-content: space-between; gap: 1rem; padding: .2rem 0; }
    .sl-modal .sl-ringkas div span:first-child { color: #64748b; }
    .sl-modal .sl-ringkas div span:last-child { font-weight: 600; text-align: right; }
    .sl-modal .sl-warn { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; border-radius: 10px; padding: .6rem .85rem; font-size: .76rem; }
</style>

<div class="siswa-list">
    <!-- [BAGIAN STATISTIK DAN FILTER TETAP SAMA, TIDAK SAYA UBAH] -->
    <div class="row mb-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-graduation-cap"></i></span>
                <div>
                    <div class="sl-stat-label">Total Siswa</div>
                    <div class="sl-stat-value" style="color:#0f1d3a;"><?= $e($totalSiswa) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-briefcase"></i></span>
                <div>
                    <div class="sl-stat-label">Sedang Aktif</div>
                    <div class="sl-stat-value" style="color:#0c6aa6;"><?= $e($siswaAktif) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="far fa-check-circle"></i></span>
                <div>
                    <div class="sl-stat-label">Selesai</div>
                    <div class="sl-stat-value" style="color:#0a5d7a;"><?= $e($siswaSelesai) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="sl-toolbar mb-3">
        <div class="sl-search">
            <i class="fas fa-search"></i>
            <input type="search" id="slCari" class="sl-field" placeholder="Cari nama atau NIS..." aria-label="Cari nama atau NIS">
        </div>
        <div class="sl-filters">
            <select id="slJurusan" class="sl-field sl-jurusan" aria-label="Filter jurusan">
                <option value="">Semua Jurusan</option>
                <option value="RPL">RPL</option>
                <option value="TKJ">TKJ</option>
                <option value="DKV">DKV</option>
            </select>
            <select id="slStatus" class="sl-field sl-status" aria-label="Filter status">
                <option value="">Status: Semua</option>
                <option value="Aktif">Aktif</option>
                <option value="Selesai">Selesai</option>
            </select>
        </div>
    </div>

    <div class="sl-card mb-4">
        <div class="table-responsive">
            <table class="sl-table" id="slTabel">
                <thead>
                    <tr>
                        <th class="c-no" style="width:6%;">No</th>
                        <th style="width:11%;">NIS</th>
                        <th style="width:15%;">Nama Siswa</th>
                        <th style="width:15%;">Kelas / Jurusan</th>
                        <th style="width:15%;">Instansi Penempatan</th>
                        <th style="width:15%;">Guru Pembimbing</th>
                        <th style="width:11%;">Status</th>
                        <th style="width:16%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($siswaList)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data siswa.</td></tr>
                    <?php else: ?>
                        <?php foreach ($siswaList as $i => $s): ?>
                            <tr data-kelas="<?= $e($s['kelas'] ?? '') ?>" data-status="<?= $e($s['status'] ?? 'Aktif') ?>">
                                <td class="c-no"><?= $offset + $i + 1 ?></td>
                                <td class="sl-nisn"><?= $e($s['nis'] ?? '-') ?></td>
                                <td class="sl-nama"><?= $e($s['nama_siswa']) ?></td>
                                <td><?= $e($s['kelas'] ?? '-') ?></td>
                                <td><?= $e($s['nama_instansi'] ?? '-') ?></td>
                                <td>-</td>
                                <td>
                                    <span class="sl-badge <?= ($s['status'] ?? '') === 'Selesai' ? 'sl-badge-selesai' : 'sl-badge-aktif' ?>"><?= $e($s['status'] ?? 'Aktif') ?></span>
                                </td>
                                <td class="sl-aksi">
                                    <a class="a-detail" href="?page=detail&id=<?= (int) $s['id_siswa'] ?>">Detail</a>
                                    <a class="a-edit" href="?page=edit&id=<?= (int) $s['id_siswa'] ?>">Edit</a>
                                    <!-- TAMBAHKAN data-id -->
                                    <button type="button" class="a-hapus sl-hapus"
                                            data-id="<?= (int) $s['id_siswa'] ?>"
                                            data-nama="<?= $e($s['nama_siswa']) ?>"
                                            data-nisn="<?= $e($s['nis'] ?? '-') ?>"
                                            data-kelas="<?= $e($s['kelas'] ?? '-') ?>"
                                            data-instansi="<?= $e($s['nama_instansi'] ?? '-') ?>"
                                            data-guru="-">Hapus</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr id="slKosong" style="display:none;">
                        <td colspan="8" class="text-center py-4 text-muted">Tidak ada data siswa yang sesuai.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sl-foot">
            <div class="sl-info" id="slInfo">
                Menampilkan <?= ($offset + 1) ?>-<?= ($offset + count($siswaList)) ?> dari <?= $e($totalSiswa) ?> siswa
            </div>
            <ul class="sl-pager" aria-label="Navigasi halaman">
                <?php
                $prevClass = ($page <= 1) ? 'disabled' : '';
                $prevHal   = max(1, $page - 1);
                ?>
                <li class="<?= $prevClass ?>"><a href="?page=data-siswa&hal=<?= $prevHal ?>">Sebelumnya</a></li>

                <?php
                $start = max(1, $page - 2);
                $end   = min($totalPages, $start + 4);
                $start = max(1, $end - 4);

                for ($p = $start; $p <= $end; $p++):
                    $activeClass = ($p == $page) ? 'active' : '';
                ?>
                    <li class="<?= $activeClass ?>"><a href="?page=data-siswa&hal=<?= $p ?>"><?= $p ?></a></li>
                <?php endfor; ?>

                <?php
                $nextClass = ($page >= $totalPages) ? 'disabled' : '';
                $nextHal   = min($totalPages, $page + 1);
                ?>
                <li class="<?= $nextClass ?>"><a href="?page=data-siswa&hal=<?= $nextHal ?>">Selanjutnya</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Modal Hapus (DIBUNGKUS FORM) -->
<div class="modal fade sl-modal" id="slModalHapus" tabindex="-1" role="dialog" aria-labelledby="slModalHapusJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-2">
            <!-- ACTION MENGARAH KE HALAMAN HAPUS -->
            <form action="?page=hapus-siswa" method="POST">
                <div class="modal-body">
                    <!-- INPUT HIDDEN UNTUK MENYIMPAN ID -->
                    <input type="hidden" name="id_siswa" id="slHId">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

                    <div class="d-flex mb-3" style="gap:1rem;">
                        <div class="sl-trash"><i class="fas fa-trash-alt"></i></div>
                        <div>
                            <h5 class="font-weight-bold mb-1" id="slModalHapusJudul">Nonaktifkan Akun Siswa?</h5>
                            <p class="small text-muted mb-0">
                                Apakah Anda yakin ingin menonaktifkan akun Siswa
                                <strong id="slHNama">-</strong> (NIS: <span id="slHNisn">-</span>)?
                            </p>
                        </div>
                    </div>
                    <div class="sl-ringkas mb-3">
                        <div><span>Nama Siswa:</span><span id="slHNama2">-</span></div>
                        <div><span>Kelas &amp; Jurusan:</span><span id="slHKelas">-</span></div>
                        <div><span>Instansi Penempatan:</span><span id="slHInstansi" class="text-primary">-</span></div>
                        <div><span>Guru Pembimbing:</span><span id="slHGuru">-</span></div>
                    </div>
                    <div class="sl-warn d-flex mb-3" style="gap:.5rem;">
                        <i class="fas fa-exclamation-triangle mt-1"></i>
                        <span>Akun akan dinonaktifkan dan status Siswa menjadi keluar. Riwayat penempatan dan data lainnya tetap disimpan.</span>
                    </div>
                    <div class="d-flex justify-content-end" style="gap:.5rem;">
                        <button type="button" class="btn btn-light border btn-sm px-4" data-dismiss="modal">Batal</button>
                        <!-- TIPE SUBMIT UNTUK MENGIRIM FORM -->
                        <button type="submit" class="btn btn-warning btn-sm px-3">
                            <i class="fas fa-user-slash mr-1"></i> Nonaktifkan Akun
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
        
        function terapkan() {
            var q = $('#slCari').val().toLowerCase().trim();
            var j = $('#slJurusan').val();
            var st = $('#slStatus').val();
            var tampil = 0;
            $('#slTabel tbody tr[data-status]').each(function () {
                var $r = $(this);
                var cocok = (!q || $r.text().toLowerCase().indexOf(q) > -1)
                    && (!j || String($r.data('kelas')).indexOf(j) > -1)
                    && (!st || $r.data('status') === st);
                $r.toggle(cocok);
                if (cocok) { tampil++; }
            });
            $('#slKosong').toggle(tampil === 0);
            $('#slInfo').text('Menampilkan ' + tampil + ' siswa (difilter)');
        }
        $('#slCari').on('input', terapkan);
        $('#slJurusan, #slStatus').on('change', terapkan);

        // ISI DATA MODAL SAAT TOMBOL HAPUS DIKLIK
        $(document).on('click', '.sl-hapus', function () {
            var d = $(this).data();
            $('#slHId').val(d.id); // <-- ISI ID KE DALAM FORM HIDDEN
            $('#slHNama, #slHNama2').text(d.nama);
            $('#slHNisn').text(d.nisn);
            $('#slHKelas').text(d.kelas);
            $('#slHInstansi').text(d.instansi);
            $('#slHGuru').text(d.guru);
            $('#slModalHapus').modal('show');
        });
    });
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';