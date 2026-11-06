<?php
$u = current_user();
?>

<header class="topbar">
    <div class="container topbar-in">
        <a class="brand" href="<?= url('beranda') ?>" aria-label="Ke Beranda">
            <img class="brand-logo" src="<?= e(asset('img/logo_e-pkl.png')) ?>" alt="Logo E-PKL">
        </a>

        <div class="topbar-actions">
            <a class="user-chip" href="<?= url('profil') ?>" title="Profil saya">
                <span class="avatar"><?= e($u['inisial']) ?></span>
                <span class="user-name"><?= e($u['nama']) ?></span>
            </a>

            <form method="POST" action="<?= e(url('login/logout')) ?>">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >
                <button type="submit" class="logout-button">
                    Keluar
                </button>
            </form>
        </div>
    </div>
</header>