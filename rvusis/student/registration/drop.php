<?php
session_start();
include("../../includes/db.php");

if(!isset($_SESSION['student_id']))
{
    header("Location: ../login.php");
    exit();
}

if(!isset($_GET['id']))
{
    header("Location: my_courses.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$registration_id = intval($_GET['id']);

// Make sure this registration belongs to the logged-in student
$check = mysqli_query($conn,"
SELECT *
FROM course_registrations
WHERE registration_id='$registration_id'
AND student_id='$student_id'
");

if(mysqli_num_rows($check) == 0)
{
    die("Invalid request.");
}

// Update the status instead of deleting the record
$sql = "
UPDATE course_registrations
SET status='Dropped'
WHERE registration_id='$registration_id'
";

if(mysqli_query($conn, $sql))
{
    echo "<script>
    alert('Course dropped successfully.');
    window.location='my_courses.php';
    </script>";
}
else
{
    die('Error: ' . mysqli_error($conn));
}
?>