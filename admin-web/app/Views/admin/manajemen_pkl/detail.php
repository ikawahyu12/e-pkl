<?php
$pageTitle = 'Detail Penempatan PKL';
$pageSubtitle = 'Data penempatan dan seluruh riwayat perubahan.';
$activePage = 'manajemen-pkl';
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$placement = is_array($placement ?? null) ? $placement : [];
$placementInput = is_array($placementInput ?? null) ? $placementInput : [];
$isOpen = in_array($placement['status'] ?? null, ['belum_mulai', 'aktif'], true);
$isCanceled = ($placement['status'] ?? null) === 'dibatalkan';
$periodAcceptsPlacements = in_array($placement['periode']['status'] ?? null, ['persiapan', 'berjalan'], true);
$canMove = $isOpen && $periodAcceptsPlacements;
$canReactivate = $isCanceled && $periodAcceptsPlacements;
$placementFormMode = 'move';
$cancellationReasons = [
    'siswa_mengundurkan_diri' => 'Siswa mengundurkan diri.',
    'perubahan_penempatan_dari_sekolah' => 'Perubahan penempatan dari sekolah.',
    'instansi_tidak_dapat_menerima_siswa' => 'Instansi tidak dapat menerima siswa.',
    'ketidaksesuaian_data_penempatan' => 'Ketidaksesuaian data penempatan.',
    'pelanggaran_atau_ketidakdisiplinan' => 'Pelanggaran atau ketidakdisiplinan.',
    'kondisi_operasional_tertentu' => 'Kondisi operasional tertentu.',
    'lainnya' => 'Lainnya.',
];
$placementInput['tanggal_mulai'] ??= $placement['tanggal_mulai'] ?? '';
$placementInput['tanggal_selesai'] ??= $placement['tanggal_selesai'] ?? '';
ob_start();
?>
<div class="mb-3">
    <a class="btn btn-sm btn-outline-secondary" href="?page=manajemen-pkl"><i class="fas fa-arrow-left mr-1"></i>Kembali ke daftar</a>
</div>
<?php if (is_string($adminPageError ?? null)): ?>
    <div class="alert alert-danger"><?= $e($adminPageError) ?></div>
<?php endif; ?>
<?php if (is_string($adminPageSuccess ?? null)): ?>
    <div class="alert alert-success"><?= $e($adminPageSuccess) ?></div>
<?php endif; ?>
<?php if (is_array($adminFlash ?? null) && ($adminFlash['type'] ?? '') === 'danger'): ?>
    <div class="alert alert-danger">
        <?= $e($adminFlash['message'] ?? 'Permintaan gagal.') ?>
        <?php foreach (($adminFlash['errors'] ?? []) as $messages): ?>
            <?php foreach ((array) $messages as $message): ?><div><?= $e($message) ?></div><?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if ($placement === []): ?>
    <div class="alert alert-warning">Detail penempatan tidak tersedia.</div>
<?php else: ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Penempatan #<?= (int) ($placement['id'] ?? 0) ?></strong>
            <span class="badge badge-<?= ($placement['status'] ?? '') === 'aktif' ? 'success' : (($placement['status'] ?? '') === 'belum_mulai' ? 'info' : (($placement['status'] ?? '') === 'dibatalkan' ? 'danger' : 'secondary')) ?>">
                <?= $e(str_replace('_', ' ', $placement['status'] ?? '-')) ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl>
                        <dt>Siswa</dt><dd><?= $e($placement['siswa']['nama_lengkap'] ?? '-') ?></dd>
                        <dt>NISN / NIS</dt><dd><?= $e($placement['siswa']['nisn'] ?? '-') ?> / <?= $e($placement['siswa']['nis'] ?? '-') ?></dd>
                        <dt>Periode PKL</dt><dd><?= $e($placement['periode']['nama'] ?? '-') ?> (<?= $e($placement['periode']['status'] ?? '-') ?>)</dd>
                        <dt>Tanggal</dt><dd><?= $e($placement['tanggal_mulai'] ?? '-') ?> – <?= $e($placement['tanggal_selesai'] ?? '-') ?></dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl>
                        <dt>Instansi</dt><dd><?= $e($placement['instansi']['nama'] ?? '-') ?></dd>
                        <dt>Guru Pembimbing</dt><dd><?= $e($placement['guru']['nama_lengkap'] ?? '-') ?> · <?= $e($placement['guru']['nip'] ?? '-') ?></dd>
                        <dt>PIC Instansi</dt><dd><?= $e($placement['pic']['nama'] ?? '-') ?><?= !empty($placement['pic']['jabatan']) ? ' · ' . $e($placement['pic']['jabatan']) : '' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isOpen): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Aksi Penempatan</strong></div>
            <div class="card-body">
                <?php if (!$canMove): ?>
                    <div class="alert alert-warning">Pemindahan tidak tersedia karena periode ini tidak menerima penempatan baru.</div>
                <?php endif; ?>
                <div class="row">
                    <?php if ($canMove): ?>
                    <div class="col-lg-6 mb-4">
                        <h5>Pindahkan Penempatan</h5>
                        <p class="small text-muted">Siswa dan periode tetap. Pemindahan dilakukan melalui satu operasi atomik.</p>
                        <form id="placement-form" method="post" action="?page=detail-penempatan-pkl&amp;id=<?= (int) $placement['id'] ?>">
                            <input type="hidden" name="action" value="placement-move">
                            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                            <input type="hidden" name="placement_id" value="<?= (int) $placement['id'] ?>">
                            <?php require __DIR__ . '/_fields.php'; ?>
                            <div class="form-group">
                                <label for="move-reason-code">Alasan pembatalan penempatan lama</label>
                                <select class="form-control cancellation-reason" id="move-reason-code" name="alasan_kode" required>
                                    <option value="">Pilih alasan</option>
                                    <?php foreach ($cancellationReasons as $code => $label): ?>
                                        <option value="<?= $e($code) ?>" <?= ($placementInput['alasan_kode'] ?? '') === $code ? 'selected' : '' ?>><?= $e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group cancellation-detail d-none">
                                <label for="move-reason-detail">Alasan detail</label>
                                <textarea class="form-control" id="move-reason-detail" name="alasan_detail" maxlength="5000"><?= $e($placementInput['alasan_detail'] ?? '') ?></textarea>
                            </div>
                            <button class="btn btn-warning" type="submit" <?= ($placementReferencesLoaded ?? false) ? '' : 'disabled' ?> onclick="return confirm('Pindahkan penempatan ini? Penempatan lama akan dibatalkan dan riwayat akan dicatat.')">
                                Pindahkan Penempatan
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <div class="col-lg-6">
                        <h5>Batalkan Penempatan</h5>
                        <form method="post" action="?page=detail-penempatan-pkl&amp;id=<?= (int) $placement['id'] ?>" onsubmit="return confirm('Yakin ingin membatalkan penempatan ini?')">
                            <input type="hidden" name="action" value="placement-cancel">
                            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                            <input type="hidden" name="placement_id" value="<?= (int) $placement['id'] ?>">
                            <div class="form-group">
                                <label for="cancel-reason-code">Alasan pembatalan</label>
                                <select class="form-control cancellation-reason" id="cancel-reason-code" name="alasan_kode" required>
                                    <option value="">Pilih alasan</option>
                                    <?php foreach ($cancellationReasons as $code => $label): ?>
                                        <option value="<?= $e($code) ?>" <?= ($placementInput['alasan_kode'] ?? '') === $code ? 'selected' : '' ?>><?= $e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group cancellation-detail d-none">
                                <label for="cancel-reason-detail">Alasan detail</label>
                                <textarea class="form-control" id="cancel-reason-detail" name="alasan_detail" maxlength="5000"><?= $e($placementInput['alasan_detail'] ?? '') ?></textarea>
                            </div>
                            <button class="btn btn-danger" type="submit">Batalkan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($canReactivate): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Aktifkan Kembali</strong></div>
            <div class="card-body">
                <form method="post" action="?page=detail-penempatan-pkl&amp;id=<?= (int) $placement['id'] ?>" onsubmit="return confirm('Ajukan pengaktifan kembali? Backend akan memeriksa kelayakan dan kuota.')">
                    <input type="hidden" name="action" value="placement-reactivate">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                    <input type="hidden" name="placement_id" value="<?= (int) $placement['id'] ?>">
                    <div class="form-group">
                        <label for="reactivation-reason">Alasan pengaktifan kembali</label>
                        <textarea class="form-control" id="reactivation-reason" name="alasan" maxlength="5000" required><?= $e($placementInput['alasan'] ?? '') ?></textarea>
                    </div>
                    <button class="btn btn-success" type="submit">Aktifkan Kembali</button>
                </form>
            </div>
        </div>
    <?php elseif ($isCanceled): ?>
        <div class="alert alert-warning">Pengaktifan kembali tidak tersedia karena periode ini tidak menerima penempatan baru.</div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Riwayat Perubahan Status</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="thead-light"><tr><th>Waktu</th><th>Status Lama</th><th>Status Baru</th><th>Alasan</th><th>Admin</th></tr></thead>
                <tbody>
                    <?php if (($placement['riwayat_status'] ?? []) === []): ?>
                        <tr><td colspan="5" class="text-center text-muted">Belum ada riwayat status.</td></tr>
                    <?php else: foreach ($placement['riwayat_status'] as $history): ?>
                        <tr>
                            <td><?= $e($history['created_at'] ?? '-') ?></td>
                            <td><?= $e($history['status_lama'] ?? '-') ?></td>
                            <td><?= $e($history['status_baru'] ?? '-') ?></td>
                            <td><?= $e($history['alasan_kode'] ?? '-') ?><?= !empty($history['alasan_detail']) ? '<br>' . $e($history['alasan_detail']) : '' ?></td>
                            <td><?= $e($history['admin']['nama_tampilan'] ?? $history['admin']['username'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Riwayat Pergantian Guru</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="thead-light"><tr><th>Waktu</th><th>Guru Lama</th><th>Guru Baru</th><th>Alasan</th><th>Admin</th></tr></thead>
                <tbody>
                    <?php if (($placement['riwayat_guru'] ?? []) === []): ?>
                        <tr><td colspan="5" class="text-center text-muted">Belum ada riwayat pergantian Guru.</td></tr>
                    <?php else: foreach ($placement['riwayat_guru'] as $history): ?>
                        <tr>
                            <td><?= $e($history['created_at'] ?? '-') ?></td>
                            <td><?= $e($history['guru_lama']['nama_lengkap'] ?? '-') ?></td>
                            <td><?= $e($history['guru_baru']['nama_lengkap'] ?? '-') ?></td>
                            <td><?= $e($history['alasan'] ?? '-') ?></td>
                            <td><?= $e($history['admin']['nama_tampilan'] ?? $history['admin']['username'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cancellation-reason').forEach(function (select) {
        const detail = select.closest('form').querySelector('.cancellation-detail');
        const textarea = detail?.querySelector('textarea');
        function update() {
            const required = select.value === 'lainnya';
            detail?.classList.toggle('d-none', !required);
            if (textarea) textarea.required = required;
        }
        select.addEventListener('change', update);
        update();
    });
});
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__, 2) . '/layouts/admin.php';
?>
