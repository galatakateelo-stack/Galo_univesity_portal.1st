<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (empty($_SESSION['student_recovery_csrf'])) {
    $_SESSION['student_recovery_csrf'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['student_recovery_message']['text'] ?? '';
$messageType = $_SESSION['student_recovery_message']['type'] ?? 'info';
unset($_SESSION['student_recovery_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['student_recovery_csrf'], $submittedToken)) {
        $message = 'Your request could not be validated. Refresh the page and try again.';
        $messageType = 'danger';
    } else {
        $now = time();
        $attempts = $_SESSION['student_recovery_attempts'] ?? [];
        $attempts = array_values(array_filter(
            is_array($attempts) ? $attempts : [],
            static function ($timestamp) use ($now) {
                return is_int($timestamp) && $timestamp > ($now - 900);
            }
        ));

        if (count($attempts) >= 5) {
            $message = 'Too many attempts. Please wait 15 minutes and try again, or contact the campus administrator.';
            $messageType = 'warning';
        } else {
            $attempts[] = $now;
            $_SESSION['student_recovery_attempts'] = $attempts;

            // Silently accept the honeypot so automated form submissions learn nothing.
            if (trim((string)($_POST['website'] ?? '')) !== '') {
                $_SESSION['student_recovery_message'] = [
                    'text' => 'If the details match a student record, your request has been sent for review. A campus administrator will verify your identity before resetting the password.',
                    'type' => 'success',
                ];
                header('Location: forgot_password.php', true, 303);
                exit;
            }

            $username = trim((string)($_POST['username'] ?? ''));
            $registrationNo = trim((string)($_POST['registration_no'] ?? ''));

            if ($username === '' || $registrationNo === '') {
                $message = 'Enter both your student username and registration number.';
                $messageType = 'danger';
            } elseif (strlen($username) > 50 || strlen($registrationNo) > 30) {
                $message = 'The information entered is not valid. Please check it and try again.';
                $messageType = 'danger';
            } else {
                try {
                    $lookup = mysqli_prepare(
                        $conn,
                        'SELECT student_id FROM students WHERE username = ? AND registration_no = ? LIMIT 1'
                    );

                    if (!$lookup) {
                        throw new RuntimeException('Unable to prepare student lookup.');
                    }

                    mysqli_stmt_bind_param($lookup, 'ss', $username, $registrationNo);
                    mysqli_stmt_execute($lookup);
                    $lookupResult = mysqli_stmt_get_result($lookup);
                    $student = $lookupResult ? mysqli_fetch_assoc($lookupResult) : null;
                    mysqli_stmt_close($lookup);

                    $studentId = $student ? (int)$student['student_id'] : null;

                    // Avoid creating repeated pending requests for the same matched account.
                    $alreadyPending = false;
                    if ($studentId !== null) {
                        $pending = mysqli_prepare(
                            $conn,
                            "SELECT request_id FROM password_reset_requests WHERE student_id = ? AND status = 'pending' LIMIT 1"
                        );
                        if (!$pending) {
                            throw new RuntimeException('Unable to check pending requests.');
                        }
                        mysqli_stmt_bind_param($pending, 'i', $studentId);
                        mysqli_stmt_execute($pending);
                        $pendingResult = mysqli_stmt_get_result($pending);
                        $alreadyPending = $pendingResult && mysqli_num_rows($pendingResult) > 0;
                        mysqli_stmt_close($pending);
                    }

                    if (!$alreadyPending) {
                        $insert = mysqli_prepare(
                            $conn,
                            'INSERT INTO password_reset_requests (student_id, submitted_username, submitted_registration_no) VALUES (?, ?, ?)'
                        );
                        if (!$insert) {
                            throw new RuntimeException('Unable to prepare the recovery request.');
                        }
                        mysqli_stmt_bind_param($insert, 'iss', $studentId, $username, $registrationNo);
                        if (!mysqli_stmt_execute($insert)) {
                            mysqli_stmt_close($insert);
                            throw new RuntimeException('Unable to save the recovery request.');
                        }
                        mysqli_stmt_close($insert);
                    }

                    $_SESSION['student_recovery_message'] = [
                        'text' => 'If the details match a student record, your request has been sent for review. A campus administrator will verify your identity before resetting the password.',
                        'type' => 'success',
                    ];
                    header('Location: forgot_password.php', true, 303);
                    exit;
                } catch (Throwable $error) {
                    // Keep database details out of the public recovery page.
                    $message = 'Password recovery is temporarily unavailable. Please contact the campus administrator.';
                    $messageType = 'warning';
                }
            }
        }
    }
}

function recoveryEscape($value)
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
    <title>Student Password Recovery | RVU SIS</title>
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
            <h1>Student Password Recovery</h1>
            <p>Rift Valley University · Abichu Campus</p>
        </header>

        <p class="intro">Submit your student username and registration number. Your request will be reviewed by an authorized campus administrator.</p>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= recoveryEscape($messageType) ?>" role="status">
                <?= recoveryEscape($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="forgot_password.php" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= recoveryEscape($_SESSION['student_recovery_csrf']) ?>">
            <div class="honeypot" aria-hidden="true">
                <label for="website">Leave this field empty</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Student username</label>
                <input type="text" id="username" name="username" class="form-control" maxlength="50" autocomplete="username" required>
            </div>

            <div class="mb-4">
                <label for="registration_no" class="form-label">Registration number</label>
                <input type="text" id="registration_no" name="registration_no" class="form-control" maxlength="30" autocomplete="off" required>
            </div>

            <button type="submit" class="btn btn-submit">Submit recovery request</button>
        </form>

        <p class="security-note"><strong>For your protection:</strong> no password is changed by this form. An administrator must verify your identity through the campus process before issuing a temporary password. If you cannot use this form, contact the campus administration office.</p>
        <a href="login.php" class="back-link">&larr; Back to Student Login</a>
    </main>
</body>
</html>
