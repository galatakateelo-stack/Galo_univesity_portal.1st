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
   DELETE SEMESTER
========================= */

if (isset($_GET['delete'])) {

    $semester_id = intval($_GET['delete']);

    // Check whether semester is being used by courses
    $check_courses = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM courses
        WHERE semester_id = '$semester_id'
    ");

    $course_data = mysqli_fetch_assoc($check_courses);

    // Check whether semester is being used by grades
    $check_grades = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM grades
        WHERE semester_id = '$semester_id'
    ");

    $grade_data = mysqli_fetch_assoc($check_grades);

    // Check instructor assignments
    $check_assignments = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM instructor_courses
        WHERE semester_id = '$semester_id'
    ");

    $assignment_data = mysqli_fetch_assoc($check_assignments);

    if (
        $course_data['total'] > 0 ||
        $grade_data['total'] > 0 ||
        $assignment_data['total'] > 0
    ) {

        $message = "This semester cannot be deleted because it is already being used by the system.";
        $message_type = "warning";

    } else {

        mysqli_query($conn, "
            DELETE FROM semesters
            WHERE semester_id = '$semester_id'
        ");

        $message = "Semester deleted successfully.";
        $message_type = "success";
    }
}


/* =========================
   ADD SEMESTER
========================= */

if (isset($_POST['add_semester'])) {

    $semester_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['semester_name'])
    );

    $academic_year = mysqli_real_escape_string(
        $conn,
        trim($_POST['academic_year'])
    );

    if ($semester_name == "" || $academic_year == "") {

        $message = "Please fill in all fields.";
        $message_type = "danger";

    } else {

        // Check duplicate semester
        $check = mysqli_query($conn, "
            SELECT semester_id
            FROM semesters
            WHERE semester_name = '$semester_name'
            AND academic_year = '$academic_year'
        ");

        if (mysqli_num_rows($check) > 0) {

            $message = "This semester already exists for the selected academic year.";
            $message_type = "warning";

        } else {

            $insert = mysqli_query($conn, "
                INSERT INTO semesters
                (
                    semester_name,
                    academic_year
                )
                VALUES
                (
                    '$semester_name',
                    '$academic_year'
                )
            ");

            if ($insert) {

                $message = "Semester added successfully.";
                $message_type = "success";

            } else {

                $message = "Unable to add semester.";
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
   GET SEMESTERS
========================= */

$semesters = mysqli_query($conn, "
    SELECT *
    FROM semesters
    WHERE semester_name LIKE '%$search%'
       OR academic_year LIKE '%$search%'
    ORDER BY semester_id DESC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Semester Management | RVU Academic System</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .page-header {
            background: linear-gradient(
                135deg,
                #212529,
                #343a40
            );

            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.10);
        }

        .management-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .card-header {
            border-radius: 15px 15px 0 0 !important;
        }

        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
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

        .table {
            vertical-align: middle;
        }

        .table thead th {
            white-space: nowrap;
        }

        .btn {
            border-radius: 8px;
        }

        .badge-semester {
            font-size: 13px;
            padding: 8px 12px;
        }

    </style>

</head>


<body>


<div class="container-fluid px-4 py-4">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap">

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-calendar3"></i>

                    Semester Management

                </h2>

                <p class="mb-0 text-white-50">

                    Manage academic semesters and academic years

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



    <!-- ALERT -->

    <?php if ($message != "") { ?>

        <div
            class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show shadow-sm">

            <?php echo $message; ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php } ?>



    <!-- STATISTICS -->

    <?php

    $semester_count_result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM semesters"
    );

    $semester_count = mysqli_fetch_assoc(
        $semester_count_result
    )['total'];

    ?>


    <div class="row g-4 mb-4">


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="stat-icon bg-primary text-white">

                            <i class="bi bi-calendar-check"></i>

                        </div>

                        <div class="ms-3">

                            <h6 class="text-muted mb-1">

                                Total Semesters

                            </h6>

                            <h3 class="mb-0">

                                <?php echo $semester_count; ?>

                            </h3>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="stat-icon bg-success text-white">

                            <i class="bi bi-mortarboard"></i>

                        </div>

                        <div class="ms-3">

                            <h6 class="text-muted mb-1">

                                Academic System

                            </h6>

                            <h5 class="mb-0">

                                Active

                            </h5>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="stat-icon bg-warning text-dark">

                            <i class="bi bi-clock-history"></i>

                        </div>

                        <div class="ms-3">

                            <h6 class="text-muted mb-1">

                                Current Date

                            </h6>

                            <h6 class="mb-0">

                                <?php echo date("d M Y"); ?>

                            </h6>

                        </div>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <div class="row g-4">


        <!-- ADD SEMESTER -->

        <div class="col-lg-4">

            <div class="card management-card">

                <div class="card-header bg-primary text-white py-3">

                    <h5 class="mb-0">

                        <i class="bi bi-plus-circle"></i>

                        Add New Semester

                    </h5>

                </div>


                <div class="card-body p-4">


                    <form method="POST">


                        <div class="mb-3">

                            <label class="form-label fw-semibold">

                                Semester Name

                            </label>

                            <select
                                name="semester_name"
                                class="form-select"
                                required>

                                <option value="">

                                    Select Semester

                                </option>

                                <option value="Semester I">

                                    Semester I

                                </option>

                                <option value="Semester II">

                                    Semester II

                                </option>

                                <option value="Summer">

                                    Summer

                                </option>

                            </select>

                        </div>



                        <div class="mb-4">

                            <label class="form-label fw-semibold">

                                Academic Year

                            </label>

                            <input
                                type="text"
                                name="academic_year"
                                class="form-control"
                                placeholder="2026/27"
                                required>

                            <small class="text-muted">

                                Example: 2026/27

                            </small>

                        </div>


                        <button
                            type="submit"
                            name="add_semester"
                            class="btn btn-primary w-100">

                            <i class="bi bi-plus-lg"></i>

                            Add Semester

                        </button>


                    </form>


                </div>

            </div>

        </div>



        <!-- SEMESTER LIST -->

        <div class="col-lg-8">

            <div class="card management-card">

                <div class="card-header bg-dark text-white py-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <h5 class="mb-0">

                            <i class="bi bi-list-ul"></i>

                            Semester List

                        </h5>

                        <span class="badge bg-primary">

                            <?php echo $semester_count; ?>

                        </span>

                    </div>

                </div>


                <div class="card-body">


                    <!-- SEARCH -->

                    <form method="GET" class="mb-4">

                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="bi bi-search"></i>

                            </span>

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search semester or academic year..."
                                value="<?php echo htmlspecialchars($search); ?>">

                            <button
                                class="btn btn-primary">

                                Search

                            </button>

                            <a
                                href="index.php"
                                class="btn btn-outline-secondary">

                                Reset

                            </a>

                        </div>

                    </form>



                    <div class="table-responsive">

                        <table class="table table-hover">

                            <thead class="table-dark">

                                <tr>

                                    <th>#</th>

                                    <th>Semester</th>

                                    <th>Academic Year</th>

                                    <th>Status</th>

                                    <th class="text-center">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php

                            if (mysqli_num_rows($semesters) > 0) {

                                $number = 1;

                                while (
                                    $row =
                                    mysqli_fetch_assoc($semesters)
                                ) {

                            ?>

                                <tr>

                                    <td>

                                        <span class="text-muted">

                                            <?php echo $number++; ?>

                                        </span>

                                    </td>


                                    <td>

                                        <strong>

                                            <i class="bi bi-calendar-event text-primary"></i>

                                            <?php
                                            echo htmlspecialchars(
                                                $row['semester_name']
                                            );
                                            ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <span class="badge bg-secondary badge-semester">

                                            <?php
                                            echo htmlspecialchars(
                                                $row['academic_year']
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="badge bg-success">

                                            <i class="bi bi-check-circle"></i>

                                            Active

                                        </span>

                                    </td>


                                    <td class="text-center">

                                        <a
                                            href="edit.php?id=<?php echo $row['semester_id']; ?>"
                                            class="btn btn-warning btn-sm">

                                            <i class="bi bi-pencil"></i>

                                            Edit

                                        </a>


                                        <a
                                            href="index.php?delete=<?php echo $row['semester_id']; ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Are you sure you want to delete this semester?');">

                                            <i class="bi bi-trash"></i>

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
                                            class="bi bi-calendar-x fs-1 text-muted">
                                        </i>

                                        <h5 class="mt-3">

                                            No semesters found

                                        </h5>

                                        <p class="text-muted">

                                            Add a new semester to get started.

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


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>