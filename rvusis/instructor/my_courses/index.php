
<?php
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

$instructor_id = $_SESSION['instructor_id'];

/* =========================
   SEARCH
========================= */

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

$search_safe = mysqli_real_escape_string($conn, $search);


/* =========================
   GET ASSIGNED COURSES
========================= */

$sql = "
SELECT
    ic.id,
    ic.course_id,
    ic.semester_id,
    ic.academic_year,

    c.course_code,
    c.course_name,
    c.credit_hours,

    s.semester_name

FROM instructor_courses ic

INNER JOIN courses c
    ON ic.course_id = c.course_id

INNER JOIN semesters s
    ON ic.semester_id = s.semester_id

WHERE ic.instructor_id = '$instructor_id'

AND (
    c.course_code LIKE '%$search_safe%'
    OR c.course_name LIKE '%$search_safe%'
)

ORDER BY ic.academic_year DESC, s.semester_id ASC
";

$result = mysqli_query($conn, $sql);


/* =========================
   TOTAL ASSIGNED COURSES
========================= */

$count_query = mysqli_query($conn, "
SELECT COUNT(*) AS total
FROM instructor_courses
WHERE instructor_id = '$instructor_id'
");

$count_data = mysqli_fetch_assoc($count_query);
$total_courses = $count_data['total'];


/* =========================
   TOTAL CREDIT HOURS
========================= */

$credit_query = mysqli_query($conn, "
SELECT COALESCE(SUM(c.credit_hours), 0) AS total_credits

FROM instructor_courses ic

INNER JOIN courses c
    ON ic.course_id = c.course_id

WHERE ic.instructor_id = '$instructor_id'
");

$credit_data = mysqli_fetch_assoc($credit_query);
$total_credits = $credit_data['total_credits'];


/* =========================
   INSTRUCTOR NAME
========================= */

$instructor_query = mysqli_query($conn, "
SELECT full_name
FROM instructors
WHERE instructor_id = '$instructor_id'
LIMIT 1
");

$instructor = mysqli_fetch_assoc($instructor_query);

$instructor_name = $instructor['full_name'] ?? "Instructor";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Assigned Courses | Instructor Portal</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <style>

        body {
            background: #f4f7fb;
            font-family: Arial, sans-serif;
        }

        .page-header {
            background: linear-gradient(
                135deg,
                #0d6efd,
                #084298
            );

            color: white;
            border-radius: 0 0 25px 25px;
            padding: 35px 0;
            margin-bottom: 35px;
        }

        .page-title {
            font-weight: 700;
        }

        .stat-card {
            border: none;
            border-radius: 18px;
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 25px;
        }

        .main-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .course-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 5px 18px rgba(0,0,0,0.07);
            transition: 0.3s;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 28px rgba(0,0,0,0.12);
        }

        .course-code {
            font-size: 14px;
            font-weight: 700;
            color: #0d6efd;
            background: #eaf2ff;
            padding: 7px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        .course-title {
            font-weight: 700;
            color: #1f2937;
            margin-top: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #555;
        }

        .info-item i {
            color: #0d6efd;
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 60px;
            color: #adb5bd;
        }

    </style>

</head>

<body>


<!-- =========================
     HEADER
========================= -->

<div class="page-header">

    <div class="container">

        <div class="d-flex justify-content-between
                    align-items-center flex-wrap gap-3">

            <div>

                <h1 class="page-title mb-2">

                    <i class="bi bi-book-half"></i>

                    My Assigned Courses

                </h1>

                <p class="mb-0">

                    Instructor:
                    <strong>
                        <?php echo htmlspecialchars($instructor_name); ?>
                    </strong>

                </p>

            </div>

            <a href="../dashboard.php"
               class="btn btn-light">

                <i class="bi bi-arrow-left"></i>

                Dashboard

            </a>

        </div>

    </div>

</div>


<div class="container pb-5">


<!-- =========================
     STATISTICS
========================= -->

<div class="row g-4 mb-4">

    <div class="col-md-6">

        <div class="card stat-card bg-white shadow-sm">

            <div class="card-body d-flex
                        align-items-center gap-3">

                <div class="stat-icon bg-primary text-white">

                    <i class="bi bi-book"></i>

                </div>

                <div>

                    <small class="text-muted">
                        Assigned Courses
                    </small>

                    <h3 class="mb-0 fw-bold">

                        <?php echo $total_courses; ?>

                    </h3>

                </div>

            </div>

        </div>

    </div>


    <div class="col-md-6">

        <div class="card stat-card bg-white shadow-sm">

            <div class="card-body d-flex
                        align-items-center gap-3">

                <div class="stat-icon bg-success text-white">

                    <i class="bi bi-clock-history"></i>

                </div>

                <div>

                    <small class="text-muted">
                        Total Credit Hours
                    </small>

                    <h3 class="mb-0 fw-bold">

                        <?php echo $total_credits; ?>

                    </h3>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =========================
     COURSE SECTION
========================= -->

<div class="card main-card">

    <div class="card-body p-4">


        <!-- TOP BAR -->

        <div class="d-flex justify-content-between
                    align-items-center flex-wrap gap-3 mb-4">

            <div>

                <h3 class="fw-bold mb-1">

                    <i class="bi bi-journal-bookmark-fill text-primary"></i>

                    Assigned Courses

                </h3>

                <p class="text-muted mb-0">

                    Courses currently assigned to you

                </p>

            </div>


            <!-- SEARCH -->

            <form method="GET"
                  class="d-flex"
                  style="max-width: 350px; width:100%;">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Search course..."
                    value="<?php echo htmlspecialchars($search); ?>">

                <button
                    type="submit"
                    class="btn btn-primary ms-2">

                    <i class="bi bi-search"></i>

                </button>

            </form>

        </div>


        <!-- COURSE LIST -->

        <div class="row g-4">

            <?php

            if ($result && mysqli_num_rows($result) > 0) {

                while ($row = mysqli_fetch_assoc($result)) {

            ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card course-card">

                        <div class="card-body p-4">


                            <!-- COURSE CODE -->

                            <span class="course-code">

                                <i class="bi bi-bookmark"></i>

                                <?php
                                echo htmlspecialchars(
                                    $row['course_code']
                                );
                                ?>

                            </span>


                            <!-- COURSE NAME -->

                            <h4 class="course-title">

                                <?php
                                echo htmlspecialchars(
                                    $row['course_name']
                                );
                                ?>

                            </h4>


                            <hr>


                            <!-- CREDIT -->

                            <div class="info-item">

                                <i class="bi bi-clock"></i>

                                <span>

                                    <strong>
                                        Credit Hours:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['credit_hours']
                                    );
                                    ?>

                                </span>

                            </div>


                            <!-- SEMESTER -->

                            <div class="info-item">

                                <i class="bi bi-calendar3"></i>

                                <span>

                                    <strong>
                                        Semester:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['semester_name']
                                    );
                                    ?>

                                </span>

                            </div>


                            <!-- ACADEMIC YEAR -->

                            <div class="info-item">

                                <i class="bi bi-calendar-event"></i>

                                <span>

                                    <strong>
                                        Academic Year:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['academic_year']
                                    );
                                    ?>

                                </span>

                            </div>


                            <!-- VIEW STUDENTS -->

                            <div class="d-grid mt-4">

                                <a
                                    href="view_students.php?course_id=<?php echo $row['course_id']; ?>&semester_id=<?php echo $row['semester_id']; ?>"
                                    class="btn btn-success">

                                    <i class="bi bi-people-fill"></i>

                                    View Students

                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            <?php

                }

            } else {

            ?>

                <div class="col-12">

                    <div class="empty-state">

                        <i class="bi bi-journal-x"></i>

                        <h4 class="mt-3">
                            No Assigned Courses
                        </h4>

                        <p>
                            <?php
                            if ($search != "") {
                                echo "No courses matched your search.";
                            } else {
                                echo "You currently have no assigned courses.";
                            }
                            ?>
                        </p>

                        <?php if ($search != "") { ?>

                            <a href="index.php"
                               class="btn btn-outline-primary">

                                <i class="bi bi-arrow-counterclockwise"></i>

                                Clear Search

                            </a>

                        <?php } ?>

                    </div>

                </div>

            <?php } ?>

        </div>

    </div>

</div>


<!-- =========================
     FOOTER
========================= -->

<div class="text-center text-muted mt-4">

    <small>

        <i class="bi bi-shield-check"></i>

        University ERP — Instructor Portal

    </small>

</div>


</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>
