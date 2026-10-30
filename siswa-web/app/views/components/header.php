<?php /* Header atas. Navigasi antar fitur lewat kartu menu di Beranda, jadi header hanya berisi logo + profil. */
$u = current_user(); ?>
<header class="topbar">
    <div class="container topbar-in">
        <a class="brand" href="<?= url('beranda') ?>" aria-label="Ke Beranda">
            <span class="brand-mark">E</span>
            <span class="brand-text">Pantau PKL</span>
        </a>
        <a class="user-chip" href="<?= url('profil') ?>" title="Profil saya">
            <span class="avatar"><?= e($u['inisial']) ?></span>
            <span class="user-name"><?= e($u['nama']) ?></span>
        </a>
    </div>
</header>
