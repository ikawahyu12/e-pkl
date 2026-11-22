<?php
$pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Login Admin';
$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$scriptDirectory = rtrim($scriptDirectory, '/');
$scriptDirectory = $scriptDirectory === '.' ? '' : $scriptDirectory;
$assetBaseUrl = isset($assetBaseUrl) && is_string($assetBaseUrl)
    ? rtrim($assetBaseUrl, '/')
    : $scriptDirectory . '/assets';
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Login administrator E-PKL">
    <title><?= $e($pageTitle) ?> | E-PKL</title>
    <link rel="stylesheet" href="<?= $e($assetBaseUrl) ?>/vendor/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?= $e($assetBaseUrl) ?>/css/sb-admin-2.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $e($assetBaseUrl) ?>/css/font-montserrat.css">
    <style>
        :root {
            color-scheme: light;
            --login-blue: #078cf2;
            --login-ink: #223247;
            --login-muted: #778394;
            --login-input: #eef3ff;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        .login-title {
            margin: 0 0 8px;
            color: #223247;
            font-size: 1.65rem;
            font-weight: 800;
            text-align: center;
        }

        .login-subtitle {
            margin: 0 0 28px;
            color: #778394;
            font-size: .75rem;
            text-align: center;
        }

        body.login-page {
            min-height: 100vh;
            margin: 0;
            color: var(--login-ink);
            background:
                radial-gradient(ellipse at 15% 82%, rgba(180, 205, 255, .4), transparent 30%),
                linear-gradient(125deg, #eaf4ff 0%, #f3f5f8 54%, #dceaff 100%);
        }

        .app-logo {
            width: 210px;
            height: auto;
            object-fit: contain;
        }

        .login-stage {
            display: grid;
            min-height: 100vh;
            padding: 48px 28px;
            place-items: center;
        }

        .login-card {
            display: grid;
            grid-template-columns: 42% 58%;
            width: min(100%, 1016px);
            min-height: 660px;
            overflow: hidden;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 18px 38px rgba(30, 53, 81, .17);
        }

        .login-brand-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background:
                radial-gradient(ellipse at 100% 0%, rgba(172, 207, 255, .38), transparent 34%),
                linear-gradient(155deg, #f0f7ff 0%, #edf2ff 100%);
        }

        .portal-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            width: fit-content;
            padding: 5px 12px;
            color: #2878bb;
            background: rgba(255, 255, 255, .9);
            border-radius: 20px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .portal-label i {
            color: #078cf2;
            font-size: .48rem;
        }

        .brand-heading {
            max-width: 330px;
            margin: 22px 0 0;
            color: #25354a;
            font-size: 1.22rem;
            font-weight: 800;
            line-height: 1.48;
        }

        .login-form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 60px;
        }

        .login-form-content {
            width: 100%;
            max-width: 500px;
        }


        .login-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 11px 13px;
            color: #738096;
            background: #eef3ff;
            border-radius: 8px;
            font-size: .75rem;
            line-height: 1.45;
        }

        .login-notice i {
            flex: 0 0 auto;
            color: #5494c8;
            font-size: .9rem;
        }

        .login-field {
            margin-bottom: 14px;
        }

        .login-field label {
            display: block;
            margin-bottom: 6px;
            color: #48566a;
            font-size: .72rem;
            font-weight: 800;
        }

        .login-input-group {
            display: flex;
            min-height: 43px;
            align-items: center;
            padding: 0 13px;
            color: #8997a8;
            background: var(--login-input);
            border: 1px solid transparent;
            border-radius: 7px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .login-input-group:focus-within {
            border-color: #7bbafa;
            box-shadow: 0 0 0 .18rem rgba(7, 140, 242, .12);
        }

        .login-input-group > i {
            flex: 0 0 auto;
            width: 17px;
            font-size: .82rem;
        }

        .login-input-group input {
            width: 100%;
            min-width: 0;
            height: 41px;
            padding: 0 8px;
            color: #344054;
            background: transparent;
            border: 0;
            outline: 0;
            font-size: .78rem;
        }

        .login-input-group input::placeholder {
            color: #9aa5b4;
            opacity: 1;
        }

        .login-password-toggle {
            display: inline-flex;
            flex: 0 0 auto;
            width: 30px;
            height: 34px;
            align-items: center;
            justify-content: center;
            padding: 0;
            color: #8794a5;
            background: transparent;
            border: 0;
            cursor: pointer;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 2px 0 17px;
            font-size: .7rem;
        }

        .remember-option {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin: 0;
            color: #738096;
            cursor: pointer;
        }

        .remember-option input {
            width: 14px;
            height: 14px;
            margin: 0;
            accent-color: var(--login-blue);
        }

        .forgot-link {
            color: #0876bf;
            font-weight: 700;
            text-decoration: none;
        }

        .forgot-link:hover {
            color: #075b97;
            text-decoration: underline;
        }

        .login-button {
            display: flex;
            width: 100%;
            min-height: 43px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border-radius: 7px;
            font-size: .78rem;
            font-weight: 800;
            cursor: pointer;
            transition: background-color .15s ease, box-shadow .15s ease;
        }

        .login-button {
            color: #fff;
            background: #078cf2;
            border: 1px solid #078cf2;
            box-shadow: 0 3px 7px rgba(7, 140, 242, .12);
        }

        .login-button:hover,
        .login-button:focus {
            background: #087bd0;
            border-color: #087bd0;
        }

        .ui-only-note {
            margin: 12px 0 0;
            color: #98a2b1;
            font-size: .65rem;
            text-align: center;
        }

        * {
            font-family: 'Montserrat', sans-serif;
        }

        html,
        body {
            font-family: 'Montserrat', sans-serif;
        }

        button,
        input,
        select,
        textarea {
            font-family: 'Montserrat', sans-serif;
        }

        @media (max-width: 767.98px) {
            body.login-page {
                background: linear-gradient(145deg, #eaf4ff, #f3f5f8);
            }

            .login-stage {
                min-height: 100vh;
                padding: 22px 14px;
            }

            .login-card {
                grid-template-columns: 1fr;
                width: min(100%, 540px);
                min-height: 0;
                border-radius: 11px;
            }

            .login-brand-panel {
                min-height: 150px;
                padding: 23px 25px;
            }

            .brand-heading {
                max-width: 440px;
                margin-top: 12px;
                font-size: 1rem;
            }

            .integration-card {
                display: none;
            }

            .login-form-panel {
                padding: 28px 25px 30px;
            }

            .login-brand {
                margin-left: 0;
            }
        }

        @media (max-width: 380px) {
            .login-stage {
                padding: 12px 9px;
            }

            .login-brand-panel {
                min-height: 136px;
                padding: 19px 19px;
            }

            .login-form-panel {
                padding: 24px 19px;
            }

            .login-options {
                font-size: .66rem;
            }
        }

        body.login-page {
            background:
                radial-gradient(ellipse at 12% 15%, rgba(212, 236, 255, .95), transparent 35%),
                linear-gradient(145deg, #e8f5ff 0%, #eaf1fc 52%, #dcecff 100%);
        }

        .login-stage {
            padding: 28px 20px;
        }

        .login-card {
            grid-template-columns: 1fr 1fr;
            width: min(100%, 960px);
            min-height: 540px;
            border: 1px solid rgba(220, 232, 247, .9);
            border-radius: 22px;
            box-shadow: 0 20px 50px rgba(48, 86, 139, .16);
        }

        .login-brand-panel {
            position: relative;
            display: block;
            min-width: 0;
            padding: 34px;
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(circle at 90% 8%, rgba(255, 255, 255, .2), transparent 25%),
                linear-gradient(135deg, #0d58d2 0%, #1677f5 58%, #2598ff 100%);
        }

        .login-brand-panel::before,
        .login-brand-panel::after {
            position: absolute;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 50%;
            content: "";
            pointer-events: none;
        }

        .login-brand-panel::before {
            top: -110px;
            right: -52px;
            width: 230px;
            height: 230px;
        }

        .login-brand-panel::after {
            bottom: -155px;
            left: -74px;
            width: 250px;
            height: 250px;
        }

        .login-brand {
            position: relative;
            z-index: 2;
            display: inline-flex;
            min-height: 36px;
            align-items: center;
            padding: 2px 6px;
            background: rgba(255, 255, 255, .96);
            border-radius: 12px;
        }

        .app-logo {
            display: block;
            width: 78px;
            height: 30px;
            object-fit: cover;
            object-position: center;
        }

        .brand-copy {
            position: relative;
            z-index: 2;
            max-width: 360px;
            margin-top: 40px;
        }

        .portal-label {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            color: #fff;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .35);
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .04em;
        }

        .brand-heading {
            margin: 16px 0 0;
            color: #fff;
            font-size: clamp(1.3rem, 2.2vw, 1.8rem);
            font-weight: 800;
            line-height: 1.17;
            letter-spacing: -.03em;
        }

        .login-vector {
            position: absolute;
            z-index: 1;
            right: -3%;
            bottom: -2%;
            width: 106%;
            height: 67%;
            object-fit: cover;
            object-position: center bottom;
            pointer-events: none;
            -webkit-mask-image: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, .18) 8%, #000 34%, #000 100%);
            mask-image: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, .18) 8%, #000 34%, #000 100%);
        }

        .login-form-panel {
            min-width: 0;
            padding: 48px;
        }

        .login-form-content {
            width: 100%;
            max-width: 390px;
        }

        .login-title {
            margin-bottom: 5px;
            font-size: 1.55rem;
            text-align: left;
        }

        .login-subtitle {
            margin-bottom: 22px;
            font-size: .8rem;
            text-align: left;
        }

        .login-field label {
            color: #334155;
            font-size: .76rem;
        }

        .login-input-group {
            min-height: 46px;
            background: #f4f8ff;
            border: 1px solid #dce8f8;
            border-radius: 10px;
        }

        .login-input-group:focus-within {
            border-color: #80b5ff;
            box-shadow: 0 0 0 3px rgba(22, 119, 255, .12);
        }

        .login-input-group input {
            height: 44px;
            color: #334155;
            font-size: .8rem;
        }

        .login-input-group input::placeholder {
            color: #8a9ab0;
        }

        .login-password-toggle {
            border-radius: 8px;
        }

        .login-button {
            min-height: 48px;
            margin-top: 5px;
            color: #fff;
            background: linear-gradient(105deg, #0d58d2 0%, #1677f5 58%, #38a0ff 100%);
            border: 0;
            border-radius: 10px;
            box-shadow: 0 10px 20px -12px rgba(11, 92, 213, .8);
            font-size: .8rem;
        }

        .login-button:hover,
        .login-button:focus {
            color: #fff;
            background: linear-gradient(105deg, #0b4fbf 0%, #126be0 58%, #278eea 100%);
            box-shadow: 0 14px 24px -12px rgba(11, 92, 213, .85);
        }

        .login-forgot {
            margin: 15px 0 0;
            font-size: .75rem;
            text-align: center;
        }

        .login-forgot a {
            color: #1768d2;
            font-weight: 700;
            text-decoration: none;
        }

        .login-forgot a:hover {
            text-decoration: underline;
        }

        .login-footer {
            margin: 20px 0 0;
            color: #8a9ab0;
            font-size: .7rem;
            text-align: center;
        }

        @media (max-width: 700px) {
            body.login-page {
                padding: 18px 14px;
            }

            .login-stage {
                min-height: calc(100vh - 36px);
                padding: 0;
            }

            .login-card {
                grid-template-columns: 1fr;
                width: min(100%, 480px);
                min-height: 0;
                border-radius: 20px;
            }

            .login-brand-panel {
                min-height: 370px;
                padding: 24px;
            }

            .brand-copy {
                margin-top: 30px;
            }

            .brand-heading {
                font-size: 1.55rem;
            }

            .login-vector {
                right: -3%;
                width: 106%;
                height: 65%;
            }

            .login-form-panel {
                padding: 30px 26px;
            }

            .login-form-content {
                max-width: none;
            }

            .login-options {
                font-size: .7rem;
            }
        }

        @media (max-width: 380px) {
            .login-brand-panel {
                min-height: 330px;
                padding: 20px;
            }

            .login-form-panel {
                padding: 26px 20px;
            }
        }
    </style>
    <link rel="stylesheet" href="<?= $e($assetBaseUrl) ?>/css/admin-login-background.css">
</head>
<body class="login-page admin-login-background">

    <main class="login-stage">

        <section class="login-card" aria-label="Login Admin E-PKL">

            <!-- PANEL KIRI -->
            <aside class="login-brand-panel">
                <div class="login-brand">
                    <img
                        src="<?= $e($assetBaseUrl) ?>/img/logo_e-pkl.png"
                        alt="Logo E-PKL"
                        class="app-logo"
                    >
                </div>
                <div class="brand-copy">
                    <span class="portal-label">PORTAL ADMIN</span>
                    <h2 class="brand-heading">Data Rapi,<br>Kontrol Pasti</h2>
                </div>
                <img
                    class="login-vector"
                    src="<?= $e($assetBaseUrl) ?>/img/login-admin-vector.png"
                    alt=""
                    aria-hidden="true"
                >
            </aside>


            <!-- PANEL KANAN -->
            <section class="login-form-panel">

                <div class="login-form-content">

                    <h1 class="login-title">Selamat datang</h1>

                    <p class="login-subtitle">
                        Masuk untuk mengakses dashboard E-PKL
                    </p>


                    <form id="adminLoginForm" method="POST" action="<?= $e($routerUrl) ?>?page=login" autocomplete="on">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

                        <?php if (isset($loginError) && is_string($loginError) && $loginError !== ''): ?>
                            <div class="alert alert-danger small" role="alert">
                                <?= $e($loginError) ?>
                            </div>
                        <?php endif; ?>

                        <!-- USERNAME -->
                        <div class="login-field">

                            <label for="adminUsername">
                                Username atau Email
                            </label>

                            <div class="login-input-group">

                                <i class="far fa-user" aria-hidden="true"></i>

                                <input
                                    id="adminUsername"
                                    name="username"
                                    type="text"
                                    placeholder="Masukkan username atau email"
                                    autocomplete="username"
                                    required
                                >

                            </div>

                        </div>


                        <!-- PASSWORD -->
                        <div class="login-field">

                            <label for="adminPassword">
                                Password
                            </label>

                            <div class="login-input-group">

                                <i class="fas fa-lock" aria-hidden="true"></i>

                                <input
                                    id="adminPassword"
                                    name="password"
                                    type="password"
                                    placeholder="Masukkan password"
                                    autocomplete="current-password"
                                    required
                                >

                                <button
                                    class="login-password-toggle"
                                    type="button"
                                    aria-label="Tampilkan password"
                                    aria-pressed="false"
                                >
                                    <i class="far fa-eye" aria-hidden="true"></i>
                                </button>

                            </div>

                        </div>


                        <!-- BUTTON LOGIN -->
                        <button
                            class="login-button"
                            type="submit"
                        >
                            Masuk

                            <i
                                class="fas fa-arrow-right"
                                aria-hidden="true"
                            ></i>

                        </button>

                        <p class="login-forgot">
                            <a href="<?= $e($routerUrl) ?>?page=forgot-password">Lupa kata sandi?</a>
                        </p>

                        <?php if (is_array($passwordResetFlash ?? null)): ?>
                            <div class="alert alert-<?= ($passwordResetFlash['type'] ?? '') === 'success' ? 'success' : 'danger' ?> small mt-3" role="status">
                                <?= $e($passwordResetFlash['message'] ?? '') ?>
                            </div>
                        <?php endif; ?>
                    </form>

                    <p class="login-footer">E-PKL</p>
                </div>

            </section>

        </section>

    </main>

    <script>
        const passwordToggle = document.querySelector('.login-password-toggle');
        const passwordInput = document.getElementById('adminPassword');

        passwordToggle.addEventListener('click', () => {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', String(showPassword));
            passwordToggle.setAttribute('aria-label', showPassword ? 'Sembunyikan password' : 'Tampilkan password');
            passwordToggle.innerHTML = `<i class="far ${showPassword ? 'fa-eye-slash' : 'fa-eye'}" aria-hidden="true"></i>`;
        });

    </script>
</body>
</html>
