
<section class="activity-card history-page" data-pkl-start-date="<?= e($tanggalMulaiPkl ?? '') ?>">
    <div class="activity-heading">
        <h2>Riwayat PKL Siswa</h2>
        <p>Rekap aktivitas dan status kehadiran Anda.</p>
    </div>

    <div class="history-toolbar">
        <div class="history-tabs" role="group" aria-label="Filter jenis riwayat">
            <button type="button" class="is-active" data-history-type="all" aria-pressed="true">Semua</button>
            <button type="button" data-history-type="attendance" aria-pressed="false">Presensi</button>
            <button type="button" data-history-type="journal" aria-pressed="false">Jurnal</button>
        </div>

        <div class="history-filters">
            <label class="sr-only" for="history-week">Minggu</label>
            <select id="history-week">
                <option value="all">Semua Minggu</option>
            </select>

            <label class="sr-only" for="history-month">Bulan</label>
            <select id="history-month">
                <option value="all">Semua Bulan</option>
                <option value="1">Januari</option>
                <option value="2">Februari</option>
                <option value="3">Maret</option>
                <option value="4">April</option>
                <option value="5">Mei</option>
                <option value="6">Juni</option>
                <option value="7">Juli</option>
                <option value="8">Agustus</option>
                <option value="9">September</option>
                <option value="10">Oktober</option>
                <option value="11">November</option>
                <option value="12">Desember</option>
            </select>
        </div>
    </div>

    <div class="history-list" id="history-list">
        <article class="history-item" data-history-item data-type="attendance" data-date="2026-03-12">
            <div>
                <strong>Kamis, 12 Mar 2026</strong>
                <p>Presensi: Masuk</p>
            </div>
            <div class="history-meta">
                <span class="pill pill-ok">Masuk</span>
                <small>07:42 WIB</small>
            </div>
        </article>

        <article class="history-item" data-history-item data-type="journal" data-date="2026-03-11">
            <div>
                <strong>Rabu, 11 Mar 2026</strong>
                <p>Laporan Jurnal Harian</p>
            </div>
            <div class="history-meta">
                <span class="pill pill-info">Terverifikasi</span>
                <small>11:21 WIB</small>
            </div>
        </article>

        <article class="history-item" data-history-item data-type="attendance" data-date="2026-03-11">
            <div>
                <strong>Rabu, 11 Mar 2026</strong>
                <p>Presensi: Pulang</p>
            </div>
            <div class="history-meta">
                <span class="pill pill-ok">Pulang</span>
                <small>17:02 WIB</small>
            </div>
        </article>

        <article class="history-item" data-history-item data-type="attendance" data-date="2026-03-09">
            <div>
                <strong>Senin, 09 Mar 2026</strong>
                <p>Presensi: Masuk</p>
            </div>
            <div class="history-meta">
                <span class="pill pill-ok">Masuk</span>
                <small>08:12 WIB</small>
            </div>
        </article>
    </div>

    <p class="history-empty" id="history-empty" role="status" hidden>
        Tidak ada riwayat untuk filter yang dipilih.
    </p>
</section>
