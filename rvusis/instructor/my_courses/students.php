<?php
session_start();
include("../../includes/db.php");

if(!isset($_SESSION['instructor_id']))
{
    header("Location: ../login.php");
    exit();
}

if(!isset($_GET['course_id']))
{
    die("Course not selected.");
}

$course_id = $_GET['course_id'];

$sql = "
SELECT
students.student_id,
students.registration_no,
students.first_name,
students.last_name,

course_registrations.registration_id

FROM course_registrations

INNER JOIN students
ON students.student_id=course_registrations.student_id

WHERE course_registrations.course_id='$course_id'
AND course_registrations.status='Registered'

ORDER BY students.first_name
";

$result=mysqli_query($conn,$sql);
?>

<!DOCTYPE html>
<html>

<head>

<title>Registered Students</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h3>Registered Students</h3>

</div>

<div class="card-body">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>#</th>

<th>Registration No</th>

<th>Student Name</th>

<th>Action</th>

</tr>

</thead>

<tbody>

<?php

$no=1;

while($row=mysqli_fetch_assoc($result))
{

?>

<tr>

<td><?php echo $no++; ?></td>

<td><?php echo $row['registration_no']; ?></td>

<td>

<?php
echo $row['first_name']." ".$row['last_name'];
?>

</td>

<td>

<a
href="../upload_grade.php?student_id=<?php echo $row['student_id']; ?>&course_id=<?php echo $course_id; ?>"
class="btn btn-primary btn-sm">

Upload Grade

</a>

</td>

</tr>

<?php
}
?>

</tbody>

</table>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</div>

</div>

</div>

</body>

</html>