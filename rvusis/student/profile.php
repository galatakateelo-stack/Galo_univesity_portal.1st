<?php
session_start();
require_once("../includes/db.php");

/* =========================================================
   STUDENT AUTHENTICATION
   ========================================================= */

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = (int) $_SESSION['student_id'];

$message = "";
$message_type = "";

/* =========================================================
   UPDATE CONTACT INFORMATION
   Students can update only email and phone.
   Academic information remains controlled by administration.
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact'])) {

    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    /* Validate email */
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "danger";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE students
             SET email = ?, phone = ?
             WHERE student_id = ?"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ssi",
                $email,
                $phone,
                $student_id
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Your contact information was updated successfully.";
                $message_type = "success";

            } else {

                $message = "Unable to update your information.";
                $message_type = "danger";
            }

            mysqli_stmt_close($stmt);

        } else {

            $message = "Database error. Please try again.";
            $message_type = "danger";
        }
    }
}

/* =========================================================
   GET STUDENT INFORMATION
   ========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        s.student_id,
        s.registration_no,
        s.first_name,
        s.last_name,
        s.gender,
        s.email,
        s.phone,
        s.username,
        s.profile_photo,
        s.academic_year,

        d.department_name,

        sem.semester_name

     FROM students s

     LEFT JOIN departments d
        ON s.department_id = d.department_id

     LEFT JOIN semesters sem
        ON s.semester_id = sem.semester_id

     WHERE s.student_id = ?

     LIMIT 1"
);

if (!$stmt) {
    die("Database error: " . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt, "i", $student_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$student = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================================================
   ACCOUNT CHECK
   ========================================================= */

if (!$student) {

    session_destroy();

    die("
        <div style='
            font-family:Arial;
            padding:40px;
            text-align:center;
        '>
            <h2>Student account not found</h2>
            <p>Please contact the system administrator.</p>
        </div>
    ");
}

/* =========================================================
   PROFILE PHOTO
   ========================================================= */

$photo = trim($student['profile_photo'] ?? '');

if ($photo !== "") {

    $photo_path = "../uploads/profiles/" . $photo;

    if (!file_exists($photo_path)) {
        $photo = "";
    }
}

/* Default avatar */
if ($photo === "") {
    $photo_url = "https://ui-avatars.com/api/?name="
        . urlencode(
            $student['first_name'] . " " . $student['last_name']
        )
        . "&size=200&background=0d6efd&color=ffffff";
} else {

    $photo_url = "../uploads/profiles/" . rawurlencode($photo);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>My Academic Profile | RVU SIS</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">

<style>

body {
    background: #f4f7fb;
    font-family: Arial, Helvetica, sans-serif;
}

/* Main container */

.profile-wrapper {
    max-width: 1100px;
    margin: 40px auto;
}

/* Header */

.profile-header {
    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #6610f2
        );

    color: white;

    border-radius: 20px 20px 0 0;

    padding: 35px;
}

/* Profile image */

.profile-photo {
    width: 150px;
    height: 150px;

    object-fit: cover;

    border-radius: 50%;

    border: 6px solid rgba(255,255,255,.9);

    box-shadow:
        0 8px 25px rgba(0,0,0,.25);
}

/* Cards */

.profile-card {
    border: none;

    border-radius: 0 0 20px 20px;

    box-shadow:
        0 10px 35px rgba(0,0,0,.08);

    overflow: hidden;
}

.info-card {

    background: #f8f9fa;

    border: 1px solid #e9ecef;

    border-radius: 14px;

    padding: 20px;

    height: 100%;
}

.info-label {

    font-size: 12px;

    font-weight: 700;

    color: #6c757d;

    text-transform: uppercase;

    letter-spacing: .5px;

    margin-bottom: 6px;
}

.info-value {

    font-size: 16px;

    font-weight: 600;

    color: #212529;

}

.section-title {

    font-weight: 700;

    color: #212529;

    margin-bottom: 20px;
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

.badge-status {

    font-size: 13px;

    padding: 8px 12px;

    border-radius: 20px;
}

</style>

</head>

<body>

<div class="container profile-wrapper">

    <!-- =====================================================
         PROFILE HEADER
         ===================================================== -->

    <div class="profile-header">

        <div class="row align-items-center">

            <div class="col-md-3 text-center mb-3 mb-md-0">

                <img
                    src="<?php echo htmlspecialchars($photo_url); ?>"
                    class="profile-photo"
                    alt="Student Profile Photo">

            </div>

            <div class="col-md-9">

                <div class="d-flex align-items-center gap-2 mb-2">

                    <i class="bi bi-mortarboard-fill fs-3"></i>

                    <span class="opacity-75">
                        RVU Student Information System
                    </span>

                </div>

                <h1 class="fw-bold mb-2">

                    <?php
                    echo htmlspecialchars(
                        $student['first_name'] . " " .
                        $student['last_name']
                    );
                    ?>

                </h1>

                <p class="mb-2 fs-5 opacity-75">

                    Student Academic Profile

                </p>

                <span class="badge bg-light text-primary badge-status">

                    <i class="bi bi-person-check-fill me-1"></i>

                    Active Student Account

                </span>

            </div>

        </div>

    </div>


    <!-- =====================================================
         MAIN PROFILE CARD
         ===================================================== -->

    <div class="card profile-card">

        <div class="card-body p-4 p-md-5">


            <!-- =================================================
                 ALERT MESSAGE
                 ================================================= -->

            <?php if ($message !== ""): ?>

                <div class="alert alert-<?php echo $message_type; ?>">

                    <?php if ($message_type === "success"): ?>

                        <i class="bi bi-check-circle-fill me-2"></i>

                    <?php else: ?>

                        <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    <?php endif; ?>

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 PERSONAL INFORMATION
                 ================================================= -->

            <h4 class="section-title">

                <i class="bi bi-person-vcard-fill text-primary me-2"></i>

                Personal Information

            </h4>


            <div class="row g-3 mb-5">

                <div class="col-md-6">

                    <div class="info-card">

                        <div class="info-label">
                            First Name
                        </div>

                        <div class="info-value">
                            <?php
                            echo htmlspecialchars(
                                $student['first_name']
                            );
                            ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-card">

                        <div class="info-label">
                            Last Name
                        </div>

                        <div class="info-value">
                            <?php
                            echo htmlspecialchars(
                                $student['last_name']
                            );
                            ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-card">

                        <div class="info-label">
                            Gender
                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $student['gender']
                            );
                            ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-card">

                        <div class="info-label">
                            Student ID
                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $student['student_id']
                            );
                            ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-card">

                        <div class="info-label">
                            Registration Number
                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $student['registration_no']
                            );
                            ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-card">

                        <div class="info-label">
                            Username
                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $student['username']
                            );
                            ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 ACADEMIC INFORMATION
                 ================================================= -->

            <h4 class="section-title">

                <i class="bi bi-mortarboard-fill text-primary me-2"></i>

                Academic Information

            </h4>


            <div class="row g-3 mb-5">

                <div class="col-md-6">

                    <div class="info-card">

                        <div class="info-label">
                            Department
                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $student['department_name']
                                ?? 'Not assigned'
                            );
                            ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-card">

                        <div class="info-label">
                            Academic Year
                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $student['academic_year']
                                ?? 'Not assigned'
                            );
                            ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-card">

                        <div class="info-label">
                            Current Semester
                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $student['semester_name']
                                ?? 'Not assigned'
                            );
                            ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-card">

                        <div class="info-label">
                            Academic Status
                        </div>

                        <div class="info-value">

                            <span class="badge bg-success badge-status">

                                Active

                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 CONTACT INFORMATION
                 ================================================= -->

            <h4 class="section-title">

                <i class="bi bi-person-lines-fill text-primary me-2"></i>

                Contact Information

            </h4>


            <form method="POST">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">

                            Email Address

                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $student['email'] ?? ''
                            );
                            ?>"
                            placeholder="Enter email address">

                    </div>


                    <div class="col-md-6">

                        <label class="form-label fw-semibold">

                            Phone Number

                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $student['phone'] ?? ''
                            );
                            ?>"
                            placeholder="Enter phone number">

                    </div>

                </div>


                <div class="d-flex flex-wrap gap-2 mt-4">

                    <button
                        type="submit"
                        name="update_contact"
                        class="btn btn-primary">

                        <i class="bi bi-check-circle-fill me-2"></i>

                        Save Contact Information

                    </button>


                    <a
                        href="change_password.php"
                        class="btn btn-warning">

                        <i class="bi bi-shield-lock-fill me-2"></i>

                        Change Password

                    </a>


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


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>