<?php
$years = is_array($years ?? null) ? $years : [];
$year = is_array($year ?? null) ? $year : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];
$mode = is_string($masterMode ?? null) ? $masterMode : 'list';
$pageTitle = in_array($mode, ['create', 'edit'], true) ? ($mode === 'create' ? 'Tambah Tahun Ajaran' : 'Edit Tahun Ajaran') : 'Tahun Ajaran';
$pageSubtitle = 'Kelola nama, rentang tanggal, dan penanda aktif Tahun Ajaran.';
$activePage = 'tahun-ajaran';
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$currentPage = max(1, (int) ($pagination['current_page'] ?? 1));
$lastPage = max(1, (int) ($pagination['last_page'] ?? 1));
ob_start();
?>
<?php if (is_string($adminPageError ?? null)): ?><div class="alert alert-danger"><?= $e($adminPageError) ?></div><?php endif; ?>
<?php if (is_string($adminPageSuccess ?? null)): ?><div class="alert alert-success"><?= $e($adminPageSuccess) ?></div><?php endif; ?>
<div class="d-flex justify-content-between mb-3"><form method="get" class="form-inline"><input type="hidden" name="page" value="tahun-ajaran"><input class="form-control mr-2" name="search" value="<?= $e($_GET['search'] ?? '') ?>" placeholder="Cari nama Tahun Ajaran"><button class="btn btn-outline-primary">Cari</button></form><a class="btn btn-primary" href="?page=create-tahun-ajaran">Tambah Tahun Ajaran</a></div>
<?php if (in_array($mode, ['create', 'edit'], true)): ?>
    <?php $isEdit = $mode === 'edit'; ?>
    <div class="card shadow-sm"><div class="card-body"><form method="post" action="?page=<?= $isEdit ? 'edit-tahun-ajaran' : 'create-tahun-ajaran' ?>">
        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="action" value="<?= $isEdit ? 'master-year-update' : 'master-year-create' ?>"><?php if ($isEdit): ?><input type="hidden" name="tahun_ajaran_id" value="<?= (int) ($year['id'] ?? 0) ?>"><?php endif; ?>
        <div class="form-group"><label>Nama</label><input class="form-control" name="nama" maxlength="20" required value="<?= $e($year['nama'] ?? '') ?>"></div>
        <div class="form-row"><div class="form-group col-md-6"><label>Tanggal Mulai</label><input class="form-control" type="date" name="tanggal_mulai" required value="<?= $e($year['tanggal_mulai'] ?? '') ?>"></div><div class="form-group col-md-6"><label>Tanggal Selesai</label><input class="form-control" type="date" name="tanggal_selesai" required value="<?= $e($year['tanggal_selesai'] ?? '') ?>"></div></div>
        <div class="form-group"><input type="hidden" name="aktif" value="0"><label><input type="checkbox" name="aktif" value="1" <?= !empty($year['aktif']) ? 'checked' : '' ?>> Tahun ajaran aktif</label></div>
        <button class="btn btn-primary">Simpan</button> <a class="btn btn-light" href="?page=tahun-ajaran">Batal</a>
    </form></div></div>
<?php else: ?>
    <div class="alert alert-info">Skema menyediakan penanda aktif pada tiap baris. Kode yang ada tidak menetapkan aturan hanya satu Tahun Ajaran aktif, sehingga perubahan ini tidak memaksa penonaktifan tahun lain.</div>
    <div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Nama</th><th>Mulai</th><th>Selesai</th><th>Aktif</th><th></th></tr></thead><tbody>
    <?php foreach ($years as $row): ?><tr><td><?= $e($row['nama'] ?? '') ?></td><td><?= $e($row['tanggal_mulai'] ?? '') ?></td><td><?= $e($row['tanggal_selesai'] ?? '') ?></td><td><?= !empty($row['aktif']) ? 'Ya' : 'Tidak' ?></td><td><a class="btn btn-sm btn-outline-secondary" href="?page=edit-tahun-ajaran&id=<?= (int) ($row['id'] ?? 0) ?>">Edit</a></td></tr><?php endforeach; ?>
    <?php if ($years === []): ?><tr><td colspan="5" class="text-muted text-center">Belum ada Tahun Ajaran.</td></tr><?php endif; ?>
    </tbody></table></div><div class="card-body d-flex justify-content-between"><span><?= (int) ($pagination['total'] ?? 0) ?> Tahun Ajaran</span><div><?php if ($currentPage > 1): ?><a class="btn btn-sm btn-outline-secondary" href="?page=tahun-ajaran&list_page=<?= $currentPage - 1 ?>">Sebelumnya</a><?php endif; ?> <?php if ($currentPage < $lastPage): ?><a class="btn btn-sm btn-outline-secondary" href="?page=tahun-ajaran&list_page=<?= $currentPage + 1 ?>">Berikutnya</a><?php endif; ?></div></div></div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';
