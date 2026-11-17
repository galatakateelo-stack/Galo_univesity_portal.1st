<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['instructor_id'])) {
    header("Location: login.php");
    exit();
}

$instructor_id = $_SESSION['instructor_id'];

$sql = "SELECT
            grades.grade_id,
            CONCAT(students.first_name,' ',students.last_name) AS student_name,
            courses.course_name,
            semesters.semester_name,
            grades.grade
        FROM grades

        INNER JOIN students
            ON grades.student_id = students.student_id

        INNER JOIN courses
            ON grades.course_id = courses.course_id

        INNER JOIN semesters
            ON grades.semester_id = semesters.semester_id

        WHERE grades.instructor_id = '$instructor_id'

        ORDER BY grades.grade_id DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>

<head>

    <title>View Grades</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h3>Uploaded Grades</h3>

</div>

<div class="card-body">

<table class="table table-bordered table-hover table-striped">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Student</th>
<th>Course</th>
<th>Semester</th>
<th>Grade</th>
<th width="180">Action</th>

</tr>

</thead>

<tbody>

<?php

if(mysqli_num_rows($result) > 0)
{

    while($row = mysqli_fetch_assoc($result))
    {
?>

<tr>

<td><?php echo $row['grade_id']; ?></td>

<td><?php echo $row['student_name']; ?></td>

<td><?php echo $row['course_name']; ?></td>

<td><?php echo $row['semester_name']; ?></td>

<td>
<span class="badge bg-success fs-6">
<?php echo $row['grade']; ?>
</span>
</td>

<td>

<a href="edit_grade.php?id=<?php echo $row['grade_id']; ?>"
class="btn btn-warning btn-sm">
Edit
</a>

<a href="delete_grade.php?id=<?php echo $row['grade_id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Are you sure you want to delete this grade?');">
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

<td colspan="6" class="text-center text-danger">

No grades uploaded yet.

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