<?php
$periods = is_array($periods ?? null) ? $periods : [];
$period = is_array($period ?? null) ? $period : [];
$years = is_array($years ?? null) ? $years : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];
$mode = is_string($masterMode ?? null) ? $masterMode : 'list';
$pageTitle = in_array($mode, ['create', 'edit'], true) ? ($mode === 'create' ? 'Tambah Periode PKL' : 'Edit Periode PKL') : 'Periode PKL';
$pageSubtitle = 'Kelola Periode PKL dan hubungan tanggalnya dengan Tahun Ajaran.';
$activePage = 'periode-pkl';
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$currentPage = max(1, (int) ($pagination['current_page'] ?? 1));
$lastPage = max(1, (int) ($pagination['last_page'] ?? 1));
ob_start();
?>
<?php if (is_string($adminPageError ?? null)): ?><div class="alert alert-danger"><?= $e($adminPageError) ?></div><?php endif; ?>
<?php if (is_string($adminPageSuccess ?? null)): ?><div class="alert alert-success"><?= $e($adminPageSuccess) ?></div><?php endif; ?>
<div class="d-flex justify-content-between mb-3"><form method="get" class="form-inline"><input type="hidden" name="page" value="periode-pkl"><input class="form-control mr-2" name="search" value="<?= $e($_GET['search'] ?? '') ?>" placeholder="Cari nama Periode"><button class="btn btn-outline-primary">Cari</button></form><a class="btn btn-primary" href="?page=create-periode-pkl">Tambah Periode</a></div>
<?php if (in_array($mode, ['create', 'edit'], true)): ?>
    <?php $isEdit = $mode === 'edit'; ?>
    <div class="card shadow-sm"><div class="card-body"><form method="post" action="?page=<?= $isEdit ? 'edit-periode-pkl' : 'create-periode-pkl' ?>">
        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="action" value="<?= $isEdit ? 'master-period-update' : 'master-period-create' ?>"><?php if ($isEdit): ?><input type="hidden" name="periode_id" value="<?= (int) ($period['id'] ?? 0) ?>"><?php endif; ?>
        <div class="form-group"><label>Tahun Ajaran (opsional)</label><select class="form-control" name="tahun_ajaran_id"><option value="">Tanpa Tahun Ajaran</option><?php foreach ($years as $yearRow): ?><option value="<?= (int) ($yearRow['id'] ?? 0) ?>" <?= (string) ($period['tahun_ajaran_id'] ?? '') === (string) ($yearRow['id'] ?? '') ? 'selected' : '' ?>><?= $e($yearRow['nama'] ?? '') ?> (<?= $e($yearRow['tanggal_mulai'] ?? '') ?> - <?= $e($yearRow['tanggal_selesai'] ?? '') ?>)</option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Nama Periode</label><input class="form-control" name="nama" maxlength="100" required value="<?= $e($period['nama'] ?? '') ?>"></div>
        <div class="form-row"><div class="form-group col-md-6"><label>Tanggal Mulai</label><input class="form-control" type="date" name="tanggal_mulai" required value="<?= $e($period['tanggal_mulai'] ?? '') ?>"></div><div class="form-group col-md-6"><label>Tanggal Selesai</label><input class="form-control" type="date" name="tanggal_selesai" required value="<?= $e($period['tanggal_selesai'] ?? '') ?>"></div></div>
        <div class="form-group"><label>Status</label><select class="form-control" name="status" required><?php foreach (['persiapan', 'berjalan', 'selesai'] as $status): ?><option value="<?= $e($status) ?>" <?= ($period['status'] ?? 'persiapan') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></div>
        <button class="btn btn-primary">Simpan</button> <a class="btn btn-light" href="?page=periode-pkl">Batal</a>
    </form></div></div>
<?php else: ?>
    <div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Periode</th><th>Tahun Ajaran</th><th>Rentang</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($periods as $row): ?><tr><td><?= $e($row['nama'] ?? '') ?></td><td><?= $e($row['tahun_ajaran']['nama'] ?? '-') ?></td><td><?= $e($row['tanggal_mulai'] ?? '') ?> – <?= $e($row['tanggal_selesai'] ?? '') ?></td><td><?= $e($row['status'] ?? '') ?></td><td><a class="btn btn-sm btn-outline-secondary" href="?page=edit-periode-pkl&id=<?= (int) ($row['id'] ?? 0) ?>">Edit</a></td></tr><?php endforeach; ?>
    <?php if ($periods === []): ?><tr><td colspan="5" class="text-muted text-center">Belum ada Periode PKL.</td></tr><?php endif; ?>
    </tbody></table></div><div class="card-body d-flex justify-content-between"><span><?= (int) ($pagination['total'] ?? 0) ?> Periode</span><div><?php if ($currentPage > 1): ?><a class="btn btn-sm btn-outline-secondary" href="?page=periode-pkl&list_page=<?= $currentPage - 1 ?>">Sebelumnya</a><?php endif; ?> <?php if ($currentPage < $lastPage): ?><a class="btn btn-sm btn-outline-secondary" href="?page=periode-pkl&list_page=<?= $currentPage + 1 ?>">Berikutnya</a><?php endif; ?></div></div></div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';
