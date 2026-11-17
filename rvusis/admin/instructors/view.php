<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");


/* =========================
   GET INSTRUCTOR ID
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$instructor_id = intval($_GET['id']);


/* =========================
   INSTRUCTOR INFORMATION
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
   ASSIGNED COURSES
========================= */

$courses_sql = "
SELECT
    c.course_code,
    c.course_name,
    c.credit_hours,
    s.semester_name,
    ic.academic_year

FROM instructor_courses ic

INNER JOIN courses c
ON ic.course_id = c.course_id

INNER JOIN semesters s
ON ic.semester_id = s.semester_id

WHERE ic.instructor_id = '$instructor_id'

ORDER BY ic.id DESC
";

$courses_result = mysqli_query($conn, $courses_sql);

if (!$courses_result) {
    die("Course Query Error: " . mysqli_error($conn));
}


/* =========================
   COUNT ASSIGNED COURSES
========================= */

$count_sql = "
SELECT COUNT(*) AS total
FROM instructor_courses
WHERE instructor_id = '$instructor_id'
";

$count_result = mysqli_query($conn, $count_sql);

$course_count = mysqli_fetch_assoc($count_result)['total'];


/* =========================
   COUNT GRADES
========================= */

$grade_sql = "
SELECT COUNT(*) AS total
FROM grades
WHERE instructor_id = '$instructor_id'
";

$grade_result = mysqli_query($conn, $grade_sql);

$grade_count = 0;

if ($grade_result) {
    $grade_count = mysqli_fetch_assoc($grade_result)['total'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        View Instructor | RVU Academic System
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


        .main-card {

            border: none;

            border-radius: 16px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.08);
        }


        .profile-icon {

            width: 90px;

            height: 90px;

            border-radius: 50%;

            background: #e7f1ff;

            color: #0d6efd;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 42px;

            margin: auto;

        }


        .info-box {

            background: #f8f9fa;

            border-radius: 12px;

            padding: 18px;

            height: 100%;

            border: 1px solid #e9ecef;

        }


        .info-label {

            color: #6c757d;

            font-size: 13px;

            margin-bottom: 5px;

        }


        .info-value {

            font-size: 17px;

            font-weight: 600;

            color: #212529;

        }


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


        .course-code {

            font-weight: 700;

            color: #0d6efd;
        }


        .table {

            vertical-align: middle;
        }


        .action-btn {

            border-radius: 8px;
        }

    </style>

</head>


<body>


<div class="container-fluid px-4 py-4">


    <!-- =========================
         HEADER
    ========================== -->

    <div class="page-header">

        <div
            class="d-flex
                   justify-content-between
                   align-items-center
                   flex-wrap">

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-person-badge"></i>

                    Instructor Profile

                </h2>

                <p class="mb-0 opacity-75">

                    Instructor information and assigned courses

                </p>

            </div>


            <div class="mt-3 mt-md-0">

                <a
                    href="edit.php?id=<?php echo $instructor_id; ?>"
                    class="btn btn-warning me-2">

                    <i class="bi bi-pencil"></i>

                    Edit Instructor

                </a>


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
         PROFILE
    ========================== -->

    <div class="card main-card mb-4">

        <div class="card-body p-4">


            <div class="row align-items-center">


                <!-- PROFILE ICON -->

                <div class="col-md-2 text-center">

                    <div class="profile-icon">

                        <i class="bi bi-person"></i>

                    </div>

                </div>



                <!-- BASIC INFORMATION -->

                <div class="col-md-10">

                    <h2 class="mb-1">

                        <?php
                        echo htmlspecialchars(
                            $instructor['full_name']
                        );
                        ?>

                    </h2>


                    <p class="text-muted mb-2">

                        Instructor ID:

                        <strong>
                            <?php
                            echo $instructor['instructor_id'];
                            ?>
                        </strong>

                    </p>


                    <span class="badge bg-success">

                        <i class="bi bi-check-circle"></i>

                        Active Instructor

                    </span>

                </div>


            </div>

        </div>

    </div>



    <!-- =========================
         STATISTICS
    ========================== -->

    <div class="row g-4 mb-4">


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body text-center">

                    <i
                        class="bi bi-book fs-1 text-primary">
                    </i>

                    <div class="stat-number">

                        <?php
                        echo $course_count;
                        ?>

                    </div>

                    <div class="text-muted">

                        Assigned Courses

                    </div>

                </div>

            </div>

        </div>



        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body text-center">

                    <i
                        class="bi bi-bar-chart fs-1 text-success">
                    </i>

                    <div class="stat-number">

                        <?php
                        echo $grade_count;
                        ?>

                    </div>

                    <div class="text-muted">

                        Grades Uploaded

                    </div>

                </div>

            </div>

        </div>



        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body text-center">

                    <i
                        class="bi bi-building fs-1 text-warning">
                    </i>

                    <div class="stat-number">

                        <?php
                        echo !empty(
                            $instructor['department_name']
                        )
                        ? "1"
                        : "0";
                        ?>

                    </div>

                    <div class="text-muted">

                        Department

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- =========================
         INSTRUCTOR DETAILS
    ========================== -->

    <div class="card main-card mb-4">

        <div class="card-header bg-dark text-white">

            <h5 class="mb-0">

                <i class="bi bi-person-lines-fill"></i>

                Instructor Information

            </h5>

        </div>


        <div class="card-body">

            <div class="row g-4">


                <!-- FULL NAME -->

                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Full Name

                        </div>

                        <div class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $instructor['full_name']
                            );
                            ?>

                        </div>

                    </div>

                </div>



                <!-- DEPARTMENT -->

                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Department

                        </div>

                        <div class="info-value">

                            <?php

                            echo !empty(
                                $instructor['department_name']
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



                <!-- EMAIL -->

                <?php if (isset($instructor['email'])) { ?>

                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Email

                        </div>

                        <div class="info-value">

                            <i
                                class="bi bi-envelope text-primary">
                            </i>

                            <?php
                            echo htmlspecialchars(
                                $instructor['email']
                            );
                            ?>

                        </div>

                    </div>

                </div>

                <?php } ?>



                <!-- PHONE -->

                <?php if (isset($instructor['phone'])) { ?>

                <div class="col-md-6">

                    <div class="info-box">

                        <div class="info-label">

                            Phone

                        </div>

                        <div class="info-value">

                            <i
                                class="bi bi-telephone text-success">
                            </i>

                            <?php
                            echo htmlspecialchars(
                                $instructor['phone']
                            );
                            ?>

                        </div>

                    </div>

                </div>

                <?php } ?>


            </div>

        </div>

    </div>



    <!-- =========================
         ASSIGNED COURSES
    ========================== -->

    <div class="card main-card">

        <div class="card-header bg-primary text-white">

            <div
                class="d-flex
                       justify-content-between
                       align-items-center">

                <h5 class="mb-0">

                    <i class="bi bi-journal-bookmark"></i>

                    Assigned Courses

                </h5>


                <span class="badge bg-light text-dark">

                    <?php
                    echo $course_count;
                    ?>

                    Courses

                </span>

            </div>

        </div>



        <div class="card-body">

            <div class="table-responsive">

                <table
                    class="table
                           table-hover
                           align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>#</th>

                            <th>Course Code</th>

                            <th>Course Name</th>

                            <th>Credit Hours</th>

                            <th>Semester</th>

                            <th>Academic Year</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    if (
                        mysqli_num_rows(
                            $courses_result
                        ) > 0
                    ) {

                        $number = 1;

                        while (
                            $course =
                            mysqli_fetch_assoc(
                                $courses_result
                            )
                        ) {

                    ?>

                        <tr>

                            <td>

                                <?php
                                echo $number++;
                                ?>

                            </td>


                            <td>

                                <span
                                    class="course-code">

                                    <?php
                                    echo htmlspecialchars(
                                        $course[
                                            'course_code'
                                        ]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $course[
                                        'course_name'
                                    ]
                                );
                                ?>

                            </td>


                            <td>

                                <span
                                    class="badge bg-primary">

                                    <?php
                                    echo $course[
                                        'credit_hours'
                                    ];
                                    ?>

                                    Credits

                                </span>

                            </td>


                            <td>

                                <span
                                    class="badge bg-info text-dark">

                                    <?php
                                    echo htmlspecialchars(
                                        $course[
                                            'semester_name'
                                        ]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $course[
                                        'academic_year'
                                    ]
                                );
                                ?>

                            </td>

                        </tr>


                    <?php

                        }

                    } else {

                    ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5">

                                <i
                                    class="bi
                                           bi-journal-x
                                           fs-1
                                           text-muted">
                                </i>

                                <h5 class="mt-3">

                                    No Courses Assigned

                                </h5>

                                <p class="text-muted">

                                    This instructor currently
                                    has no assigned courses.

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


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>