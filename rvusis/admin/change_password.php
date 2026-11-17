<?php
session_start();
require_once("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = (int)$_SESSION['admin_id'];

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (
        $current_password === '' ||
        $new_password === '' ||
        $confirm_password === ''
    ) {

        $message = "Please fill in all password fields.";
        $message_type = "danger";

    } elseif (strlen($new_password) < 8) {

        $message = "New password must contain at least 8 characters.";
        $message_type = "danger";

    } elseif (!preg_match('/[A-Za-z]/', $new_password)) {

        $message = "New password must contain at least one letter.";
        $message_type = "danger";

    } elseif (!preg_match('/[0-9]/', $new_password)) {

        $message = "New password must contain at least one number.";
        $message_type = "danger";

    } elseif ($new_password !== $confirm_password) {

        $message = "New password and confirmation password do not match.";
        $message_type = "danger";

    } elseif ($current_password === $new_password) {

        $message = "New password must be different from your current password.";
        $message_type = "danger";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT password
             FROM admins
             WHERE admin_id = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $admin_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if (!$admin) {

            $message = "Administrator account not found.";
            $message_type = "danger";

        } else {

            $validPassword = password_verify(
                $current_password,
                $admin['password']
            );

            /*
             * Temporary support for old plain-text passwords.
             */
            if (!$validPassword &&
                hash_equals(
                    (string)$admin['password'],
                    (string)$current_password
                )
            ) {
                $validPassword = true;
            }

            if (!$validPassword) {

                $message = "Current password is incorrect.";
                $message_type = "danger";

            } else {

                $hashed_password = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );

                $update = mysqli_prepare(
                    $conn,
                    "UPDATE admins
                     SET password = ?
                     WHERE admin_id = ?"
                );

                mysqli_stmt_bind_param(
                    $update,
                    "si",
                    $hashed_password,
                    $admin_id
                );

                if (mysqli_stmt_execute($update)) {

                    $message = "Password changed successfully.";
                    $message_type = "success";

                } else {

                    $message = "Unable to change password.";
                    $message_type = "danger";
                }

                mysqli_stmt_close($update);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Change Password | Admin</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
rel="stylesheet">

<style>

body {
    background: #f4f7fb;
    font-family: Arial, Helvetica, sans-serif;
}

.password-wrapper {
    max-width: 620px;
    margin: 70px auto;
    padding: 20px;
}

.password-card {
    border: none;
    border-radius: 20px;
    overflow: hidden;

    box-shadow:
        0 12px 40px rgba(0,0,0,.10);
}

.password-header {
    color: white;

    background: linear-gradient(
        135deg,
        #0d6efd,
        #6610f2
    );

    padding: 32px;
}

.password-icon {
    font-size: 45px;
}

.form-control {
    min-height: 52px;
    border-radius: 11px;
}

.btn {
    min-height: 50px;
    border-radius: 11px;
    font-weight: 600;
}

</style>

</head>

<body>

<div class="container">

<div class="password-wrapper">

<div class="card password-card">

<div class="password-header">

<div class="d-flex align-items-center gap-3">

<i class="bi bi-shield-lock-fill password-icon"></i>

<div>

<h2 class="mb-1">
Change Password
</h2>

<p class="mb-0 opacity-75">
Administrator Security Settings
</p>

</div>

</div>

</div>

<div class="card-body p-4 p-md-5">

<?php if ($message !== ''): ?>

<div class="alert alert-<?php echo $message_type; ?>">

<i class="bi
<?php echo $message_type === 'success'
    ? 'bi-check-circle-fill'
    : 'bi-exclamation-triangle-fill';
?> me-2"></i>

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>

<form method="POST">

<div class="mb-3">

<label class="form-label fw-semibold">
Current Password
</label>

<input
type="password"
name="current_password"
class="form-control"
placeholder="Enter current password"
required>

</div>

<div class="mb-3">

<label class="form-label fw-semibold">
New Password
</label>

<input
type="password"
name="new_password"
class="form-control"
placeholder="Minimum 8 characters, letters + numbers"
required>

<div class="form-text">
Use at least 8 characters including letters and numbers.
</div>

</div>

<div class="mb-4">

<label class="form-label fw-semibold">
Confirm New Password
</label>

<input
type="password"
name="confirm_password"
class="form-control"
placeholder="Confirm new password"
required>

</div>

<div class="d-grid gap-2">

<button
type="submit"
class="btn btn-primary">

<i class="bi bi-key-fill me-2"></i>

Change Password

</button>

<a
href="dashboard.php"
class="btn btn-secondary">

<i class="bi bi-arrow-left me-2"></i>

Back to Dashboard

</a>

</div>

</form>

</div>

</div>

</div>

</div>

</body>
</html>