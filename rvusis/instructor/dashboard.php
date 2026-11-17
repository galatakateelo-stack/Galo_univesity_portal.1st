
<?php
session_start();
include("../includes/db.php");

/* =========================================================
   INSTRUCTOR AUTHENTICATION
========================================================= */

if (!isset($_SESSION['instructor_id'])) {
    header("Location: login.php");
    exit();
}

$instructor_id = (int) $_SESSION['instructor_id'];


/* =========================================================
   HELPER FUNCTION
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   GET INSTRUCTOR INFORMATION
========================================================= */

$instructor_query = mysqli_query($conn, "
    SELECT
        instructor_id,
        full_name,
        email,
        phone,
        profile_photo
    FROM instructors
    WHERE instructor_id = $instructor_id
    LIMIT 1
");

if (!$instructor_query) {
    die("Database Error: " . mysqli_error($conn));
}

$instructor = mysqli_fetch_assoc($instructor_query);

if (!$instructor) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$instructor_name  = $instructor['full_name'] ?? 'Instructor';
$instructor_email = $instructor['email'] ?? '';
$instructor_phone = $instructor['phone'] ?? '';
$profile_photo    = $instructor['profile_photo'] ?? '';


/* =========================================================
   PROFILE PHOTO
========================================================= */

if (!empty($profile_photo)) {

    if (
        strpos($profile_photo, 'http://') === 0 ||
        strpos($profile_photo, 'https://') === 0
    ) {

        $profile_image = $profile_photo;

    } else {

        $profile_photo = ltrim($profile_photo, '/');

        /*
         * If database accidentally contains rvusis/
         * remove it because dashboard is already inside rvusis.
         */
        if (strpos($profile_photo, 'rvusis/') === 0) {
            $profile_photo = substr($profile_photo, 7);
        }

        $profile_image = "../" . $profile_photo;
    }

} else {

    $profile_image = "../assets/images/default-profile.png";
}


/* =========================================================
   ASSIGNED COURSES
========================================================= */

$assigned_courses_query = mysqli_query($conn, "

    SELECT
        ic.id,
        c.course_id,
        c.course_code,
        c.course_name,
        c.credit_hours,
        s.semester_name,
        ic.academic_year

    FROM instructor_courses ic

    INNER JOIN courses c
        ON ic.course_id = c.course_id

    INNER JOIN semesters s
        ON ic.semester_id = s.semester_id

    WHERE ic.instructor_id = $instructor_id

    ORDER BY ic.id DESC

");

if (!$assigned_courses_query) {
    die("Database Error: " . mysqli_error($conn));
}

$course_count = mysqli_num_rows($assigned_courses_query);


/* =========================================================
   STUDENTS GRADED
========================================================= */

$student_count = 0;

$student_query = mysqli_query($conn, "

    SELECT DISTINCT
        g.student_id

    FROM grades g

    INNER JOIN instructor_courses ic
        ON g.course_id = ic.course_id
        AND g.semester_id = ic.semester_id

    WHERE ic.instructor_id = $instructor_id

");

if ($student_query) {
    $student_count = mysqli_num_rows($student_query);
}


/* =========================================================
   GRADES UPLOADED
========================================================= */

$grade_count = 0;

$grade_query = mysqli_query($conn, "

    SELECT COUNT(*) AS total

    FROM grades g

    INNER JOIN instructor_courses ic
        ON g.course_id = ic.course_id
        AND g.semester_id = ic.semester_id

    WHERE ic.instructor_id = $instructor_id

");

if ($grade_query) {

    $grade_row = mysqli_fetch_assoc($grade_query);

    $grade_count = (int)($grade_row['total'] ?? 0);
}


/* =========================================================
   RECENT GRADE ACTIVITY
========================================================= */

$recent_grades = mysqli_query($conn, "

    SELECT
        CONCAT(
            students.first_name,
            ' ',
            students.last_name
        ) AS student_name,

        courses.course_code,
        courses.course_name,
        grades.grade,
        grades.grade_point

    FROM grades

    INNER JOIN students
        ON grades.student_id = students.student_id

    INNER JOIN courses
        ON grades.course_id = courses.course_id

    INNER JOIN instructor_courses ic
        ON grades.course_id = ic.course_id
        AND grades.semester_id = ic.semester_id

    WHERE ic.instructor_id = $instructor_id

    ORDER BY grades.grade_id DESC

    LIMIT 5

");

if (!$recent_grades) {
    die("Database Error: " . mysqli_error($conn));
}


/* =========================================================
   GRADE BADGE COLOR
========================================================= */

function gradeClass($grade)
{
    $grade = strtoupper(trim($grade));

    if (in_array($grade, ['A+', 'A'])) {
        return 'success';
    }

    if (in_array($grade, ['A-', 'B+'])) {
        return 'primary';
    }

    if (in_array($grade, ['B', 'B-', 'C+'])) {
        return 'warning';
    }

    if (in_array($grade, ['C', 'C-', 'D+', 'D'])) {
        return 'danger';
    }

    return 'secondary';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Instructor Dashboard | RVU Academic Portal</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        body {
            background: #f4f7fb;
            font-family: Arial, Helvetica, sans-serif;
            color: #172033;
        }

        /* =========================
           TOP NAVIGATION
        ========================= */

        .topbar {
            background: linear-gradient(
                135deg,
                #0d6efd,
                #084298
            );

            color: white;
            padding: 18px 0;

            box-shadow:
                0 4px 15px rgba(0,0,0,.12);
        }

        .brand {
            font-size: 22px;
            font-weight: 700;
        }

        .profile-link {
            color: white;
            text-decoration: none;
        }

        .profile-link:hover {
            color: white;
        }

        .profile-photo {
            width: 52px;
            height: 52px;

            object-fit: cover;

            border-radius: 50%;

            border: 3px solid white;

            box-shadow:
                0 3px 10px rgba(0,0,0,.2);
        }


        /* =========================
           WELCOME CARD
        ========================= */

        .welcome-card {
            border: none;
            border-radius: 18px;
            overflow: hidden;

            box-shadow:
                0 8px 25px rgba(0,0,0,.08);
        }

        .welcome-header {

            background: linear-gradient(
                135deg,
                #6610f2,
                #0d6efd
            );

            color: white;
            padding: 30px;
        }


        /* =========================
           STATISTICS
        ========================= */

        .stat-card {

            border: none;

            border-radius: 18px;

            color: white;

            min-height: 145px;

            box-shadow:
                0 8px 22px rgba(0,0,0,.08);

            transition: .2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            font-size: 35px;
            opacity: .9;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
        }


        /* =========================
           CONTENT CARDS
        ========================= */

        .course-card,
        .activity-card,
        .action-card {

            border: none;

            border-radius: 18px;

            box-shadow:
                0 8px 25px rgba(0,0,0,.07);
        }

        .section-title {
            font-weight: 700;
            color: #172033;
        }


        /* =========================
           TABLE
        ========================= */

        .table th {

            background: #212529;

            color: white;

            padding: 15px;

            white-space: nowrap;
        }

        .table td {

            padding: 15px;

            vertical-align: middle;
        }


        /* =========================
           GRADE BADGE
        ========================= */

        .grade-badge {

            padding: 8px 13px;

            border-radius: 20px;

            font-weight: 700;
        }


        /* =========================
           QUICK ACTION BUTTONS
        ========================= */

        .quick-btn {

            border-radius: 12px;

            padding: 14px;

            font-weight: 600;
        }


        /* =========================
           FOOTER
        ========================= */

        footer {

            color: #6c757d;

            padding: 30px 0;
        }


        /* =========================
           MOBILE
        ========================= */

        @media(max-width:768px) {

            .brand {
                font-size: 18px;
            }

            .welcome-header {
                padding: 22px;
            }

            .stat-number {
                font-size: 26px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     TOP NAVIGATION
========================================================= -->

<nav class="topbar">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center">

            <!-- BRAND -->

            <div class="brand">

                <i class="bi bi-mortarboard-fill me-2"></i>

                RVU Academic Portal

            </div>


            <!-- PROFILE -->

            <a
                href="profile.php"
                class="profile-link"
            >

                <div class="d-flex align-items-center gap-3">

                    <img
                        src="<?php echo e($profile_image); ?>"
                        alt="Instructor Profile"
                        class="profile-photo"
                        onerror="this.onerror=null;this.src='../assets/images/default-profile.png';"
                    >

                    <div class="d-none d-md-block">

                        <strong>
                            <?php echo e($instructor_name); ?>
                        </strong>

                        <small class="d-block opacity-75">
                            Instructor
                        </small>

                    </div>

                </div>

            </a>

        </div>

    </div>

</nav>



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="container py-5">


    <!-- =====================================================
         WELCOME
    ====================================================== -->

    <div class="welcome-card mb-4">

        <div class="welcome-header">

            <div class="row align-items-center">

                <div class="col-md-8">

                    <h1 class="fw-bold mb-2">

                        Instructor Dashboard

                    </h1>

                    <p class="mb-0 opacity-75">

                        Welcome back,
                        <?php echo e($instructor_name); ?>.

                        Manage your courses,
                        students and grades
                        from one place.

                    </p>

                </div>


                <div class="col-md-4 text-md-end mt-3 mt-md-0">

                    <a
                        href="profile.php"
                        class="btn btn-light"
                    >

                        <i class="bi bi-person-circle me-1"></i>

                        My Profile

                    </a>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <div class="row g-4 mb-4">


        <!-- ASSIGNED COURSES -->

        <div class="col-md-4">

            <div class="stat-card bg-primary p-4">

                <div class="d-flex justify-content-between">

                    <div>

                        <div class="stat-number">
                            <?php echo $course_count; ?>
                        </div>

                        <div>
                            Assigned Courses
                        </div>

                    </div>

                    <i class="bi bi-book stat-icon"></i>

                </div>

            </div>

        </div>


        <!-- STUDENTS -->

        <div class="col-md-4">

            <div class="stat-card bg-success p-4">

                <div class="d-flex justify-content-between">

                    <div>

                        <div class="stat-number">
                            <?php echo $student_count; ?>
                        </div>

                        <div>
                            Students Graded
                        </div>

                    </div>

                    <i class="bi bi-people stat-icon"></i>

                </div>

            </div>

        </div>


        <!-- GRADES -->

        <div class="col-md-4">

            <div class="stat-card bg-danger p-4">

                <div class="d-flex justify-content-between">

                    <div>

                        <div class="stat-number">
                            <?php echo $grade_count; ?>
                        </div>

                        <div>
                            Grades Uploaded
                        </div>

                    </div>

                    <i class="bi bi-file-earmark-check stat-icon"></i>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         MAIN ROW
    ====================================================== -->

    <div class="row g-4">


        <!-- =================================================
             ASSIGNED COURSES
        ================================================== -->

        <div class="col-lg-8">

            <div class="card course-card">

                <div class="card-header bg-white border-0 p-4">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h4 class="section-title mb-1">

                                <i class="bi bi-book me-2 text-primary"></i>

                                My Assigned Courses

                            </h4>

                            <small class="text-muted">

                                Courses assigned to you by the administrator

                            </small>

                        </div>

                        <span class="badge bg-primary">

                            <?php echo $course_count; ?>

                            Courses

                        </span>

                    </div>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover mb-0">

                            <thead>

                                <tr>

                                    <th>#</th>

                                    <th>Course</th>

                                    <th>Credit</th>

                                    <th>Semester</th>

                                    <th>Academic Year</th>

                                    <th>Action</th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php

                            if ($course_count > 0) {

                                $no = 1;

                                while (
                                    $course =
                                    mysqli_fetch_assoc(
                                        $assigned_courses_query
                                    )
                                ) {

                            ?>

                                <tr>

                                    <td>
                                        <?php echo $no++; ?>
                                    </td>


                                    <td>

                                        <strong>

                                            <?php
                                            echo e(
                                                $course['course_code']
                                            );
                                            ?>

                                        </strong>

                                        <small class="d-block text-muted">

                                            <?php
                                            echo e(
                                                $course['course_name']
                                            );
                                            ?>

                                        </small>

                                    </td>


                                    <td>

                                        <?php
                                        echo e(
                                            $course['credit_hours']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo e(
                                            $course['semester_name']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo e(
                                            $course['academic_year']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <a
                                            href="my_courses/index.php"
                                            class="btn btn-success btn-sm"
                                        >

                                            <i class="bi bi-eye me-1"></i>

                                            View Students

                                        </a>

                                    </td>

                                </tr>

                            <?php

                                }

                            } else {

                            ?>

                                <tr>

                                    <td
                                        colspan="6"
                                        class="text-center py-5 text-muted"
                                    >

                                        <i
                                            class="bi bi-book"
                                            style="font-size:40px;"
                                        ></i>

                                        <p class="mt-2 mb-0">

                                            No courses assigned yet.

                                        </p>

                                    </td>

                                </tr>

                            <?php

                            }

                            ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <div class="col-lg-4">

            <div class="card action-card">

                <div class="card-header bg-white border-0 p-4">

                    <h4 class="section-title mb-0">

                        <i class="bi bi-grid me-2 text-primary"></i>

                        Quick Actions

                    </h4>

                </div>


                <div class="card-body p-4">


                    <a
                        href="my_courses/index.php"
                        class="btn btn-success w-100 quick-btn mb-3"
                    >

                        <i class="bi bi-book me-2"></i>

                        My Assigned Courses

                    </a>


                    <a
                        href="upload_grade.php"
                        class="btn btn-primary w-100 quick-btn mb-3"
                    >

                        <i class="bi bi-cloud-upload me-2"></i>

                        Upload Grade

                    </a>


                    <a
                        href="grades.php"
                        class="btn btn-info text-white w-100 quick-btn mb-3"
                    >

                        <i class="bi bi-bar-chart me-2"></i>

                        View Grades

                    </a>


                    <a
                        href="profile.php"
                        class="btn btn-outline-primary w-100 quick-btn mb-3"
                    >

                        <i class="bi bi-person-circle me-2"></i>

                        My Profile

                    </a>


                    <!-- IMPORTANT:
                         logout.php is inside instructor folder
                         therefore DO NOT use ../logout.php
                    -->
<a href="change_password.php"
   class="btn btn-warning w-100 quick-btn mb-3">
    <i class="bi bi-key-fill me-2"></i>
    Change Password
</a>
                    <a
                        href="logout.php"
                        class="btn btn-danger w-100 quick-btn"
                        onclick="return confirm('Are you sure you want to logout?');"
                    >

                        <i class="bi bi-box-arrow-right me-2"></i>

                        Logout

                    </a>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         RECENT GRADE ACTIVITY
    ====================================================== -->

    <div class="card activity-card mt-4">

        <div class="card-header bg-white border-0 p-4">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h4 class="section-title mb-1">

                        <i class="bi bi-clock-history me-2 text-primary"></i>

                        Recent Grade Activity

                    </h4>

                    <small class="text-muted">

                        Your recently uploaded student grades

                    </small>

                </div>


                <a
                    href="grades.php"
                    class="btn btn-outline-primary btn-sm"
                >

                    View All

                </a>

            </div>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead>

                        <tr>

                            <th>Student</th>

                            <th>Course</th>

                            <th>Grade</th>

                            <th>Grade Point</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    $activity_count = 0;

                    while (
                        $grade =
                        mysqli_fetch_assoc($recent_grades)
                    ) {

                        $activity_count++;

                        $badge =
                            gradeClass($grade['grade']);

                    ?>

                        <tr>

                            <td>

                                <strong>

                                    <?php
                                    echo e(
                                        $grade['student_name']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo e(
                                        $grade['course_code']
                                    );
                                    ?>

                                </strong>

                                <small class="d-block text-muted">

                                    <?php
                                    echo e(
                                        $grade['course_name']
                                    );
                                    ?>

                                </small>

                            </td>


                            <td>

                                <span
                                    class="badge bg-<?php echo $badge; ?> grade-badge"
                                >

                                    <?php
                                    echo e(
                                        $grade['grade']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo e(
                                        $grade['grade_point']
                                    );
                                    ?>

                                </strong>

                            </td>

                        </tr>

                    <?php
                    }


                    if ($activity_count === 0) {

                    ?>

                        <tr>

                            <td
                                colspan="4"
                                class="text-center py-5 text-muted"
                            >

                                <i
                                    class="bi bi-clipboard-x"
                                    style="font-size:40px;"
                                ></i>

                                <p class="mt-2 mb-0">

                                    No grade activity found.

                                </p>

                            </td>

                        </tr>

                    <?php
                    }

                    ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


</div>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="text-center">

    <div class="container">

        <i class="bi bi-mortarboard-fill me-1"></i>

        RVU Academic Portal

        <span class="mx-2">•</span>

        Instructor Portal

        <div class="small mt-1">

            © <?php echo date('Y'); ?>

            All rights reserved.

        </div>

    </div>

</footer>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>
