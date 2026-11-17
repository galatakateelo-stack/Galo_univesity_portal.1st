<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

include("../includes/db.php");

$student_id = $_SESSION['student_id'];

/* =========================================================
   STUDENT INFORMATION
========================================================= */

$sql = "
SELECT
    students.*,
    departments.department_name
FROM students
INNER JOIN departments
    ON students.department_id = departments.department_id
WHERE students.student_id = '$student_id'
";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Student information could not be found.");
}

$student = mysqli_fetch_assoc($result);


/* =========================================================
   GPA CALCULATION
========================================================= */

$sql = "
SELECT
    grades.grade,
    grades.grade_point,
    courses.credit_hours
FROM grades
INNER JOIN courses
    ON grades.course_id = courses.course_id
WHERE grades.student_id = '$student_id'
";

$result = mysqli_query($conn, $sql);

$totalCredits = 0;
$totalPoints = 0;
$totalCourses = 0;

while ($row = mysqli_fetch_assoc($result)) {

    $credit = (float)$row['credit_hours'];

    /*
       Use grade_point from database when available.
       This is better than calculating it again.
    */

    if (
        isset($row['grade_point']) &&
        $row['grade_point'] !== null &&
        $row['grade_point'] !== ''
    ) {
        $point = (float)$row['grade_point'];
    } else {

        switch ($row['grade']) {

            case "A+":
            case "A":
                $point = 4.00;
                break;

            case "A-":
                $point = 3.75;
                break;

            case "B+":
                $point = 3.50;
                break;

            case "B":
                $point = 3.00;
                break;

            case "B-":
                $point = 2.75;
                break;

            case "C+":
                $point = 2.50;
                break;

            case "C":
                $point = 2.00;
                break;

            case "C-":
                $point = 1.75;
                break;

            case "D":
                $point = 1.00;
                break;

            default:
                $point = 0.00;
        }
    }

    $totalCredits += $credit;
    $totalPoints += ($credit * $point);

    $totalCourses++;
}

$gpa = ($totalCredits > 0)
    ? $totalPoints / $totalCredits
    : 0;


/* =========================================================
   ACADEMIC STATUS
========================================================= */

if ($gpa >= 3.50) {
    $academicStatus = "Excellent";
    $statusColor = "success";
} elseif ($gpa >= 3.00) {
    $academicStatus = "Very Good";
    $statusColor = "primary";
} elseif ($gpa >= 2.00) {
    $academicStatus = "Good Standing";
    $statusColor = "warning";
} elseif ($gpa > 0) {
    $academicStatus = "Needs Improvement";
    $statusColor = "danger";
} else {
    $academicStatus = "No Grades Yet";
    $statusColor = "secondary";
}


/* =========================================================
   RECENT GRADES
========================================================= */

$recentGrades = mysqli_query($conn, "
SELECT
    courses.course_code,
    courses.course_name,
    courses.credit_hours,
    grades.grade,
    grades.grade_point,
    semesters.semester_name,
    semesters.academic_year
FROM grades

INNER JOIN courses
    ON grades.course_id = courses.course_id

INNER JOIN semesters
    ON grades.semester_id = semesters.semester_id

WHERE grades.student_id = '$student_id'

ORDER BY grades.grade_id DESC

LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Student Dashboard | Campus Grades</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">

<style>

/* =========================================================
   GENERAL
========================================================= */

body {
    margin: 0;
    background: #f5f7fb;
    font-family: Arial, Helvetica, sans-serif;
    color: #212529;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 230px;
    height: 100vh;
    height: 100dvh;
    box-sizing: border-box;

    background: #111827;
    color: white;

    padding: 20px 14px;

    overflow-y: auto;
    overflow-x: hidden;
    overscroll-behavior-y: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: #4b5563 #111827;

    z-index: 1000;
}

.sidebar::-webkit-scrollbar {
    width: 7px;
}

.sidebar::-webkit-scrollbar-track {
    background: #111827;
}

.sidebar::-webkit-scrollbar-thumb {
    background: #4b5563;
    border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

.logo {
    text-align: center;
    font-size: 21px;
    font-weight: bold;
    margin-bottom: 30px;
}

.logo i {
    color: #38bdf8;
    margin-right: 7px;
}

.sidebar-title {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 20px 10px 8px;
}

.sidebar a {
    display: flex;
    align-items: center;

    text-decoration: none;
    color: #d1d5db;

    padding: 11px 12px;
    margin-bottom: 5px;

    border-radius: 8px;

    transition: 0.2s;
}

.sidebar a i {
    font-size: 17px;
    width: 28px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #1d4ed8;
    color: white;
}

.logout {
    margin-top: 30px;
}


/* =========================================================
   MAIN CONTENT
========================================================= */

.main-content {
    margin-left: 230px;
    min-height: 100vh;
}


/* =========================================================
   TOP BAR
========================================================= */

.topbar {
    height: 70px;

    background: white;

    border-bottom: 1px solid #e5e7eb;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 30px;
}

.topbar-title {
    font-size: 20px;
    font-weight: 600;
}

.student-mini {
    display: flex;
    align-items: center;
    gap: 10px;
}

.student-avatar {
    width: 40px;
    height: 40px;

    border-radius: 50%;

    background: #2563eb;
    color: white;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: bold;
}


/* =========================================================
   CONTENT
========================================================= */

.content {
    padding: 30px;
}

.welcome-card {
    border: none;
    border-radius: 16px;

    background: linear-gradient(
        135deg,
        #2563eb,
        #1e40af
    );

    color: white;

    padding: 28px;

    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

.welcome-card h2 {
    font-weight: 700;
}

.welcome-card p {
    margin-bottom: 0;
    opacity: 0.9;
}


/* =========================================================
   STAT CARDS
========================================================= */

.stat-card {
    background: white;

    border: none;

    border-radius: 14px;

    padding: 20px;

    height: 100%;

    box-shadow: 0 4px 15px rgba(0,0,0,0.05);

    transition: 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 48px;
    height: 48px;

    border-radius: 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 22px;

    margin-bottom: 12px;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
}

.stat-label {
    color: #6b7280;
    font-size: 14px;
}


/* =========================================================
   PANELS
========================================================= */

.panel {
    background: white;

    border: none;

    border-radius: 14px;

    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.panel-header {
    padding: 18px 20px;

    border-bottom: 1px solid #e5e7eb;

    font-weight: 600;
}


/* =========================================================
   TABLE
========================================================= */

.table {
    margin-bottom: 0;
}

.table thead th {
    font-size: 13px;
    color: #6b7280;
    background: #f9fafb;

    border-bottom: 1px solid #e5e7eb;
}

.table tbody td {
    vertical-align: middle;
    padding: 14px;
}


/* =========================================================
   QUICK ACTIONS
========================================================= */

.quick-btn {
    display: flex;
    align-items: center;

    gap: 10px;

    text-decoration: none;

    padding: 14px;

    border-radius: 10px;

    margin-bottom: 10px;

    font-weight: 500;

    transition: 0.2s;
}

.quick-btn:hover {
    transform: translateX(3px);
}

.btn-transcript {
    background: #eff6ff;
    color: #1d4ed8;
}

.btn-registration {
    background: #ecfdf5;
    color: #047857;
}

.btn-courses {
    background: #fff7ed;
    color: #c2410c;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 768px) {

    .sidebar {
        width: 70px;
        padding: 15px 8px;
    }

    .logo span,
    .sidebar-title,
    .sidebar a span {
        display: none;
    }

    .sidebar a {
        justify-content: center;
    }

    .sidebar a i {
        width: auto;
        font-size: 20px;
    }

    .main-content {
        margin-left: 70px;
    }

    .content {
        padding: 18px;
    }

    .topbar {
        padding: 0 18px;
    }
}

</style>

</head>


<body>


<!-- ======================================================
     SIDEBAR
====================================================== -->

<div class="sidebar">

    <div class="logo">

        <i class="bi bi-mortarboard-fill"></i>

        <span>Campus Grades</span>

    </div>


    <div class="sidebar-title">
        Student
    </div>


    <a href="dashboard.php" class="active">

        <i class="bi bi-speedometer2"></i>

        <span>Dashboard</span>

    </a>


    <a href="transcript.php">

        <i class="bi bi-file-earmark-text"></i>

        <span>My Transcript</span>

    </a>
<a href="academic_performance.php">
    <i class="bi bi-graph-up-arrow"></i>
    Academic Performance
</a>

    <a href="registration/index.php">

        <i class="bi bi-journal-plus"></i>

        <span>Course Registration</span>

    </a>


    <a href="registration/my_courses.php">

        <i class="bi bi-book"></i>

        <span>My Courses</span>

    </a>


    <div class="sidebar-title">
        Account
    </div>
<a href="change_password.php">
    <i class="bi bi-key-fill me-2"></i>
    Change Password
</a>

    <a href="logout.php" class="logout">

        <i class="bi bi-box-arrow-right"></i>

        <span>Logout</span>

    </a>

</div>


<!-- ======================================================
     MAIN CONTENT
====================================================== -->

<div class="main-content">


    <!-- TOP BAR -->

    <div class="topbar">

        <div class="topbar-title">

            Student Portal

        </div>


        <div class="student-mini">

            <div class="student-avatar">

                <?php
                echo strtoupper(
                    substr($student['first_name'], 0, 1)
                );
                ?>

            </div>

            <div>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $student['first_name']
                        . " "
                        . $student['last_name']
                    );
                    ?>
                </strong>

                <small class="text-muted d-block">
                    Student
                </small>

            </div>

        </div>

    </div>


    <!-- PAGE CONTENT -->

    <div class="content">


        <!-- WELCOME -->

        <div class="welcome-card mb-4">

            <h2>

                Welcome,
                <?php
                echo htmlspecialchars(
                    $student['first_name']
                );
                ?>! 👋

            </h2>

            <p>

                Here is your academic overview.

                Today is
                <?php
                echo date("l, d F Y");
                ?>.

            </p>

        </div>


        <!-- STUDENT INFORMATION -->

        <div class="row g-3 mb-4">


            <div class="col-md-4">

                <div class="stat-card">

                    <div
                    class="stat-icon bg-primary-subtle text-primary">

                        <i class="bi bi-person-badge"></i>

                    </div>

                    <div class="stat-label">
                        Registration Number
                    </div>

                    <div class="stat-number"
                         style="font-size:20px;">

                        <?php
                        echo htmlspecialchars(
                            $student['registration_no']
                        );
                        ?>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="stat-card">

                    <div
                    class="stat-icon bg-success-subtle text-success">

                        <i class="bi bi-building"></i>

                    </div>

                    <div class="stat-label">
                        Department
                    </div>

                    <div class="fw-bold mt-2">

                        <?php
                        echo htmlspecialchars(
                            $student['department_name']
                        );
                        ?>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="stat-card">

                    <div
                    class="stat-icon bg-info-subtle text-info">

                        <i class="bi bi-calendar3"></i>

                    </div>

                    <div class="stat-label">
                        Academic Year
                    </div>

                    <div class="fw-bold mt-2">

                        <?php
                        echo isset($student['academic_year'])
                            ? htmlspecialchars($student['academic_year'])
                            : "Current";
                        ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- ACADEMIC STATISTICS -->

        <div class="row g-3 mb-4">


            <!-- GPA -->

            <div class="col-md-3">

                <div class="stat-card">

                    <div
                    class="stat-icon bg-success-subtle text-success">

                        <i class="bi bi-graph-up"></i>

                    </div>

                    <div class="stat-label">
                        Current GPA
                    </div>

                    <div class="stat-number">

                        <?php
                        echo number_format($gpa, 2);
                        ?>

                    </div>

                </div>

            </div>


            <!-- CREDITS -->

            <div class="col-md-3">

                <div class="stat-card">

                    <div
                    class="stat-icon bg-warning-subtle text-warning">

                        <i class="bi bi-award"></i>

                    </div>

                    <div class="stat-label">
                        Completed Credits
                    </div>

                    <div class="stat-number">

                        <?php
                        echo number_format(
                            $totalCredits,
                            0
                        );
                        ?>

                    </div>

                </div>

            </div>


            <!-- COURSES -->

            <div class="col-md-3">

                <div class="stat-card">

                    <div
                    class="stat-icon bg-primary-subtle text-primary">

                        <i class="bi bi-book"></i>

                    </div>

                    <div class="stat-label">
                        Graded Courses
                    </div>

                    <div class="stat-number">

                        <?php
                        echo $totalCourses;
                        ?>

                    </div>

                </div>

            </div>


            <!-- STATUS -->

            <div class="col-md-3">

                <div class="stat-card">

                    <div
                    class="stat-icon bg-<?php echo $statusColor; ?>-subtle text-<?php echo $statusColor; ?>">

                        <i class="bi bi-check-circle"></i>

                    </div>

                    <div class="stat-label">
                        Academic Status
                    </div>

                    <div class="mt-2">

                        <span class="badge bg-<?php echo $statusColor; ?>">

                            <?php
                            echo $academicStatus;
                            ?>

                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- LOWER SECTION -->

        <div class="row g-4">


            <!-- RECENT GRADES -->

            <div class="col-lg-8">

                <div class="panel">

                    <div class="panel-header">

                        <i class="bi bi-clock-history me-2"></i>

                        Recent Grade Activity

                    </div>


                    <div class="table-responsive">

                        <table class="table">

                            <thead>

                                <tr>

                                    <th>Course</th>

                                    <th>Course Name</th>

                                    <th>Credit</th>

                                    <th>Grade</th>

                                    <th>Point</th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php

                            if (
                                $recentGrades &&
                                mysqli_num_rows($recentGrades) > 0
                            ) {

                                while (
                                    $row =
                                    mysqli_fetch_assoc(
                                        $recentGrades
                                    )
                                ) {

                                    $grade = $row['grade'];

                                    $badge = "secondary";

                                    if (
                                        $grade == "A+" ||
                                        $grade == "A"
                                    ) {
                                        $badge = "success";

                                    } elseif (
                                        $grade == "A-" ||
                                        $grade == "B+" ||
                                        $grade == "B"
                                    ) {
                                        $badge = "primary";

                                    } elseif (
                                        $grade == "B-" ||
                                        $grade == "C+" ||
                                        $grade == "C"
                                    ) {
                                        $badge = "warning";

                                    } else {
                                        $badge = "danger";
                                    }

                            ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['course_code']
                                            );
                                            ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['course_name']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $row['credit_hours'];
                                        ?>
                                    </td>

                                    <td>

                                        <span
                                        class="badge bg-<?php echo $badge; ?>">

                                            <?php
                                            echo htmlspecialchars(
                                                $grade
                                            );
                                            ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?php
                                        echo number_format(
                                            (float)$row['grade_point'],
                                            2
                                        );
                                        ?>

                                    </td>

                                </tr>

                            <?php

                                }

                            } else {

                            ?>

                                <tr>

                                    <td
                                    colspan="5"
                                    class="text-center text-muted py-4">

                                        No grades available yet.

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


            <!-- QUICK ACTIONS -->

            <div class="col-lg-4">

                <div class="panel">

                    <div class="panel-header">

                        <i class="bi bi-lightning-charge me-2"></i>

                        Quick Actions

                    </div>


                    <div class="p-3">


                        <a
                        href="transcript.php"
                        class="quick-btn btn-transcript">

                            <i class="bi bi-file-earmark-text fs-5"></i>

                            <span>
                                View Academic Transcript
                            </span>

                        </a>


                        <a
                        href="registration/index.php"
                        class="quick-btn btn-registration">

                            <i class="bi bi-journal-plus fs-5"></i>

                            <span>
                                Course Registration
                            </span>

                        </a>


                        <a
                        href="registration/my_courses.php"
                        class="quick-btn btn-courses">

                            <i class="bi bi-book fs-5"></i>

                            <span>
                                My Registered Courses
                            </span>

                        </a>


                    </div>

                </div>


                <!-- GPA INFORMATION -->

                <div class="panel mt-4">

                    <div class="panel-header">

                        <i class="bi bi-info-circle me-2"></i>

                        Academic Summary

                    </div>

                    <div class="p-3">

                        <div
                        class="d-flex justify-content-between mb-2">

                            <span>
                                GPA
                            </span>

                            <strong>
                                <?php
                                echo number_format($gpa, 2);
                                ?>
                            </strong>

                        </div>


                        <div
                        class="d-flex justify-content-between mb-2">

                            <span>
                                Credits
                            </span>

                            <strong>
                                <?php
                                echo $totalCredits;
                                ?>
                            </strong>

                        </div>


                        <div
                        class="d-flex justify-content-between">

                            <span>
                                Status
                            </span>

                            <span
                            class="badge bg-<?php echo $statusColor; ?>">

                                <?php
                                echo $academicStatus;
                                ?>

                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


    </div>

</div>


</body>

</html>