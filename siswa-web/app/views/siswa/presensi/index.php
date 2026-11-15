<?php
$presensiError = $presensiError ?? null;
$instansi = $presensiData['instansi'] ?? [];
$sessions = $presensiData['sesi'] ?? [];
$hasGeofence = ($instansi['geofence_configured'] ?? false) === true;
$formatTime = static function ($value): string {
    if (! is_string($value)) {
        return '--:--';
    }

    return substr($value, 10, 1) === 'T' || substr($value, 10, 1) === ' '
        ? substr($value, 11, 5)
        : substr($value, 0, 5);
};
$geofence = [
    'configured' => $hasGeofence,
    'latitude' => $hasGeofence ? (float) $instansi['latitude'] : null,
    'longitude' => $hasGeofence ? (float) $instansi['longitude'] : null,
    'radius' => $hasGeofence ? (int) $instansi['radius_meters'] : null,
];
?>
<section class="activity-layout" id="view-schedule-list">
    <div class="activity-card">
        <div class="activity-heading">
            <h2>Jadwal Presensi <?= e($presensiData['tanggal'] ?? date('Y-m-d')) ?></h2>
            <p>Jadwal dibuat oleh Guru pembimbing. Pastikan memilih sesi yang sesuai.</p>
        </div>

        <?php if ($presensiError !== null): ?>
            <div class="alert alert-danger"><?= e($presensiError) ?></div>
        <?php endif; ?>

        <div class="activity-fields" id="schedule-container">
            <?php foreach ($sessions as $session): ?>
                <?php
                $sessionOpen = $session['status'] === 'dibuka';
                ?>
                <div class="activity-field activity-field-wide">
                    <div class="activity-record">
                        <div>
                            <strong><?= e($session['nama_shift']) ?></strong>
                            <p>
                                Datang <?= e($formatTime($session['jam_masuk_mulai'])) ?>
                                - <?= e($formatTime($session['jam_masuk_selesai'])) ?>
                                &middot;
                                Pulang <?= e($formatTime($session['jam_pulang_mulai'])) ?>
                                - <?= e($formatTime($session['jam_pulang_selesai'])) ?>
                            </p>
                            <p>
                                <?= $sessionOpen ? 'Sesi dibuka' : 'Sesi ditutup' ?>
                                <?php if ($session['waktu_masuk'] !== null): ?>
                                    &middot; Datang tercatat <?= e($formatTime($session['waktu_masuk'])) ?>
                                <?php endif; ?>
                                <?php if ($session['waktu_pulang'] !== null): ?>
                                    &middot; Pulang tercatat <?= e($formatTime($session['waktu_pulang'])) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <button class="btn btn-primary" type="button"
                                onclick="openPresensiForm('datang', <?= (int) $session['id'] ?>)"
                                <?= ! $hasGeofence || ! $sessionOpen || $session['waktu_masuk'] !== null ? 'disabled' : '' ?>>
                                Presensi datang
                            </button>
                            <button class="btn btn-primary" type="button"
                                onclick="openPresensiForm('pulang', <?= (int) $session['id'] ?>)"
                                <?= ! $hasGeofence || ! $sessionOpen || $session['waktu_pulang'] !== null ? 'disabled' : '' ?>>
                                Presensi pulang
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($sessions) === 0): ?>
            <div id="empty-schedule-text" class="empty">
                <h3>Belum Ada Jadwal Presensi</h3>
                <p>Jadwal sesi presensi hari ini belum tersedia dari Guru pembimbing.</p>
            </div>
        <?php endif; ?>
    </div>

    <aside class="activity-card activity-status">
        <div class="activity-heading">
            <h2>Lokasi Instansi</h2>
            <p>Koordinat dan radius geofencing dikelola Admin.</p>
        </div>
        <?php if ($instansi !== []): ?>
            <div class="activity-record">
                <div>
                    <strong><?= e($instansi['nama'] ?? 'Instansi') ?></strong>
                    <p><?= e($instansi['alamat'] ?? 'Alamat instansi tidak tersedia.') ?></p>
                    <?php if ($hasGeofence): ?>
                        <p>Koordinat: <?= e($instansi['latitude']) ?>, <?= e($instansi['longitude']) ?></p>
                        <p>Radius maksimal: <?= e($instansi['radius_meters']) ?> meter</p>
                    <?php else: ?>
                        <p class="text-danger">Koordinat atau radius geofencing belum diatur Admin.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="activity-record">
                <p>Informasi lokasi instansi tidak tersedia karena penempatan aktif tidak ditemukan.</p>
            </div>
        <?php endif; ?>
    </aside>
</section>

<section class="activity-layout" id="view-presensi-form" style="display: none;">
    <div class="activity-card">
        <div class="activity-heading" style="text-align: center; margin-bottom: 24px;">
            <div style="font-size: 32px; font-weight: 800; letter-spacing: -.02em; color: var(--text);" id="clock-display"></div>
            <p id="form-date-text" style="margin-top: 4px;"><?= e($presensiData['tanggal'] ?? '') ?></p>
        </div>
    </div>

        <div class="activity-fields">
            <div class="activity-field activity-field-wide" id="gps-status-box">
                <div class="alert alert-info" id="gps-alert-box">
                    <span>📍</span>
                    <span id="gps-status-text">Memeriksa lokasi GPS...</span>
                </div>
            </div>

            <div class="activity-field activity-field-wide" id="previous-checkin-box" style="display: none;">
                <div class="activity-record">
                    <div>
                        <strong>Check In Sebelumnya</strong>
                        <p id="prev-checkin-time"></p>
                    </div>
                </div>
            </div>

            <div class="activity-field activity-field-wide">
                <label>Titik Lokasi Anda dan Instansi</label>
                <div id="map" style="width: 100%; height: 200px; border-radius: 12px; background: #e2e8f0; border: 1.5px solid var(--line);"></div>
            </div>

            <input type="hidden" id="csrf-token" value="<?= e($csrfToken ?? '') ?>">

            <div class="activity-field activity-field-wide" style="margin-top: 8px;">
                <button class="btn btn-primary btn-block" id="btn-submit-presensi" type="button" onclick="submitPresensi()" disabled>
                    <span id="btn-text">Kirim Presensi</span>
                </button>
            </div>
        </div>
    </div>

    <aside class="activity-card activity-status">
        <div class="activity-heading">
            <h2>Ketentuan Presensi</h2>
            <p>Lokasi siswa dan jadwal divalidasi kembali oleh server.</p>
        </div>
        <div class="activity-record" style="margin-bottom: 14px;">
            <div>
                <strong><?= e($instansi['nama'] ?? 'Instansi') ?></strong>
                <?php if ($hasGeofence): ?>
                    <p>Presensi harus berada dalam radius <?= e($instansi['radius_meters']) ?> meter dari titik yang ditetapkan Admin.</p>
                <?php else: ?>
                    <p>Data koordinat atau radius Admin belum tersedia.</p>
                <?php endif; ?>
            </div>
        </div>
        <button class="btn btn-outline btn-block" type="button" onclick="backToSchedule()">
            Kembali ke Daftar Jadwal
        </button>
    </aside>
</section>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const PRESENSI_STORE_URL = <?= json_encode(url('presensi/store'), JSON_UNESCAPED_SLASHES) ?>;
    const GEOFENCE = <?= json_encode($geofence, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    let currentType = 'datang';
    let currentSessionId = null;
    let userLat = null;
    let userLng = null;
    let map = null;
    let marker = null;
    let targetMarker = null;
    let radiusCircle = null;

    function updateClock() {
        const clockEl = document.getElementById('clock-display');
        if (clockEl) {
            clockEl.innerText = new Date().toLocaleTimeString('id-ID', {
                hour12: false,
                timeZone: 'Asia/Jakarta'
            }) + ' WIB';
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    function openPresensiForm(type, sessionId) {
        currentType = type;
        currentSessionId = sessionId;
        document.getElementById('view-schedule-list').style.display = 'none';
        document.getElementById('view-presensi-form').style.display = '';
        document.getElementById('btn-text').innerText = type === 'datang' ? 'Check In Datang' : 'Check Out Pulang';

        const session = <?= json_encode($sessions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const selectedSession = session.find(item => Number(item.id) === Number(sessionId));
        const previousBox = document.getElementById('previous-checkin-box');
        previousBox.style.display = type === 'pulang' && selectedSession && selectedSession.waktu_masuk ? 'block' : 'none';
        if (type === 'pulang' && selectedSession && selectedSession.waktu_masuk) {
            document.getElementById('prev-checkin-time').innerText = formatAttendanceTime(selectedSession.waktu_masuk);
        }

        initGPSLocation();
    }

    function backToSchedule() {
        document.getElementById('view-presensi-form').style.display = 'none';
        document.getElementById('view-schedule-list').style.display = '';
    }

    function initGPSLocation() {
        const statusText = document.getElementById('gps-status-text');
        const alertBox = document.getElementById('gps-alert-box');
        const submitBtn = document.getElementById('btn-submit-presensi');
        userLat = null;
        userLng = null;
        submitBtn.disabled = true;
        statusText.innerText = 'Memeriksa lokasi GPS...';
        alertBox.className = 'alert alert-info';

        if (!navigator.geolocation) {
            statusText.innerText = 'Browser Anda tidak mendukung fitur Geolocation GPS.';
            alertBox.className = 'alert alert-danger';
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;
                statusText.innerText = 'Lokasi terdeteksi. Radius akan divalidasi oleh server saat presensi dikirim.';
                alertBox.className = 'alert alert-success';
                submitBtn.disabled = false;
                renderMap(userLat, userLng);
            },
            (error) => {
                statusText.innerText = geolocationError(error);
                alertBox.className = 'alert alert-danger';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    function geolocationError(error) {
        if (error.code === error.PERMISSION_DENIED) {
            return 'Izin lokasi ditolak. Aktifkan izin lokasi browser Anda.';
        }
        if (error.code === error.POSITION_UNAVAILABLE) {
            return 'Sinyal GPS tidak tersedia.';
        }
        if (error.code === error.TIMEOUT) {
            return 'Waktu pengambilan lokasi habis. Silakan coba lagi.';
        }
        return 'Gagal mengakses GPS.';
    }

    function formatAttendanceTime(value) {
        return value && value.length > 10 ? value.substring(11, 16) : value;
    }

    function renderMap(lat, lng) {
        if (!map) {
            map = L.map('map');
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);
        }

        if (!marker) {
            marker = L.marker([lat, lng]).bindPopup('Lokasi GPS Anda').addTo(map);
        } else {
            marker.setLatLng([lat, lng]);
        }

        const mapBounds = [marker.getLatLng()];
        if (GEOFENCE.configured) {
            const target = [GEOFENCE.latitude, GEOFENCE.longitude];
            if (!targetMarker) {
                targetMarker = L.marker(target).bindPopup('Lokasi instansi dari Admin').addTo(map);
                radiusCircle = L.circle(target, {
                    radius: GEOFENCE.radius,
                    color: '#2563eb',
                    fillOpacity: 0.12
                }).addTo(map);
            } else {
                targetMarker.setLatLng(target);
                radiusCircle.setLatLng(target);
                radiusCircle.setRadius(GEOFENCE.radius);
            }
            mapBounds.push(targetMarker.getLatLng());
            mapBounds.push(radiusCircle.getBounds().getNorthEast());
            mapBounds.push(radiusCircle.getBounds().getSouthWest());
        }

        map.fitBounds(L.latLngBounds(mapBounds), { padding: [20, 20], maxZoom: 16 });
        setTimeout(() => map.invalidateSize(), 200);
    }

    function submitPresensi() {
        const submitBtn = document.getElementById('btn-submit-presensi');
        const statusText = document.getElementById('gps-status-text');
        const alertBox = document.getElementById('gps-alert-box');

        if (!navigator.geolocation) {
            statusText.innerText = 'Browser Anda tidak mendukung fitur Geolocation GPS.';
            alertBox.className = 'alert alert-danger';
            return;
        }

        submitBtn.disabled = true;
        statusText.innerText = 'Mengambil lokasi terbaru untuk presensi...';
        alertBox.className = 'alert alert-info';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;
                renderMap(userLat, userLng);
                statusText.innerText = 'Lokasi terdeteksi. Mengirim koordinat untuk validasi server...';
                sendPresensi(submitBtn);
            },
            (error) => {
                statusText.innerText = geolocationError(error);
                alertBox.className = 'alert alert-danger';
                submitBtn.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    function sendPresensi(submitBtn) {
        const payload = {
            session_id: currentSessionId,
            type: currentType,
            latitude: userLat,
            longitude: userLng,
            csrf_token: document.getElementById('csrf-token').value
        };

        fetch(PRESENSI_STORE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok && data.status !== 'error') {
                    throw new Error(data.message || 'Server gagal memproses presensi.');
                }
                return data;
            })
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message || 'Presensi berhasil disimpan!');
                    window.location.reload();
                } else {
                    alert('Gagal presensi: ' + data.message);
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                alert(error.message || 'Koneksi ke server gagal. Silakan coba lagi.');
                submitBtn.disabled = false;
            });
    }
</script>