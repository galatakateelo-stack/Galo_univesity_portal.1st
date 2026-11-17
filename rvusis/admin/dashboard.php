
<?php
session_start();

require_once __DIR__ . '/../includes/db.php';

/*
|--------------------------------------------------------------------------
| Admin authentication
|--------------------------------------------------------------------------
| If your existing login uses a different session variable, keep the
| authentication check from your working dashboard.php.
*/
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| Database statistics
|--------------------------------------------------------------------------
*/
$counts = [
    'students'     => 0,
    'instructors'  => 0,
    'courses'      => 0,
    'departments'  => 0,
    'semesters'    => 0,
    'grades'       => 0
];

foreach ($counts as $table => $unused) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");

    if ($result) {
        $row = $result->fetch_assoc();
        $counts[$table] = (int)$row['total'];
    }
}

/*
|--------------------------------------------------------------------------
| Students by department
|--------------------------------------------------------------------------
*/
$departmentData = [];

$sql = "
    SELECT d.department_name, COUNT(s.student_id) AS total_students
    FROM departments d
    LEFT JOIN students s ON s.department_id = d.department_id
    GROUP BY d.department_id, d.department_name
    ORDER BY total_students DESC, d.department_name ASC
";

$departmentResult = $conn->query($sql);

if ($departmentResult) {
    while ($row = $departmentResult->fetch_assoc()) {
        $departmentData[] = $row;
    }
}

$maxStudents = 1;

foreach ($departmentData as $department) {
    $maxStudents = max($maxStudents, (int)$department['total_students']);
}

$adminName = $_SESSION['admin_name']
    ?? $_SESSION['username']
    ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrator Dashboard | Rift Valley University</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        rel="stylesheet"
    >

    <style>
	.university-brand {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 24px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 24px;
}

.university-logo {
    width: 72px;
    height: 72px;
    object-fit: contain;
    flex-shrink: 0;
}

.university-brand-text h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #17365d;
}

.university-brand-text p {
    margin: 5px 0 0;
    font-size: 13px;
    color: #64748b;
}

@media (max-width: 576px) {
    .university-brand {
        padding: 14px;
        gap: 12px;
    }

    .university-logo {
        width: 54px;
        height: 54px;
    }

    .university-brand-text h2 {
        font-size: 17px;
    }

    .university-brand-text p {
        font-size: 11px;
    }
}
        :root {
            --navy: #111827;
            --navy-light: #1f2937;
            --blue: #2563eb;
            --page: #f3f6fb;
            --text: #172033;
            --muted: #64748b;
            --border: #e7edf5;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--page);
            color: var(--text);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        a {
            text-decoration: none;
        }

        .app {
            display: flex;
            height: 100vh;
            min-height: 0;
            overflow: hidden;
        }

        /* SIDEBAR */

        .sidebar {
            width: 280px;
            flex-shrink: 0;
            height: 100vh;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior-y: contain;
            padding: 20px 13px;
            background: var(--navy);
            color: #fff;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 22px 16px 28px;
            margin-bottom: 25px;
            border-bottom: 1px solid #2b3445;
        }

        .brand-icon {
            width: 58px;
            height: 58px;
            border-radius: 17px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            font-size: 25px;
        }

        .brand h2 {
            font-size: 22px;
            font-weight: 800;
            margin: 0 0 4px;
        }

        .brand span {
            color: #aab4c5;
            font-size: 15px;
        }

        .menu-label {
            padding: 15px 17px 9px;
            color: #8b96a8;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 16px;
            min-height: 55px;
            margin: 5px 0;
            padding: 14px 17px;
            border-radius: 13px;
            color: #d9e0eb;
            font-size: 15px;
            transition: .2s ease;
        }

        .nav-link-custom i {
            width: 23px;
            text-align: center;
            font-size: 17px;
        }

        .nav-link-custom:hover {
            background: #202c40;
            color: #fff;
        }

        .nav-link-custom.active {
            color: #fff;
            background: var(--blue);
            box-shadow: 0 7px 18px rgba(37, 99, 235, .2);
        }

        .sidebar-footer {
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #2b3445;
        }

        /* MAIN CONTENT */

        .main {
            flex: 1;
            height: 100vh;
            min-width: 0;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior-y: contain;
            padding: 26px;
        }

        .top-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 30px 34px;
            margin-bottom: 24px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 23px;
            box-shadow: 0 8px 28px rgba(15, 23, 42, .035);
        }

        .top-header h1 {
            margin: 0 0 9px;
            font-size: clamp(26px, 3vw, 39px);
            font-weight: 800;
            letter-spacing: -.7px;
        }

        .top-header p {
            margin: 0 0 9px;
            color: var(--muted);
            font-size: 16px;
        }

        .current-date {
            color: #64748b;
            font-size: 14px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 13px;
            flex-shrink: 0;
        }

        .admin-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            font-size: 21px;
        }

        .admin-profile strong {
            display: block;
            font-size: 15px;
        }

        .admin-profile small {
            display: block;
            margin-top: 4px;
            color: var(--muted);
        }

        /* WELCOME + COMPACT STATISTICS */

        .dashboard-overview {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 22px;
            align-items: stretch;
            margin-bottom: 24px;
        }

        .welcome-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 420px;
            padding: 44px;
            border-radius: 23px;
            color: #fff;
            background: linear-gradient(135deg, #123783 0%, #2563eb 58%, #6941c6 100%);
            box-shadow: 0 13px 32px rgba(37, 99, 235, .15);
        }

        .welcome-panel::before {
            content: "";
            position: absolute;
            width: 290px;
            height: 290px;
            right: -90px;
            top: -105px;
            border: 45px solid rgba(255,255,255,.08);
            border-radius: 50%;
        }

        .welcome-panel::after {
            content: "";
            position: absolute;
            width: 210px;
            height: 210px;
            right: 40px;
            bottom: -150px;
            background: rgba(255,255,255,.08);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            margin-bottom: 25px;
            border-radius: 30px;
            background: rgba(255,255,255,.15);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.1px;
        }

        .welcome-panel h2 {
            margin: 0 0 20px;
            color: #fff;
            font-size: clamp(32px, 3.3vw, 49px);
            line-height: 1.15;
            font-weight: 850;
            letter-spacing: -1px;
        }

        .welcome-panel h2 span {
            color: #fde68a;
        }

        .welcome-panel p {
            max-width: 600px;
            margin-bottom: 26px;
            color: rgba(255,255,255,.9);
            font-size: 16px;
            line-height: 1.8;
        }

        .welcome-date {
            color: rgba(255,255,255,.85);
            font-size: 13px;
        }

        /* RIGHT-SIDE OVERVIEW */

        .stats-panel {
            padding: 22px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 23px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .035);
        }

        .panel-heading {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 18px;
        }

        .panel-heading-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 39px;
            height: 39px;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
        }

        .panel-heading h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .stats-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 13px;
            background: #f7f9fd;
            border: 1px solid #edf1f7;
            border-radius: 12px;
        }

        .stat-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #475569;
            font-size: 13px;
        }

        .stat-label i {
            width: 17px;
            color: #64748b;
            text-align: center;
        }

        .stat-number {
            color: #1d4ed8;
            font-size: 22px;
            font-weight: 850;
            font-variant-numeric: tabular-nums;
        }

        /* LOWER CONTENT PANELS */

        .lower-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(280px, .9fr);
            gap: 22px;
        }

        .content-panel {
            min-width: 0;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .035);
        }

        .content-panel-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 23px 25px;
            border-bottom: 1px solid var(--border);
        }

        .content-panel-header i {
            color: #2563eb;
            font-size: 20px;
        }

        .content-panel-header h3 {
            margin: 0;
            font-size: 19px;
            font-weight: 800;
        }

        .department-list {
            padding: 8px 25px 22px;
        }

        .department-row {
            padding: 15px 0;
            border-bottom: 1px solid #edf1f7;
        }

        .department-row:last-child {
            border-bottom: none;
        }

        .department-info {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 9px;
            font-size: 13px;
        }

        .department-info strong {
            font-weight: 700;
        }

        .department-info span {
            color: #64748b;
        }

        .progress-track {
            height: 8px;
            overflow: hidden;
            border-radius: 20px;
            background: #edf2f8;
        }

        .progress-bar-custom {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(90deg, #2563eb, #7c3aed);
        }

        .quick-actions {
            padding: 20px;
        }

        .action-link {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 14px 12px;
            margin-bottom: 9px;
            border: 1px solid #edf1f7;
            border-radius: 13px;
            color: #334155;
            transition: .2s ease;
        }

        .action-link:last-child {
            margin-bottom: 0;
        }

        .action-link:hover {
            border-color: #bfdbfe;
            background: #f5f8ff;
            color: #1d4ed8;
            transform: translateY(-1px);
        }

        .action-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 39px;
            height: 39px;
            flex-shrink: 0;
            border-radius: 11px;
            background: #eff6ff;
            color: #2563eb;
        }

        .action-link strong {
            display: block;
            font-size: 13px;
        }

        .action-link small {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 11px;
        }

        .action-arrow {
            margin-left: auto;
            color: #94a3b8;
        }

        .footer {
            padding: 25px 5px 8px;
            color: #94a3b8;
            text-align: center;
            font-size: 12px;
        }

        /* RESPONSIVE LAYOUT */

        @media (max-width: 1200px) {
            .sidebar {
                width: 245px;
            }

            .main {
                padding: 20px;
            }

            .dashboard-overview {
                grid-template-columns: minmax(0, 1fr) 275px;
            }

            .welcome-panel {
                padding: 32px;
            }
        }

        @media (max-width: 950px) {
            .dashboard-overview,
            .lower-grid {
                grid-template-columns: 1fr;
            }

            .welcome-panel {
                min-height: 330px;
            }

            .stats-list {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .app {
                display: flex;
                flex-direction: column;
                height: 100vh;
                min-height: 0;
                overflow: hidden;
            }

            .sidebar {
                width: 100%;
                flex: 0 0 auto;
                height: auto;
                max-height: 42vh;
                min-height: 0;
                overflow-y: auto;
                overscroll-behavior-y: contain;
            }

            .brand {
                margin-bottom: 12px;
            }

            .main {
                flex: 1;
                width: 100%;
                height: auto;
                min-height: 0;
                overflow-y: auto;
                overscroll-behavior-y: contain;
                padding: 14px;
            }

            .top-header {
                align-items: flex-start;
                padding: 22px;
            }

            .admin-profile {
                display: none;
            }

            .welcome-panel {
                min-height: 340px;
                padding: 27px;
            }

            .welcome-panel h2 {
                font-size: 34px;
            }

            .stats-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <div class="brand-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>

            <div>
                <h2>Campus SIS</h2>
                <span>Administration</span>
            </div>
        </div>

        <div class="menu-label">Main Menu</div>

        <a href="dashboard.php" class="nav-link-custom active">
            <i class="fa-solid fa-table-cells-large"></i>
            Dashboard
        </a>

        <a href="students/index.php" class="nav-link-custom">
            <i class="fa-solid fa-users"></i>
            Students
        </a>

        <a href="password_reset_requests.php" class="nav-link-custom">
            <i class="fa-solid fa-key"></i>
            Password Requests
        </a>

        <a href="admin_password_reset_requests.php" class="nav-link-custom">
            <i class="fa-solid fa-user-shield"></i>
            Admin Recovery Requests
        </a>

        <a href="instructors/index.php" class="nav-link-custom">
            <i class="fa-solid fa-chalkboard-user"></i>
            Instructors
        </a>

        <a href="courses/index.php" class="nav-link-custom">
            <i class="fa-solid fa-book-open"></i>
            Courses
        </a>

        <a href="departments/index.php" class="nav-link-custom">
            <i class="fa-solid fa-building"></i>
            Departments
        </a>

        <a href="semesters/index.php" class="nav-link-custom">
            <i class="fa-solid fa-calendar-days"></i>
            Semesters
        </a>

        <div class="menu-label">Academic Management</div>

        <a href="instructor_courses/index.php" class="nav-link-custom">
            <i class="fa-solid fa-user-plus"></i>
            Course Assignments
        </a>

        <a href="grades/index.php" class="nav-link-custom">
            <i class="fa-solid fa-chart-column"></i>
            Grades
        </a>

        <a href="reports.php" class="nav-link-custom">
            <i class="fa-solid fa-file-lines"></i>
            Reports
        </a>
<a href="change_password.php" class="nav-link-custom">
    <i class="fa-solid fa-key"></i>
    Change Password
</a>

        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link-custom">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">

        <!-- HEADER -->
        <header class="top-header">

            <div>
                <h1>Administrator Dashboard</h1>
                <div class="current-date">
                    <i class="fa-regular fa-calendar me-2"></i>
                    <?php echo date('l, d F Y'); ?>
                </div>
            </div>
<div class="university-brand">
    <img
    src="../assets/rvu.logo.webp"
    alt="Rift Valley University Logo"
    class="university-logo"
>

    <div class="university-brand-text">
        <h2>Rift Valley University</h2>
        <p>Abichu Campus Student Information System</p>
    </div>
</div>
            <div class="admin-profile">
                <div class="admin-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div>
                    <strong><?php echo e($adminName); ?></strong>
                    <small>System Administrator</small>
                </div>
            </div>

        </header>

        <!-- WELCOME PANEL + COMPACT OVERVIEW -->
        <section class="dashboard-overview">

            <div class="welcome-panel">
                <div class="welcome-content">

                    <div class="welcome-badge">
                        <i class="fa-solid fa-graduation-cap"></i>
                        CAMPUS MANAGEMENT SYSTEM
                    </div>

                    <h2>
                        Welcome to<br>
                        <span>Rift Valley University</span>
                    </h2>

                    <p>
                        Empowering education through excellence, innovation,
                        and technology. Manage students, instructors, courses,
                        academic records, and university activities in one place.
                    </p>

                </div>
            </div>

            <!-- COMPACT STATISTICS -->
            <aside class="stats-panel">

                <div class="panel-heading">
                    <div class="panel-heading-icon">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>

                    <h3>University Overview</h3>
                </div>

                <div class="stats-list">

                    <div class="stat-item">
                        <span class="stat-label">
                            <i class="fa-solid fa-users"></i>
                            Students
                        </span>
                        <span class="stat-number">
                            <?php echo $counts['students']; ?>
                        </span>
                    </div>

                    <div class="stat-item">
                        <span class="stat-label">
                            <i class="fa-solid fa-chalkboard-user"></i>
                            Instructors
                        </span>
                        <span class="stat-number">
                            <?php echo $counts['instructors']; ?>
                        </span>
                    </div>

                    <div class="stat-item">
                        <span class="stat-label">
                            <i class="fa-solid fa-book-open"></i>
                            Courses
                        </span>
                        <span class="stat-number">
                            <?php echo $counts['courses']; ?>
                        </span>
                    </div>

                    <div class="stat-item">
                        <span class="stat-label">
                            <i class="fa-solid fa-building"></i>
                            Departments
                        </span>
                        <span class="stat-number">
                            <?php echo $counts['departments']; ?>
                        </span>
                    </div>

                    <div class="stat-item">
                        <span class="stat-label">
                            <i class="fa-solid fa-calendar-days"></i>
                            Semesters
                        </span>
                        <span class="stat-number">
                            <?php echo $counts['semesters']; ?>
                        </span>
                    </div>

                    <div class="stat-item">
                        <span class="stat-label">
                            <i class="fa-solid fa-chart-column"></i>
                            Grade Records
                        </span>
                        <span class="stat-number">
                            <?php echo $counts['grades']; ?>
                        </span>
                    </div>

                </div>

            </aside>

        </section>

        <!-- LOWER DASHBOARD SECTIONS -->
        <section class="lower-grid">

            <!-- STUDENTS BY DEPARTMENT -->
            <div class="content-panel">

                <div class="content-panel-header">
                    <i class="fa-solid fa-chart-column"></i>
                    <h3>Students by Department</h3>
                </div>

                <div class="department-list">

                    <?php if (empty($departmentData)): ?>

                        <p class="text-secondary py-4 mb-0">
                            No department records are available yet.
                        </p>

                    <?php else: ?>

                        <?php foreach ($departmentData as $department): ?>

                            <?php
                            $studentTotal = (int)$department['total_students'];

                            $percentage = ($studentTotal / $maxStudents) * 100;
                            ?>

                            <div class="department-row">

                                <div class="department-info">
                                    <strong>
                                        <?php echo e($department['department_name']); ?>
                                    </strong>

                                    <span>
                                        <?php echo $studentTotal; ?>
                                        student<?php echo $studentTotal === 1 ? '' : 's'; ?>
                                    </span>
                                </div>

                                <div class="progress-track">
                                    <div
                                        class="progress-bar-custom"
                                        style="width: <?php echo $percentage; ?>%;"
                                    ></div>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>
			

            <!-- QUICK ACTIONS -->
            <div class="content-panel">

                <div class="content-panel-header">
                    <i class="fa-solid fa-bolt"></i>
                    <h3>Quick Actions</h3>
                </div>

                <div class="quick-actions">

                    <a href="students/add.php" class="action-link">
                        <span class="action-icon">
                            <i class="fa-solid fa-user-plus"></i>
                        </span>

                        <span>
                            <strong>Register Student</strong>
                            <small>Add a new student record</small>
                        </span>

                        <i class="fa-solid fa-chevron-right action-arrow"></i>
                    </a>

                    <a href="students/index.php" class="action-link">
                        <span class="action-icon">
                            <i class="fa-solid fa-users"></i>
                        </span>

                        <span>
                            <strong>Manage Students</strong>
                            <small>View student information</small>
                        </span>

                        <i class="fa-solid fa-chevron-right action-arrow"></i>
                    </a>

                    <a href="instructors/index.php" class="action-link">
                        <span class="action-icon">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </span>

                        <span>
                            <strong>Manage Instructors</strong>
                            <small>View instructor records</small>
                        </span>

                        <i class="fa-solid fa-chevron-right action-arrow"></i>
                    </a>

                    <a href="courses/index.php" class="action-link">
                        <span class="action-icon">
                            <i class="fa-solid fa-book-open"></i>
                        </span>

                        <span>
                            <strong>Manage Courses</strong>
                            <small>Review available courses</small>
                        </span>

                        <i class="fa-solid fa-chevron-right action-arrow"></i>
                    </a>

                    <a href="grades/index.php" class="action-link">
                        <span class="action-icon">
                            <i class="fa-solid fa-chart-column"></i>
                        </span>

                        <span>
                            <strong>Grade Records</strong>
                            <small>Review academic results</small>
                        </span>

                        <i class="fa-solid fa-chevron-right action-arrow"></i>
                    </a>

                </div>

            </div>

        </section>

        <footer class="footer">
            &copy; <?php echo date('Y'); ?> Rift Valley University —
            Student Information System
        </footer>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
