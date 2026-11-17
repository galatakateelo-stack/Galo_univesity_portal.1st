<?php
session_start();
include("../../includes/db.php");

$message = "";

if(isset($_POST['save']))
{
    $department_name = trim($_POST['department_name']);

    $check = mysqli_query($conn, "SELECT * FROM departments WHERE department_name='$department_name'");

    if(mysqli_num_rows($check) > 0)
    {
        $message = "<div class='alert alert-warning'>Department already exists.</div>";
    }
    else
    {
        mysqli_query($conn, "INSERT INTO departments(department_name)
                             VALUES('$department_name')");

        $message = "<div class='alert alert-success'>
        Department added successfully.
        </div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Add Department</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h3>Add Department</h3>

</div>

<div class="card-body">

<?php echo $message; ?>

<form method="POST">

<label>Department Name</label>

<input
type="text"
name="department_name"
class="form-control"
required>

<br>

<button
class="btn btn-success"
name="save">

Save Department

</button>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</form>

</div>

</div>

</div>

</body>
</html>