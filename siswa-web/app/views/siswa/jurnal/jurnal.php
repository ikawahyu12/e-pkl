<section class="activity-layout">
    <div class="activity-card">
        <div class="activity-heading">
            <h2>Jurnal Harian Magang</h2>
            <p>Isi aktivitas harian Anda, 12 Mar 2026.</p>
        </div>

        <div class="activity-fields">
            <div class="activity-field">
                <label for="tanggal-jurnal">Tanggal</label>
                <input id="tanggal-jurnal" type="date" value="2026-03-12">
            </div>
            <div class="activity-field activity-field-wide">
                <label for="aktivitas-jurnal">Pekerjaan / Aktivitas</label>
                <textarea id="aktivitas-jurnal" rows="6" placeholder="Tuliskan pekerjaan magang yang Anda lakukan hari ini..."></textarea>
            </div>
            <div class="activity-field activity-field-wide">
                <label>Foto Dokumentasi</label>
                <div class="activity-upload">
                    <span class="activity-upload-icon" aria-hidden="true">&#9729;</span>
                    <input class="activity-file-input" id="jurnal-file" type="file" accept=".jpg,.jpeg,.png,.webp" data-file-input data-file-name="jurnal-file-name">
                    <label class="activity-file-picker" for="jurnal-file">Pilih foto</label>
                    <span class="activity-file-name" id="jurnal-file-name" aria-live="polite">Belum ada foto dipilih</span>
                    <small>Format JPG, PNG, atau WEBP</small>
                </div>
            </div>
            <div class="activity-field activity-field-wide">
                <button class="btn btn-primary btn-block" type="button">Kirim Jurnal</button>
            </div>
        </div>
    </div>

    <aside class="activity-card activity-status">
        <div class="activity-heading">
            <h2>Laporan Jurnal Kemarin</h2>
            <p>Ringkasan laporan terakhir Anda.</p>
        </div>
        <div class="activity-record">
            <div>
                <strong>Rabu, 11 Maret 2026</strong>
                <p>Lihat detail aktivitas</p>
            </div>
            <span class="pill pill-ok">Selesai</span>
        </div>
    </aside>
</section>
