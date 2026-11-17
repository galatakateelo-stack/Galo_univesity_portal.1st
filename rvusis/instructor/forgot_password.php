
<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($username === '' || $newPassword === '' || $confirmPassword === '') {
        $message = 'Please complete all fields.';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 12) {
        $message = 'Your new password must contain at least 12 characters.';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'The passwords do not match.';
        $messageType = 'error';
    } else {
        /*
         * SECURITY:
         * Do not update a password using only a username.
         * A secure reset requires a verified, single-use recovery token
         * delivered to an instructor's verified email address, or an
         * administrator-approved identity verification process.
         *
         * Configure that verification process before enabling password
         * changes on this page.
         */
        $message = 'For your security, instructor identity verification must be configured before a password can be reset. Please contact your authorized campus administrator.';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Password Recovery | RVU SIS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #102a43, #174a73);
        }

        .recovery-card {
            width: 100%;
            max-width: 460px;
            padding: 34px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, .25);
        }

        .brand {
            text-align: center;
            margin-bottom: 26px;
        }

        .brand img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 12px;
        }

        .brand h1 {
            color: #17365d;
            font-size: 23px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .brand p {
            color: #64748b;
            font-size: 13px;
            margin: 0;
        }

        .form-label {
            color: #243b53;
            font-weight: 600;
        }

        .form-control {
            padding: 12px;
            border-radius: 9px;
        }

        .btn-reset {
            width: 100%;
            padding: 12px;
            border: 0;
            border-radius: 9px;
            background: #17365d;
            color: #fff;
            font-weight: 700;
        }

        .btn-reset:hover {
            background: #24527a;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #17365d;
            font-weight: 600;
        }

        .security-note {
            font-size: 12px;
            line-height: 1.6;
            color: #64748b;
            margin-top: 18px;
        }
    </style>
</head>
<body>
    <main class="recovery-card">
        <header class="brand">
            <img src="../assets/rvu.logo.webp"
                 alt="Rift Valley University Logo"
                 onerror="this.style.display='none'">

            <h1>Instructor Password Recovery</h1>
            <p>Rift Valley University — Abichu Campus</p>
        </header>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?=
                $messageType === 'error' ? 'warning' : 'success'
            ?>" role="alert">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">
                    Instructor Username
                </label>
                <input type="text"
                       id="username"
                       name="username"
                       class="form-control"
                       autocomplete="username"
                       required>
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label">
                    New Password
                </label>
                <input type="password"
                       id="new_password"
                       name="new_password"
                       class="form-control"
                       minlength="12"
                       autocomplete="new-password"
                       required>
                <div class="form-text">
                    Use at least 12 characters.
                </div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label">
                    Confirm New Password
                </label>
                <input type="password"
                       id="confirm_password"
                       name="confirm_password"
                       class="form-control"
                       minlength="12"
                       autocomplete="new-password"
                       required>
            </div>

            <button type="submit" class="btn-reset">
                Reset Password
            </button>
        </form>

        <p class="security-note">
            Password changes are disabled until a secure instructor
            identity-verification method is configured. Contact your
            authorized campus administrator for assistance.
        </p>

        <a href="login.php" class="back-link">
            &larr; Back to Instructor Login
        </a>
    </main>
</body>
</html>
