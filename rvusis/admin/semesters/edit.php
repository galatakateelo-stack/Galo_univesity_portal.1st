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

$sql = "SELECT * FROM semesters WHERE semester_id='$id'";
$result = mysqli_query($conn,$sql);

if(mysqli_num_rows($result)==0)
{
    die("Semester not found.");
}

$row = mysqli_fetch_assoc($result);

$message = "";

if(isset($_POST['update']))
{
    $semester_name = mysqli_real_escape_string($conn,$_POST['semester_name']);
    $academic_year = mysqli_real_escape_string($conn,$_POST['academic_year']);

    $update = "UPDATE semesters
               SET semester_name='$semester_name',
                   academic_year='$academic_year'
               WHERE semester_id='$id'";

    if(mysqli_query($conn,$update))
    {
        header("Location: index.php");
        exit();
    }
    else
    {
        $message = "<div class='alert alert-danger'>
        Error updating semester.
        </div>";
    }
}
?>

<!DOCTYPE html>
<html>

<head>

<title>Edit Semester</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-warning">

<h3>Edit Semester</h3>

</div>

<div class="card-body">

<?php echo $message; ?>

<form method="POST">

<div class="mb-3">

<label class="form-label">Semester Name</label>

<select name="semester_name" class="form-select">

<option value="Semester I"
<?php if($row['semester_name']=="Semester I") echo "selected"; ?>>
Semester I
</option>

<option value="Semester II"
<?php if($row['semester_name']=="Semester II") echo "selected"; ?>>
Semester II
</option>

<option value="Summer"
<?php if($row['semester_name']=="Summer") echo "selected"; ?>>
Summer
</option>

</select>

</div>

<div class="mb-3">

<label class="form-label">Academic Year</label>

<input
type="text"
name="academic_year"
class="form-control"
value="<?php echo $row['academic_year']; ?>"
required>

</div>

<button
type="submit"
name="update"
class="btn btn-warning">

Update Semester

</button>

<a href="index.php" class="btn btn-secondary">

Cancel

</a>

</form>

</div>

</div>

</div>

</body>

</html>