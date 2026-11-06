<?php
/**
 * Profil Siswa (fragment, dipasang oleh layouts)
 * Variabel dari ProfilController::index(): $siswa (array), $flash (array|null)
 */
include __DIR__ . '/_styles.php';

// Sesuaikan nama variabel dengan yang dikirim controllermu (cukup ubah di sini)
$siswa = $siswa ?? $profil ?? $user ?? [];
$flash = $flash ?? null;
if (empty($siswa)) $siswa = require __DIR__ . '/_dummy.php'; // DUMMY: hapus kalau controller sudah kirim data

$val = fn($k) => e(($siswa[$k] ?? '') !== '' ? $siswa[$k] : '-');
$row = fn($icon, $label, $v) =>
    '<div class="pf-row"><span class="pf-ico">' . pf_icon($icon) . '</span><div><small>' . e($label) . '</small><strong>' . $v . '</strong></div></div>';

// Kartu dengan judul + elemen kanan (link / badge)
$card = fn($title, $right, $body) =>
    '<section class="pf-card"><div class="pf-card-head"><h3 class="pf-card-title">' . e($title) . '</h3>' . $right . '</div>' . $body . '</section>';

$tgl = function ($d) {
    if (empty($d)) return '-';
    $bln = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($d);
    return date('j', $t) . ' ' . $bln[(int) date('n', $t)] . ' ' . date('Y', $t);
};

// Avatar: pakai foto kalau ada, kalau tidak pakai inisial nama (tanpa butuh file gambar)
$ini = '';
foreach (array_slice(preg_split('/\s+/', trim($siswa['nama_lengkap'] ?? '')), 0, 2) as $w) {
    $ini .= mb_strtoupper(mb_substr($w, 0, 1));
}
$fallback = 'data:image/svg+xml;base64,' . base64_encode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#2b8cff"/>' .
    '<text x="50%" y="54%" font-family="Arial" font-size="80" font-weight="700" fill="#fff" text-anchor="middle" dominant-baseline="middle">' .
    htmlspecialchars($ini ?: '?') . '</text></svg>'
);
$foto = !empty($siswa['foto']) ? url('uploads/siswa/' . $siswa['foto']) : $fallback;

$statusMap = [
    'berjalan' => ['Sedang Berjalan', ''],
    'selesai'  => ['Selesai', ' pf-badge-selesai'],
    'belum'    => ['Belum Mulai', ' pf-badge-belum'],
];
[$statusLabel, $statusClass] = $statusMap[$siswa['status_pkl'] ?? 'berjalan'] ?? $statusMap['berjalan'];

$dataDiri =
    $row('user', 'NISN / Username', $val('nisn')) .
    $row('user', 'Nama Lengkap', $val('nama_lengkap')) .
    $row('briefcase', 'Kelas / Jurusan', $val('kelas')) .
    $row('phone', 'No. Handphone', $val('no_hp')) .
    $row('mail', 'Email', $val('email')) .
    $row('pin', 'Alamat', $val('alamat'));

$instansi =
    $row('layers', 'Nama Perusahaan', $val('nama_perusahaan')) .
    $row('pin', 'Alamat Instansi', $val('alamat_instansi')) .
    $row('usercheck', 'Pembimbing Lapangan', $val('pembimbing_lapangan')) .
    $row('phone', 'No. Kontak Pembimbing', $val('kontak_pembimbing_lapangan')) .
    $row('calendar', 'Periode PKL', e($tgl($siswa['tanggal_mulai'] ?? null)) . ' – ' . e($tgl($siswa['tanggal_selesai'] ?? null)));

$guru =
    $row('usercheck', 'Nama Guru Pembimbing', $val('guru_pembimbing')) .
    $row('phone', 'No. Kontak', $val('kontak_guru')) .
    $row('mail', 'Email', $val('email_guru'));
?>
<div class="pf-stack pf-overview reveal">
    <?php if (!empty($flash)): ?>
        <div class="pf-alert pf-alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <section class="pf-hero">
        <div class="pf-banner"></div>
        <div class="pf-hero-body">
            <div class="pf-avatar-wrap">
                <img class="pf-avatar" src="<?= e($foto) ?>" alt="Foto profil" onerror="this.onerror=null;this.src='<?= $fallback ?>'">
                <a class="pf-avatar-btn" href="<?= url('profil/edit') ?>" aria-label="Ganti foto"><?= pf_icon('camera', 15) ?></a>
            </div>
            <h2><?= e($siswa['nama_lengkap'] ?? '') ?></h2>
            <p><?= e($siswa['nisn'] ?? '') ?> &bull; <?= e($siswa['kelas'] ?? '') ?></p>
        </div>
    </section>

    <?= $card('Data Diri', '<a class="pf-link" href="' . url('profil/edit') . '">Edit</a>', $dataDiri) ?>
    <?= $card('Data Instansi PKL', '<span class="pf-badge' . $statusClass . '">' . e($statusLabel) . '</span>', $instansi) ?>
    <?= $card('Pembimbing Sekolah', '', $guru) ?>

    <a class="pf-menu" href="<?= url('profil/password') ?>">
        <span class="l"><?= pf_icon('lock', 20) ?> Ubah Kata Sandi</span><?= pf_icon('chevron', 18) ?>
    </a>
        <span class="l"><?= pf_icon('logout', 20) ?> Keluar (Logout)</span>
    </a>
</div>