<?php
session_start();

require_once "../includes/db.php";

$error = "";

/*
|--------------------------------------------------------------------------
| If Already Logged In
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['instructor_id'])) {
    header("Location: dashboard.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Login Processing
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login"])) {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Get Instructor
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                instructor_id,
                full_name,
                email,
                phone,
                department_id,
                username,
                password,
                profile_photo
             FROM instructors
             WHERE username = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error = "Database error. Please contact the administrator.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (!$result) {

                $error = "Unable to process login. Please try again.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Check Username
                |--------------------------------------------------------------------------
                */

                if (mysqli_num_rows($result) !== 1) {

                    $error = "Invalid username or password.";

                } else {

                    $instructor = mysqli_fetch_assoc($result);

                    $stored_password = $instructor["password"];

                    /*
                    |--------------------------------------------------------------------------
                    | Check Password
                    |--------------------------------------------------------------------------
                    |
                    | Supports:
                    | 1. Secure password_hash() passwords
                    | 2. Existing old plain-text passwords
                    |
                    */

                    $password_valid = false;
                    $old_password = false;

                    /*
                    | Secure hashed password
                    */

                    if (
                        password_verify(
                            $password,
                            $stored_password
                        )
                    ) {

                        $password_valid = true;

                    }

                    /*
                    | Old plain-text password
                    */

                    elseif (
                        $password === $stored_password
                    ) {

                        $password_valid = true;
                        $old_password = true;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Invalid Password
                    |--------------------------------------------------------------------------
                    */

                    if (!$password_valid) {

                        $error = "Invalid username or password.";

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Convert Old Password To Secure Hash
                        |--------------------------------------------------------------------------
                        */

                        if ($old_password) {

                            $new_hash = password_hash(
                                $password,
                                PASSWORD_DEFAULT
                            );

                            $update = mysqli_prepare(
                                $conn,
                                "UPDATE instructors
                                 SET password = ?
                                 WHERE instructor_id = ?"
                            );

                            if ($update) {

                                $instructor_id =
                                    (int)$instructor["instructor_id"];

                                mysqli_stmt_bind_param(
                                    $update,
                                    "si",
                                    $new_hash,
                                    $instructor_id
                                );

                                mysqli_stmt_execute($update);

                                mysqli_stmt_close($update);
                            }
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Create New Secure Session
                        |--------------------------------------------------------------------------
                        */

                        session_regenerate_id(true);


                        $_SESSION["instructor_id"] =
                            (int)$instructor["instructor_id"];


                        $_SESSION["instructor_name"] =
                            $instructor["full_name"];


                        $_SESSION["instructor_username"] =
                            $instructor["username"];


                        $_SESSION["instructor_email"] =
                            $instructor["email"];


                        $_SESSION["department_id"] =
                            (int)$instructor["department_id"];


                        $_SESSION["profile_photo"] =
                            $instructor["profile_photo"];


                        $_SESSION["role"] =
                            "instructor";


                        $_SESSION["login_time"] =
                            time();


                        /*
                        |--------------------------------------------------------------------------
                        | Login Successful
                        |--------------------------------------------------------------------------
                        */

                        header("Location: dashboard.php");
                        exit();
                    }
                }
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Instructor Login | RVU SIS</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Bootstrap Icons -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet"
>


<style>

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

body {

    min-height: 100vh;

    margin: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #eef2ff,
            #f8f9ff
        );

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}


/*
|--------------------------------------------------------------------------
| Login Wrapper
|--------------------------------------------------------------------------
*/

.login-wrapper {

    width: 100%;

    max-width: 500px;

    padding: 20px;
}


/*
|--------------------------------------------------------------------------
| Login Card
|--------------------------------------------------------------------------
*/

.login-card {

    border: none;

    border-radius: 24px;

    overflow: hidden;

    background: white;

    box-shadow:
        0 15px 45px
        rgba(0, 0, 0, 0.12);
}


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

.login-header {

    padding: 42px 30px;

    text-align: center;

    color: white;

    background:
        linear-gradient(
            135deg,
            #6610f2,
            #0d6efd
        );
}


/*
|--------------------------------------------------------------------------
| Header Icon
|--------------------------------------------------------------------------
*/

.login-icon {

    width: 85px;

    height: 85px;

    margin: 0 auto 20px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(255,255,255,.18);
}


.login-icon i {

    font-size: 42px;
}


/*
|--------------------------------------------------------------------------
| Header Text
|--------------------------------------------------------------------------
*/

.login-header h2 {

    margin-bottom: 8px;

    font-weight: 700;
}


.login-header p {

    margin: 0;

    opacity: .85;
}


/*
|--------------------------------------------------------------------------
| Body
|--------------------------------------------------------------------------
*/

.login-body {

    padding: 38px;
}


/*
|--------------------------------------------------------------------------
| Labels
|--------------------------------------------------------------------------
*/

.form-label {

    font-weight: 600;
}


/*
|--------------------------------------------------------------------------
| Inputs
|--------------------------------------------------------------------------
*/

.form-control {

    min-height: 53px;

    border-radius:
        0 11px 11px 0;

    font-size: 16px;
}


.input-group-text {

    min-width: 52px;

    justify-content: center;

    background: #f8f9fa;

    border-radius:
        11px 0 0 11px;
}


.form-control:focus {

    border-color: #6610f2;

    box-shadow:
        0 0 0
        .2rem
        rgba(
            102,
            16,
            242,
            .12
        );
}


/*
|--------------------------------------------------------------------------
| Login Button
|--------------------------------------------------------------------------
*/

.login-btn {

    min-height: 53px;

    border: none;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #6610f2,
            #0d6efd
        );

    font-size: 17px;

    font-weight: 700;

    transition: .2s;
}


.login-btn:hover {

    transform: translateY(-1px);

    box-shadow:
        0 8px 20px
        rgba(
            102,
            16,
            242,
            .25
        );
}


/*
|--------------------------------------------------------------------------
| Error
|--------------------------------------------------------------------------
*/

.alert {

    border-radius: 12px;
}


/*
|--------------------------------------------------------------------------
| Security
|--------------------------------------------------------------------------
*/

.security-text {

    margin-top: 25px;

    text-align: center;

    color: #6c757d;

    font-size: 14px;
}


/*
|--------------------------------------------------------------------------
| Mobile
|--------------------------------------------------------------------------
*/

@media (max-width: 576px) {

    .login-body {

        padding: 25px 20px;
    }

    .login-header {

        padding: 32px 20px;
    }
}

</style>

</head>


<body>


<div class="login-wrapper">


<div class="card login-card">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="login-header">

    <div class="login-icon">

        <i class="bi bi-person-workspace"></i>

    </div>

    <h2>
        Instructor Login
    </h2>

    <p>
        RVU Student Information System
    </p>

</div>


<!-- =====================================================
     BODY
===================================================== -->

<div class="login-body">


<?php if ($error !== ""): ?>

<div class="alert alert-danger">

    <i class="bi bi-exclamation-triangle-fill me-2"></i>

    <?php
    echo htmlspecialchars($error);
    ?>

</div>

<?php endif; ?>


<form
    method="POST"
    action=""
    autocomplete="off"
>


<!-- =====================================================
     USERNAME
===================================================== -->

<div class="mb-3">

    <label class="form-label">

        Username

    </label>


    <div class="input-group">

        <span class="input-group-text">

            <i class="bi bi-person-fill"></i>

        </span>


        <input
            type="text"
            name="username"
            class="form-control"
            placeholder="Enter your username"
            autocomplete="username"
            required
        >

    </div>

</div>


<!-- =====================================================
     PASSWORD
===================================================== -->

<div class="mb-4">

    <label class="form-label">

        Password

    </label>


    <div class="input-group">

        <span class="input-group-text">

            <i class="bi bi-lock-fill"></i>

        </span>


        <input
            type="password"
            name="password"
            class="form-control"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
        >

    </div>

</div>


<!-- =====================================================
     LOGIN BUTTON
===================================================== -->

<button
    type="submit"
    name="login"
    class="btn btn-primary login-btn w-100"
>

    <i class="bi bi-box-arrow-in-right me-2"></i>

    Login

</button>


</form>
<div class="text-center mt-3">
    <a href="forgot_password.php" class="text-decoration-none">
        Forgot Password?
    </a>
</div>

<!-- =====================================================
     SECURITY MESSAGE
===================================================== -->

<div class="security-text">

    <i class="bi bi-shield-check me-1"></i>

    Secure Instructor Portal

</div>


</div>

</div>

</div>


</body>

</html>

