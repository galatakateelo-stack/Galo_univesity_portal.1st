<?php
session_start();

include("../includes/db.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

/* =========================================================
   STUDENT INFORMATION
========================================================= */

$student_query = mysqli_query($conn, "
    SELECT 
        students.student_id,
        students.first_name,
        students.last_name,
        students.registration_no,
        departments.department_name
    FROM students
    LEFT JOIN departments
        ON students.department_id = departments.department_id
    WHERE students.student_id = '$student_id'
");

$student = mysqli_fetch_assoc($student_query);

if (!$student) {
    die("Student information not found.");
}


/* =========================================================
   TRANSCRIPT DATA
========================================================= */

$grades_query = mysqli_query($conn, "
    SELECT
        grades.grade_id,
        grades.grade,
        grades.grade_point,

        courses.course_code,
        courses.course_name,
        courses.credit_hours,

        semesters.semester_id,
        semesters.semester_name,
        semesters.academic_year

    FROM grades

    INNER JOIN courses
        ON grades.course_id = courses.course_id

    INNER JOIN semesters
        ON grades.semester_id = semesters.semester_id

    WHERE grades.student_id = '$student_id'

    ORDER BY
        semesters.semester_id ASC,
        courses.course_code ASC,
        grades.grade_id ASC
");


/* =========================================================
   CALCULATE TOTALS
========================================================= */

$total_credits = 0;
$total_quality_points = 0;

$semester_data = [];

while ($row = mysqli_fetch_assoc($grades_query)) {

    $credit_hours = (float)$row['credit_hours'];
    $grade_point  = (float)$row['grade_point'];

    $quality_point = $credit_hours * $grade_point;

    $total_credits += $credit_hours;
    $total_quality_points += $quality_point;

    $semester_id = $row['semester_id'];

    if (!isset($semester_data[$semester_id])) {

        $semester_data[$semester_id] = [
            'semester_name' => $row['semester_name'],
            'academic_year' => $row['academic_year'],
            'credits' => 0,
            'quality_points' => 0
        ];
    }

    $semester_data[$semester_id]['credits'] += $credit_hours;

    $semester_data[$semester_id]['quality_points'] += $quality_point;
}


/* =========================================================
   OVERALL CGPA
========================================================= */

if ($total_credits > 0) {
    $cgpa = $total_quality_points / $total_credits;
} else {
    $cgpa = 0;
}


/* =========================================================
   ACADEMIC STATUS
========================================================= */

if ($total_credits == 0) {

    $academic_status = "NO GRADES";

    $status_class = "secondary";

} elseif ($cgpa >= 2.00) {

    $academic_status = "PASS";

    $status_class = "success";

} else {

    $academic_status = "ACADEMIC WARNING";

    $status_class = "danger";
}


/* =========================================================
   GRADE COLOR
========================================================= */

function gradeBadge($grade)
{
    switch ($grade) {

        case "A+":
        case "A":
            return "success";

        case "A-":
        case "B+":
        case "B":
            return "primary";

        case "B-":
        case "C+":
        case "C":
            return "warning";

        default:
            return "danger";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Academic Transcript</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<style>

/* =========================================================
   GENERAL
========================================================= */

body {

    background: #f4f6f9;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    color: #212529;

}


/* =========================================================
   MAIN TRANSCRIPT
========================================================= */

.transcript-container {

    max-width: 1200px;

    margin: 40px auto;

    background: white;

    border-radius: 15px;

    box-shadow:
        0 8px 30px rgba(0,0,0,0.10);

    overflow: hidden;

}


/* =========================================================
   HEADER
========================================================= */

.transcript-header {

    background:
        linear-gradient(
            135deg,
            #198754,
            #0d6efd
        );

    color: white;

    padding: 35px;

}


.university-title {

    font-size: 30px;

    font-weight: 700;

    margin-bottom: 5px;

}


.transcript-title {

    font-size: 20px;

    opacity: .9;

}


/* =========================================================
   STUDENT INFORMATION
========================================================= */

.student-info {

    padding: 30px;

    background: white;

}


.info-box {

    background: #f8f9fa;

    border-radius: 10px;

    padding: 18px;

    border-left: 5px solid #198754;

}


.info-label {

    color: #6c757d;

    font-size: 13px;

    text-transform: uppercase;

    font-weight: 600;

}


.info-value {

    font-size: 18px;

    font-weight: 600;

}


/* =========================================================
   SUMMARY
========================================================= */

.summary-section {

    padding: 0 30px 30px 30px;

}


.summary-card {

    border: none;

    border-radius: 12px;

    padding: 22px;

    height: 100%;

    box-shadow:
        0 4px 15px rgba(0,0,0,.08);

}


.summary-number {

    font-size: 30px;

    font-weight: 700;

}


.summary-label {

    color: #6c757d;

}


/* =========================================================
   TABLE
========================================================= */

.table-section {

    padding: 0 30px 30px 30px;

}


.table thead th {

    background: #212529;

    color: white;

    padding: 14px;

    white-space: nowrap;

}


.table tbody td {

    padding: 13px;

    vertical-align: middle;

}


.table tbody tr:hover {

    background: #f8f9fa;

}


.quality-point {

    font-weight: 600;

}


/* =========================================================
   SEMESTER GPA
========================================================= */

.semester-section {

    padding: 0 30px 30px 30px;

}


.semester-card {

    border: 1px solid #dee2e6;

    border-radius: 12px;

    padding: 20px;

    margin-bottom: 15px;

    background: #fff;

}


.semester-title {

    font-size: 18px;

    font-weight: 700;

}


.semester-gpa {

    font-size: 25px;

    font-weight: 700;

    color: #198754;

}


/* =========================================================
   BUTTONS
========================================================= */

.action-section {

    padding: 0 30px 35px 30px;

}


.btn-modern {

    border-radius: 8px;

    padding: 11px 20px;

    font-weight: 600;

}


/* =========================================================
   FOOTER
========================================================= */

.transcript-footer {

    background: #f8f9fa;

    padding: 20px 30px;

    color: #6c757d;

    text-align: center;

    border-top: 1px solid #dee2e6;

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    body {

        background: white;

    }

    .transcript-container {

        margin: 0;

        box-shadow: none;

        max-width: 100%;

    }

    .action-section {

        display: none !important;

    }

    .transcript-header {

        background: #198754 !important;

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

    .table thead th {

        background: #212529 !important;

        color: white !important;

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

    .badge {

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

}

</style>

</head>


<body>


<div class="transcript-container" id="printArea">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="transcript-header">

<div class="d-flex justify-content-between align-items-center">

<div>

<div class="university-title">

<i class="bi bi-mortarboard-fill"></i>

University ERP

</div>

<div class="transcript-title">

Academic Transcript

</div>

</div>


<div class="text-end">

<div>

<strong>
Academic Year
</strong>

</div>

<div>

<?php echo date("Y"); ?>

</div>

</div>

</div>

</div>


<!-- =====================================================
     STUDENT INFORMATION
===================================================== -->

<div class="student-info">

<h4 class="mb-4">

<i class="bi bi-person-badge"></i>

Student Information

</h4>


<div class="row g-3">


<div class="col-md-4">

<div class="info-box">

<div class="info-label">

Student Name

</div>

<div class="info-value">

<?php

echo htmlspecialchars(
    $student['first_name']
    . " "
    . $student['last_name']
);

?>

</div>

</div>

</div>


<div class="col-md-4">

<div class="info-box">

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


<div class="col-md-4">

<div class="info-box">

<div class="info-label">

Department

</div>

<div class="info-value">

<?php

echo htmlspecialchars(
    $student['department_name']
);

?>

</div>

</div>

</div>


</div>

</div>


<!-- =====================================================
     ACADEMIC SUMMARY
===================================================== -->

<div class="summary-section">

<h4 class="mb-4">

<i class="bi bi-bar-chart-fill"></i>

Academic Summary

</h4>


<div class="row g-3">


<div class="col-md-3">

<div class="summary-card">

<div class="summary-label">

Total Credit Hours

</div>

<div class="summary-number text-primary">

<?php

echo number_format(
    $total_credits,
    0
);

?>

</div>

</div>

</div>


<div class="col-md-3">

<div class="summary-card">

<div class="summary-label">

Total Quality Points

</div>

<div class="summary-number text-info">

<?php

echo number_format(
    $total_quality_points,
    2
);

?>

</div>

</div>

</div>


<div class="col-md-3">

<div class="summary-card">

<div class="summary-label">

CGPA

</div>

<div class="summary-number text-success">

<?php

echo number_format(
    $cgpa,
    2
);

?>

</div>

</div>

</div>


<div class="col-md-3">

<div class="summary-card">

<div class="summary-label">

Academic Status

</div>

<div class="mt-2">

<span class="badge bg-<?php echo $status_class; ?> fs-6">

<?php

echo $academic_status;

?>

</span>

</div>

</div>

</div>


</div>

</div>


<!-- =====================================================
     COURSE RESULTS
===================================================== -->

<div class="table-section">

<h4 class="mb-4">

<i class="bi bi-journal-text"></i>

Academic Results

</h4>


<div class="table-responsive">

<table class="table table-bordered table-hover">


<thead>

<tr>

<th>Semester</th>

<th>Course Code</th>

<th>Course Name</th>

<th>Credit Hours</th>

<th>Grade</th>

<th>Grade Point</th>

<th>Quality Point</th>

</tr>

</thead>


<tbody>


<?php

/* Run the query again because the previous loop
   was used for calculations. */

$grades_display = mysqli_query($conn, "

SELECT

grades.grade,

grades.grade_point,

courses.course_code,

courses.course_name,

courses.credit_hours,

semesters.semester_name,

semesters.academic_year

FROM grades

INNER JOIN courses

ON grades.course_id = courses.course_id

INNER JOIN semesters

ON grades.semester_id = semesters.semester_id

WHERE grades.student_id = '$student_id'

ORDER BY

semesters.semester_id ASC,

courses.course_code ASC,

grades.grade_id ASC

");


if (mysqli_num_rows($grades_display) == 0) {

?>

<tr>

<td colspan="7"
    class="text-center text-muted py-4">

<i class="bi bi-info-circle"></i>

No grades have been uploaded yet.

</td>

</tr>

<?php

}


while ($row = mysqli_fetch_assoc($grades_display)) {

    $credit_hours =
        (float)$row['credit_hours'];

    $grade_point =
        (float)$row['grade_point'];

    $quality_point =
        $credit_hours * $grade_point;

?>

<tr>


<td>

<?php

echo htmlspecialchars(
    $row['semester_name']
);

?>

<br>

<small class="text-muted">

<?php

echo htmlspecialchars(
    $row['academic_year']
);

?>

</small>

</td>


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

echo $credit_hours;

?>

</td>


<td>

<span class="badge bg-<?php

echo gradeBadge(
    $row['grade']
);

?> fs-6">

<?php

echo htmlspecialchars(
    $row['grade']
);

?>

</span>

</td>


<td>

<strong>

<?php

echo number_format(
    $grade_point,
    2
);

?>

</strong>

</td>


<td class="quality-point">

<?php

echo number_format(
    $quality_point,
    2
);

?>

</td>


</tr>

<?php

}

?>


</tbody>

</table>

</div>

</div>


<!-- =====================================================
     SEMESTER GPA
===================================================== -->

<div class="semester-section">

<h4 class="mb-4">

<i class="bi bi-graph-up-arrow"></i>

Semester Performance

</h4>


<?php

if (count($semester_data) > 0) {

foreach ($semester_data as $semester) {

    if ($semester['credits'] > 0) {

        $semester_gpa =
            $semester['quality_points']
            /
            $semester['credits'];

    } else {

        $semester_gpa = 0;

    }

?>

<div class="semester-card">

<div class="row align-items-center">


<div class="col-md-5">

<div class="semester-title">

<?php

echo htmlspecialchars(
    $semester['semester_name']
);

?>

</div>

<div class="text-muted">

Academic Year:

<?php

echo htmlspecialchars(
    $semester['academic_year']
);

?>

</div>

</div>


<div class="col-md-3">

<div class="text-muted">

Credit Hours

</div>

<strong>

<?php

echo number_format(
    $semester['credits'],
    0
);

?>

</strong>

</div>


<div class="col-md-2">

<div class="text-muted">

Quality Points

</div>

<strong>

<?php

echo number_format(
    $semester['quality_points'],
    2
);

?>

</strong>

</div>


<div class="col-md-2 text-md-end">

<div class="text-muted">

Semester GPA

</div>

<div class="semester-gpa">

<?php

echo number_format(
    $semester_gpa,
    2
);

?>

</div>

</div>


</div>

</div>

<?php

}

}

else {

?>

<div class="alert alert-info">

<i class="bi bi-info-circle"></i>

No semester results available.

</div>

<?php

}

?>

</div>


<!-- =====================================================
     ACTION BUTTONS
===================================================== -->

<div class="action-section">

<div class="d-flex flex-wrap gap-2">


<a href="dashboard.php"
   class="btn btn-primary btn-modern">

<i class="bi bi-arrow-left"></i>

Back to Dashboard

</a>


<button
onclick="window.print()"
class="btn btn-success btn-modern">

<i class="bi bi-printer-fill"></i>

Print Transcript

</button>


<a href="logout.php"
   class="btn btn-outline-danger btn-modern">

<i class="bi bi-box-arrow-right"></i>

Logout

</a>


</div>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<div class="transcript-footer">

<i class="bi bi-shield-check"></i>

Official Academic Transcript

<br>

<small>

Generated by University ERP System

|

<?php

echo date("d F Y");

?>

</small>

</div>


</div>


</body>

</html>