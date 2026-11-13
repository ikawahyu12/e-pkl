<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Harian Magang - EPKL</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; color: #333; }
        .activity-layout { display: flex; gap: 20px; max-width: 800px; margin: auto; }
        .activity-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; }
        .activity-heading h2 { margin-top: 0; color: #2c3e50; }
        .activity-heading p { color: #7f8c8d; font-size: 14px; }
        .activity-fields { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 20px; }
        .activity-field { display: flex; flex-direction: column; width: 100%; }
        .activity-field:not(.activity-field-wide) { width: calc(50% - 10px); }
        label { font-weight: bold; margin-bottom: 5px; font-size: 14px; }
        input[type="date"], input[type="text"], textarea { padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; }
        textarea { resize: vertical; }
        .activity-upload { border: 2px dashed #ccc; padding: 15px; text-align: center; border-radius: 8px; background: #fafafa; }
        .activity-file-input { display: none; }
        .activity-file-picker { display: inline-block; background: #3498db; color: white; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin: 10px 0; }
        .activity-file-picker:hover { background: #2980b9; }
        .activity-file-name { display: block; font-size: 13px; color: #666; margin-top: 5px; }
        .btn-primary { background: #2ecc71; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%; }
        .btn-primary:hover { background: #27ae60; }
        .btn-primary:disabled { background: #95a5a6; cursor: not-allowed; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; display: none; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<section class="activity-layout">
    <div class="activity-card">
        <div class="activity-heading">
            <h2>Jurnal Harian Magang</h2>
            <p>Isi aktivitas harian Anda, <?= date('d M Y') ?>.</p>
        </div>

        <!-- Notifikasi pesan -->
        <div id="notif" class="alert"></div>

        <form id="form-jurnal" action="<?= e(url('jurnal/kirim')) ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="activity-fields">
                <!-- Tanggal -->
                <div class="activity-field">
                    <label for="tanggal-jurnal">Tanggal</label>
                    <input id="tanggal-jurnal" name="tanggal" type="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <!-- Judul -->
                <div class="activity-field">
                    <label for="judul-jurnal">Judul Jurnal</label>
                    <input id="judul-jurnal" name="judul" type="text" placeholder="Contoh: Membuat UI Halaman Login" required>
                </div>
                
                <!-- Kegiatan -->
                <div class="activity-field activity-field-wide">
                    <label for="kegiatan-jurnal">Pekerjaan / Kegiatan</label>
                    <textarea id="kegiatan-jurnal" name="kegiatan" rows="4" placeholder="Tuliskan detail pekerjaan magang..." required></textarea>
                </div>

                <!-- Hasil -->
                <div class="activity-field activity-field-wide">
                    <label for="hasil-jurnal">Hasil / Output</label>
                    <textarea id="hasil-jurnal" name="hasil" rows="2" placeholder="Apa hasil yang didapat hari ini?" required></textarea>
                </div>

                <!-- Kendala -->
                <div class="activity-field activity-field-wide">
                    <label for="kendala-jurnal">Kendala (Opsional)</label>
                    <textarea id="kendala-jurnal" name="kendala" rows="2" placeholder="Apakah ada kendala hari ini?"></textarea>
                </div>
                
                <!-- Foto Dokumentasi -->
                <div class="activity-field activity-field-wide">
                    <label>Foto Dokumentasi (Opsional)</label>
                    <div class="activity-upload">
                        <span class="activity-upload-icon" aria-hidden="true">&#9729;</span>
                        <input class="activity-file-input" id="jurnal-file" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp">
                        <label class="activity-file-picker" for="jurnal-file">Pilih foto</label>
                        <span class="activity-file-name" id="jurnal-file-name" aria-live="polite">Belum ada foto dipilih</span>
                        <small>Format JPG, PNG, atau WEBP (Maks 5MB)</small>
                    </div>
                </div>
                
                <!-- Tombol Submit -->
                <div class="activity-field activity-field-wide">
                    <button class="btn btn-primary btn-block" type="submit" id="btn-submit">Kirim Jurnal</button>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
    document.getElementById('jurnal-file').addEventListener('change', function() {
        const fileName = this.files[0] ? this.files[0].name : 'Belum ada foto dipilih';
        document.getElementById('jurnal-file-name').textContent = fileName;
    });

    document.getElementById('form-jurnal').addEventListener('submit', async function(e) {
        e.preventDefault(); 

        const btn = document.getElementById('btn-submit');
        const notif = document.getElementById('notif');
        const formData = new FormData(this);

        notif.style.display = 'none';
        btn.disabled = true;
        btn.textContent = 'Mengirim...';

        try {
            const response = await fetch(this.action, {
                method: this.method,
                headers: { 'Accept': 'application/json' },
                body: formData
            });
            
            const result = await response.json();

            if (result.success) {
                notif.className = 'alert alert-success';
                notif.style.display = 'block';
                notif.textContent = '✅ ' + result.message;
                
                this.reset(); 
                document.getElementById('tanggal-jurnal').value = '<?= date('Y-m-d') ?>';
                document.getElementById('jurnal-file-name').textContent = 'Belum ada foto dipilih';
            } else {
                notif.className = 'alert alert-danger';
                notif.style.display = 'block';
                notif.textContent = '❌ ' + result.message;
            }
        } catch (error) {
            notif.className = 'alert alert-danger';
            notif.style.display = 'block';
            notif.textContent = '❌ Terjadi kesalahan pada server atau jaringan.';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Kirim Jurnal';
        }
    });
</script>

</body>
</html>