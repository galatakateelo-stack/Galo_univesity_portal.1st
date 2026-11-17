<?php
session_start();

include("../../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";

/* =========================================================
   ADD COURSE
========================================================= */

if (isset($_POST['add_course'])) {

    // Get submitted values safely
    $course_code   = trim($_POST['course_code'] ?? '');
    $course_name   = trim($_POST['course_name'] ?? '');
    $credit_hours  = (int)($_POST['credit_hours'] ?? 0);
    $department_id = (int)($_POST['department_id'] ?? 0);
    $semester_id   = (int)($_POST['semester_id'] ?? 0);

    /* Validation */

    if (
        $course_code === '' ||
        $course_name === '' ||
        $credit_hours <= 0 ||
        $department_id <= 0 ||
        $semester_id <= 0
    ) {

        $message = '
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Please complete all required fields.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }

    else {

        $course_code = mysqli_real_escape_string($conn, $course_code);
        $course_name = mysqli_real_escape_string($conn, $course_name);

        /* Check duplicate course code */

        $check = mysqli_query(
            $conn,
            "SELECT course_id
             FROM courses
             WHERE course_code = '$course_code'
             LIMIT 1"
        );

        if (mysqli_num_rows($check) > 0) {

            $message = '
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="bi bi-exclamation-circle me-2"></i>
                Course code <strong>' . htmlspecialchars($course_code) . '</strong>
                already exists.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }

        else {

            /* Insert course */

            $sql = "
                INSERT INTO courses
                (
                    course_code,
                    course_name,
                    credit_hours,
                    department_id,
                    semester_id
                )
                VALUES
                (
                    '$course_code',
                    '$course_name',
                    '$credit_hours',
                    '$department_id',
                    '$semester_id'
                )
            ";

            if (mysqli_query($conn, $sql)) {

                $message = '
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Course <strong>' . htmlspecialchars($course_code) . '</strong>
                    was added successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';

            } else {

                $message = '
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-database-exclamation me-2"></i>
                    Database Error: ' . htmlspecialchars(mysqli_error($conn)) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
        }
    }
}


/* =========================================================
   LOAD DEPARTMENTS
========================================================= */

$departments = mysqli_query(
    $conn,
    "SELECT department_id, department_name
     FROM departments
     ORDER BY department_name ASC"
);


/* =========================================================
   LOAD SEMESTERS
========================================================= */

$semesters = mysqli_query(
    $conn,
    "SELECT semester_id, semester_name
     FROM semesters
     ORDER BY semester_id ASC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add New Course | Administrator</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        body {
            background: #f4f7fb;
            font-family: Arial, sans-serif;
        }

        .course-card {
            max-width: 1000px;
            margin: 40px auto;
            border: none;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 35px rgba(0,0,0,0.10);
        }

        .course-header {
            background: linear-gradient(135deg, #0d6efd, #084298);
            color: white;
            padding: 30px;
        }

        .course-header h2 {
            margin: 0;
            font-weight: 700;
        }

        .course-header p {
            margin: 5px 0 0;
            opacity: .85;
        }

        .course-body {
            padding: 35px;
            background: white;
        }

        .form-label {
            font-weight: 600;
        }

        .form-control,
        .form-select {
            min-height: 50px;
            border-radius: 10px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 .2rem rgba(13,110,253,.15);
        }

        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 11px 20px;
        }

        .section-title {
            font-weight: 700;
            color: #212529;
            margin-bottom: 25px;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="card course-card">

        <!-- HEADER -->

        <div class="course-header">

            <div class="d-flex align-items-center">

                <i class="bi bi-book-half fs-1 me-3"></i>

                <div>

                    <h2>Add New Course</h2>

                    <p>Create a new academic course</p>

                </div>

            </div>

        </div>


        <!-- BODY -->

        <div class="course-body">

            <?php echo $message; ?>


            <div class="alert alert-primary border-start border-primary border-4">

                <i class="bi bi-info-circle-fill me-2"></i>

                Enter the course information carefully.
                Course code must be unique.

            </div>


            <h5 class="section-title">

                <i class="bi bi-journal-plus me-2 text-primary"></i>

                Course Information

            </h5>


            <form method="POST" action="">


                <div class="row g-4">


                    <!-- COURSE CODE -->

                    <div class="col-md-6">

                        <label class="form-label">

                            <i class="bi bi-upc-scan me-1"></i>

                            Course Code

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="text"
                            name="course_code"
                            class="form-control"
                            placeholder="Example: CS1206"
                            maxlength="50"
                            required>

                        <small class="text-muted">
                            Example: CS1206, MGT106
                        </small>

                    </div>


                    <!-- COURSE NAME -->

                    <div class="col-md-6">

                        <label class="form-label">

                            <i class="bi bi-book me-1"></i>

                            Course Name

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="text"
                            name="course_name"
                            class="form-control"
                            placeholder="Example: Database Systems"
                            maxlength="150"
                            required>

                    </div>


                    <!-- CREDIT HOURS -->

                    <div class="col-md-6">

                        <label class="form-label">

                            <i class="bi bi-clock me-1"></i>

                            Credit Hours

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="number"
                            name="credit_hours"
                            class="form-control"
                            min="1"
                            max="10"
                            placeholder="Example: 3"
                            required>

                    </div>


                    <!-- DEPARTMENT -->

                    <div class="col-md-6">

                        <label class="form-label">

                            <i class="bi bi-building me-1"></i>

                            Department

                            <span class="text-danger">*</span>

                        </label>

                        <select
                            name="department_id"
                            class="form-select"
                            required>

                            <option value="">
                                Select Department
                            </option>

                            <?php

                            if ($departments && mysqli_num_rows($departments) > 0) {

                                while ($department = mysqli_fetch_assoc($departments)) {

                            ?>

                                <option
                                    value="<?php echo $department['department_id']; ?>">

                                    <?php
                                    echo htmlspecialchars(
                                        $department['department_name']
                                    );
                                    ?>

                                </option>

                            <?php

                                }

                            }

                            ?>

                        </select>

                    </div>


                    <!-- SEMESTER -->

                    <div class="col-md-6">

                        <label class="form-label">

                            <i class="bi bi-calendar3 me-1"></i>

                            Semester

                            <span class="text-danger">*</span>

                        </label>

                        <select
                            name="semester_id"
                            class="form-select"
                            required>

                            <option value="">
                                Select Semester
                            </option>

                            <?php

                            if ($semesters && mysqli_num_rows($semesters) > 0) {

                                while ($semester = mysqli_fetch_assoc($semesters)) {

                            ?>

                                <option
                                    value="<?php echo $semester['semester_id']; ?>">

                                    <?php
                                    echo htmlspecialchars(
                                        $semester['semester_name']
                                    );
                                    ?>

                                </option>

                            <?php

                                }

                            }

                            ?>

                        </select>

                    </div>


                </div>


                <hr class="my-4">


                <!-- BUTTONS -->

                <div class="d-flex justify-content-between">

                    <a
                        href="index.php"
                        class="btn btn-secondary">

                        <i class="bi bi-arrow-left me-1"></i>

                        Back to Courses

                    </a>


                    <button
                        type="submit"
                        name="add_course"
                        class="btn btn-primary">

                        <i class="bi bi-plus-circle me-1"></i>

                        Add Course

                    </button>

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