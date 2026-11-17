<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../includes/db.php");

$admin_id = (int) $_SESSION['admin_id'];

$message = "";
$message_type = "";

/* Get administrator */
$sql = "SELECT * FROM admins WHERE admin_id = '$admin_id' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Administrator account not found.");
}

$admin = mysqli_fetch_assoc($result);


/* =========================
   UPLOAD PROFILE PHOTO
========================= */

if (isset($_POST['upload_photo'])) {

    if (
        !isset($_FILES['profile_photo']) ||
        $_FILES['profile_photo']['error'] != UPLOAD_ERR_OK
    ) {

        $message = "Please select a valid image.";
        $message_type = "danger";

    } else {

        $file = $_FILES['profile_photo'];

        $file_tmp  = $file['tmp_name'];
        $file_size = $file['size'];
        $file_name = $file['name'];

        /* Maximum 2 MB */
        $max_size = 2 * 1024 * 1024;

        /* Allowed formats */
        $allowed = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];

        $extension = strtolower(
            pathinfo($file_name, PATHINFO_EXTENSION)
        );


        /* Check extension */

        if (!in_array($extension, $allowed)) {

            $message =
                "Invalid image format. Use JPG, JPEG, PNG or WEBP.";

            $message_type = "danger";

        }

        /* Check file size */

        elseif ($file_size > $max_size) {

            $message =
                "Image is too large. Maximum size is 2 MB.";

            $message_type = "danger";

        }

        /* Check actual image */

        elseif (getimagesize($file_tmp) === false) {

            $message =
                "The uploaded file is not a valid image.";

            $message_type = "danger";

        }

        else {

            /* Upload directory */

            $upload_dir = "../assets/uploads/admin/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }


            /* Unique filename */

            $new_filename =
                "admin_" .
                $admin_id .
                "_" .
                bin2hex(random_bytes(8)) .
                "." .
                $extension;


            $destination =
                $upload_dir . $new_filename;


            /* Move image */

            if (move_uploaded_file(
                $file_tmp,
                $destination
            )) {


                /* Delete previous image */

                if (!empty($admin['profile_photo'])) {

                    $old_photo =
                        "../" .
                        ltrim(
                            $admin['profile_photo'],
                            "/"
                        );

                    if (
                        file_exists($old_photo) &&
                        is_file($old_photo)
                    ) {
                        unlink($old_photo);
                    }
                }


                /* Database path */

                $photo_path =
                    "assets/uploads/admin/" .
                    $new_filename;

                $photo_path =
                    mysqli_real_escape_string(
                        $conn,
                        $photo_path
                    );


                /* Update database */

                $update = mysqli_query(
                    $conn,
                    "
                    UPDATE admins
                    SET profile_photo = '$photo_path'
                    WHERE admin_id = '$admin_id'
                    "
                );


                if ($update) {

                    $message =
                        "Profile photo updated successfully.";

                    $message_type = "success";


                    /* Refresh data */

                    $result = mysqli_query(
                        $conn,
                        "
                        SELECT *
                        FROM admins
                        WHERE admin_id = '$admin_id'
                        LIMIT 1
                        "
                    );

                    $admin =
                        mysqli_fetch_assoc($result);

                } else {

                    /* Remove image if DB update fails */

                    if (file_exists($destination)) {
                        unlink($destination);
                    }

                    $message =
                        "Database error: " .
                        mysqli_error($conn);

                    $message_type = "danger";
                }

            } else {

                $message =
                    "Unable to upload the image.";

                $message_type = "danger";
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

<title>Administrator Profile</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<style>

body {
    background: #f4f7fb;
    font-family: Arial, sans-serif;
}

.profile-container {
    max-width: 850px;
    margin: 50px auto;
    padding: 0 20px;
}

.profile-card {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 35px rgba(0,0,0,.10);
}

.profile-header {
    background: linear-gradient(
        135deg,
        #4f46e5,
        #2563eb
    );

    color: white;
    text-align: center;
    padding: 35px;
}

.profile-header h2 {
    font-weight: 700;
}

.photo-section {
    text-align: center;
    padding: 35px;
}

.profile-photo {
    width: 170px;
    height: 170px;
    object-fit: cover;
    border-radius: 50%;
    border: 6px solid white;
    box-shadow: 0 5px 20px rgba(0,0,0,.15);
}

.default-avatar {
    width: 170px;
    height: 170px;
    margin: auto;

    border-radius: 50%;

    background: #e5e7eb;

    display: flex;
    align-items: center;
    justify-content: center;

    box-shadow: 0 5px 20px rgba(0,0,0,.15);
}

.default-avatar i {
    font-size: 80px;
    color: #6b7280;
}

.upload-box {
    background: #f8fafc;
    border-radius: 15px;
    padding: 25px;
}

.info-box {
    background: #f8fafc;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 15px;
}

.info-label {
    font-size: 12px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
}

.info-value {
    font-size: 17px;
    font-weight: 600;
}

</style>

</head>


<body>


<div class="profile-container">


<div class="card profile-card">


<!-- HEADER -->

<div class="profile-header">

    <i
        class="bi bi-person-badge-fill"
        style="font-size:45px;">
    </i>

    <h2 class="mt-2">
        Administrator Profile
    </h2>

    <p class="mb-0">
        Manage your profile photo and account
    </p>

</div>


<!-- PROFILE PHOTO -->

<div class="photo-section">


<?php if (!empty($admin['profile_photo'])): ?>

<?php

$photo =
    "../" .
    ltrim(
        $admin['profile_photo'],
        "/"
    );

?>

<img
    src="<?php echo htmlspecialchars($photo); ?>"
    class="profile-photo"
    alt="Profile Photo">

<?php else: ?>

<div class="default-avatar">

    <i class="bi bi-person-fill"></i>

</div>

<?php endif; ?>


<h4 class="mt-4 mb-1">
    Administrator
</h4>

<p class="text-muted">
    System Administrator
</p>

</div>


<div class="px-4 pb-4">


<!-- MESSAGE -->

<?php if (!empty($message)): ?>

<div class="alert alert-<?php echo $message_type; ?>">

<?php if ($message_type == "success"): ?>

<i class="bi bi-check-circle-fill"></i>

<?php else: ?>

<i class="bi bi-exclamation-triangle-fill"></i>

<?php endif; ?>

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>


<!-- UPLOAD -->

<div class="upload-box mb-4">

<h5 class="mb-3">

<i class="bi bi-camera-fill"></i>

Change Profile Photo

</h5>


<form
method="POST"
enctype="multipart/form-data">


<div class="mb-3">

<input
type="file"
name="profile_photo"
class="form-control"
accept=".jpg,.jpeg,.png,.webp"
required>

<div class="form-text">

JPG, JPEG, PNG or WEBP.
Maximum size: 2 MB.

</div>

</div>


<button
type="submit"
name="upload_photo"
class="btn btn-primary">

<i class="bi bi-cloud-arrow-up-fill"></i>

Upload Photo

</button>


</form>

</div>


<!-- ACCOUNT INFORMATION -->

<h5 class="mb-3">

<i class="bi bi-person-lines-fill"></i>

Account Information

</h5>


<div class="row">


<div class="col-md-6">

<div class="info-box">

<div class="info-label">
Administrator ID
</div>

<div class="info-value">

<?php
echo htmlspecialchars(
    $admin['admin_id']
);
?>

</div>

</div>

</div>


<div class="col-md-6">

<div class="info-box">

<div class="info-label">
Username
</div>

<div class="info-value">

<?php

echo htmlspecialchars(
    $admin['username'] ?? "N/A"
);

?>

</div>

</div>

</div>


</div>


<!-- NAVIGATION -->

<div class="d-flex
            justify-content-between
            mt-4">


<a
href="dashboard.php"
class="btn btn-secondary">

<i class="bi bi-arrow-left"></i>

Dashboard

</a>


<a
href="logout.php"
class="btn btn-danger">

<i class="bi bi-box-arrow-right"></i>

Logout

</a>


</div>


</div>

</div>


<div class="text-center text-muted mt-4">

<small>

<i class="bi bi-shield-check"></i>

Campus SIS — Administrator Profile

</small>

</div>


</div>


</body>

</html>