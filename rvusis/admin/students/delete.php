<?php
session_start();
include("../../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    // Check if the student has grades
    $check = mysqli_query($conn, "SELECT * FROM grades WHERE student_id='$id'");

    if (mysqli_num_rows($check) > 0) {

        echo "<script>
        alert('Cannot delete this student because grade records already exist.');
        window.location='index.php';
        </script>";

        exit();
    }

    $delete = mysqli_query($conn, "DELETE FROM students WHERE student_id='$id'");

    if ($delete) {

        header("Location: index.php");
        exit();

    } else {

        echo mysqli_error($conn);

    }
}
?>