<?php
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location: login.php");
    exit();
}

include("../includes/db.php");

$instructor_id = intval($_SESSION['instructor_id']);
$grade_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($grade_id <= 0) {
    header("Location: grades.php");
    exit();
}

/* =========================================================
   GRADE POINT FUNCTION
========================================================= */

function getGradePoint($grade)
{
    switch (strtoupper(trim($grade))) {

        case "A+":
        case "A":
            return 4.00;

        case "A-":
            return 3.70;

        case "B+":
            return 3.50;

        case "B":
            return 3.00;

        case "B-":
            return 2.70;

        case "C+":
            return 2.50;

        case "C":
            return 2.00;

        case "C-":
            return 1.70;

        case "D+":
            return 1.50;

        case "D":
            return 1.00;

        case "F":
            return 0.00;

        default:
            return null;
    }
}

/* =========================================================
   GET EXISTING GRADE
========================================================= */

$sql = "
SELECT
    g.grade_id,
    g.student_id,
    g.course_id,
    g.semester_id,
    g.grade,
    g.grade_point,

    s.registration_no,
    s.first_name,
    s.last_name,

    c.course_code,
    c.course_name,
    c.credit_hours,

    sem.semester_name,
    sem.academic_year

FROM grades g

INNER JOIN students s
    ON g.student_id = s.student_id

INNER JOIN courses c
    ON g.course_id = c.course_id

LEFT JOIN semesters sem
    ON g.semester_id = sem.semester_id

INNER JOIN instructor_courses ic
    ON g.course_id = ic.course_id
    AND ic.instructor_id = $instructor_id

WHERE
    g.grade_id = $grade_id
    AND g.instructor_id = $instructor_id

LIMIT 1
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . htmlspecialchars(mysqli_error($conn)));
}

if (mysqli_num_rows($result) == 0) {
    die("
        <div style='
            font-family:Arial;
            padding:40px;
            text-align:center;
        '>
            <h2 style='color:#dc3545;'>
                Access Denied
            </h2>

            <p>
                This grade does not belong to you
                or the course is not currently assigned to you.
            </p>

            <a href='grades.php'
               style='
                   display:inline-block;
                   padding:10px 20px;
                   background:#0d6efd;
                   color:white;
                   text-decoration:none;
                   border-radius:8px;
               '>
                Back to Grades
            </a>
        </div>
    ");

}

$grade_data = mysqli_fetch_assoc($result);

/* =========================================================
   UPDATE GRADE
========================================================= */

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_grade = strtoupper(
        trim($_POST['grade'] ?? '')
    );

    $grade_point = getGradePoint($new_grade);

    if ($grade_point === null) {

        $error = "Please select a valid grade.";

    } else {

        $new_grade_safe = mysqli_real_escape_string(
            $conn,
            $new_grade
        );

        $update_sql = "
        UPDATE grades

        SET
            grade = '$new_grade_safe',
            grade_point = $grade_point

        WHERE
            grade_id = $grade_id
            AND instructor_id = $instructor_id

            AND course_id IN (
                SELECT course_id
                FROM instructor_courses
                WHERE instructor_id = $instructor_id
            )
        ";

        if (mysqli_query($conn, $update_sql)) {

            $message = "Grade updated successfully.";

            /* Refresh displayed grade */

            $grade_data['grade'] = $new_grade;
            $grade_data['grade_point'] = $grade_point;

        } else {

            $error =
                "Unable to update grade: "
                . mysqli_error($conn);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Grade | Instructor</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
rel="stylesheet">

<style>

body {
    background:#f4f7fb;
    font-family:Arial, Helvetica, sans-serif;
    color:#172033;
}

.top-header {

    background:
    linear-gradient(
        135deg,
        #0d6efd,
        #084298
    );

    color:white;

    padding:30px;

    border-radius:
    0 0 22px 22px;

    box-shadow:
    0 6px 25px
    rgba(0,0,0,.12);
}

.top-header h1 {

    margin:0;

    font-size:30px;

    font-weight:700;
}

.main-container {

    max-width:900px;

    margin:40px auto;

    padding:0 20px;
}

.grade-card {

    background:white;

    border:none;

    border-radius:20px;

    box-shadow:
    0 10px 35px
    rgba(0,0,0,.08);

    overflow:hidden;
}

.grade-card-header {

    padding:25px;

    border-bottom:
    1px solid #e9ecef;
}

.grade-card-body {

    padding:30px;
}

.info-box {

    background:#f8f9fa;

    border-radius:12px;

    padding:18px;

    margin-bottom:25px;
}

.info-label {

    font-size:13px;

    color:#6c757d;

    font-weight:600;

    margin-bottom:4px;
}

.info-value {

    font-size:17px;

    font-weight:600;
}

.form-select {

    padding:12px;

    border-radius:10px;
}

.btn {

    border-radius:10px;

    padding:11px 20px;

    font-weight:600;
}

</style>

</head>

<body>

<!-- HEADER -->

<div class="top-header">

    <div class="container">

        <h1>
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Student Grade
        </h1>

        <p class="mb-0 mt-2">
            Update an existing academic grade
        </p>

    </div>

</div>


<!-- MAIN -->

<div class="main-container">

    <div class="mb-4">

        <a
        href="grades.php"
        class="btn btn-outline-primary">

            <i class="fa-solid fa-arrow-left"></i>

            Back to Grades

        </a>

    </div>


    <div class="grade-card">

        <div class="grade-card-header">

            <h3 class="mb-1">

                <i class="fa-solid fa-user-graduate text-primary"></i>

                Student Grade

            </h3>

            <small class="text-muted">

                Grade ID:
                <?php echo $grade_data['grade_id']; ?>

            </small>

        </div>


        <div class="grade-card-body">


            <?php if ($message): ?>

                <div class="alert alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <?php if ($error): ?>

                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <!-- STUDENT -->

            <div class="row g-3">

                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Registration Number

                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $grade_data['registration_no']
                            );
                            ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Student Name

                        </div>

                        <div class="info-value">

                            <?php

                            echo htmlspecialchars(
                                $grade_data['first_name']
                                . " "
                                . $grade_data['last_name']
                            );

                            ?>

                        </div>

                    </div>

                </div>


                <!-- COURSE -->

                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Course

                        </div>

                        <div class="info-value">

                            <?php

                            echo htmlspecialchars(
                                $grade_data['course_code']
                            );

                            ?>

                            <br>

                            <small class="text-muted">

                                <?php

                                echo htmlspecialchars(
                                    $grade_data['course_name']
                                );

                                ?>

                            </small>

                        </div>

                    </div>

                </div>


                <!-- CREDIT -->

                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Credit Hours

                        </div>

                        <div class="info-value">

                            <?php

                            echo htmlspecialchars(
                                $grade_data['credit_hours']
                            );

                            ?>

                        </div>

                    </div>

                </div>


                <!-- SEMESTER -->

                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Semester

                        </div>

                        <div class="info-value">

                            <?php

                            echo htmlspecialchars(
                                $grade_data['semester_name']
                                ?? "N/A"
                            );

                            ?>

                        </div>

                    </div>

                </div>


                <!-- ACADEMIC YEAR -->

                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Academic Year

                        </div>

                        <div class="info-value">

                            <?php

                            echo htmlspecialchars(
                                $grade_data['academic_year']
                                ?? "N/A"
                            );

                            ?>

                        </div>

                    </div>

                </div>

            </div>


            <hr class="my-4">


            <!-- EDIT FORM -->

            <form method="POST">


                <div class="mb-4">

                    <label class="form-label fw-bold">

                        <i class="fa-solid fa-graduation-cap"></i>

                        Select Grade

                    </label>


                    <select
                        name="grade"
                        class="form-select"
                        required>

                        <?php

                        $grades = [
                            "A+",
                            "A",
                            "A-",
                            "B+",
                            "B",
                            "B-",
                            "C+",
                            "C",
                            "C-",
                            "D+",
                            "D",
                            "F"
                        ];

                        foreach ($grades as $g):

                        ?>

                            <option
                                value="<?php echo $g; ?>"
                                <?php
                                echo (
                                    $grade_data['grade']
                                    === $g
                                )
                                ? "selected"
                                : "";
                                ?>>

                                <?php echo $g; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="alert alert-info">

                    <i class="fa-solid fa-circle-info"></i>

                    The grade point will be calculated
                    automatically when you save the grade.

                </div>


                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fa-solid fa-save"></i>

                        Save Changes

                    </button>


                    <a
                        href="grades.php"
                        class="btn btn-secondary">

                        Cancel

                    </a>

                </div>


            </form>


        </div>

    </div>

</div>

</body>

</html>