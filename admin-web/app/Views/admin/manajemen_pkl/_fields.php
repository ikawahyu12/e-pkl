<?php
$referenceData = is_array($placementReferences ?? null) ? $placementReferences : [];
$formInput = is_array($placementInput ?? null) ? $placementInput : [];
$studentRows = is_array($referenceData['siswa'] ?? null) ? $referenceData['siswa'] : [];
$teacherRows = is_array($referenceData['guru'] ?? null) ? $referenceData['guru'] : [];
$selectedStudent = is_array($referenceData['selected']['siswa'] ?? null) ? $referenceData['selected']['siswa'] : null;
$selectedTeacher = is_array($referenceData['selected']['guru'] ?? null) ? $referenceData['selected']['guru'] : null;
$periodRows = is_array($referenceData['periode'] ?? null) ? $referenceData['periode'] : [];
$instanceRows = is_array($referenceData['instansi'] ?? null) ? $referenceData['instansi'] : [];
$picRows = is_array($referenceData['pic_instansi'] ?? null) ? $referenceData['pic_instansi'] : [];
$pagination = is_array($referenceData['pagination'] ?? null) ? $referenceData['pagination'] : [];
$selectedId = static fn (string $key): string => is_scalar($formInput[$key] ?? null) ? (string) $formInput[$key] : '';
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$isMove = ($placementFormMode ?? 'create') === 'move';
$referenceLoaded = ($placementReferencesLoaded ?? false) === true;
$quota = is_array($referenceData['kuota'] ?? null) ? $referenceData['kuota'] : null;
$movePeriodStart = $isMove ? ($placement['periode']['tanggal_mulai'] ?? '') : '';
$movePeriodEnd = $isMove ? ($placement['periode']['tanggal_selesai'] ?? '') : '';
?>
<?php if (!$referenceLoaded): ?>
    <div class="alert alert-danger">Referensi Siswa, Guru, periode, dan Instansi belum tersedia. Muat ulang halaman sebelum mengirim.</div>
<?php endif; ?>
<div class="alert alert-danger d-none" id="placementReferenceError" role="alert"></div>
<?php if (!$isMove): ?>
    <div class="form-group">
        <label for="student-search">Cari Siswa (nama/NISN/NIS)</label>
        <div class="input-group">
            <input id="student-search" name="siswa_search" class="form-control" value="<?= $escape($formInput['siswa_search'] ?? '') ?>" maxlength="100">
            <div class="input-group-append"><button class="btn btn-outline-primary" type="button" id="student-search-button">Cari</button></div>
        </div>
        <select class="form-control mt-2" id="student-select" name="siswa_id" required>
            <option value="">Pilih Siswa aktif</option>
            <?php if ($selectedStudent !== null): ?>
                <option value="<?= (int) $selectedStudent['id'] ?>" <?= $selectedId('siswa_id') === (string) $selectedStudent['id'] ? 'selected' : '' ?>>
                    <?= $escape($selectedStudent['nama_lengkap'] . ' · ' . ($selectedStudent['nisn'] ?? '-')) ?>
                </option>
            <?php endif; ?>
            <?php foreach ($studentRows as $student): ?>
                <?php if ((string) ($student['id'] ?? '') === (string) ($selectedStudent['id'] ?? '')) { continue; } ?>
                <option value="<?= (int) ($student['id'] ?? 0) ?>" <?= $selectedId('siswa_id') === (string) ($student['id'] ?? '') ? 'selected' : '' ?>>
                    <?= $escape(($student['nama_lengkap'] ?? '-') . ' · ' . ($student['nisn'] ?? '-')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="d-flex align-items-center mt-1">
            <button type="button" class="btn btn-sm btn-link px-0" id="student-prev">Sebelumnya</button>
            <span class="small text-muted mx-2" id="student-page"></span>
            <button type="button" class="btn btn-sm btn-link px-0" id="student-next">Berikutnya</button>
            <span class="small text-muted ml-auto">Pilihan menggunakan ID profil Siswa.</span>
        </div>
    </div>
    <div class="form-group">
        <label for="period-select">Periode PKL</label>
        <select class="form-control" id="period-select" name="periode_id" required>
            <option value="">Pilih periode persiapan/berjalan</option>
            <?php foreach ($periodRows as $period): ?>
                <option value="<?= (int) ($period['id'] ?? 0) ?>"
                    data-start="<?= $escape($period['tanggal_mulai'] ?? '') ?>"
                    data-end="<?= $escape($period['tanggal_selesai'] ?? '') ?>"
                    <?= $selectedId('periode_id') === (string) ($period['id'] ?? '') ? 'selected' : '' ?>>
                    <?= $escape(($period['nama'] ?? '-') . ' · ' . ($period['status'] ?? '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
<?php else: ?>
    <div class="form-group">
        <label>Siswa dan periode (dipertahankan)</label>
        <p class="form-control-plaintext mb-0">
            <?= $escape(($placement['siswa']['nama_lengkap'] ?? '-') . ' · ' . ($placement['siswa']['nisn'] ?? '-')) ?>
            — <?= $escape($placement['periode']['nama'] ?? '-') ?>
        </p>
    </div>
<?php endif; ?>
<div class="form-group">
    <label for="instance-select">Instansi <?= $isMove ? 'tujuan' : '' ?></label>
    <select class="form-control" id="instance-select" name="instansi_id" required>
        <option value="">Pilih Instansi aktif</option>
        <?php foreach ($instanceRows as $instance): ?>
            <option value="<?= (int) ($instance['id'] ?? 0) ?>" <?= $selectedId('instansi_id') === (string) ($instance['id'] ?? '') ? 'selected' : '' ?>>
                <?= $escape($instance['nama'] ?? '-') ?>
            </option>
        <?php endforeach; ?>
    </select>
    <small class="form-text text-muted" id="placement-quota">
        <?php if ($quota === null): ?>
            Pilih Instansi dan periode untuk melihat kuota.
        <?php elseif ($quota['tanpa_batas'] ?? false): ?>
            Kuota tidak dibatasi; terpakai <?= (int) ($quota['terpakai'] ?? 0) ?>.
        <?php else: ?>
            Kuota <?= (int) ($quota['kuota_siswa'] ?? 0) ?>; terpakai <?= (int) ($quota['terpakai'] ?? 0) ?>; sisa <?= (int) ($quota['sisa'] ?? 0) ?>.
        <?php endif; ?>
    </small>
</div>
<div class="form-group">
    <label for="teacher-search">Cari Guru (nama/NIP)</label>
    <div class="input-group">
        <input id="teacher-search" name="guru_search" class="form-control" value="<?= $escape($formInput['guru_search'] ?? '') ?>" maxlength="100">
        <div class="input-group-append"><button class="btn btn-outline-primary" type="button" id="teacher-search-button">Cari</button></div>
    </div>
    <select class="form-control mt-2" id="teacher-select" name="guru_id" required>
        <option value="">Pilih Guru aktif</option>
        <?php if ($selectedTeacher !== null): ?>
            <option value="<?= (int) $selectedTeacher['id'] ?>" <?= $selectedId('guru_id') === (string) $selectedTeacher['id'] ? 'selected' : '' ?>>
                <?= $escape($selectedTeacher['nama_lengkap'] . ' · ' . $selectedTeacher['nip']) ?>
            </option>
        <?php endif; ?>
        <?php foreach ($teacherRows as $teacher): ?>
            <?php if ((string) ($teacher['id'] ?? '') === (string) ($selectedTeacher['id'] ?? '')) { continue; } ?>
            <option value="<?= (int) ($teacher['id'] ?? 0) ?>" <?= $selectedId('guru_id') === (string) ($teacher['id'] ?? '') ? 'selected' : '' ?>>
                <?= $escape(($teacher['nama_lengkap'] ?? '-') . ' · ' . ($teacher['nip'] ?? '-')) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <div class="d-flex align-items-center mt-1">
        <button type="button" class="btn btn-sm btn-link px-0" id="teacher-prev">Sebelumnya</button>
        <span class="small text-muted mx-2" id="teacher-page"></span>
        <button type="button" class="btn btn-sm btn-link px-0" id="teacher-next">Berikutnya</button>
        <span class="small text-muted ml-auto">Pilihan menggunakan ID profil Guru.</span>
    </div>
</div>
<div class="form-group">
    <label for="pic-select">PIC Instansi (opsional)</label>
    <select class="form-control" id="pic-select" name="pic_instansi_id">
        <option value="">Tanpa PIC</option>
        <?php foreach ($picRows as $pic): ?>
            <option value="<?= (int) ($pic['id'] ?? 0) ?>" <?= $selectedId('pic_instansi_id') === (string) ($pic['id'] ?? '') ? 'selected' : '' ?>>
                <?= $escape($pic['nama'] ?? '-') ?><?= !empty($pic['jabatan']) ? ' · ' . $escape($pic['jabatan']) : '' ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="placement-start">Tanggal mulai</label>
        <input class="form-control" type="date" id="placement-start" name="tanggal_mulai" value="<?= $escape($formInput['tanggal_mulai'] ?? '') ?>" min="<?= $escape($movePeriodStart) ?>" max="<?= $escape($movePeriodEnd) ?>" required>
    </div>
    <div class="form-group col-md-6">
        <label for="placement-end">Tanggal selesai</label>
        <input class="form-control" type="date" id="placement-end" name="tanggal_selesai" value="<?= $escape($formInput['tanggal_selesai'] ?? '') ?>" min="<?= $escape($movePeriodStart) ?>" max="<?= $escape($movePeriodEnd) ?>" required>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('placement-form');
    const errorBox = document.getElementById('placementReferenceError');
    const studentSelect = document.getElementById('student-select');
    const teacherSelect = document.getElementById('teacher-select');
    const periodSelect = document.getElementById('period-select');
    const instanceSelect = document.getElementById('instance-select');
    const picSelect = document.getElementById('pic-select');
    let referenceData = <?= json_encode($referenceData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    let referencesReady = <?= $referenceLoaded ? 'true' : 'false' ?>;
    let referenceRequest = 0;

    function updatePageLabels() {
        ['siswa', 'guru'].forEach(function (key) {
            const data = referenceData.pagination?.[key];
            const label = document.getElementById(key === 'siswa' ? 'student-page' : 'teacher-page');
            const previous = document.getElementById(key === 'siswa' ? 'student-prev' : 'teacher-prev');
            const next = document.getElementById(key === 'siswa' ? 'student-next' : 'teacher-next');
            if (!label || !data) return;
            label.textContent = data.current_page + ' / ' + Math.max(data.last_page, 1);
            previous.disabled = data.current_page <= 1;
            next.disabled = data.current_page >= data.last_page;
        });
    }

    function setOptions(select, rows, selected, label, preserve) {
        if (!select) return;
        const oldValue = preserve ? select.value : '';
        select.replaceChildren(new Option(label, ''));
        const visible = Array.isArray(rows) ? rows.slice() : [];
        if (selected && !visible.some((row) => String(row.id) === String(selected.id))) {
            visible.unshift(selected);
        }
        visible.forEach(function (row) {
            const text = select.id === 'student-select'
                ? (row.nama_lengkap || '-') + ' · ' + (row.nisn || '-')
                : (row.nama_lengkap || '-') + ' · ' + (row.nip || '-');
            select.add(new Option(text, row.id));
        });
        if (oldValue && Array.from(select.options).some((option) => option.value === oldValue)) {
            select.value = oldValue;
        } else if (selected && String(selected.id) === oldValue) {
            select.value = oldValue;
        }
    }

    function updateQuota() {
        const target = document.getElementById('placement-quota');
        const quota = referenceData.kuota;
        if (!target) return;
        if (!quota) {
            target.textContent = 'Pilih Instansi dan periode untuk melihat kuota.';
        } else if (quota.tanpa_batas) {
            target.textContent = 'Kuota tidak dibatasi; terpakai ' + quota.terpakai + '.';
        } else {
            target.textContent = 'Kuota ' + quota.kuota_siswa + '; terpakai ' + quota.terpakai + '; sisa ' + quota.sisa + '.';
        }
    }

    function updateDateRange() {
        const start = document.getElementById('placement-start');
        const end = document.getElementById('placement-end');
        let periodStart = <?= json_encode($movePeriodStart) ?>;
        let periodEnd = <?= json_encode($movePeriodEnd) ?>;
        if (periodSelect) {
            const option = periodSelect.options[periodSelect.selectedIndex];
            periodStart = option?.dataset.start || '';
            periodEnd = option?.dataset.end || '';
        }
        start.min = periodStart;
        start.max = periodEnd;
        end.min = start.value || periodStart;
        end.max = periodEnd;
    }

    async function loadReferences(change) {
        const requestId = ++referenceRequest;
        referencesReady = false;
        errorBox.classList.add('d-none');
        const currentStudent = studentSelect?.value || '';
        const currentTeacher = teacherSelect?.value || '';
        const params = new URLSearchParams({
            page: 'referensi-penempatan-pkl',
            per_page: '20',
            siswa_search: document.getElementById('student-search')?.value || '',
            guru_search: document.getElementById('teacher-search')?.value || '',
            siswa_page: String(change.siswaPage ?? referenceData.pagination?.siswa?.current_page ?? 1),
            guru_page: String(change.guruPage ?? referenceData.pagination?.guru?.current_page ?? 1),
            instansi_id: instanceSelect?.value || '',
            periode_id: periodSelect?.value || <?= $isMove ? json_encode((string) ($placement['periode']['id'] ?? '')) : "''" ?>,
            siswa_id: currentStudent,
            guru_id: currentTeacher
        });
        try {
            const response = await fetch('?' + params.toString(), { headers: { Accept: 'application/json' } });
            const body = await response.json();
            if (requestId !== referenceRequest) return;
            if (!response.ok || body.success !== true || !body.data) {
                throw new Error(body.message || 'Referensi tidak dapat dimuat.');
            }
            referenceData = body.data;
            setOptions(studentSelect, referenceData.siswa, referenceData.selected?.siswa, 'Pilih Siswa aktif', true);
            setOptions(teacherSelect, referenceData.guru, referenceData.selected?.guru, 'Pilih Guru aktif', true);
            if (picSelect) {
                const oldPic = picSelect.value;
                picSelect.replaceChildren(new Option('Tanpa PIC', ''));
                (referenceData.pic_instansi || []).forEach((pic) => {
                    picSelect.add(new Option(pic.nama + (pic.jabatan ? ' · ' + pic.jabatan : ''), pic.id));
                });
                if (Array.from(picSelect.options).some((option) => option.value === oldPic)) picSelect.value = oldPic;
            }
            updatePageLabels();
            updateQuota();
            referencesReady = true;
        } catch (error) {
            if (requestId !== referenceRequest) return;
            errorBox.textContent = error.message || 'Referensi tidak dapat dimuat.';
            errorBox.classList.remove('d-none');
        }
    }

    document.getElementById('student-search-button')?.addEventListener('click', () => loadReferences({ siswaPage: 1 }));
    document.getElementById('teacher-search-button')?.addEventListener('click', () => loadReferences({ guruPage: 1 }));
    document.getElementById('student-search')?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') { event.preventDefault(); loadReferences({ siswaPage: 1 }); }
    });
    document.getElementById('teacher-search')?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') { event.preventDefault(); loadReferences({ guruPage: 1 }); }
    });
    document.getElementById('student-prev')?.addEventListener('click', () => loadReferences({ siswaPage: Math.max(1, (referenceData.pagination?.siswa?.current_page || 1) - 1) }));
    document.getElementById('student-next')?.addEventListener('click', () => loadReferences({ siswaPage: (referenceData.pagination?.siswa?.current_page || 1) + 1 }));
    document.getElementById('teacher-prev')?.addEventListener('click', () => loadReferences({ guruPage: Math.max(1, (referenceData.pagination?.guru?.current_page || 1) - 1) }));
    document.getElementById('teacher-next')?.addEventListener('click', () => loadReferences({ guruPage: (referenceData.pagination?.guru?.current_page || 1) + 1 }));
    instanceSelect?.addEventListener('change', () => loadReferences({}));
    periodSelect?.addEventListener('change', () => { updateDateRange(); loadReferences({}); });
    document.getElementById('placement-start')?.addEventListener('change', updateDateRange);
    updatePageLabels();
    updateQuota();
    updateDateRange();
    form?.addEventListener('submit', function (event) {
        if (!referencesReady) {
            event.preventDefault();
            errorBox.textContent = 'Form tidak dapat dikirim sebelum data referensi berhasil dimuat.';
            errorBox.classList.remove('d-none');
        }
    });
});
</script>
