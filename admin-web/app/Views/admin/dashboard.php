<?php
/** @var list<array{id: int|string, nama: string}> $instansiList Disediakan oleh DashboardController. */
/**
 * @var array{
 *     id: int|string,
 *     nama: string,
 *     jenis: string|null,
 *     alamat: string,
 *     kota: string|null,
 *     provinsi: string|null,
 *     latitude: string|null,
 *     longitude: string|null,
 *     radius_geofence_meter: int|string|null,
 *     alamat_lengkap: string,
 *     student_location_available: bool,
 *     inside_count: int|null,
 *     outside_count: int|null
 * }|null $selectedInstansi Disediakan oleh DashboardController.
 */
/** @var string $geofenceEndpoint Disediakan oleh DashboardController. */
/** @var string $monitoringEndpoint Disediakan oleh DashboardController. */
$pageTitle = 'Dashboard Monitoring PKL';
$pageSubtitle = 'Pantau aktivitas PKL secara realtime';
$pageStatusLabel = 'Realtime';
$activePage = 'dashboard';

$statistics = [
    [
        'label' => 'TOTAL SISWA PKL',
        'value' => '—',
        'description' => 'Siswa aktif terdaftar',
        'icon' => 'fas fa-users',
        'color' => 'primary',
    ],
    [
        'label' => 'HADIR HARI INI',
        'value' => '—',
        'description' => 'Siswa hadir di instansi',
        'icon' => 'fas fa-check-circle',
        'color' => 'success',
    ],
    [
        'label' => 'IZIN HARI INI',
        'value' => '—',
        'description' => 'Siswa dengan izin hari ini',
        'icon' => 'fas fa-clipboard-list',
        'color' => 'warning',
    ],
    [
        'label' => 'TIDAK HADIR HARI INI',
        'value' => '—',
        'description' => 'Presensi alpha atau sesi ditutup',
        'icon' => 'fas fa-user-times',
        'color' => 'danger',
    ],
];

$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

ob_start();
?>
<style>
    .dashboard-stat-card {
        overflow: hidden;
        border-radius: .55rem;
    }

    .dashboard-stat-card .card-body {
        min-height: 112px;
    }

    .dashboard-stat-label {
        color: #7b8794;
        font-size: .67rem;
        font-weight: 700;
        letter-spacing: .045em;
    }

    .dashboard-stat-value {
        color: #253247;
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1.15;
    }

    .dashboard-stat-description {
        color: #7b8794;
        font-size: .68rem;
    }

    .dashboard-stat-icon {
        display: inline-flex;
        width: 42px;
        height: 42px;
        align-items: center;
        justify-content: center;
        border-radius: .55rem;
        font-size: 1rem;
    }

    .dashboard-stat-icon-primary {
        color: #2875bd;
        background: #e8f2fb;
    }

    .dashboard-stat-icon-success {
        color: #1f8a63;
        background: #e6f5ef;
    }

    .dashboard-stat-icon-warning {
        color: #b7791f;
        background: #fff4dc;
    }

    .dashboard-stat-icon-danger {
        color: #c54a55;
        background: #fbeaec;
    }

    .dashboard-table-card {
        border: 1px solid #e7ecf2;
        border-radius: .6rem;
    }

    .dashboard-table-card .card-header {
        background: #fff;
        border-bottom: 1px solid #edf0f4;
        border-radius: .6rem .6rem 0 0;
    }

    .dashboard-table-title {
        color: #253247;
        font-size: .98rem;
        font-weight: 700;
    }

    .dashboard-date-label {
        color: #677588;
        font-size: .75rem;
        font-weight: 600;
    }

    .dashboard-filter-input {
        min-width: 205px;
    }

    .dashboard-filter-status {
        min-width: 140px;
    }

    .dashboard-attendance-table {
        min-width: 760px;
    }

    .dashboard-attendance-table thead th {
        padding-top: .8rem;
        padding-bottom: .8rem;
        color: #748092;
        background: #f8fafc;
        border-top: 0;
        border-bottom: 1px solid #e9edf2;
        font-size: .66rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .dashboard-attendance-table tbody td {
        padding-top: .82rem;
        padding-bottom: .82rem;
        color: #536174;
        font-size: .77rem;
        vertical-align: middle;
    }

    .dashboard-student-name {
        color: #344054;
        font-weight: 600;
    }

    .dashboard-company-name {
        min-width: 185px;
    }

    .dashboard-no-results {
        display: none;
    }

    /* Style tambahan untuk Panel Geofence Map */
    .geofence-map-card {
        border: 1px solid #e7ecf2;
        border-radius: .6rem;
    }

    .geofence-map-container {
        position: relative;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: .5rem;
        height: 280px;
        overflow: hidden;
    }

    #geofenceMap {
        width: 100%;
        height: 100%;
    }

    .geofence-map-message {
        position: absolute;
        inset: 0;
        z-index: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(248, 250, 252, .92);
        color: #677588;
        text-align: center;
    }

    .geofence-info-card {
        position: absolute;
        top: 16px;
        left: 16px;
        z-index: 500;
        max-width: min(360px, calc(100% - 32px));
    }

    .geofence-info-card .geofence-address {
        overflow-wrap: anywhere;
    }

    .geofence-instance-select {
        min-width: 220px;
        max-width: 100%;
    }

    .geofence-instance-details {
        font-size: .78rem;
    }

    .geofence-popup {
        line-height: 1.5;
    }

    @media (max-width: 575.98px) {
        .dashboard-stat-card .card-body {
            min-height: 104px;
        }

        .dashboard-stat-value {
            font-size: 1.35rem;
        }

        .dashboard-table-card .card-header {
            align-items: flex-start !important;
            flex-direction: column;
        }

        .dashboard-filter-input,
        .dashboard-filter-status {
            min-width: 0;
            width: 100%;
        }
    }
</style>

<section class="row" aria-label="Ringkasan presensi siswa PKL">
    <?php foreach ($statistics as $statistic): ?>
        <div class="col-12 col-sm-6 col-xl-3 mb-4">
            <article class="card dashboard-stat-card border-left-<?= $e($statistic['color']) ?> shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="mr-2">
                        <div class="dashboard-stat-label mb-2"><?= $e($statistic['label']) ?></div>
                        <div class="dashboard-stat-value mb-1"><?= $e($statistic['value']) ?></div>
                        <div class="dashboard-stat-description"><?= $e($statistic['description']) ?></div>
                    </div>
                    <span class="dashboard-stat-icon dashboard-stat-icon-<?= $e($statistic['color']) ?>">
                        <i class="<?= $e($statistic['icon']) ?>" aria-hidden="true"></i>
                    </span>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</section>

<!-- PANEL PEMETAAN GEOFENCE -->
<section class="card geofence-map-card shadow-sm mb-4" aria-labelledby="geofenceTitle">
    <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between flex-wrap">
        <div>
            <h2 class="dashboard-table-title mb-1" id="geofenceTitle">Pemetaan Geofencing & Koordinat</h2>
            <div class="dashboard-date-label">Visualisasi radius absensi otomatis instansi mitra</div>
        </div>
        <div class="d-flex align-items-center flex-wrap mt-2 mt-md-0">
            <label class="sr-only" for="geofenceInstanceSelect">Pilih instansi</label>
            <select class="custom-select custom-select-sm geofence-instance-select mr-2 mb-2 mb-sm-0"
                id="geofenceInstanceSelect" aria-label="Pilih instansi untuk pemetaan geofencing"
                <?= $instansiList === [] ? 'disabled' : '' ?>>
                <?php foreach ($instansiList as $instance): ?>
                    <option value="<?= $e($instance['id']) ?>"
                        <?= $selectedInstansi !== null && (int) $selectedInstansi['id'] === (int) $instance['id'] ? 'selected' : '' ?>>
                        <?= $e($instance['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="badge badge-primary p-2" id="geofenceRadiusBadge">
                <?= $selectedInstansi !== null && (float) ($selectedInstansi['radius_geofence_meter'] ?? 0) > 0
                    ? 'Radius Aktif: ' . $e($selectedInstansi['radius_geofence_meter']) . ' Meter'
                    : 'Radius geofencing belum diatur' ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="geofence-map-container mb-3">
            <div id="geofenceMap" role="application" aria-label="Peta lokasi instansi"></div>
            <div class="geofence-info-card p-2 bg-white rounded shadow-sm border" id="geofenceInfoCard"
                <?= $selectedInstansi === null ? 'hidden' : '' ?>>
                <div class="font-weight-bold text-dark" style="font-size: 0.8rem;" id="geofenceInfoName">
                    <?= $e($selectedInstansi['nama'] ?? '') ?>
                </div>
                <div class="text-muted geofence-address" style="font-size: 0.7rem;" id="geofenceInfoAddress">
                    <?= $e($selectedInstansi['alamat_lengkap'] ?? '') ?>
                </div>
                <div class="text-muted" style="font-size: 0.7rem;" id="geofenceInfoCoordinates">
                    <?php if ($selectedInstansi !== null && $selectedInstansi['latitude'] !== null && $selectedInstansi['longitude'] !== null): ?>
                        Koordinat: <?= $e($selectedInstansi['latitude']) ?>, <?= $e($selectedInstansi['longitude']) ?>
                    <?php else: ?>
                        Koordinat belum tersedia
                    <?php endif; ?>
                </div>
                <div class="text-muted" style="font-size: 0.7rem;" id="geofenceInfoRadius">
                    <?= $selectedInstansi !== null && (float) ($selectedInstansi['radius_geofence_meter'] ?? 0) > 0
                        ? 'Radius geofencing: ' . $e($selectedInstansi['radius_geofence_meter']) . ' Meter'
                        : 'Radius geofencing belum diatur' ?>
                </div>
            </div>
            <div class="geofence-map-message" id="geofenceMapMessage"
                <?= $selectedInstansi !== null
                    && $selectedInstansi['latitude'] !== null
                    && $selectedInstansi['longitude'] !== null
                    ? 'hidden'
                    : '' ?>>
                <?= $selectedInstansi === null
                    ? 'Tidak ada instansi aktif.'
                    : 'Koordinat lokasi instansi belum tersedia.' ?>
            </div>
        </div>

        <div class="geofence-instance-details text-muted mb-3" id="geofenceInstanceDetails">
            <div><strong>Jenis instansi:</strong> <span id="geofenceInfoType"><?= $e($selectedInstansi['jenis'] ?? '—') ?></span></div>
            <div><strong>Alamat lengkap:</strong> <span id="geofenceInfoFullAddress"><?= $e($selectedInstansi['alamat_lengkap'] ?? '—') ?></span></div>
            <div><strong>Koordinat:</strong> <span id="geofenceInfoFullCoordinates">
                <?php if ($selectedInstansi !== null && $selectedInstansi['latitude'] !== null && $selectedInstansi['longitude'] !== null): ?>
                    <?= $e($selectedInstansi['latitude']) ?>, <?= $e($selectedInstansi['longitude']) ?>
                <?php else: ?>
                    Belum tersedia
                <?php endif; ?>
            </span></div>
            <div><strong>Radius geofencing aktif:</strong> <span id="geofenceInfoFullRadius">
                <?= $selectedInstansi !== null && (float) ($selectedInstansi['radius_geofence_meter'] ?? 0) > 0
                    ? $e($selectedInstansi['radius_geofence_meter']) . ' Meter'
                    : 'Belum diatur' ?>
            </span></div>
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap text-muted" style="font-size: 0.78rem;">
            <div id="geofenceStudentStatus" aria-live="polite">
                <?php if ($selectedInstansi === null || !$selectedInstansi['student_location_available']): ?>
                    Data lokasi siswa belum tersedia.
                <?php elseif ($selectedInstansi['inside_count'] === null || $selectedInstansi['outside_count'] === null): ?>
                    Status lokasi siswa belum dapat dihitung karena koordinat atau radius geofencing belum tersedia.
                <?php else: ?>
                    <span class="badge badge-pill badge-primary mr-1">&nbsp;</span>
                    <strong id="geofenceInsideCount"><?= $e($selectedInstansi['inside_count']) ?> Siswa</strong> di dalam zona
                    <span class="badge badge-pill badge-danger ml-3 mr-1">&nbsp;</span>
                    <strong id="geofenceOutsideCount"><?= $e($selectedInstansi['outside_count']) ?> Siswa</strong> di luar zona
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="card dashboard-table-card shadow-sm mb-4" aria-labelledby="attendanceTitle">
    <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
        <div class="mb-2 mb-md-0">
            <h2 class="dashboard-table-title mb-1" id="attendanceTitle">Presensi Harian</h2>
            <div class="dashboard-date-label">
                <i class="far fa-calendar-alt text-info mr-1" aria-hidden="true"></i>
                <time id="attendanceDate">Memuat tanggal...</time>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap">
            <div class="input-group input-group-sm dashboard-filter-input mr-sm-2 mb-2 mb-sm-0">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-search text-gray-500" aria-hidden="true"></i>
                    </span>
                </div>
                <input class="form-control" id="attendanceSearch" type="search"
                    placeholder="Cari nama, NIS, instansi..." aria-label="Cari presensi harian">
            </div>

            <select class="custom-select custom-select-sm dashboard-filter-status"
                id="attendanceStatusFilter" aria-label="Filter status presensi">
                <option value="">Semua Status</option>
                <option value="Hadir">Hadir</option>
                <option value="Izin">Izin</option>
                <option value="Sakit">Sakit</option>
                <option value="Tidak Hadir">Tidak Hadir</option>
                <option value="Belum Presensi">Belum Presensi</option>
            </select>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table dashboard-attendance-table mb-0" id="attendanceTable">
                <thead>
                    <tr>
                        <th scope="col" class="text-center">No</th>
                        <th scope="col">Nama Siswa</th>
                        <th scope="col">NIS</th>
                        <th scope="col">Instansi</th>
                        <th scope="col">Jam Masuk</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <tr class="dashboard-no-results" id="attendanceNoResults" style="display: table-row;">
                        <td colspan="6" class="py-4 text-center text-muted">Memuat data presensi...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white py-2">
        <small class="text-muted" id="attendanceResultCount">
            Memuat data presensi...
        </small>
    </div>
    <div class="card-footer bg-white py-2 text-danger" id="monitoringError" role="alert" hidden></div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    (() => {
            const selector = document.getElementById('geofenceInstanceSelect');
            const mapElement = document.getElementById('geofenceMap');
            const mapMessage = document.getElementById('geofenceMapMessage');
            const infoCard = document.getElementById('geofenceInfoCard');
            const radiusBadge = document.getElementById('geofenceRadiusBadge');
            const studentStatus = document.getElementById('geofenceStudentStatus');
            const endpoint = <?= json_encode($geofenceEndpoint, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const initialInstance = <?= json_encode(
                $selectedInstansi,
                JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
            ) ?>;
            let map = null;
            let marker = null;
            let radiusCircle = null;
            let requestNumber = 0;

            const setText = (id, value) => {
                document.getElementById(id).textContent = value;
            };

            const formatCoordinate = (value) => value === null || value === '' ? null : String(value);

            const popupContent = (instance, latitude, longitude, radius) => {
                const container = document.createElement('div');
                container.className = 'geofence-popup';
                [
                    ['Nama instansi', instance.nama],
                    ['Alamat', instance.alamat_lengkap],
                    ['Latitude', latitude],
                    ['Longitude', longitude],
                    ['Radius', radius > 0 ? `${radius} Meter` : 'Belum diatur']
                ].forEach(([label, value]) => {
                    const row = document.createElement('div');
                    const strong = document.createElement('strong');
                    strong.textContent = `${label}: `;
                    row.append(strong, document.createTextNode(value || '—'));
                    container.appendChild(row);
                });
                return container;
            };

            const updateMap = (instance) => {
                const latitude = Number(instance.latitude);
                const longitude = Number(instance.longitude);
                const hasCoordinates = instance.latitude !== null
                    && instance.longitude !== null
                    && Number.isFinite(latitude)
                    && Number.isFinite(longitude)
                    && latitude >= -90 && latitude <= 90
                    && longitude >= -180 && longitude <= 180;

                infoCard.hidden = false;
                mapMessage.hidden = hasCoordinates;
                mapMessage.textContent = hasCoordinates
                    ? ''
                    : 'Koordinat lokasi instansi belum tersedia.';

                if (!hasCoordinates) {
                    if (map !== null) {
                        map.remove();
                        map = null;
                        marker = null;
                        radiusCircle = null;
                    }
                    return;
                }

                if (typeof L === 'undefined') {
                    mapMessage.hidden = false;
                    mapMessage.textContent = 'Peta tidak dapat dimuat. Periksa koneksi internet.';
                    return;
                }

                if (map === null) {
                    map = L.map(mapElement);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19
                    }).addTo(map);
                }

                const radius = Number(instance.radius_geofence_meter) || 0;
                if (marker !== null) {
                    map.removeLayer(marker);
                }
                if (radiusCircle !== null) {
                    map.removeLayer(radiusCircle);
                    radiusCircle = null;
                }

                marker = L.marker([latitude, longitude])
                    .addTo(map)
                    .bindPopup(popupContent(instance, formatCoordinate(instance.latitude), formatCoordinate(instance.longitude), radius));
                map.setView([latitude, longitude], 16);
                if (radius > 0) {
                    radiusCircle = L.circle([latitude, longitude], {
                        radius,
                        color: '#2875bd',
                        fillColor: '#2875bd',
                        fillOpacity: 0.12
                    }).addTo(map);
                    map.fitBounds(radiusCircle.getBounds(), {padding: [24, 24]});
                }
                window.setTimeout(() => map.invalidateSize(), 0);
            };

            const updateInformation = (instance) => {
                setText('geofenceInfoName', instance.nama || '—');
                setText('geofenceInfoAddress', instance.alamat_lengkap || '—');
                setText(
                    'geofenceInfoCoordinates',
                    instance.latitude !== null && instance.longitude !== null
                        ? `Koordinat: ${instance.latitude}, ${instance.longitude}`
                        : 'Koordinat belum tersedia'
                );
                const radius = Number(instance.radius_geofence_meter) || 0;
                const radiusText = radius > 0 ? `${radius} Meter` : 'Belum diatur';
                setText('geofenceInfoRadius', radius > 0 ? `Radius geofencing: ${radiusText}` : 'Radius geofencing belum diatur');
                setText('geofenceInfoType', instance.jenis || '—');
                setText('geofenceInfoFullAddress', instance.alamat_lengkap || '—');
                setText(
                    'geofenceInfoFullCoordinates',
                    instance.latitude !== null && instance.longitude !== null
                        ? `${instance.latitude}, ${instance.longitude}`
                        : 'Belum tersedia'
                );
                setText('geofenceInfoFullRadius', radiusText);
                radiusBadge.textContent = radius > 0
                    ? `Radius Aktif: ${radius} Meter`
                    : 'Radius geofencing belum diatur';

                if (!instance.student_location_available) {
                    studentStatus.textContent = 'Data lokasi siswa belum tersedia.';
                } else if (instance.inside_count === null || instance.outside_count === null) {
                    studentStatus.textContent = 'Status lokasi siswa belum dapat dihitung karena koordinat atau radius geofencing belum tersedia.';
                } else {
                    studentStatus.replaceChildren();
                    const insideBadge = document.createElement('span');
                    insideBadge.className = 'badge badge-pill badge-primary mr-1';
                    insideBadge.textContent = ' ';
                    const inside = document.createElement('strong');
                    inside.textContent = `${instance.inside_count} Siswa`;
                    const outsideBadge = document.createElement('span');
                    outsideBadge.className = 'badge badge-pill badge-danger ml-3 mr-1';
                    outsideBadge.textContent = ' ';
                    const outside = document.createElement('strong');
                    outside.textContent = `${instance.outside_count} Siswa`;
                    studentStatus.append(
                        insideBadge,
                        inside,
                        document.createTextNode(' di dalam zona '),
                        outsideBadge,
                        outside,
                        document.createTextNode(' di luar zona')
                    );
                }
                updateMap(instance);
            };

            if (initialInstance !== null) {
                updateInformation(initialInstance);
            }

            if (selector !== null) {
                selector.addEventListener('change', async () => {
                    if (selector.value === '') {
                        return;
                    }
                    const currentRequest = ++requestNumber;
                    try {
                        const response = await fetch(`${endpoint}&id=${encodeURIComponent(selector.value)}`, {
                            headers: {Accept: 'application/json'},
                            credentials: 'same-origin'
                        });
                        const payload = await response.json();
                        if (!response.ok || !payload.data) {
                            throw new Error(payload.error || 'Data instansi tidak dapat dimuat.');
                        }
                        if (currentRequest !== requestNumber) {
                            return;
                        }
                        updateInformation(payload.data);
                    } catch (error) {
                        if (currentRequest !== requestNumber) {
                            return;
                        }
                        mapMessage.hidden = false;
                        mapMessage.textContent = error.message || 'Data instansi tidak dapat dimuat.';
                    }
                });
            }
    })();

    (() => {
        const endpoint = <?= json_encode($monitoringEndpoint, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const searchInput = document.getElementById('attendanceSearch');
        const statusFilter = document.getElementById('attendanceStatusFilter');
        const tableBody = document.getElementById('attendanceTableBody');
        const noResultsRow = document.getElementById('attendanceNoResults');
        const resultCount = document.getElementById('attendanceResultCount');
        const dateElement = document.getElementById('attendanceDate');
        const errorElement = document.getElementById('monitoringError');
        const statisticElements = Array.from(document.querySelectorAll('.dashboard-stat-value'));
        const badgeClasses = {
            Hadir: 'badge-success',
            Izin: 'badge-warning',
            Sakit: 'badge-info',
            'Tidak Hadir': 'badge-danger',
            'Belum Presensi': 'badge-secondary'
        };
        let rows = [];
        let loaded = false;
        let fetching = false;

        const filterAttendance = () => {
            const query = searchInput.value.trim().toLocaleLowerCase('id-ID');
            const selectedStatus = statusFilter.value;
            const tableRows = Array.from(tableBody.querySelectorAll('tr[data-status]'));
            let visibleCount = 0;

            tableRows.forEach((row) => {
                const matchesQuery = row.dataset.search.includes(query);
                const matchesStatus = selectedStatus === '' || row.dataset.status === selectedStatus;
                row.hidden = !(matchesQuery && matchesStatus);
                if (!row.hidden) {
                    visibleCount += 1;
                    row.cells[0].textContent = String(visibleCount);
                }
            });

            noResultsRow.style.display = visibleCount === 0 ? 'table-row' : 'none';
            noResultsRow.querySelector('td').textContent = rows.length === 0
                ? 'Belum ada data presensi untuk hari ini.'
                : 'Tidak ada data presensi yang sesuai.';
            resultCount.textContent = `Menampilkan ${visibleCount} dari ${rows.length} data`;
        };

        const renderRows = () => {
            tableBody.querySelectorAll('tr[data-status]').forEach((row) => row.remove());

            rows.forEach((record, index) => {
                const row = document.createElement('tr');
                row.dataset.status = record.status;
                row.dataset.search = [record.name, record.nis, record.company]
                    .join(' ')
                    .toLocaleLowerCase('id-ID');

                const cells = [
                    {value: String(index + 1), className: 'text-center'},
                    {value: record.name, className: 'dashboard-student-name'},
                    {value: record.nis || '—', className: ''},
                    {value: record.company, className: 'dashboard-company-name'},
                    {value: record.time || '—', className: ''}
                ];

                cells.forEach(({value, className}) => {
                    const cell = document.createElement('td');
                    cell.textContent = value;
                    if (className !== '') {
                        cell.className = className;
                    }
                    row.appendChild(cell);
                });

                const statusCell = document.createElement('td');
                const badge = document.createElement('span');
                badge.className = `badge ${badgeClasses[record.status] || 'badge-secondary'}`;
                badge.textContent = record.status;
                statusCell.appendChild(badge);
                row.appendChild(statusCell);
                tableBody.insertBefore(row, noResultsRow);
            });

            filterAttendance();
        };

        const updateDate = (date) => {
            const parsedDate = new Date(`${date}T00:00:00+07:00`);
            dateElement.dateTime = date;
            dateElement.textContent = new Intl.DateTimeFormat('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                timeZone: 'Asia/Jakarta'
            }).format(parsedDate);
        };

        const loadMonitoringData = async () => {
            if (fetching || document.hidden) {
                return;
            }
            fetching = true;

            try {
                const response = await fetch(endpoint, {
                    headers: {Accept: 'application/json'},
                    credentials: 'same-origin',
                    cache: 'no-store'
                });
                const payload = await response.json();
                if (
                    !response.ok
                    || payload.success !== true
                    || !payload.data
                    || !Array.isArray(payload.data.rows)
                    || !payload.data.statistics
                ) {
                    throw new Error(payload.message || 'Data monitoring tidak dapat dimuat.');
                }

                const nextRows = payload.data.rows.map((record) => {
                    if (
                        !record
                        || typeof record.name !== 'string'
                        || typeof record.nis !== 'string'
                        || typeof record.company !== 'string'
                        || typeof record.time !== 'string'
                        || !Object.prototype.hasOwnProperty.call(badgeClasses, record.status)
                    ) {
                        throw new Error('Data presensi monitoring tidak valid.');
                    }

                    return {
                        name: record.name,
                        nis: record.nis,
                        company: record.company,
                        time: record.time,
                        status: record.status
                    };
                });
                const statistics = payload.data.statistics;
                const values = [
                    statistics.total_siswa,
                    statistics.hadir,
                    statistics.izin,
                    statistics.tidak_hadir
                ];

                if (
                    values.length !== statisticElements.length
                    || values.some((value) => typeof value !== 'number' || !Number.isInteger(value) || value < 0)
                    || typeof payload.data.tanggal !== 'string'
                    || !/^\d{4}-\d{2}-\d{2}$/.test(payload.data.tanggal)
                ) {
                    throw new Error('Data statistik monitoring tidak valid.');
                }

                rows = nextRows;
                values.forEach((value, index) => {
                    statisticElements[index].textContent = new Intl.NumberFormat('id-ID').format(value);
                });
                updateDate(payload.data.tanggal);
                renderRows();
                errorElement.hidden = true;
                loaded = true;
            } catch (error) {
                errorElement.textContent = error.message || 'Data monitoring tidak dapat dimuat.';
                errorElement.hidden = false;
                if (!loaded) {
                    noResultsRow.querySelector('td').textContent = 'Data presensi tidak dapat dimuat.';
                    resultCount.textContent = 'Data monitoring tidak tersedia';
                }
            } finally {
                fetching = false;
            }
        };

        searchInput.addEventListener('input', filterAttendance);
        statusFilter.addEventListener('change', filterAttendance);
        document.addEventListener('visibilitychange', loadMonitoringData);
        window.setInterval(loadMonitoringData, 30000);
        loadMonitoringData();
    })();
</script>
<?php
$content = ob_get_clean();

require dirname(__DIR__) . '/layouts/admin.php';
