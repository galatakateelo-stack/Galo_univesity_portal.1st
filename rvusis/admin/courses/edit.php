<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

$message = "";
$course_id = 0;

/* -------------------------------------------------------
   Get Course ID
------------------------------------------------------- */
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $course_id = (int) $_GET['id'];
} else {
    header("Location: index.php");
    exit();
}

/* -------------------------------------------------------
   Update Course
------------------------------------------------------- */
if (isset($_POST['update_course'])) {

    $course_code  = trim($_POST['course_code']);
    $course_name  = trim($_POST['course_name']);
    $credit_hours = trim($_POST['credit_hours']);
    $department_id = (int) $_POST['department_id'];

    /* Basic validation */
    if (
        empty($course_code) ||
        empty($course_name) ||
        empty($credit_hours) ||
        $department_id <= 0
    ) {

        $message = '
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Please fill in all required fields.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';

    } elseif (!is_numeric($credit_hours) || $credit_hours <= 0) {

        $message = '
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Credit hours must be a valid positive number.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';

    } else {

        /* Check duplicate course code */
        $check = mysqli_prepare(
            $conn,
            "SELECT course_id
             FROM courses
             WHERE course_code = ?
             AND course_id != ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "si",
            $course_code,
            $course_id
        );

        mysqli_stmt_execute($check);

        $check_result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($check_result) > 0) {

            $message = '
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="bi bi-exclamation-circle-fill"></i>
                This course code already exists.
                Please use another course code.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';

        } else {

            /* Update course */
            $update = mysqli_prepare(
                $conn,
                "UPDATE courses
                 SET course_code = ?,
                     course_name = ?,
                     credit_hours = ?,
                     department_id = ?
                 WHERE course_id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "ssdii",
                $course_code,
                $course_name,
                $credit_hours,
                $department_id,
                $course_id
            );

            if (mysqli_stmt_execute($update)) {

                $message = '
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill"></i>
                    <strong>Success!</strong>
                    Course information has been updated successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';

            } else {

                $message = '
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-x-circle-fill"></i>
                    Failed to update the course.
                    Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
        }
    }
}

/* -------------------------------------------------------
   Get Current Course Information
------------------------------------------------------- */
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
WHERE courses.course_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $course_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit();
}

$course = mysqli_fetch_assoc($result);

/* -------------------------------------------------------
   Get Departments
------------------------------------------------------- */
$departments = mysqli_query(
    $conn,
    "SELECT department_id, department_name
     FROM departments
     ORDER BY department_name ASC"
);
?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Course | University ERP</title>

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
        background: #f4f7fb;
    }

    .page-header {
        background: linear-gradient(135deg, #0d6efd, #084298);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 25px;
    }

    .form-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }

    .form-label {
        font-weight: 600;
    }

    .required {
        color: #dc3545;
    }

    .btn {
        border-radius: 8px;
    }

    .course-icon {
        width: 55px;
        height: 55px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(255,255,255,0.18);
        font-size: 25px;
        margin-right: 15px;
    }

</style>

</head>

<body>

<div class="container py-5">

<!-- Page Header -->
<div class="page-header">

    <div class="d-flex align-items-center">

        <div class="course-icon">
            <i class="bi bi-pencil-square"></i>
        </div>

        <div>

            <h2 class="mb-1">
                Edit Course
            </h2>

            <p class="mb-0 opacity-75">
                Update course information and academic details
            </p>

        </div>

    </div>

</div>


<!-- Messages -->
<?php echo $message; ?>


<!-- Form Card -->
<div class="card form-card">

    <div class="card-body p-4 p-md-5">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h4 class="mb-1">
                    <i class="bi bi-book text-primary"></i>
                    Course Information
                </h4>

                <small class="text-muted">
                    Update the information below.
                </small>

            </div>

            <span class="badge bg-primary">
                Course ID: <?php echo $course['course_id']; ?>
            </span>

        </div>


        <form method="POST">

            <div class="row g-4">

                <!-- Course Code -->
                <div class="col-md-6">

                    <label class="form-label">
                        Course Code
                        <span class="required">*</span>
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">
                            <i class="bi bi-hash"></i>
                        </span>

                        <input
                            type="text"
                            name="course_code"
                            class="form-control"
                            value="<?php echo htmlspecialchars($course['course_code']); ?>"
                            placeholder="Example: CS101"
                            maxlength="30"
                            required>

                    </div>

                    <small class="text-muted">
                        Enter the official course code.
                    </small>

                </div>


                <!-- Course Name -->
                <div class="col-md-6">

                    <label class="form-label">
                        Course Name
                        <span class="required">*</span>
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">
                            <i class="bi bi-book"></i>
                        </span>

                        <input
                            type="text"
                            name="course_name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($course['course_name']); ?>"
                            placeholder="Example: Database Systems"
                            maxlength="100"
                            required>

                    </div>

                    <small class="text-muted">
                        Enter the full course name.
                    </small>

                </div>


                <!-- Credit Hours -->
                <div class="col-md-6">

                    <label class="form-label">
                        Credit Hours
                        <span class="required">*</span>
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">
                            <i class="bi bi-clock"></i>
                        </span>

                        <input
                            type="number"
                            name="credit_hours"
                            class="form-control"
                            value="<?php echo htmlspecialchars($course['credit_hours']); ?>"
                            min="0.5"
                            max="20"
                            step="0.5"
                            required>

                    </div>

                    <small class="text-muted">
                        Example: 3 credit hours.
                    </small>

                </div>


                <!-- Department -->
                <div class="col-md-6">

                    <label class="form-label">
                        Department
                        <span class="required">*</span>
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">
                            <i class="bi bi-building"></i>
                        </span>

                        <select
                            name="department_id"
                            class="form-select"
                            required>

                            <option value="">
                                Select Department
                            </option>

                            <?php
                            while ($department = mysqli_fetch_assoc($departments)) {
                            ?>

                                <option
                                    value="<?php echo $department['department_id']; ?>"
                                    <?php
                                    if (
                                        $department['department_id']
                                        == $course['department_id']
                                    ) {
                                        echo "selected";
                                    }
                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $department['department_name']
                                    );
                                    ?>

                                </option>

                            <?php
                            }
                            ?>

                        </select>

                    </div>

                    <small class="text-muted">
                        Select the department offering this course.
                    </small>

                </div>

            </div>


            <!-- Divider -->
            <hr class="my-5">


            <!-- Buttons -->
            <div class="d-flex justify-content-between flex-wrap gap-2">

                <a
                    href="index.php"
                    class="btn btn-outline-secondary px-4">

                    <i class="bi bi-arrow-left"></i>
                    Back to Courses

                </a>


                <button
                    type="submit"
                    name="update_course"
                    class="btn btn-primary px-4">

                    <i class="bi bi-check-circle"></i>
                    Update Course

                </button>

            </div>

        </form>

    </div>

</div>


<!-- Footer Info -->
<div class="text-center mt-4 text-muted">

    <small>
        <i class="bi bi-shield-check"></i>
        University ERP • Course Management
    </small>

</div>

</div>

<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>
