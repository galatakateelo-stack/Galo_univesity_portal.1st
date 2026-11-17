<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");


/* =========================
   CHECK INSTRUCTOR ID
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$instructor_id = intval($_GET['id']);

$message = "";
$message_type = "";


/* =========================
   FETCH INSTRUCTOR
========================= */

$sql = "
SELECT
    instructors.*,
    departments.department_name

FROM instructors

LEFT JOIN departments
ON instructors.department_id = departments.department_id

WHERE instructors.instructor_id = '$instructor_id'

LIMIT 1
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

$instructor = mysqli_fetch_assoc($result);

if (!$instructor) {
    header("Location: index.php");
    exit();
}


/* =========================
   FETCH DEPARTMENTS
========================= */

$departments = mysqli_query($conn, "
    SELECT
        department_id,
        department_name

    FROM departments

    ORDER BY department_name ASC
");

if (!$departments) {
    die("Department Query Error: " . mysqli_error($conn));
}


/* =========================
   UPDATE INSTRUCTOR
========================= */

if (isset($_POST['update'])) {

    $full_name = trim($_POST['full_name']);
    $department_id = intval($_POST['department_id']);


    /* =====================
       VALIDATION
    ===================== */

    if ($full_name == "") {

        $message = "Instructor name is required.";
        $message_type = "danger";

    } elseif ($department_id <= 0) {

        $message = "Please select a department.";
        $message_type = "danger";

    } else {

        $full_name = mysqli_real_escape_string(
            $conn,
            $full_name
        );


        /* =====================
           CHECK DUPLICATE NAME
        ===================== */

        $check = mysqli_query($conn, "
            SELECT instructor_id

            FROM instructors

            WHERE full_name = '$full_name'

            AND instructor_id != '$instructor_id'

            LIMIT 1
        ");

        if (!$check) {
            die("Database Error: " . mysqli_error($conn));
        }


        if (mysqli_num_rows($check) > 0) {

            $message =
                "Another instructor with this name already exists.";

            $message_type = "warning";

        } else {


            /* =====================
               UPDATE
            ===================== */

            $update = mysqli_query($conn, "

                UPDATE instructors

                SET
                    full_name = '$full_name',
                    department_id = '$department_id'

                WHERE instructor_id = '$instructor_id'

            ");


            if ($update) {

                $message =
                    "Instructor updated successfully.";

                $message_type = "success";


                /* Refresh instructor data */

                $refresh = mysqli_query($conn, "

                    SELECT
                        instructors.*,
                        departments.department_name

                    FROM instructors

                    LEFT JOIN departments
                    ON instructors.department_id =
                       departments.department_id

                    WHERE instructors.instructor_id =
                          '$instructor_id'

                    LIMIT 1
                ");

                if ($refresh) {

                    $instructor =
                        mysqli_fetch_assoc($refresh);
                }

            } else {

                $message =
                    "Unable to update instructor.";

                $message_type = "danger";
            }
        }
    }
}


/* =========================
   COUNT ASSIGNED COURSES
========================= */

$course_count = 0;

$course_query = mysqli_query($conn, "

    SELECT COUNT(*) AS total

    FROM instructor_courses

    WHERE instructor_id = '$instructor_id'

");

if ($course_query) {

    $course_count =
        mysqli_fetch_assoc($course_query)['total'];
}


/* =========================
   COUNT UPLOADED GRADES
========================= */

$grade_count = 0;

$grade_query = mysqli_query($conn, "

    SELECT COUNT(*) AS total

    FROM grades

    WHERE instructor_id = '$instructor_id'

");

if ($grade_query) {

    $grade_count =
        mysqli_fetch_assoc($grade_query)['total'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Edit Instructor | RVU Academic System
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


        /* =========================
           PAGE HEADER
        ========================= */

        .page-header {

            background: linear-gradient(
                135deg,
                #0d6efd,
                #084298
            );

            color: white;

            border-radius: 16px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 6px 20px
                rgba(0,0,0,0.12);

        }


        /* =========================
           MAIN CARD
        ========================= */

        .main-card {

            border: none;

            border-radius: 16px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.08);

        }


        /* =========================
           PROFILE ICON
        ========================= */

        .profile-icon {

            width: 90px;

            height: 90px;

            border-radius: 50%;

            background: #cfe2ff;

            color: #0d6efd;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 42px;

            margin: auto;

        }


        /* =========================
           FORM
        ========================= */

        .form-label {

            font-weight: 600;

        }


        .form-control,
        .form-select {

            border-radius: 10px;

            padding: 12px;

        }


        .form-control:focus,
        .form-select:focus {

            border-color: #0d6efd;

            box-shadow:
                0 0 0 0.2rem
                rgba(13,110,253,0.15);

        }


        /* =========================
           STAT CARDS
        ========================= */

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


        /* =========================
           BUTTONS
        ========================= */

        .btn {

            border-radius: 9px;

        }


        .save-btn {

            padding: 12px 25px;

            font-weight: 600;

        }


        /* =========================
           INFO BOX
        ========================= */

        .info-box {

            background: #f8f9fa;

            border: 1px solid #e9ecef;

            border-radius: 12px;

            padding: 18px;

        }


        .info-label {

            color: #6c757d;

            font-size: 13px;

        }


        .info-value {

            font-size: 17px;

            font-weight: 600;

        }

    </style>

</head>


<body>


<div class="container-fluid px-4 py-4">


    <!-- =========================
         PAGE HEADER
    ========================= -->

    <div class="page-header">

        <div
            class="d-flex
                   justify-content-between
                   align-items-center
                   flex-wrap">

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-person-badge"></i>

                    Edit Instructor

                </h2>

                <p class="mb-0 opacity-75">

                    Update instructor information
                    and department assignment

                </p>

            </div>


            <div class="mt-3 mt-md-0">

                <a
                    href="index.php"
                    class="btn btn-light">

                    <i class="bi bi-arrow-left"></i>

                    Back to Instructors

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

            <?php
            echo htmlspecialchars($message);
            ?>

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

                        Instructor Information

                    </h5>

                </div>


                <div class="card-body p-4">


                    <form
                        method="POST"
                        autocomplete="off">


                        <!-- Instructor ID -->

                        <div class="mb-4">

                            <label class="form-label">

                                Instructor ID

                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?php
                                    echo $instructor_id;
                                ?>"
                                readonly>

                            <div class="form-text">

                                Instructor ID cannot be changed.

                            </div>

                        </div>



                        <!-- Full Name -->

                        <div class="mb-4">

                            <label
                                for="full_name"
                                class="form-label">

                                Full Name

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <div class="input-group">

                                <span
                                    class="input-group-text">

                                    <i class="bi bi-person"></i>

                                </span>


                                <input
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    class="form-control"
                                    placeholder="Enter instructor full name"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $instructor[
                                                'full_name'
                                            ]
                                        );
                                    ?>"
                                    required>

                            </div>

                        </div>



                        <!-- Department -->

                        <div class="mb-4">

                            <label
                                for="department_id"
                                class="form-label">

                                Department

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


                                <select
                                    id="department_id"
                                    name="department_id"
                                    class="form-select"
                                    required>

                                    <option value="">

                                        Select Department

                                    </option>


                                    <?php

                                    while (
                                        $department =
                                        mysqli_fetch_assoc(
                                            $departments
                                        )
                                    ) {

                                    ?>

                                        <option
                                            value="<?php
                                                echo $department[
                                                    'department_id'
                                                ];
                                            ?>"
                                            <?php

                                            if (
                                                $department[
                                                    'department_id'
                                                ]
                                                ==
                                                $instructor[
                                                    'department_id'
                                                ]
                                            ) {

                                                echo "selected";

                                            }

                                            ?>>

                                            <?php
                                            echo htmlspecialchars(
                                                $department[
                                                    'department_name'
                                                ]
                                            );
                                            ?>

                                        </option>

                                    <?php } ?>

                                </select>

                            </div>

                        </div>



                        <!-- =====================
                             BUTTONS
                        ====================== -->

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
                                       btn-primary
                                       save-btn">

                                <i
                                    class="bi bi-check-circle">
                                </i>

                                Update Instructor

                            </button>


                        </div>


                    </form>

                </div>

            </div>

        </div>



        <!-- =========================
             PREVIEW + STATISTICS
        ========================== -->

        <div class="col-lg-4">


            <!-- PROFILE -->

            <div class="card main-card mb-4">

                <div
                    class="card-body
                           text-center
                           p-4">


                    <div class="profile-icon">

                        <i class="bi bi-person"></i>

                    </div>


                    <h4 class="mt-3 mb-1">

                        <?php
                        echo htmlspecialchars(
                            $instructor[
                                'full_name'
                            ]
                        );
                        ?>

                    </h4>


                    <p class="text-muted mb-2">

                        Instructor ID:

                        <strong>

                            <?php
                            echo $instructor_id;
                            ?>

                        </strong>

                    </p>


                    <span class="badge bg-success">

                        <i
                            class="bi bi-check-circle">
                        </i>

                        Active Instructor

                    </span>

                </div>

            </div>



            <!-- DEPARTMENT -->

            <div class="card main-card mb-4">

                <div class="card-body">

                    <div class="info-label">

                        Current Department

                    </div>

                    <div class="info-value mt-1">

                        <i
                            class="bi
                                   bi-building
                                   text-primary">
                        </i>

                        <?php

                        echo !empty(
                            $instructor[
                                'department_name'
                            ]
                        )
                        ? htmlspecialchars(
                            $instructor[
                                'department_name'
                            ]
                        )
                        : "Not Assigned";

                        ?>

                    </div>

                </div>

            </div>



            <!-- STATISTICS -->

            <div class="row g-3">


                <!-- Courses -->

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
                                        class="bi bi-book-fill">
                                    </i>

                                </div>


                                <div class="ms-3">

                                    <div class="text-muted">

                                        Assigned Courses

                                    </div>

                                    <div class="stat-number">

                                        <?php
                                        echo $course_count;
                                        ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- Grades -->

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
                                        class="bi bi-bar-chart-fill">
                                    </i>

                                </div>


                                <div class="ms-3">

                                    <div class="text-muted">

                                        Grades Uploaded

                                    </div>

                                    <div class="stat-number">

                                        <?php
                                        echo $grade_count;
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