<?php

include("../../includes/db.php");

$id = $_GET['id'];

mysqli_query($conn,"DELETE FROM departments WHERE department_id='$id'");

header("Location: index.php");

exit();

?>