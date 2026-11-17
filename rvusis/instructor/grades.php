<?php
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location: login.php");
    exit();
}

include("../includes/db.php");

$instructor_id = intval($_SESSION['instructor_id']);

$instructor_name = $_SESSION['instructor_name'] ?? "Instructor";


/* =========================================================
   COURSE FILTER
========================================================= */

$course_id = isset($_GET['course_id'])
    ? intval($_GET['course_id'])
    : 0;


/* =========================================================
   ASSIGNED COURSES
========================================================= */

$courses_query = mysqli_query($conn, "
    SELECT
        c.course_id,
        c.course_code,
        c.course_name
    FROM instructor_courses ic
    INNER JOIN courses c
        ON ic.course_id = c.course_id
    WHERE ic.instructor_id = $instructor_id
    ORDER BY c.course_code ASC
");


/*
   Check SQL error
*/

if (!$courses_query) {
    die(
        "Course Query Error: "
        . mysqli_error($conn)
    );
}


/* =========================================================
   GET GRADES
========================================================= */

$sql = "
    SELECT
        grades.grade_id,
        grades.student_id,
        grades.course_id,
        grades.semester_id,
        grades.grade,
        grades.grade_point,

        students.registration_no,
        students.first_name,
        students.last_name,

        courses.course_code,
        courses.course_name,
        courses.credit_hours,

        semesters.semester_name,
        semesters.academic_year

    FROM grades

    INNER JOIN students
        ON grades.student_id = students.student_id

    INNER JOIN courses
        ON grades.course_id = courses.course_id

    LEFT JOIN semesters
        ON grades.semester_id = semesters.semester_id

    WHERE grades.instructor_id = $instructor_id
";


if ($course_id > 0) {

    $sql .= "
        AND grades.course_id = $course_id
    ";

}


$sql .= "
    ORDER BY grades.grade_id DESC
";


$grades_query = mysqli_query($conn, $sql);


/*
   VERY IMPORTANT:
   Show the real MySQL error instead of
   mysqli_fetch_assoc(false)
*/

if (!$grades_query) {

    die(
        "<div style='
            font-family:Arial;
            padding:30px;
            color:#842029;
            background:#f8d7da;
            border:1px solid #f5c2c7;
            margin:30px;
        '>
        <h2>Database Error</h2>
        <p>"
        . htmlspecialchars(mysqli_error($conn))
        . "</p>
        </div>"
    );

}


/* =========================================================
   STATISTICS
========================================================= */

$total_grades = mysqli_num_rows($grades_query);

$student_ids = [];
$course_ids = [];


/*
   We need the records later,
   so store them in an array.
*/

$grade_rows = [];

while ($row = mysqli_fetch_assoc($grades_query)) {

    $grade_rows[] = $row;

    $student_ids[$row['student_id']] = true;

    $course_ids[$row['course_id']] = true;
}


$total_students = count($student_ids);

$total_courses = count($course_ids);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>View Grades | Instructor</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
rel="stylesheet">


<style>

body {

    background:#f4f7fb;

    font-family:
    Arial,
    Helvetica,
    sans-serif;

    color:#172033;
}


/* HEADER */

.top-header {

    background:
    linear-gradient(
        135deg,
        #0d6efd,
        #084298
    );

    color:white;

    padding:28px 35px;

    border-radius:
    0 0 22px 22px;

    box-shadow:
    0 6px 25px
    rgba(0,0,0,.12);
}


.top-header h1 {

    margin:0;

    font-size:30px;

    font-weight:700;
}


.top-header p {

    margin-top:6px;

    opacity:.9;
}


/* MAIN */

.main-container {

    max-width:1250px;

    margin:35px auto;

    padding:0 20px;
}


/* BUTTONS */

.back-buttons {

    margin-bottom:25px;
}


.back-buttons .btn {

    border-radius:10px;

    font-weight:600;

    margin-right:8px;
}


/* STATISTICS */

.stat-card {

    border:none;

    border-radius:18px;

    padding:25px;

    color:white;

    box-shadow:
    0 8px 25px
    rgba(0,0,0,.08);

    transition:.3s;
}


.stat-card:hover {

    transform:
    translateY(-4px);
}


.stat-icon {

    font-size:30px;

    margin-bottom:8px;
}


.stat-number {

    font-size:30px;

    font-weight:700;
}


.stat-title {

    opacity:.9;

    font-size:14px;
}


/* FILTER */

.filter-card {

    background:white;

    border:none;

    border-radius:18px;

    padding:22px;

    margin-top:30px;

    margin-bottom:25px;

    box-shadow:
    0 8px 30px
    rgba(0,0,0,.06);
}


.filter-card label {

    font-weight:600;

    margin-bottom:8px;
}


/* TABLE */

.table-card {

    background:white;

    border:none;

    border-radius:18px;

    overflow:hidden;

    box-shadow:
    0 8px 30px
    rgba(0,0,0,.07);
}


.table-card-header {

    padding:20px 22px;

    border-bottom:
    1px solid #e9ecef;

    display:flex;

    justify-content:space-between;

    align-items:center;
}


.table-card-header h3 {

    margin:0;

    font-weight:700;
}


.table {

    margin:0;
}


.table thead th {

    background:#172033;

    color:white;

    padding:15px;

    white-space:nowrap;
}


.table tbody td {

    padding:14px;

    vertical-align:middle;
}


.table tbody tr:hover {

    background:#f1f5ff;
}


/* GRADE */

.grade-badge {

    padding:7px 12px;

    border-radius:8px;

    font-weight:700;

    display:inline-block;
}


/* EMPTY */

.empty-box {

    background:white;

    border-radius:18px;

    padding:65px 30px;

    text-align:center;

    box-shadow:
    0 8px 30px
    rgba(0,0,0,.06);
}


.empty-box i {

    font-size:55px;

    color:#adb5bd;

    margin-bottom:20px;
}


.empty-box h3 {

    font-weight:700;
}


/* MOBILE */

@media(max-width:768px) {

    .top-header {

        padding:22px;
    }


    .top-header h1 {

        font-size:24px;
    }


    .table {

        min-width:950px;
    }

}

</style>

</head>


<body>


<!-- HEADER -->

<div class="top-header">

<div class="container">

<h1>

<i class="fa-solid fa-chart-line"></i>

View Student Grades

</h1>


<p>

Instructor:

<strong>

<?php
echo htmlspecialchars($instructor_name);
?>

</strong>

</p>

</div>

</div>


<!-- MAIN -->

<div class="main-container">


<!-- BACK -->

<div class="back-buttons">

<a
href="dashboard.php"
class="btn btn-outline-primary">

<i class="fa-solid fa-arrow-left"></i>

Dashboard

</a>

</div>


<!-- STATISTICS -->

<div class="row g-4">


<div class="col-md-4">

<div class="stat-card bg-primary">

<div class="stat-icon">

<i class="fa-solid fa-file-lines"></i>

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


<div class="col-md-4">

<div class="stat-card bg-success">

<div class="stat-icon">

<i class="fa-solid fa-user-graduate"></i>

</div>

<div class="stat-number">

<?php
echo $total_students;
?>

</div>

<div class="stat-title">

Students Graded

</div>

</div>

</div>


<div class="col-md-4">

<div class="stat-card bg-dark">

<div class="stat-icon">

<i class="fa-solid fa-book-open"></i>

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


<!-- FILTER -->

<div class="filter-card">

<div class="row g-3 align-items-end">


<div class="col-md-6">

<label>

<i class="fa-solid fa-filter"></i>

Filter by Course

</label>


<select
class="form-select"
onchange="filterCourse(this.value)">

<option value="0">

All Courses

</option>


<?php

while (
    $course =
    mysqli_fetch_assoc($courses_query)
) {

    $selected =
        ($course_id ==
        $course['course_id'])
        ? "selected"
        : "";

?>

<option
value="<?php
echo $course['course_id'];
?>"
<?php
echo $selected;
?>>

<?php

echo htmlspecialchars(
    $course['course_code']
);

?>

 -

<?php

echo htmlspecialchars(
    $course['course_name']
);

?>

</option>

<?php

}

?>

</select>

</div>


<div class="col-md-6">

<label>

<i class="fa-solid fa-magnifying-glass"></i>

Search Student

</label>


<input
type="text"
id="studentSearch"
class="form-control"
placeholder="Search student...">

</div>


</div>

</div>


<!-- GRADES -->

<?php if ($total_grades > 0): ?>


<div class="table-card">


<div class="table-card-header">

<div>

<h3>

<i class="fa-solid fa-list-check text-primary"></i>

Grade Records

</h3>

<small class="text-muted">

Your uploaded grade records

</small>

</div>


<span class="badge bg-primary">

<?php
echo $total_grades;
?>

Records

</span>

</div>


<div class="table-responsive">

<table
class="table table-hover"
id="gradesTable">


<thead>

<tr>

<th>#</th>

<th>Registration No</th>

<th>Student</th>

<th>Course</th>

<th>Credit</th>

<th>Semester</th>

<th>Grade</th>

<th>Grade Point</th>

<th>Academic Year</th>

</tr>

</thead>


<tbody>

<?php

$number = 1;

foreach (
    $grade_rows
    as $row
) {


$grade =
strtoupper(
trim($row['grade'])
);


$badge_class =
"bg-danger";


switch ($grade) {

case "A+":

case "A":

$badge_class =
"bg-success";

break;


case "A-":

case "B+":

case "B":

$badge_class =
"bg-primary";

break;


case "B-":

case "C+":

case "C":

$badge_class =
"bg-warning text-dark";

break;


case "C-":

case "D+":

case "D":

$badge_class =
"bg-secondary";

break;


case "F":

$badge_class =
"bg-danger";

break;

}

?>


<tr class="grade-row">


<td>

<?php
echo $number++;
?>

</td>


<td class="searchable">

<strong>

<?php

echo htmlspecialchars(
    $row['registration_no']
);

?>

</strong>

</td>


<td class="searchable">

<?php

echo htmlspecialchars(
    $row['first_name']
    . " "
    . $row['last_name']
);

?>

</td>


<td>

<strong>

<?php

echo htmlspecialchars(
    $row['course_code']
);

?>

</strong>

<br>

<small class="text-muted">

<?php

echo htmlspecialchars(
    $row['course_name']
);

?>

</small>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['credit_hours']
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['semester_name']
    ?? "N/A"
);

?>

</td>


<td>

<span
class="grade-badge
<?php
echo $badge_class;
?>">

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
    (float)$row['grade_point'],
    2
);

?>

</strong>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['academic_year']
    ?? "N/A"
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


<?php else: ?>


<div class="empty-box">

<i class="fa-solid fa-folder-open"></i>

<h3>

No Grades Found

</h3>

<p class="text-muted">

You have not uploaded any grades yet.

</p>


<a
href="upload_grade.php"
class="btn btn-success">

<i class="fa-solid fa-upload"></i>

Upload Grade

</a>

</div>


<?php endif; ?>


</div>


<!-- JAVASCRIPT -->

<script>


function filterCourse(courseId)
{

    window.location.href =
        "grades.php?course_id="
        + courseId;

}


document
.getElementById("studentSearch")
.addEventListener(
"keyup",
function()
{

let search =
this.value.toLowerCase();


let rows =
document.querySelectorAll(
".grade-row"
);


rows.forEach(
function(row)
{

let text =
row.innerText.toLowerCase();


if (
text.includes(search)
) {

row.style.display =
"";

}

else {

row.style.display =
"none";

}

});

});


</script>


</body>

</html>