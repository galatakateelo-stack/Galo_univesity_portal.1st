<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

$message = "";
$message_type = "";


/* =========================
   DELETE DEPARTMENT
========================= */

if (isset($_GET['delete'])) {

    $department_id = intval($_GET['delete']);

    // Check whether students use this department
    $check_students = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM students
        WHERE department_id = '$department_id'
    ");

    $student_data = mysqli_fetch_assoc($check_students);

    // Check whether courses use this department
    $check_courses = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM courses
        WHERE department_id = '$department_id'
    ");

    $course_data = mysqli_fetch_assoc($check_courses);

    if (
        $student_data['total'] > 0 ||
        $course_data['total'] > 0
    ) {

        $message = "This department cannot be deleted because it is already being used by students or courses.";
        $message_type = "warning";

    } else {

        $delete = mysqli_query($conn, "
            DELETE FROM departments
            WHERE department_id = '$department_id'
        ");

        if ($delete) {

            $message = "Department deleted successfully.";
            $message_type = "success";

        } else {

            $message = "Unable to delete department.";
            $message_type = "danger";
        }
    }
}


/* =========================
   ADD DEPARTMENT
========================= */

if (isset($_POST['add_department'])) {

    $department_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['department_name'])
    );

    if ($department_name == "") {

        $message = "Please enter a department name.";
        $message_type = "danger";

    } else {

        // Prevent duplicate department
        $check = mysqli_query($conn, "
            SELECT department_id
            FROM departments
            WHERE department_name = '$department_name'
        ");

        if (mysqli_num_rows($check) > 0) {

            $message = "This department already exists.";
            $message_type = "warning";

        } else {

            $insert = mysqli_query($conn, "
                INSERT INTO departments
                (department_name)
                VALUES
                ('$department_name')
            ");

            if ($insert) {

                $message = "Department added successfully.";
                $message_type = "success";

            } else {

                $message = "Unable to add department.";
                $message_type = "danger";
            }
        }
    }
}


/* =========================
   SEARCH
========================= */

$search = "";

if (isset($_GET['search'])) {

    $search = mysqli_real_escape_string(
        $conn,
        trim($_GET['search'])
    );
}


/* =========================
   DEPARTMENT LIST
========================= */

$departments = mysqli_query($conn, "
    SELECT
        d.department_id,
        d.department_name,

        (
            SELECT COUNT(*)
            FROM students s
            WHERE s.department_id = d.department_id
        ) AS student_count,

        (
            SELECT COUNT(*)
            FROM courses c
            WHERE c.department_id = d.department_id
        ) AS course_count

    FROM departments d

    WHERE d.department_name LIKE '%$search%'

    ORDER BY d.department_id DESC
");


/* =========================
   STATISTICS
========================= */

$total_departments_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM departments"
);

$total_departments = mysqli_fetch_assoc(
    $total_departments_result
)['total'];


$total_students_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM students"
);

$total_students = mysqli_fetch_assoc(
    $total_students_result
)['total'];


$total_courses_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM courses"
);

$total_courses = mysqli_fetch_assoc(
    $total_courses_result
)['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Department Management | RVU Academic System</title>


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


        /* Page Header */

        .page-header {

            background: linear-gradient(
                135deg,
                #198754,
                #157347
            );

            color: white;

            border-radius: 16px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.10);

        }


        /* Cards */

        .management-card {

            border: none;

            border-radius: 15px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.08);

        }


        .stat-card {

            border: none;

            border-radius: 15px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,0.07);

        }


        .stat-icon {

            width: 55px;

            height: 55px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;

        }


        /* Buttons */

        .btn {

            border-radius: 8px;

        }


        /* Table */

        .table {

            vertical-align: middle;

        }


        .table thead th {

            white-space: nowrap;

        }


        /* Department Icon */

        .department-icon {

            width: 42px;

            height: 42px;

            border-radius: 10px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: #e8f5e9;

            color: #198754;

            font-size: 20px;

            margin-right: 10px;

        }


        /* Badges */

        .count-badge {

            padding: 7px 11px;

            border-radius: 20px;

            font-size: 12px;

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

                    Department Management

                </h2>

                <p class="mb-0 text-white-50">

                    Manage academic departments and monitor
                    department activity

                </p>

            </div>


            <div class="mt-3 mt-md-0">

                <a
                    href="../dashboard.php"
                    class="btn btn-light">

                    <i class="bi bi-arrow-left"></i>

                    Dashboard

                </a>

            </div>

        </div>

    </div>



    <!-- =========================
         ALERT MESSAGE
    ========================== -->

    <?php if ($message != "") { ?>

        <div
            class="alert
                   alert-<?php echo $message_type; ?>
                   alert-dismissible
                   fade show
                   shadow-sm">

            <?php echo $message; ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php } ?>



    <!-- =========================
         STATISTICS
    ========================== -->

    <div class="row g-4 mb-4">


        <!-- Departments -->

        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div
                        class="d-flex
                               align-items-center">

                        <div
                            class="stat-icon
                                   bg-success
                                   text-white">

                            <i class="bi bi-building"></i>

                        </div>


                        <div class="ms-3">

                            <h6 class="text-muted mb-1">

                                Total Departments

                            </h6>

                            <h3 class="mb-0">

                                <?php
                                echo $total_departments;
                                ?>

                            </h3>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- Students -->

        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div
                        class="d-flex
                               align-items-center">

                        <div
                            class="stat-icon
                                   bg-primary
                                   text-white">

                            <i class="bi bi-people"></i>

                        </div>


                        <div class="ms-3">

                            <h6 class="text-muted mb-1">

                                Total Students

                            </h6>

                            <h3 class="mb-0">

                                <?php
                                echo $total_students;
                                ?>

                            </h3>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- Courses -->

        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div
                        class="d-flex
                               align-items-center">

                        <div
                            class="stat-icon
                                   bg-warning
                                   text-dark">

                            <i class="bi bi-book"></i>

                        </div>


                        <div class="ms-3">

                            <h6 class="text-muted mb-1">

                                Total Courses

                            </h6>

                            <h3 class="mb-0">

                                <?php
                                echo $total_courses;
                                ?>

                            </h3>

                        </div>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <div class="row g-4">


        <!-- =========================
             ADD DEPARTMENT
        ========================== -->

        <div class="col-lg-4">

            <div class="card management-card">

                <div
                    class="card-header
                           bg-success
                           text-white
                           py-3">

                    <h5 class="mb-0">

                        <i class="bi bi-plus-circle"></i>

                        Add New Department

                    </h5>

                </div>


                <div class="card-body p-4">


                    <form method="POST">


                        <div class="mb-4">

                            <label
                                class="form-label fw-semibold">

                                Department Name

                            </label>


                            <div class="input-group">

                                <span
                                    class="input-group-text">

                                    <i
                                        class="bi
                                               bi-building">
                                    </i>

                                </span>


                                <input
                                    type="text"
                                    name="department_name"
                                    class="form-control"
                                    placeholder="e.g. Computer Science"
                                    required>

                            </div>

                        </div>


                        <button
                            type="submit"
                            name="add_department"
                            class="btn btn-success w-100">

                            <i class="bi bi-plus-lg"></i>

                            Add Department

                        </button>


                    </form>


                </div>

            </div>

        </div>



        <!-- =========================
             DEPARTMENT LIST
        ========================== -->

        <div class="col-lg-8">

            <div class="card management-card">


                <div
                    class="card-header
                           bg-dark
                           text-white
                           py-3">

                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center">

                        <h5 class="mb-0">

                            <i class="bi bi-list-ul"></i>

                            Department List

                        </h5>


                        <span
                            class="badge bg-success">

                            <?php
                            echo $total_departments;
                            ?>

                        </span>

                    </div>

                </div>



                <div class="card-body">


                    <!-- SEARCH -->

                    <form
                        method="GET"
                        class="mb-4">

                        <div class="input-group">

                            <span
                                class="input-group-text">

                                <i class="bi bi-search"></i>

                            </span>


                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search department..."
                                value="<?php
                                    echo htmlspecialchars($search);
                                ?>">


                            <button
                                class="btn btn-success">

                                Search

                            </button>


                            <a
                                href="index.php"
                                class="btn
                                       btn-outline-secondary">

                                Reset

                            </a>

                        </div>

                    </form>



                    <!-- TABLE -->

                    <div class="table-responsive">

                        <table
                            class="table
                                   table-hover">

                            <thead class="table-dark">

                                <tr>

                                    <th>#</th>

                                    <th>
                                        Department
                                    </th>

                                    <th>
                                        Students
                                    </th>

                                    <th>
                                        Courses
                                    </th>

                                    <th class="text-center">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php

                            if (
                                mysqli_num_rows(
                                    $departments
                                ) > 0
                            ) {

                                $number = 1;


                                while (
                                    $row =
                                    mysqli_fetch_assoc(
                                        $departments
                                    )
                                ) {

                            ?>

                                <tr>


                                    <!-- NUMBER -->

                                    <td>

                                        <span
                                            class="text-muted">

                                            <?php
                                            echo $number++;
                                            ?>

                                        </span>

                                    </td>



                                    <!-- DEPARTMENT -->

                                    <td>

                                        <div
                                            class="d-flex
                                                   align-items-center">

                                            <span
                                                class="department-icon">

                                                <i
                                                    class="bi
                                                           bi-building">
                                                </i>

                                            </span>


                                            <strong>

                                                <?php
                                                echo htmlspecialchars(
                                                    $row[
                                                        'department_name'
                                                    ]
                                                );
                                                ?>

                                            </strong>

                                        </div>

                                    </td>



                                    <!-- STUDENTS -->

                                    <td>

                                        <span
                                            class="badge
                                                   bg-primary
                                                   count-badge">

                                            <i
                                                class="bi
                                                       bi-people">
                                            </i>

                                            <?php
                                            echo $row[
                                                'student_count'
                                            ];
                                            ?>

                                            Students

                                        </span>

                                    </td>



                                    <!-- COURSES -->

                                    <td>

                                        <span
                                            class="badge
                                                   bg-warning
                                                   text-dark
                                                   count-badge">

                                            <i
                                                class="bi
                                                       bi-book">
                                            </i>

                                            <?php
                                            echo $row[
                                                'course_count'
                                            ];
                                            ?>

                                            Courses

                                        </span>

                                    </td>



                                    <!-- ACTION -->

                                    <td
                                        class="text-center">


                                        <a
                                            href="edit.php?id=<?php
                                                echo $row[
                                                    'department_id'
                                                ];
                                            ?>"
                                            class="btn
                                                   btn-warning
                                                   btn-sm">

                                            <i
                                                class="bi
                                                       bi-pencil">
                                            </i>

                                            Edit

                                        </a>


                                        <a
                                            href="index.php?delete=<?php
                                                echo $row[
                                                    'department_id'
                                                ];
                                            ?>"
                                            class="btn
                                                   btn-danger
                                                   btn-sm"
                                            onclick="return confirm(
                                                'Are you sure you want to delete this department?'
                                            );">

                                            <i
                                                class="bi
                                                       bi-trash">
                                            </i>

                                            Delete

                                        </a>


                                    </td>


                                </tr>


                            <?php

                                }

                            } else {

                            ?>


                                <tr>

                                    <td
                                        colspan="5"
                                        class="text-center py-5">

                                        <i
                                            class="bi
                                                   bi-building-x
                                                   fs-1
                                                   text-muted">
                                        </i>


                                        <h5 class="mt-3">

                                            No departments found

                                        </h5>


                                        <p class="text-muted">

                                            Add a department to
                                            get started.

                                        </p>

                                    </td>

                                </tr>


                            <?php } ?>


                            </tbody>

                        </table>

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