<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../../includes/db.php");

$error = "";
$success = "";

if (isset($_POST['add_semester'])) {

    $semester_name =
        mysqli_real_escape_string(
            $conn,
            $_POST['semester_name']
        );

    $academic_year =
        mysqli_real_escape_string(
            $conn,
            $_POST['academic_year']
        );


    if (
        empty($semester_name) ||
        empty($academic_year)
    ) {

        $error = "Please fill in all fields.";

    } else {

        /* Check duplicate */

        $check = mysqli_query(
            $conn,
            "
            SELECT semester_id
            FROM semesters
            WHERE semester_name='$semester_name'
            AND academic_year='$academic_year'
            "
        );


        if (mysqli_num_rows($check) > 0) {

            $error =
                "This semester already exists for this academic year.";

        } else {

            $insert = mysqli_query(
                $conn,
                "
                INSERT INTO semesters
                (
                    semester_name,
                    academic_year
                )
                VALUES
                (
                    '$semester_name',
                    '$academic_year'
                )
                "
            );


            if ($insert) {

                header(
                    "Location: index.php?success=1"
                );

                exit();

            } else {

                $error =
                    "Database error: "
                    . mysqli_error($conn);
            }
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

<title>Add Semester | Campus SIS</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

body {
    background:#f4f7fb;
}

.form-card {
    max-width:700px;
    margin:60px auto;
    border:none;
    border-radius:18px;
    box-shadow:0 8px 30px rgba(0,0,0,.08);
    overflow:hidden;
}

.form-header {
    background:linear-gradient(
        135deg,
        #4f46e5,
        #2563eb
    );
    color:white;
    padding:25px;
}

.form-body {
    padding:30px;
}

.form-label {
    font-weight:600;
}

</style>

</head>


<body>


<div class="card form-card">


    <div class="form-header">

        <h3>

            <i class="bi bi-calendar-plus"></i>

            Add New Semester

        </h3>

        <p class="mb-0">

            Create a new academic semester

        </p>

    </div>


    <div class="form-body">


        <?php if (!empty($error)): ?>

            <div class="alert alert-danger">

                <i
                    class="bi bi-exclamation-triangle-fill">
                </i>

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="mb-4">

                <label class="form-label">

                    Semester Name

                </label>


                <select
                    name="semester_name"
                    class="form-select"
                    required>

                    <option value="">
                        Select Semester
                    </option>

                    <option value="Semester I">
                        Semester I
                    </option>

                    <option value="Semester II">
                        Semester II
                    </option>

                </select>

            </div>


            <div class="mb-4">

                <label class="form-label">

                    Academic Year

                </label>


                <input
                    type="text"
                    name="academic_year"
                    class="form-control"
                    placeholder="Example: 2027/2028"
                    required>

                <div class="form-text">

                    Example format:
                    <strong>2027/2028</strong>

                </div>

            </div>


            <div class="d-flex
                        justify-content-between">


                <a
                    href="index.php"
                    class="btn btn-secondary">

                    <i class="bi bi-arrow-left"></i>

                    Back

                </a>


                <button
                    type="submit"
                    name="add_semester"
                    class="btn btn-primary">

                    <i class="bi bi-save-fill"></i>

                    Save Semester

                </button>


            </div>


        </form>


    </div>

</div>


</body>

</html>