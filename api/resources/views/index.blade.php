<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Harian Magang</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Panggil CSS Anda di sini -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

    <header class="top-header">
        <span class="app-title">jurnal-mobile</span>
    </header>

    <main class="main-container">
        <nav class="nav-bar">
            <div class="nav-left">
                <span class="back-icon">&#10094;</span>
                <span class="nav-title">Jurnal</span>
            </div>
            <div class="nav-right">
                <span class="logo-text">GNL</span>
            </div>
        </nav>

        <div class="page-header">
            <h1>Jurnal Harian Magang</h1>
            <p>PT Sinergi Teknologi Indonesia - {{ \Carbon\Carbon::now()->translatedFormat('l, d M Y') }}</p>
        </div>

        <!-- Notifikasi Laravel -->
        @if(session('success'))
            <div style="background-color: #dcfce7; color: #166534; padding: 12px; margin: 0 20px 15px; border-radius: 8px; font-size: 0.9rem;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background-color: #fee2e2; color: #991b1b; padding: 12px; margin: 0 20px 15px; border-radius: 8px; font-size: 0.9rem;">
                {{ session('error') }}
            </div>
        @endif

        <section class="activity-card">
            <div class="card-heading">
                <h2>Tulis Laporan Baru</h2>
            </div>

            <form action="{{ route('jurnal.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="tanggal">Tanggal</label>
                    <input type="date" id="tanggal" name="tanggal" class="input-custom" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="form-group">
                    <label for="aktivitas">Pekerjaan / Aktivitas</label>
                    <textarea id="aktivitas" name="aktivitas" rows="5" placeholder="Tuliskan di sini apa saja pekerjaan magang yang Anda rampungkan hari ini..." required>{{ old('aktivitas') }}</textarea>
                </div>

                <div class="form-group">
                    <label>Foto Dokumentasi</label>
                    <div class="upload-area" onclick="document.getElementById('foto').click()">
                        <div class="upload-icon">📄</div>
                        <div class="upload-text" id="upload-text-visual">
                            <strong>Tarik & lepas foto di sini</strong>
                            <span>JPG, PNG max 5MB</span>
                        </div>
                        <input type="file" id="foto" name="foto" class="hidden-file-input" accept=".jpg,.jpeg,.png,.webp" required onchange="updateFileName(this)">
                    </div>
                </div>

                <button type="submit" class="btn-primary">Kirim Jurnal</button>
            </form>
        </section>

        <section class="history-section">
            <h3>Laporan Jurnal Kemarin</h3>
            <div class="history-card">
                <div class="history-date">
                    @if($laporanKemarin)
                        <strong>{{ \Carbon\Carbon::parse($laporanKemarin['tanggal'])->translatedFormat('l, d F Y') }}</strong>
                    @else
                        <strong style="color:#94a3b8; font-weight:500;">Belum ada laporan</strong>
                    @endif
                </div>
                @if($laporanKemarin)
                    <span class="pill pill-ok">Selesai</span>
                @endif
            </div>
        </section>
    </main>

    <script>
        function updateFileName(input) {
            const textVisual = document.getElementById('upload-text-visual');
            if (input.files && input.files.length > 0) {
                textVisual.innerHTML = `<strong>${input.files[0].name}</strong><span>Foto siap diunggah</span>`;
            } else {
                textVisual.innerHTML = `<strong>Tarik & lepas foto di sini</strong><span>JPG, PNG max 5MB</span>`;
            }
        }
    </script>
</body>
</html>