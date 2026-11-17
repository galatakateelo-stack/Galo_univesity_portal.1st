<?php
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location: login.php");
    exit();
}

include("../includes/db.php");

$instructor_id = intval($_SESSION['instructor_id']);

$message = "";
$message_type = "";


/* =========================================================
   GET INSTRUCTOR INFORMATION
========================================================= */

$instructor_sql = "
    SELECT instructor_id, first_name, last_name
    FROM instructors
    WHERE instructor_id = ?
    LIMIT 1
";

$instructor_stmt = mysqli_prepare($conn, $instructor_sql);

if (!$instructor_stmt) {
    die("Instructor query error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $instructor_stmt,
    "i",
    $instructor_id
);

mysqli_stmt_execute($instructor_stmt);

$instructor_result =
    mysqli_stmt_get_result($instructor_stmt);

$instructor = mysqli_fetch_assoc($instructor_result);

mysqli_stmt_close($instructor_stmt);


$instructor_name = "Instructor";

if ($instructor) {
    $instructor_name =
        $instructor['first_name'] . " " .
        $instructor['last_name'];
}


/* =========================================================
   GET ASSIGNED COURSES
========================================================= */

$course_sql = "
    SELECT
        c.course_id,
        c.course_code,
        c.course_name,
        c.credit_hours,
        d.department_name,
        s.semester_name,
        s.academic_year

    FROM instructor_courses ic

    INNER JOIN courses c
        ON ic.course_id = c.course_id

    LEFT JOIN departments d
        ON c.department_id = d.department_id

    LEFT JOIN semesters s
        ON c.semester_id = s.semester_id

    WHERE ic.instructor_id = ?

    ORDER BY c.course_code ASC
";


$course_stmt = mysqli_prepare(
    $conn,
    $course_sql
);


/*
   If the query fails, show the REAL MySQL error
   instead of mysqli_fetch_assoc(false).
*/

if (!$course_stmt) {

    die(
        "<div style='font-family:Arial;padding:30px;'>
            <h2>Database Query Error</h2>
            <p>" .
            htmlspecialchars(mysqli_error($conn)) .
            "</p>
            <a href='dashboard.php'>
                Back to Dashboard
            </a>
        </div>"
    );
}


mysqli_stmt_bind_param(
    $course_stmt,
    "i",
    $instructor_id
);


if (!mysqli_stmt_execute($course_stmt)) {

    die(
        "<div style='font-family:Arial;padding:30px;'>
            <h2>Database Execution Error</h2>
            <p>" .
            htmlspecialchars(mysqli_stmt_error($course_stmt)) .
            "</p>
            <a href='dashboard.php'>
                Back to Dashboard
            </a>
        </div>"
    );
}


$courses_query =
    mysqli_stmt_get_result($course_stmt);


if (!$courses_query) {

    die(
        "<div style='font-family:Arial;padding:30px;'>
            <h2>Unable to Load Courses</h2>
            <p>" .
            htmlspecialchars(mysqli_error($conn)) .
            "</p>
            <a href='dashboard.php'>
                Back to Dashboard
            </a>
        </div>"
    );
}


$total_courses =
    mysqli_num_rows($courses_query);


/* =========================================================
   PAGE
========================================================= */
?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"

>

<title>
My Assigned Courses
</title>

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

    margin:0;

    background:
    linear-gradient(
        135deg,
        #eef2ff,
        #f8fafc
    );

    font-family:
    "Segoe UI",
    Arial,
    sans-serif;

    min-height:100vh;
}


/* =========================================================
   HEADER
========================================================= */

.top-header {

    background:
    linear-gradient(
        135deg,
        #0d6efd,
        #084298
    );

    color:white;

    padding:30px 20px;

    box-shadow:
    0 5px 20px
    rgba(0,0,0,.12);
}


.header-container {

    max-width:1150px;

    margin:auto;
}


.top-header h1 {

    font-size:28px;

    font-weight:700;

    margin:0;
}


.top-header p {

    margin:5px 0 0;

    opacity:.9;
}


/* =========================================================
   MAIN
========================================================= */

.main-container {

    max-width:1150px;

    margin:35px auto;

    padding:
    0 20px;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.back-button {

    margin-bottom:25px;
}


/* =========================================================
   STAT CARDS
========================================================= */

.stat-card {

    border:none;

    border-radius:18px;

    color:white;

    padding:25px;

    box-shadow:
    0 8px 25px
    rgba(0,0,0,.10);

    height:100%;

    transition:.2s;
}


.stat-card:hover {

    transform:
    translateY(-3px);
}


.stat-icon {

    font-size:30px;

    margin-bottom:10px;
}


.stat-number {

    font-size:30px;

    font-weight:700;
}


.stat-title {

    opacity:.9;

    font-weight:500;
}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title {

    margin-top:35px;

    margin-bottom:25px;
}


.page-title h2 {

    font-weight:700;

    margin-bottom:5px;
}


/* =========================================================
   COURSE CARD
========================================================= */

.course-card {

    background:white;

    border:none;

    border-radius:18px;

    overflow:hidden;

    box-shadow:
    0 8px 25px
    rgba(0,0,0,.08);

    height:100%;

    transition:.25s;
}


.course-card:hover {

    transform:
    translateY(-5px);

    box-shadow:
    0 15px 35px
    rgba(0,0,0,.13);
}


/* =========================================================
   COURSE HEADER
========================================================= */

.course-header {

    background:
    linear-gradient(
        135deg,
        #198754,
        #116b43
    );

    color:white;

    padding:22px;
}


.course-code {

    font-size:14px;

    font-weight:700;

    opacity:.9;

    margin-bottom:6px;
}


.course-name {

    font-size:20px;

    font-weight:700;
}


/* =========================================================
   COURSE BODY
========================================================= */

.course-body {

    padding:22px;
}


.course-info {

    display:flex;

    align-items:center;

    gap:8px;

    margin-bottom:13px;

    color:#475467;

    font-size:14px;
}


.course-info i {

    width:20px;

    color:#198754;
}


.course-info strong {

    color:#344054;
}


/* =========================================================
   ACTION BUTTONS
========================================================= */

.course-actions {

    margin-top:20px;
}


.course-actions .btn {

    border-radius:10px;

    font-weight:600;

    padding:10px;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-box {

    background:white;

    border-radius:20px;

    padding:60px 25px;

    text-align:center;

    box-shadow:
    0 8px 25px
    rgba(0,0,0,.08);
}


.empty-box i {

    font-size:60px;

    color:#98a2b3;

    margin-bottom:20px;
}


.empty-box h3 {

    font-weight:700;
}


/* =========================================================
   FOOTER
========================================================= */

.page-footer {

    text-align:center;

    color:#667085;

    margin:
    40px 0 25px;
}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:768px) {

    .top-header h1 {

        font-size:23px;
    }

    .main-container {

        margin-top:20px;
    }

}

</style>

</head>

<body>

<!-- =====================================================
     HEADER
===================================================== -->

<header class="top-header">

<div class="header-container">

<h1>

<i class="bi bi-book-half"></i>

My Assigned Courses

</h1>

<p>

Instructor: <strong>

<?php
echo htmlspecialchars(
    $instructor_name
);
?>

</strong>

</p>

</div>

</header>

<!-- =====================================================
     MAIN
===================================================== -->

<div class="main-container">

<!-- BACK -->

<div class="back-button">

<a
href="dashboard.php"
class="btn btn-outline-primary"

>

<i class="bi bi-arrow-left"></i>

Back to Dashboard

</a>

</div>

<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="row g-4">

<div class="col-md-4">

<div
class="stat-card"
style="background:#198754;"
>

<div class="stat-icon">

<i class="bi bi-book"></i>

</div>

<div class="stat-number">

<?php
echo $total_courses;
?>

</div>

<div class="stat-title">

Assigned Courses

</div>

</div>

</div>

<div class="col-md-4">

<div
class="stat-card"
style="background:#0d6efd;"
>

<div class="stat-icon">

<i class="bi bi-calendar3"></i>

</div>

<div class="stat-number">

<?php
echo date("Y");
?>

</div>

<div class="stat-title">

Academic Year

</div>

</div>

</div>

<div class="col-md-4">

<div
class="stat-card"
style="background:#212529;"
>

<div class="stat-icon">

<i class="bi bi-person-badge"></i>

</div>

<div
class="stat-number"
style="font-size:22px;"
>

Instructor

</div>

<div class="stat-title">

<?php

echo htmlspecialchars(
    $instructor_name
);

?>

</div>

</div>

</div>

</div>

<!-- =====================================================
     TITLE
===================================================== -->

<div class="page-title">

<h2>

Assigned Courses

</h2>

<p class="text-muted">

These are the courses currently assigned
to your instructor account.

</p>

</div>

<!-- =====================================================
     COURSE LIST
===================================================== -->

<?php if ($total_courses > 0): ?>

<div class="row g-4">

<?php

while (
    $course =
    mysqli_fetch_assoc(
        $courses_query
    )
):

?>

<div class="col-lg-4 col-md-6">

<div class="course-card">

<!-- COURSE HEADER -->

<div class="course-header">

<div class="course-code">

<?php

echo htmlspecialchars(
    $course['course_code']
);

?>

</div>

<div class="course-name">

<?php

echo htmlspecialchars(
    $course['course_name']
);

?>

</div>

</div>

<!-- COURSE BODY -->

<div class="course-body">

<div class="course-info">

<i class="bi bi-clock"></i>

<strong>
Credit Hours:
</strong>

<?php

echo htmlspecialchars(
    $course['credit_hours']
);

?>

</div>

<div class="course-info">

<i class="bi bi-building"></i>

<strong>
Department:
</strong>

<?php

echo htmlspecialchars(
    $course['department_name']
    ?? 'N/A'
);

?>

</div>

<div class="course-info">

<i class="bi bi-calendar3"></i>

<strong>
Semester:
</strong>

<?php

echo htmlspecialchars(
    $course['semester_name']
    ?? 'N/A'
);

?>

</div>

<div class="course-info">

<i class="bi bi-mortarboard"></i>

<strong>
Academic Year:
</strong>

<?php

echo htmlspecialchars(
    $course['academic_year']
    ?? 'N/A'
);

?>

</div>

<!-- ACTIONS -->

<div class="course-actions d-grid gap-2">

<a
href="grades.php?course_id=<?php
echo $course['course_id'];
?>"
class="btn btn-primary"

>

<i class="bi bi-bar-chart-fill"></i>

View Grades

</a>

<a
href="upload_grade.php?course_id=<?php
echo $course['course_id'];
?>"
class="btn btn-success"

>

<i class="bi bi-cloud-arrow-up-fill"></i>

Upload Grade

</a>

</div>

</div>

</div>

</div>

<?php endwhile; ?>

</div>

<?php else: ?>

<!-- =====================================================
     EMPTY
===================================================== -->

<div class="empty-box">

<i class="bi bi-journal-x"></i>

<h3>

No Assigned Courses

</h3>

<p class="text-muted">

You currently don't have any courses
assigned to your instructor account.

</p>

<a
href="dashboard.php"
class="btn btn-primary"

>

<i class="bi bi-arrow-left"></i>

Back to Dashboard

</a>

</div>

<?php endif; ?>

<!-- FOOTER -->

<div class="page-footer">

University Student Information System

<br>

<small>

Instructor Academic Portal

</small>

</div>

</div>

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>
