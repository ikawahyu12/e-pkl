<section class="activity-card history-page" data-pkl-start-date="<?= e($tanggalMulaiPkl ?? '') ?>">
    <div class="activity-heading">
        <h2>Riwayat PKL Siswa</h2>
        <p>Rekap aktivitas dan status kehadiran Anda.</p>
    </div>

    <?php if (!empty($apiError)): ?>
        <p class="history-empty" role="alert"><?= e($apiError) ?></p>
    <?php endif; ?>

    <div class="history-toolbar">
        <div class="history-tabs" role="group" aria-label="Filter jenis riwayat">
            <button type="button" class="is-active" data-history-type="all" aria-pressed="true">Semua</button>
            <button type="button" data-history-type="attendance" aria-pressed="false">Presensi</button>
            <button type="button" data-history-type="journal" aria-pressed="false">Jurnal</button>
            <!-- Tambahan Tab Izin -->
            <button type="button" data-history-type="izin" aria-pressed="false">Izin / Sakit</button>
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
        <?php foreach (($riwayat ?? []) as $item): ?>
            <article class="history-item" data-history-item data-type="<?= e($item['type']) ?>" data-date="<?= e($item['date']) ?>">
                <div>
                    <strong><?= e($item['label']) ?></strong>
                    <p><?= e($item['title']) ?></p>
                </div>
                <div class="history-meta">
                    <span class="pill <?= e($item['pill']) ?>"><?= e($item['badge']) ?></span>
                    <?php if (!empty($item['jam'])): ?>
                        <small><?= e($item['jam']) ?></small>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <p class="history-empty" id="history-empty" role="status" <?= empty($riwayat) ? '' : 'hidden' ?>>
        Tidak ada riwayat untuk filter yang dipilih.
    </p>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.history-tabs button[data-history-type]');
    const items = document.querySelectorAll('[data-history-item]');
    const emptyState = document.getElementById('history-empty');
    const monthFilter = document.getElementById('history-month');
    const weekFilter = document.getElementById('history-week');

    let currentType = 'all';

    // 1. Logika untuk Tab Filter (Semua, Presensi, Jurnal, Izin)
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update class active
            tabs.forEach(t => {
                t.classList.remove('is-active');
                t.setAttribute('aria-pressed', 'false');
            });
            this.classList.add('is-active');
            this.setAttribute('aria-pressed', 'true');

            currentType = this.getAttribute('data-history-type');
            applyFilters();
        });
    });

    // 2. Logika untuk Filter Bulan & Minggu
    if (monthFilter) monthFilter.addEventListener('change', applyFilters);
    if (weekFilter) weekFilter.addEventListener('change', applyFilters);

    // 3. Fungsi Utama untuk Menyaring Data
    function applyFilters() {
        const selectedMonth = monthFilter ? monthFilter.value : 'all';
        const selectedWeek = weekFilter ? weekFilter.value : 'all';
        
        let visibleCount = 0;

        items.forEach(item => {
            const itemType = item.getAttribute('data-type');
            const itemDateStr = item.getAttribute('data-date'); // Format: YYYY-MM-DD
            
            // Cek Tipe (Tab)
            const matchType = (currentType === 'all' || itemType === currentType);

            // Cek Bulan
            let matchMonth = true;
            if (selectedMonth !== 'all' && itemDateStr) {
                const dateObj = new Date(itemDateStr + 'T00:00:00');
                // getMonth() dimulai dari 0, jadi ditambah 1
                matchMonth = (dateObj.getMonth() + 1).toString() === selectedMonth;
            }

            // Cek Minggu (Logika sederhana berdasarkan tanggal)
            let matchWeek = true;
            if (selectedWeek !== 'all' && itemDateStr) {
                const dateObj = new Date(itemDateStr + 'T00:00:00');
                const dateOfMonth = dateObj.getDate();
                // Asumsi: Minggu 1 (tgl 1-7), Minggu 2 (8-14), Minggu 3 (15-21), Minggu 4 (22-28), Minggu 5 (29-31)
                const weekNumber = Math.ceil(dateOfMonth / 7).toString();
                matchWeek = (weekNumber === selectedWeek);
            }

            // Tampilkan atau sembunyikan
            if (matchType && matchMonth && matchWeek) {
                item.style.display = ''; // Tampil
                visibleCount++;
            } else {
                item.style.display = 'none'; // Sembunyi
            }
        });

        // Tampilkan pesan "Kosong" jika tidak ada data yang cocok
        if (emptyState) {
            emptyState.hidden = (visibleCount > 0);
        }
    }
});
</script>