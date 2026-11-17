<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

$message = "";

/* =========================================================
   ASSIGN COURSE
========================================================= */

if (isset($_POST['assign'])) {

    $instructor_id = mysqli_real_escape_string(
        $conn,
        $_POST['instructor_id']
    );

    $course_id = mysqli_real_escape_string(
        $conn,
        $_POST['course_id']
    );

    $semester_id = mysqli_real_escape_string(
        $conn,
        $_POST['semester_id']
    );

    $academic_year = trim($_POST['academic_year']);

    $academic_year_safe = mysqli_real_escape_string(
        $conn,
        $academic_year
    );

    /* Validate academic year */

    if (!preg_match('/^[0-9]{4}\/[0-9]{2,4}$/', $academic_year)) {

        $message = "
        <div class='alert alert-danger alert-dismissible fade show'>
            <i class='bi bi-exclamation-triangle-fill me-2'></i>
            Please enter a valid academic year, for example
            <strong>2026/27</strong>.
            <button type='button'
                    class='btn-close'
                    data-bs-dismiss='alert'>
            </button>
        </div>";

    } else {

        /* =================================================
           CHECK DUPLICATE ASSIGNMENT
        ================================================= */

        $check = mysqli_query($conn, "
            SELECT id
            FROM instructor_courses
            WHERE instructor_id='$instructor_id'
            AND course_id='$course_id'
            AND semester_id='$semester_id'
            AND academic_year='$academic_year_safe'
        ");

        if (!$check) {

            $message = "
            <div class='alert alert-danger'>
                <i class='bi bi-database-x me-2'></i>
                Database Error:
                " . htmlspecialchars(mysqli_error($conn)) . "
            </div>";

        } elseif (mysqli_num_rows($check) > 0) {

            $message = "
            <div class='alert alert-warning alert-dismissible fade show'>
                <i class='bi bi-exclamation-circle-fill me-2'></i>
                This instructor is already assigned to this course
                for the selected semester and academic year.
                <button type='button'
                        class='btn-close'
                        data-bs-dismiss='alert'>
                </button>
            </div>";

        } else {

            /* =================================================
               INSERT ASSIGNMENT
            ================================================= */

            $insert = mysqli_query($conn, "
                INSERT INTO instructor_courses
                (
                    instructor_id,
                    course_id,
                    semester_id,
                    academic_year
                )
                VALUES
                (
                    '$instructor_id',
                    '$course_id',
                    '$semester_id',
                    '$academic_year_safe'
                )
            ");

            if ($insert) {

                $message = "
                <div class='alert alert-success alert-dismissible fade show'>
                    <i class='bi bi-check-circle-fill me-2'></i>
                    <strong>Assignment successful!</strong>
                    The course has been assigned to the instructor.
                    <button type='button'
                            class='btn-close'
                            data-bs-dismiss='alert'>
                    </button>
                </div>";

            } else {

                $message = "
                <div class='alert alert-danger alert-dismissible fade show'>
                    <i class='bi bi-x-circle-fill me-2'></i>
                    Unable to assign the course.
                    <button type='button'
                            class='btn-close'
                            data-bs-dismiss='alert'>
                    </button>
                </div>";
            }
        }
    }
}


/* =========================================================
   FETCH INSTRUCTORS
========================================================= */

$instructors = mysqli_query(
    $conn,
    "
    SELECT instructor_id, full_name
    FROM instructors
    ORDER BY full_name ASC
    "
);


/* =========================================================
   FETCH COURSES
========================================================= */

$courses = mysqli_query(
    $conn,
    "
    SELECT
        course_id,
        course_code,
        course_name
    FROM courses
    ORDER BY course_code ASC
    "
);


/* =========================================================
   FETCH SEMESTERS
========================================================= */

$semesters = mysqli_query(
    $conn,
    "
    SELECT
        semester_id,
        semester_name,
        academic_year
    FROM semesters
    ORDER BY semester_id ASC
    "
);


/* =========================================================
   FETCH ASSIGNMENTS
========================================================= */

$list = mysqli_query(
    $conn,
    "
    SELECT

        ic.id,

        i.full_name,

        c.course_code,
        c.course_name,

        s.semester_name,

        ic.academic_year

    FROM instructor_courses ic

    INNER JOIN instructors i
        ON ic.instructor_id = i.instructor_id

    INNER JOIN courses c
        ON ic.course_id = c.course_id

    INNER JOIN semesters s
        ON ic.semester_id = s.semester_id

    ORDER BY ic.id DESC
    "
);


/* =========================================================
   TOTAL ASSIGNMENTS
========================================================= */

$count_result = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM instructor_courses
    "
);

$count_data = mysqli_fetch_assoc($count_result);

$total_assignments = $count_data['total'];

?>

<!DOCTYPE html>

<html lang="en">

<head>
<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Instructor Course Assignment | Administrator
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

        background: #f5f7fb;

        font-family: Arial, sans-serif;

    }


    /* HEADER */

    .page-header {

        background:
            linear-gradient(
                135deg,
                #111827,
                #374151
            );

        color: white;

        border-radius: 18px;

        padding: 30px;

        margin-bottom: 25px;

        box-shadow:
            0 8px 25px
            rgba(0,0,0,0.12);

    }


    .page-header h2 {

        font-weight: 700;

        margin-bottom: 5px;

    }


    .page-header p {

        margin-bottom: 0;

        opacity: 0.85;

    }


    /* STAT CARD */

    .stat-card {

        border: none;

        border-radius: 16px;

        box-shadow:
            0 6px 20px
            rgba(0,0,0,0.08);

    }


    .stat-icon {

        width: 55px;

        height: 55px;

        border-radius: 14px;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 25px;

        background:
            rgba(255,255,255,0.20);

    }


    /* FORM CARD */

    .form-card {

        border: none;

        border-radius: 18px;

        box-shadow:
            0 6px 25px
            rgba(0,0,0,0.08);

    }


    .form-label {

        font-weight: 600;

        color: #374151;

    }


    .form-control,
    .form-select {

        border-radius: 10px;

        padding: 11px 14px;

    }


    .form-control:focus,
    .form-select:focus {

        border-color: #6366f1;

        box-shadow:
            0 0 0 0.2rem
            rgba(99,102,241,0.15);

    }


    /* TABLE */

    .table-card {

        border: none;

        border-radius: 18px;

        overflow: hidden;

        box-shadow:
            0 6px 25px
            rgba(0,0,0,0.08);

    }


    .table thead th {

        background: #172033;

        color: white;

        padding: 15px;

        white-space: nowrap;

    }


    .table tbody td {

        padding: 15px;

        vertical-align: middle;

    }


    .table tbody tr:hover {

        background: #f8fafc;

    }


    /* BADGES */

    .course-badge {

        background: #eef2ff;

        color: #4338ca;

        padding: 7px 12px;

        border-radius: 20px;

        font-weight: 600;

    }


    .semester-badge {

        background: #ecfdf5;

        color: #047857;

        padding: 7px 12px;

        border-radius: 20px;

        font-weight: 600;

    }


    .year-badge {

        background: #fff7ed;

        color: #c2410c;

        padding: 7px 12px;

        border-radius: 20px;

        font-weight: 600;

    }


    .action-btn {

        border-radius: 8px;

    }


    .empty-state {

        padding: 50px 20px;

        text-align: center;

        color: #6b7280;

    }


    .empty-state i {

        font-size: 50px;

        color: #9ca3af;

        margin-bottom: 15px;

    }


</style>
</head>

<body>

<div class="container-fluid px-4 py-4">
<!-- =================================================
     PAGE HEADER
================================================= -->

<div class="page-header">

    <div
        class="d-flex
               flex-column
               flex-md-row
               justify-content-between
               align-items-md-center
               gap-3">

        <div>

            <h2>

                <i class="bi bi-person-workspace me-2"></i>

                Instructor Course Assignment

            </h2>

            <p>

                Assign courses to instructors by semester
                and academic year.

            </p>

        </div>


        <div>

            <a
                href="../dashboard.php"
                class="btn btn-light">

                <i class="bi bi-arrow-left me-1"></i>

                Dashboard

            </a>

        </div>

    </div>

</div>


<!-- =================================================
     STATISTICS
================================================= -->

<div class="row mb-4">

    <div class="col-md-4">

        <div
            class="card
                   stat-card
                   bg-primary
                   text-white">

            <div
                class="card-body
                       d-flex
                       justify-content-between
                       align-items-center">

                <div>

                    <small>
                        Total Assignments
                    </small>

                    <h2 class="fw-bold mb-0">

                        <?php
                        echo $total_assignments;
                        ?>

                    </h2>

                </div>


                <div class="stat-icon">

                    <i class="bi bi-person-check"></i>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =================================================
     ASSIGN COURSE FORM
================================================= -->

<div class="card form-card mb-4">

    <div class="card-body p-4">


        <div class="mb-4">

            <h4 class="fw-bold mb-1">

                <i
                    class="bi bi-plus-circle-fill
                           text-success
                           me-2">
                </i>

                Create New Assignment

            </h4>

            <small class="text-muted">

                Select an instructor, course,
                semester and academic year.

            </small>

        </div>


        <?php echo $message; ?>


        <form method="POST">


            <div class="row g-3">


                <!-- INSTRUCTOR -->

                <div class="col-lg-3 col-md-6">

                    <label class="form-label">

                        <i class="bi bi-person-fill me-1"></i>

                        Instructor

                    </label>

                    <select
                        name="instructor_id"
                        class="form-select"
                        required>

                        <option value="">
                            Select Instructor
                        </option>


                        <?php

                        while (
                            $i =
                            mysqli_fetch_assoc(
                                $instructors
                            )
                        ) {

                        ?>

                            <option
                                value="<?php
                                echo $i['instructor_id'];
                                ?>">

                                <?php
                                echo htmlspecialchars(
                                    $i['full_name']
                                );
                                ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>


                <!-- COURSE -->

                <div class="col-lg-3 col-md-6">

                    <label class="form-label">

                        <i class="bi bi-book-fill me-1"></i>

                        Course

                    </label>

                    <select
                        name="course_id"
                        class="form-select"
                        required>

                        <option value="">
                            Select Course
                        </option>


                        <?php

                        while (
                            $c =
                            mysqli_fetch_assoc(
                                $courses
                            )
                        ) {

                        ?>

                            <option
                                value="<?php
                                echo $c['course_id'];
                                ?>">

                                <?php
                                echo htmlspecialchars(
                                    $c['course_code']
                                    . " - "
                                    . $c['course_name']
                                );
                                ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>


                <!-- SEMESTER -->

                <div class="col-lg-2 col-md-6">

                    <label class="form-label">

                        <i
                            class="bi bi-calendar3 me-1">
                        </i>

                        Semester

                    </label>

                    <select
                        name="semester_id"
                        class="form-select"
                        required>

                        <option value="">
                            Select Semester
                        </option>


                        <?php

                        while (
                            $s =
                            mysqli_fetch_assoc(
                                $semesters
                            )
                        ) {

                        ?>

                            <option
                                value="<?php
                                echo $s['semester_id'];
                                ?>">

                                <?php
                                echo htmlspecialchars(
                                    $s['semester_name']
                                );
                                ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>


                <!-- ACADEMIC YEAR -->

                <div class="col-lg-2 col-md-6">

                    <label class="form-label">

                        <i
                            class="bi bi-calendar-range me-1">
                        </i>

                        Academic Year

                    </label>

                    <input
                        type="text"
                        name="academic_year"
                        class="form-control"
                        placeholder="2026/27"
                        pattern="[0-9]{4}/[0-9]{2,4}"
                        title="Example: 2026/27"
                        required>

                </div>


                <!-- BUTTON -->

                <div
                    class="col-lg-2 col-md-12
                           d-flex
                           align-items-end">

                    <button
                        type="submit"
                        name="assign"
                        class="btn btn-success
                               w-100 py-2">

                        <i
                            class="bi bi-person-plus-fill
                                   me-1">
                        </i>

                        Assign Course

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


<!-- =================================================
     ASSIGNMENT TABLE
================================================= -->

<div class="card table-card">


    <div class="card-body p-0">


        <div
            class="p-4
                   border-bottom
                   bg-white">

            <div
                class="d-flex
                       flex-column
                       flex-md-row
                       justify-content-between
                       align-items-md-center
                       gap-3">

                <div>

                    <h4 class="fw-bold mb-1">

                        <i
                            class="bi bi-list-check
                                   text-primary
                                   me-2">
                        </i>

                        Assigned Courses

                    </h4>

                    <small class="text-muted">

                        Current instructor-course
                        assignments.

                    </small>

                </div>


                <span
                    class="badge
                           bg-primary
                           fs-6">

                    <?php
                    echo $total_assignments;
                    ?>

                    Assignments

                </span>

            </div>

        </div>


        <div class="table-responsive">


            <table
                class="table
                       table-hover
                       mb-0">


                <thead>

                    <tr>

                        <th>#</th>

                        <th>Instructor</th>

                        <th>Course</th>

                        <th>Semester</th>

                        <th>Academic Year</th>

                        <th class="text-center">

                            Action

                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php

                if (
                    $list &&
                    mysqli_num_rows($list) > 0
                ) {

                    $no = 1;

                    while (
                        $row =
                        mysqli_fetch_assoc($list)
                    ) {

                ?>


                    <tr>


                        <td class="fw-bold text-muted">

                            <?php
                            echo $no++;
                            ?>

                        </td>


                        <!-- INSTRUCTOR -->

                        <td>

                            <strong>

                                <i
                                    class="bi bi-person-circle
                                           text-primary
                                           me-1">
                                </i>

                                <?php
                                echo htmlspecialchars(
                                    $row['full_name']
                                );
                                ?>

                            </strong>

                        </td>


                        <!-- COURSE -->

                        <td>

                            <span class="course-badge">

                                <?php
                                echo htmlspecialchars(
                                    $row['course_code']
                                );
                                ?>

                            </span>

                            <div class="small text-muted mt-1">

                                <?php
                                echo htmlspecialchars(
                                    $row['course_name']
                                );
                                ?>

                            </div>

                        </td>


                        <!-- SEMESTER -->

                        <td>

                            <span class="semester-badge">

                                <?php
                                echo htmlspecialchars(
                                    $row['semester_name']
                                );
                                ?>

                            </span>

                        </td>


                        <!-- YEAR -->

                        <td>

                            <span class="year-badge">

                                <i
                                    class="bi bi-calendar-event
                                           me-1">
                                </i>

                                <?php
                                echo htmlspecialchars(
                                    $row['academic_year']
                                );
                                ?>

                            </span>

                        </td>


                        <!-- ACTION -->

                        <td class="text-center">

                            <a
                                href="delete.php?id=<?php
                                    echo $row['id'];
                                ?>"
                                class="btn btn-danger
                                       btn-sm
                                       action-btn"
                                onclick="
                                    return confirm(
                                        'Are you sure you want to remove this course assignment?'
                                    );
                                ">

                                <i
                                    class="bi bi-trash3 me-1">
                                </i>

                                Remove

                            </a>

                        </td>


                    </tr>


                <?php

                    }

                } else {

                ?>


                    <tr>

                        <td colspan="6">

                            <div class="empty-state">

                                <i
                                    class="bi bi-person-workspace">
                                </i>

                                <h5>
                                    No Course Assignments
                                </h5>

                                <p>
                                    No instructor courses
                                    have been assigned yet.
                                </p>

                            </div>

                        </td>

                    </tr>


                <?php } ?>


                </tbody>

            </table>

        </div>

    </div>


    <!-- FOOTER -->

    <div
        class="card-footer
               bg-white
               border-0
               p-4">

        <div
            class="d-flex
                   justify-content-between
                   align-items-center
                   flex-wrap
                   gap-2">

            <small class="text-muted">

                <i
                    class="bi bi-shield-check
                           text-success
                           me-1">
                </i>

                Administrator Course Assignment

            </small>


            <a
                href="../dashboard.php"
                class="btn btn-outline-dark">

                <i class="bi bi-arrow-left me-1"></i>

                Back to Dashboard

            </a>

        </div>

    </div>


</div>


<!-- FOOTER -->

<div
    class="text-center
           text-muted
           mt-4">

    <small>

        University Academic Management System
        &copy; <?php echo date("Y"); ?>

    </small>

</div>

</div>

<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>
