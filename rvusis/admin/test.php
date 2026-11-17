<?php
session_start();

$_SESSION['admin_name']="Administrator";

include("../includes/layouts/header.php");
include("../includes/layouts/sidebar.php");
?>

<div class="main">

<?php include("../includes/layouts/navbar.php"); ?>

<div class="content">

<div class="row">

<div class="col-md-3">

<div class="dashboard-card bg-primary">

<h2>150</h2>

<h5>Students</h5>

</div>

</div>

<div class="col-md-3">

<div class="dashboard-card bg-success">

<h2>25</h2>

<h5>Faculty</h5>

</div>

</div>

<div class="col-md-3">

<div class="dashboard-card bg-warning">

<h2>40</h2>

<h5>Courses</h5>

</div>

</div>

<div class="col-md-3">

<div class="dashboard-card bg-danger">

<h2>8</h2>

<h5>Departments</h5>

</div>

</div>

</div>

<div class="card shadow mt-4">

<div class="card-header">

<h4>Welcome to UniCore ERP</h4>

</div>

<div class="card-body">

This is the new professional university portal layout.

</div>

</div>

</div>

</div>

<?php include("../includes/layouts/footer.php"); ?>