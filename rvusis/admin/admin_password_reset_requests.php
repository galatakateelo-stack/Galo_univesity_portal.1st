<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['admin_password_reset_csrf'])) {
    $_SESSION['admin_password_reset_csrf'] = bin2hex(random_bytes(32));
}

function adminResetQueueEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    $messageType = 'danger';
    $token = $_POST['csrf_token'] ?? '';
    $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

    if (!is_string($token) || !hash_equals($_SESSION['admin_password_reset_csrf'], $token)) {
        $message = 'The request could not be validated. Refresh the page and try again.';
    } elseif (!$requestId || !in_array($action, ['approve', 'reject'], true)) {
        $message = 'The selected request is not valid.';
    } elseif ($action === 'approve' && ($_POST['confirm_identity'] ?? '') !== 'yes') {
        $message = 'Confirm that you verified the administrator’s identity before approving the request.';
    } else {
        try {
            mysqli_begin_transaction($conn);

            $lock = mysqli_prepare(
                $conn,
                'SELECT request_id, admin_id, submitted_username, submitted_full_name, status FROM admin_password_reset_requests WHERE request_id = ? FOR UPDATE'
            );
            if (!$lock) {
                throw new RuntimeException('Unable to inspect recovery request.');
            }
            mysqli_stmt_bind_param($lock, 'i', $requestId);
            mysqli_stmt_execute($lock);
            $lockResult = mysqli_stmt_get_result($lock);
            $request = $lockResult ? mysqli_fetch_assoc($lockResult) : null;
            mysqli_stmt_close($lock);

            if (!$request || $request['status'] !== 'pending') {
                throw new RuntimeException('The request is no longer pending.');
            }

            $reviewerId = (int)$_SESSION['admin_id'];

            if ($action === 'reject') {
                $update = mysqli_prepare(
                    $conn,
                    "UPDATE admin_password_reset_requests SET status = 'rejected', reviewed_at = CURRENT_TIMESTAMP, reviewed_by = ? WHERE request_id = ? AND status = 'pending'"
                );
                if (!$update) {
                    throw new RuntimeException('Unable to close recovery request.');
                }
                mysqli_stmt_bind_param($update, 'ii', $reviewerId, $requestId);
                mysqli_stmt_execute($update);
                $changed = mysqli_stmt_affected_rows($update);
                mysqli_stmt_close($update);
                if ($changed !== 1) {
                    throw new RuntimeException('The request could not be updated.');
                }
                mysqli_commit($conn);
                $message = 'The request was closed. No password was changed.';
                $messageType = 'success';
            } elseif ($request['admin_id'] === null) {
                throw new RuntimeException('The submitted details do not match an administrator account.');
            } else {
                $adminId = (int)$request['admin_id'];
                if ($adminId === $reviewerId) {
                    throw new RuntimeException('A different administrator must review this request.');
                }

                $adminLookup = mysqli_prepare(
                    $conn,
                    'SELECT admin_id, username, full_name FROM admins WHERE admin_id = ? FOR UPDATE'
                );
                if (!$adminLookup) {
                    throw new RuntimeException('Unable to verify administrator record.');
                }
                mysqli_stmt_bind_param($adminLookup, 'i', $adminId);
                mysqli_stmt_execute($adminLookup);
                $adminResult = mysqli_stmt_get_result($adminLookup);
                $admin = $adminResult ? mysqli_fetch_assoc($adminResult) : null;
                mysqli_stmt_close($adminLookup);
                if (!$admin) {
                    throw new RuntimeException('The administrator record no longer exists.');
                }

                // Require the submitted identifiers to still match the linked account.
                if ($admin['username'] !== $request['submitted_username'] || $admin['full_name'] !== $request['submitted_full_name']) {
                    throw new RuntimeException('The request details no longer match the administrator record.');
                }

                // A high-entropy temporary password is displayed once to the reviewer.
                $temporaryPassword = 'Rvu-' . bin2hex(random_bytes(16));
                $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
                if ($passwordHash === false) {
                    throw new RuntimeException('Unable to hash temporary password.');
                }

                $updateAdmin = mysqli_prepare($conn, 'UPDATE admins SET password = ? WHERE admin_id = ?');
                if (!$updateAdmin) {
                    throw new RuntimeException('Unable to prepare administrator password reset.');
                }
                mysqli_stmt_bind_param($updateAdmin, 'si', $passwordHash, $adminId);
                mysqli_stmt_execute($updateAdmin);
                $updated = mysqli_stmt_affected_rows($updateAdmin);
                mysqli_stmt_close($updateAdmin);
                if ($updated !== 1) {
                    throw new RuntimeException('The administrator password could not be changed.');
                }

                $complete = mysqli_prepare(
                    $conn,
                    "UPDATE admin_password_reset_requests SET status = 'completed', reviewed_at = CURRENT_TIMESTAMP, reviewed_by = ? WHERE request_id = ? AND status = 'pending'"
                );
                if (!$complete) {
                    throw new RuntimeException('Unable to complete recovery request.');
                }
                mysqli_stmt_bind_param($complete, 'ii', $reviewerId, $requestId);
                mysqli_stmt_execute($complete);
                $completed = mysqli_stmt_affected_rows($complete);
                mysqli_stmt_close($complete);
                if ($completed !== 1) {
                    throw new RuntimeException('The request could not be completed.');
                }

                mysqli_commit($conn);
                $_SESSION['admin_password_reset_credential_flash'] = [
                    'username' => $admin['username'],
                    'password' => $temporaryPassword,
                ];
                $message = 'The password reset is complete. Deliver the temporary password only after verifying the administrator’s identity, then ask them to change it immediately.';
                $messageType = 'success';
            }
        } catch (Throwable $error) {
            if (isset($conn) && $conn instanceof mysqli) {
                try {
                    mysqli_rollback($conn);
                } catch (Throwable $ignored) {
                    // Keep database details out of the reviewer-facing page.
                }
            }
            $message = $action === 'reject'
                ? 'The request could not be closed. Refresh the page and try again.'
                : 'The reset could not be completed. Confirm identity and ensure a different administrator is reviewing the request.';
            $messageType = 'danger';
        }
    }

    $_SESSION['admin_password_reset_message'] = ['text' => $message, 'type' => $messageType];
    header('Location: admin_password_reset_requests.php', true, 303);
    exit;
}

$message = $_SESSION['admin_password_reset_message']['text'] ?? '';
$messageType = $_SESSION['admin_password_reset_message']['type'] ?? 'info';
unset($_SESSION['admin_password_reset_message']);

$credential = $_SESSION['admin_password_reset_credential_flash'] ?? null;
unset($_SESSION['admin_password_reset_credential_flash']);

$requests = [];
$loadError = false;
try {
    $query = mysqli_query(
        $conn,
        "SELECT r.request_id, r.admin_id, r.submitted_username, r.submitted_full_name, r.requested_at,
                a.username, a.full_name
         FROM admin_password_reset_requests r
         LEFT JOIN admins a ON a.admin_id = r.admin_id
         WHERE r.status = 'pending'
         ORDER BY r.requested_at ASC"
    );
    if (!$query) {
        throw new RuntimeException('Unable to load administrator recovery requests.');
    }
    while ($row = mysqli_fetch_assoc($query)) {
        $requests[] = $row;
    }
    mysqli_free_result($query);
} catch (Throwable $error) {
    $loadError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Recovery Requests | RVU SIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; margin: 0; background: #f2f6fa; color: #20364a; font-family: Inter, "Segoe UI", Arial, sans-serif; }
        .topbar { background: #102a43; color: #fff; padding: 17px max(20px, calc((100% - 1160px) / 2)); display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .brand { font-weight: 750; letter-spacing: .01em; }
        .brand i { margin-right: 9px; color: #83c5d9; }
        .topbar a { color: #d8e8f2; text-decoration: none; font-size: 14px; }
        .topbar a:hover { color: #fff; text-decoration: underline; }
        .page { width: min(1160px, calc(100% - 32px)); margin: 34px auto 56px; }
        .page-title { margin-bottom: 22px; }
        .page-title h1 { color: #17365d; font-size: clamp(25px, 4vw, 32px); font-weight: 750; letter-spacing: -.03em; margin: 0 0 7px; }
        .page-title p { color: #60758a; margin: 0; }
        .panel { background: #fff; border: 1px solid #e0e9ef; border-radius: 16px; box-shadow: 0 8px 28px rgba(28, 54, 77, .06); }
        .instructions { padding: 19px 22px; margin-bottom: 22px; border-left: 4px solid #28708f; color: #53697d; line-height: 1.6; }
        .instructions strong { color: #274760; }
        .table-wrap { overflow-x: auto; }
        .table { margin: 0; min-width: 760px; }
        .table thead th { border-bottom-color: #e1eaf0; color: #5d7184; font-size: 12px; text-transform: uppercase; letter-spacing: .055em; padding: 15px 17px; white-space: nowrap; }
        .table tbody td { padding: 17px; vertical-align: middle; border-color: #edf1f4; }
        .identifier { font-weight: 650; color: #253f56; }
        .subtle { color: #718397; font-size: 12px; margin-top: 4px; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .empty { padding: 48px 20px; text-align: center; color: #6c8091; }
        .empty i { display: block; color: #4b91a7; font-size: 27px; margin-bottom: 13px; }
        .credential-box { border: 1px solid #b9ddc4; border-radius: 13px; background: #f1faf4; padding: 20px; margin-bottom: 20px; }
        .credential-box code { display: inline-block; padding: 11px 13px; background: #fff; border: 1px solid #cce2d2; border-radius: 8px; color: #17365d; font-size: 16px; user-select: all; overflow-wrap: anywhere; }
        .credential-box .hint { margin: 10px 0 0; color: #4c6b57; font-size: 13px; }
        @media (max-width: 560px) { .topbar { align-items: flex-start; flex-direction: column; } .page { margin-top: 24px; } }
    </style>
</head>
<body>
    <nav class="topbar">
        <div class="brand"><i class="fa-solid fa-graduation-cap"></i>RVU SIS · Administration</div>
        <a href="dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Back to dashboard</a>
    </nav>

    <main class="page">
        <header class="page-title">
            <h1>Administrator password requests</h1>
            <p>Review requests and issue a temporary password after campus identity verification.</p>
        </header>

        <section class="panel instructions">
            <strong><i class="fa-solid fa-shield-halved me-2"></i>Verify identity before approval.</strong>
            A username and full name are not proof of identity. Verify the requester through the campus process, and have a different administrator review the request. An administrator cannot approve their own reset.
        </section>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= adminResetQueueEscape($messageType) ?>" role="status"><?= adminResetQueueEscape($message) ?></div>
        <?php endif; ?>

        <?php if ($credential): ?>
            <section class="credential-box" aria-live="polite">
                <strong>Temporary password for <?= adminResetQueueEscape($credential['username']) ?></strong>
                <div class="mt-2">
                    <code id="temporary-password"><?= adminResetQueueEscape($credential['password']) ?></code>
                    <button type="button" class="btn btn-sm btn-outline-success ms-2" onclick="navigator.clipboard.writeText(document.getElementById('temporary-password').textContent)">Copy</button>
                </div>
                <p class="hint">This value is shown once. Deliver it only after verifying identity through the campus process. Ask the administrator to sign in and change it immediately.</p>
            </section>
        <?php endif; ?>

        <?php if ($loadError): ?>
            <div class="alert alert-warning" role="alert">
                The administrator recovery queue is unavailable. Apply <code>database/migrations/20261025_create_admin_password_reset_requests.sql</code> and reload this page.
            </div>
        <?php else: ?>
            <section class="panel">
                <?php if (!$requests): ?>
                    <div class="empty"><i class="fa-regular fa-circle-check"></i><strong>No pending administrator requests</strong><div class="mt-1">New recovery requests will appear here.</div></div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Request</th><th>Submitted details</th><th>Matched account</th><th>Received</th><th>Review</th></tr></thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td class="identifier">#<?= (int)$request['request_id'] ?></td>
                                        <td>
                                            <div class="identifier"><?= adminResetQueueEscape($request['submitted_username']) ?></div>
                                            <div class="subtle"><?= adminResetQueueEscape($request['submitted_full_name']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($request['admin_id'] !== null && $request['username'] !== null): ?>
                                                <div class="identifier"><?= adminResetQueueEscape($request['full_name']) ?></div>
                                                <div class="subtle">Administrator #<?= (int)$request['admin_id'] ?> · <?= adminResetQueueEscape($request['username']) ?></div>
                                                <?php if ((int)$request['admin_id'] === (int)$_SESSION['admin_id']): ?>
                                                    <span class="badge text-bg-warning mt-1">Another administrator must review</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge text-bg-secondary">No exact account match</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= adminResetQueueEscape(date('M j, Y H:i', strtotime($request['requested_at']))) ?></td>
                                        <td>
                                            <div class="actions">
                                                <?php if ($request['admin_id'] !== null && (int)$request['admin_id'] !== (int)$_SESSION['admin_id']): ?>
                                                    <form method="post" onsubmit="return confirm('Confirm that you verified this administrator’s identity and want to replace their password?');">
                                                        <input type="hidden" name="csrf_token" value="<?= adminResetQueueEscape($_SESSION['admin_password_reset_csrf']) ?>">
                                                        <input type="hidden" name="request_id" value="<?= (int)$request['request_id'] ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <div class="form-check small mb-2">
                                                            <input class="form-check-input" type="checkbox" id="identity-<?= (int)$request['request_id'] ?>" name="confirm_identity" value="yes" required>
                                                            <label class="form-check-label" for="identity-<?= (int)$request['request_id'] ?>">Identity verified</label>
                                                        </div>
                                                        <button class="btn btn-sm btn-primary" type="submit"><i class="fa-solid fa-key me-1"></i>Verify and reset</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="post" onsubmit="return confirm('Close this request without changing a password?');">
                                                    <input type="hidden" name="csrf_token" value="<?= adminResetQueueEscape($_SESSION['admin_password_reset_csrf']) ?>">
                                                    <input type="hidden" name="request_id" value="<?= (int)$request['request_id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Close request</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
