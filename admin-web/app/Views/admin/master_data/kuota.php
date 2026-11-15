<?php
$quotas = is_array($quotas ?? null) ? $quotas : [];
$instances = is_array($instances ?? null) ? $instances : [];
$periods = is_array($periods ?? null) ? $periods : [];
$quotaInput = is_array($quotaInput ?? null) ? $quotaInput : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];
$pageTitle = 'Kuota Instansi per Periode';
$pageSubtitle = 'NULL berarti tidak ada batas kuota. Kuota aktif tidak dapat diturunkan di bawah jumlah penempatan yang sudah memakai kapasitas.';
$activePage = 'kuota-instansi';
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$currentPage = max(1, (int) ($pagination['current_page'] ?? 1));
$lastPage = max(1, (int) ($pagination['last_page'] ?? 1));
ob_start();
?>
<?php if (is_string($adminPageError ?? null)): ?><div class="alert alert-danger"><?= $e($adminPageError) ?></div><?php endif; ?>
<?php if (is_string($adminPageSuccess ?? null)): ?><div class="alert alert-success"><?= $e($adminPageSuccess) ?></div><?php endif; ?>
<div class="card shadow-sm mb-3"><div class="card-header">Tambah atau perbarui konfigurasi</div><div class="card-body">
    <form method="post" action="?page=kuota-instansi"><input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="action" value="master-quota-save">
        <div class="form-row align-items-end">
            <div class="form-group col-md-4"><label>Instansi</label><select class="form-control" name="instansi_id" required><option value="">Pilih Instansi</option><?php foreach ($instances as $instance): ?><option value="<?= (int) ($instance['id'] ?? 0) ?>" <?= (string) ($quotaInput['instansi_id'] ?? $_GET['instansi_id'] ?? '') === (string) ($instance['id'] ?? '') ? 'selected' : '' ?>><?= $e($instance['nama'] ?? '') ?><?= ($instance['status'] ?? '') !== 'aktif' ? ' (nonaktif)' : '' ?></option><?php endforeach; ?></select></div>
            <div class="form-group col-md-4"><label>Periode PKL</label><select class="form-control" name="periode_id" required><option value="">Pilih Periode</option><?php foreach ($periods as $period): ?><option value="<?= (int) ($period['id'] ?? 0) ?>" <?= (string) ($quotaInput['periode_id'] ?? $_GET['periode_id'] ?? '') === (string) ($period['id'] ?? '') ? 'selected' : '' ?>><?= $e($period['nama'] ?? '') ?> — <?= $e($period['status'] ?? '') ?></option><?php endforeach; ?></select></div>
            <div class="form-group col-md-2"><label>Kuota Siswa</label><input class="form-control" type="number" min="0" max="4294967295" name="kuota_siswa" placeholder="Kosong = tanpa batas" value="<?= $e($quotaInput['kuota_siswa'] ?? '') ?>"></div>
            <div class="form-group col-md-2"><button class="btn btn-primary btn-block">Simpan</button></div>
        </div>
    </form>
</div></div>
<form class="form-inline mb-3" method="get"><input type="hidden" name="page" value="kuota-instansi"><label class="mr-2">Filter Instansi ID</label><input class="form-control form-control-sm mr-2" type="number" min="1" name="instansi_id" value="<?= $e($_GET['instansi_id'] ?? '') ?>"><label class="mr-2">Periode ID</label><input class="form-control form-control-sm mr-2" type="number" min="1" name="periode_id" value="<?= $e($_GET['periode_id'] ?? '') ?>"><button class="btn btn-sm btn-outline-primary">Filter</button></form>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Instansi</th><th>Periode</th><th>Kuota Siswa</th><th>Diubah</th><th></th></tr></thead><tbody>
<?php foreach ($quotas as $quota): ?><tr><td><?= $e($quota['instansi']['nama'] ?? 'Instansi') ?></td><td><?= $e($quota['periode']['nama'] ?? 'Periode') ?></td><td colspan="3"><form method="post" action="?page=kuota-instansi" class="form-inline">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="action" value="master-quota-save"><input type="hidden" name="instansi_id" value="<?= (int) ($quota['instansi_id'] ?? 0) ?>"><input type="hidden" name="periode_id" value="<?= (int) ($quota['periode_id'] ?? 0) ?>">
    <input class="form-control form-control-sm mr-2" type="number" min="0" max="4294967295" name="kuota_siswa" placeholder="Tanpa batas" value="<?= $e($quota['kuota_siswa'] ?? '') ?>"><span class="small text-muted mr-2"><?= $e($quota['updated_at'] ?? '') ?></span><button class="btn btn-sm btn-outline-primary">Simpan</button>
</form></td></tr><?php endforeach; ?>
<?php if ($quotas === []): ?><tr><td colspan="5" class="text-center text-muted">Belum ada konfigurasi kuota; pasangan tanpa konfigurasi juga diperlakukan tanpa batas.</td></tr><?php endif; ?>
</tbody></table></div><div class="card-body d-flex justify-content-between"><span><?= (int) ($pagination['total'] ?? 0) ?> konfigurasi</span><div><?php if ($currentPage > 1): ?><a class="btn btn-sm btn-outline-secondary" href="?<?= $e(http_build_query(array_merge($_GET, ['page' => 'kuota-instansi', 'list_page' => $currentPage - 1]))) ?>">Sebelumnya</a><?php endif; ?> <?php if ($currentPage < $lastPage): ?><a class="btn btn-sm btn-outline-secondary" href="?<?= $e(http_build_query(array_merge($_GET, ['page' => 'kuota-instansi', 'list_page' => $currentPage + 1]))) ?>">Berikutnya</a><?php endif; ?></div></div></div>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';
