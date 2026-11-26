<section class="activity-layout">
    <div class="activity-card">
        <div class="activity-heading">
            <h2>Ajukan Izin / Sakit</h2>
            <p>Lengkapi informasi perizinan dan lampirkan surat bukti jika tersedia.</p>
        </div>

        <!-- Notifikasi sukses/gagal akan muncul di sini -->
        <div id="izin-notif" class="alert" role="status" aria-live="polite" hidden></div>

        <form id="form-izin" action="<?= e(url('izin/kirim')) ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="activity-fields">
            <div class="activity-field">
                <label for="jenis-izin">Jenis Perizinan</label>
                <select id="jenis-izin" name="jenis">
                    <option value="sakit">Sakit</option>
                    <option value="izin">Izin</option>
                </select>
            </div>
            <div class="activity-field">
                <label for="izin-mulai">Mulai Tanggal</label>
                <input id="izin-mulai" name="tanggal_mulai" type="date">
            </div>
            <div class="activity-field">
                <label for="izin-selesai">Hingga Tanggal</label>
                <input id="izin-selesai" name="tanggal_selesai" type="date">
            </div>
            <div class="activity-field activity-field-wide">
                <label for="alasan-izin">Keterangan / Alasan</label>
                <textarea id="alasan-izin" name="alasan" rows="4" placeholder="Berikan alasan jelas ketidakhadiran Anda..."></textarea>
            </div>
            <div class="activity-field activity-field-wide">
                <label for="izin-file">Surat Bukti</label>
                <div class="activity-upload">
                    <span class="activity-upload-icon" aria-hidden="true">&#128196;</span>
                    <input class="activity-file-input" id="izin-file" name="lampiran" type="file" accept=".jpg,.jpeg,.png,.pdf" data-file-input data-file-name="izin-file-name">
                    <label class="activity-file-picker" for="izin-file">Pilih file</label>
                    <span class="activity-file-name" id="izin-file-name" aria-live="polite">Belum ada file dipilih</span>
                    <small>Format JPG, PNG, atau PDF (maksimal 5MB)</small>
                </div>
            </div>
            <div class="activity-field activity-field-wide">
                <button class="btn btn-primary btn-block" type="submit" id="izin-submit">Kirim Pengajuan</button>
            </div>
            </div>
        </form>
    </div>
</section>

<script>
    const izinFile = document.getElementById('izin-file');
    const izinFileName = document.getElementById('izin-file-name');
    const izinForm = document.getElementById('form-izin');

    izinFile.addEventListener('change', function() {
        izinFileName.textContent = this.files[0] ? this.files[0].name : 'Belum ada file dipilih';
    });

    izinForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        const button = document.getElementById('izin-submit');
        const notification = document.getElementById('izin-notif');
        button.disabled = true;
        button.textContent = 'Mengirim...';
        notification.hidden = true;

        try {
            const response = await fetch(this.action, {
                method: this.method,
                headers: { 'Accept': 'application/json' },
                body: new FormData(this)
            });
            const result = await response.json();

            // Menampilkan notifikasi sukses atau gagal
            notification.className = 'alert ' + (result.success ? 'alert-success' : 'alert-danger');
            notification.textContent = result.message || 'Pengajuan gagal dikirim.';
            notification.hidden = false;

            if (result.success) {
                // Hanya reset form jika berhasil
                this.reset();
                izinFileName.textContent = 'Belum ada file dipilih';
            }
        } catch (error) {
            notification.className = 'alert alert-danger';
            notification.textContent = 'Tidak dapat menghubungi server. Periksa koneksi lalu coba lagi.';
            notification.hidden = false;
        } finally {
            button.disabled = false;
            button.textContent = 'Kirim Pengajuan';
        }
    });
</script>