<?php
session_start();

if(!isset($_SESSION['admin_id']))
{
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

// Students
$students = mysqli_query($conn,"SELECT student_id, registration_no, first_name, last_name FROM students ORDER BY first_name");

// Courses
$courses = mysqli_query($conn,"SELECT course_id, course_code, course_name FROM courses ORDER BY course_name");

// Instructors
$instructors = mysqli_query($conn,"SELECT instructor_id, full_name FROM instructors ORDER BY full_name");

// Semesters
$semesters = mysqli_query($conn,"SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");

if(isset($_POST['save']))
{
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];
    $semester_id = $_POST['semester_id'];
    $instructor_id = $_POST['instructor_id'];
    $grade = $_POST['grade'];

    $sql = "INSERT INTO grades(student_id,course_id,instructor_id,semester_id,grade)
            VALUES('$student_id','$course_id','$instructor_id','$semester_id','$grade')";

    if(mysqli_query($conn,$sql))
    {
        echo "<script>
        alert('Grade Added Successfully');
        window.location='index.php';
        </script>";
    }
    else
    {
        echo "<div class='alert alert-danger'>".mysqli_error($conn)."</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Add Grade</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h3>Add New Grade</h3>

</div>

<div class="card-body">

<form method="POST">

<div class="mb-3">

<label class="form-label">Student</label>

<select name="student_id" class="form-select" required>

<option value="">Select Student</option>

<?php while($row=mysqli_fetch_assoc($students)){ ?>

<option value="<?php echo $row['student_id']; ?>">

<?php echo $row['registration_no']." - ".$row['first_name']." ".$row['last_name']; ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">Course</label>

<select name="course_id" class="form-select" required>

<option value="">Select Course</option>

<?php while($row=mysqli_fetch_assoc($courses)){ ?>

<option value="<?php echo $row['course_id']; ?>">

<?php echo $row['course_code']." - ".$row['course_name']; ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">Instructor</label>

<select name="instructor_id" class="form-select" required>

<option value="">Select Instructor</option>

<?php while($row=mysqli_fetch_assoc($instructors)){ ?>

<option value="<?php echo $row['instructor_id']; ?>">

<?php echo $row['full_name']; ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">Semester</label>

<select name="semester_id" class="form-select" required>

<option value="">Select Semester</option>

<?php while($row=mysqli_fetch_assoc($semesters)){ ?>

<option value="<?php echo $row['semester_id']; ?>">

<?php echo $row['semester_name']; ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">Grade</label>

<select name="grade" class="form-select" required>

<option>A+</option>
<option>A</option>
<option>A-</option>
<option>B+</option>
<option>B</option>
<option>B-</option>
<option>C+</option>
<option>C</option>
<option>D</option>
<option>F</option>

</select>

</div>

<button class="btn btn-success" name="save">

Save Grade

</button>

<a href="index.php" class="btn btn-secondary">

Cancel

</a>

</form>

</div>

</div>

</div>

</body>
</html>