<?php
session_start();
include("../includes/db.php");

if(!isset($_SESSION['instructor_id']))
{
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];

mysqli_query($conn,"DELETE FROM grades WHERE grade_id='$id'");

header("Location: view_grades.php");
exit();
?>