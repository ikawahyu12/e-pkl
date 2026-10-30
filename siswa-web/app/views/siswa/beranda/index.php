<?php
$statIcon = ['Hadir' => ['green', 'check'], 'Telat' => ['red', 'alert'], 'Izin' => ['yellow', 'izin'], 'Jurnal' => ['blue', 'jurnal']];
?>
<section class="hero reveal">
    <div class="hero-main">
        <span class="eyebrow">Dashboard Siswa</span>
        <h2>Halo, <?= e($nama) ?> &#128075;</h2>
        <p class="hero-sub"><?= e($instansi) ?></p>
        <div class="hero-clock">
            <small data-clock-date>Memuat tanggal...</small>
            <strong data-clock>--:--:-- WIB</strong>
        </div>
    </div>
    <div class="hero-status">
        <small>Status Presensi Hari Ini</small>
        <?= ui_badge($statusPresensi, $statusTone) ?>
        <p>Lakukan check in tepat waktu dan jangan lupa isi jurnal sebelum pulang.</p>
        <?= ui_button('Presensi Sekarang', ['href' => url('presensi'), 'variant' => 'light', 'icon' => 'presensi']) ?>
    </div>
</section>

<div class="section-title">Menu Utama</div>
<div class="menu-grid">
    <?php foreach (array_keys(menu_items()) as $i => $key): ?>
        <?= ui_menu_card($key, $i) ?>
    <?php endforeach; ?>
</div>

<div class="dash-grid">
    <?php
    $grid = '';
    foreach ($statistik as $label => $val) {
        $grid .= ui_stat($label, $val, $statIcon[$label][0], $statIcon[$label][1]);
    }
    echo ui_card('<div class="grid-2">' . $grid . '</div>', 'Statistik Bulan Ini');

    $bars = '';
    foreach ($minggu as [$hari, $tipe, $tinggi]) {
        $bars .= '<div class="bar"><span class="fill' . ($tipe === 'izin' ? ' fill-warn' : '') . '" style="--h:' . (int)$tinggi . '%"></span><small>' . e($hari) . '</small></div>';
    }
    echo ui_card('<div class="bars">' . $bars . '</div><div class="legend"><span><i class="dot"></i>Hadir</span><span><i class="dot dot-warn"></i>Izin</span></div>', 'Aktivitas Harian Minggu Ini');
    ?>
</div>
