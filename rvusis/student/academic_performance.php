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
    s.first_name,
    s.last_name,
    s.registration_no,
    d.department_name
FROM students s
LEFT JOIN departments d
    ON s.department_id = d.department_id
WHERE s.student_id = ?
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    die("Student information not found.");
}

/* =========================================================
   GET ALL GRADES GROUPED BY SEMESTER
   ========================================================= */

$sql = "
SELECT
    g.grade,
    g.grade_point,
    c.course_code,
    c.course_name,
    c.credit_hours,
    s.semester_id,
    s.semester_name,
    s.academic_year
FROM grades g

INNER JOIN courses c
    ON g.course_id = c.course_id

INNER JOIN semesters s
    ON g.semester_id = s.semester_id

WHERE g.student_id = ?

ORDER BY
    s.semester_id ASC,
    c.course_code ASC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

/* =========================================================
   ORGANIZE SEMESTERS
   ========================================================= */

$semesters = [];

$total_credits = 0;
$total_quality_points = 0;

while ($row = mysqli_fetch_assoc($result)) {

    $semester_id = $row['semester_id'];

    if (!isset($semesters[$semester_id])) {

        $semesters[$semester_id] = [
            'semester_name' => $row['semester_name'],
            'academic_year' => $row['academic_year'],
            'courses' => [],
            'credits' => 0,
            'quality_points' => 0
        ];
    }

    /*
       Use the grade_point stored in the grades table.
       This is better than calculating it again from the
       letter grade.
    */

    $credit = (float)$row['credit_hours'];
    $grade_point = (float)$row['grade_point'];

    $quality_point = $credit * $grade_point;

    $semesters[$semester_id]['courses'][] = [
        'course_code' => $row['course_code'],
        'course_name' => $row['course_name'],
        'credit_hours' => $credit,
        'grade' => $row['grade'],
        'grade_point' => $grade_point,
        'quality_point' => $quality_point
    ];

    $semesters[$semester_id]['credits'] += $credit;
    $semesters[$semester_id]['quality_points'] += $quality_point;

    $total_credits += $credit;
    $total_quality_points += $quality_point;
}

/* =========================================================
   CUMULATIVE GPA
   ========================================================= */

$cumulative_gpa = ($total_credits > 0)
    ? $total_quality_points / $total_credits
    : 0;

/* =========================================================
   ACADEMIC STANDING
   ========================================================= */

if ($cumulative_gpa >= 3.50) {

    $standing = "Excellent";
    $standing_class = "success";
    $standing_icon = "bi-award";

} elseif ($cumulative_gpa >= 3.00) {

    $standing = "Good Standing";
    $standing_class = "primary";
    $standing_icon = "bi-check-circle";

} elseif ($cumulative_gpa >= 2.00) {

    $standing = "Satisfactory";
    $standing_class = "warning";
    $standing_icon = "bi-exclamation-circle";

} else {

    $standing = "Academic Warning";
    $standing_class = "danger";
    $standing_icon = "bi-exclamation-triangle";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Academic Performance</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
rel="stylesheet">

<style>

body {
    background: #f5f7fb;
    font-family: Arial, sans-serif;
}

.page-header {
    background: linear-gradient(135deg, #0d6efd, #084298);
    color: white;
    border-radius: 18px;
    padding: 30px;
    margin-bottom: 25px;
}

.stat-card {
    border: none;
    border-radius: 16px;
    transition: 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-icon {
    font-size: 35px;
}

.semester-card {
    border: none;
    border-radius: 18px;
    overflow: hidden;
}

.semester-header {
    background: #212529;
    color: white;
    padding: 18px 22px;
}

.gpa-number {
    font-size: 30px;
    font-weight: bold;
}

.table th {
    white-space: nowrap;
}

.badge-grade {
    font-size: 13px;
    padding: 7px 10px;
}

.empty-box {
    padding: 60px 20px;
    text-align: center;
}

</style>

</head>

<body>

<div class="container py-4">

<!-- =====================================================
     HEADER
     ===================================================== -->

<div class="page-header shadow">

<div class="d-flex justify-content-between align-items-center flex-wrap">

<div>

<h2 class="fw-bold mb-1">

<i class="bi bi-mortarboard-fill"></i>

Academic Performance

</h2>

<p class="mb-0">

<?php
echo htmlspecialchars(
    $student['first_name'] . " " . $student['last_name']
);
?>

</p>

</div>

<div class="mt-3 mt-md-0">

<a href="dashboard.php"
class="btn btn-light">

<i class="bi bi-arrow-left"></i>

Back to Dashboard

</a>

</div>

</div>

</div>


<!-- =====================================================
     STUDENT INFORMATION
     ===================================================== -->

<div class="card shadow-sm border-0 mb-4">

<div class="card-body">

<div class="row">

<div class="col-md-4">

<small class="text-muted">
Registration Number
</small>

<h5 class="fw-bold">

<?php
echo htmlspecialchars($student['registration_no']);
?>

</h5>

</div>

<div class="col-md-4">

<small class="text-muted">
Department
</small>

<h5 class="fw-bold">

<?php
echo htmlspecialchars($student['department_name']);
?>

</h5>

</div>

<div class="col-md-4">

<small class="text-muted">
Academic Record
</small>

<h5 class="fw-bold">

<?php echo count($semesters); ?> Semesters

</h5>

</div>

</div>

</div>

</div>


<!-- =====================================================
     SUMMARY CARDS
     ===================================================== -->

<div class="row g-3 mb-4">

<div class="col-md-4">

<div class="card stat-card shadow-sm h-100">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">
Cumulative GPA
</small>

<div class="gpa-number text-primary">

<?php
echo number_format($cumulative_gpa, 2);
?>

</div>

</div>

<i class="bi bi-graph-up-arrow stat-icon text-primary"></i>

</div>

</div>

</div>

</div>


<div class="col-md-4">

<div class="card stat-card shadow-sm h-100">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">
Completed Credits
</small>

<div class="gpa-number">

<?php
echo number_format($total_credits, 0);
?>

</div>

</div>

<i class="bi bi-book stat-icon text-success"></i>

</div>

</div>

</div>

</div>


<div class="col-md-4">

<div class="card stat-card shadow-sm h-100">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">
Academic Standing
</small>

<h5 class="mt-2">

<span class="badge bg-<?php echo $standing_class; ?>">

<i class="bi <?php echo $standing_icon; ?>"></i>

<?php echo $standing; ?>

</span>

</h5>

</div>

<i class="bi <?php echo $standing_icon; ?> stat-icon text-<?php echo $standing_class; ?>"></i>

</div>

</div>

</div>

</div>

</div>


<!-- =====================================================
     SEMESTER PERFORMANCE
     ===================================================== -->

<?php if (count($semesters) > 0): ?>

<?php foreach ($semesters as $semester): ?>

<?php

$semester_gpa = ($semester['credits'] > 0)
    ? $semester['quality_points'] / $semester['credits']
    : 0;

if ($semester_gpa >= 3.50) {
    $gpa_class = "success";
} elseif ($semester_gpa >= 3.00) {
    $gpa_class = "primary";
} elseif ($semester_gpa >= 2.00) {
    $gpa_class = "warning";
} else {
    $gpa_class = "danger";
}

?>

<div class="card semester-card shadow-sm mb-4">

<div class="semester-header">

<div class="d-flex justify-content-between align-items-center flex-wrap">

<div>

<h5 class="mb-1">

<i class="bi bi-calendar3"></i>

<?php
echo htmlspecialchars($semester['semester_name']);
?>

</h5>

<small>

Academic Year:

<?php
echo htmlspecialchars($semester['academic_year']);
?>

</small>

</div>

<div class="text-end mt-2 mt-md-0">

<div>

GPA

<strong class="fs-4">

<?php
echo number_format($semester_gpa, 2);
?>

</strong>

</div>

<span class="badge bg-<?php echo $gpa_class; ?>">

<?php
echo $semester['credits'];
?>

 Credits

</span>

</div>

</div>

</div>


<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>

<th>#</th>

<th>Course Code</th>

<th>Course</th>

<th>Credits</th>

<th>Grade</th>

<th>Grade Point</th>

<th>Quality Points</th>

</tr>

</thead>

<tbody>

<?php

$number = 1;

foreach ($semester['courses'] as $course):

?>

<tr>

<td>

<?php echo $number++; ?>

</td>

<td>

<strong>

<?php
echo htmlspecialchars($course['course_code']);
?>

</strong>

</td>

<td>

<?php
echo htmlspecialchars($course['course_name']);
?>

</td>

<td>

<?php
echo $course['credit_hours'];
?>

</td>

<td>

<span class="badge bg-primary badge-grade">

<?php
echo htmlspecialchars($course['grade']);
?>

</span>

</td>

<td>

<?php
echo number_format($course['grade_point'], 2);
?>

</td>

<td>

<strong>

<?php
echo number_format($course['quality_point'], 2);
?>

</strong>

</td>

</tr>

<?php endforeach; ?>

</tbody>

<tfoot class="table-light">

<tr>

<th colspan="3" class="text-end">

Semester Total

</th>

<th>

<?php
echo $semester['credits'];
?>

</th>

<th></th>

<th></th>

<th>

<?php
echo number_format(
    $semester['quality_points'],
    2
);
?>

</th>

</tr>

</tfoot>

</table>

</div>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="card shadow-sm border-0">

<div class="card-body empty-box">

<i class="bi bi-journal-x display-3 text-muted"></i>

<h4 class="mt-3">

No Academic Records Yet

</h4>

<p class="text-muted">

Your academic performance will appear here after grades are uploaded.

</p>

</div>

</div>

<?php endif; ?>


<!-- =====================================================
     FOOTER ACTIONS
     ===================================================== -->

<div class="d-flex gap-2 flex-wrap mb-4">

<a href="transcript.php"
class="btn btn-primary">

<i class="bi bi-file-earmark-text"></i>

View Transcript

</a>

<a href="dashboard.php"
class="btn btn-outline-secondary">

<i class="bi bi-house"></i>

Dashboard

</a>

</div>

</div>

</body>

</html>