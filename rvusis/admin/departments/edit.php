<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");


/* =========================
   CHECK DEPARTMENT ID
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$department_id = intval($_GET['id']);

$message = "";
$message_type = "";


/* =========================
   FETCH DEPARTMENT
========================= */

$sql = "
SELECT *
FROM departments
WHERE department_id = '$department_id'
LIMIT 1
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

$department = mysqli_fetch_assoc($result);

if (!$department) {
    header("Location: index.php");
    exit();
}


/* =========================
   UPDATE DEPARTMENT
========================= */

if (isset($_POST['update'])) {

    $department_name = trim($_POST['department_name']);

    $department_name = mysqli_real_escape_string(
        $conn,
        $department_name
    );


    /* Validate */

    if ($department_name == "") {

        $message = "Department name is required.";
        $message_type = "danger";

    } else {

        /* Check duplicate department */

        $check = mysqli_query($conn, "
            SELECT department_id
            FROM departments
            WHERE department_name = '$department_name'
            AND department_id != '$department_id'
            LIMIT 1
        ");

        if (!$check) {
            die("Database Error: " . mysqli_error($conn));
        }


        if (mysqli_num_rows($check) > 0) {

            $message =
                "A department with this name already exists.";

            $message_type = "warning";

        } else {

            /* Update */

            $update = mysqli_query($conn, "
                UPDATE departments
                SET department_name = '$department_name'
                WHERE department_id = '$department_id'
            ");


            if ($update) {

                $message =
                    "Department updated successfully.";

                $message_type = "success";


                /* Refresh displayed data */

                $department['department_name'] =
                    $department_name;

            } else {

                $message =
                    "Unable to update department.";

                $message_type = "danger";
            }
        }
    }
}


/* =========================
   COUNT STUDENTS
========================= */

$student_count = 0;

$student_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM students
    WHERE department_id = '$department_id'
");

if ($student_query) {

    $student_count =
        mysqli_fetch_assoc($student_query)['total'];
}


/* =========================
   COUNT COURSES
========================= */

$course_count = 0;

$course_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM courses
    WHERE department_id = '$department_id'
");

if ($course_query) {

    $course_count =
        mysqli_fetch_assoc($course_query)['total'];
}


/* =========================
   COUNT INSTRUCTORS
========================= */

$instructor_count = 0;

$instructor_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM instructors
    WHERE department_id = '$department_id'
");

if ($instructor_query) {

    $instructor_count =
        mysqli_fetch_assoc($instructor_query)['total'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Edit Department | RVU Academic System
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    <style>

        body {

            background: #f4f6f9;

            font-family: Arial, sans-serif;

        }


        /* Header */

        .page-header {

            background: linear-gradient(
                135deg,
                #198754,
                #146c43
            );

            color: white;

            border-radius: 16px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 6px 20px
                rgba(0,0,0,0.12);

        }


        /* Main Card */

        .main-card {

            border: none;

            border-radius: 16px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.08);

        }


        /* Department Icon */

        .department-icon {

            width: 90px;

            height: 90px;

            border-radius: 18px;

            background: #d1e7dd;

            color: #198754;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 42px;

            margin: auto;

        }


        /* Form */

        .form-label {

            font-weight: 600;

        }


        .form-control {

            border-radius: 10px;

            padding: 12px;

        }


        .form-control:focus {

            border-color: #198754;

            box-shadow:
                0 0 0 0.2rem
                rgba(25,135,84,0.15);

        }


        /* Statistics */

        .stat-card {

            border: none;

            border-radius: 14px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,0.07);

        }


        .stat-number {

            font-size: 28px;

            font-weight: 700;

        }


        /* Buttons */

        .btn {

            border-radius: 9px;

        }


        .save-btn {

            padding: 12px 25px;

            font-weight: 600;

        }

    </style>

</head>


<body>


<div class="container-fluid px-4 py-4">


    <!-- =========================
         PAGE HEADER
    ========================== -->

    <div class="page-header">

        <div
            class="d-flex
                   justify-content-between
                   align-items-center
                   flex-wrap">

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-building"></i>

                    Edit Department

                </h2>

                <p class="mb-0 opacity-75">

                    Update department information

                </p>

            </div>


            <div class="mt-3 mt-md-0">

                <a
                    href="index.php"
                    class="btn btn-light">

                    <i class="bi bi-arrow-left"></i>

                    Back to Departments

                </a>

            </div>

        </div>

    </div>



    <!-- =========================
         ALERT
    ========================== -->

    <?php if ($message != "") { ?>

        <div
            class="alert
                   alert-<?php echo $message_type; ?>
                   alert-dismissible
                   fade show
                   shadow-sm">

            <i class="bi bi-info-circle"></i>

            <?php echo htmlspecialchars($message); ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php } ?>



    <div class="row g-4">


        <!-- =========================
             EDIT FORM
        ========================== -->

        <div class="col-lg-8">

            <div class="card main-card">

                <div
                    class="card-header
                           bg-dark
                           text-white
                           py-3">

                    <h5 class="mb-0">

                        <i class="bi bi-pencil-square"></i>

                        Department Information

                    </h5>

                </div>


                <div class="card-body p-4">


                    <form
                        method="POST"
                        autocomplete="off">


                        <!-- Department ID -->

                        <div class="mb-4">

                            <label class="form-label">

                                Department ID

                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?php
                                    echo $department_id;
                                ?>"
                                readonly>

                            <div
                                class="form-text">

                                Department ID cannot be changed.

                            </div>

                        </div>



                        <!-- Department Name -->

                        <div class="mb-4">

                            <label
                                for="department_name"
                                class="form-label">

                                Department Name

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <div class="input-group">

                                <span
                                    class="input-group-text">

                                    <i
                                        class="bi bi-building">
                                    </i>

                                </span>


                                <input
                                    type="text"
                                    id="department_name"
                                    name="department_name"
                                    class="form-control"
                                    placeholder="Enter department name"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $department[
                                                'department_name'
                                            ]
                                        );
                                    ?>"
                                    required>

                            </div>

                        </div>



                        <!-- Buttons -->

                        <div
                            class="d-flex
                                   justify-content-between
                                   align-items-center
                                   flex-wrap
                                   gap-2">


                            <a
                                href="index.php"
                                class="btn
                                       btn-outline-secondary">

                                <i
                                    class="bi bi-x-circle">
                                </i>

                                Cancel

                            </a>


                            <button
                                type="submit"
                                name="update"
                                class="btn
                                       btn-success
                                       save-btn">

                                <i
                                    class="bi bi-check-circle">
                                </i>

                                Update Department

                            </button>


                        </div>


                    </form>

                </div>

            </div>

        </div>



        <!-- =========================
             DEPARTMENT PREVIEW
        ========================== -->

        <div class="col-lg-4">

            <div class="card main-card mb-4">

                <div class="card-body text-center p-4">


                    <div class="department-icon">

                        <i class="bi bi-building"></i>

                    </div>


                    <h4 class="mt-3 mb-1">

                        <?php
                        echo htmlspecialchars(
                            $department[
                                'department_name'
                            ]
                        );
                        ?>

                    </h4>


                    <p class="text-muted">

                        Department ID:

                        <strong>

                            <?php
                            echo $department_id;
                            ?>

                        </strong>

                    </p>


                    <span
                        class="badge bg-success">

                        <i
                            class="bi bi-check-circle">
                        </i>

                        Active Department

                    </span>

                </div>

            </div>



            <!-- =========================
                 STATISTICS
            ========================== -->

            <div class="row g-3">


                <!-- Students -->

                <div class="col-12">

                    <div class="card stat-card">

                        <div class="card-body">

                            <div
                                class="d-flex
                                       align-items-center">

                                <div
                                    class="rounded-circle
                                           bg-primary
                                           text-white
                                           p-3">

                                    <i
                                        class="bi bi-people-fill">
                                    </i>

                                </div>


                                <div class="ms-3">

                                    <div
                                        class="text-muted">

                                        Students

                                    </div>

                                    <div
                                        class="stat-number">

                                        <?php
                                        echo $student_count;
                                        ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- Courses -->

                <div class="col-12">

                    <div class="card stat-card">

                        <div class="card-body">

                            <div
                                class="d-flex
                                       align-items-center">

                                <div
                                    class="rounded-circle
                                           bg-warning
                                           text-dark
                                           p-3">

                                    <i
                                        class="bi bi-book-fill">
                                    </i>

                                </div>


                                <div class="ms-3">

                                    <div
                                        class="text-muted">

                                        Courses

                                    </div>

                                    <div
                                        class="stat-number">

                                        <?php
                                        echo $course_count;
                                        ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- Instructors -->

                <div class="col-12">

                    <div class="card stat-card">

                        <div class="card-body">

                            <div
                                class="d-flex
                                       align-items-center">

                                <div
                                    class="rounded-circle
                                           bg-success
                                           text-white
                                           p-3">

                                    <i
                                        class="bi bi-person-badge-fill">
                                    </i>

                                </div>


                                <div class="ms-3">

                                    <div
                                        class="text-muted">

                                        Instructors

                                    </div>

                                    <div
                                        class="stat-number">

                                        <?php
                                        echo $instructor_count;
                                        ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>

</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>