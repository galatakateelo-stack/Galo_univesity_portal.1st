<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

/* ==============================
   SEARCH
============================== */

$search = "";

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

/* ==============================
   INSTRUCTOR LIST
============================== */

$sql = "
SELECT
    instructors.instructor_id,
    instructors.full_name,
    instructors.email,
    instructors.phone,
    instructors.username,
    departments.department_name

FROM instructors

INNER JOIN departments
ON instructors.department_id = departments.department_id

WHERE
    instructors.full_name LIKE '%$search%'
    OR instructors.email LIKE '%$search%'
    OR instructors.phone LIKE '%$search%'
    OR instructors.username LIKE '%$search%'

ORDER BY instructors.instructor_id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

/* ==============================
   TOTAL INSTRUCTORS
============================== */

$count_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM instructors"
);

$count_data = mysqli_fetch_assoc($count_result);
$total_instructors = $count_data['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Instructor Management</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        body {
            background: #f4f7fb;
        }

        .page-header {
            background: linear-gradient(
                135deg,
                #198754,
                #0d6efd
            );
            color: white;
            border-radius: 18px;
            padding: 30px;
            margin-bottom: 25px;
        }

        .stat-card {
            border: none;
            border-radius: 16px;
            color: white;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .main-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }

        .table thead th {
            background: #172033;
            color: white;
            border: none;
            padding: 15px;
        }

        .table tbody td {
            padding: 14px;
            vertical-align: middle;
        }

        .btn {
            border-radius: 9px;
        }

        .search-box {
            border-radius: 10px;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #198754;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

    </style>

</head>

<body>

<div class="container-fluid px-4 py-4">

    <!-- HEADER -->

    <div class="page-header">

        <div class="d-flex justify-content-between
                    align-items-center flex-wrap">

            <div>

                <h2 class="fw-bold mb-2">

                    <i class="bi bi-person-workspace"></i>

                    Instructor Management

                </h2>

                <p class="mb-0 opacity-75">

                    Manage instructors, accounts and departments.

                </p>

            </div>

            <div class="mt-3 mt-md-0">

                <a href="../dashboard.php"
                   class="btn btn-light me-2">

                    <i class="bi bi-speedometer2"></i>

                    Dashboard

                </a>

                <a href="add.php"
                   class="btn btn-warning">

                    <i class="bi bi-person-plus"></i>

                    Add Instructor

                </a>

            </div>

        </div>

    </div>


    <!-- STATISTICS -->

    <div class="row g-4 mb-4">

        <div class="col-md-4">

            <div class="card stat-card bg-primary">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <small>Total Instructors</small>

                            <h2 class="fw-bold mb-0">

                                <?php echo $total_instructors; ?>

                            </h2>

                        </div>

                        <i class="bi bi-people-fill fs-1"></i>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card bg-success">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <small>Management</small>

                            <h5 class="fw-bold mb-0">

                                Instructor Accounts

                            </h5>

                        </div>

                        <i class="bi bi-person-badge fs-1"></i>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card bg-dark">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <small>System</small>

                            <h5 class="fw-bold mb-0">

                                Academic Staff

                            </h5>

                        </div>

                        <i class="bi bi-mortarboard-fill fs-1"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- MAIN CARD -->

    <div class="card main-card">

        <div class="card-body p-4">


            <!-- SEARCH -->

            <div class="row align-items-center mb-4">

                <div class="col-lg-7 mb-3 mb-lg-0">

                    <form method="GET">

                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="bi bi-search"></i>

                            </span>

                            <input
                                type="text"
                                name="search"
                                class="form-control search-box"
                                placeholder="Search by name, email, phone or username..."
                                value="<?php echo htmlspecialchars($search); ?>">

                            <button
                                type="submit"
                                class="btn btn-primary">

                                Search

                            </button>

                            <?php if ($search != "") { ?>

                                <a href="index.php"
                                   class="btn btn-secondary">

                                    Reset

                                </a>

                            <?php } ?>

                        </div>

                    </form>

                </div>

                <div class="col-lg-5 text-lg-end">

                    <span class="badge bg-light text-dark p-2">

                        <?php echo $total_instructors; ?>

                        Total Instructors

                    </span>

                </div>

            </div>


            <!-- TABLE -->

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                    <tr>

                        <th>#</th>

                        <th>Instructor</th>

                        <th>Email</th>

                        <th>Phone</th>

                        <th>Department</th>

                        <th>Username</th>

                        <th class="text-center">
                            Actions
                        </th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php

                    if (mysqli_num_rows($result) > 0) {

                        $number = 1;

                        while ($row = mysqli_fetch_assoc($result)) {

                            $name = htmlspecialchars(
                                $row['full_name']
                            );

                            $initial = strtoupper(
                                substr(
                                    $row['full_name'],
                                    0,
                                    1
                                )
                            );

                    ?>

                    <tr>

                        <td>

                            <?php echo $number++; ?>

                        </td>

                        <td>

                            <div class="d-flex align-items-center">

                                <span class="avatar">

                                    <?php echo $initial; ?>

                                </span>

                                <strong>

                                    <?php echo $name; ?>

                                </strong>

                            </div>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['email'] ?? ''
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['phone'] ?? ''
                            );

                            ?>

                        </td>

                        <td>

                            <span class="badge bg-info text-dark">

                                <?php

                                echo htmlspecialchars(
                                    $row['department_name']
                                );

                                ?>

                            </span>

                        </td>

                        <td>

                            <code>

                                <?php

                                echo htmlspecialchars(
                                    $row['username']
                                );

                                ?>

                            </code>

                        </td>

                        <td class="text-center">

                            <a
                                href="view.php?id=<?php echo $row['instructor_id']; ?>"
                                class="btn btn-outline-primary btn-sm">

                                <i class="bi bi-eye"></i>

                                View

                            </a>

                            <a
                                href="edit.php?id=<?php echo $row['instructor_id']; ?>"
                                class="btn btn-outline-warning btn-sm">

                                <i class="bi bi-pencil"></i>

                                Edit

                            </a>

                            <a
                                href="delete.php?id=<?php echo $row['instructor_id']; ?>"
                                class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('Are you sure you want to delete this instructor?');">

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

                        <td colspan="7"
                            class="text-center py-5">

                            <i class="bi bi-person-x fs-1
                                      text-secondary"></i>

                            <h5 class="mt-3">

                                No instructors found

                            </h5>

                            <p class="text-muted">

                                Try another search or add a new instructor.

                            </p>

                        </td>

                    </tr>

                    <?php

                    }

                    ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>

</html>