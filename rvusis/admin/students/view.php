<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

/* Get student ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$student_id = intval($_GET['id']);

/* Get student information */
$sql = "
SELECT 
    students.*,
    departments.department_name
FROM students
LEFT JOIN departments
    ON students.department_id = departments.department_id
WHERE students.student_id = ?
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    header("Location: index.php");
    exit();
}

/* Get student's grades */
$grade_sql = "
SELECT
    courses.course_code,
    courses.course_name,
    courses.credit_hours,
    semesters.semester_name,
    semesters.academic_year,
    grades.grade,
    grades.grade_point
FROM grades
INNER JOIN courses
    ON grades.course_id = courses.course_id
INNER JOIN semesters
    ON grades.semester_id = semesters.semester_id
WHERE grades.student_id = ?
ORDER BY grades.grade_id DESC
";

$grade_stmt = mysqli_prepare($conn, $grade_sql);
mysqli_stmt_bind_param($grade_stmt, "i", $student_id);
mysqli_stmt_execute($grade_stmt);

$grades = mysqli_stmt_get_result($grade_stmt);

/* Calculate GPA */
$total_points = 0;
$total_credits = 0;

$grade_rows = [];

while ($grade = mysqli_fetch_assoc($grades)) {

    $credit = (float)$grade['credit_hours'];
    $point = (float)$grade['grade_point'];

    $total_credits += $credit;
    $total_points += ($credit * $point);

    $grade_rows[] = $grade;
}

$gpa = ($total_credits > 0)
    ? $total_points / $total_credits
    : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>
Student Profile - <?php echo htmlspecialchars($student['first_name']); ?>
</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

body {
    background: #f4f6f9;
}

.profile-header {
    background: linear-gradient(135deg, #0d6efd, #0b5ed7);
    color: white;
    border-radius: 15px;
    padding: 30px;
}

.profile-icon {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 45px;
}

.info-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.info-label {
    color: #6c757d;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
}

.info-value {
    font-size: 17px;
    font-weight: 600;
}

.stat-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.gpa-number {
    font-size: 32px;
    font-weight: bold;
}

.table {
    vertical-align: middle;
}

@media print {

    body * {
        visibility: hidden;
    }

    #printArea,
    #printArea * {
        visibility: visible;
    }

    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .no-print {
        display: none !important;
    }
}

</style>

</head>

<body>

<div class="container py-5" id="printArea">

    <!-- Header Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">

        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            Back to Students
        </a>

        <div>

            <a
            href="edit.php?id=<?php echo $student_id; ?>"
            class="btn btn-warning">

                <i class="bi bi-pencil-square"></i>
                Edit Student

            </a>

            <button
            onclick="window.print()"
            class="btn btn-dark">

                <i class="bi bi-printer"></i>
                Print Profile

            </button>

        </div>

    </div>


    <!-- Student Header -->

    <div class="profile-header mb-4">

        <div class="row align-items-center">

            <div class="col-md-2 text-center">

                <div class="profile-icon mx-auto">

                    <i class="bi bi-person-fill"></i>

                </div>

            </div>

            <div class="col-md-10">

                <h2 class="mb-1">

                    <?php
                    echo htmlspecialchars(
                        $student['first_name'] . " " .
                        $student['last_name']
                    );
                    ?>

                </h2>

                <p class="mb-1">

                    <i class="bi bi-person-badge"></i>

                    Registration No:
                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $student['registration_no']
                        );
                        ?>
                    </strong>

                </p>

                <p class="mb-0">

                    <i class="bi bi-building"></i>

                    <?php
                    echo htmlspecialchars(
                        $student['department_name'] ?? 'Not Assigned'
                    );
                    ?>

                </p>

            </div>

        </div>

    </div>


    <!-- Student Information -->

    <div class="card info-card mb-4">

        <div class="card-body p-4">

            <h4 class="mb-4">

                <i class="bi bi-person-vcard text-primary"></i>

                Student Information

            </h4>

            <div class="row g-4">

                <div class="col-md-4">

                    <div class="info-label">
                        Registration Number
                    </div>

                    <div class="info-value">
                        <?php
                        echo htmlspecialchars(
                            $student['registration_no']
                        );
                        ?>
                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-label">
                        First Name
                    </div>

                    <div class="info-value">
                        <?php
                        echo htmlspecialchars(
                            $student['first_name']
                        );
                        ?>
                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-label">
                        Last Name
                    </div>

                    <div class="info-value">
                        <?php
                        echo htmlspecialchars(
                            $student['last_name']
                        );
                        ?>
                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-label">
                        Gender
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student['gender'] ?? 'Not specified'
                        );
                        ?>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-label">
                        Department
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student['department_name'] ?? 'Not Assigned'
                        );
                        ?>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="info-label">
                        Student ID
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student['student_id']
                        );
                        ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Academic Statistics -->

    <div class="row g-4 mb-4">

        <div class="col-md-4">

            <div class="card stat-card text-center">

                <div class="card-body p-4">

                    <i class="bi bi-award text-warning fs-1"></i>

                    <h6 class="text-muted mt-2">
                        Current GPA
                    </h6>

                    <div class="gpa-number text-primary">

                        <?php
                        echo number_format($gpa, 2);
                        ?>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card text-center">

                <div class="card-body p-4">

                    <i class="bi bi-book text-success fs-1"></i>

                    <h6 class="text-muted mt-2">
                        Courses Completed
                    </h6>

                    <div class="gpa-number text-success">

                        <?php
                        echo count($grade_rows);
                        ?>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card text-center">

                <div class="card-body p-4">

                    <i class="bi bi-clock-history text-info fs-1"></i>

                    <h6 class="text-muted mt-2">
                        Credit Hours
                    </h6>

                    <div class="gpa-number text-info">

                        <?php
                        echo number_format($total_credits, 0);
                        ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Academic Record -->

    <div class="card info-card">

        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h4 class="mb-0">

                    <i class="bi bi-mortarboard text-primary"></i>

                    Academic Record

                </h4>

                <span class="badge bg-primary">

                    <?php echo count($grade_rows); ?>
                    Records

                </span>

            </div>


            <?php if (count($grade_rows) > 0) { ?>

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead class="table-dark">

                        <tr>

                            <th>#</th>

                            <th>Course Code</th>

                            <th>Course</th>

                            <th>Credit</th>

                            <th>Semester</th>

                            <th>Academic Year</th>

                            <th>Grade</th>

                            <th>Grade Point</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php

                    $number = 1;

                    foreach ($grade_rows as $grade) {

                        $grade_color = "secondary";

                        switch ($grade['grade']) {

                            case "A+":
                            case "A":
                                $grade_color = "success";
                                break;

                            case "A-":
                            case "B+":
                            case "B":
                                $grade_color = "primary";
                                break;

                            case "B-":
                            case "C+":
                            case "C":
                                $grade_color = "warning";
                                break;

                            default:
                                $grade_color = "danger";
                        }

                    ?>

                    <tr>

                        <td>
                            <?php echo $number++; ?>
                        </td>

                        <td>
                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $grade['course_code']
                                );
                                ?>
                            </strong>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $grade['course_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $grade['credit_hours']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $grade['semester_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $grade['academic_year']
                            );
                            ?>
                        </td>

                        <td>

                            <span
                            class="badge bg-<?php echo $grade_color; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $grade['grade']
                                );
                                ?>

                            </span>

                        </td>

                        <td>

                            <strong>

                                <?php
                                echo number_format(
                                    (float)$grade['grade_point'],
                                    2
                                );
                                ?>

                            </strong>

                        </td>

                    </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

            <?php } else { ?>

                <div class="text-center py-5">

                    <i class="bi bi-journal-x text-muted fs-1"></i>

                    <h5 class="mt-3">
                        No Academic Records
                    </h5>

                    <p class="text-muted">
                        This student does not have any grades yet.
                    </p>

                </div>

            <?php } ?>

        </div>

    </div>


    <!-- Footer -->

    <div class="text-center text-muted mt-4">

        <small>
            Campus Grade Management System
            &copy; <?php echo date("Y"); ?>
        </small>

    </div>

</div>

</body>

</html>