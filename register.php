<?php
session_start();

$error = $_SESSION['register_error'] ?? null;
$success = $_SESSION['register_success'] ?? null;

unset($_SESSION['register_error'], $_SESSION['register_success']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Daftar — Luminous Zine</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Anybody:wght@300;400;600;700;800;900&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

<link rel="stylesheet" href="css/register.css">
</head>

<body>

<!-- ═══ LEFT PANEL ═══ -->
<div class="panel-left">

    <div class="issue-tag">
        ✦ Luminous &nbsp;·&nbsp; Vol. <?php echo date('Y'); ?>
    </div>

    <div class="hero-text">

        <div class="hero-eyebrow">
            Mulai perjalananmu
        </div>

        <div class="hero-title">

            <div class="ghost" aria-hidden="true">
                Buat<br><em>akun</em>
            </div>

            <div>
                Buat<br><em>akun</em>
            </div>

        </div>

        <p class="hero-desc">
            Bergabung dan mulai kelola tugasmu dengan tampilan yang estetik dan sistem yang terstruktur.
        </p>

        <div class="steps">

            <div class="step">
                <div class="step-num">01</div>

                <div class="step-content">
                    <div class="step-title">Buat identitas akun</div>

                    <div class="step-desc">
                        Username, email, dan password
                    </div>
                </div>
            </div>

            <div class="step">
                <div class="step-num">02</div>

                <div class="step-content">
                    <div class="step-title">Masuk ke dashboard</div>

                    <div class="step-desc">
                        Langsung akses semua fitur manajemen tugas
                    </div>
                </div>
            </div>

            <div class="step">
                <div class="step-num">03</div>

                <div class="step-content">
                    <div class="step-title">Mulai produktif</div>

                    <div class="step-desc">
                        Tambah, kelola, dan selesaikan tugasmu
                    </div>
                </div>
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

        <div class="form-tag">✦ Sign Up</div>

        <div class="form-headline">
            Daftar akun<br><em>baru</em>
        </div>

        <p class="form-sub">
            <?php echo date('d M Y'); ?>
            &nbsp;·&nbsp;
            Gratis & Mudah
        </p>

        <?php if ($error) { ?>
            <div class="alert-error">
                ⚠ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <?php if ($success) { ?>
            <div class="alert-success">
                ✓ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php } ?>

        <form action="proses_register.php" method="POST" id="regForm" novalidate>

            <div class="form-grid">

                <!-- Username -->
                <div class="form-group full">

                    <label class="form-label" for="username">
                        Username
                    </label>

                    <input
                        class="form-input"
                        type="text"
                        id="username"
                        name="username"
                        placeholder="pilih username unikmu..."
                        required
                        autocomplete="username"
                        minlength="3"
                    >

                    <span class="form-hint">
                        Minimal 3 karakter, tanpa spasi
                    </span>
                </div>

                <!-- Email -->
                <div class="form-group full">

                    <label class="form-label" for="email">
                        Email
                    </label>

                    <input
                        class="form-input"
                        type="email"
                        id="email"
                        name="email"
                        placeholder="email@contoh.com"
                        required
                        autocomplete="email"
                    >
                </div>

                <!-- Password -->
                <div class="form-group full">

                    <label class="form-label" for="password">
                        Password
                    </label>

                    <div class="pw-wrap">

                        <input
                            class="form-input"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="minimal 8 karakter huruf dan angka..."
                            required
                            autocomplete="new-password"
                            minlength="8"
                            pattern="(?=.*[A-Za-z])(?=.*\d).{8,}"
                        >

                        <button
                            type="button"
                            class="pw-toggle"
                            id="pwToggle1"
                            title="Tampilkan password"
                        >
                            👁
                        </button>

                    </div>

                    <span class="form-hint">
                        Minimal 8 karakter, gabungan huruf dan angka
                    </span>
                </div>

                <!-- Confirm Password -->
                <div class="form-group full">

                    <label class="form-label" for="confirm_password">
                        Konfirmasi Password
                    </label>

                    <div class="pw-wrap">

                        <input
                            class="form-input"
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="ulangi password..."
                            required
                            autocomplete="new-password"
                        >

                        <button
                            type="button"
                            class="pw-toggle"
                            id="pwToggle2"
                            title="Tampilkan password"
                        >
                            👁
                        </button>

                    </div>

                    <span class="form-hint" id="matchHint"></span>
                </div>

            </div>

            <button class="form-submit" type="submit" id="submitBtn">
                ✦ Buat Akun Sekarang
            </button>

        </form>

        <div class="divider">
            <div class="divider-line"></div>
            <span class="divider-text">sudah punya akun?</span>
            <div class="divider-line"></div>
        </div>

        <div class="login-row">
            <a href="login.php" class="login-btn">
                → Masuk Sekarang
            </a>
        </div>

    </div>
</div>

<script src="js/register.js">
    
</script>

</body>
</html>
