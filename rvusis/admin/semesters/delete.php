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
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Check whether this semester is used in grades
$check = mysqli_query($conn,
"SELECT * FROM grades WHERE semester_id='$id'");

if(mysqli_num_rows($check) > 0)
{
    echo "<script>
    alert('Cannot delete this semester because grade records already exist.');
    window.location='index.php';
    </script>";
    exit();
}

// Delete semester
$sql = "DELETE FROM semesters WHERE semester_id='$id'";

if(mysqli_query($conn,$sql))
{
    header("Location: index.php");
    exit();
}
else
{
    echo "Error deleting semester: ".mysqli_error($conn);
}
?>