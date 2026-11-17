<?php
session_start();

include("../includes/db.php");

$error = "";

/*
|--------------------------------------------------------------------------
| If student is already logged in
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| Student Login
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    if ($username === '' || $password === '') {

        $error = "Please enter your username and password.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Find student by username
        |--------------------------------------------------------------------------
        */
        $stmt = mysqli_prepare(
            $conn,
            "SELECT *
             FROM students
             WHERE username = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error = "Database error. Please try again.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            /*
            |--------------------------------------------------------------------------
            | Student found
            |--------------------------------------------------------------------------
            */
            if ($result && mysqli_num_rows($result) === 1) {

                $student = mysqli_fetch_assoc($result);

                $stored_password = $student['password'];

                $password_valid = false;


                /*
                |--------------------------------------------------------------------------
                | 1. Check old plain-text password
                |
                | This allows your existing students to continue logging in.
                |--------------------------------------------------------------------------
                */
                if ($password === $stored_password) {

                    $password_valid = true;


                    /*
                    |--------------------------------------------------------------------------
                    | Automatically convert old password to secure hash
                    |--------------------------------------------------------------------------
                    */
                    $new_hash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                    $update_stmt = mysqli_prepare(
                        $conn,
                        "UPDATE students
                         SET password = ?
                         WHERE student_id = ?"
                    );


                    if ($update_stmt) {

                        $student_id =
                            (int)$student['student_id'];

                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "si",
                            $new_hash,
                            $student_id
                        );

                        mysqli_stmt_execute(
                            $update_stmt
                        );

                        mysqli_stmt_close(
                            $update_stmt
                        );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | 2. Check secure hashed password
                |--------------------------------------------------------------------------
                */
                elseif (
                    password_verify(
                        $password,
                        $stored_password
                    )
                ) {

                    $password_valid = true;
                }


                /*
                |--------------------------------------------------------------------------
                | Login successful
                |--------------------------------------------------------------------------
                */
                if ($password_valid) {

                    /*
                    | Prevent session fixation
                    */
                    session_regenerate_id(true);


                    /*
                    | Store student session
                    */
                    $_SESSION['student_id'] =
                        (int)$student['student_id'];

                    $_SESSION['student_name'] =
                        $student['first_name'];


                    /*
                    | Optional additional session information
                    */
                    $_SESSION['student_username'] =
                        $student['username'];


                    /*
                    | Login timestamp
                    */
                    $_SESSION['login_time'] =
                        time();


                    /*
                    | Redirect to dashboard
                    */
                    header(
                        "Location: dashboard.php"
                    );

                    exit();

                } else {

                    $error =
                        "Invalid username or password.";
                }

            } else {

                $error =
                    "Invalid username or password.";
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

    <title>Student Login | RVU SIS</title>


    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #eef3ff,
                    #f8f9ff
                );

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }


        .login-wrapper {

            width: 100%;

            max-width: 550px;

            padding: 20px;
        }


        .login-card {

            background: white;

            border: none;

            border-radius: 24px;

            overflow: hidden;

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
                    #0d6efd,
                    #6610f2
                );
        }


        .login-icon {

            width: 86px;

            height: 86px;

            margin: 0 auto 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.18);

            font-size: 42px;
        }


        .login-header h1 {

            margin: 0;

            font-size: 34px;

            font-weight: 700;
        }


        .login-header p {

            margin-top: 8px;

            margin-bottom: 0;

            font-size: 17px;

            opacity: 0.85;
        }


        /*
        |--------------------------------------------------------------------------
        | Body
        |--------------------------------------------------------------------------
        */

        .login-body {

            padding: 38px;
        }


        .form-label {

            font-weight: 600;

            color: #212529;

            margin-bottom: 8px;
        }


        .input-group {

            margin-bottom: 22px;
        }


        .input-group-text {

            min-width: 52px;

            justify-content: center;

            background: #f8f9fa;

            border-color: #dee2e6;

            font-size: 18px;
        }


        .form-control {

            min-height: 55px;

            border-radius: 0 10px 10px 0;

            font-size: 16px;
        }


        .form-control:focus {

            border-color: #6610f2;

            box-shadow:
                0 0 0 0.2rem
                rgba(102,16,242,0.12);
        }


        /*
        |--------------------------------------------------------------------------
        | Login button
        |--------------------------------------------------------------------------
        */

        .login-button {

            width: 100%;

            min-height: 55px;

            border: none;

            border-radius: 11px;

            color: white;

            font-size: 17px;

            font-weight: 700;

            background:
                linear-gradient(
                    135deg,
                    #0d6efd,
                    #6610f2
                );

            transition: 0.2s ease;
        }


        .login-button:hover {

            transform: translateY(-1px);

            box-shadow:
                0 8px 20px
                rgba(102,16,242,0.25);
        }


        /*
        |--------------------------------------------------------------------------
        | Security text
        |--------------------------------------------------------------------------
        */

        .security-text {

            margin-top: 25px;

            text-align: center;

            color: #6c757d;

            font-size: 15px;
        }


        /*
        |--------------------------------------------------------------------------
        | Error
        |--------------------------------------------------------------------------
        */

        .alert {

            border-radius: 12px;

            margin-bottom: 25px;
        }


        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        @media (max-width: 576px) {

            .login-wrapper {

                padding: 12px;
            }

            .login-header {

                padding: 32px 20px;
            }

            .login-header h1 {

                font-size: 28px;
            }

            .login-body {

                padding: 25px 20px;
            }
        }

    </style>

</head>


<body>


<div class="login-wrapper">

    <div class="login-card">


        <!-- =========================================================
             HEADER
        ========================================================== -->

        <div class="login-header">

            <div class="login-icon">

                <i class="bi bi-mortarboard-fill"></i>

            </div>


            <h1>
                Student Login
            </h1>


            <p>
                RVU Student Information System
            </p>

        </div>



        <!-- =========================================================
             LOGIN BODY
        ========================================================== -->

        <div class="login-body">


            <?php if (!empty($error)): ?>

                <div class="alert alert-danger">

                    <i
                        class="bi bi-exclamation-triangle-fill me-2"
                    ></i>

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


                <!-- Username -->

                <div>

                    <label class="form-label">

                        Username

                    </label>


                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-person"></i>

                        </span>


                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            placeholder="Enter your username"
                            value="<?php
                                echo htmlspecialchars(
                                    $_POST['username'] ?? ''
                                );
                            ?>"
                            autocomplete="username"
                            required
                        >

                    </div>

                </div>



                <!-- Password -->

                <div>

                    <label class="form-label">

                        Password

                    </label>


                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-lock"></i>

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



                <!-- Login -->

                <button
                    type="submit"
                    name="login"
                    class="login-button"
                >

                    <i
                        class="bi bi-box-arrow-in-right me-2"
                    ></i>

                    Login

                </button>


            </form>

<div class="text-center mt-3">
    <a href="forgot_password.php" class="text-decoration-none">
        Forgot Password?
    </a>
</div>

            <!-- Security -->

            <div class="security-text">

                <i class="bi bi-shield-check me-1"></i>

                Secure Student Portal

            </div>


        </div>

    </div>

</div>



</body>

</html>
