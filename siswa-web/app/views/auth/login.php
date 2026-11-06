<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f1f6ff">
    <title>Masuk - <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <style>
        .login-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 20px;
        }

        .login-shell {
            width: 100%;
            max-width: 960px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 28px;
            background: var(--card);
            box-shadow: var(--shadow);
        }

        .login-intro {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 540px;
            padding: 42px;
            color: #fff;
            background: var(--grad);
        }

        .login-brand {
            display: flex;
            align-items: center;
            width: fit-content;
            min-height: 42px;
            padding: 3px 7px;
            border-radius: 14px;
            background: rgba(255,255,255,.96);
        }

        .login-brand img {
            display: block;
            width: 96px;
            height: 36px;
            object-fit: cover;
            object-position: center;
        }

        .login-intro-copy {
            max-width: 360px;
            margin: auto 0;
            padding: 48px 0;
        }

        .login-eyebrow {
            display: inline-block;
            margin-bottom: 16px;
            padding: 5px 12px;
            border: 1px solid rgba(255,255,255,.35);
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            font-size: 12px;
            font-weight: 700;
        }

        .login-intro h1 {
            font-size: clamp(30px, 4vw, 42px);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -.03em;
        }

        .login-intro p {
            margin-top: 16px;
            color: rgba(255,255,255,.88);
            font-size: 14px;
            line-height: 1.75;
        }

        .login-intro-foot {
            color: rgba(255,255,255,.78);
            font-size: 12px;
        }

        .login-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px;
        }

        .login-panel h2 {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .login-description {
            margin-top: 8px;
            margin-bottom: 28px;
            color: var(--muted);
        }

        .login-field {
            margin-bottom: 18px;
        }

        .login-field label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input {
            width: 100%;
            min-height: 50px;
            padding: 12px 14px;
            border: 1.5px solid var(--line);
            border-radius: 12px;
            outline: 0;
            background: #f7faff;
            color: var(--text);
            font: inherit;
            transition: border-color .2s, box-shadow .2s;
        }

        .login-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(22,119,255,.12);
        }

        .login-input[aria-invalid="true"] {
            border-color: var(--danger);
        }

        .login-password-input {
            padding-right: 54px;
        }

        .login-password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
        }

        .login-password-toggle:hover {
            background: #eaf1ff;
            color: var(--blue-2);
        }

        .login-password-toggle svg {
            width: 20px;
            height: 20px;
        }

        .login-error {
            display: block;
            min-height: 0;
            margin-top: 5px;
            color: #be123c;
            font-size: 12px;
        }

        .login-error:empty {
            display: none;
        }

        .login-submit {
            width: 100%;
            min-height: 50px;
            margin-top: 6px;
            border: 0;
            border-radius: 12px;
            background: var(--grad);
            box-shadow: 0 10px 20px -12px rgba(11,92,213,.8);
            color: #fff;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
        }

        .login-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px -12px rgba(11,92,213,.85);
        }

        .login-submit:focus-visible {
            outline: 3px solid rgba(22,119,255,.35);
            outline-offset: 3px;
        }

        .login-message {
            margin-top: 16px;
            padding: 12px 14px;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 13px;
        }

        .login-message:empty {
            display: none;
        }

        .login-footer {
            margin-top: 24px;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 700px) {
            .login-page {
                padding: 18px 14px;
            }

            .login-shell {
                max-width: 480px;
                grid-template-columns: 1fr;
                border-radius: 22px;
            }

            .login-intro {
                min-height: 0;
                padding: 24px;
            }

            .login-intro-copy {
                padding: 30px 0 10px;
            }

            .login-intro h1 {
                font-size: 29px;
            }

            .login-intro p {
                margin-top: 10px;
                font-size: 13px;
            }

            .login-intro-foot {
                display: none;
            }

            .login-panel {
                padding: 28px 24px 30px;
            }
        }

        @media (max-width: 360px) {
            .login-intro,
            .login-panel {
                padding-right: 18px;
                padding-left: 18px;
            }
        }

        @media (max-width: 560px) {
            .login-brand {
                min-height: 36px;
                padding: 2px 6px;
                border-radius: 10px;
            }

            .login-brand img {
                width: 78px;
                height: 30px;
            }
        }
    </style>
</head>
<body>
<div class="bg-deco" aria-hidden="true"></div>
<main class="login-page">
    <div class="login-shell">
        <section class="login-intro" aria-label="Tentang E-PKL">
            <div class="login-brand">
                <img src="<?= e(asset('img/logo_e-pkl.png')) ?>" alt="Logo E-PKL">
            </div>
            <div class="login-intro-copy">
                <span class="login-eyebrow">PORTAL SISWA</span>
                <h1>Pantau perjalanan PKL-mu.</h1>
                <p>Masuk untuk melihat informasi dan aktivitas Praktik Kerja Lapangan dalam satu tempat.</p>
            </div>
            <p class="login-intro-foot">Direktorat Pembinaan SMK</p>
        </section>

        <section class="login-panel" aria-labelledby="loginTitle">
            <h2 id="loginTitle">Selamat datang</h2>
            <p class="login-description">Masuk menggunakan email atau username yang terdaftar.</p>

            <form class="login-form" method="POST" action="<?= e(url('login')) ?>" aria-labelledby="loginTitle">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <div class="login-field">
                    <label for="loginIdentifier">Email atau username</label>
                    <input
                        class="login-input"
                        id="loginIdentifier"
                        name="identifier"
                        type="text"
                        value="<?= e($identifier ?? '') ?>"
                        autocomplete="username"
                        placeholder="Masukkan email atau username"
                        aria-describedby="identifierError"
                        required
                    >
                    <span class="login-error" id="identifierError"></span>
                </div>

                <div class="login-field">
                    <label for="loginPassword">Password</label>
                    <div class="login-input-wrap">
                        <input
                            class="login-input login-password-input"
                            id="loginPassword"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Masukkan password"
                            aria-describedby="passwordError"
                            required
                        >
                        <button
                            class="login-password-toggle"
                            id="togglePassword"
                            type="button"
                            aria-label="Tampilkan password"
                            aria-pressed="false"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <span class="login-error" id="passwordError"></span>
                </div>

                <button class="login-submit" id="loginSubmit" type="submit">Masuk</button>

                <?php if (!empty($error)): ?>
                    <p class="login-message" role="alert"><?= e($error) ?></p>
                <?php endif; ?>
            </form>

            <p class="login-footer">E-PKL</p>
            </section>
    </div>
</main>
<script>
(function () {
    var password = document.getElementById('loginPassword');
    var toggle = document.getElementById('togglePassword');

    toggle.addEventListener('click', function () {
        var shouldShow = password.type === 'password';

        password.type = shouldShow ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(shouldShow));
        toggle.setAttribute(
            'aria-label',
            shouldShow ? 'Sembunyikan password' : 'Tampilkan password'
        );
    });
})();
</script>
</body>
</html>
