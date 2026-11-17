<?php
session_start();
include("../includes/db.php");

$error = "";

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = "Please enter both username and password.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT admin_id, username, password
             FROM admins
             WHERE username = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error = "Database error: " . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (!$result) {

                $error = "Database error: " . mysqli_error($conn);

            } elseif (mysqli_num_rows($result) !== 1) {

                $error = "Invalid username or password.";

            } else {

                $admin = mysqli_fetch_assoc($result);

                $stored_password = $admin['password'];

                /*
                 * First try secure hashed password.
                 */
                $valid_password = password_verify(
                    $password,
                    $stored_password
                );

                /*
                 * Temporary support for old plain-text passwords.
                 */
                $old_password = false;

                if (
                    !$valid_password &&
                    $password === $stored_password
                ) {
                    $valid_password = true;
                    $old_password = true;
                }

                if (!$valid_password) {

                    $error = "Invalid username or password.";

                } else {

                    /*
                     * Convert old plain-text password
                     * to secure password hash.
                     */
                    if ($old_password) {

                        $new_hash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $update = mysqli_prepare(
                            $conn,
                            "UPDATE admins
                             SET password = ?
                             WHERE admin_id = ?"
                        );

                        if ($update) {

                            $admin_id =
                                (int)$admin['admin_id'];

                            mysqli_stmt_bind_param(
                                $update,
                                "si",
                                $new_hash,
                                $admin_id
                            );

                            mysqli_stmt_execute($update);

                            mysqli_stmt_close($update);
                        }
                    }

                    /*
                     * Create secure session.
                     */
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] =
                        (int)$admin['admin_id'];

                    $_SESSION['admin_username'] =
                        $admin['username'];

                    $_SESSION['role'] = "admin";

                    header(
                        "Location: dashboard.php"
                    );

                    exit();
                }
            }

            mysqli_stmt_close($stmt);
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

<title>Administrator Login | RVU SIS</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
rel="stylesheet">

<style>

body {
    min-height: 100vh;
    background: #f4f7fb;

    display: flex;
    align-items: center;
    justify-content: center;

    font-family: Arial, Helvetica, sans-serif;
}

.login-wrapper {
    width: 100%;
    max-width: 480px;
    padding: 20px;
}

.login-card {
    border: none;
    border-radius: 22px;
    overflow: hidden;

    box-shadow:
        0 15px 45px rgba(0,0,0,.12);
}

.login-header {
    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #6610f2
        );

    color: white;

    padding: 40px 30px;

    text-align: center;
}

.login-icon {
    width: 80px;
    height: 80px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.18);

    display: flex;

    align-items: center;
    justify-content: center;

    margin: 0 auto 20px;
}

.login-icon i {
    font-size: 40px;
}

.login-body {
    padding: 35px;
}

.form-control {
    min-height: 52px;
    border-radius: 11px;
}

.input-group-text {
    background: #f8f9fa;
}

.login-btn {
    min-height: 52px;

    border: none;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #6610f2
        );

    font-weight: 600;
}

.login-btn:hover {
    opacity: .92;
}

</style>

</head>

<body>

<div class="login-wrapper">

<div class="card login-card">

<div class="login-header">

<div class="login-icon">

<i class="bi bi-shield-lock-fill"></i>

</div>

<h2 class="fw-bold mb-2">
Administrator Login
</h2>

<p class="mb-0 opacity-75">
RVU Student Information System
</p>

</div>

<div class="login-body">

<?php if ($error !== ''): ?>

<div class="alert alert-danger">

<i class="bi bi-exclamation-triangle-fill me-2"></i>

<?php
echo htmlspecialchars($error);
?>

</div>

<?php endif; ?>

<form
method="POST"
autocomplete="off">

<div class="mb-3">

<label class="form-label fw-semibold">
Username
</label>

<div class="input-group">

<span class="input-group-text">
<i class="bi bi-person"></i>
</span>

<input
type="text"
name="username"
class="form-control"
placeholder="Enter username"
required>

</div>

</div>

<div class="mb-4">

<label class="form-label fw-semibold">
Password
</label>

<div class="input-group">

<span class="input-group-text">
<i class="bi bi-lock"></i>
</span>

<input
type="password"
name="password"
class="form-control"
placeholder="Enter password"
required>

</div>

</div>

<button
type="submit"
name="login"
class="btn btn-primary login-btn w-100">

<i class="bi bi-box-arrow-in-right me-2"></i>

Login

</button>

</form>
<div class="text-center mt-3">
    <a href="forgot_password.php" class="text-decoration-none">
        Forgot Password?
    </a>
</div>
<div class="text-center mt-4 text-muted">

<i class="bi bi-shield-check me-1"></i>

Secure Administrator Portal

</div>

</div>

</div>

</div>

</body>

</html>