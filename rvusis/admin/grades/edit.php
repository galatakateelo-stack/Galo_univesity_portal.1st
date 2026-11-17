<?php
session_start();

if(!isset($_SESSION['admin_id']))
{
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

if(!isset($_GET['id']))
{
    header("Location:index.php");
    exit();
}

$grade_id = $_GET['id'];

// Load current grade
$sql = "SELECT * FROM grades WHERE grade_id='$grade_id'";
$result = mysqli_query($conn,$sql);
$grade = mysqli_fetch_assoc($result);

// Dropdown data
$students = mysqli_query($conn,"SELECT student_id,registration_no,first_name,last_name FROM students ORDER BY first_name");
$courses = mysqli_query($conn,"SELECT course_id,course_code,course_name FROM courses ORDER BY course_name");
$instructors = mysqli_query($conn,"SELECT instructor_id,full_name FROM instructors ORDER BY full_name");
$semesters = mysqli_query($conn,"SELECT semester_id,semester_name FROM semesters ORDER BY semester_name");

// Update
if(isset($_POST['update']))
{
    $student_id=$_POST['student_id'];
    $course_id=$_POST['course_id'];
    $instructor_id=$_POST['instructor_id'];
    $semester_id=$_POST['semester_id'];
    $letter_grade=$_POST['grade'];

    $update="UPDATE grades SET

    student_id='$student_id',
    course_id='$course_id',
    instructor_id='$instructor_id',
    semester_id='$semester_id',
    grade='$letter_grade'

    WHERE grade_id='$grade_id'";

    if(mysqli_query($conn,$update))
    {
        echo "<script>
        alert('Grade Updated Successfully');
        window.location='index.php';
        </script>";
        exit();
    }
    else
    {
        echo mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>

<head>

<title>Edit Grade</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-warning">

<h3>Edit Grade</h3>

</div>

<div class="card-body">

<form method="POST">

<label class="form-label">Student</label>

<select name="student_id" class="form-select mb-3">

<?php while($row=mysqli_fetch_assoc($students)){ ?>

<option
value="<?php echo $row['student_id'];?>"

<?php
if($row['student_id']==$grade['student_id'])
echo "selected";
?>

>

<?php
echo $row['registration_no']." - ".$row['first_name']." ".$row['last_name'];
?>

</option>

<?php } ?>

</select>

<label class="form-label">Course</label>

<select name="course_id" class="form-select mb-3">

<?php while($row=mysqli_fetch_assoc($courses)){ ?>

<option
value="<?php echo $row['course_id'];?>"

<?php
if($row['course_id']==$grade['course_id'])
echo "selected";
?>

>

<?php
echo $row['course_code']." - ".$row['course_name'];
?>

</option>

<?php } ?>

</select>

<label class="form-label">Instructor</label>

<select name="instructor_id" class="form-select mb-3">

<?php while($row=mysqli_fetch_assoc($instructors)){ ?>

<option
value="<?php echo $row['instructor_id'];?>"

<?php
if($row['instructor_id']==$grade['instructor_id'])
echo "selected";
?>

>

<?php echo $row['full_name'];?>

</option>

<?php } ?>

</select>

<label class="form-label">Semester</label>

<select name="semester_id" class="form-select mb-3">

<?php while($row=mysqli_fetch_assoc($semesters)){ ?>

<option
value="<?php echo $row['semester_id'];?>"

<?php
if($row['semester_id']==$grade['semester_id'])
echo "selected";
?>

>

<?php echo $row['semester_name'];?>

</option>

<?php } ?>

</select>

<label class="form-label">Grade</label>

<select name="grade" class="form-select mb-4">

<?php

$letters=["A+","A","A-","B+","B","B-","C+","C","D","F"];

foreach($letters as $g)
{

?>

<option

<?php

if($g==$grade['grade'])
echo "selected";

?>

>

<?php echo $g;?>

</option>

<?php

}

?>

</select>

<button class="btn btn-success" name="update">

Update Grade

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