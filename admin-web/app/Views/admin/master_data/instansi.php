<?php
$masterMode = is_string($masterMode ?? null) ? $masterMode : 'list';
$instance = is_array($instance ?? null) ? $instance : [];
$instances = is_array($instances ?? null) ? $instances : [];
$pics = is_array($pics ?? null) ? $pics : [];
$picForEdit = is_array($picForEdit ?? null) ? $picForEdit : [];
$picInput = is_array($picInput ?? null) ? $picInput : [];
if ($picForEdit !== [] && $picInput !== []) {
    $picForEdit = array_merge($picForEdit, $picInput);
}
$pagination = is_array($pagination ?? null) ? $pagination : [];
$currentPage = max(1, (int) ($pagination['current_page'] ?? 1));
$lastPage = max(1, (int) ($pagination['last_page'] ?? 1));
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
<?php if (is_string($adminPageError ?? null)): ?><div class="alert alert-danger"><?= $e($adminPageError) ?></div><?php endif; ?>
<?php if (is_string($adminPageSuccess ?? null)): ?><div class="alert alert-success"><?= $e($adminPageSuccess) ?></div><?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><a class="btn btn-outline-secondary btn-sm" href="?page=data-mitra">Daftar Instansi</a></div>
    <?php if ($masterMode === 'list'): ?><a class="btn btn-primary" href="?page=create-mitra">Tambah Instansi</a><?php endif; ?>
</div>
<?php if ($masterMode === 'list'): ?>
    <form class="card card-body mb-3" method="get">
        <input type="hidden" name="page" value="data-mitra">
        <div class="form-row align-items-end">
            <div class="form-group col-md-6 mb-0"><label for="instance-search">Cari nama, alamat, atau penanggung jawab</label><input id="instance-search" class="form-control" name="search" value="<?= $e($_GET['search'] ?? '') ?>" maxlength="100"></div>
            <div class="form-group col-md-3 mb-0"><label for="instance-status">Status</label><select id="instance-status" class="form-control" name="status"><option value="">Semua</option><?php foreach (['aktif', 'nonaktif'] as $status): ?><option value="<?= $status ?>" <?= ($_GET['status'] ?? '') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></div>
            <div class="form-group col-md-3 mb-0"><button class="btn btn-outline-primary">Cari</button></div>
        </div>
    </form>
    <div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Nama</th><th>Jenis</th><th>Kontak</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($instances as $row): ?>
        <tr><td><?= $e($row['nama'] ?? '') ?></td><td><?= $e($row['jenis'] ?? '-') ?></td><td><?= $e($row['telepon'] ?? '-') ?><br><?= $e($row['email'] ?? '-') ?></td><td><?= $e($row['status'] ?? '') ?></td><td class="text-right"><a class="btn btn-sm btn-outline-primary" href="?page=detail-mitra&id=<?= (int) ($row['id'] ?? 0) ?>">Detail</a> <a class="btn btn-sm btn-outline-secondary" href="?page=edit-mitra&id=<?= (int) ($row['id'] ?? 0) ?>">Edit</a></td></tr>
    <?php endforeach; ?>
    <?php if ($instances === []): ?><tr><td colspan="5" class="text-center text-muted">Tidak ada Instansi.</td></tr><?php endif; ?>
    </tbody></table></div><div class="card-body d-flex justify-content-between"><span><?= (int) ($pagination['total'] ?? 0) ?> Instansi</span><div><?php if ($currentPage > 1): ?><a class="btn btn-sm btn-outline-secondary" href="?<?= $e(http_build_query(array_merge($_GET, ['page' => 'data-mitra', 'list_page' => $currentPage - 1]))) ?>">Sebelumnya</a><?php endif; ?> <?php if ($currentPage < $lastPage): ?><a class="btn btn-sm btn-outline-secondary" href="?<?= $e(http_build_query(array_merge($_GET, ['page' => 'data-mitra', 'list_page' => $currentPage + 1]))) ?>">Berikutnya</a><?php endif; ?></div></div></div>
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
                <?php if ($isEdit): ?><div class="form-group col-md-6"><label for="instance-status-edit">Status</label><select id="instance-status-edit" class="form-control" name="status"><option value="aktif" <?= $value('status') === 'aktif' ? 'selected' : '' ?>>Aktif</option><option value="nonaktif" <?= $value('status') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option></select></div><?php endif; ?>
            </div>
            <button class="btn btn-primary">Simpan</button> <a class="btn btn-light" href="?page=data-mitra">Batal</a>
        </form>
    </div></div>
<?php elseif ($masterMode === 'detail'): ?>
    <?php if ($instance === []): ?><div class="alert alert-warning">Detail Instansi tidak tersedia.</div><?php else: ?>
        <div class="card shadow-sm mb-3"><div class="card-body">
            <div class="d-flex justify-content-between"><h2 class="h5"><?= $e($instance['nama'] ?? '') ?></h2><a class="btn btn-outline-secondary btn-sm" href="?page=edit-mitra&id=<?= (int) $instance['id'] ?>">Edit</a></div>
            <dl class="row mb-3"><?php foreach (['jenis' => 'Jenis', 'alamat' => 'Alamat', 'kota' => 'Kota', 'provinsi' => 'Provinsi', 'telepon' => 'Telepon', 'email' => 'Email', 'nama_penanggung_jawab' => 'Penanggung Jawab', 'status' => 'Status'] as $key => $label): ?><dt class="col-sm-3"><?= $e($label) ?></dt><dd class="col-sm-9"><?= nl2br($e($instance[$key] ?? '-')) ?></dd><?php endforeach; ?></dl>
            <form method="post" action="?page=detail-mitra" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="action" value="master-instance-status"><input type="hidden" name="instansi_id" value="<?= (int) $instance['id'] ?>">
                <input type="hidden" name="status" value="<?= ($instance['status'] ?? '') === 'aktif' ? 'nonaktif' : 'aktif' ?>">
                <button class="btn btn-sm <?= ($instance['status'] ?? '') === 'aktif' ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= ($instance['status'] ?? '') === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
            </form>
        </div></div>
        <?php if ($picForEdit !== []): ?><div class="card shadow-sm mb-3"><div class="card-header">Edit PIC</div><div class="card-body"><form method="post" action="?page=detail-mitra">
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="action" value="master-pic-update"><input type="hidden" name="instansi_id" value="<?= (int) $instance['id'] ?>"><input type="hidden" name="pic_id" value="<?= (int) $picForEdit['id'] ?>">
            <?php foreach (['nama' => 'Nama', 'jabatan' => 'Jabatan', 'no_hp' => 'Nomor HP', 'email' => 'Email'] as $key => $label): ?><div class="form-group"><label><?= $e($label) ?></label><input class="form-control" name="<?= $e($key) ?>" value="<?= $e($picForEdit[$key] ?? '') ?>" <?= $key === 'nama' ? 'required' : '' ?>></div><?php endforeach; ?>
            <button class="btn btn-primary">Simpan PIC</button> <a class="btn btn-light" href="?page=detail-mitra&id=<?= (int) $instance['id'] ?>">Batal</a>
        </form></div></div><?php endif; ?>
        <div class="card shadow-sm mb-3"><div class="card-header">PIC Instansi <span class="small text-muted">(opsional untuk penempatan PKL)</span></div><div class="card-body">
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Nama</th><th>Jabatan</th><th>Kontak</th><th>Status</th><th></th></tr></thead><tbody>
            <?php foreach ($pics as $pic): ?><tr><td><?= $e($pic['nama'] ?? '') ?></td><td><?= $e($pic['jabatan'] ?? '-') ?></td><td><?= $e($pic['no_hp'] ?? '-') ?><br><?= $e($pic['email'] ?? '-') ?></td><td><?= !empty($pic['aktif']) ? 'Aktif' : 'Nonaktif' ?></td><td><a class="btn btn-sm btn-outline-secondary" href="?page=detail-mitra&id=<?= (int) $instance['id'] ?>&pic_id=<?= (int) $pic['id'] ?>">Edit</a> <form class="d-inline" method="post" action="?page=detail-mitra"><input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="action" value="master-pic-status"><input type="hidden" name="instansi_id" value="<?= (int) $instance['id'] ?>"><input type="hidden" name="pic_id" value="<?= (int) $pic['id'] ?>"><input type="hidden" name="aktif" value="<?= !empty($pic['aktif']) ? '0' : '1' ?>"><button class="btn btn-sm btn-outline-<?= !empty($pic['aktif']) ? 'danger' : 'success' ?>"><?= !empty($pic['aktif']) ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td></tr><?php endforeach; ?>
            <?php if ($pics === []): ?><tr><td colspan="5" class="text-muted">Belum ada PIC.</td></tr><?php endif; ?></tbody></table></div>
            <form method="post" action="?page=detail-mitra" class="border-top pt-3"><input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>"><input type="hidden" name="action" value="master-pic-create"><input type="hidden" name="instansi_id" value="<?= (int) $instance['id'] ?>">
                <h3 class="h6">Tambah PIC</h3><div class="form-row"><?php foreach (['nama' => 'Nama', 'jabatan' => 'Jabatan', 'no_hp' => 'Nomor HP', 'email' => 'Email'] as $key => $label): ?><div class="form-group col-md-3"><label><?= $e($label) ?></label><input class="form-control" name="<?= $e($key) ?>" value="<?= $e($picInput[$key] ?? '') ?>" <?= $key === 'nama' ? 'required' : '' ?>></div><?php endforeach; ?></div><button class="btn btn-sm btn-primary">Tambah PIC</button>
            </form>
        </div></div>
    <?php endif; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';
