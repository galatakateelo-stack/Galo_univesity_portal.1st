<?php

/*
|--------------------------------------------------------------------------
| Campus Grades - Authentication & Authorization
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

function requireLogin()
{
    if (
        !isset($_SESSION['admin_id']) &&
        !isset($_SESSION['instructor_id']) &&
        !isset($_SESSION['student_id'])
    ) {
        header("Location: ../login.php");
        exit();
    }
}


/*
|--------------------------------------------------------------------------
| Require Admin
|--------------------------------------------------------------------------
*/

function requireAdmin()
{
    if (!isset($_SESSION['admin_id'])) {
        header("Location: ../login.php");
        exit();
    }
}


/*
|--------------------------------------------------------------------------
| Require Instructor
|--------------------------------------------------------------------------
*/

function requireInstructor()
{
    if (!isset($_SESSION['instructor_id'])) {
        header("Location: ../login.php");
        exit();
    }
}


/*
|--------------------------------------------------------------------------
| Require Student
|--------------------------------------------------------------------------
*/

function requireStudent()
{
    if (!isset($_SESSION['student_id'])) {
        header("Location: ../login.php");
        exit();
    }
}


/*
|--------------------------------------------------------------------------
| Get Current User Role
|--------------------------------------------------------------------------
*/

function getUserRole()
{
    if (isset($_SESSION['admin_id'])) {
        return "admin";
    }

    if (isset($_SESSION['instructor_id'])) {
        return "instructor";
    }

    if (isset($_SESSION['student_id'])) {
        return "student";
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

function logoutUser()
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}
?>