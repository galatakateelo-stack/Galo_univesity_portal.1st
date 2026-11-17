<?php
session_start();

if(!isset($_SESSION['admin_id']))
{
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

$search = "";

if(isset($_GET['search']))
{
    $search = mysqli_real_escape_string($conn,$_GET['search']);
}

$sql = "
SELECT
grades.grade_id,
students.registration_no,
students.first_name,
students.last_name,
courses.course_code,
courses.course_name,
semesters.semester_name,
instructors.full_name,
grades.grade

FROM grades

INNER JOIN students
ON grades.student_id = students.student_id

INNER JOIN courses
ON grades.course_id = courses.course_id

INNER JOIN semesters
ON grades.semester_id = semesters.semester_id

INNER JOIN instructors
ON grades.instructor_id = instructors.instructor_id

WHERE

students.first_name LIKE '%$search%'

OR students.last_name LIKE '%$search%'

OR students.registration_no LIKE '%$search%'

OR courses.course_name LIKE '%$search%'

OR courses.course_code LIKE '%$search%'

ORDER BY grades.grade_id DESC
";

$result = mysqli_query($conn,$sql);

if(!$result)
{
    die("SQL Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html>

<head>

<title>Grade Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h3>
<i class="fa-solid fa-file-lines"></i>
Grade Management
</h3>

</div>

<div class="card-body">

<div class="row mb-3">

<div class="col-md-6">

<form method="GET">

<div class="input-group">

<input
type="text"
name="search"
class="form-control"
placeholder="Search Student or Course"
value="<?php echo $search; ?>">

<button class="btn btn-primary">

Search

</button>

<a href="index.php"
class="btn btn-secondary">

Reset

</a>

</div>

</form>

</div>

<div class="col-md-6 text-end">

<a href="add.php"
class="btn btn-success">

<i class="fa fa-plus"></i>

Add Grade

</a>

</div>

</div>

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>Registration</th>

<th>Student</th>

<th>Course</th>

<th>Semester</th>

<th>Instructor</th>

<th>Grade</th>

<th>Action</th>

</tr>

</thead>

<tbody>

<?php

if(mysqli_num_rows($result)>0)
{

while($row=mysqli_fetch_assoc($result))
{

$badge="secondary";

switch($row['grade'])
{
    case "A+":
    case "A":
        $badge="success";
        break;

    case "A-":
    case "B+":
    case "B":
        $badge="primary";
        break;

    case "B-":
    case "C+":
    case "C":
        $badge="warning";
        break;

    default:
        $badge="danger";
}

?>

<tr>

<td><?php echo $row['grade_id']; ?></td>

<td><?php echo $row['registration_no']; ?></td>

<td><?php echo $row['first_name']." ".$row['last_name']; ?></td>

<td>

<?php

echo $row['course_code']." - ".$row['course_name'];

?>

</td>

<td><?php echo $row['semester_name']; ?></td>

<td>

<?php echo $row['full_name']; ?>

</td>

<td>

<span class="badge bg-<?php echo $badge; ?>">

<?php echo $row['grade']; ?>

</span>

</td>

<td>

<a href="edit.php?id=<?php echo $row['grade_id']; ?>"
class="btn btn-warning btn-sm">

Edit

</a>

<a href="delete.php?id=<?php echo $row['grade_id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this grade?')">

Delete

</a>

</td>

</tr>

<?php

}

}
else
{

?>

<tr>

<td colspan="8"
class="text-center text-danger">

No Grades Found

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

</body>

</html>