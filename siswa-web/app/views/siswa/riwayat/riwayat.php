<?php
// ============================================================
// DATA PALSU (sementara). Nanti ganti dengan data dari controller:
// $riwayat = $data['riwayat'];
// 'tipe' harus 'presensi' atau 'jurnal'
// ============================================================
$riwayat = [
    ['tipe' => 'presensi', 'tanggal' => 'Kamis, 12 Mar 2026', 'judul' => 'Presensi Masuk',        'nilai' => '07:42 WIB', 'status' => 'Tepat Waktu',  'warna' => 'hijau'],
    ['tipe' => 'jurnal',   'tanggal' => 'Rabu, 11 Mar 2026',  'judul' => 'Laporan Jurnal Harian', 'nilai' => '1 Laporan', 'status' => 'Terverifikasi', 'warna' => 'hijau'],
    ['tipe' => 'presensi', 'tanggal' => 'Rabu, 11 Mar 2026',  'judul' => 'Presensi Pulang',       'nilai' => '17:02 WIB', 'status' => 'Hadir',        'warna' => 'hijau'],
    ['tipe' => 'presensi', 'tanggal' => 'Senin, 09 Mar 2026', 'judul' => 'Presensi Masuk',        'nilai' => '08:12 WIB', 'status' => 'Terlambat',    'warna' => 'oranye'],
    ['tipe' => 'jurnal',   'tanggal' => 'Selasa, 10 Mar 2026','judul' => 'Laporan Jurnal Harian', 'nilai' => '1 Laporan', 'status' => 'Menunggu',     'warna' => 'oranye'],
];
?>

<style>
    .tab-riwayat { display: flex; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 4px; }
    .tab-riwayat button {
        flex: 1; border: 0; background: transparent; padding: 8px 0;
        font-size: 13px; color: #475569; border-radius: 8px; cursor: pointer;
    }
    .tab-riwayat button.aktif { background: #dbeafe; color: #1e40af; font-weight: 600; }

    .kartu-riwayat { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-top: 12px; }
    .kartu-riwayat .baris { display: flex; justify-content: space-between; align-items: center; }
    .kartu-riwayat .tanggal { font-weight: 600; font-size: 14px; color: #0f172a; }
    .kartu-riwayat .judul { font-size: 13px; color: #64748b; margin-top: 6px; }
    .kartu-riwayat .nilai { font-size: 13px; font-weight: 600; color: #0f172a; margin-top: 6px; }

    .badge { font-size: 11px; padding: 3px 10px; border-radius: 999px; font-weight: 600; }
    .badge.hijau  { background: #d1fae5; color: #047857; }
    .badge.oranye { background: #ffedd5; color: #c2410c; }

    #kosong-riwayat { display: none; text-align: center; color: #94a3b8; font-size: 13px; padding: 32px 0; }
</style>

<!-- TAB FILTER -->
<div class="tab-riwayat" id="tab-riwayat">
    <button type="button" class="aktif" data-filter="semua">Semua</button>
    <button type="button" data-filter="presensi">Presensi</button>
    <button type="button" data-filter="jurnal">Jurnal</button>
</div>

<!-- LIST RIWAYAT -->
<div id="list-riwayat">
    <?php foreach ($riwayat as $item): ?>
        <div class="kartu-riwayat" data-tipe="<?= htmlspecialchars($item['tipe']) ?>">
            <div class="baris">
                <span class="tanggal"><?= htmlspecialchars($item['tanggal']) ?></span>
                <span class="badge <?= htmlspecialchars($item['warna']) ?>"><?= htmlspecialchars($item['status']) ?></span>
            </div>
            <div class="baris">
                <span class="judul"><?= htmlspecialchars($item['judul']) ?></span>
                <span class="nilai"><?= htmlspecialchars($item['nilai']) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="kosong-riwayat">Belum ada data.</div>

<script>
    (function () {
        const tombol = document.querySelectorAll('#tab-riwayat button');
        const kartu  = document.querySelectorAll('#list-riwayat .kartu-riwayat');
        const kosong = document.getElementById('kosong-riwayat');

        tombol.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const filter = btn.dataset.filter;

                // ganti tab aktif
                tombol.forEach(function (b) { b.classList.remove('aktif'); });
                btn.classList.add('aktif');

                // tampilkan / sembunyikan kartu
                let jumlah = 0;
                kartu.forEach(function (k) {
                    const cocok = filter === 'semua' || k.dataset.tipe === filter;
                    k.style.display = cocok ? '' : 'none';
                    if (cocok) jumlah++;
                });

                kosong.style.display = jumlah === 0 ? 'block' : 'none';
            });
        });
    })();
</script>