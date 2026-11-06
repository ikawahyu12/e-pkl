<section class="activity-layout">
    <div class="activity-card">
        <div class="activity-heading">
            <h2>Ajukan Izin / Sakit</h2>
            <p>Lengkapi informasi perizinan dan lampirkan surat bukti jika tersedia.</p>
        </div>

        <div class="activity-fields">
            <div class="activity-field">
                <label for="jenis-izin">Jenis Perizinan</label>
                <select id="jenis-izin">
                    <option>Sakit</option>
                    <option>Izin</option>
                </select>
            </div>
            <div class="activity-field">
                <label for="izin-mulai">Mulai Tanggal</label>
                <input id="izin-mulai" type="date" value="2026-03-13">
            </div>
            <div class="activity-field">
                <label for="izin-selesai">Hingga Tanggal</label>
                <input id="izin-selesai" type="date" value="2026-03-15">
            </div>
            <div class="activity-field activity-field-wide">
                <label for="alasan-izin">Keterangan / Alasan</label>
                <textarea id="alasan-izin" rows="4" placeholder="Berikan alasan jelas ketidakhadiran Anda..."></textarea>
            </div>
            <div class="activity-field activity-field-wide">
                <label>Surat Bukti</label>
                <div class="activity-upload">
                    <span class="activity-upload-icon" aria-hidden="true">&#128196;</span>
                    <input class="activity-file-input" id="izin-file" type="file" accept=".jpg,.jpeg,.png,.pdf" data-file-input data-file-name="izin-file-name">
                    <label class="activity-file-picker" for="izin-file">Pilih file</label>
                    <span class="activity-file-name" id="izin-file-name" aria-live="polite">Belum ada file dipilih</span>
                    <small>Format JPG, PNG, atau PDF</small>
                </div>
            </div>
            <div class="activity-field activity-field-wide">
                <button class="btn btn-primary btn-block" type="button">Kirim Pengajuan</button>
            </div>
        </div>
    </div>

    <aside class="activity-card activity-status">
        <div class="activity-heading">
            <h2>Status Pengajuan Terbaru</h2>
            <p>Pantau status permohonan izin Anda.</p>
        </div>
        <div class="activity-record">
            <div>
                <strong>13 - 15 Mar 2026</strong>
                <p>Keterangan: Sakit</p>
            </div>
            <span class="pill pill-warn">Menunggu</span>
        </div>
    </aside>
</section>
