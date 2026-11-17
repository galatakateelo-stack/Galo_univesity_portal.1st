<?php

$conn = mysqli_connect(
    "127.0.0.1",
    "root",
    "",
    "rvuac_sis",
    3306
);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>