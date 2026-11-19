<?php
$masterMode = is_string($masterMode ?? null) ? $masterMode : 'list';
$instance = is_array($instance ?? null) ? $instance : [];
$instances = is_array($instances ?? null) ? $instances : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];
$statistics = is_array($instanceStatistics ?? null) ? $instanceStatistics : [];
$currentPage = max(1, (int) ($pagination['current_page'] ?? 1));
$lastPage = max(1, (int) ($pagination['last_page'] ?? 1));
$totalInstances = max(0, (int) ($statistics['total_instansi'] ?? $pagination['total'] ?? 0));
$totalQuota = max(0, (int) ($statistics['total_kuota'] ?? 0));
$occupiedQuota = max(0, (int) ($statistics['terisi'] ?? 0));
$quotaPercentage = $totalQuota > 0 ? min(100, (int) round($occupiedQuota / $totalQuota * 100)) : 0;
$searchTerm = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
$pageTitle = match ($masterMode) {
    'create' => 'Tambah Instansi Mitra',
    'edit' => 'Edit Instansi Mitra',
    'detail' => 'Detail Instansi Mitra',
    default => 'Instansi Mitra',
};
$pageSubtitle = 'Kelola data Instansi yang digunakan sebagai referensi penempatan PKL.';
$activePage = 'instansi-mitra';
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$value = static fn (string $key, mixed $fallback = ''): mixed => $instance[$key] ?? $fallback;
$postPage = match ($masterMode) {
    'create' => 'create-mitra',
    'edit' => 'edit-mitra',
    'detail' => 'detail-mitra',
    default => 'data-mitra',
};
ob_start();
?>
<style>
    .instance-list { --instance-ink: #172b4d; --instance-muted: #68788e; --instance-blue: #2563eb; }
    .instance-list .instance-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-bottom: 1rem; }
    .instance-list .instance-btn { display: inline-flex; align-items: center; gap: .45rem; border: 0; border-radius: 10px; padding: .65rem 1rem; font-size: .85rem; font-weight: 600; text-decoration: none; }
    .instance-list .instance-btn-primary { background: var(--instance-blue); color: #fff; }
    .instance-list .instance-btn-primary:hover { background: #1d4ed8; color: #fff; }
    .instance-list .instance-btn-light { background: #e2ebf8; color: var(--instance-ink); }
    .instance-list .instance-btn-light:hover { background: #d3e0f1; color: var(--instance-ink); }
    .instance-list .instance-stats { margin-right: -10px; margin-left: -10px; }
    .instance-list .instance-stats > [class*="col-"] { padding-right: 10px; padding-left: 10px; }
    .instance-list .instance-stat { display: flex; align-items: center; gap: 1rem; min-height: 100px; height: 100%; padding: 1rem 1.25rem; border-radius: 16px; background: #fff; box-shadow: 0 2px 6px rgba(16,24,40,.06); }
    .instance-list .instance-stat-icon { display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; flex: 0 0 56px; border-radius: 14px; background: #eaf1fb; color: #0b5f8a; font-size: 1.15rem; }
    .instance-list .instance-stat-label { color: #53647b; font-size: .78rem; }
    .instance-list .instance-stat-value { color: var(--instance-ink); font-size: 1.5rem; font-weight: 800; line-height: 1.15; }
    .instance-list .instance-stat-value.is-blue { color: #0c6aa6; }
    .instance-list .instance-stat-note { color: var(--instance-muted); font-size: .7rem; }
    .instance-list .instance-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .75rem; padding: .65rem; border-radius: 15px; background: #fff; box-shadow: 0 2px 6px rgba(16,24,40,.06); }
    .instance-list .instance-search { position: relative; flex: 1 1 300px; max-width: 440px; }
    .instance-list .instance-search i { position: absolute; top: 50%; left: 15px; color: var(--instance-muted); transform: translateY(-50%); }
    .instance-list .instance-search input, .instance-list .instance-toolbar select { width: 100%; height: 46px; border: 0; border-radius: 11px; background: #eaf1fb; color: #3b4a5e; font-size: .85rem; }
    .instance-list .instance-search input { padding: 0 .8rem 0 42px; }
    .instance-list .instance-toolbar select { min-width: 190px; padding: 0 1rem; }
    .instance-list .instance-filter-actions { display: flex; gap: .5rem; }
    .instance-list .instance-filter-actions .btn { height: 46px; border-radius: 11px; }
    .instance-list .instance-card { overflow: hidden; border: 0; border-radius: 17px; background: #fff; box-shadow: 0 2px 6px rgba(16,24,40,.06); }
    .instance-list .instance-table { min-width: 1020px; margin: 0; border-collapse: separate; border-spacing: 0; }
    .instance-list .instance-table th { padding: 1rem .85rem; border: 0; background: #f1f5fb; color: #3b4a5e; font-size: .72rem; font-weight: 700; letter-spacing: .02em; text-transform: uppercase; }
    .instance-list .instance-table td { padding: 1rem .85rem; border: 0; border-bottom: 1px solid #edf1f6; color: #536176; font-size: .82rem; vertical-align: middle; }
    .instance-list .instance-table tr:last-child td { border-bottom: 0; }
    .instance-list .instance-name { color: var(--instance-ink); font-weight: 700; }
    .instance-list .instance-subtext { display: block; margin-top: .2rem; color: #8190a4; font-size: .72rem; }
    .instance-list .instance-type { display: inline-block; margin-top: .35rem; padding: .18rem .55rem; border-radius: 999px; background: #e8f1fb; color: #2a5aa0; font-size: .68rem; }
    .instance-list .instance-status { display: inline-block; padding: .3rem .7rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
    .instance-list .instance-status-active { background: #dbe8fb; color: #2a5aa0; }
    .instance-list .instance-status-inactive { background: #fee2e2; color: #b91c1c; }
    .instance-list .instance-actions-cell { white-space: nowrap; }
    .instance-list .instance-actions-cell a, .instance-list .instance-actions-cell button { margin-right: .4rem; border: 0; padding: 0; background: none; font-size: .78rem; text-decoration: none; cursor: pointer; }
    .instance-list .instance-link-detail { color: #1a2438; }
    .instance-list .instance-link-edit { color: #2563eb; }
    .instance-list .instance-link-delete { color: #e0264a; }
    .instance-list .instance-link-activate { color: #15803d; }
    .instance-list .instance-footer { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: 1rem 1.25rem; }
    .instance-list .instance-pagination { display: flex; align-items: center; gap: .3rem; margin: 0; padding: 0; list-style: none; }
    .instance-list .instance-pagination a { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 .55rem; border-radius: 8px; color: #42536b; font-size: .8rem; text-decoration: none; }
    .instance-list .instance-pagination .active a { background: #0b5f8a; color: #fff; }
    .instance-list .instance-pagination .disabled a { opacity: .5; pointer-events: none; }
    @media (max-width: 767.98px) {
        .instance-list .instance-actions { justify-content: flex-start; }
        .instance-list .instance-toolbar, .instance-list .instance-filter-actions { width: 100%; }
        .instance-list .instance-search { max-width: none; flex-basis: 100%; }
        .instance-list .instance-toolbar select { min-width: 0; }
        .instance-list .instance-footer { align-items: flex-start; flex-direction: column; }
    }
</style>
<?php if (is_string($adminPageError ?? null)): ?><div class="alert alert-danger"><?= $e($adminPageError) ?></div><?php endif; ?>
<?php if (is_string($adminPageSuccess ?? null)): ?><div class="alert alert-success"><?= $e($adminPageSuccess) ?></div><?php endif; ?>
<?php if ($masterMode !== 'list'): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="?page=data-mitra">Daftar Instansi</a>
</div>
<?php endif; ?>
<?php if ($masterMode === 'list'): ?>
    <div class="instance-list">
        <div class="instance-actions">
            <a class="instance-btn instance-btn-primary" href="?page=create-mitra"><i class="fas fa-plus"></i>Tambah Instansi</a>
        </div>
        <div class="row instance-stats mb-3">
            <div class="col-md-4 mb-3 mb-md-0"><div class="instance-stat"><span class="instance-stat-icon"><i class="fas fa-building"></i></span><div><div class="instance-stat-label">Total Instansi</div><div class="instance-stat-value"><?= $e($totalInstances) ?></div></div></div></div>
            <div class="col-md-4 mb-3 mb-md-0"><div class="instance-stat"><span class="instance-stat-icon"><i class="fas fa-users"></i></span><div><div class="instance-stat-label">Total Kuota</div><div class="instance-stat-value is-blue"><?= $e($totalQuota) ?> <small class="instance-stat-note">Siswa</small></div></div></div></div>
            <div class="col-md-4"><div class="instance-stat"><span class="instance-stat-icon"><i class="fas fa-user-check"></i></span><div><div class="instance-stat-label">Kuota Terisi</div><div class="instance-stat-value is-blue"><?= $e($occupiedQuota) ?> <small class="instance-stat-note"><?= $e($quotaPercentage) ?>%</small></div></div></div></div>
        </div>
        <form class="instance-toolbar mb-3" method="get">
            <input type="hidden" name="page" value="data-mitra">
            <div class="instance-search"><i class="fas fa-search"></i><input id="instance-search" type="search" name="search" value="<?= $e($searchTerm) ?>" maxlength="100" placeholder="Cari nama instansi atau kota..." aria-label="Cari instansi"></div>
            <div class="instance-filter-actions">
                <select id="instance-status" name="status" aria-label="Filter status">
                    <option value="">Semua Status</option>
                    <?php foreach (['aktif', 'nonaktif'] as $status): ?>
                        <option value="<?= $e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $e(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Cari</button>
                <a class="btn btn-light border" href="?page=data-mitra">Reset</a>
            </div>
        </form>
        <div class="instance-card mb-4">
            <div class="table-responsive">
                <table class="table table-hover instance-table">
                    <thead><tr><th style="width:4%;">No</th><th style="width:18%;">Nama Instansi / Perusahaan</th><th style="width:12%;">Bidang Industri</th><th style="width:17%;">Alamat &amp; Kota</th><th style="width:15%;">Penanggung Jawab</th><th style="width:12%;">Kuota Terisi</th><th style="width:8%;">Status</th><th style="width:14%;">Aksi</th></tr></thead>
                    <tbody>
                    <?php if ($instances === []): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada Instansi yang sesuai.</td></tr>
                    <?php else: ?>
                        <?php foreach ($instances as $index => $row): ?>
                            <?php
                            $id = (int) ($row['id'] ?? 0);
                            $status = (string) ($row['status'] ?? '');
                            $isActive = $status === 'aktif';
                            $filled = max(0, (int) ($row['terisi'] ?? 0));
                            $quota = $row['kuota_siswa'] ?? null;
                            ?>
                            <tr>
                                <td><?= (($currentPage - 1) * max(1, (int) ($pagination['per_page'] ?? 15))) + $index + 1 ?></td>
                                <td><span class="instance-name"><?= $e($row['nama'] ?? '-') ?></span><span class="instance-subtext">ID: <?= $id ?></span></td>
                                <td><?= $e($row['jenis'] ?? '-') ?></td>
                                <td><?= $e($row['alamat'] ?? '-') ?><?php if (!empty($row['kota'])): ?><span class="instance-subtext"><?= $e($row['kota']) ?><?= !empty($row['provinsi']) ? ', ' . $e($row['provinsi']) : '' ?></span><?php endif; ?></td>
                                <td><?= $e($row['nama_penanggung_jawab'] ?? '-') ?><span class="instance-subtext"><?= $e($row['telepon'] ?? '-') ?></span></td>
                                <td><strong><?= $e($filled) ?>/<?= $quota === null ? '&infin;' : $e((int) $quota) ?></strong><span class="instance-subtext">Siswa</span></td>
                                <td><span class="instance-status <?= $isActive ? 'instance-status-active' : 'instance-status-inactive' ?>"><?= $e($isActive ? 'Aktif' : 'Nonaktif') ?></span></td>
                                <td class="instance-actions-cell">
                                    <a class="instance-link-detail" href="?page=detail-mitra&amp;id=<?= $id ?>">Detail</a>
                                    <a class="instance-link-edit" href="?page=edit-mitra&amp;id=<?= $id ?>">Edit</a>
                                    <?php if ($isActive): ?>
                                        <form class="d-inline" method="post" action="?page=data-mitra" onsubmit="return confirm('Nonaktifkan Instansi ini? Data dan riwayat penempatannya tetap tersimpan.')">
                                            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                            <input type="hidden" name="action" value="master-instance-deactivate">
                                            <input type="hidden" name="instansi_id" value="<?= $id ?>">
                                            <button class="instance-link-delete" type="submit">Hapus</button>
                                        </form>
                                    <?php else: ?>
                                        <form class="d-inline" method="post" action="?page=detail-mitra" onsubmit="return confirm('Aktifkan kembali Instansi ini?')">
                                            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                            <input type="hidden" name="action" value="master-instance-status">
                                            <input type="hidden" name="instansi_id" value="<?= $id ?>">
                                            <input type="hidden" name="status" value="aktif">
                                            <button class="instance-link-activate" type="submit">Aktifkan</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="instance-footer">
                <span class="text-muted small">Menampilkan <?= $totalInstances === 0 ? 0 : (($currentPage - 1) * max(1, (int) ($pagination['per_page'] ?? 15))) + 1 ?>-<?= (($currentPage - 1) * max(1, (int) ($pagination['per_page'] ?? 15))) + count($instances) ?> dari <?= $e($pagination['total'] ?? 0) ?> Instansi</span>
                <ul class="instance-pagination">
                    <?php
                    $pageLink = static function (int $targetPage) use ($searchTerm, $statusFilter): string {
                        $params = ['page' => 'data-mitra', 'list_page' => $targetPage];
                        if ($searchTerm !== '') {
                            $params['search'] = $searchTerm;
                        }
                        if ($statusFilter !== '') {
                            $params['status'] = $statusFilter;
                        }

                        return '?' . http_build_query($params);
                    };
                    ?>
                    <li class="<?= $currentPage <= 1 ? 'disabled' : '' ?>"><a href="<?= $e($pageLink(max(1, $currentPage - 1))) ?>">Sebelumnya</a></li>
                    <?php for ($pageNumber = max(1, $currentPage - 2); $pageNumber <= min($lastPage, $currentPage + 2); $pageNumber++): ?>
                        <li class="<?= $pageNumber === $currentPage ? 'active' : '' ?>"><a href="<?= $e($pageLink($pageNumber)) ?>"><?= $pageNumber ?></a></li>
                    <?php endfor; ?>
                    <li class="<?= $currentPage >= $lastPage ? 'disabled' : '' ?>"><a href="<?= $e($pageLink(min($lastPage, $currentPage + 1))) ?>">Selanjutnya</a></li>
                </ul>
            </div>
        </div>
    </div>
<?php elseif (in_array($masterMode, ['create', 'edit'], true)): ?>
    <?php $isEdit = $masterMode === 'edit'; ?>
    <div class="card shadow-sm"><div class="card-body">
        <form method="post" action="?page=<?= $postPage ?>">
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $isEdit ? 'master-instance-update' : 'master-instance-create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="instansi_id" value="<?= (int) ($instance['id'] ?? 0) ?>"><?php endif; ?>
            <div class="form-row">
                <?php foreach (['nama' => ['Nama Instansi', true], 'jenis' => ['Jenis', false], 'alamat' => ['Alamat', true], 'kota' => ['Kota', false], 'provinsi' => ['Provinsi', false], 'telepon' => ['Telepon', false], 'email' => ['Email', false], 'nama_penanggung_jawab' => ['Nama Penanggung Jawab', false]] as $key => [$label, $required]): ?>
                    <div class="form-group <?= $key === 'alamat' ? 'col-12' : 'col-md-6' ?>"><label for="instance-<?= $e($key) ?>"><?= $e($label) ?></label>
                        <?php if ($key === 'alamat'): ?><textarea class="form-control" id="instance-<?= $e($key) ?>" name="<?= $e($key) ?>" rows="3" <?= $required ? 'required' : '' ?>><?= $e($value($key)) ?></textarea>
                        <?php else: ?><input class="form-control" id="instance-<?= $e($key) ?>" name="<?= $e($key) ?>" value="<?= $e($value($key)) ?>" <?= $required ? 'required' : '' ?> maxlength="<?= $key === 'nama' ? 160 : ($key === 'jenis' ? 80 : (in_array($key, ['kota', 'provinsi'], true) ? 100 : ($key === 'telepon' ? 25 : ($key === 'email' ? 150 : 120)))) ?>"><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="form-group col-md-4">
                    <label for="instance-latitude">Latitude</label>
                    <input class="form-control" id="instance-latitude" name="latitude" type="number" step="0.0000001" min="-90" max="90" value="<?= $e($value('latitude')) ?>" placeholder="Contoh: -7.7543000">
                </div>
                <div class="form-group col-md-4">
                    <label for="instance-longitude">Longitude</label>
                    <input class="form-control" id="instance-longitude" name="longitude" type="number" step="0.0000001" min="-180" max="180" value="<?= $e($value('longitude')) ?>" placeholder="Contoh: 111.9025000">
                </div>
                <div class="form-group col-md-4">
                    <label for="instance-radius">Radius Geofence (meter)</label>
                    <input class="form-control" id="instance-radius" name="radius_geofence_meter" type="number" min="1" max="4294967295" step="1" value="<?= $e($value('radius_geofence_meter')) ?>" placeholder="Contoh: 100">
                </div>
                <?php if ($isEdit): ?><div class="form-group col-md-6"><label for="instance-status-edit">Status</label><select id="instance-status-edit" class="form-control" name="status"><option value="aktif" <?= $value('status') === 'aktif' ? 'selected' : '' ?>>Aktif</option><option value="nonaktif" <?= $value('status') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option></select></div><?php endif; ?>
            </div>
            <button class="btn btn-primary">Simpan</button> <a class="btn btn-light" href="?page=data-mitra">Batal</a>
        </form>
    </div></div>
<?php elseif ($masterMode === 'detail'): ?>
    <?php if ($instance === []): ?><div class="alert alert-warning">Detail Instansi tidak tersedia.</div><?php else: ?>
        <div class="card shadow-sm mb-3"><div class="card-body">
            <div class="d-flex justify-content-between"><h2 class="h5"><?= $e($instance['nama'] ?? '') ?></h2><a class="btn btn-outline-secondary btn-sm" href="?page=edit-mitra&id=<?= (int) $instance['id'] ?>">Edit</a></div>
            <dl class="row mb-3"><?php foreach (['jenis' => 'Jenis', 'alamat' => 'Alamat', 'kota' => 'Kota', 'provinsi' => 'Provinsi', 'telepon' => 'Telepon', 'email' => 'Email', 'nama_penanggung_jawab' => 'Penanggung Jawab', 'latitude' => 'Latitude', 'longitude' => 'Longitude', 'radius_geofence_meter' => 'Radius Geofence (meter)', 'status' => 'Status'] as $key => $label): ?><dt class="col-sm-3"><?= $e($label) ?></dt><dd class="col-sm-9"><?= nl2br($e($instance[$key] ?? '-')) ?></dd><?php endforeach; ?></dl>
            <form method="post" action="?page=detail-mitra" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="action" value="master-instance-status"><input type="hidden" name="instansi_id" value="<?= (int) $instance['id'] ?>">
                <input type="hidden" name="status" value="<?= ($instance['status'] ?? '') === 'aktif' ? 'nonaktif' : 'aktif' ?>">
                <button class="btn btn-sm <?= ($instance['status'] ?? '') === 'aktif' ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= ($instance['status'] ?? '') === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
            </form>
        </div></div>
    <?php endif; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';
