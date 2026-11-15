<?php
$pageTitle = 'Manajemen PKL';
$pageSubtitle = 'Kelola penempatan Siswa, pembimbing, dan riwayat penempatan.';
$activePage = 'manajemen-pkl';
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$filterValue = static fn (string $key): string => isset($_GET[$key]) && is_string($_GET[$key]) ? $_GET[$key] : '';
$pagination = is_array($placementPagination ?? null) ? $placementPagination : [];
$currentPage = max(1, (int) ($pagination['current_page'] ?? 1));
$lastPage = max(1, (int) ($pagination['last_page'] ?? 1));
$perPage = max(1, (int) ($pagination['per_page'] ?? 15));
$total = max(0, (int) ($pagination['total'] ?? 0));
$offset = ($currentPage - 1) * $perPage;
$references = is_array($placementReferences ?? null) ? $placementReferences : [];
$queryForPage = static function (int $page): string {
    $params = ['page' => 'manajemen-pkl'];
    foreach (['search', 'periode_id', 'status', 'instansi_id', 'guru_id'] as $key) {
        if (isset($_GET[$key]) && is_string($_GET[$key]) && $_GET[$key] !== '') {
            $params[$key] = $_GET[$key];
        }
    }
    $params['list_page'] = $page;

    return '?' . http_build_query($params);
};
ob_start();
?>
<?php if (is_string($adminPageError ?? null)): ?>
    <div class="alert alert-danger"><?= $e($adminPageError) ?></div>
<?php endif; ?>
<?php if (is_string($adminPageSuccess ?? null)): ?>
    <div class="alert alert-success"><?= $e($adminPageSuccess) ?></div>
<?php endif; ?>
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary" href="?page=create-penempatan-pkl"><i class="fas fa-plus mr-1"></i>Tambah Penempatan</a>
</div>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="">
            <input type="hidden" name="page" value="manajemen-pkl">
            <div class="form-row align-items-end">
                <div class="form-group col-lg-3">
                    <label for="placement-search">Nama Siswa / NISN</label>
                    <input class="form-control" id="placement-search" name="search" value="<?= $e($filterValue('search')) ?>" maxlength="100">
                </div>
                <div class="form-group col-lg-2">
                    <label for="period-filter">ID Periode</label>
                    <input class="form-control" id="period-filter" name="periode_id" type="number" min="1" value="<?= $e($filterValue('periode_id')) ?>">
                </div>
                <div class="form-group col-lg-2">
                    <label for="status-filter">Status</label>
                    <select class="form-control" id="status-filter" name="status">
                        <option value="">Semua status</option>
                        <?php foreach (['belum_mulai', 'aktif', 'selesai', 'dibatalkan'] as $status): ?>
                            <option value="<?= $e($status) ?>" <?= $filterValue('status') === $status ? 'selected' : '' ?>><?= $e(str_replace('_', ' ', ucfirst($status))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-lg-2">
                    <label for="instance-filter">ID Instansi</label>
                    <input class="form-control" id="instance-filter" name="instansi_id" type="number" min="1" value="<?= $e($filterValue('instansi_id')) ?>">
                </div>
                <div class="form-group col-lg-2">
                    <label for="teacher-filter">ID Profil Guru</label>
                    <input class="form-control" id="teacher-filter" name="guru_id" type="number" min="1" value="<?= $e($filterValue('guru_id')) ?>">
                </div>
                <div class="form-group col-lg-1">
                    <button class="btn btn-outline-primary btn-block" type="submit">Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Daftar Penempatan</strong>
        <span class="small text-muted"><?= $total ?> data</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>No.</th><th>Siswa</th><th>NISN</th><th>Periode</th><th>Instansi</th>
                    <th>Guru Pembimbing</th><th>PIC Instansi</th><th>Tanggal</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (($placements ?? []) === []): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">
                        <?= is_string($adminPageError ?? null) ? 'Data tidak tersedia karena permintaan API gagal.' : 'Tidak ada penempatan yang cocok dengan filter.' ?>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($placements as $index => $placement): ?>
                        <?php
                        $status = (string) ($placement['status'] ?? '');
                        $badge = $status === 'aktif' ? 'success' : ($status === 'belum_mulai' ? 'info' : ($status === 'dibatalkan' ? 'danger' : 'secondary'));
                        ?>
                        <tr>
                            <td><?= $offset + $index + 1 ?></td>
                            <td><?= $e($placement['siswa']['nama_lengkap'] ?? '-') ?></td>
                            <td><?= $e($placement['siswa']['nisn'] ?? '-') ?></td>
                            <td><?= $e($placement['periode']['nama'] ?? '-') ?></td>
                            <td><?= $e($placement['instansi']['nama'] ?? '-') ?></td>
                            <td><?= $e($placement['guru']['nama_lengkap'] ?? '-') ?></td>
                            <td><?= $e($placement['pic']['nama'] ?? '-') ?></td>
                            <td><?= $e($placement['tanggal_mulai'] ?? '-') ?> – <?= $e($placement['tanggal_selesai'] ?? '-') ?></td>
                            <td><span class="badge badge-<?= $badge ?>"><?= $e(str_replace('_', ' ', $status)) ?></span></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="?page=detail-penempatan-pkl&amp;id=<?= (int) ($placement['id'] ?? 0) ?>">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($lastPage > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <span class="small text-muted">Halaman <?= $currentPage ?> dari <?= $lastPage ?></span>
            <div>
                <?php if ($currentPage > 1): ?><a class="btn btn-sm btn-outline-secondary" href="<?= $e($queryForPage($currentPage - 1)) ?>">Sebelumnya</a><?php endif; ?>
                <?php if ($currentPage < $lastPage): ?><a class="btn btn-sm btn-outline-secondary" href="<?= $e($queryForPage($currentPage + 1)) ?>">Berikutnya</a><?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';
?>
