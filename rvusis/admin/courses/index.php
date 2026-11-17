<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

/* =========================
   SEARCH & FILTER
========================= */

$search = "";
$department_id = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if (isset($_GET['department_id'])) {
    $department_id = $_GET['department_id'];
}

$search_safe = mysqli_real_escape_string($conn, $search);

/* =========================
   DEPARTMENTS
========================= */

$department_result = mysqli_query($conn, "
    SELECT department_id, department_name
    FROM departments
    ORDER BY department_name ASC
");

/* =========================
   COURSE QUERY
========================= */

$sql = "
    SELECT 
        courses.course_id,
        courses.course_code,
        courses.course_name,
        courses.credit_hours,
        courses.department_id,
        departments.department_name
    FROM courses
    INNER JOIN departments
        ON courses.department_id = departments.department_id
    WHERE
        (
            courses.course_code LIKE '%$search_safe%'
            OR courses.course_name LIKE '%$search_safe%'
        )
";

if ($department_id !== "") {
    $department_safe = mysqli_real_escape_string($conn, $department_id);
    $sql .= " AND courses.department_id = '$department_safe'";
}

$sql .= " ORDER BY courses.course_id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

/* =========================
   TOTAL COURSES
========================= */

$count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM courses");
$count_data = mysqli_fetch_assoc($count_result);
$total_courses = $count_data['total'];

?>

<!DOCTYPE html>

<html lang="en">

<head>
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Course Management | Administrator</title>

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
        background: #f5f7fb;
        font-family: Arial, sans-serif;
    }

    .page-header {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.10);
    }

    .page-header h2 {
        font-weight: 700;
        margin-bottom: 5px;
    }

    .page-header p {
        margin-bottom: 0;
        opacity: 0.9;
    }

    .stat-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    }

    .stat-icon {
        width: 55px;
        height: 55px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 25px;
        background: rgba(255,255,255,0.20);
    }

    .main-card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 6px 25px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .card-title {
        font-weight: 700;
    }

    .table thead th {
        background: #172033;
        color: white;
        padding: 15px;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 15px;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .course-code {
        font-weight: 700;
        color: #2563eb;
    }

    .department-badge {
        background: #eef2ff;
        color: #4338ca;
        padding: 7px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .credit-badge {
        background: #ecfdf5;
        color: #047857;
        padding: 7px 12px;
        border-radius: 20px;
        font-weight: 600;
    }

    .action-btn {
        border-radius: 8px;
        padding: 6px 11px;
    }

    .empty-state {
        padding: 50px 20px;
        text-align: center;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 50px;
        color: #9ca3af;
        margin-bottom: 15px;
    }

    .filter-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.06);
    }

    .form-control,
    .form-select {
        border-radius: 10px;
        padding: 11px 14px;
    }

    .btn {
        border-radius: 9px;
    }

</style>

</head>

<body>

<div class="container-fluid px-4 py-4">
<!-- PAGE HEADER -->

<div class="page-header">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

        <div>

            <h2>
                <i class="bi bi-book-half me-2"></i>
                Course Management
            </h2>

            <p>
                Manage university courses, credit hours and departments.
            </p>

        </div>

        <div>

            <a href="../dashboard.php"
               class="btn btn-light">

                <i class="bi bi-arrow-left me-1"></i>
                Dashboard

            </a>

        </div>

    </div>

</div>


<!-- STATISTICS -->

<div class="row mb-4">

    <div class="col-md-4">

        <div class="card stat-card bg-primary text-white">

            <div class="card-body d-flex justify-content-between align-items-center">

                <div>

                    <small>Total Courses</small>

                    <h2 class="fw-bold mb-0">
                        <?php echo $total_courses; ?>
                    </h2>

                </div>

                <div class="stat-icon">

                    <i class="bi bi-bookshelf"></i>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- SEARCH & FILTER -->

<div class="card filter-card mb-4">

    <div class="card-body">

        <div class="d-flex align-items-center mb-3">

            <i class="bi bi-funnel-fill text-primary fs-5 me-2"></i>

            <h5 class="mb-0 fw-bold">
                Search & Filter Courses
            </h5>

        </div>

        <form method="GET">

            <div class="row g-3">

                <div class="col-md-5">

                    <label class="form-label fw-semibold">
                        Search Course
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Course code or course name..."
                        value="<?php echo htmlspecialchars($search); ?>">

                </div>


                <div class="col-md-4">

                    <label class="form-label fw-semibold">
                        Department
                    </label>

                    <select
                        name="department_id"
                        class="form-select">

                        <option value="">
                            All Departments
                        </option>

                        <?php while ($department = mysqli_fetch_assoc($department_result)) { ?>

                            <option
                                value="<?php echo $department['department_id']; ?>"
                                <?php
                                if ($department_id == $department['department_id']) {
                                    echo "selected";
                                }
                                ?>>

                                <?php echo htmlspecialchars($department['department_name']); ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>


                <div class="col-md-3 d-flex align-items-end gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary flex-fill">

                        <i class="bi bi-search me-1"></i>
                        Search

                    </button>

                    <a
                        href="index.php"
                        class="btn btn-outline-secondary">

                        <i class="bi bi-arrow-clockwise"></i>

                    </a>

                </div>

            </div>

        </form>

    </div>

</div>


<!-- COURSE TABLE -->

<div class="card main-card">

    <div class="card-body p-0">

        <div class="p-4 border-bottom">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

                <div>

                    <h4 class="card-title mb-1">

                        <i class="bi bi-list-ul text-primary me-2"></i>
                        Course List

                    </h4>

                    <small class="text-muted">
                        Manage all registered university courses.
                    </small>

                </div>


                <a
                    href="add.php"
                    class="btn btn-success">

                    <i class="bi bi-plus-circle me-1"></i>
                    Add New Course

                </a>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Course Code</th>

                        <th>Course Name</th>

                        <th>Credit Hours</th>

                        <th>Department</th>

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

                ?>

                    <tr>

                        <td>
                            <?php echo $number++; ?>
                        </td>


                        <td>

                            <span class="course-code">

                                <?php
                                echo htmlspecialchars($row['course_code']);
                                ?>

                            </span>

                        </td>


                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars($row['course_name']);
                                ?>
                            </strong>

                        </td>


                        <td>

                            <span class="credit-badge">

                                <i class="bi bi-clock me-1"></i>

                                <?php
                                echo htmlspecialchars($row['credit_hours']);
                                ?>

                                Credits

                            </span>

                        </td>


                        <td>

                            <span class="department-badge">

                                <i class="bi bi-building me-1"></i>

                                <?php
                                echo htmlspecialchars($row['department_name']);
                                ?>

                            </span>

                        </td>


                        <td class="text-center">

                            <a
                                href="edit.php?id=<?php echo $row['course_id']; ?>"
                                class="btn btn-warning btn-sm action-btn me-1">

                                <i class="bi bi-pencil-square"></i>
                                Edit

                            </a>


                            <a
                                href="delete.php?id=<?php echo $row['course_id']; ?>"
                                class="btn btn-danger btn-sm action-btn"
                                onclick="return confirm('Are you sure you want to delete this course?');">

                                <i class="bi bi-trash3"></i>
                                Delete

                            </a>

                        </td>

                    </tr>

                <?php

                    }

                } else {

                ?>

                    <tr>

                        <td colspan="6">

                            <div class="empty-state">

                                <i class="bi bi-book"></i>

                                <h5>
                                    No Courses Found
                                </h5>

                                <p>
                                    Try another search or add a new course.
                                </p>

                                <a
                                    href="add.php"
                                    class="btn btn-success">

                                    <i class="bi bi-plus-circle me-1"></i>
                                    Add Course

                                </a>

                            </div>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<!-- FOOTER -->

<div class="text-center text-muted mt-4">

    <small>
        University Academic Management System
        &copy; <?php echo date("Y"); ?>
    </small>

</div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>
