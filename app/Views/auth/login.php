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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        body.login-page {
            min-height: 100vh;
            margin: 0;
            color: var(--login-ink);
            background:
                radial-gradient(ellipse at 15% 82%, rgba(180, 205, 255, .4), transparent 30%),
                linear-gradient(125deg, #eaf4ff 0%, #f3f5f8 54%, #dceaff 100%);
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
            flex-direction: column;
            justify-content: space-between;
            padding: 34px 32px 30px;
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

        .integration-card {
            padding: 19px 18px 15px;
            background: rgba(255, 255, 255, .95);
            border: 1px solid rgba(224, 234, 248, .85);
            border-radius: 11px;
            box-shadow: 0 8px 20px rgba(41, 69, 106, .06);
        }

        .integration-main {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .integration-icon {
            display: inline-flex;
            flex: 0 0 36px;
            width: 36px;
            height: 36px;
            align-items: center;
            justify-content: center;
            color: #1488df;
            background: #e5f1ff;
            border-radius: 8px;
        }

        .integration-title {
            margin: 0 0 3px;
            color: #334257;
            font-size: .86rem;
            font-weight: 800;
        }

        .integration-copy {
            margin: 0;
            color: var(--login-muted);
            font-size: .72rem;
        }

        .integration-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 17px;
            color: #657386;
            font-size: .65rem;
        }

        .integration-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .integration-status i,
        .integration-version {
            color: #1679bd;
        }

        .login-form-panel {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 42px 40px;
        }

        .login-form-content {
            width: 100%;
            max-width: 506px;
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 0 0 13px 16px;
        }

        .brand-wordmark {
            color: #087fe4;
            background: linear-gradient(115deg, #087ce0 12%, #18b8ec 52%, #0879df 85%);
            background-clip: text;
            font-size: 2.1rem;
            font-style: italic;
            font-weight: 900;
            letter-spacing: -.105em;
            line-height: 1;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-badge {
            padding: 4px 10px;
            color: #176db7;
            background: #e2f0ff;
            border-radius: 4px;
            font-size: .65rem;
            font-weight: 800;
            letter-spacing: .035em;
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

        .login-button,
        .google-button {
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

        .login-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 18px 0;
            color: #8a95a4;
            font-size: .63rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .login-divider::before,
        .login-divider::after {
            height: 1px;
            flex: 1;
            background: #e7edf4;
            content: "";
        }

        .google-button {
            color: #455468;
            background: #eef3ff;
            border: 1px solid #eef3ff;
        }

        .google-button:hover,
        .google-button:focus {
            background: #e4edff;
            border-color: #d9e5fb;
        }

        .google-mark {
            color: #4285f4;
            font-size: .95rem;
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
    </style>
</head>
<body class="login-page">
    <main class="login-stage">
        <section class="login-card" aria-label="Login Admin E-PKL">
            <aside class="login-brand-panel">
                <div>
                    <span class="portal-label">
                        <i class="fas fa-circle" aria-hidden="true"></i>
                        Portal Resmi E-PKL
                    </span>
                    <h1 class="brand-heading">Sistem Pengelolaan Praktik Kerja Lapangan (E-PKL)</h1>
                </div>

                <section class="integration-card" aria-label="Informasi integrasi">
                    <div class="integration-main">
                        <span class="integration-icon">
                            <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h2 class="integration-title">Sinkronisasi Dapodik</h2>
                            <p class="integration-copy">Integrasi data guru &amp; siswa berkala</p>
                        </div>
                    </div>
                    <div class="integration-footer">
                        <span class="integration-status">
                            <i class="far fa-check-circle" aria-hidden="true"></i>
                            Status Server: Aktif
                        </span>
                        <span class="integration-version">v3.4.2</span>
                    </div>
                </section>
            </aside>

            <section class="login-form-panel">
                <div class="login-form-content">
                    <div class="login-brand" aria-label="EPKL Admin">
                        <span class="brand-wordmark" aria-hidden="true">EPKL</span>
                        <span class="admin-badge">ADMIN</span>
                    </div>

                    <div class="login-notice" role="note">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <span>Gunakan akun Administrator yang telah terdaftar di sistem Sekolah.</span>
                    </div>

                    <form id="adminLoginForm" autocomplete="on">
                        <div class="login-field">
                            <label for="adminUsername">Username</label>
                            <div class="login-input-group">
                                <i class="far fa-envelope" aria-hidden="true"></i>
                                <input id="adminUsername" name="username" type="text"
                                       placeholder="Masukan Username." autocomplete="username">
                            </div>
                        </div>

                        <div class="login-field">
                            <label for="adminPassword">Password</label>
                            <div class="login-input-group">
                                <i class="fas fa-lock" aria-hidden="true"></i>
                                <input id="adminPassword" name="password" type="password"
                                       placeholder="Masukan Password." autocomplete="current-password">
                                <button class="login-password-toggle" type="button"
                                        aria-label="Tampilkan password" aria-pressed="false">
                                    <i class="far fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="login-options">
                            <label class="remember-option" for="rememberAdmin">
                                <input id="rememberAdmin" name="remember" type="checkbox">
                                <span>Ingat Saya</span>
                            </label>
                            <a class="forgot-link" href="#" aria-label="Lupa password">Lupa Password?</a>
                        </div>

                        <button class="login-button" type="button">
                            Masuk ke Dashboard
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>

                        <div class="login-divider">ATAU MASUK VIA SSO</div>

                        <button class="google-button" type="button">
                            <i class="fab fa-google google-mark" aria-hidden="true"></i>
                            Masuk dengan Akun Google
                        </button>
                    </form>

                    <p class="ui-only-note">Tampilan login administrator</p>
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

        document.getElementById('adminLoginForm').addEventListener('submit', (event) => {
            event.preventDefault();
        });
    </script>
</body>
</html>
