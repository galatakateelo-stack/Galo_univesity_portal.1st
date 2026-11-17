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

$grade_id = intval($_GET['id']);

// Delete the selected grade
$sql = "DELETE FROM grades WHERE grade_id = '$grade_id'";

if(mysqli_query($conn, $sql))
{
    echo "<script>
    alert('Grade deleted successfully!');
    window.location='index.php';
    </script>";
}
else
{
    die("Error: " . mysqli_error($conn));
}
?>