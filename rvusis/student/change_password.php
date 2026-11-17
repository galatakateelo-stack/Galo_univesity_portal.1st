<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = (int) $_SESSION['student_id'];

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    /*
     * STEP 1: Check empty fields
     */
    if (
        trim($current_password) === '' ||
        trim($new_password) === '' ||
        trim($confirm_password) === ''
    ) {

        $message = "Please fill in all password fields.";
        $message_type = "danger";

    }

    /*
     * STEP 2: Minimum 8 characters
     */
    elseif (strlen($new_password) < 8) {

        $message =
            "New password must contain at least 8 characters.";

        $message_type = "danger";

    }

    /*
     * STEP 3: Require at least one letter
     */
    elseif (!preg_match('/[A-Za-z]/', $new_password)) {

        $message =
            "New password must contain at least one letter.";

        $message_type = "danger";

    }

    /*
     * STEP 4: Require at least one number
     */
    elseif (!preg_match('/[0-9]/', $new_password)) {

        $message =
            "New password must contain at least one number.";

        $message_type = "danger";

    }

    /*
     * STEP 5: Confirm passwords
     */
    elseif ($new_password !== $confirm_password) {

        $message =
            "New password and confirmation password do not match.";

        $message_type = "danger";

    }

    /*
     * STEP 6: Prevent same password
     */
    elseif ($current_password === $new_password) {

        $message =
            "New password must be different from your current password.";

        $message_type = "danger";

    }

    else {

        /*
         * Get current password
         */
        $stmt = mysqli_prepare(
            $conn,
            "SELECT password
             FROM students
             WHERE student_id = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $student_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (!$result || mysqli_num_rows($result) !== 1) {

            $message = "Student account not found.";
            $message_type = "danger";

        } else {

            $student = mysqli_fetch_assoc($result);

            $stored_password = $student['password'];

            /*
             * Support both:
             *
             * 1. Existing plain-text passwords
             * 2. New hashed passwords
             */
            $current_correct = false;

            if (
                password_verify(
                    $current_password,
                    $stored_password
                )
            ) {

                $current_correct = true;

            } elseif (
                $current_password === $stored_password
            ) {

                $current_correct = true;
            }

            if (!$current_correct) {

                $message =
                    "Current password is incorrect.";

                $message_type = "danger";

            } else {

                /*
                 * Create secure password hash
                 */
                $hashed_password = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );

                /*
                 * Update password
                 */
                $update = mysqli_prepare(
                    $conn,
                    "UPDATE students
                     SET password = ?
                     WHERE student_id = ?"
                );

                mysqli_stmt_bind_param(
                    $update,
                    "si",
                    $hashed_password,
                    $student_id
                );

                if (mysqli_stmt_execute($update)) {

                    $message =
                        "Password changed successfully.";

                    $message_type = "success";

                    /*
                     * Clear form after successful change
                     */
                    $_POST = [];

                } else {

                    $message =
                        "Unable to change password. Please try again.";

                    $message_type = "danger";
                }

                mysqli_stmt_close($update);
            }
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Change Password | Student</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

body {

    background: #f4f7fb;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}

.password-wrapper {

    max-width: 600px;

    margin: 70px auto;
}

.password-card {

    border: none;

    border-radius: 20px;

    overflow: hidden;

    box-shadow:
        0 10px 35px
        rgba(0,0,0,.10);
}

.password-header {

    background:
        linear-gradient(
            135deg,
            #6610f2,
            #0d6efd
        );

    color: white;

    padding: 30px;
}

.password-icon {

    font-size: 45px;
}

.form-control {

    min-height: 50px;

    border-radius: 10px;
}

.btn {

    min-height: 48px;

    border-radius: 10px;

    font-weight: 600;
}

.password-rules {

    background: #f8f9fa;

    border-radius: 10px;

    padding: 15px;

    margin-bottom: 20px;
}

.password-rules li {

    margin-bottom: 5px;
}

</style>

</head>

<body>

<div class="container">

<div class="password-wrapper">

<div class="card password-card">

<!-- HEADER -->

<div class="password-header">

<div class="d-flex align-items-center gap-3">

<i class="bi bi-shield-lock-fill password-icon"></i>

<div>

<h2 class="mb-1">
Change Password
</h2>

<p class="mb-0 opacity-75">
Student Security Settings
</p>

</div>

</div>

</div>


<!-- BODY -->

<div class="card-body p-4 p-md-5">

<?php if (!empty($message)): ?>

<div class="alert alert-<?php echo $message_type; ?>">

<i class="bi
<?php
echo $message_type === 'success'
    ? 'bi-check-circle-fill'
    : 'bi-exclamation-triangle-fill';
?>
me-2"></i>

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>


<!-- PASSWORD RULES -->

<div class="password-rules">

<strong>
<i class="bi bi-info-circle me-1"></i>
Password Requirements
</strong>

<ul class="mb-0 mt-2">

<li>Minimum 8 characters</li>

<li>At least one letter</li>

<li>At least one number</li>

<li>Special characters are allowed</li>

</ul>

</div>


<form method="POST">

<!-- CURRENT PASSWORD -->

<div class="mb-3">

<label class="form-label fw-semibold">

Current Password

</label>

<input
type="password"
name="current_password"
class="form-control"
placeholder="Enter current password"
autocomplete="current-password"
required>

</div>


<!-- NEW PASSWORD -->

<div class="mb-3">

<label class="form-label fw-semibold">

New Password

</label>

<input
type="password"
name="new_password"
class="form-control"
placeholder="Example: Campus2026"
autocomplete="new-password"
required>

</div>


<!-- CONFIRM -->

<div class="mb-4">

<label class="form-label fw-semibold">

Confirm New Password

</label>

<input
type="password"
name="confirm_password"
class="form-control"
placeholder="Confirm new password"
autocomplete="new-password"
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