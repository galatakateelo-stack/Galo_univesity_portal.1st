<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

$student_id = intval($_SESSION['student_id']);

$message = "";
$message_type = "";


/* =========================================================
   STUDENT INFORMATION
========================================================= */

$student_sql = "
    SELECT
        s.student_id,
        s.first_name,
        s.last_name,
        s.registration_no,
        d.department_name
    FROM students s
    LEFT JOIN departments d
        ON s.department_id = d.department_id
    WHERE s.student_id = '$student_id'
    LIMIT 1
";

$student_result = mysqli_query($conn, $student_sql);

if (!$student_result) {
    die("Database Error: " . mysqli_error($conn));
}

$student = mysqli_fetch_assoc($student_result);

if (!$student) {
    die("Student information not found.");
}


/* =========================================================
   ACTIVE SEMESTER
========================================================= */

$semester_result = mysqli_query($conn, "
    SELECT *
    FROM semesters
    ORDER BY semester_id DESC
    LIMIT 1
");

if (!$semester_result) {
    die("Database Error: " . mysqli_error($conn));
}

$current_semester = mysqli_fetch_assoc($semester_result);

if (!$current_semester) {
    die("No semester has been created yet.");
}

$semester_id = intval($current_semester['semester_id']);


/* =========================================================
   COURSE REGISTRATION
========================================================= */

if (isset($_POST['register'])) {

    $course_id = intval($_POST['course_id']);

    if ($course_id <= 0) {

        $message = "Please select a valid course.";
        $message_type = "danger";

    } else {

        /* Check whether course exists */

        $course_check = mysqli_query($conn, "
            SELECT course_id
            FROM courses
            WHERE course_id = '$course_id'
            LIMIT 1
        ");

        if (!$course_check || mysqli_num_rows($course_check) == 0) {

            $message = "Selected course does not exist.";
            $message_type = "danger";

        } else {

            /* Check duplicate registration */

            $duplicate = mysqli_query($conn, "
                SELECT registration_id
                FROM course_registrations
                WHERE student_id = '$student_id'
                AND course_id = '$course_id'
                AND semester_id = '$semester_id'
                LIMIT 1
            ");

            if (!$duplicate) {
                die("Database Error: " . mysqli_error($conn));
            }


            if (mysqli_num_rows($duplicate) > 0) {

                $message =
                    "You have already registered for this course.";

                $message_type = "warning";

            } else {

                /* Register */

                $insert = mysqli_query($conn, "
                    INSERT INTO course_registrations
                    (
                        student_id,
                        course_id,
                        semester_id
                    )
                    VALUES
                    (
                        '$student_id',
                        '$course_id',
                        '$semester_id'
                    )
                ");

                if ($insert) {

                    $message =
                        "Course registered successfully.";

                    $message_type = "success";

                } else {

                    $message =
                        "Unable to register for this course.";

                    $message_type = "danger";
                }
            }
        }
    }
}


/* =========================================================
   AVAILABLE COURSES
========================================================= */

$courses = mysqli_query($conn, "
    SELECT
        c.course_id,
        c.course_code,
        c.course_name,
        c.credit_hours,
        d.department_name
    FROM courses c
    LEFT JOIN departments d
        ON c.department_id = d.department_id
    WHERE c.department_id = (
        SELECT department_id
        FROM students
        WHERE student_id = '$student_id'
    )
    ORDER BY c.course_code ASC
");

if (!$courses) {
    die("Course Query Error: " . mysqli_error($conn));
}


/* =========================================================
   REGISTERED CREDIT HOURS
========================================================= */

$credit_result = mysqli_query($conn, "
    SELECT
        COALESCE(SUM(c.credit_hours), 0) AS total_credits
    FROM course_registrations r
    INNER JOIN courses c
        ON r.course_id = c.course_id
    WHERE r.student_id = '$student_id'
    AND r.semester_id = '$semester_id'
");

if (!$credit_result) {
    die("Database Error: " . mysqli_error($conn));
}

$credit_data = mysqli_fetch_assoc($credit_result);

$total_credits = intval($credit_data['total_credits']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Course Registration | RVU Academic System
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    <style>

        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .page-header {
            background: linear-gradient(
                135deg,
                #0d6efd,
                #084298
            );

            color: white;

            border-radius: 16px;

            padding: 28px;

            margin-bottom: 25px;

            box-shadow:
                0 6px 20px
                rgba(0,0,0,0.12);
        }

        .main-card {
            border: none;
            border-radius: 16px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.08);
        }

        .student-avatar {
            width: 75px;
            height: 75px;

            border-radius: 50%;

            background: #cfe2ff;
            color: #0d6efd;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 32px;
        }

        .stat-card {
            border: none;
            border-radius: 14px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,0.07);
        }

        .course-card {
            border: 1px solid #e9ecef;

            border-radius: 14px;

            transition: 0.2s;
        }

        .course-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(0,0,0,0.08);
        }

        .course-code {
            font-weight: 700;
            color: #0d6efd;
        }

        .credit-badge {
            background: #e7f1ff;
            color: #0d6efd;
        }

        .btn {
            border-radius: 9px;
        }

        .form-select {
            border-radius: 10px;
            padding: 12px;
        }

    </style>

</head>


<body>


<div class="container-fluid px-4 py-4">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="page-header">

        <div
            class="d-flex
                   justify-content-between
                   align-items-center
                   flex-wrap">

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-journal-plus"></i>

                    Course Registration

                </h2>

                <p class="mb-0 opacity-75">

                    Register for courses for the current semester

                </p>

            </div>


            <div class="mt-3 mt-md-0">

                <a
                    href="../dashboard.php"
                    class="btn btn-light me-2">

                    <i class="bi bi-house"></i>

                    Dashboard

                </a>


                <a
                    href="my_courses.php"
                    class="btn btn-warning">

                    <i class="bi bi-book"></i>

                    My Courses

                </a>

            </div>

        </div>

    </div>



    <!-- =====================================================
         ALERT
    ====================================================== -->

    <?php if ($message != "") { ?>

        <div
            class="alert
                   alert-<?php echo $message_type; ?>
                   alert-dismissible
                   fade show
                   shadow-sm">

            <i class="bi bi-info-circle"></i>

            <?php
            echo htmlspecialchars($message);
            ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php } ?>



    <div class="row g-4">


        <!-- =================================================
             STUDENT INFORMATION
        ================================================== -->

        <div class="col-lg-4">

            <div class="card main-card">

                <div class="card-body p-4">


                    <div
                        class="d-flex
                               align-items-center
                               mb-4">

                        <div class="student-avatar">

                            <i class="bi bi-person"></i>

                        </div>


                        <div class="ms-3">

                            <h5 class="mb-1">

                                <?php
                                echo htmlspecialchars(
                                    $student['first_name']
                                    . " "
                                    . $student['last_name']
                                );
                                ?>

                            </h5>

                            <small class="text-muted">

                                Student

                            </small>

                        </div>

                    </div>



                    <div class="mb-3">

                        <small class="text-muted">

                            Registration Number

                        </small>

                        <div class="fw-bold">

                            <?php
                            echo htmlspecialchars(
                                $student['registration_no']
                            );
                            ?>

                        </div>

                    </div>


                    <div class="mb-3">

                        <small class="text-muted">

                            Department

                        </small>

                        <div class="fw-bold">

                            <?php
                            echo htmlspecialchars(
                                $student['department_name']
                            );
                            ?>

                        </div>

                    </div>


                    <div>

                        <small class="text-muted">

                            Current Semester

                        </small>

                        <div class="fw-bold text-primary">

                            <?php
                            echo htmlspecialchars(
                                $current_semester[
                                    'semester_name'
                                ]
                            );
                            ?>

                        </div>

                    </div>

                </div>

            </div>



            <!-- CREDIT SUMMARY -->

            <div class="card stat-card mt-4">

                <div class="card-body p-4">

                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center">

                        <div>

                            <small class="text-muted">

                                Registered Credits

                            </small>

                            <h2 class="mb-0">

                                <?php
                                echo $total_credits;
                                ?>

                            </h2>

                        </div>


                        <div
                            class="rounded-circle
                                   bg-primary
                                   text-white
                                   p-3">

                            <i
                                class="bi bi-clock-history
                                       fs-4">
                            </i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             COURSE REGISTRATION
        ================================================== -->

        <div class="col-lg-8">

            <div class="card main-card">

                <div
                    class="card-header
                           bg-dark
                           text-white
                           py-3">

                    <h5 class="mb-0">

                        <i class="bi bi-book-half"></i>

                        Available Courses

                    </h5>

                </div>


                <div class="card-body p-4">


                    <?php

                    if (mysqli_num_rows($courses) > 0) {

                        while (
                            $course =
                            mysqli_fetch_assoc($courses)
                        ) {

                    ?>

                        <div
                            class="course-card
                                   p-3
                                   mb-3">

                            <div
                                class="row
                                       align-items-center">


                                <!-- COURSE INFO -->

                                <div class="col-md-7">

                                    <div class="course-code">

                                        <?php
                                        echo htmlspecialchars(
                                            $course[
                                                'course_code'
                                            ]
                                        );
                                        ?>

                                    </div>


                                    <h5 class="mb-1">

                                        <?php
                                        echo htmlspecialchars(
                                            $course[
                                                'course_name'
                                            ]
                                        );
                                        ?>

                                    </h5>


                                    <small
                                        class="text-muted">

                                        <i
                                            class="bi
                                                   bi-building">
                                        </i>

                                        <?php
                                        echo htmlspecialchars(
                                            $course[
                                                'department_name'
                                            ]
                                        );
                                        ?>

                                    </small>

                                </div>



                                <!-- CREDIT -->

                                <div class="col-md-2 mt-3 mt-md-0">

                                    <span
                                        class="badge
                                               credit-badge
                                               p-2">

                                        <?php
                                        echo $course[
                                            'credit_hours'
                                        ];
                                        ?>

                                        Credit

                                    </span>

                                </div>



                                <!-- REGISTER -->

                                <div
                                    class="col-md-3
                                           mt-3
                                           mt-md-0">

                                    <form
                                        method="POST">

                                        <input
                                            type="hidden"
                                            name="course_id"
                                            value="<?php
                                                echo $course[
                                                    'course_id'
                                                ];
                                            ?>">


                                        <button
                                            type="submit"
                                            name="register"
                                            class="btn
                                                   btn-success
                                                   w-100"
                                            onclick="
                                                return confirm(
                                                    'Register for this course?'
                                                );
                                            ">

                                            <i
                                                class="bi
                                                       bi-check-circle">
                                            </i>

                                            Register

                                        </button>

                                    </form>

                                </div>


                            </div>

                        </div>

                    <?php

                        }

                    } else {

                    ?>

                        <div
                            class="text-center
                                   py-5">

                            <i
                                class="bi
                                       bi-journal-x
                                       display-4
                                       text-muted">
                            </i>

                            <h5 class="mt-3">

                                No Courses Available

                            </h5>

                            <p class="text-muted">

                                There are currently no courses
                                available for your department.

                            </p>

                        </div>

                    <?php } ?>


                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>