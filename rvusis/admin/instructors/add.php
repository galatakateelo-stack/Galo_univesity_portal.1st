<?php
session_start();
include("../../includes/db.php");

if(!isset($_SESSION['admin_id']))
{
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['save']))
{
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department_id = $_POST['department_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    $check = mysqli_query($conn,"SELECT * FROM instructors WHERE username='$username'");

    if(mysqli_num_rows($check) > 0)
    {
        $error = "Username already exists!";
    }
    else
    {
        $sql = "INSERT INTO instructors
        (full_name,email,phone,department_id,username,password)
        VALUES
        ('$full_name','$email','$phone','$department_id','$username','$password')";

        if(mysqli_query($conn,$sql))
        {
            header("Location: index.php");
            exit();
        }
        else
        {
            $error = mysqli_error($conn);
        }
    }
}

$departments = mysqli_query($conn,"SELECT * FROM departments");
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Instructor</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-success text-white">
<h3>Add New Instructor</h3>
</div>

<div class="card-body">

<?php
if(isset($error))
{
    echo "<div class='alert alert-danger'>$error</div>";
}
?>

<form method="POST">

<label>Full Name</label>
<input type="text" name="full_name" class="form-control" required>

<br>

<label>Email</label>
<input type="email" name="email" class="form-control" required>

<br>

<label>Phone</label>
<input type="text" name="phone" class="form-control" required>

<br>

<label>Department</label>

<select name="department_id" class="form-control" required>

<option value="">Select Department</option>

<?php while($dept=mysqli_fetch_assoc($departments)){ ?>

<option value="<?php echo $dept['department_id']; ?>">
<?php echo $dept['department_name']; ?>
</option>

<?php } ?>

</select>

<br>

<label>Username</label>
<input type="text" name="username" class="form-control" required>

<br>

<label>Password</label>
<input type="password" name="password" class="form-control" required>

<br>

<button type="submit" name="save" class="btn btn-success">
Save Instructor
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