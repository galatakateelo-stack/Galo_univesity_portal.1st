<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (empty($_SESSION['admin_recovery_csrf'])) {
    $_SESSION['admin_recovery_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['admin_recovery_csrf'], $submittedToken)) {
        $message = 'Your request could not be validated. Refresh the page and try again.';
        $messageType = 'danger';
    } else {
        $now = time();
        $attempts = $_SESSION['admin_recovery_attempts'] ?? [];
        $attempts = array_values(array_filter(
            is_array($attempts) ? $attempts : [],
            static function ($timestamp) use ($now) {
                return is_int($timestamp) && $timestamp > ($now - 900);
            }
        ));

        if (count($attempts) >= 5) {
            $message = 'Too many attempts. Please wait 15 minutes or contact the campus system administrator.';
            $messageType = 'warning';
        } else {
            $attempts[] = $now;
            $_SESSION['admin_recovery_attempts'] = $attempts;

            $genericResponse = 'If the details match an administrator account, the recovery request has been submitted for review. An authorized administrator must verify your identity before any password is changed.';

            // Silently accept automated submissions that fill the hidden field.
            if (trim(is_string($_POST['website'] ?? null) ? $_POST['website'] : '') !== '') {
                $_SESSION['admin_recovery_message'] = [
                    'text' => $genericResponse,
                    'type' => 'success',
                ];
                header('Location: forgot_password.php', true, 303);
                exit;
            }

            $username = trim(is_string($_POST['username'] ?? null) ? $_POST['username'] : '');
            $fullName = trim(is_string($_POST['full_name'] ?? null) ? $_POST['full_name'] : '');

            if ($username === '' || $fullName === '') {
                $message = 'Enter both your administrator username and full name.';
                $messageType = 'danger';
            } elseif (strlen($username) > 50 || strlen($fullName) > 300) {
                $message = 'The information entered is not valid. Please check it and try again.';
                $messageType = 'danger';
            } else {
                try {
                    $lookup = mysqli_prepare(
                        $conn,
                        'SELECT admin_id, username, full_name FROM admins WHERE username = ? AND full_name = ? LIMIT 1'
                    );
                    if (!$lookup) {
                        throw new RuntimeException('Unable to prepare administrator lookup.');
                    }
                    mysqli_stmt_bind_param($lookup, 'ss', $username, $fullName);
                    mysqli_stmt_execute($lookup);
                    $lookupResult = mysqli_stmt_get_result($lookup);
                    $admin = $lookupResult ? mysqli_fetch_assoc($lookupResult) : null;
                    mysqli_stmt_close($lookup);

                    $adminId = $admin ? (int)$admin['admin_id'] : null;
                    $savedUsername = $admin ? $admin['username'] : $username;
                    $savedFullName = $admin ? $admin['full_name'] : $fullName;
                    if ($adminId !== null) {
                        $pending = mysqli_prepare(
                            $conn,
                            "SELECT request_id FROM admin_password_reset_requests WHERE admin_id = ? AND status = 'pending' LIMIT 1"
                        );
                        if (!$pending) {
                            throw new RuntimeException('Unable to check pending administrator requests.');
                        }
                        mysqli_stmt_bind_param($pending, 'i', $adminId);
                    } else {
                        $pending = mysqli_prepare(
                            $conn,
                            "SELECT request_id FROM admin_password_reset_requests WHERE admin_id IS NULL AND submitted_username = ? AND submitted_full_name = ? AND status = 'pending' LIMIT 1"
                        );
                        if (!$pending) {
                            throw new RuntimeException('Unable to check pending administrator requests.');
                        }
                        mysqli_stmt_bind_param($pending, 'ss', $username, $fullName);
                    }
                    mysqli_stmt_execute($pending);
                    $pendingResult = mysqli_stmt_get_result($pending);
                    $alreadyPending = $pendingResult && mysqli_num_rows($pendingResult) > 0;
                    mysqli_stmt_close($pending);

                    if (!$alreadyPending) {
                        if ($adminId !== null) {
                            $insert = mysqli_prepare(
                                $conn,
                                'INSERT INTO admin_password_reset_requests (admin_id, submitted_username, submitted_full_name) VALUES (?, ?, ?)'
                            );
                            if (!$insert) {
                                throw new RuntimeException('Unable to prepare administrator recovery request.');
                            }
                            mysqli_stmt_bind_param($insert, 'iss', $adminId, $savedUsername, $savedFullName);
                        } else {
                            $insert = mysqli_prepare(
                                $conn,
                                'INSERT INTO admin_password_reset_requests (admin_id, submitted_username, submitted_full_name) VALUES (NULL, ?, ?)'
                            );
                            if (!$insert) {
                                throw new RuntimeException('Unable to prepare administrator recovery request.');
                            }
                            mysqli_stmt_bind_param($insert, 'ss', $username, $fullName);
                        }
                        if (!mysqli_stmt_execute($insert)) {
                            mysqli_stmt_close($insert);
                            throw new RuntimeException('Unable to save administrator recovery request.');
                        }
                        mysqli_stmt_close($insert);
                    }

                    $_SESSION['admin_recovery_message'] = [
                        'text' => $genericResponse,
                        'type' => 'success',
                    ];
                    header('Location: forgot_password.php', true, 303);
                    exit;
                } catch (Throwable $error) {
                    // Keep account existence and database details out of the public page.
                    $message = 'Password recovery is temporarily unavailable. Please contact the campus system administrator.';
                    $messageType = 'warning';
                }
            }
        }
    }
}

$message = $_SESSION['admin_recovery_message']['text'] ?? $message;
$messageType = $_SESSION['admin_recovery_message']['type'] ?? $messageType;
unset($_SESSION['admin_recovery_message']);

function adminRecoveryEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102a43">
    <title>Administrator Password Recovery | RVU SIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 28px 16px;
            color: #243b53;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            background: radial-gradient(circle at 15% 15%, #326b91 0, transparent 34%), linear-gradient(135deg, #102a43, #174a73);
        }
        .recovery-card {
            width: min(100%, 470px);
            padding: clamp(26px, 6vw, 42px);
            background: #fff;
            border: 1px solid rgba(255,255,255,.55);
            border-radius: 22px;
            box-shadow: 0 24px 70px rgba(5, 24, 41, .30);
        }
        .brand { text-align: center; margin-bottom: 28px; }
        .brand img { width: 88px; height: 88px; object-fit: contain; margin-bottom: 12px; }
        .brand h1 { margin: 0 0 8px; color: #17365d; font-size: 25px; font-weight: 750; letter-spacing: -.03em; }
        .brand p { margin: 0; color: #64748b; font-size: 14px; }
        .intro { color: #52677c; font-size: 14px; line-height: 1.65; margin: 0 0 22px; }
        .form-label { color: #243b53; font-weight: 650; font-size: 14px; }
        .form-control { min-height: 48px; padding: 11px 13px; border-color: #d6e0e8; border-radius: 10px; }
        .form-control:focus { border-color: #3b82a0; box-shadow: 0 0 0 .2rem rgba(59,130,160,.17); }
        .btn-submit { width: 100%; min-height: 49px; border: 0; border-radius: 10px; background: #17365d; color: #fff; font-weight: 700; }
        .btn-submit:hover, .btn-submit:focus { background: #24527a; color: #fff; }
        .security-note { padding: 14px 15px; border-radius: 11px; background: #f0f6fa; color: #53697d; font-size: 12.5px; line-height: 1.6; margin: 20px 0 0; }
        .security-note strong { color: #294a66; }
        .back-link { display: block; margin-top: 21px; text-align: center; color: #17365d; font-weight: 650; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .honeypot { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    </style>
</head>
<body>
    <main class="recovery-card">
        <header class="brand">
            <img src="../assets/rvu.logo.webp" alt="Rift Valley University logo" onerror="this.style.display='none'">
            <h1>Administrator Password Recovery</h1>
            <p>Rift Valley University · Abichu Campus</p>
        </header>

        <p class="intro">Submit your administrator username and full name. An authorized administrator will review the request and contact you through the campus verification process.</p>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= adminRecoveryEscape($messageType) ?>" role="status">
                <?= adminRecoveryEscape($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="forgot_password.php" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= adminRecoveryEscape($_SESSION['admin_recovery_csrf']) ?>">
            <div class="honeypot" aria-hidden="true">
                <label for="website">Leave this field empty</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Administrator username</label>
                <input type="text" id="username" name="username" class="form-control" maxlength="50" autocomplete="username" required>
            </div>

            <div class="mb-4">
                <label for="full_name" class="form-label">Full name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" maxlength="100" autocomplete="name" required>
            </div>

            <button type="submit" class="btn btn-submit">Submit recovery request</button>
        </form>

        <p class="security-note"><strong>For your protection:</strong> this page does not change passwords or send email. A different authorized administrator must verify your identity before issuing a temporary password. If no other administrator can sign in, contact the campus system owner in person.</p>
        <a href="login.php" class="back-link">&larr; Back to Administrator Login</a>
    </main>
</body>
</html>
