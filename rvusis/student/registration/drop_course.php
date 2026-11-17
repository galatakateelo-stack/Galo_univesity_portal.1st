<?php

session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

$student_id = intval($_SESSION['student_id']);

if (!isset($_GET['id'])) {
    header("Location: my_courses.php");
    exit();
}

$registration_id = intval($_GET['id']);

$delete = mysqli_query($conn, "
    DELETE FROM course_registrations
    WHERE registration_id = '$registration_id'
    AND student_id = '$student_id'
");

header("Location: my_courses.php");
exit();
?>