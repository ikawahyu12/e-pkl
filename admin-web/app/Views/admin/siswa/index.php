<?php
// ===== 1. BLOK PHP ATAS (semua logika & variabel di sini) =====
$activePage   = 'data-siswa';   // HARUS 'data-siswa', bukan 'siswa'
$pageTitle    = 'Data Siswa PKL';
$pageSubtitle = 'Kelola data siswa dan alokasi tempat PKL.';

$pageHeaderActions = '
    <a href="#" class="btn btn-light btn-sm mr-2">Export Data</a>
    <a href="?page=create" class="btn btn-primary btn-sm">Tambah Siswa</a>';

// Asset (tidak hardcode nama folder)
$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';

// Fungsi escape: wajib di sini, karena dipakai sebelum layout dimuat
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ---------------------------------------------------------------------------
// 2. Data dummy (ganti dengan data dari database nanti)
// ---------------------------------------------------------------------------
$totalSiswa   = 348;
$siswaAktif   = 320;
$siswaSelesai = 28;

$siswaList = [
    ['id' => 1, 'nisn' => '0061829102', 'nama' => 'Ahmad Rizky Pratama', 'kelas' => 'XII RPL 1', 'kelas_lengkap' => 'XII RPL 1 (Rekayasa Perangkat Lunak)',   'instansi' => 'PT Telkom Indonesia',     'guru' => 'Budi Santoso, S.Kom.', 'status' => 'Aktif'],
    ['id' => 2, 'nisn' => '0064920193', 'nama' => 'Siti Nurhaliza',       'kelas' => 'XII DKV 2', 'kelas_lengkap' => 'XII DKV 2 (Desain Komunikasi Visual)',     'instansi' => 'Studio Animasi Semesta',  'guru' => 'Dewi Sartika, M.Sn.',  'status' => 'Aktif'],
    ['id' => 3, 'nisn' => '0062839104', 'nama' => 'Muhammad Faisal',      'kelas' => 'XII TKJ 1', 'kelas_lengkap' => 'XII TKJ 1 (Teknik Komputer & Jaringan)',   'instansi' => 'Lintasarta Datacenter',   'guru' => 'Eko Prasetyo, S.T.',   'status' => 'Aktif'],
    ['id' => 4, 'nisn' => '0069382012', 'nama' => 'Nabila Putri',         'kelas' => 'XII RPL 2', 'kelas_lengkap' => 'XII RPL 2 (Rekayasa Perangkat Lunak)',     'instansi' => 'CV Digital Optima',       'guru' => 'Budi Santoso, S.Kom.', 'status' => 'Selesai'],
    ['id' => 5, 'nisn' => '0061928371', 'nama' => 'Dimas Wicaksono',      'kelas' => 'XII TKJ 2', 'kelas_lengkap' => 'XII TKJ 2 (Teknik Komputer & Jaringan)',   'instansi' => 'Biznet Networks',         'guru' => 'Eko Prasetyo, S.T.',   'status' => 'Aktif'],
    ['id' => 6, 'nisn' => '0068472910', 'nama' => 'Sarah Safitri',        'kelas' => 'XII DKV 1', 'kelas_lengkap' => 'XII DKV 1 (Desain Komunikasi Visual)',     'instansi' => 'PT Kreatif Media Kreasi', 'guru' => 'Dewi Sartika, M.Sn.',  'status' => 'Aktif'],
    ['id' => 7, 'nisn' => '0063920194', 'nama' => 'Yoga Pratama',         'kelas' => 'XII RPL 1', 'kelas_lengkap' => 'XII RPL 1 (Rekayasa Perangkat Lunak)',     'instansi' => 'PT Finnet Indonesia',     'guru' => 'Budi Santoso, S.Kom.', 'status' => 'Selesai'],
];

// ---------------------------------------------------------------------------
// 3. Variabel untuk layouts/admin.php (judul, subjudul, tombol kanan atas)
// ---------------------------------------------------------------------------
$pageTitle    = 'Data Siswa PKL';
$pageSubtitle = 'Kelola data siswa dan alokasi tempat PKL.';
$activePage   = 'data-siswa';
$pageHeaderActions =
    '<button type="button" class="btn btn-sm sl-btn-export mr-2"><i class="fas fa-download mr-2"></i>Export Data</button>'
    . '<a href="?page=create" class="btn btn-sm sl-btn-add"><i class="fas fa-plus mr-2"></i>Tambah Siswa</a>';

// ---------------------------------------------------------------------------
// 4. Isi halaman
// ---------------------------------------------------------------------------
ob_start();
?>
<style>
    /* ---- Tombol di header (kanan atas) ---- */
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
    .sl-filters .sl-status { width: 100px; }
    .sl-field:focus { box-shadow: 0 0 0 2px rgba(37,99,235,.25); }

    /* ---- Tabel ---- */
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

    /* ---- Footer tabel + pagination ---- */
    .sl-foot { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; padding: .85rem 1rem; }
    .sl-info { font-size: .7rem; color: #6b7787; }
    .sl-pager { display: flex; align-items: center; gap: .35rem; margin: 0; padding: 0; list-style: none; }
    .sl-pager a { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 .35rem; border-radius: 5px; font-size: .7rem; color: #3b4a5e; text-decoration: none; }
    .sl-pager a:hover { background: #eef2f8; }
    .sl-pager .active a { background: #0b5f8a; color: #fff; }
    .sl-pager .disabled a { color: #3b4a5e; pointer-events: none; }

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

<div class="siswa-list">

    <!-- Statistik -->
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

    <!-- Pencarian & filter -->
    <div class="sl-toolbar mb-3">
        <div class="sl-search">
            <i class="fas fa-search"></i>
            <input type="search" id="slCari" class="sl-field" placeholder="Cari nama atau NISN..." aria-label="Cari nama atau NISN">
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

    <!-- Tabel -->
    <div class="sl-card mb-4">
        <div class="table-responsive">
            <table class="sl-table" id="slTabel">
                <thead>
                    <tr>
                        <th class="c-no" style="width:6%;">No</th>
                        <th style="width:11%;">NISN</th>
                        <th style="width:15%;">Nama Siswa</th>
                        <th style="width:11%;">Kelas / Jurusan</th>
                        <th style="width:15%;">Instansi Penempatan</th>
                        <th style="width:15%;">Guru Pembimbing</th>
                        <th style="width:11%;">Status</th>
                        <th style="width:16%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($siswaList as $i => $s): ?>
                        <tr data-kelas="<?= $e($s['kelas']) ?>" data-status="<?= $e($s['status']) ?>">
                            <td class="c-no"><?= $i + 1 ?></td>
                            <td class="sl-nisn"><?= $e($s['nisn']) ?></td>
                            <td class="sl-nama"><?= $e($s['nama']) ?></td>
                            <td><?= $e($s['kelas']) ?></td>
                            <td><?= $e($s['instansi']) ?></td>
                            <td><?= $e($s['guru']) ?></td>
                            <td>
                                <span class="sl-badge <?= $s['status'] === 'Selesai' ? 'sl-badge-selesai' : 'sl-badge-aktif' ?>"><?= $e($s['status']) ?></span>
                            </td>
                            <td class="sl-aksi">
                                <a class="a-detail" href="?page=detail&id=<?= (int) $s['id'] ?>">Detail</a>
                                <a class="a-edit" href="?page=edit&id=<?= (int) $s['id'] ?>">Edit</a>
                                <button type="button" class="a-hapus sl-hapus"
                                        data-nama="<?= $e($s['nama']) ?>"
                                        data-nisn="<?= $e($s['nisn']) ?>"
                                        data-kelas="<?= $e($s['kelas_lengkap']) ?>"
                                        data-instansi="<?= $e($s['instansi']) ?>"
                                        data-guru="<?= $e($s['guru']) ?>">Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="slKosong" style="display:none;">
                        <td colspan="8" class="text-center py-4 text-muted">Tidak ada data siswa yang sesuai.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sl-foot">
            <div class="sl-info" id="slInfo">Menampilkan 1-<?= count($siswaList) ?> dari <?= $e($totalSiswa) ?> siswa</div>
            <ul class="sl-pager" aria-label="Navigasi halaman">
                <li class="disabled"><a href="#">Sebelumnya</a></li>
                <li class="active"><a href="#">1</a></li>
                <li><a href="#">2</a></li>
                <li><a href="#">3</a></li>
                <li><a href="#">Selanjutnya</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Dialog konfirmasi hapus (UI saja, belum menghapus data) -->
<div class="modal fade sl-modal" id="slModalHapus" tabindex="-1" role="dialog" aria-labelledby="slModalHapusJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-2">
            <div class="modal-body">
                <div class="d-flex mb-3" style="gap:1rem;">
                    <div class="sl-trash"><i class="fas fa-trash-alt"></i></div>
                    <div>
                        <h5 class="font-weight-bold mb-1" id="slModalHapusJudul">Hapus Data Siswa?</h5>
                        <p class="small text-muted mb-0">
                            Apakah Anda yakin ingin menghapus data siswa
                            <strong id="slHNama">-</strong> (NISN: <span id="slHNisn">-</span>)?
                            Tindakan ini akan membatalkan status penempatan PKL dan seluruh riwayat jurnal serta presensi siswa terkait.
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
                    <span>Pastikan koordinasi dengan guru pembimbing dan pihak instansi sebelum menghapus siswa aktif.</span>
                </div>

                <div class="d-flex justify-content-end" style="gap:.5rem;">
                    <button type="button" class="btn btn-light border btn-sm px-4" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm px-3" id="slKonfirmasiHapus">
                        <i class="fas fa-trash-alt mr-1"></i> Ya, Hapus Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // jQuery dimuat layout di akhir halaman, jadi skrip dijalankan setelah semuanya siap.
    window.addEventListener('load', function () {
        var $ = window.jQuery;

        // Pencarian + filter (data dummy, di sisi browser)
        var total = <?= (int) $totalSiswa ?>;
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
            $('#slInfo').text(tampil === $('#slTabel tbody tr[data-status]').length
                ? 'Menampilkan 1-' + tampil + ' dari ' + total + ' siswa'
                : 'Menampilkan ' + tampil + ' siswa');
        }
        $('#slCari').on('input', terapkan);
        $('#slJurusan, #slStatus').on('change', terapkan);

        // Dialog hapus: isi dari atribut data-* tombol yang diklik
        $(document).on('click', '.sl-hapus', function () {
            var d = $(this).data();
            $('#slHNama, #slHNama2').text(d.nama);
            $('#slHNisn').text(d.nisn);
            $('#slHKelas').text(d.kelas);
            $('#slHInstansi').text(d.instansi);
            $('#slHGuru').text(d.guru);
            $('#slModalHapus').modal('show');
        });
        $('#slKonfirmasiHapus').on('click', function () {
            // TODO (backend): kirim permintaan hapus ke controller. Sementara hanya menutup dialog.
            $('#slModalHapus').modal('hide');
        });

        // Link pagination hanya tampilan
        $('.sl-pager a').on('click', function (ev) { ev.preventDefault(); });
    });
</script>
<?php
// 4. Simpan hasil tangkapan ke $content, lalu panggil layout PALING AKHIR
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';