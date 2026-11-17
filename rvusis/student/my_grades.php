<?php
session_start();
include("../includes/db.php");

if(!isset($_SESSION['student_id']))
{
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$query = "
SELECT
courses.course_name,
semesters.semester_name,
semesters.academic_year,
grades.grade,
instructors.full_name

FROM grades

JOIN courses
ON grades.course_id = courses.course_id

JOIN semesters
ON grades.semester_id = semesters.semester_id

JOIN instructors
ON grades.instructor_id = instructors.instructor_id

WHERE grades.student_id='$student_id'
";

$result = mysqli_query($conn,$query);
?>

<!DOCTYPE html>
<html>
<head>

<title>My Grades</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h2>My Grades</h2>

</div>

<div class="card-body">

<table class="table table-bordered table-striped">

<tr>

<th>Course</th>
<th>Semester</th>
<th>Academic Year</th>
<th>Grade</th>
<th>Instructor</th>

</tr>

<?php

while($row=mysqli_fetch_assoc($result))
{

?>

<tr>

<td><?php echo $row['course_name']; ?></td>

<td><?php echo $row['semester_name']; ?></td>

<td><?php echo $row['academic_year']; ?></td>

<td><?php echo $row['grade']; ?></td>

<td><?php echo $row['full_name']; ?></td>

</tr>

<?php
}
?>

</table>

<a href="dashboard.php" class="btn btn-primary">

Back

</a>

</div>

</div>

</div>

</body>
</html>