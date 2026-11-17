<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

$error = "";
$success = "";

/* =====================================================
   CHECK STUDENT ID
===================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$student_id = (int) $_GET['id'];


/* =====================================================
   GET STUDENT INFORMATION
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM students
     WHERE student_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit();
}

$student = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =====================================================
   HANDLE UPDATE
===================================================== */

if (isset($_POST['update_student'])) {

    $registration_no = trim($_POST['registration_no']);
    $first_name      = trim($_POST['first_name']);
    $last_name       = trim($_POST['last_name']);
    $gender          = trim($_POST['gender']);
    $department_id   = (int) $_POST['department_id'];


    /* =================================================
       VALIDATION
    ================================================= */

    if (
        empty($registration_no) ||
        empty($first_name) ||
        empty($last_name) ||
        empty($gender) ||
        empty($department_id)
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $first_name)) {

        $error = "First name contains invalid characters.";

    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $last_name)) {

        $error = "Last name contains invalid characters.";

    } else {


        /* =============================================
           CHECK DUPLICATE REGISTRATION NUMBER
           EXCLUDE CURRENT STUDENT
        ============================================= */

        $check = mysqli_prepare(
            $conn,
            "SELECT student_id
             FROM students
             WHERE registration_no = ?
             AND student_id != ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "si",
            $registration_no,
            $student_id
        );

        mysqli_stmt_execute($check);

        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $error = "This registration number already belongs to another student.";

        } else {


            /* =========================================
               UPDATE STUDENT
            ========================================= */

            $update = mysqli_prepare(
                $conn,
                "UPDATE students
                 SET
                    registration_no = ?,
                    first_name = ?,
                    last_name = ?,
                    gender = ?,
                    department_id = ?
                 WHERE student_id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "ssssii",
                $registration_no,
                $first_name,
                $last_name,
                $gender,
                $department_id,
                $student_id
            );


            if (mysqli_stmt_execute($update)) {

                $success = "Student information updated successfully.";

                /* Update displayed values */

                $student['registration_no'] = $registration_no;
                $student['first_name'] = $first_name;
                $student['last_name'] = $last_name;
                $student['gender'] = $gender;
                $student['department_id'] = $department_id;

            } else {

                $error = "Unable to update student information.";

            }

            mysqli_stmt_close($update);
        }

        mysqli_stmt_close($check);
    }
}


/* =====================================================
   GET DEPARTMENTS
===================================================== */

$departments = mysqli_query(
    $conn,
    "SELECT department_id, department_name
     FROM departments
     ORDER BY department_name ASC"
);

if (!$departments) {
    die("Database Error: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Student | Admin</title>


<!-- Bootstrap -->

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<!-- Bootstrap Icons -->

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<style>

/* =====================================================
   GENERAL
===================================================== */

body {

    background: #f5f7fb;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}


/* =====================================================
   PAGE CONTAINER
===================================================== */

.page-container {

    max-width: 1000px;

    margin: 40px auto;

    padding: 0 20px;
}


/* =====================================================
   HEADER
===================================================== */

.page-header {

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color: white;

    border-radius: 15px;

    padding: 25px 30px;

    margin-bottom: 25px;

    box-shadow:
        0 8px 25px rgba(0,0,0,0.10);
}


.page-header h2 {

    margin: 0;

    font-weight: 700;
}


.page-header p {

    margin-top: 5px;

    opacity: 0.9;
}


/* =====================================================
   FORM CARD
===================================================== */

.form-card {

    border: none;

    border-radius: 15px;

    box-shadow:
        0 5px 25px rgba(0,0,0,0.08);

    overflow: hidden;
}


/* =====================================================
   FORM SECTION
===================================================== */

.form-section {

    background: #f8f9fa;

    border-radius: 12px;

    padding: 20px;

    margin-bottom: 20px;
}


.section-title {

    font-weight: 700;

    color: #343a40;

    margin-bottom: 18px;
}


/* =====================================================
   LABELS
===================================================== */

.form-label {

    font-weight: 600;
}


.required {

    color: #dc3545;
}


/* =====================================================
   INPUTS
===================================================== */

.form-control,
.form-select {

    min-height: 48px;

    border-radius: 9px;
}


.form-control:focus,
.form-select:focus {

    border-color: #0d6efd;

    box-shadow:
        0 0 0 0.2rem
        rgba(13,110,253,0.15);
}


/* =====================================================
   BUTTONS
===================================================== */

.btn {

    border-radius: 8px;

    font-weight: 600;
}


/* =====================================================
   FOOTER
===================================================== */

.footer-text {

    text-align: center;

    color: #6c757d;

    margin-top: 25px;
}

</style>

</head>


<body>


<div class="page-container">


<!-- =================================================
     HEADER
================================================== -->

<div class="page-header
            d-flex
            justify-content-between
            align-items-center">


    <div>

        <h2>

            <i class="bi bi-pencil-square"></i>

            Edit Student

        </h2>

        <p class="mb-0">

            Update student academic and personal information.

        </p>

    </div>


    <div>

        <a
            href="index.php"
            class="btn btn-light">

            <i class="bi bi-arrow-left"></i>

            Students

        </a>

    </div>

</div>



<!-- =================================================
     SUCCESS MESSAGE
================================================== -->

<?php if (!empty($success)) { ?>

<div
    class="alert alert-success
           alert-dismissible
           fade show">

    <i class="bi bi-check-circle-fill"></i>

    <?php echo htmlspecialchars($success); ?>


    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>

</div>

<?php } ?>



<!-- =================================================
     ERROR MESSAGE
================================================== -->

<?php if (!empty($error)) { ?>

<div
    class="alert alert-danger
           alert-dismissible
           fade show">

    <i class="bi bi-exclamation-triangle-fill"></i>

    <?php echo htmlspecialchars($error); ?>


    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>

</div>

<?php } ?>



<!-- =================================================
     FORM CARD
================================================== -->

<div class="card form-card">

<div class="card-body p-4 p-md-5">


<form method="POST">


<!-- =================================================
     STUDENT INFORMATION
================================================== -->

<div class="form-section">


<h5 class="section-title">

    <i class="bi bi-person-vcard text-primary"></i>

    Student Information

</h5>



<div class="row g-4">


<!-- Registration Number -->

<div class="col-md-6">

<label class="form-label">

    Registration Number

    <span class="required">*</span>

</label>


<input
    type="text"
    name="registration_no"
    class="form-control"
    value="<?php
        echo htmlspecialchars(
            $student['registration_no']
        );
    ?>"
    required>


<small class="text-muted">

    Registration number must be unique.

</small>

</div>



<!-- Gender -->

<div class="col-md-6">

<label class="form-label">

    Gender

    <span class="required">*</span>

</label>


<select
    name="gender"
    class="form-select"
    required>


<option value="">

    Select Gender

</option>


<option
    value="Male"
    <?php
    if ($student['gender'] == "Male") {
        echo "selected";
    }
    ?>>

    Male

</option>


<option
    value="Female"
    <?php
    if ($student['gender'] == "Female") {
        echo "selected";
    }
    ?>>

    Female

</option>


</select>

</div>



<!-- First Name -->

<div class="col-md-6">

<label class="form-label">

    First Name

    <span class="required">*</span>

</label>


<input
    type="text"
    name="first_name"
    class="form-control"
    value="<?php
        echo htmlspecialchars(
            $student['first_name']
        );
    ?>"
    required>

</div>



<!-- Last Name -->

<div class="col-md-6">

<label class="form-label">

    Last Name

    <span class="required">*</span>

</label>


<input
    type="text"
    name="last_name"
    class="form-control"
    value="<?php
        echo htmlspecialchars(
            $student['last_name']
        );
    ?>"
    required>

</div>


</div>

</div>



<!-- =================================================
     ACADEMIC INFORMATION
================================================== -->

<div class="form-section">


<h5 class="section-title">

    <i class="bi bi-building text-primary"></i>

    Academic Information

</h5>


<div class="row">


<div class="col-md-6">


<label class="form-label">

    Department

    <span class="required">*</span>

</label>


<select
    name="department_id"
    class="form-select"
    required>


<option value="">

    Select Department

</option>


<?php

while (
    $department =
    mysqli_fetch_assoc($departments)
) {

?>

<option
    value="<?php
        echo $department['department_id'];
    ?>"
    <?php

    if (
        $student['department_id']
        ==
        $department['department_id']
    ) {

        echo "selected";

    }

    ?>>

    <?php

    echo htmlspecialchars(
        $department['department_name']
    );

    ?>

</option>

<?php

}

?>


</select>


</div>


</div>

</div>



<!-- =================================================
     ACTION BUTTONS
================================================== -->

<div
    class="d-flex
           justify-content-between
           align-items-center
           flex-wrap
           gap-2">


<!-- Cancel -->

<a
    href="index.php"
    class="btn btn-outline-secondary">

    <i class="bi bi-arrow-left"></i>

    Cancel

</a>



<!-- Update -->

<button
    type="submit"
    name="update_student"
    class="btn btn-primary px-4">

    <i class="bi bi-save"></i>

    Update Student

</button>


</div>


</form>


</div>

</div>



<!-- =================================================
     FOOTER
================================================== -->

<div class="footer-text">

<small>

    Student Information System
    &copy;
    <?php echo date("Y"); ?>

</small>

</div>


</div>



<!-- Bootstrap JS -->

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>