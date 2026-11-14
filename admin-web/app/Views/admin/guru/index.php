<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
// ===== 1. BLOK PHP ATAS =====
$activePage   = 'data-guru';
$pageTitle    = 'Data Guru Pembimbing';
$pageSubtitle = 'Kelola data guru pembimbing dan alokasi kuota bimbingan PKL.';

$pageHeaderActions = '
    <button type="button" class="btn btn-sm sl-btn-export mr-2"><i class="fas fa-download mr-2"></i>Export Data</button>
    <a href="?page=create-guru" class="btn btn-sm sl-btn-add"><i class="fas fa-plus mr-2"></i>Tambah Guru</a>';

$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

require_once dirname(__DIR__, 3) . '/Support/AdminHttp.php';

// Proses hapus (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus-guru') {
    if (!guru_csrf_ok()) {
        admin_flash_set('error', 'Sesi formulir tidak valid. Coba lagi.');
    } else {
        $r = admin_api('DELETE', '/admin/guru/' . (int) ($_POST['id'] ?? 0));
        $r['status'] === 200
            ? admin_flash_set('success', 'Guru berhasil dihapus.')
            : admin_flash_set('error', admin_errors($r));
    }
    header('Location: ?page=data-guru');
    exit;
}

$flash = admin_flash_get();
// ===== 2. AMBIL DATA DARI API LARAVEL =====
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$apiBase = 'http://127.0.0.1:8000/api/v1';
$token   = $_SESSION['admin_token'] ?? '';

$apiGet = static function (string $path, array $query = []) use ($apiBase, $token): array {
    $url = $apiBase . $path . ($query ? '?' . http_build_query($query) : '');
    $ch  = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ],
    ]);

    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    $json = is_string($body) ? json_decode($body, true) : null;

    return [
        'ok'    => $code === 200 && is_array($json),
        'code'  => $code,
        'json'  => $json ?? [],
        'error' => $err
    ];
};

$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$perPage     = 5;

$resGuru = $apiGet('/admin/guru', [
    'page'       => $currentPage,
    'per_page'   => $perPage,
    'search'     => $_GET['q'] ?? '',
    'jurusan_id' => $_GET['jurusan'] ?? '',
    'status'     => $_GET['status'] ?? '',
]);

$resJurusan = $apiGet('/admin/jurusan');

$apiError = $resGuru['ok']
    ? null
    : ('Gagal mengambil data dari API (kode ' . $resGuru['code'] . ') ' . $resGuru['error']);

$jurusanList = $resJurusan['json']['data'] ?? [];

$meta = $resGuru['json']['meta'] ?? [
    'page'      => 1,
    'per_page'  => $perPage,
    'total'     => 0,
    'last_page' => 1
];

$stats = $resGuru['json']['stats'] ?? [
    'total_guru'       => 0,
    'pembimbing_aktif' => 0,
    'total_kuota'      => 0
];

// Petakan field API ke field yang dipakai tampilan
$guruList = array_map(static fn ($g) => [
    'id'              => $g['id'] ?? 0,
    'nip'             => $g['nip'] ?? '-',
    'nama'            => $g['nama_lengkap'] ?? '-',
    'jurusan'         => $g['jurusan_kode'] ?? '-',
    'jurusan_lengkap' => $g['jurusan_nama'] ?? '-',
    'kontak'          => $g['no_hp'] ?? '-',
    'email'           => $g['email'] ?? '',
    'kuota'           => $g['kuota_bimbingan'] ?? 0,
    'terisi'          => $g['kuota_terpakai'] ?? 0,
    'status'          => ($g['status_bimbingan'] ?? '') === 'aktif'
        ? 'Aktif'
        : 'Non-Aktif',
], $resGuru['json']['data'] ?? []);

$totalGuru   = (int) ($meta['total'] ?? 0);
$totalPages  = max(1, (int) ($meta['last_page'] ?? 1));
$currentPage = (int) ($meta['page'] ?? 1);
$offset      = ($currentPage - 1) * (int) ($meta['per_page'] ?? $perPage);
$guruAktif   = (int) ($stats['pembimbing_aktif'] ?? 0);
$totalKuota  = (int) ($stats['total_kuota'] ?? 0);

function getPageUrl(int $p): string {
    $params = $_GET;
    $params['p'] = $p;
    return '?' . http_build_query($params);
}



// ---------------------------------------------------------------------------
// 3. Isi halaman
// ---------------------------------------------------------------------------
ob_start();
?>
<style>
    /* ---- Tombol di header ---- */
    .sl-btn-export, .sl-btn-add { border: 0; border-radius: 8px; padding: .5rem .9rem; font-size: .78rem; font-weight: 600; }
    .sl-btn-export { background: #dbe7f6; color: #1e293b; }
    .sl-btn-export:hover { background: #cddff2; color: #1e293b; }
    .sl-btn-add { background: #2563eb; color: #fff; }
    .sl-btn-add:hover { background: #1d4ed8; color: #fff; }

    /* ---- Kartu statistik ---- */
    .sl-stat { display: flex; align-items: center; gap: .9rem; background: #fff; border-radius: 12px; padding: .9rem 1rem; box-shadow: 0 1px 3px rgba(16,24,40,.07); height: 100%; }
    .sl-stat-icon { width: 46px; height: 46px; border-radius: 10px; background: #eaf1fb; color: #0b5f8a; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; }
    .sl-stat-label { font-size: .68rem; color: #475569; line-height: 1.2; }
    .sl-stat-value { font-size: 1.45rem; font-weight: 800; line-height: 1.15; }

    /* ---- Pencarian & filter ---- */
    .sl-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .6rem; background: #fff; border-radius: 12px; padding: .55rem; box-shadow: 0 1px 3px rgba(16,24,40,.07); }
    .sl-search { position: relative; flex: 0 1 230px; }
    .sl-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: .78rem; }
    .sl-field { height: 34px; border: 0; border-radius: 8px; background: #eaf1fb; font-size: .75rem; color: #3b4a5e; outline: 0; }
    .sl-search .sl-field { width: 100%; padding: 0 .75rem 0 34px; }
    .sl-search .sl-field::placeholder { color: #64748b; }
    .sl-filters { display: flex; gap: .4rem; }
    .sl-filters select.sl-field { -webkit-appearance: none; -moz-appearance: none; appearance: none; padding: 0 .9rem; cursor: pointer; }
    .sl-filters .sl-jurusan { width: 175px; }
    .sl-filters .sl-status { width: 110px; }
    .sl-field:focus { box-shadow: 0 0 0 2px rgba(37,99,235,.25); }

    /* ---- Tabel ---- */
    .sl-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(16,24,40,.07); overflow: hidden; }
    .sl-table { width: 100%; min-width: 940px; table-layout: fixed; border-collapse: collapse; }
    .sl-table th { background: #f1f5fb; color: #3b4a5e; font-size: .66rem; font-weight: 600; text-transform: uppercase; letter-spacing: .02em; padding: .8rem .6rem; text-align: left; border: 0; }
    .sl-table td { padding: .85rem .6rem; font-size: .72rem; color: #4b586b; vertical-align: middle; border: 0; border-bottom: 1px solid #f0f3f8; }
    .sl-table tr:last-child td { border-bottom: 0; }
    .sl-table .c-no { text-align: center; }
    .sl-nip { font-weight: 700; color: #0f1d3a; }
    .sl-nama { font-weight: 700; color: #0f1d3a; font-size: .82rem; }

    .sl-badge { display: inline-block; padding: .12rem .6rem; border-radius: 999px; font-size: .62rem; font-weight: 500; }
    .sl-badge-aktif { background: #dbe8fb; color: #2a5aa0; }
    .sl-badge-nonaktif { background: #fee2e2; color: #991b1b; }

    .sl-aksi { white-space: nowrap; }
    .sl-aksi a, .sl-aksi button { font-size: .66rem; margin-right: .45rem; padding: 0; border: 0; background: none; text-decoration: none; cursor: pointer; }
    .sl-aksi .a-detail { color: #1a2438; }
    .sl-aksi .a-edit { color: #2563eb; }
    .sl-aksi .a-hapus { color: #e0264a; }

    /* ---- Footer tabel + pagination ---- */
    .sl-foot { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; padding: .85rem 1rem; }
    .sl-info { font-size: .7rem; color: #6b7787; }
    .sl-pager { display: flex; align-items: center; gap: .35rem; margin: 0; padding: 0; list-style: none; }
    .sl-pager a, .sl-pager span { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 28px; padding: 0 .4rem; border-radius: 6px; font-size: .72rem; color: #3b4a5e; text-decoration: none; font-weight: 500; }
    .sl-pager a:hover { background: #eef2f8; color: #0b5f8a; }
    .sl-pager .active span { background: #0b5f8a; color: #fff; font-weight: 700; }
    .sl-pager .disabled span { color: #94a3b8; cursor: not-allowed; opacity: .6; }

    /* ---- Dialog hapus ---- */
    .sl-modal .modal-content { border: 0; border-radius: 16px; }
    .sl-modal .sl-trash { width: 44px; height: 44px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .sl-modal .sl-ringkas { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px; padding: .7rem 1rem; font-size: .78rem; }
    .sl-modal .sl-ringkas div { display: flex; justify-content: space-between; gap: 1rem; padding: .2rem 0; }
    .sl-modal .sl-ringkas div span:first-child { color: #64748b; }
    .sl-modal .sl-ringkas div span:last-child { font-weight: 600; text-align: right; }
    .sl-modal .sl-warn { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; border-radius: 10px; padding: .6rem .85rem; font-size: .76rem; }

    /* ---- Layar kecil ---- */
    @media (max-width: 575.98px) {
        .sl-search { flex: 1 1 100%; }
        .sl-filters { width: 100%; }
        .sl-filters .sl-jurusan, .sl-filters .sl-status { flex: 1; width: auto; }
        .sl-foot { justify-content: center; }
    }
</style>

<div class="guru-list">

    <!-- Statistik -->
    <div class="row mb-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-user-tie"></i></span>
                <div>
                    <div class="sl-stat-label">Total Guru Pembimbing</div>
                    <div class="sl-stat-value" style="color:#0f1d3a;"><?= $e($stats['total_guru']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-user-check"></i></span>
                <div>
                    <div class="sl-stat-label">Pembimbing Aktif</div>
                    <div class="sl-stat-value" style="color:#0c6aa6;"><?= $e($stats['pembimbing_aktif']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-users-cog"></i></span>
                <div>
                    <div class="sl-stat-label">Total Kapasitas Kuota</div>
                    <div class="sl-stat-value" style="color:#0a5d7a;"><?= $e($stats['total_kuota']) ?> Siswa</div>
                </div>
            </div>
        </div>
    </div>

        <!-- Pencarian & filter -->
    <div class="sl-toolbar mb-3">
        <div class="sl-search">
            <i class="fas fa-search"></i>
            <input type="search"
                   id="slCari"
                   class="sl-field"
                   placeholder="Cari nama atau NIP..."
                   value="<?= $e($_GET['q'] ?? '') ?>"
                   aria-label="Cari nama atau NIP">
        </div>

        <div class="sl-filters">
            <select id="slJurusan" class="sl-field sl-jurusan" aria-label="Filter jurusan">
                <option value="">Semua Jurusan</option>

                <?php foreach ($jurusanList as $j): ?>
                    <option value="<?= (int) $j['id'] ?>"
                        <?= ($_GET['jurusan'] ?? '') == $j['id'] ? 'selected' : '' ?>>
                        <?= $e($j['kode']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="slStatus" class="sl-field sl-status" aria-label="Filter status">
                <option value="">Status: Semua</option>
                <option value="aktif" <?= ($_GET['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>
                    Aktif
                </option>
                <option value="nonaktif" <?= ($_GET['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>
                    Non-Aktif
                </option>
            </select>
        </div>
    </div>

    <!-- Pesan error API -->
            <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= $e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($apiError): ?>
        <div class="alert alert-danger"><?= $e($apiError) ?></div>
    <?php endif; ?>

    <!-- Tabel -->
    <div class="sl-card mb-4">
        <div class="table-responsive">
            <table class="sl-table" id="slTabel">
                <thead>
                    <tr>
                        <th class="c-no" style="width:5%;">No</th>
                        <th style="width:14%;">NIP / NUPTK</th>
                        <th style="width:18%;">Nama Guru</th>
                        <th style="width:12%;">Keahlian</th>
                        <th style="width:15%;">Kontak / Email</th>
                        <th style="width:12%;">Kuota Bimbingan</th>
                        <th style="width:10%;">Status</th>
                        <th style="width:14%;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($guruList)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                Tidak ada data guru pembimbing.
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($guruList as $i => $g): ?>
                            <tr data-jurusan="<?= $e($g['jurusan']) ?>"
                                data-status="<?= $e($g['status']) ?>">

                                <td class="c-no">
                                    <?= $offset + $i + 1 ?>
                                </td>

                                <td class="sl-nip">
                                    <?= $e($g['nip']) ?>
                                </td>

                                <td class="sl-nama">
                                    <?= $e($g['nama']) ?>
                                </td>

                                <td>
                                    <?= $e($g['jurusan']) ?>
                                </td>

                                <td>
                                    <div><?= $e($g['kontak']) ?></div>
                                    <small class="text-muted">
                                        <?= $e($g['email']) ?>
                                    </small>
                                </td>

                                <td>
                                    <strong><?= $e($g['terisi']) ?></strong>
                                    / <?= $e($g['kuota']) ?> Siswa
                                </td>

                                <td>
                                    <span class="sl-badge <?= $g['status'] === 'Aktif' ? 'sl-badge-aktif' : 'sl-badge-nonaktif' ?>">
                                        <?= $e($g['status']) ?>
                                    </span>
                                </td>

                                <td class="sl-aksi">
                                    <a class="a-detail"
                                       href="?page=detail-guru&id=<?= (int) $g['id'] ?>">Detail</a>

                                    <a class="a-edit"
                                       href="?page=edit-guru&id=<?= (int) $g['id'] ?>">Edit</a>

                                    <button type="button"
                                            class="a-hapus sl-hapus"
                                            data-id="<?= (int) $g['id'] ?>"
                                            data-nama="<?= $e($g['nama']) ?>"
                                            data-nip="<?= $e($g['nip']) ?>"
                                            data-jurusan="<?= $e($g['jurusan_lengkap']) ?>"
                                            data-kontak="<?= $e($g['kontak']) ?>"
                                            data-terisi="<?= $e($g['terisi']) ?>">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer tabel + Pagination -->
        <div class="sl-foot">
            <?php
                $startItem = $totalGuru > 0 ? $offset + 1 : 0;
                $endItem   = $offset + count($guruList);
            ?>

            <div class="sl-info" id="slInfo">
                Menampilkan <?= $startItem ?>-<?= $endItem ?>
                dari <?= $totalGuru ?> guru
            </div>

            <ul class="sl-pager" aria-label="Navigasi halaman">

                <?php if ($currentPage > 1): ?>
                    <li>
                        <a href="<?= getPageUrl($currentPage - 1) ?>">Sebelumnya</a>
                    </li>
                <?php else: ?>
                    <li class="disabled"><span>Sebelumnya</span></li>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($p === $currentPage): ?>
                        <li class="active"><span><?= $p ?></span></li>
                    <?php else: ?>
                        <li>
                            <a href="<?= getPageUrl($p) ?>"><?= $p ?></a>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li>
                        <a href="<?= getPageUrl($currentPage + 1) ?>">Selanjutnya</a>
                    </li>
                <?php else: ?>
                    <li class="disabled"><span>Selanjutnya</span></li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</div>

<div class="modal fade sl-modal" id="slModalHapus" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-2">
            <form method="POST" action="?page=data-guru" class="modal-body">
                <input type="hidden" name="aksi" value="hapus-guru">
                <input type="hidden" name="csrf" value="<?= $e(guru_csrf()) ?>">
                <input type="hidden" name="id" id="slHId">

                <div class="d-flex mb-3" style="gap:1rem;">
                    <div class="sl-trash"><i class="fas fa-trash-alt"></i></div>
                    <div>
                        <h5 class="font-weight-bold mb-1">Hapus Data Guru?</h5>
                        <p class="small text-muted mb-0">Yakin menghapus <strong id="slHNama"></strong>? Akun login guru ini juga ikut terhapus.</p>
                    </div>
                </div>
                <div class="sl-ringkas mb-3">
                    <div><span>Nama</span><span id="slHNama2"></span></div>
                    <div><span>NIP</span><span id="slHNip"></span></div>
                    <div><span>Jurusan</span><span id="slHJurusan"></span></div>
                    <div><span>Kontak</span><span id="slHKontak"></span></div>
                    <div><span>Siswa dibimbing</span><span id="slHTerisi"></span></div>
                </div>
                <div class="sl-warn mb-3">Guru yang masih tercatat di penempatan PKL tidak bisa dihapus. Ubah statusnya menjadi Non-Aktif sebagai gantinya.</div>
                <div class="d-flex justify-content-end" style="gap:.5rem;">
                    <button type="button" class="btn btn-light border btn-sm px-4" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load', function () {
        var $ = window.jQuery;

        function cari() {
            var p = new URLSearchParams(window.location.search);
            p.set('page', 'data-guru');
            p.delete('p');
            ['q:#slCari', 'jurusan:#slJurusan', 'status:#slStatus'].forEach(function (x) {
                var k = x.split(':')[0];
                var v = $(x.split(':')[1]).val();
                v ? p.set(k, v) : p.delete(k);
            });
            window.location.search = p.toString();
        }

        $('#slCari').on('keydown', function (e) { if (e.key === 'Enter') { cari(); } });
        $('#slJurusan, #slStatus').on('change', cari);

        $(document).on('click', '.sl-hapus', function () {
            var d = $(this).data();
            $('#slHId').val(d.id);
            $('#slHNama, #slHNama2').text(d.nama);
            $('#slHNip').text(d.nip);
            $('#slHJurusan').text(d.jurusan);
            $('#slHKontak').text(d.kontak);
            $('#slHTerisi').text(d.terisi + ' Siswa');
            $('#slModalHapus').modal('show');
        });
    });
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';