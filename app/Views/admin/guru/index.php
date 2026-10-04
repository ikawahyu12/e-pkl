<?php
// ===== 1. BLOK PHP ATAS (semua logika & variabel di sini) =====
$activePage   = 'data-guru';   // HARUS 'data-guru'
$pageTitle    = 'Data Guru Pembimbing';
$pageSubtitle = 'Kelola data guru pembimbing dan alokasi kuota bimbingan PKL.';

$pageHeaderActions = '
    <button type="button" class="btn btn-sm sl-btn-export mr-2"><i class="fas fa-download mr-2"></i>Export Data</button>
    <a href="?page=create" class="btn btn-sm sl-btn-add"><i class="fas fa-plus mr-2"></i>Tambah Guru</a>';

// Asset (tidak hardcode nama folder)
$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';

// Fungsi escape: wajib di sini, karena dipakai sebelum layout dimuat
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ---------------------------------------------------------------------------
// 2. Data dummy Guru Pembimbing
// ---------------------------------------------------------------------------
$totalGuru  = 24;
$guruAktif  = 21;
$totalKuota = 350;

$guruList = [
    ['id' => 1, 'nip' => '198203152008011003', 'nama' => 'Budi Santoso, S.Kom.', 'jurusan' => 'RPL', 'jurusan_lengkap' => 'Rekayasa Perangkat Lunak', 'kontak' => '0812-3456-7890', 'email' => 'budi.santoso@smk.sch.id', 'kuota' => 15, 'terisi' => 12, 'status' => 'Aktif'],
    ['id' => 2, 'nip' => '198504122010012005', 'nama' => 'Dewi Sartika, M.Sn.',  'jurusan' => 'DKV', 'jurusan_lengkap' => 'Desain Komunikasi Visual', 'kontak' => '0813-9876-5432', 'email' => 'dewi.sartika@smk.sch.id', 'kuota' => 12, 'terisi' => 10, 'status' => 'Aktif'],
    ['id' => 3, 'nip' => '197911202005011002', 'nama' => 'Eko Prasetyo, S.T.',   'jurusan' => 'TKJ', 'jurusan_lengkap' => 'Teknik Komputer & Jaringan', 'kontak' => '0811-2233-4455', 'email' => 'eko.prasetyo@smk.sch.id', 'kuota' => 15, 'terisi' => 15, 'status' => 'Aktif'],
    ['id' => 4, 'nip' => '199001082015042001', 'nama' => 'Rina Astuti, S.Pd.',    'jurusan' => 'RPL', 'jurusan_lengkap' => 'Rekayasa Perangkat Lunak', 'kontak' => '0857-1122-3344', 'email' => 'rina.astuti@smk.sch.id', 'kuota' => 10, 'terisi' => 0,  'status' => 'Non-Aktif'],
    ['id' => 5, 'nip' => '198807142012011004', 'nama' => 'Hendra Wijaya, M.T.',  'jurusan' => 'TKJ', 'jurusan_lengkap' => 'Teknik Komputer & Jaringan', 'kontak' => '0821-4455-6677', 'email' => 'hendra.w@smk.sch.id',      'kuota' => 15, 'terisi' => 8,  'status' => 'Aktif'],
];

// ---------------------------------------------------------------------------
// 3. Isi halaman
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

<div class="guru-list">

    <!-- Statistik -->
    <div class="row mb-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-user-tie"></i></span>
                <div>
                    <div class="sl-stat-label">Total Guru Pembimbing</div>
                    <div class="sl-stat-value" style="color:#0f1d3a;"><?= $e($totalGuru) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-user-check"></i></span>
                <div>
                    <div class="sl-stat-label">Pembimbing Aktif</div>
                    <div class="sl-stat-value" style="color:#0c6aa6;"><?= $e($guruAktif) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sl-stat">
                <span class="sl-stat-icon"><i class="fas fa-users-cog"></i></span>
                <div>
                    <div class="sl-stat-label">Total Kapasitas Kuota</div>
                    <div class="sl-stat-value" style="color:#0a5d7a;"><?= $e($totalKuota) ?> Siswa</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pencarian & filter -->
    <div class="sl-toolbar mb-3">
        <div class="sl-search">
            <i class="fas fa-search"></i>
            <input type="search" id="slCari" class="sl-field" placeholder="Cari nama atau NIP..." aria-label="Cari nama atau NIP">
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
                <option value="Non-Aktif">Non-Aktif</option>
            </select>
        </div>
    </div>

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
                    <?php foreach ($guruList as $i => $g): ?>
                        <tr data-jurusan="<?= $e($g['jurusan']) ?>" data-status="<?= $e($g['status']) ?>">
                            <td class="c-no"><?= $i + 1 ?></td>
                            <td class="sl-nip"><?= $e($g['nip']) ?></td>
                            <td class="sl-nama"><?= $e($g['nama']) ?></td>
                            <td><?= $e($g['jurusan']) ?></td>
                            <td>
                                <div><?= $e($g['kontak']) ?></div>
                                <small class="text-muted"><?= $e($g['email']) ?></small>
                            </td>
                            <td>
                                <strong><?= $e($g['terisi']) ?></strong> / <?= $e($g['kuota']) ?> Siswa
                            </td>
                            <td>
                                <span class="sl-badge <?= $g['status'] === 'Aktif' ? 'sl-badge-aktif' : 'sl-badge-nonaktif' ?>"><?= $e($g['status']) ?></span>
                            </td>
                            <td class="sl-aksi">
                                <a class="a-detail" href="?page=detail&id=<?= (int) $g['id'] ?>">Detail</a>
                                <a class="a-edit" href="?page=edit&id=<?= (int) $g['id'] ?>">Edit</a>
                                <button type="button" class="a-hapus sl-hapus"
                                        data-nama="<?= $e($g['nama']) ?>"
                                        data-nip="<?= $e($g['nip']) ?>"
                                        data-jurusan="<?= $e($g['jurusan_lengkap']) ?>"
                                        data-kontak="<?= $e($g['kontak']) ?>"
                                        data-terisi="<?= $e($g['terisi']) ?>">Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="slKosong" style="display:none;">
                        <td colspan="8" class="text-center py-4 text-muted">Tidak ada data guru pembimbing yang sesuai.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sl-foot">
            <div class="sl-info" id="slInfo">Menampilkan 1-<?= count($guruList) ?> dari <?= $e($totalGuru) ?> guru</div>
            <ul class="sl-pager" aria-label="Navigasi halaman">
                <li class="disabled"><a href="#">Sebelumnya</a></li>
                <li class="active"><a href="#">1</a></li>
                <li><a href="#">2</a></li>
                <li><a href="#">Selanjutnya</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Dialog konfirmasi hapus -->
<div class="modal fade sl-modal" id="slModalHapus" tabindex="-1" role="dialog" aria-labelledby="slModalHapusJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-2">
            <div class="modal-body">
                <div class="d-flex mb-3" style="gap:1rem;">
                    <div class="sl-trash"><i class="fas fa-trash-alt"></i></div>
                    <div>
                        <h5 class="font-weight-bold mb-1" id="slModalHapusJudul">Hapus Data Guru Pembimbing?</h5>
                        <p class="small text-muted mb-0">
                            Apakah Anda yakin ingin menghapus data guru pembimbing
                            <strong id="slHNama">-</strong> (NIP: <span id="slHNip">-</span>)?
                            Tindakan ini akan membatalkan alokasi bimbingan pada siswa yang terhubung.
                        </p>
                    </div>
                </div>

                <div class="sl-ringkas mb-3">
                    <div><span>Nama Guru:</span><span id="slHNama2">-</span></div>
                    <div><span>Program Keahlian:</span><span id="slHJurusan">-</span></div>
                    <div><span>No. Kontak:</span><span id="slHKontak" class="text-primary">-</span></div>
                    <div><span>Siswa Bimbingan Aktif:</span><span id="slHTerisi" class="text-danger">-</span></div>
                </div>

                <div class="sl-warn d-flex mb-3" style="gap:.5rem;">
                    <i class="fas fa-exclamation-triangle mt-1"></i>
                    <span>Pastikan telah memindahkan alokasi siswa bimbingan ke guru lain sebelum menghapus.</span>
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
    window.addEventListener('load', function () {
        var $ = window.jQuery;

        var total = <?= (int) $totalGuru ?>;
        function terapkan() {
            var q = $('#slCari').val().toLowerCase().trim();
            var j = $('#slJurusan').val();
            var st = $('#slStatus').val();
            var tampil = 0;
            $('#slTabel tbody tr[data-status]').each(function () {
                var $r = $(this);
                var cocok = (!q || $r.text().toLowerCase().indexOf(q) > -1)
                    && (!j || String($r.data('jurusan')).indexOf(j) > -1)
                    && (!st || $r.data('status') === st);
                $r.toggle(cocok);
                if (cocok) { tampil++; }
            });
            $('#slKosong').toggle(tampil === 0);
            $('#slInfo').text(tampil === $('#slTabel tbody tr[data-status]').length
                ? 'Menampilkan 1-' + tampil + ' dari ' + total + ' guru'
                : 'Menampilkan ' + tampil + ' guru');
        }
        $('#slCari').on('input', terapkan);
        $('#slJurusan, #slStatus').on('change', terapkan);

        // Dialog hapus
        $(document).on('click', '.sl-hapus', function () {
            var d = $(this).data();
            $('#slHNama, #slHNama2').text(d.nama);
            $('#slHNip').text(d.nip);
            $('#slHJurusan').text(d.jurusan);
            $('#slHKontak').text(d.kontak);
            $('#slHTerisi').text(d.terisi + ' Siswa');
            $('#slModalHapus').modal('show');
        });
        $('#slKonfirmasiHapus').on('click', function () {
            $('#slModalHapus').modal('hide');
        });

        $('.sl-pager a').on('click', function (ev) { ev.preventDefault(); });
    });
</script>
<?php
// Simpan hasil ke $content & panggil layout
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';