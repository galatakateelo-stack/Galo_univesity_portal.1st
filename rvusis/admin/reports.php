<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../includes/db.php");

/* =========================================================
   SAFE OUTPUT
========================================================= */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   COUNT FUNCTION
========================================================= */
function getCount($conn, $table)
{
    $allowed = [
        'students',
        'instructors',
        'courses',
        'departments',
        'semesters',
        'grades',
        'course_registrations'
    ];

    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    $query = "SELECT COUNT(*) AS total FROM `$table`";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    return (int)$row['total'];
}


/* =========================================================
   SYSTEM COUNTS
========================================================= */

$total_students =
    getCount($conn, 'students');

$total_instructors =
    getCount($conn, 'instructors');

$total_courses =
    getCount($conn, 'courses');

$total_departments =
    getCount($conn, 'departments');

$total_semesters =
    getCount($conn, 'semesters');

$total_registrations =
    getCount($conn, 'course_registrations');

$total_grades =
    getCount($conn, 'grades');


/* =========================================================
   STUDENTS BY DEPARTMENT
========================================================= */

$department_data = [];

$sql = "
    SELECT
        departments.department_name,
        COUNT(students.student_id) AS total_students

    FROM departments

    LEFT JOIN students
        ON students.department_id =
           departments.department_id

    GROUP BY
        departments.department_id,
        departments.department_name

    ORDER BY
        total_students DESC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $department_data[] = $row;
    }
}


/* =========================================================
   GRADE DISTRIBUTION
========================================================= */

$grade_distribution = [];

$sql = "
    SELECT
        grade,
        COUNT(*) AS total

    FROM grades

    GROUP BY grade

    ORDER BY
        FIELD(
            grade,
            'A+',
            'A',
            'A-',
            'B+',
            'B',
            'B-',
            'C+',
            'C',
            'D',
            'F'
        )
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $grade_distribution[] = $row;
    }
}


/* =========================================================
   GPA STATISTICS
========================================================= */

$average_gpa = 0;

$sql = "
    SELECT
        AVG(grade_point) AS average_gpa

    FROM grades
";

$result = mysqli_query($conn, $sql);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $average_gpa =
        (float)($row['average_gpa'] ?? 0);
}


/* =========================================================
   HIGHEST GRADE POINT
========================================================= */

$highest_grade_point = 0;

$sql = "
    SELECT
        MAX(grade_point) AS highest

    FROM grades
";

$result = mysqli_query($conn, $sql);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $highest_grade_point =
        (float)($row['highest'] ?? 0);
}


/* =========================================================
   LOWEST GRADE POINT
========================================================= */

$lowest_grade_point = 0;

$sql = "
    SELECT
        MIN(grade_point) AS lowest

    FROM grades
";

$result = mysqli_query($conn, $sql);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $lowest_grade_point =
        (float)($row['lowest'] ?? 0);
}


/* =========================================================
   RECENT GRADES
========================================================= */

$recent_grades = [];

$sql = "
    SELECT

        students.first_name,
        students.last_name,
        students.registration_no,

        courses.course_code,
        courses.course_name,

        grades.grade,
        grades.grade_point

    FROM grades

    INNER JOIN students
        ON grades.student_id =
           students.student_id

    INNER JOIN courses
        ON grades.course_id =
           courses.course_id

    ORDER BY
        grades.grade_id DESC

    LIMIT 10
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $recent_grades[] = $row;
    }
}


/* =========================================================
   CURRENT DATE
========================================================= */

$report_date = date("l, d F Y");

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        System Reports | Campus SIS
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
            background: #f4f7fb;
            font-family: Arial, Helvetica, sans-serif;
            color: #172033;
        }

        .report-container {
            max-width: 1450px;
            margin: auto;
            padding: 30px;
        }

        .header-card {
            background:
                linear-gradient(
                    135deg,
                    #1d4ed8,
                    #2563eb,
                    #4338ca
                );

            color: white;

            border-radius: 18px;

            padding: 30px;

            box-shadow:
                0 10px 30px
                rgba(37, 99, 235, .25);

            margin-bottom: 25px;
        }

        .header-card h1 {
            font-weight: 700;
        }

        .stat-card {
            border: none;
            border-radius: 16px;

            color: white;

            overflow: hidden;

            box-shadow:
                0 6px 20px
                rgba(0, 0, 0, .08);

            transition: .2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-body {
            padding: 23px;
        }

        .stat-icon {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: 700;
        }

        .stat-title {
            opacity: .9;
        }

        .blue {
            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );
        }

        .green {
            background:
                linear-gradient(
                    135deg,
                    #059669,
                    #047857
                );
        }

        .orange {
            background:
                linear-gradient(
                    135deg,
                    #f59e0b,
                    #d97706
                );
        }

        .purple {
            background:
                linear-gradient(
                    135deg,
                    #7c3aed,
                    #6d28d9
                );
        }

        .cyan {
            background:
                linear-gradient(
                    135deg,
                    #0891b2,
                    #0e7490
                );
        }

        .red {
            background:
                linear-gradient(
                    135deg,
                    #dc2626,
                    #b91c1c
                );
        }

        .dark {
            background:
                linear-gradient(
                    135deg,
                    #374151,
                    #111827
                );
        }

        .section-card {
            background: white;

            border-radius: 16px;

            border: none;

            box-shadow:
                0 5px 25px
                rgba(0, 0, 0, .05);

            margin-bottom: 25px;

            overflow: hidden;
        }

        .section-header {
            padding: 20px 23px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            justify-content:
                space-between;

            align-items: center;
        }

        .section-header h4 {
            margin: 0;

            font-weight: 700;
        }

        .section-body {
            padding: 23px;
        }

        .table thead th {
            background: #111827;
            color: white;
            border: none;
            padding: 14px;
        }

        .table tbody td {
            padding: 14px;
            vertical-align: middle;
        }

        .progress {
            height: 10px;
            border-radius: 20px;
        }

        .progress-bar {
            border-radius: 20px;
        }

        .grade-badge {
            display: inline-block;

            min-width: 48px;

            text-align: center;

            padding: 7px 11px;

            border-radius: 20px;

            font-weight: 700;
        }

        .grade-a {
            background: #dcfce7;
            color: #15803d;
        }

        .grade-b {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .grade-c {
            background: #fef3c7;
            color: #b45309;
        }

        .grade-d {
            background: #fee2e2;
            color: #b91c1c;
        }

        .gpa-card {
            border-radius: 16px;

            background: white;

            padding: 25px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.05);

            text-align: center;
        }

        .gpa-value {
            font-size: 35px;
            font-weight: 700;
            color: #2563eb;
        }

        @media print {

            body {
                background: white;
            }

            .no-print {
                display: none !important;
            }

            .report-container {
                max-width: 100%;
                padding: 0;
            }

            .section-card,
            .stat-card {
                box-shadow: none;
            }

        }

    </style>

</head>


<body>


<div class="report-container">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="header-card">

        <div class="row align-items-center">

            <div class="col-md-8">

                <div class="mb-2">

                    <i class="bi bi-bar-chart-fill fs-1"></i>

                </div>

                <h1>
                    System Reports
                </h1>

                <p class="mb-0 fs-5">

                    Campus Student Information System

                </p>

            </div>


            <div class="col-md-4 text-md-end mt-3 mt-md-0">

                <div>
                    <i class="bi bi-calendar3"></i>
                    <?php echo e($report_date); ?>
                </div>

                <div class="mt-3">

                    <button
                        onclick="window.print()"
                        class="btn btn-light no-print">

                        <i class="bi bi-printer-fill"></i>

                        Print Report

                    </button>


                    <a
                        href="dashboard.php"
                        class="btn btn-dark no-print">

                        <i class="bi bi-arrow-left"></i>

                        Dashboard

                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="row g-4">


        <div class="col-xl-3 col-md-6">

            <div class="stat-card blue">

                <div class="stat-body">

                    <div class="stat-icon">

                        <i class="bi bi-people-fill"></i>

                    </div>

                    <div class="stat-number">

                        <?php
                        echo $total_students;
                        ?>

                    </div>

                    <div class="stat-title">

                        Total Students

                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card green">

                <div class="stat-body">

                    <div class="stat-icon">

                        <i class="bi bi-person-workspace"></i>

                    </div>

                    <div class="stat-number">

                        <?php
                        echo $total_instructors;
                        ?>

                    </div>

                    <div class="stat-title">

                        Instructors

                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card orange">

                <div class="stat-body">

                    <div class="stat-icon">

                        <i class="bi bi-book-fill"></i>

                    </div>

                    <div class="stat-number">

                        <?php
                        echo $total_courses;
                        ?>

                    </div>

                    <div class="stat-title">

                        Courses

                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card purple">

                <div class="stat-body">

                    <div class="stat-icon">

                        <i class="bi bi-building-fill"></i>

                    </div>

                    <div class="stat-number">

                        <?php
                        echo $total_departments;
                        ?>

                    </div>

                    <div class="stat-title">

                        Departments

                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card cyan">

                <div class="stat-body">

                    <div class="stat-icon">

                        <i class="bi bi-calendar3"></i>

                    </div>

                    <div class="stat-number">

                        <?php
                        echo $total_semesters;
                        ?>

                    </div>

                    <div class="stat-title">

                        Semesters

                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card dark">

                <div class="stat-body">

                    <div class="stat-icon">

                        <i class="bi bi-journal-check"></i>

                    </div>

                    <div class="stat-number">

                        <?php
                        echo $total_registrations;
                        ?>

                    </div>

                    <div class="stat-title">

                        Course Registrations

                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card red">

                <div class="stat-body">

                    <div class="stat-icon">

                        <i class="bi bi-award-fill"></i>

                    </div>

                    <div class="stat-number">

                        <?php
                        echo $total_grades;
                        ?>

                    </div>

                    <div class="stat-title">

                        Grades Uploaded

                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card green">

                <div class="stat-body">

                    <div class="stat-icon">

                        <i class="bi bi-graph-up-arrow"></i>

                    </div>

                    <div class="stat-number">

                        <?php
                        echo number_format(
                            $average_gpa,
                            2
                        );
                        ?>

                    </div>

                    <div class="stat-title">

                        Average Grade Point

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         GPA SUMMARY
    ================================================== -->

    <div class="row g-4 mt-1">


        <div class="col-md-4">

            <div class="gpa-card">

                <div class="text-muted">
                    Average GPA
                </div>

                <div class="gpa-value">

                    <?php
                    echo number_format(
                        $average_gpa,
                        2
                    );
                    ?>

                </div>

                <small class="text-muted">
                    Based on uploaded grades
                </small>

            </div>

        </div>


        <div class="col-md-4">

            <div class="gpa-card">

                <div class="text-muted">
                    Highest Grade Point
                </div>

                <div class="gpa-value text-success">

                    <?php
                    echo number_format(
                        $highest_grade_point,
                        2
                    );
                    ?>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="gpa-card">

                <div class="text-muted">
                    Lowest Grade Point
                </div>

                <div class="gpa-value text-danger">

                    <?php
                    echo number_format(
                        $lowest_grade_point,
                        2
                    );
                    ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         DEPARTMENT REPORT
    ================================================== -->

    <div class="section-card mt-4">

        <div class="section-header">

            <h4>

                <i class="bi bi-building text-primary"></i>

                Students by Department

            </h4>

        </div>


        <div class="section-body">

            <?php if (count($department_data) > 0): ?>

                <?php

                $max_students = 1;

                foreach ($department_data as $department) {

                    if (
                        (int)$department['total_students']
                        > $max_students
                    ) {

                        $max_students =
                            (int)$department['total_students'];
                    }
                }

                ?>


                <?php foreach ($department_data as $department): ?>

                    <?php

                    $total =
                        (int)$department['total_students'];

                    $percentage =
                        ($total / $max_students) * 100;

                    ?>


                    <div class="mb-4">

                        <div class="d-flex
                                    justify-content-between
                                    mb-2">

                            <strong>

                                <?php
                                echo e(
                                    $department[
                                        'department_name'
                                    ]
                                );
                                ?>

                            </strong>

                            <span class="badge bg-primary">

                                <?php
                                echo $total;
                                ?>

                                Students

                            </span>

                        </div>


                        <div class="progress">

                            <div
                                class="progress-bar bg-primary"
                                style="
                                width:
                                <?php
                                echo $percentage;
                                ?>%;
                                ">
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>


            <?php else: ?>

                <div class="text-center text-muted">

                    No department data available.

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =================================================
         GRADE DISTRIBUTION
    ================================================== -->

    <div class="section-card">

        <div class="section-header">

            <h4>

                <i class="bi bi-bar-chart-fill text-success"></i>

                Grade Distribution

            </h4>

        </div>


        <div class="section-body">

            <?php if (count($grade_distribution) > 0): ?>

                <div class="row">

                    <?php foreach (
                        $grade_distribution
                        as $grade
                    ): ?>

                        <?php

                        $total =
                            (int)$grade['total'];

                        $percentage =
                            $total > 0 &&
                            $total_grades > 0

                            ? ($total / $total_grades)
                              * 100

                            : 0;

                        $letter =
                            strtoupper(
                                $grade['grade']
                            );

                        $class =
                            'grade-d';

                        if (
                            in_array(
                                $letter,
                                ['A+', 'A', 'A-']
                            )
                        ) {

                            $class =
                                'grade-a';
                        }
                        elseif (
                            in_array(
                                $letter,
                                ['B+', 'B', 'B-']
                            )
                        ) {

                            $class =
                                'grade-b';
                        }
                        elseif (
                            in_array(
                                $letter,
                                ['C+', 'C', 'C-']
                            )
                        ) {

                            $class =
                                'grade-c';
                        }

                        ?>


                        <div class="col-md-6 mb-4">

                            <div class="d-flex
                                        justify-content-between
                                        mb-2">

                                <span
                                    class="grade-badge
                                    <?php
                                    echo $class;
                                    ?>">

                                    <?php
                                    echo e(
                                        $grade['grade']
                                    );
                                    ?>

                                </span>


                                <strong>

                                    <?php
                                    echo $total;
                                    ?>

                                    Records

                                </strong>

                            </div>


                            <div class="progress">

                                <div
                                    class="progress-bar
                                    <?php
                                    echo
                                        $letter[0] === 'A'
                                        ? 'bg-success'
                                        : (
                                            $letter[0] === 'B'
                                            ? 'bg-primary'
                                            : (
                                                $letter[0] === 'C'
                                                ? 'bg-warning'
                                                : 'bg-danger'
                                            )
                                        );
                                    ?>"
                                    style="
                                    width:
                                    <?php
                                    echo $percentage;
                                    ?>%;
                                    ">
                                </div>

                            </div>


                            <small class="text-muted">

                                <?php
                                echo number_format(
                                    $percentage,
                                    1
                                );
                                ?>%

                            </small>

                        </div>


                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="text-center text-muted">

                    No grade data available.

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =================================================
         RECENT GRADES
    ================================================== -->

    <div class="section-card">

        <div class="section-header">

            <h4>

                <i class="bi bi-award-fill text-primary"></i>

                Recent Grade Records

            </h4>

            <span class="badge bg-primary">

                <?php
                echo count($recent_grades);
                ?>

                Records

            </span>

        </div>


        <div class="section-body p-0">

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Registration No</th>

                            <th>Student</th>

                            <th>Course</th>

                            <th>Grade</th>

                            <th>Grade Point</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (
                        count($recent_grades) > 0
                    ): ?>

                        <?php $number = 1; ?>


                        <?php foreach (
                            $recent_grades
                            as $row
                        ): ?>

                            <?php

                            $letter =
                                strtoupper(
                                    $row['grade']
                                );

                            $class =
                                'grade-d';

                            if (
                                in_array(
                                    $letter,
                                    ['A+', 'A', 'A-']
                                )
                            ) {

                                $class =
                                    'grade-a';

                            }
                            elseif (
                                in_array(
                                    $letter,
                                    ['B+', 'B', 'B-']
                                )
                            ) {

                                $class =
                                    'grade-b';

                            }
                            elseif (
                                in_array(
                                    $letter,
                                    ['C+', 'C', 'C-']
                                )
                            ) {

                                $class =
                                    'grade-c';
                            }

                            ?>


                            <tr>

                                <td>

                                    <?php
                                    echo $number++;
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $row[
                                            'registration_no'
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <strong>

                                        <?php
                                        echo e(
                                            $row[
                                                'first_name'
                                            ]
                                            . ' '
                                            . $row[
                                                'last_name'
                                            ]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <strong>

                                        <?php
                                        echo e(
                                            $row[
                                                'course_code'
                                            ]
                                        );
                                        ?>

                                    </strong>

                                    <div
                                        class="small text-muted">

                                        <?php
                                        echo e(
                                            $row[
                                                'course_name'
                                            ]
                                        );
                                        ?>

                                    </div>

                                </td>


                                <td>

                                    <span
                                        class="grade-badge
                                        <?php
                                        echo $class;
                                        ?>">

                                        <?php
                                        echo e(
                                            $row['grade']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <strong>

                                        <?php
                                        echo number_format(
                                            (float)
                                            $row[
                                                'grade_point'
                                            ],
                                            2
                                        );
                                        ?>

                                    </strong>

                                </td>

                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center
                                       text-muted
                                       py-4">

                                No grade records found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- =================================================
         FOOTER
    ================================================== -->

    <div class="text-center text-muted py-4">

        <i class="bi bi-mortarboard-fill"></i>

        Campus SIS — University Student Information System

        <br>

        <small>
            Official Administrative Report
        </small>

    </div>


</div>


</body>

</html>