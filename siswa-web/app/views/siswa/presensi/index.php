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
                                <?= ! $hasGeofence || ! $sessionOpen || $session['waktu_masuk'] === null || $session['waktu_pulang'] !== null ? 'disabled' : '' ?>>
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
                <div class="alert alert-danger" id="map-status" role="status" aria-live="polite" style="display: none; margin-top: 8px;"></div>
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

<link id="leaflet-css" rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<script>
    const PRESENSI_STORE_URL = <?= json_encode(url('presensi/store'), JSON_UNESCAPED_SLASHES) ?>;
    const GEOFENCE = <?= json_encode($geofence, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    let currentType = 'datang';
    let currentSessionId = null;
    let userLat = null;
    let userLng = null;
    let map = null;
    let marker = null;
    let radiusCircle = null;
    let tileLayer = null;
    let leafletPromise = null;
    let submitInProgress = false;

    document.getElementById('leaflet-css').addEventListener('error', function () {
        if (this.dataset.fallbackLoaded !== 'true') {
            this.dataset.fallbackLoaded = 'true';
            this.href = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css';
            return;
        }

        showMapStatus('Gaya peta gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.');
    });

    function setGPSStatus(message, type) {
        document.getElementById('gps-status-text').innerText = message;
        document.getElementById('gps-alert-box').className = 'alert alert-' + type;
    }

    function showMapStatus(message) {
        const status = document.getElementById('map-status');
        status.innerText = message;
        status.style.display = '';
    }

    function validCoordinates(latitude, longitude) {
        return typeof latitude === 'number'
            && Number.isFinite(latitude)
            && latitude >= -90
            && latitude <= 90
            && typeof longitude === 'number'
            && Number.isFinite(longitude)
            && longitude >= -180
            && longitude <= 180;
    }

    function clearUserMarker() {
        if (!map || !marker) {
            return;
        }

        map.removeLayer(marker);
        marker = null;
        if (radiusCircle) {
            map.fitBounds(radiusCircle.getBounds(), { padding: [20, 20], maxZoom: 16 });
        }
    }

    function currentPosition(timeoutMs) {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject({ code: 0 });
                return;
            }

            if (window.isSecureContext === false) {
                reject({ code: 1, insecureContext: true });
                return;
            }

            let settled = false;
            const timeoutId = setTimeout(() => {
                if (!settled) {
                    settled = true;
                    reject({ code: 3 });
                }
            }, timeoutMs);
            const finish = (callback, value) => {
                if (settled) {
                    return;
                }
                settled = true;
                clearTimeout(timeoutId);
                callback(value);
            };

            try {
                navigator.geolocation.getCurrentPosition(
                    position => finish(resolve, position),
                    error => finish(reject, error),
                    { enableHighAccuracy: true, timeout: timeoutMs, maximumAge: 0 }
                );
            } catch (error) {
                finish(reject, error);
            }
        });
    }

    function loadLeafletScript(source) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            const timeoutId = setTimeout(() => {
                script.remove();
                reject(new Error('Waktu pemuatan library peta habis.'));
            }, 8000);

            script.onload = () => {
                clearTimeout(timeoutId);
                resolve();
            };
            script.onerror = () => {
                clearTimeout(timeoutId);
                script.remove();
                reject(new Error('Library peta gagal dimuat.'));
            };
            script.src = source;
            document.head.appendChild(script);
        });
    }

    function ensureLeaflet() {
        if (window.L) {
            return Promise.resolve();
        }

        if (!leafletPromise) {
            leafletPromise = (async () => {
                const sources = [
                    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                    'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js'
                ];
                let lastError = null;

                for (const source of sources) {
                    try {
                        await loadLeafletScript(source);
                        if (window.L) {
                            return;
                        }
                        lastError = new Error('Library peta tidak dapat digunakan.');
                    } catch (error) {
                        lastError = error;
                    }
                }

                throw lastError || new Error('Library peta gagal dimuat.');
            })().catch(error => {
                leafletPromise = null;
                throw error;
            });
        }

        return leafletPromise;
    }

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

        const sessions = <?= json_encode($sessions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const selectedSession = sessions.find(item => Number(item.id) === Number(sessionId));
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

    async function initGPSLocation() {
        const submitBtn = document.getElementById('btn-submit-presensi');
        userLat = null;
        userLng = null;
        clearUserMarker();
        submitBtn.disabled = true;
        setGPSStatus('Memeriksa lokasi GPS...', 'info');

        try {
            const position = await currentPosition(12000);
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;

            if (!validCoordinates(latitude, longitude)) {
                throw new Error('Koordinat GPS tidak valid. Coba ambil lokasi kembali.');
            }

            userLat = latitude;
            userLng = longitude;
            submitBtn.disabled = false;
            setGPSStatus('Lokasi terdeteksi. Radius akan divalidasi oleh server saat presensi dikirim.', 'success');
            renderMap(userLat, userLng).catch(error => {
                showMapStatus(error.message || 'Peta gagal dimuat. Periksa koneksi internet.');
            });
        } catch (error) {
            userLat = null;
            userLng = null;
            setGPSStatus(geolocationError(error), 'danger');
        }
    }

    function geolocationError(error) {
        if (error && error.code === 1) {
            return error.insecureContext
                ? 'Lokasi hanya dapat diakses melalui HTTPS atau localhost.'
                : 'Izin lokasi ditolak. Aktifkan izin lokasi browser Anda.';
        }
        if (error && error.code === 2) {
            return 'Sinyal GPS tidak tersedia. Pastikan layanan lokasi perangkat aktif.';
        }
        if (error && error.code === 3) {
            return 'Waktu pengambilan lokasi habis. Periksa GPS dan izin lokasi, lalu coba lagi.';
        }
        return error && error.message
            ? error.message
            : 'Browser Anda tidak mendukung fitur Geolocation GPS.';
    }

    function formatAttendanceTime(value) {
        return value && value.length > 10 ? value.substring(11, 16) : value;
    }

    async function renderMap(lat, lng) {
        if (!validCoordinates(lat, lng)) {
            throw new Error('Koordinat peta tidak valid.');
        }

        if (!map) {
            await ensureLeaflet();
        }

        if (!map) {
            map = window.L.map('map', { zoomControl: true }).setView([lat, lng], 16);
            tileLayer = window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            tileLayer.on('tileerror', () => {
                showMapStatus('Tile peta gagal dimuat. Periksa koneksi internet Anda.');
            });
        }

        if (!marker) {
            marker = window.L.marker([lat, lng]).bindPopup('Lokasi GPS Anda').addTo(map);
        } else {
            marker.setLatLng([lat, lng]);
        }

        const mapBounds = [marker.getLatLng()];
        if (GEOFENCE.configured) {
            const target = [GEOFENCE.latitude, GEOFENCE.longitude];
            if (!validCoordinates(target[0], target[1]) || !Number.isFinite(GEOFENCE.radius) || GEOFENCE.radius <= 0) {
                throw new Error('Koordinat atau radius geofence instansi tidak valid.');
            }
            if (!radiusCircle) {
                radiusCircle = window.L.circle(target, {
                    radius: GEOFENCE.radius,
                    color: '#2563eb',
                    fillOpacity: 0.12
                }).addTo(map);
            } else {
                radiusCircle.setLatLng(target);
                radiusCircle.setRadius(GEOFENCE.radius);
            }
            mapBounds.push(radiusCircle.getBounds().getNorthEast());
            mapBounds.push(radiusCircle.getBounds().getSouthWest());
            map.fitBounds(window.L.latLngBounds(mapBounds), { padding: [20, 20], maxZoom: 16 });
        } else {
            map.setView([lat, lng], 16);
        }

        document.getElementById('map-status').style.display = 'none';
        requestAnimationFrame(() => map.invalidateSize({ pan: false }));
    }

    async function submitPresensi() {
        const submitBtn = document.getElementById('btn-submit-presensi');
        const buttonText = document.getElementById('btn-text');

        if (submitInProgress) {
            return;
        }

        submitInProgress = true;
        submitBtn.disabled = true;
        submitBtn.setAttribute('aria-busy', 'true');
        userLat = null;
        userLng = null;
        clearUserMarker();

        try {
            if (!currentSessionId || !['datang', 'pulang'].includes(currentType)) {
                throw new Error('Sesi atau jenis presensi tidak valid. Kembali ke jadwal dan coba lagi.');
            }

            setGPSStatus('Mengambil lokasi terbaru untuk presensi...', 'info');
            buttonText.innerText = 'Mengambil lokasi...';
            const position = await currentPosition(15000);
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;

            if (!validCoordinates(latitude, longitude)) {
                throw new Error('Koordinat GPS tidak valid. Presensi tidak dapat dikirim.');
            }

            userLat = latitude;
            userLng = longitude;
            try {
                await renderMap(latitude, longitude);
            } catch (error) {
                showMapStatus(error.message || 'Peta gagal dimuat. Periksa koneksi internet.');
            }
            setGPSStatus('Lokasi terdeteksi. Mengirim koordinat untuk validasi server...', 'success');
            buttonText.innerText = 'Mengirim presensi...';

            const result = await sendPresensi(latitude, longitude);
            alert(result.message || 'Presensi berhasil disimpan!');
            window.location.reload();
        } catch (error) {
            const message = error && typeof error.code === 'number'
                ? geolocationError(error)
                : error.message || 'Presensi gagal diproses. Silakan coba lagi.';
            setGPSStatus(message, 'danger');
        } finally {
            submitInProgress = false;
            submitBtn.disabled = false;
            submitBtn.removeAttribute('aria-busy');
            buttonText.innerText = currentType === 'datang' ? 'Check In Datang' : 'Check Out Pulang';
        }
    }

    async function sendPresensi(latitude, longitude) {
        if (!validCoordinates(latitude, longitude)) {
            throw new Error('Koordinat GPS tidak valid. Presensi tidak dapat dikirim.');
        }

        const payload = {
            session_id: currentSessionId,
            type: currentType,
            latitude,
            longitude,
            csrf_token: document.getElementById('csrf-token').value
        };

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000);

        try {
            const response = await fetch(PRESENSI_STORE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload),
                signal: controller.signal
            });

            let result;
            try {
                result = await response.json();
            } catch (error) {
                throw new Error('Respons server tidak valid. Silakan coba lagi.');
            }

            if (!response.ok || !result || result.status !== 'success') {
                throw new Error(result && result.message
                    ? result.message
                    : 'Server gagal memproses presensi.');
            }

            return result;
        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error('Waktu koneksi ke server habis. Silakan coba lagi.');
            }
            if (error instanceof TypeError) {
                throw new Error('Koneksi ke server gagal. Periksa jaringan internet lalu coba lagi.');
            }
            throw error;
        } finally {
            clearTimeout(timeoutId);
        }
    }
</script>