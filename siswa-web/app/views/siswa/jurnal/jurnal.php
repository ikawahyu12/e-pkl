<section class="activity-layout journal-page">
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
