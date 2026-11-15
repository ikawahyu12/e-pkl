<?php
$pageTitle  = 'Edit Akun Guru';
$activePage = 'user-guru';

$isEditing = true;
$guru = is_array($guru ?? null) ? $guru : [];

ob_start();
?>
<style>
    .page-heading { display: none !important; }
    .sf-crumb { font-size: .72rem; color: #64748b; margin-bottom: .5rem; }
    .sf-crumb a { color: #64748b; text-decoration: none; }
    .sf-crumb .now { color: #2563eb; font-weight: 700; }
    .sf-crumb .sep { margin: 0 .35rem; }
    .sf-title { font-size: 1.45rem; font-weight: 800; color: #0f1d3a; margin: 0; }
    .sf-sub { font-size: .82rem; color: #64748b; margin: .25rem 0 1.25rem; }

    .sf-card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(16,24,40,.07); padding: 1.5rem; }
    .sf-label { display: block; font-size: .64rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #475569; margin-bottom: .4rem; }
    .sf-label .req { color: #e11d48; }

    .sf-ic { position: relative; }
    .sf-ic > i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; pointer-events: none; }
    .sf-input { width: 100%; height: 40px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; font-size: .8rem; color: #1e293b; padding: 0 .85rem 0 36px; outline: 0; }
    .sf-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }

    .sf-radios { display: flex; gap: .6rem; }
    .sf-radio { flex: 1; display: flex; align-items: center; justify-content: space-between; gap: .4rem; min-height: 40px; border: 1px solid #e2e8f0; border-radius: 10px; padding: .35rem .75rem; margin: 0; cursor: pointer; font-size: .76rem; color: #334155; background: #fff; }
    .sf-radio input { accent-color: #2563eb; margin: 0 .5rem 0 0; }
    .sf-radio.on { border-color: #2563eb; background: #fafcff; }
    .sf-tag { font-size: .6rem; font-weight: 600; padding: .1rem .5rem; border-radius: 5px; border: 1px solid; white-space: nowrap; }
    .sf-tag-aktif { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
    .sf-tag-pending { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .sf-foot { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.5rem; }
    .sf-btn { height: 36px; border-radius: 9px; font-size: .76rem; font-weight: 600; padding: 0 1.15rem; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; }
    .sf-btn-cancel { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
    .sf-btn-save { background: #1d56c9; color: #fff; border: 0; }
</style>

<div class="sf">
    <div class="sf-crumb">
        <a href="?page=manajemen-guru">Manajemen Guru</a><span class="sep">&gt;</span><span class="now">Edit Akun</span>
    </div>
    <h1 class="sf-title">Edit Akun Guru</h1>
    <p class="sf-sub">Perbarui nama, username, password, dan status akun guru</p>

    <?php if (is_string($adminPageError ?? null)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($adminPageError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (is_array($adminFlash ?? null) && ($adminFlash['type'] ?? '') === 'danger'): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($adminFlash['message'] ?? 'Permintaan gagal.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <?php if (is_array($adminFlash['errors'] ?? null) && $adminFlash['errors'] !== []): ?>
            <ul class="alert alert-danger">
                <?php foreach ($adminFlash['errors'] as $messages): ?>
                    <?php foreach ((array) $messages as $message): ?>
                        <li><?= htmlspecialchars((string) $message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
    <form id="sfForm" action="?page=edit-guru&amp;id=<?= (int) ($guru['id'] ?? 0) ?>" method="post">
        <input type="hidden" name="action" value="teacher-user-update">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="user_id" value="<?= (int) ($guru['id'] ?? 0) ?>">
        <div class="sf-card">
            <?php include __DIR__ . '/_form.php'; ?>

            <?php
            $responsibilities = is_array($teacherResponsibilities['penempatan'] ?? null)
                ? $teacherResponsibilities['penempatan']
                : [];
            $replacements = is_array($teacherResponsibilities['guru_pengganti'] ?? null)
                ? $teacherResponsibilities['guru_pengganti']
                : [];
            ?>
            <?php if ($responsibilities !== []): ?>
                <hr>
                <h6 class="font-weight-bold">Siswa yang masih dibimbing</h6>
                <p class="small text-muted">Pilih Guru pengganti dan isi alasan untuk setiap penempatan sebelum memilih status Nonaktif.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>Instansi</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Guru pengganti</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responsibilities as $placement): ?>
                                <?php $placementId = (int) ($placement['id'] ?? 0); ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars((string) ($placement['siswa']['nama'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                        <br><small><?= htmlspecialchars((string) ($placement['siswa']['nisn'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
                                        <input type="hidden" name="reassignments[<?= $placementId ?>][penempatan_id]" value="<?= $placementId ?>">
                                    </td>
                                    <td><?= htmlspecialchars((string) ($placement['instansi']['nama'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($placement['periode']['nama'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($placement['status'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                    <td>
                                        <select class="form-control form-control-sm teacher-replacement" name="reassignments[<?= $placementId ?>][guru_baru_id]">
                                            <option value="">Pilih Guru aktif</option>
                                            <?php foreach ($replacements as $replacement): ?>
                                                <option value="<?= (int) ($replacement['id'] ?? 0) ?>">
                                                    <?= htmlspecialchars((string) ($replacement['nama'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                                    (<?= htmlspecialchars((string) ($replacement['nip'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input class="form-control form-control-sm teacher-reason" name="reassignments[<?= $placementId ?>][alasan]" maxlength="5000"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="sf-foot">
                <a href="?page=manajemen-guru" class="sf-btn sf-btn-cancel">Batal</a>
                <button type="submit" class="sf-btn sf-btn-save"><i class="fas fa-save mr-2"></i>Perbarui Akun</button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusInputs = document.querySelectorAll('input[name="status"]');
    const replacements = document.querySelectorAll('.teacher-replacement');
    const reasons = document.querySelectorAll('.teacher-reason');

    function updateTransferRequirements() {
        const deactivating = document.querySelector('input[name="status"]:checked')?.value === 'nonaktif';
        replacements.forEach((input) => { input.required = deactivating; });
        reasons.forEach((input) => { input.required = deactivating; });
    }

    statusInputs.forEach((input) => input.addEventListener('change', updateTransferRequirements));
    updateTransferRequirements();
});
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/layouts/admin.php';
?>