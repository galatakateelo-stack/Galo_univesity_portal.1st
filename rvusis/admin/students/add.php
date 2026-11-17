<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

function addStudentEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function addStudentPostText($key)
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function addStudentTextLength($value)
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? PHP_INT_MAX : $count;
}

if (empty($_SESSION['add_student_csrf'])) {
    $_SESSION['add_student_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = $_SESSION['add_student_notice'] ?? '';
unset($_SESSION['add_student_notice']);

$registrationNo = '';
$firstName = '';
$lastName = '';
$gender = '';
$departmentId = 0;
$username = '';

$departments = [];
$departmentIds = [];
$departmentResult = mysqli_query(
    $conn,
    'SELECT department_id, department_name FROM departments ORDER BY department_name ASC'
);

if ($departmentResult) {
    while ($department = mysqli_fetch_assoc($departmentResult)) {
        $department['department_id'] = (int)$department['department_id'];
        $departments[] = $department;
        $departmentIds[] = $department['department_id'];
    }
    mysqli_free_result($departmentResult);
} else {
    $error = 'Departments could not be loaded. Please try again later.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedCsrf) || !hash_equals($_SESSION['add_student_csrf'], $submittedCsrf)) {
        $error = 'Your form session expired. Refresh the page and try again.';
    } else {
        $registrationNo = addStudentPostText('registration_no');
        $firstName = addStudentPostText('first_name');
        $lastName = addStudentPostText('last_name');
        $gender = addStudentPostText('gender');
        $username = addStudentPostText('username');
        $departmentValue = $_POST['department_id'] ?? '';
        $departmentId = filter_var($departmentValue, FILTER_VALIDATE_INT);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!is_string($password)) {
            $password = '';
        }
        if (!is_string($confirmPassword)) {
            $confirmPassword = '';
        }

        $passwordLength = function_exists('mb_strlen')
            ? mb_strlen($password, 'UTF-8')
            : strlen($password);

        if (
            $registrationNo === '' || $firstName === '' || $lastName === '' ||
            $gender === '' || $username === '' || $password === '' ||
            $confirmPassword === '' || $departmentId === false
        ) {
            $error = 'Please complete all required fields.';
        } elseif (addStudentTextLength($registrationNo) > 30) {
            $error = 'Registration number must be 30 characters or fewer.';
        } elseif (addStudentTextLength($firstName) > 50 || addStudentTextLength($lastName) > 50) {
            $error = 'First and last names must be 50 characters or fewer.';
        } elseif (
            preg_match("~^[\\p{L}\\p{M}][\\p{L}\\p{M}\\s'-]*$~u", $firstName) !== 1 ||
            preg_match("~^[\\p{L}\\p{M}][\\p{L}\\p{M}\\s'-]*$~u", $lastName) !== 1
        ) {
            $error = 'Names may contain letters, spaces, apostrophes, and hyphens.';
        } elseif (!in_array($gender, ['Male', 'Female'], true)) {
            $error = 'Select a valid gender.';
        } elseif (!in_array((int)$departmentId, $departmentIds, true)) {
            $error = 'Select a valid department.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $error = 'Username must be 3–50 characters and use letters, numbers, dots, underscores, or hyphens.';
        } elseif ($passwordLength < 6) {
            $error = 'The initial password must contain at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'The passwords do not match.';
        } else {
            try {
                $duplicate = mysqli_prepare(
                    $conn,
                    'SELECT student_id FROM students WHERE registration_no = ? LIMIT 1'
                );
                if (!$duplicate) {
                    throw new RuntimeException('Unable to check registration number.');
                }
                mysqli_stmt_bind_param($duplicate, 's', $registrationNo);
                mysqli_stmt_execute($duplicate);
                mysqli_stmt_store_result($duplicate);
                $registrationExists = mysqli_stmt_num_rows($duplicate) > 0;
                mysqli_stmt_close($duplicate);

                if ($registrationExists) {
                    $error = 'That registration number is already in use.';
                } else {
                    $duplicate = mysqli_prepare(
                        $conn,
                        'SELECT student_id FROM students WHERE username = ? LIMIT 1'
                    );
                    if (!$duplicate) {
                        throw new RuntimeException('Unable to check username.');
                    }
                    mysqli_stmt_bind_param($duplicate, 's', $username);
                    mysqli_stmt_execute($duplicate);
                    mysqli_stmt_store_result($duplicate);
                    $usernameExists = mysqli_stmt_num_rows($duplicate) > 0;
                    mysqli_stmt_close($duplicate);

                    if ($usernameExists) {
                        $error = 'That username is already in use. Choose another username.';
                    } else {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        if ($passwordHash === false) {
                            throw new RuntimeException('Unable to hash initial password.');
                        }

                        $insert = mysqli_prepare(
                            $conn,
                            'INSERT INTO students
                                (registration_no, first_name, last_name, gender, department_id, username, password)
                             VALUES (?, ?, ?, ?, ?, ?, ?)'
                        );
                        if (!$insert) {
                            throw new RuntimeException('Unable to prepare student insert.');
                        }

                        $departmentId = (int)$departmentId;
                        mysqli_stmt_bind_param(
                            $insert,
                            'ssssiss',
                            $registrationNo,
                            $firstName,
                            $lastName,
                            $gender,
                            $departmentId,
                            $username,
                            $passwordHash
                        );

                        if (!mysqli_stmt_execute($insert)) {
                            mysqli_stmt_close($insert);
                            throw new RuntimeException('Student insert failed.');
                        }
                        mysqli_stmt_close($insert);

                        $_SESSION['add_student_notice'] = 'Student account created. Give the student their username and initial password securely.';
                        header('Location: add.php', true, 303);
                        exit;
                    }
                }
            } catch (Throwable $exception) {
                error_log('Student account creation failed: ' . $exception->getMessage());
                $error = 'The student account could not be saved. Check the entered details and try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102a43">
    <title>Add Student | RVU SIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: #f2f6fa; color: #243b53; font-family: Inter, "Segoe UI", Arial, sans-serif; }
        .page { width: min(980px, calc(100% - 32px)); margin: 34px auto 55px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 25px 28px; margin-bottom: 22px; color: #fff; background: linear-gradient(135deg, #17365d, #28708f); border-radius: 17px; box-shadow: 0 12px 30px rgba(23,54,93,.16); }
        .page-header h1 { margin: 0 0 7px; font-size: clamp(24px, 4vw, 31px); font-weight: 750; letter-spacing: -.03em; }
        .page-header p { margin: 0; color: rgba(255,255,255,.82); font-size: 14px; }
        .card { border: 1px solid #e0e9ef; border-radius: 16px; box-shadow: 0 8px 28px rgba(28,54,77,.06); }
        .card-body { padding: clamp(22px, 5vw, 38px); }
        .form-section { padding: 21px; margin-bottom: 21px; border: 1px solid #e6edf2; border-radius: 13px; background: #fbfcfd; }
        .section-title { margin: 0 0 19px; color: #274760; font-size: 17px; font-weight: 700; }
        .section-title i { margin-right: 8px; color: #28708f; }
        .form-label { margin-bottom: 7px; color: #344f66; font-size: 14px; font-weight: 650; }
        .required { color: #c2413a; }
        .form-control, .form-select { min-height: 47px; border-color: #d6e0e8; border-radius: 9px; }
        .form-control:focus, .form-select:focus { border-color: #3b82a0; box-shadow: 0 0 0 .2rem rgba(59,130,160,.16); }
        .help { margin-top: 6px; color: #718397; font-size: 12px; line-height: 1.5; }
        .notice { padding: 13px 15px; border-radius: 10px; background: #eff6fa; color: #526b80; font-size: 13px; line-height: 1.6; }
        .footer { margin-top: 23px; color: #718397; text-align: center; font-size: 12px; }
        @media (max-width: 560px) { .page-header { align-items: flex-start; flex-direction: column; } }
    </style>
</head>
<body>
    <main class="page">
        <header class="page-header">
            <div>
                <h1><i class="bi bi-person-plus-fill me-2"></i>Add New Student</h1>
                <p>Create the student record and login credentials.</p>
            </div>
            <a href="index.php" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Students</a>
        </header>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success" role="status"><i class="bi bi-check-circle-fill me-2"></i><?= addStudentEscape($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= addStudentEscape($error) ?></div>
        <?php endif; ?>

        <section class="card">
            <div class="card-body">
                <form method="post" action="add.php" autocomplete="on">
                    <input type="hidden" name="csrf_token" value="<?= addStudentEscape($_SESSION['add_student_csrf']) ?>">

                    <div class="form-section">
                        <h2 class="section-title"><i class="bi bi-person-vcard"></i>Student information</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="registration_no" class="form-label">Registration number <span class="required">*</span></label>
                                <input type="text" id="registration_no" name="registration_no" class="form-control" maxlength="30" value="<?= addStudentEscape($registrationNo) ?>" placeholder="e.g. RU1234/20" required>
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender <span class="required">*</span></label>
                                <select id="gender" name="gender" class="form-select" required>
                                    <option value="">Select gender</option>
                                    <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control" maxlength="50" value="<?= addStudentEscape($firstName) ?>" autocomplete="given-name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control" maxlength="50" value="<?= addStudentEscape($lastName) ?>" autocomplete="family-name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="department_id" class="form-label">Department <span class="required">*</span></label>
                                <select id="department_id" name="department_id" class="form-select" required>
                                    <option value="">Select department</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?= (int)$department['department_id'] ?>" <?= (int)$departmentId === (int)$department['department_id'] ? 'selected' : '' ?>><?= addStudentEscape($department['department_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="section-title"><i class="bi bi-shield-lock"></i>Student login</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username <span class="required">*</span></label>
                                <input type="text" id="username" name="username" class="form-control" maxlength="50" minlength="3" pattern="[A-Za-z0-9._-]{3,50}" value="<?= addStudentEscape($username) ?>" autocomplete="username" required>
                                <div class="help">The student will use this username to sign in.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Initial password <span class="required">*</span></label>
                                <input type="password" id="password" name="password" class="form-control" minlength="12" autocomplete="new-password" required>
                                <div class="help">Use at least 12 characters. The password is stored as a secure hash.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm initial password <span class="required">*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="12" autocomplete="new-password" required>
                            </div>
                        </div>
                    </div>

                    <div class="notice mb-4"><strong>After creating the account:</strong> give the student their username and initial password securely, then ask them to change the password after their first sign-in.</div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancel</a>
                        <button type="submit" name="save_student" value="1" class="btn btn-primary px-4"><i class="bi bi-person-plus-fill me-1"></i>Create student account</button>
                    </div>
                </form>
            </div>
        </section>

        <footer class="footer">Student Information System · &copy; <?= date('Y') ?></footer>
    </main>
</body>
</html>
