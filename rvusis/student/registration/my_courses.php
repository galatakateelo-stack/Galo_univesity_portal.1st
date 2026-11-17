<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

$student_id = intval($_SESSION['student_id']);


/* =========================================================
   STUDENT INFORMATION
========================================================= */

$student_query = mysqli_query($conn, "
    SELECT
        s.first_name,
        s.last_name,
        s.registration_no,
        d.department_name
    FROM students s
    LEFT JOIN departments d
        ON s.department_id = d.department_id
    WHERE s.student_id = '$student_id'
    LIMIT 1
");

if (!$student_query) {
    die("Database Error: " . mysqli_error($conn));
}

$student = mysqli_fetch_assoc($student_query);

if (!$student) {
    die("Student information not found.");
}


/* =========================================================
   CURRENT SEMESTER
========================================================= */

$semester_query = mysqli_query($conn, "
    SELECT *
    FROM semesters
    ORDER BY semester_id DESC
    LIMIT 1
");

if (!$semester_query) {
    die("Database Error: " . mysqli_error($conn));
}

$current_semester = mysqli_fetch_assoc($semester_query);

if (!$current_semester) {
    die("No semester found.");
}

$semester_id = intval($current_semester['semester_id']);


/* =========================================================
   REGISTERED COURSES
========================================================= */

$courses_query = mysqli_query($conn, "
    SELECT
        r.registration_id,
        r.student_id,
        r.course_id,
        r.semester_id,

        c.course_code,
        c.course_name,
        c.credit_hours,

        d.department_name,

        s.semester_name

    FROM course_registrations r

    INNER JOIN courses c
        ON r.course_id = c.course_id

    LEFT JOIN departments d
        ON c.department_id = d.department_id

    INNER JOIN semesters s
        ON r.semester_id = s.semester_id

    WHERE r.student_id = '$student_id'

    AND r.semester_id = '$semester_id'

    ORDER BY c.course_code ASC
");

if (!$courses_query) {
    die("Database Error: " . mysqli_error($conn));
}


/* =========================================================
   TOTAL COURSES & CREDITS
========================================================= */

$total_courses = 0;
$total_credits = 0;

while ($course = mysqli_fetch_assoc($courses_query)) {

    $total_courses++;

    $total_credits += intval($course['credit_hours']);
}


/* =========================================================
   RUN QUERY AGAIN FOR DISPLAY
========================================================= */

$courses_query = mysqli_query($conn, "
    SELECT
        r.registration_id,
        c.course_code,
        c.course_name,
        c.credit_hours,
        d.department_name,
        s.semester_name

    FROM course_registrations r

    INNER JOIN courses c
        ON r.course_id = c.course_id

    LEFT JOIN departments d
        ON c.department_id = d.department_id

    INNER JOIN semesters s
        ON r.semester_id = s.semester_id

    WHERE r.student_id = '$student_id'

    AND r.semester_id = '$semester_id'

    ORDER BY c.course_code ASC
");

if (!$courses_query) {
    die("Database Error: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        My Registered Courses | RVU Academic System
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

            background:
                linear-gradient(
                    135deg,
                    #198754,
                    #0f5132
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

            width: 70px;

            height: 70px;

            border-radius: 50%;

            background: #d1e7dd;

            color: #198754;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

        }


        .stat-card {

            border: none;

            border-radius: 14px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,0.07);

        }


        .course-number {

            width: 40px;

            height: 40px;

            border-radius: 10px;

            background: #e7f1ff;

            color: #0d6efd;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: bold;

        }


        .course-code {

            color: #0d6efd;

            font-weight: bold;

        }


        .credit-badge {

            background: #e7f1ff;

            color: #0d6efd;

            padding: 8px 12px;

            border-radius: 20px;

        }


        .status-badge {

            background: #d1e7dd;

            color: #146c43;

            padding: 8px 12px;

            border-radius: 20px;

        }


        .table {

            vertical-align: middle;

        }


        .table thead th {

            white-space: nowrap;

        }


        .btn {

            border-radius: 9px;

        }


        .empty-state {

            padding: 70px 20px;

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

                    <i class="bi bi-book-half"></i>

                    My Registered Courses

                </h2>

                <p class="mb-0 opacity-75">

                    View your courses registered for the current semester

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
                    href="index.php"
                    class="btn btn-warning">

                    <i class="bi bi-plus-circle"></i>

                    Register Course

                </a>

            </div>

        </div>

    </div>



    <!-- =====================================================
         STUDENT INFORMATION
    ====================================================== -->

    <div class="card main-card mb-4">

        <div class="card-body p-4">

            <div
                class="row
                       align-items-center">


                <div class="col-md-6">

                    <div
                        class="d-flex
                               align-items-center">

                        <div class="student-avatar">

                            <i class="bi bi-person"></i>

                        </div>


                        <div class="ms-3">

                            <h4 class="mb-1">

                                <?php

                                echo htmlspecialchars(
                                    $student['first_name']
                                    . " "
                                    . $student['last_name']
                                );

                                ?>

                            </h4>


                            <div class="text-muted">

                                Registration No:

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $student['registration_no']
                                    );

                                    ?>

                                </strong>

                            </div>


                            <div class="text-muted">

                                <?php

                                echo htmlspecialchars(
                                    $student['department_name']
                                );

                                ?>

                            </div>

                        </div>

                    </div>

                </div>



                <div class="col-md-6 mt-4 mt-md-0">

                    <div
                        class="row
                               g-3">


                        <div class="col-6">

                            <div
                                class="card
                                       stat-card
                                       bg-primary
                                       text-white">

                                <div
                                    class="card-body
                                           text-center">

                                    <i
                                        class="bi
                                               bi-book
                                               fs-3">
                                    </i>

                                    <h3 class="mb-0 mt-1">

                                        <?php
                                        echo $total_courses;
                                        ?>

                                    </h3>

                                    <small>

                                        Registered Courses

                                    </small>

                                </div>

                            </div>

                        </div>


                        <div class="col-6">

                            <div
                                class="card
                                       stat-card
                                       bg-success
                                       text-white">

                                <div
                                    class="card-body
                                           text-center">

                                    <i
                                        class="bi
                                               bi-clock
                                               fs-3">
                                    </i>

                                    <h3 class="mb-0 mt-1">

                                        <?php
                                        echo $total_credits;
                                        ?>

                                    </h3>

                                    <small>

                                        Credit Hours

                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    <!-- =====================================================
         SEMESTER
    ====================================================== -->

    <div class="card main-card">

        <div
            class="card-header
                   bg-dark
                   text-white
                   py-3
                   d-flex
                   justify-content-between
                   align-items-center">

            <h5 class="mb-0">

                <i class="bi bi-calendar3"></i>

                Registered Courses

            </h5>


            <span class="badge bg-light text-dark">

                <?php

                echo htmlspecialchars(
                    $current_semester['semester_name']
                );

                ?>

            </span>

        </div>


        <div class="card-body p-0">


            <?php

            if (mysqli_num_rows($courses_query) > 0) {

            ?>

                <div class="table-responsive">

                    <table
                        class="table
                               table-hover
                               mb-0">

                        <thead class="table-light">

                            <tr>

                                <th class="ps-4">
                                    #
                                </th>

                                <th>
                                    Course
                                </th>

                                <th>
                                    Course Name
                                </th>

                                <th>
                                    Department
                                </th>

                                <th>
                                    Credits
                                </th>

                                <th>
                                    Semester
                                </th>

                                <th>
                                    Status
                                </th>

                                <th class="text-center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php

                        $number = 1;

                        while (
                            $course =
                            mysqli_fetch_assoc(
                                $courses_query
                            )
                        ) {

                        ?>


                            <tr>


                                <td class="ps-4">

                                    <div
                                        class="course-number">

                                        <?php
                                        echo $number++;
                                        ?>

                                    </div>

                                </td>


                                <td>

                                    <span
                                        class="course-code">

                                        <?php

                                        echo htmlspecialchars(
                                            $course[
                                                'course_code'
                                            ]
                                        );

                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $course[
                                                'course_name'
                                            ]
                                        );

                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course[
                                            'department_name'
                                        ]
                                    );

                                    ?>

                                </td>


                                <td>

                                    <span
                                        class="credit-badge">

                                        <?php

                                        echo intval(
                                            $course[
                                                'credit_hours'
                                            ]
                                        );

                                        ?>

                                        Credits

                                    </span>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course[
                                            'semester_name'
                                        ]
                                    );

                                    ?>

                                </td>


                                <td>

                                    <span
                                        class="status-badge">

                                        <i
                                            class="bi
                                                   bi-check-circle">
                                        </i>

                                        Registered

                                    </span>

                                </td>


                                <td
                                    class="text-center">


                                    <a
                                        href="drop_course.php?id=<?php
                                            echo $course[
                                                'registration_id'
                                            ];
                                        ?>"
                                        class="btn
                                               btn-outline-danger
                                               btn-sm"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to drop this course?'
                                            );
                                        ">

                                        <i
                                            class="bi
                                                   bi-x-circle">
                                        </i>

                                        Drop

                                    </a>


                                </td>


                            </tr>


                        <?php

                        }

                        ?>


                        </tbody>

                    </table>

                </div>


            <?php

            } else {

            ?>


                <!-- EMPTY STATE -->

                <div
                    class="empty-state
                           text-center">

                    <i
                        class="bi
                               bi-journal-x
                               display-3
                               text-muted">
                    </i>


                    <h4 class="mt-4">

                        No Registered Courses

                    </h4>


                    <p class="text-muted">

                        You have not registered for any
                        courses in this semester yet.

                    </p>


                    <a
                        href="index.php"
                        class="btn btn-primary">

                        <i
                            class="bi
                                   bi-plus-circle">
                        </i>

                        Register Courses

                    </a>

                </div>


            <?php

            }

            ?>


        </div>

    </div>



    <!-- =====================================================
         FOOTER ACTIONS
    ====================================================== -->

    <div
        class="d-flex
               justify-content-between
               mt-4
               flex-wrap">

        <a
            href="../dashboard.php"
            class="btn btn-secondary">

            <i class="bi bi-arrow-left"></i>

            Back to Dashboard

        </a>


        <a
            href="index.php"
            class="btn btn-success">

            <i class="bi bi-plus-circle"></i>

            Register Another Course

        </a>

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>