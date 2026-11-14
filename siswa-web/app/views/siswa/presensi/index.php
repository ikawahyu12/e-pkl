<!-- MODE 1: Tampilan Utama Pilih Jadwal Presensi -->
<section class="activity-layout" id="view-schedule-list">
    <div class="activity-card">
        <div class="activity-heading">
            <h2 id="current-date-text">Kamis, 12 Maret 2026</h2>
            <p>Pastikan Anda memilih waktu presensi yang sesuai sebelum melanjutkan.</p>
        </div>

        <div class="activity-fields" id="schedule-container">
            <!-- Kartu Presensi Datang -->
            <div class="activity-field activity-field-wide">
                <div class="activity-record">
                    <div>
                        <strong>Presensi Datang</strong>
                        <p>Lokasi instansi penempatan Anda</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="pill pill-info">08.00 - 08.30</span>
                        <button class="btn btn-primary" type="button" onclick="openPresensiForm('datang')">Presensi sekarang</button>
                    </div>
                </div>
            </div>

            <!-- Kartu Presensi Pulang -->
            <div class="activity-field activity-field-wide">
                <div class="activity-record">
                    <div>
                        <strong>Presensi Pulang</strong>
                        <p>Lokasi instansi penempatan Anda</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="pill pill-info">15.00 - 15.30</span>
                        <button class="btn btn-primary" type="button" onclick="openPresensiForm('pulang')">Presensi sekarang</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="empty-schedule-text" class="empty" style="display: none;">
            <h3>Tidak Ada Jadwal</h3>
            <p>Tidak Ada Presensi Yang berlangsung saat ini.</p>
        </div>
    </div>

    <aside class="activity-card activity-status">
        <div class="activity-heading">
            <h2>Status Kehadiran Hari Ini</h2>
            <p>Pantau status check-in dan check-out Anda.</p>
        </div>
        <div class="activity-record" style="margin-bottom: 12px;">
            <div>
                <strong>Check In Datang</strong>
                <p>Jadwal: 08.00 - 08.30</p>
            </div>
            <span class="pill pill-warn">Belum</span>
        </div>
        <div class="activity-record">
            <div>
                <strong>Check Out Pulang</strong>
                <p>Jadwal: 15.00 - 15.30</p>
            </div>
            <span class="pill pill-warn">Belum</span>
        </div>
    </aside>
</section>

<!-- MODE 2: Tampilan Form Action Presensi (Check In / Check Out) -->
<section class="activity-layout" id="view-presensi-form" style="display: none;">
    <div class="activity-card">
        <div class="activity-heading" style="text-align: center; margin-bottom: 24px;">
            <div style="font-size: 32px; font-weight: 800; letter-spacing: -.02em; color: var(--text);" id="clock-display">08:15:34 WIB</div>
            <p id="form-date-text" style="margin-top: 4px;">Kamis, 12 Maret 2026</p>
        </div>

        <div class="activity-fields">
            <!-- Status Terlambat (Conditional) -->
            <div class="activity-field activity-field-wide" id="status-terlambat-box" style="display: none;">
                <div class="alert alert-warning">
                    <span>&#9888;</span>
                    <div>
                        <strong>Status: Terlambat</strong>
                        <div id="late-desc" style="font-weight: normal; margin-top: 2px;">Tiba pukul 08:15 WIB — lebih dari 15 menit</div>
                    </div>
                </div>
            </div>

            <!-- Status Radius GPS -->
            <div class="activity-field activity-field-wide" id="gps-status-box">
                <div class="alert alert-info" id="gps-alert-box">
                    <span>📍</span>
                    <span id="gps-status-text">Memeriksa lokasi GPS...</span>
                </div>
            </div>

            <!-- Box Catatan Check-in Sebelum Check-out -->
            <div class="activity-field activity-field-wide" id="previous-checkin-box" style="display: none;">
                <div class="activity-record">
                    <div>
                        <strong>Check In Sebelumnya</strong>
                        <p id="prev-checkin-time">08:15 WIB</p>
                    </div>
                    <span class="pill pill-ok">Tepat waktu</span>
                </div>
            </div>

            <!-- Embed Map Leaflet -->
            <div class="activity-field activity-field-wide">
                <label>Titik Lokasi Anda</label>
                <div id="map" style="width: 100%; height: 200px; border-radius: 12px; background: #e2e8f0; border: 1.5px solid var(--line);"></div>
            </div>

            <input type="hidden" id="csrf-token" value="<?= e($csrfToken ?? '') ?>">

            <!-- Button Submit -->
            <div class="activity-field activity-field-wide" style="margin-top: 8px;">
                <button class="btn btn-primary btn-block" id="btn-submit-presensi" type="button" onclick="submitPresensi()" disabled>
                    <span id="btn-icon">&#10003;</span>
                    <span id="btn-text">Check In Datang</span>
                </button>
            </div>
        </div>
    </div>

    <aside class="activity-card activity-status">
        <div class="activity-heading">
            <h2>Panduan Presensi</h2>
            <p>Pastikan GPS aktif di perangkat Anda.</p>
        </div>
        <div class="activity-record" style="margin-bottom: 14px;">
            <div>
                <strong>Ketentuan Radius</strong>
                <p>Maksimal berjarak 100 meter dari titik koordinat mitra.</p>
            </div>
        </div>
        <button class="btn btn-outline btn-block" type="button" onclick="backToSchedule()">
            Kembali ke Daftar Jadwal
        </button>
    </aside>
</section>

<!-- Leaflet CSS & JS untuk Peta -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const PRESENSI_STORE_URL = <?= json_encode(url('presensi/store'), JSON_UNESCAPED_SLASHES) ?>;

    let currentType = 'datang';
    let userLat = null;
    let userLng = null;
    let map = null;
    let marker = null;

    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', {
            hour12: false
        }) + ' WIB';
        const clockEl = document.getElementById('clock-display');
        if (clockEl) clockEl.innerText = timeString;
    }
    setInterval(updateClock, 1000);
    updateClock();

    function openPresensiForm(type) {
        currentType = type;
        document.getElementById('view-schedule-list').style.display = 'none';
        document.getElementById('view-presensi-form').style.display = '';

        const btnText = document.getElementById('btn-text');
        const prevBox = document.getElementById('previous-checkin-box');
        const lateBox = document.getElementById('status-terlambat-box');

        if (type === 'datang') {
            btnText.innerText = 'Check In Datang';
            prevBox.style.display = 'none';
            lateBox.style.display = 'block';
        } else {
            btnText.innerText = 'Check Out Pulang';
            prevBox.style.display = 'block';
            lateBox.style.display = 'none';
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
                let errMsg = 'Gagal mengakses GPS.';
                if (error.code === error.PERMISSION_DENIED) {
                    errMsg = 'Izin lokasi ditolak. Aktifkan izin lokasi browser Anda.';
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    errMsg = 'Sinyal GPS tidak tersedia.';
                } else if (error.code === error.TIMEOUT) {
                    errMsg = 'Waktu pengambilan lokasi habis. Silakan coba lagi.';
                }
                statusText.innerText = errMsg;
                alertBox.className = 'alert alert-danger';
                submitBtn.disabled = true;
            }, {
                enableHighAccuracy: true,
                timeout: 10000
            }
        );
    }

    function renderMap(lat, lng) {
        if (!map) {
            map = L.map('map').setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);
            marker = L.marker([lat, lng]).addTo(map);
        } else {
            map.setView([lat, lng], 16);
            marker.setLatLng([lat, lng]);
        }
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
        submitBtn.innerText = 'Mengambil lokasi GPS...';
        statusText.innerText = 'Mengambil lokasi terbaru untuk presensi...';
        alertBox.className = 'alert alert-info';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;
                renderMap(userLat, userLng);
                statusText.innerText = 'Lokasi terdeteksi. Mengirim koordinat untuk validasi radius...';
                sendPresensi(submitBtn);
            },
            (error) => {
                let errMsg = 'Gagal mengakses GPS.';
                if (error.code === error.PERMISSION_DENIED) {
                    errMsg = 'Izin lokasi ditolak. Aktifkan izin lokasi browser Anda.';
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    errMsg = 'Sinyal GPS tidak tersedia.';
                } else if (error.code === error.TIMEOUT) {
                    errMsg = 'Waktu pengambilan lokasi habis. Silakan coba lagi.';
                }
                statusText.innerText = errMsg;
                alertBox.className = 'alert alert-danger';
                submitBtn.disabled = false;
                submitBtn.innerText = currentType === 'datang' ? 'Check In Datang' : 'Check Out Pulang';
            }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    }

    function sendPresensi(submitBtn) {
        const payload = {
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
                    submitBtn.innerText = currentType === 'datang' ? 'Check In Datang' : 'Check Out Pulang';
                }
            })
            .catch(error => {
                alert(error.message || 'Koneksi ke server gagal. Silakan coba lagi.');
                submitBtn.disabled = false;
                submitBtn.innerText = currentType === 'datang' ? 'Check In Datang' : 'Check Out Pulang';
            });
    }
</script>