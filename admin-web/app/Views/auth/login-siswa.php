<?php
$pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Login Siswa';
$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$scriptDirectory = rtrim($scriptDirectory, '/');
$scriptDirectory = $scriptDirectory === '.' ? '' : $scriptDirectory;
$assetBaseUrl = isset($assetBaseUrl) && is_string($assetBaseUrl)
    ? rtrim($assetBaseUrl, '/')
    : $scriptDirectory . '/assets';
$showError = isset($_GET['state']) && is_string($_GET['state']) && $_GET['state'] === 'error';
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Login siswa E-PKL">
    <title><?= $e($pageTitle) ?> | E-PKL</title>
    <link rel="stylesheet" href="<?= $e($assetBaseUrl) ?>/vendor/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?= $e($assetBaseUrl) ?>/css/sb-admin-2.min.css">
    <style>
        :root {
            color-scheme: light;
            --student-blue: #168bf0;
            --student-blue-dark: #2869de;
            --student-ink: #24354d;
            --student-muted: #8491a3;
            --student-input: #f2f6fd;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body.student-login-page {
            min-height: 100vh;
            margin: 0;
            padding: 28px 16px;
            color: var(--student-ink);
            background:
                radial-gradient(ellipse at 12% 15%, rgba(212, 236, 255, .95), transparent 35%),
                linear-gradient(145deg, #e8f5ff 0%, #eaf1fc 52%, #dcecff 100%);
        }

        .student-login-stage {
            display: grid;
            min-height: calc(100vh - 56px);
            place-items: center;
        }

        .student-login-card {
            width: min(100%, 440px);
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(220, 232, 247, .9);
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(48, 86, 139, .16);
        }

        .student-login-header {
            position: relative;
            min-height: 278px;
            padding: 30px 32px 66px;
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(circle at 90% 8%, rgba(255, 255, 255, .2), transparent 25%),
                linear-gradient(135deg, #289cf2 0%, #247fe7 53%, #476de0 100%);
            text-align: center;
        }

        .student-login-header::before,
        .student-login-header::after {
            position: absolute;
            border: 1px solid rgba(255, 255, 255, .13);
            border-radius: 50%;
            content: "";
            pointer-events: none;
        }

        .student-login-header::before {
            top: -105px;
            right: -58px;
            width: 230px;
            height: 230px;
        }

        .student-login-header::after {
            right: -19px;
            bottom: -142px;
            width: 250px;
            height: 250px;
        }

        .student-school-mark {
            position: relative;
            z-index: 1;
            display: grid;
            width: 84px;
            height: 84px;
            margin: 0 auto 17px;
            place-items: center;
            color: #147fe3;
            background: #fff;
            border: 5px solid rgba(255, 255, 255, .32);
            border-radius: 50%;
            box-shadow: 0 7px 18px rgba(25, 71, 143, .19);
        }

        .student-school-mark span {
            display: block;
            font-size: 1.04rem;
            font-weight: 900;
            letter-spacing: -.08em;
            line-height: 1;
        }

        .student-school-mark i {
            margin-bottom: 3px;
            font-size: 1.25rem;
        }

        .student-login-heading {
            position: relative;
            z-index: 1;
            margin: 0 0 7px;
            color: #fff;
            font-size: 1.42rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .student-login-intro {
            position: relative;
            z-index: 1;
            max-width: 315px;
            margin: 0 auto;
            color: rgba(255, 255, 255, .9);
            font-size: .84rem;
            line-height: 1.55;
        }

        .student-login-form-card {
            position: relative;
            z-index: 2;
            margin: -35px 18px 18px;
            padding: 25px 25px 24px;
            background: #fff;
            border: 1px solid #edf1f7;
            border-radius: 13px;
            box-shadow: 0 9px 24px rgba(41, 71, 113, .1);
        }

        .student-welcome-heading {
            margin: 0 0 5px;
            color: #28384f;
            font-size: 1.14rem;
            font-weight: 800;
        }

        .student-welcome-copy {
            margin: 0 0 20px;
            color: var(--student-muted);
            font-size: .8rem;
        }

        .student-login-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 0 0 17px;
            padding: 11px 12px;
            color: #a33d4b;
            background: #fff0f1;
            border: 1px solid #ffd9dd;
            border-radius: 8px;
            font-size: .76rem;
            font-weight: 600;
            line-height: 1.45;
        }

        .student-login-error i {
            flex: 0 0 auto;
            margin-top: 2px;
            color: #d44e5e;
            font-size: .92rem;
        }

        .student-login-field {
            margin-bottom: 15px;
        }

        .student-login-field label {
            display: block;
            margin-bottom: 7px;
            color: #46556a;
            font-size: .76rem;
            font-weight: 700;
        }

        .student-login-input-wrap {
            display: flex;
            min-height: 46px;
            align-items: center;
            padding: 0 13px;
            color: #8896a9;
            background: var(--student-input);
            border: 1px solid #e9eff8;
            border-radius: 8px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .student-login-input-wrap:focus-within {
            background: #fff;
            border-color: #7cb8f5;
            box-shadow: 0 0 0 .18rem rgba(22, 139, 240, .12);
        }

        .student-login-input-wrap > i {
            flex: 0 0 auto;
            width: 18px;
            font-size: .83rem;
        }

        .student-login-input-wrap input {
            width: 100%;
            min-width: 0;
            height: 44px;
            padding: 0 9px;
            color: #344054;
            background: transparent;
            border: 0;
            outline: 0;
            font-size: .8rem;
        }

        .student-login-input-wrap input::placeholder {
            color: #a0abba;
            opacity: 1;
        }

        .student-password-toggle {
            display: inline-flex;
            flex: 0 0 auto;
            width: 32px;
            height: 36px;
            align-items: center;
            justify-content: center;
            padding: 0;
            color: #8794a5;
            background: transparent;
            border: 0;
            cursor: pointer;
        }

        .student-login-button {
            display: flex;
            width: 100%;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-top: 5px;
            color: #fff;
            background: linear-gradient(100deg, #168bf0 0%, #2876e8 100%);
            border: 1px solid #2181eb;
            border-radius: 8px;
            box-shadow: 0 5px 12px rgba(30, 121, 226, .2);
            font-size: .84rem;
            font-weight: 700;
            cursor: pointer;
            transition: filter .15s ease, box-shadow .15s ease;
        }

        .student-login-button:hover,
        .student-login-button:focus {
            filter: brightness(.96);
            box-shadow: 0 6px 15px rgba(30, 121, 226, .26);
        }

        .student-login-button:focus-visible,
        .student-password-toggle:focus-visible {
            outline: 3px solid rgba(22, 139, 240, .35);
            outline-offset: 2px;
        }

        .student-login-caption {
            margin: 15px 0 0;
            color: #9aa5b3;
            font-size: .68rem;
            text-align: center;
        }

        @media (max-width: 480px) {
            body.student-login-page {
                padding: 16px 12px;
            }

            .student-login-stage {
                min-height: calc(100vh - 32px);
            }

            .student-login-card {
                width: min(100%, 420px);
                border-radius: 15px;
            }

            .student-login-header {
                min-height: 258px;
                padding: 26px 23px 59px;
            }

            .student-school-mark {
                width: 76px;
                height: 76px;
                margin-bottom: 14px;
            }

            .student-login-heading {
                font-size: 1.3rem;
            }

            .student-login-intro {
                font-size: .8rem;
            }

            .student-login-form-card {
                margin: -31px 12px 12px;
                padding: 22px 19px 20px;
            }
        }

        @media (max-width: 340px) {
            body.student-login-page {
                padding-right: 8px;
                padding-left: 8px;
            }

            .student-login-form-card {
                margin-right: 9px;
                margin-left: 9px;
                padding-right: 15px;
                padding-left: 15px;
            }
        }
    </style>
</head>
<body class="student-login-page">
    <main class="student-login-stage">
        <section class="student-login-card" aria-label="Login siswa E-PKL">
            <header class="student-login-header">
                <div class="student-school-mark" role="img" aria-label="Logo E-PKL">
                    <span>
                        <i class="fas fa-graduation-cap" aria-hidden="true"></i><br>
                        EPKL
                    </span>
                </div>
                <h1 class="student-login-heading">Pantau PKL Anda</h1>
                <p class="student-login-intro">
                    Pantau presensi dan aktivitas PKL Anda dengan mudah melalui satu akun.
                </p>
            </header>

            <section class="student-login-form-card" aria-labelledby="studentWelcome">
                <?php if ($showError): ?>
                    <div class="student-login-error" role="alert" aria-live="polite">
                        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                        <span><?= $e('masukan ulang username dan password !') ?></span>
                    </div>
                <?php endif; ?>

                <h2 class="student-welcome-heading" id="studentWelcome">Selamat Datang</h2>
                <p class="student-welcome-copy">Silakan masuk dengan akun Anda.</p>

                <form id="studentLoginForm" autocomplete="on">
                    <div class="student-login-field">
                        <label for="studentUsername">Username / NISN</label>
                        <div class="student-login-input-wrap">
                            <i class="far fa-user" aria-hidden="true"></i>
                            <input id="studentUsername" name="username" type="text"
                                   placeholder="Masukkan username atau NISN"
                                   autocomplete="username">
                        </div>
                    </div>

                    <div class="student-login-field">
                        <label for="studentPassword">Password</label>
                        <div class="student-login-input-wrap">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <input id="studentPassword" name="password" type="password"
                                   placeholder="Masukkan password"
                                   autocomplete="current-password">
                            <button class="student-password-toggle" type="button"
                                    aria-label="Tampilkan password" aria-pressed="false">
                                <i class="far fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button class="student-login-button" type="button">
                        Masuk
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <p class="student-login-caption">Portal Siswa E-PKL</p>
            </section>
        </section>
    </main>

    <script>
        const passwordToggle = document.querySelector('.student-password-toggle');
        const passwordInput = document.getElementById('studentPassword');

        passwordToggle.addEventListener('click', () => {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', String(showPassword));
            passwordToggle.setAttribute('aria-label', showPassword ? 'Sembunyikan password' : 'Tampilkan password');
            passwordToggle.innerHTML = `<i class="far ${showPassword ? 'fa-eye-slash' : 'fa-eye'}" aria-hidden="true"></i>`;
        });

        document.getElementById('studentLoginForm').addEventListener('submit', (event) => {
            event.preventDefault();
        });
    </script>
</body>
</html>
