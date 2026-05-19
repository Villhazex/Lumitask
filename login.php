<?php
session_start();
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login — Luminous Zine</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Anybody:wght@300;400;600;700;800;900&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<!-- ═══ LEFT PANEL ═══ -->
<div class="panel-left">
    <div class="issue-tag">
        ✦ Luminous &nbsp;·&nbsp; Vol. <?php echo date('Y'); ?>
    </div>

    <div class="hero-text">
        <div class="hero-eyebrow">Selamat datang kembali</div>

        <div class="hero-title">
            <div class="ghost" aria-hidden="true">
                To do<br><em>list</em>
            </div>

            <div>
                To do<br><em>list</em>
            </div>
        </div>

        <p class="hero-desc">
            Kelola tugas harian, pantau progress, dan rayakan setiap pencapaian bersama Luminous.
        </p>

        <div class="feature-list">

            <div class="feature-item">
                <div class="feature-icon">◉</div>
                <span class="feature-label">
                    Progress tracker visual & realtime
                </span>
            </div>

            <div class="feature-item">
                <div class="feature-icon">★</div>
                <span class="feature-label">
                    Filter prioritas & kategori tugas
                </span>
            </div>

            <div class="feature-item">
                <div class="feature-icon">✦</div>
                <span class="feature-label">
                    Desain zine — fungsional & estetik
                </span>
            </div>

        </div>
    </div>

    <div class="panel-left-footer">
        Tetap Semangat, pantang menyerah ✦
    </div>
</div>

<!-- ═══ RIGHT PANEL ═══ -->
<div class="panel-right">

    <div class="corner-tl"></div>
    <div class="corner-br"></div>

    <div class="form-wrap">

        <div class="form-tag">✦ Sign In</div>

        <div class="form-headline">
            Masuk ke<br>akun<em>mu</em>
        </div>

        <p class="form-sub">
            <?php echo date('d M Y'); ?>
            &nbsp;·&nbsp;
            Mulai produktif hari ini
        </p>

        <?php if ($error) { ?>
            <div class="alert-error">
                ⚠ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <form action="proses_login.php" method="POST">

            <div class="form-group">
                <label class="form-label" for="username">
                    Username
                </label>

                <input
                    class="form-input"
                    type="text"
                    id="username"
                    name="username"
                    placeholder="masukkan username..."
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group">

                <label class="form-label" for="password">
                    Password
                </label>

                <div class="pw-wrap">

                    <input
                        class="form-input"
                        type="password"
                        id="password"
                        name="password"
                        placeholder="masukkan password..."
                        required
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        class="pw-toggle"
                        id="pwToggle"
                        title="Tampilkan password"
                    >
                        Show
                    </button>

                </div>
            </div>

            <button class="form-submit" type="submit">
                ✦ Masuk Sekarang
            </button>

        </form>

        <div class="divider">
            <div class="divider-line"></div>
            <span class="divider-text">atau</span>
            <div class="divider-line"></div>
        </div>

        <div class="register-row">
            <p>Belum punya akun?</p>

            <a href="register.php" class="register-btn">
                + Daftar Sekarang
            </a>
        </div>

    </div>
</div>

<script src="js/login.js"></script>

</body>
</html>


