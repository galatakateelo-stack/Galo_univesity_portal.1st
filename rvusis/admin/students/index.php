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
$department_filter = "";

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

if (isset($_GET['department'])) {
    $department_filter = mysqli_real_escape_string($conn, $_GET['department']);
}

/* =========================
   DEPARTMENTS
========================= */

$departments = mysqli_query($conn, "
    SELECT department_id, department_name
    FROM departments
    ORDER BY department_name ASC
");

/* =========================
   STUDENT QUERY
========================= */

$sql = "
SELECT
    students.student_id,
    students.registration_no,
    students.first_name,
    students.last_name,
    students.gender,
    students.department_id,
    departments.department_name
FROM students
INNER JOIN departments
    ON students.department_id = departments.department_id
WHERE
(
    students.first_name LIKE '%$search%'
    OR students.last_name LIKE '%$search%'
    OR students.registration_no LIKE '%$search%'
)
";

/* Department filter */

if ($department_filter != "") {
    $sql .= " AND students.department_id = '$department_filter'";
}

$sql .= "
ORDER BY students.student_id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

/* =========================
   TOTAL STUDENTS
========================= */

$count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM students");
$count_data = mysqli_fetch_assoc($count_result);
$total_students = $count_data['total'];

/* =========================
   FILTERED COUNT
========================= */

$displayed_students = mysqli_num_rows($result);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Management | Admin</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

body {
    background: #f5f7fb;
    font-family: Arial, Helvetica, sans-serif;
}

/* Main container */

.page-container {
    max-width: 1400px;
    margin: 40px auto;
    padding: 0 20px;
}

/* Header */

.page-header {
    background: linear-gradient(135deg, #0d6efd, #084298);
    color: white;
    border-radius: 15px;
    padding: 25px 30px;
    margin-bottom: 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.10);
}

.page-header h2 {
    margin: 0;
    font-weight: 700;
}

.page-header p {
    margin-top: 5px;
    opacity: 0.9;
}

/* Statistics */

.stat-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
    transition: 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
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

/* Main card */

.main-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-title {
    font-weight: 700;
}

/* Search */

.search-box {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
}

/* Table */

.table {
    vertical-align: middle;
}

.table thead th {
    white-space: nowrap;
    font-size: 14px;
}

.table tbody tr {
    transition: 0.15s;
}

.table tbody tr:hover {
    background-color: #f3f7ff;
}

.student-name {
    font-weight: 600;
}

.registration {
    font-weight: 600;
    color: #0d6efd;
}

.action-buttons .btn {
    margin: 2px;
}

/* Empty */

.empty-state {
    padding: 50px 20px;
    text-align: center;
}

.empty-state i {
    font-size: 50px;
    color: #adb5bd;
}

</style>

</head>

<body>

<div class="page-container">

    <!-- =========================
         HEADER
    ========================== -->

    <div class="page-header d-flex justify-content-between align-items-center">

        <div>

            <h2>
                <i class="bi bi-people-fill"></i>
                Student Management
            </h2>

            <p class="mb-0">
                Manage registered students and their academic information
            </p>

        </div>

        <div>

            <a href="../dashboard.php"
               class="btn btn-light">

                <i class="bi bi-arrow-left"></i>
                Dashboard

            </a>

        </div>

    </div>


    <!-- =========================
         STATISTICS
    ========================== -->

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body d-flex align-items-center">

                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">

                        <i class="bi bi-people-fill"></i>

                    </div>

                    <div>

                        <small class="text-muted">
                            Total Students
                        </small>

                        <h3 class="mb-0 fw-bold">
                            <?php echo $total_students; ?>
                        </h3>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body d-flex align-items-center">

                    <div class="stat-icon bg-success bg-opacity-10 text-success me-3">

                        <i class="bi bi-search"></i>

                    </div>

                    <div>

                        <small class="text-muted">
                            Displayed Students
                        </small>

                        <h3 class="mb-0 fw-bold">
                            <?php echo $displayed_students; ?>
                        </h3>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body d-flex align-items-center">

                    <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">

                        <i class="bi bi-person-plus-fill"></i>

                    </div>

                    <div>

                        <small class="text-muted">
                            Student Registration
                        </small>

                        <div class="mt-1">

                            <a href="add.php"
                               class="btn btn-success btn-sm">

                                <i class="bi bi-plus-circle"></i>
                                Add Student

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================
         MAIN CARD
    ========================== -->

    <div class="card main-card">

        <div class="card-body p-4">

            <!-- Search -->

            <div class="search-box mb-4">

                <form method="GET">

                    <div class="row g-2">

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Search Student
                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>

                                <input
                                    type="text"
                                    name="search"
                                    class="form-control"
                                    placeholder="Name or registration number"
                                    value="<?php echo htmlspecialchars($search); ?>">

                            </div>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label fw-semibold">
                                Department
                            </label>

                            <select
                                name="department"
                                class="form-select">

                                <option value="">
                                    All Departments
                                </option>

                                <?php while ($department = mysqli_fetch_assoc($departments)) { ?>

                                    <option
                                        value="<?php echo $department['department_id']; ?>"
                                        <?php
                                        if ($department_filter == $department['department_id']) {
                                            echo "selected";
                                        }
                                        ?>>

                                        <?php echo htmlspecialchars($department['department_name']); ?>

                                    </option>

                                <?php } ?>

                            </select>

                        </div>


                        <div class="col-md-2 d-flex align-items-end">

                            <div class="w-100">

                                <button
                                    type="submit"
                                    class="btn btn-primary w-100 mb-1">

                                    <i class="bi bi-search"></i>
                                    Search

                                </button>

                                <a
                                    href="index.php"
                                    class="btn btn-outline-secondary w-100">

                                    Reset

                                </a>

                            </div>

                        </div>

                    </div>

                </form>

            </div>


            <!-- Table title -->

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h4 class="card-title mb-1">
                        Registered Students
                    </h4>

                    <small class="text-muted">
                        Student records in the system
                    </small>

                </div>

                <span class="badge bg-primary rounded-pill px-3 py-2">

                    <?php echo $displayed_students; ?> Students

                </span>

            </div>


            <!-- =========================
                 STUDENT TABLE
            ========================== -->

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>#</th>

                            <th>Registration No.</th>

                            <th>Student Name</th>

                            <th>Department</th>

                            <th>Gender</th>

                            <th class="text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php

                    if ($displayed_students > 0) {

                        $number = 1;

                        while ($row = mysqli_fetch_assoc($result)) {

                    ?>

                        <tr>

                            <td>
                                <?php echo $number++; ?>
                            </td>

                            <td>

                                <span class="registration">

                                    <?php
                                    echo htmlspecialchars(
                                        $row['registration_no']
                                    );
                                    ?>

                                </span>

                            </td>

                            <td>

                                <div class="student-name">

                                    <i class="bi bi-person-circle text-primary me-1"></i>

                                    <?php

                                    echo htmlspecialchars(
                                        $row['first_name']
                                    )
                                    . " "
                                    .
                                    htmlspecialchars(
                                        $row['last_name']
                                    );

                                    ?>

                                </div>

                            </td>

                            <td>

                                <span class="badge bg-light text-dark border">

                                    <?php
                                    echo htmlspecialchars(
                                        $row['department_name']
                                    );
                                    ?>

                                </span>

                            </td>

                            <td>

                                <?php

                                if ($row['gender'] == "Male") {

                                    echo '<span class="badge bg-primary">
                                            <i class="bi bi-gender-male"></i>
                                            Male
                                          </span>';

                                } elseif ($row['gender'] == "Female") {

                                    echo '<span class="badge bg-danger">
                                            <i class="bi bi-gender-female"></i>
                                            Female
                                          </span>';

                                } else {

                                    echo '<span class="badge bg-secondary">'
                                        . htmlspecialchars($row['gender'])
                                        . '</span>';

                                }

                                ?>

                            </td>

                            <td class="text-center action-buttons">
<a
    href="view.php?id=<?php echo $row['student_id']; ?>"
    class="btn btn-primary btn-sm"
>
    <i class="bi bi-eye"></i>
    View
</a>

<a
    href="edit.php?id=<?php echo $row['student_id']; ?>"
    class="btn btn-warning btn-sm"
>
    <i class="bi bi-pencil-square"></i>
    Edit
</a>

                                <a
                                    href="edit.php?id=<?php echo $row['student_id']; ?>"
                                    class="btn btn-warning btn-sm"
                                    title="Edit Student">

                                    <i class="bi bi-pencil-square"></i>

                                </a>


                                <!-- Delete -->

                                <a
                                    href="delete.php?id=<?php echo $row['student_id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    title="Delete Student"
                                    onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.');">

                                    <i class="bi bi-trash"></i>

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

                                    <i class="bi bi-person-x"></i>

                                    <h5 class="mt-3">
                                        No Students Found
                                    </h5>

                                    <p class="text-muted">

                                        Try another search or reset the filters.

                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- Footer -->

    <div class="text-center text-muted mt-4">

        <small>

            Student Information System
            &copy;
            <?php echo date("Y"); ?>

        </small>

    </div>

</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>