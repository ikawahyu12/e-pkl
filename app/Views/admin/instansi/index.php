<?php
// ===== 1. BLOK PHP ATAS (semua logika & variabel di sini) =====
$activePage   = 'instansi-mitra';   // HARUS 'instansi-mitra' untuk active menu
$pageTitle    = 'Data Instansi Mitra';
$pageSubtitle = 'Kelola data instansi/perusahaan mitra dan kuota penempatan PKL.';

$pageHeaderActions = '
    <button type="button" class="btn btn-sm sl-btn-export mr-2"><i class="fas fa-download mr-2"></i>Export Data</button>
    <a href="?page=mitra-create" class="btn btn-sm sl-btn-add"><i class="fas fa-plus mr-2"></i>Tambah Instansi</a>';

// Asset (tidak hardcode nama folder)
$scriptDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetBaseUrl = $scriptDir . '/assets';

// Fungsi escape: wajib di sini, karena dipakai sebelum layout dimuat
$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ---------------------------------------------------------------------------
// 2. Data dummy Instansi Mitra
// ---------------------------------------------------------------------------
$totalMitra   = 42;
$totalKuota   = 185;
$terisi       = 148;

$mitraList = [
    [
        'id'                  => 1,
        'nama_instansi'       => 'PT Telkom Indonesia (Persero) Tbk',
        'alamat'              => 'Jl. Pahlawan No. 12, Surabaya',
        'pembimbing_lapangan' => 'Budi Santoso, S.Kom.',
        'telepon'             => '081234567890',
        'kuota'               => 10,
        'terisi'              => 8,
        'sektor'              => 'Teknologi Informasi'
    ],
    [
        'id'                  => 2,
        'nama_instansi'       => 'Studio Animasi Semesta',
        'alamat'              => 'Jl. Pemuda No. 45, Malang',
        'pembimbing_lapangan' => 'Dewi Sartika, M.Sn.',
        'telepon'             => '082198765432',
        'kuota'               => 5,
        'terisi'              => 5,
        'sektor'              => 'Industri Kreatif'
    ],
    [
        'id'                  => 3,
        'nama_instansi'       => 'Lintasarta Datacenter',
        'alamat'              => 'Jl. Jend. Sudirman No. 88, Jakarta',
        'pembimbing_lapangan' => 'Eko Prasetyo, S.T.',
        'telepon'             => '085678901234',
        'kuota'               => 8,
        'terisi'              => 6,
        'sektor'              => 'Jaringan & Telekomunikasi'
    ],
    [
        'id'                  => 4,
        'nama_instansi'       => 'CV Digital Optima',
        'alamat'              => 'Jl. Anggrek No. 15, Kediri',
        'pembimbing_lapangan' => 'Rina Wijaya, S.T.',
        'telepon'             => '087812345678',
        'kuota'               => 4,
        'terisi'              => 2,
        'sektor'              => 'Teknologi Informasi'
    ],
    [
        'id'                  => 5,
        'nama_instansi'       => 'Biznet Networks',
        'alamat'              => 'Jl. Veteran No. 20, Kediri',
        'pembimbing_lapangan' => 'Ahmad Fauzi, S.Kom.',
        'telepon'             => '081901234567',
        'kuota'               => 6,
        'terisi'              => 6,
        'sektor'              => 'Jaringan & Telekomunikasi'
    ],
    [
        'id'                  => 6,
        'nama_instansi'       => 'PT Kreatif Media Kreasi',
        'alamat'              => 'Jl. Basuki Rahmat No. 3, Malang',
        'pembimbing_lapangan' => 'Hendra Gunawan',
        'telepon'             => '083890123456',
        'kuota'               => 3,
        'terisi'              => 1,
        'sektor'              => 'Industri Kreatif'
    ],
    [
        'id'                  => 7,
        'nama_instansi'       => 'Dinas Komunikasi dan Informatika',
        'alamat'              => 'Jl. Soekarno Hatta No. 1, Kediri',
        'pembimbing_lapangan' => 'Bambang Supriyadi, S.H.',
        'telepon'             => '081345678901',
        'kuota'               => 12,
        'terisi'              => 10,
        'sektor'              => 'Pemerintahan'
    ],
];

// ---------------------------------------------------------------------------
// 3. Isi halaman
// ---------------------------------------------------------------------------
ob_start();
?>
<style>
    /* ---- Tombol di header (kanan atas) ---- */
    .sl-btn-export,
    .sl-btn-add {
        border: 0;
        border-radius: 8px;
        padding: .5rem .9rem;
        font-size: .78rem;
        font-weight: 600;
    }

    .sl-btn-export {
        background: #dbe7f6;
        color: #1e293b;
    }

    .sl-btn-export:hover {
        background: #cddff2;
        color: #1e293b;
    }

    .sl-btn-add {
        background: #2563eb;
        color: #fff;
    }

    .sl-btn-add:hover {
        background: #1d4ed8;
        color: #fff;
    }

    /* ---- Kartu statistik ---- */
    .sl-stat {
        display: flex;
        align-items: center;
        gap: .9rem;
        background: #fff;
        border-radius: 12px;
        padding: .9rem 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, .07);
        height: 100%;
    }

    .sl-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        background: #eaf1fb;
        color: #0b5f8a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .sl-stat-label {
        font-size: .68rem;
        color: #475569;
        line-height: 1.2;
    }

    .sl-stat-value {
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.15;
    }

    /* ---- Pencarian & filter ---- */
    .sl-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: .6rem;
        background: #fff;
        border-radius: 12px;
        padding: .55rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, .07);
    }

    .sl-search {
        position: relative;
        flex: 0 1 230px;
    }

    .sl-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: .78rem;
    }

    .sl-field {
        height: 34px;
        border: 0;
        border-radius: 8px;
        background: #eaf1fb;
        font-size: .75rem;
        color: #3b4a5e;
        outline: 0;
    }

    .sl-search .sl-field {
        width: 100%;
        padding: 0 .75rem 0 34px;
    }

    .sl-search .sl-field::placeholder {
        color: #64748b;
    }

    .sl-filters {
        display: flex;
        gap: .4rem;
    }

    .sl-filters select.sl-field {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding: 0 .9rem;
        cursor: pointer;
    }

    .sl-filters .sl-sektor {
        width: 185px;
    }

    .sl-field:focus {
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .25);
    }

    /* ---- Tabel ---- */
    .sl-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(16, 24, 40, .07);
        overflow: hidden;
    }

    .sl-table {
        width: 100%;
        min-width: 940px;
        table-layout: fixed;
        border-collapse: collapse;
    }

    .sl-table th {
        background: #f1f5fb;
        color: #3b4a5e;
        font-size: .66rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .02em;
        padding: .8rem .6rem;
        text-align: left;
        border: 0;
    }

    .sl-table td {
        padding: .85rem .6rem;
        font-size: .72rem;
        color: #4b586b;
        vertical-align: middle;
        border: 0;
        border-bottom: 1px solid #f0f3f8;
    }

    .sl-table tr:last-child td {
        border-bottom: 0;
    }

    .sl-table .c-no {
        text-align: center;
    }

    .sl-nama {
        font-weight: 700;
        color: #0f1d3a;
        font-size: .82rem;
    }

    .sl-badge {
        display: inline-block;
        padding: .12rem .6rem;
        border-radius: 999px;
        font-size: .62rem;
        font-weight: 500;
    }

    .sl-badge-tersedia {
        background: #dbe8fb;
        color: #2a5aa0;
    }

    .sl-badge-penuh {
        background: #fee2e2;
        color: #dc2626;
    }

    .sl-aksi {
        white-space: nowrap;
    }

    .sl-aksi a,
    .sl-aksi button {
        font-size: .66rem;
        margin-right: .45rem;
        padding: 0;
        border: 0;
        background: none;
        text-decoration: none;
        cursor: pointer;
    }

    .sl-aksi .a-detail {
        color: #1a2438;
    }

    .sl-aksi .a-edit {
        color: #2563eb;
    }

    .sl-aksi .a-hapus {
        color: #e0264a;
    }

    /* ---- Footer tabel + pagination ---- */
    .sl-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem;
        padding: .85rem 1rem;
    }

    .sl-info {
        font-size: .7rem;
        color: #6b7787;
    }

    .sl-pager {
        display: flex;
        align-items: center;
        gap: .35rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .sl-pager a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        padding: 0 .35rem;
        border-radius: 5px;
        font-size: .7rem;
        color: #3b4a5e;
        text-decoration: none;
    }

    .sl-pager a:hover {
        background: #eef2f8;
    }

    .sl-pager .active a {
        background: #0b5f8a;
        color: #fff;
    }

    .sl-pager .disabled a {
        color: #3b4a5e;
        pointer-events: none;
    }

    /* ---- Dialog hapus ---- */
    .sl-modal .modal-content {
        border: 0;
        border-radius: 16px;
    }

    .sl-modal .sl-trash {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #fee2e2;
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sl-modal .sl-ringkas {
        background: #f8fafc;
        border: 1px solid #eef2f7;
        border-radius: 10px;
        padding: .7rem 1rem;
        font-size: .78rem;
    }

    .sl-modal .sl-ringkas div {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .2rem 0;
    }

    .sl-modal .sl-ringkas div span:first-child {
        color: #64748b;
    }

    .sl-modal .sl-ringkas div span:last-child {
        font-weight: 600;
        text-align: right;
    }

    .sl-modal .sl-warn {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #b45309;
        border-radius: 10px;
        padding: .6rem .85rem;
        font-size: .76rem;
    }

    /* ---- Layar kecil ---- */
    @media (max-width: 575.98px) {
        .sl-search {
            flex: 1 1 100%;
        }

        .sl-filters {
            width: 100%;
        }

        .sl-filters .sl-sektor {
            flex: 1;
            width: auto;
        }

        .sl-foot {
            justify-content: center;
        }
    }
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

    <!-- Pencarian & filter -->
    <div class="sl-toolbar mb-3">
        <div class="sl-search">
            <i class="fas fa-search"></i>
            <input type="search" id="slCari" class="sl-field" placeholder="Cari instansi atau alamat..." aria-label="Cari instansi atau alamat">
        </div>
        <div class="sl-filters">
            <select id="slSektor" class="sl-field sl-sektor" aria-label="Filter Sektor">
                <option value="">Semua Sektor / Bidang</option>
                <option value="Teknologi Informasi">Teknologi Informasi</option>
                <option value="Industri Kreatif">Industri Kreatif</option>
                <option value="Jaringan & Telekomunikasi">Jaringan & Telekomunikasi</option>
                <option value="Pemerintahan">Pemerintahan</option>
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
                        <th style="width:20%;">Nama Instansi</th>
                        <th style="width:22%;">Alamat Lengkap</th>
                        <th style="width:16%;">Penanggung Jawab</th>
                        <th style="width:12%;">No. Telepon</th>
                        <th style="width:11%;">Kuota PKL</th>
                        <th style="width:14%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mitraList as $i => $m):
                        $isPenuh = $m['terisi'] >= $m['kuota'];
                    ?>
                        <tr data-sektor="<?= $e($m['sektor']) ?>">
                            <td class="c-no"><?= $i + 1 ?></td>
                            <td class="sl-nama"><?= $e($m['nama_instansi']) ?></td>
                            <td><?= $e($m['alamat']) ?></td>
                            <td><?= $e($m['pembimbing_lapangan']) ?></td>
                            <td><?= $e($m['telepon']) ?></td>
                            <td>
                                <span class="sl-badge <?= $isPenuh ? 'sl-badge-penuh' : 'sl-badge-tersedia' ?>">
                                    <?= $e($m['terisi']) ?> / <?= $e($m['kuota']) ?> Siswa
                                </span>
                            </td>
                            <td class="sl-aksi">
                                <a class="a-detail" href="detail.php?id=<?= (int) $m['id'] ?>">Detail</a>
                                <a class="a-edit" href="edit.php?id=<?= (int) $m['id'] ?>">Edit</a>
                                <button type="button" class="a-hapus sl-hapus"
                                    data-nama="<?= $e($m['nama_instansi']) ?>"
                                    data-alamat="<?= $e($m['alamat']) ?>"
                                    data-pembimbing="<?= $e($m['pembimbing_lapangan']) ?>"
                                    data-telepon="<?= $e($m['telepon']) ?>"
                                    data-kuota="<?= $e($m['kuota']) ?>"
                                    data-terisi="<?= $e($m['terisi']) ?>">Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="slKosong" style="display:none;">
                        <td colspan="7" class="text-center py-4 text-muted">Tidak ada data instansi mitra yang sesuai.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sl-foot">
            <div class="sl-info" id="slInfo">Menampilkan 1-<?= count($mitraList) ?> dari <?= $e($totalMitra) ?> instansi</div>
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

<!-- Dialog konfirmasi hapus -->
<div class="modal fade sl-modal" id="slModalHapus" tabindex="-1" role="dialog" aria-labelledby="slModalHapusJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-2">
            <div class="modal-body">
                <div class="d-flex mb-3" style="gap:1rem;">
                    <div class="sl-trash"><i class="fas fa-trash-alt"></i></div>
                    <div>
                        <h5 class="font-weight-bold mb-1" id="slModalHapusJudul">Hapus Instansi Mitra?</h5>
                        <p class="small text-muted mb-0">
                            Apakah Anda yakin ingin menghapus data instansi
                            <strong id="slHNama">-</strong>?
                            Tindakan ini akan menghapus alokasi tempat magang siswa terkait dari sistem.
                        </p>
                    </div>
                </div>

                <div class="sl-ringkas mb-3">
                    <div><span>Nama Instansi:</span><span id="slHNama2">-</span></div>
                    <div><span>Penanggung Jawab:</span><span id="slHPembimbing">-</span></div>
                    <div><span>Kontak / Telp:</span><span id="slHTelepon">-</span></div>
                    <div><span>Status Kuota:</span><span id="slHKuota" class="text-primary">-</span></div>
                </div>

                <div class="sl-warn d-flex mb-3" style="gap:.5rem;">
                    <i class="fas fa-exclamation-triangle mt-1"></i>
                    <span>Pastikan siswa yang magang di instansi ini telah dipindahkan ke instansi lain sebelum menghapus.</span>
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
    window.addEventListener('load', function() {
        var $ = window.jQuery;

        // Pencarian + filter
        var total = <?= (int) $totalMitra ?>;

        function terapkan() {
            var q = $('#slCari').val().toLowerCase().trim();
            var s = $('#slSektor').val();
            var tampil = 0;
            $('#slTabel tbody tr[data-sektor]').each(function() {
                var $r = $(this);
                var cocok = (!q || $r.text().toLowerCase().indexOf(q) > -1) &&
                    (!s || $r.data('sektor') === s);
                $r.toggle(cocok);
                if (cocok) {
                    tampil++;
                }
            });
            $('#slKosong').toggle(tampil === 0);
            $('#slInfo').text(tampil === $('#slTabel tbody tr[data-sektor]').length ?
                'Menampilkan 1-' + tampil + ' dari ' + total + ' instansi' :
                'Menampilkan ' + tampil + ' instansi');
        }
        $('#slCari').on('input', terapkan);
        $('#slSektor').on('change', terapkan);

        // Dialog hapus
        $(document).on('click', '.sl-hapus', function() {
            var d = $(this).data();
            $('#slHNama, #slHNama2').text(d.nama);
            $('#slHPembimbing').text(d.pembimbing);
            $('#slHTelepon').text(d.telepon);
            $('#slHKuota').text(d.terisi + ' / ' + d.kuota + ' Siswa');
            $('#slModalHapus').modal('show');
        });
        $('#slKonfirmasiHapus').on('click', function() {
            $('#slModalHapus').modal('hide');
        });

        // Link pagination dummy
        $('.sl-pager a').on('click', function(ev) {
            ev.preventDefault();
        });
    });
</script>
<?php
// 4. Simpan hasil tangkapan ke $content, lalu panggil layout PALING AKHIR
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
