<section class="attendance-page">
    <style>
        @import url('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');

        .attendance-page {
            --primary-blue: #0d87f2;
            --bg-light: #f4f8fb;
            --card-border: #d2e4f7;
            --text-dark: #1e293b;
            --warning-bg: #fffbeb;
            --warning-border: #fcd34d;
            --warning-text: #b45309;
            --success-bg: #f0fdf4;
            --success-border: #86efac;
            --success-text: #15803d;
        }

        .attendance-page,
        .attendance-page * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
        }

        .attendance-page {
            width: 100%;
            color: var(--text-dark);
        }

        /* Container Responsive: Lebar penuh di HP, terpusat di Laptop */
        .attendance-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 0;
        }

        .card-main {
            background: #ffffff;
            border: 1.5px solid var(--card-border);
            border-radius: 20px;
            padding: 24px 20px;
            box-shadow: 0 4px 12px rgba(13, 135, 242, 0.05);
        }

        .date-header {
            text-align: center;
            color: var(--primary-blue);
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .time-display {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            text-align: center;
        }

        .sub-date {
            text-align: center;
            font-size: 14px;
            color: #64748b;
            margin-bottom: 20px;
        }

        /* Alert / Status Banners */
        .info-box {
            background: #f0f7ff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #334155;
            margin-bottom: 16px;
        }

        .status-box-late {
            background: var(--warning-bg);
            border: 1px solid var(--warning-border);
            color: var(--warning-text);
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .status-box-late .title {
            font-weight: 700;
            font-size: 15px;
        }

        .status-box-late .desc {
            font-size: 13px;
            margin-top: 2px;
            opacity: 0.9;
        }

        /* Status Presensi Selesai/Sudah Check In */
        .checkin-done-box {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            margin-bottom: 16px;
        }

        .checkin-done-box .label {
            font-size: 13px;
            color: #475569;
        }

        .checkin-done-box .time {
            font-size: 18px;
            font-weight: 700;
            color: var(--success-text);
            margin: 4px 0;
        }

        .badge-success {
            display: inline-block;
            background: #dcfce7;
            color: var(--success-text);
            font-size: 12px;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 6px;
        }

        /* Section Kartu Presensi Harian (Daftar Jadwal) */
        .presensi-item-card {
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 16px;
        }

        .presensi-item-card .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .presensi-item-card .title {
            font-weight: 700;
            font-size: 16px;
        }

        .presensi-item-card .time-range {
            font-weight: 700;
            font-size: 16px;
            color: #0f172a;
        }

        .presensi-item-card .subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 16px;
        }

        /* Map Preview Placeholder */
        #map {
            width: 100%;
            height: 180px;
            border-radius: 16px;
            margin-bottom: 20px;
            background: #e2e8f0;
        }

        /* Custom Button */
        .btn-primary {
            width: 100%;
            background: var(--primary-blue);
            color: #ffffff;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity 0.2s;
        }

        .btn-primary:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
            font-size: 16px;
        }

        #schedule-container {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .presensi-item-card {
            min-width: 0;
            margin-bottom: 0;
        }

        @media (max-width: 640px) {
            .card-main {
                padding: 18px 14px;
                border-radius: 16px;
            }

            #schedule-container {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .presensi-item-card .header {
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 4px 12px;
            }

            .presensi-item-card .title,
            .presensi-item-card .time-range {
                font-size: 14px;
            }

            .time-display {
                font-size: 28px;
            }
        }
    </style>

    <div class="attendance-container">
        <!-- MODE 1: Tampilan Utama Pilih Jadwal Presensi -->
        <div id="view-schedule-list" class="card-main">
            <div class="date-header" id="current-date-text">Kamis, 12 Maret 2026</div>

            <div class="info-box">
                <span style="font-size: 18px;">&#9200;</span>
                <span>Pastikan Anda memilih waktu presensi yang sesuai sebelum melanjutkan.</span>
            </div>

            <div id="schedule-container">
                <!-- Kartu Presensi Datang -->
                <div class="presensi-item-card">
                    <div class="header">
                        <span class="title">Presensi datang</span>
                        <span class="time-range">08.00-08.30</span>
                    </div>
                    <div class="subtitle">bengkel jayaabadi</div>
                    <button class="btn-primary" onclick="openPresensiForm('datang')">Presensi sekarang</button>
                </div>

                <!-- Kartu Presensi Pulang -->
                <div class="presensi-item-card">
                    <div class="header">
                        <span class="title">Presensi pulang</span>
                        <span class="time-range">15.00-15.30</span>
                    </div>
                    <div class="subtitle">bengkel jayaabadi</div>
                    <button class="btn-primary" onclick="openPresensiForm('pulang')">Presensi sekarang</button>
                </div>
            </div>

            <div id="empty-schedule-text" class="empty-state" style="display: none;">
                Tidak Ada Presensi Yang berlangsung
            </div>
        </div>

        <!-- MODE 2: Tampilan Form Action Presensi (Check In / Check Out) -->
        <div id="view-presensi-form" class="card-main" style="display: none;">
            <div class="time-display" id="clock-display">08:15:34 WIB</div>
            <div class="sub-date" id="form-date-text">Kamis, 12 Maret 2026</div>

            <!-- Status Terlambat (Conditional) -->
            <div class="status-box-late" id="status-terlambat-box" style="display: none;">
                <div class="title">&#9888; Status: Terlambat</div>
                <div class="desc" id="late-desc">Tiba pukul 08:15 WIB — lebih dari 15 menit</div>
            </div>

            <!-- Status Radius GPS -->
            <div class="info-box" id="gps-status-box">
                <span style="color: var(--primary-blue);">📍</span>
                <span id="gps-status-text">Memeriksa lokasi GPS...</span>
            </div>

            <!-- Box Catatan Check-in Sebelum Check-out -->
            <div class="checkin-done-box" id="previous-checkin-box" style="display: none;">
                <div class="label">Check in</div>
                <div class="time" id="prev-checkin-time">08:15 WIB</div>
                <span class="badge-success">Tepat waktu</span>
            </div>

            <!-- Embed Map Leaflet -->
            <div id="map"></div>

            <!-- Button Submit -->
            <button class="btn-primary" id="btn-submit-presensi" onclick="submitPresensi()" disabled>
                <span id="btn-icon">✓</span>
                <span id="btn-text">Check In Datang</span>
            </button>
        </div>
    </div>

    <!-- Leaflet JS untuk Peta -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Data Dummy Lokasi Perusahaan Mitra (Bisa didapat dari API/backend PHP)
        const MITRA_LAT = -8.1585;
        const MITRA_LNG = 113.7226;
        const RADIUS_LIMIT_METERS = 100; // Maksimal radius 100 meter

        let currentType = 'datang'; // 'datang' atau 'pulang'
        let userLat = null;
        let userLng = null;
        let map = null;
        let marker = null;

        // Jam digital realtime
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

        // Buka Halaman Form Presensi
        function openPresensiForm(type) {
            currentType = type;
            document.getElementById('view-schedule-list').style.display = 'none';
            document.getElementById('view-presensi-form').style.display = 'block';

            const btnText = document.getElementById('btn-text');
            const prevBox = document.getElementById('previous-checkin-box');
            const lateBox = document.getElementById('status-terlambat-box');

            if (type === 'datang') {
                btnText.innerText = 'Check In Datang';
                prevBox.style.display = 'none';
                lateBox.style.display = 'block'; // Contoh jika jam sekarang terlambat
            } else {
                btnText.innerText = 'Check Out Pulang';
                prevBox.style.display = 'block';
                lateBox.style.display = 'none';
            }

            initGPSLocation();
        }

        // Hitung jarak (Formula Haversine)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // meter
            const rad = Math.PI / 180;
            const dLat = (lat2 - lat1) * rad;
            const dLon = (lon2 - lon1) * rad;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * rad) * Math.cos(lat2 * rad) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c; // jarak dalam meter
        }

        // Ambil GPS Browser
        function initGPSLocation() {
            const statusBox = document.getElementById('gps-status-box');
            const statusText = document.getElementById('gps-status-text');
            const submitBtn = document.getElementById('btn-submit-presensi');

            if (!navigator.geolocation) {
                statusText.innerText = 'Browser Anda tidak mendukung fitur Geolocation GPS.';
                statusBox.style.borderColor = '#fca5a5';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLat = position.coords.latitude;
                    userLng = position.coords.longitude;

                    const distance = calculateDistance(userLat, userLng, MITRA_LAT, MITRA_LNG);

                    if (distance <= RADIUS_LIMIT_METERS) {
                        statusText.innerText = `Dalam radius PT Sinergi (Akurasi GPS Baik)`;
                        submitBtn.disabled = false;
                    } else {
                        statusText.innerText = `Luar Radius! Anda berjarak ${Math.round(distance)}m dari lokasi.`;
                        statusBox.style.background = '#fef2f2';
                        statusBox.style.borderColor = '#fca5a5';
                        submitBtn.disabled = true;
                    }

                    renderMap(userLat, userLng);
                },
                (error) => {
                    let errMsg = 'Gagal mengakses GPS.';
                    if (error.code === error.PERMISSION_DENIED) {
                        errMsg = 'Izin lokasi ditolak. Silakan aktifkan izin lokasi browser Anda.';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        errMsg = 'Sinyal GPS tidak tersedia.';
                    }
                    statusText.innerText = errMsg;
                    statusBox.style.background = '#fef2f2';
                    statusBox.style.borderColor = '#fca5a5';
                    submitBtn.disabled = true;
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000
                }
            );
        }

        // Render Peta Interaktif Sederhana
        function renderMap(lat, lng) {
            if (!map) {
                map = L.map('map').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                }).addTo(map);
                marker = L.marker([lat, lng]).addTo(map);
                L.circle([MITRA_LAT, MITRA_LNG], {
                    radius: RADIUS_LIMIT_METERS,
                    color: '#0d87f2'
                }).addTo(map);
            } else {
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
            }
        }

        // Kirim data presensi ke server API PHP
        function submitPresensi() {
            const submitBtn = document.getElementById('btn-submit-presensi');
            submitBtn.disabled = true;
            submitBtn.innerText = 'Mengirim data presensi...';

            const payload = {
                type: currentType,
                latitude: userLat,
                longitude: userLng
            };

            fetch('/siswa-web/presensi/store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Presensi berhasil disimpan!');
                        window.location.reload();
                    } else {
                        alert('Gagal presensi: ' + data.message);
                        submitBtn.disabled = false;
                        submitBtn.innerText = currentType === 'datang' ? 'Check In Datang' : 'Check Out Pulang';
                    }
                })
                .catch(err => {
                    alert('Koneksi ke server gagal. Silakan coba lagi.');
                    submitBtn.disabled = false;
                    submitBtn.innerText = currentType === 'datang' ? 'Check In Datang' : 'Check Out Pulang';
                });
        }
    </script>
</section>