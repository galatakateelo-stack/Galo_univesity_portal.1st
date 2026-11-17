<?php
session_start();

include("../includes/db.php");

if (!isset($_SESSION['instructor_id'])) {
    header("Location: login.php");
    exit();
}

$instructor_id = intval($_SESSION['instructor_id']);

$message = "";
$message_type = "";


/* =========================================================
   UPLOAD GRADE
========================================================= */

if (isset($_POST['save'])) {

    $student_id  = intval($_POST['student_id']);
    $course_id   = intval($_POST['course_id']);
    $semester_id = intval($_POST['semester_id']);

    $grade = strtoupper(trim($_POST['grade']));


    /* -----------------------------------------------------
       GRADE POINT
    ----------------------------------------------------- */

    $grade_points = [

        "A+" => 4.00,
        "A"  => 4.00,
        "A-" => 3.75,

        "B+" => 3.50,
        "B"  => 3.00,
        "B-" => 2.75,

        "C+" => 2.50,
        "C"  => 2.00,
        "C-" => 1.75,

        "D+" => 1.50,
        "D"  => 1.00,

        "F"  => 0.00

    ];


    /* -----------------------------------------------------
       VALIDATE GRADE
    ----------------------------------------------------- */

    if (!array_key_exists($grade, $grade_points)) {

        $message =
            "Invalid grade. Please enter a valid grade.";

        $message_type = "danger";

    } else {

        $grade_point = $grade_points[$grade];


        /* -------------------------------------------------
           CHECK COURSE ASSIGNED TO THIS INSTRUCTOR
        ------------------------------------------------- */

        $course_check = mysqli_prepare(
            $conn,
            "SELECT course_id
             FROM instructor_courses
             WHERE instructor_id = ?
             AND course_id = ?
             LIMIT 1"
        );


        if (!$course_check) {

            $message =
                "Unable to verify course assignment.";

            $message_type = "danger";

        } else {

            mysqli_stmt_bind_param(
                $course_check,
                "ii",
                $instructor_id,
                $course_id
            );

            mysqli_stmt_execute($course_check);

            mysqli_stmt_store_result($course_check);


            if (mysqli_stmt_num_rows($course_check) == 0) {

                $message =
                    "You cannot upload a grade for this course because it is not assigned to you.";

                $message_type = "danger";

                mysqli_stmt_close($course_check);

            } else {

                mysqli_stmt_close($course_check);


                /* -----------------------------------------
                   CHECK DUPLICATE GRADE
                ----------------------------------------- */

                $duplicate = mysqli_prepare(
                    $conn,
                    "SELECT grade_id
                     FROM grades
                     WHERE student_id = ?
                     AND course_id = ?
                     AND semester_id = ?
                     LIMIT 1"
                );


                if (!$duplicate) {

                    $message =
                        "Unable to check existing grades.";

                    $message_type = "danger";

                } else {

                    mysqli_stmt_bind_param(
                        $duplicate,
                        "iii",
                        $student_id,
                        $course_id,
                        $semester_id
                    );

                    mysqli_stmt_execute($duplicate);

                    mysqli_stmt_store_result($duplicate);


                    if (
                        mysqli_stmt_num_rows($duplicate)
                        > 0
                    ) {

                        $message =
                            "Duplicate grade! This student already has a grade for this course in this semester.";

                        $message_type = "warning";

                        mysqli_stmt_close($duplicate);

                    } else {

                        mysqli_stmt_close($duplicate);


                        /* ---------------------------------
                           INSERT GRADE
                        --------------------------------- */

                        $insert = mysqli_prepare(
                            $conn,
                            "INSERT INTO grades
                            (
                                student_id,
                                course_id,
                                instructor_id,
                                semester_id,
                                grade,
                                grade_point
                            )
                            VALUES
                            (?, ?, ?, ?, ?, ?)"
                        );


                        if (!$insert) {

                            $message =
                                "Unable to prepare grade upload.";

                            $message_type = "danger";

                        } else {

                            mysqli_stmt_bind_param(
                                $insert,
                                "iiiisd",
                                $student_id,
                                $course_id,
                                $instructor_id,
                                $semester_id,
                                $grade,
                                $grade_point
                            );


                            if (
                                mysqli_stmt_execute($insert)
                            ) {

                                $message =
                                    "Grade uploaded successfully! Grade Point: "
                                    . number_format(
                                        $grade_point,
                                        2
                                    );

                                $message_type =
                                    "success";

                            } else {

                                if (
                                    mysqli_errno($conn)
                                    == 1062
                                ) {

                                    $message =
                                        "Duplicate grade detected. This grade was not uploaded.";

                                    $message_type =
                                        "warning";

                                } else {

                                    $message =
                                        "Unable to upload grade: "
                                        . mysqli_error($conn);

                                    $message_type =
                                        "danger";
                                }
                            }


                            mysqli_stmt_close($insert);
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   GET STUDENTS
========================================================= */

$students = mysqli_query(
    $conn,
    "SELECT
        student_id,
        first_name,
        last_name,
        registration_no
     FROM students
     ORDER BY first_name ASC,
              last_name ASC"
);


if (!$students) {

    die(
        "Student Query Error: "
        . mysqli_error($conn)
    );
}


/* =========================================================
   GET ONLY ASSIGNED COURSES
========================================================= */

$courses = mysqli_query(
    $conn,
    "SELECT
        c.course_id,
        c.course_code,
        c.course_name,
        c.credit_hours

     FROM instructor_courses ic

     INNER JOIN courses c
        ON ic.course_id = c.course_id

     WHERE ic.instructor_id = $instructor_id

     ORDER BY c.course_code ASC"
);


if (!$courses) {

    die(
        "Course Query Error: "
        . mysqli_error($conn)
    );
}


/* =========================================================
   GET SEMESTERS
========================================================= */

$semesters = mysqli_query(
    $conn,
    "SELECT
        semester_id,
        semester_name,
        academic_year

     FROM semesters

     ORDER BY semester_id DESC"
);


if (!$semesters) {

    die(
        "Semester Query Error: "
        . mysqli_error($conn)
    );
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>
Upload Student Grade | University SIS
</title>


<!-- Bootstrap -->

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>


<!-- Bootstrap Icons -->

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


<style>

body {

    min-height:100vh;

    background:
    linear-gradient(
        135deg,
        #eef2ff,
        #f8fafc
    );

    font-family:
    "Segoe UI",
    Arial,
    sans-serif;
}


/* =========================================================
   MAIN CONTAINER
========================================================= */

.page-container {

    max-width:900px;

    margin:50px auto;

    padding:0 20px;
}


/* =========================================================
   CARD
========================================================= */

.grade-card {

    background:white;

    border:none;

    border-radius:22px;

    overflow:hidden;

    box-shadow:
    0 15px 45px
    rgba(0,0,0,.12);
}


/* =========================================================
   HEADER
========================================================= */

.grade-header {

    background:
    linear-gradient(
        135deg,
        #198754,
        #116b43
    );

    color:white;

    padding:30px;
}


.grade-header h2 {

    margin:0;

    font-weight:700;
}


.grade-header p {

    margin:
    8px 0 0;

    opacity:.9;
}


.header-icon {

    font-size:48px;

    opacity:.9;
}


/* =========================================================
   BODY
========================================================= */

.grade-body {

    padding:35px;
}


/* =========================================================
   LABELS
========================================================= */

.form-label {

    font-weight:600;

    color:#344054;
}


/* =========================================================
   INPUTS
========================================================= */

.form-select,
.form-control {

    min-height:48px;

    border-radius:11px;

    border:
    1px solid #d0d5dd;

    padding:
    10px 13px;
}


.form-select:focus,
.form-control:focus {

    border-color:#198754;

    box-shadow:
    0 0 0 .2rem
    rgba(25,135,84,.15);
}


/* =========================================================
   UPLOAD BUTTON
========================================================= */

.upload-btn {

    border:none;

    border-radius:11px;

    padding:
    12px 24px;

    font-weight:600;

    background:
    linear-gradient(
        135deg,
        #198754,
        #116b43
    );

    color:white;

    transition:.2s;
}


.upload-btn:hover {

    color:white;

    transform:
    translateY(-2px);

    box-shadow:
    0 8px 18px
    rgba(25,135,84,.25);
}


/* =========================================================
   NAVIGATION BUTTONS
========================================================= */

.nav-btn {

    border-radius:11px;

    padding:
    11px 18px;

    font-weight:600;
}


/* =========================================================
   ALERT
========================================================= */

.alert {

    border:none;

    border-radius:12px;

    padding:15px 18px;

    font-weight:500;
}


/* =========================================================
   GRADE SCALE
========================================================= */

.grade-info {

    background:#f8f9fa;

    border-radius:15px;

    padding:22px;

    margin-top:30px;
}


.grade-info h6 {

    font-weight:700;

    margin-bottom:15px;
}


.grade-list {

    display:flex;

    flex-wrap:wrap;

    gap:9px;
}


.grade-badge {

    background:white;

    border:
    1px solid #dee2e6;

    border-radius:9px;

    padding:
    7px 11px;

    font-size:13px;

    font-weight:500;
}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:768px) {

    .page-container {

        margin-top:20px;
    }


    .grade-body {

        padding:22px;
    }


    .grade-header {

        padding:22px;
    }


    .header-icon {

        display:none;
    }


    .action-buttons {

        flex-direction:column;
    }


    .action-buttons .btn,
    .action-buttons button {

        width:100%;
    }

}

</style>

</head>


<body>


<div class="page-container">


<div class="grade-card">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="grade-header">

<div
class="d-flex
justify-content-between
align-items-center"
>

<div>

<h2>

<i class="bi bi-mortarboard-fill"></i>

Upload Student Grade

</h2>


<p>

Academic Grade Management System

</p>

</div>


<i
class="bi bi-journal-check header-icon"
></i>

</div>

</div>


<!-- =====================================================
     BODY
===================================================== -->

<div class="grade-body">


<!-- =====================================================
     MESSAGE
===================================================== -->

<?php if ($message != "") { ?>

<div
class="alert
alert-<?php echo $message_type; ?>"
role="alert"
>

<?php

if ($message_type == "success") {

    echo
    '<i class="bi bi-check-circle-fill me-2"></i>';

}
elseif (
    $message_type == "warning"
) {

    echo
    '<i class="bi bi-exclamation-triangle-fill me-2"></i>';

}
else {

    echo
    '<i class="bi bi-x-circle-fill me-2"></i>';

}

?>

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php } ?>


<!-- =====================================================
     FORM
===================================================== -->

<form
method="POST"
autocomplete="off"
>


<!-- STUDENT -->

<div class="mb-4">

<label
class="form-label"
>

<i class="bi bi-person-fill me-1"></i>

Student

</label>


<select
name="student_id"
class="form-select"
required
>

<option value="">

Select Student

</option>


<?php

while (
    $row =
    mysqli_fetch_assoc($students)
) {

?>

<option
value="<?php
echo $row['student_id'];
?>"
>

<?php

echo htmlspecialchars(
    $row['registration_no']
    . " - "
    . $row['first_name']
    . " "
    . $row['last_name']
);

?>

</option>

<?php } ?>

</select>

</div>


<!-- COURSE -->

<div class="mb-4">

<label
class="form-label"
>

<i class="bi bi-book-fill me-1"></i>

Assigned Course

</label>


<select
name="course_id"
class="form-select"
required
>

<option value="">

Select Your Assigned Course

</option>


<?php

if (
    mysqli_num_rows($courses)
    == 0
) {

?>

<option
value=""
disabled
>

No courses assigned to you

</option>

<?php

}

while (
    $row =
    mysqli_fetch_assoc($courses)
) {

?>

<option
value="<?php
echo $row['course_id'];
?>"
>

<?php

echo htmlspecialchars(
    $row['course_code']
    . " - "
    . $row['course_name']
    . " ("
    . $row['credit_hours']
    . " Credit Hours)"
);

?>

</option>

<?php } ?>

</select>


<div class="form-text">

Only courses assigned to your instructor account are shown.

</div>

</div>


<!-- SEMESTER -->

<div class="mb-4">

<label
class="form-label"
>

<i class="bi bi-calendar3 me-1"></i>

Semester

</label>


<select
name="semester_id"
class="form-select"
required
>

<option value="">

Select Semester

</option>


<?php

while (
    $row =
    mysqli_fetch_assoc($semesters)
) {

?>

<option
value="<?php
echo $row['semester_id'];
?>"
>

<?php

echo htmlspecialchars(
    $row['semester_name']
    . " - "
    . $row['academic_year']
);

?>

</option>

<?php } ?>

</select>

</div>


<!-- GRADE -->

<div class="mb-4">

<label
class="form-label"
>

<i class="bi bi-award-fill me-1"></i>

Grade

</label>


<input
type="text"
name="grade"
class="form-control"
placeholder="Example: A+"
maxlength="2"
style="text-transform:uppercase;"
required
>


<div class="form-text">

Enter a valid grade:

<strong>

A+, A, A-, B+, B, B-, C+, C, C-, D+, D or F

</strong>

</div>

</div>


<!-- =====================================================
     BUTTONS
===================================================== -->

<div
class="d-flex
gap-2
mt-4
action-buttons"
>


<button
type="submit"
name="save"
class="upload-btn"
>

<i
class="bi bi-cloud-arrow-up-fill me-1"
></i>

Upload Grade

</button>


<a
href="grades.php"
class="btn btn-primary nav-btn"
>

<i
class="bi bi-list-check me-1"
></i>

View Grades

</a>


<a
href="dashboard.php"
class="btn btn-outline-secondary nav-btn"
>

<i
class="bi bi-arrow-left me-1"
></i>

Dashboard

</a>


</div>


</form>


<!-- =====================================================
     GRADE POINT SCALE
===================================================== -->

<div class="grade-info">


<h6>

<i
class="bi bi-info-circle-fill text-success me-1"
></i>

Grade Point Scale

</h6>


<div class="grade-list">


<span class="grade-badge">
A+ / A = 4.00
</span>


<span class="grade-badge">
A- = 3.75
</span>


<span class="grade-badge">
B+ = 3.50
</span>


<span class="grade-badge">
B = 3.00
</span>


<span class="grade-badge">
B- = 2.75
</span>


<span class="grade-badge">
C+ = 2.50
</span>


<span class="grade-badge">
C = 2.00
</span>


<span class="grade-badge">
C- = 1.75
</span>


<span class="grade-badge">
D+ = 1.50
</span>


<span class="grade-badge">
D = 1.00
</span>


<span class="grade-badge">
F = 0.00
</span>


</div>

</div>


</div>

</div>

</div>


</body>

</html>