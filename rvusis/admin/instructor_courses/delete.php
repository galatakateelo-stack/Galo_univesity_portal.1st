<?php
session_start();
include("../../includes/db.php");

if(!isset($_SESSION['admin_id']))
{
    header("Location: ../login.php");
    exit();
}

if(isset($_GET['id']))
{
    $id = intval($_GET['id']);

    $sql = "DELETE FROM instructor_courses
            WHERE id='$id'";

    if(mysqli_query($conn,$sql))
    {
        header("Location: index.php?msg=deleted");
        exit();
    }
    else
    {
        die("Error: ".mysqli_error($conn));
    }
}
else
{
    header("Location: index.php");
    exit();
}
?>