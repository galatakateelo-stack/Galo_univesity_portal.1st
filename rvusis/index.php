<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rift Valley Abichu Campus - Student Information System</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="text-center mb-5">
        <h1 class="text-primary">Rift Valley University Abichu Campus</h1>
        <h3>Student Information System</h3>
        <p class="text-secondary">
            Welcome to the Online Student Portal
        </p>
    </div>

    <div class="row">

        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h3>🎓 Student</h3>
                    <p>View grades and profile</p>

                    <a href="student/student_login.php" class="btn btn-primary w-100">
                        Student Login
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h3>👨‍🏫 Instructor</h3>
                    <p>Upload and manage grades</p>

                    <a href="instructor/login.php" class="btn btn-success w-100">
                        Instructor Login
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h3>👨‍💼 Administrator</h3>
                    <p>Manage the whole system</p>

                    <a href="admin/login.php" class="btn btn-danger w-100">
                        Admin Login
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>