<?php
session_start();
require_once "conn.php";

$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Handle profile image upload
    $profile_img_path = null;
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_img']['tmp_name'];
        $fileName = $_FILES['profile_img']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $profile_img_path = $dest_path;
                $_SESSION['profile_img'] = $profile_img_path; // update session
            }
        }
    }

    // Update details including additional user information
    if ($profile_img_path) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET fullname = :fullname, email = :email, phone = :phone, address = :address, profile_img = :profile_img
            WHERE id = :user_id
        ");
        $stmt->execute([
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'profile_img' => $profile_img_path,
            'user_id' => $_SESSION['user_id']
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET fullname = :fullname, email = :email, phone = :phone, address = :address
            WHERE id = :user_id
        ");
        $stmt->execute([
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'user_id' => $_SESSION['user_id']
        ]);
    }

    // Handle password update if user entered a new one
    if (!empty($_POST['new_password'])) {
        $new_password_hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $stmt_pass = $pdo->prepare("
            UPDATE users 
            SET password = :password 
            WHERE id = :user_id
        ");
        $stmt_pass->execute([
            'password' => $new_password_hash,
            'user_id' => $_SESSION['user_id']
        ]);
    }

    $success = "Profile updated successfully!";
}

// Fetch the latest details of the logged-in user
$stmt = $pdo->prepare("SELECT id, fullname, email, user_type, is_active, phone, address, profile_img FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $_SESSION["user_id"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$fullname = $user['fullname'];
$email = $user['email'];
$user_type = ucfirst($user['user_type']);
$status = $user['is_active'] == 1 ? 'Active' : 'Inactive';
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';
$profile_img = $user['profile_img'] ?? '../uploads/default_avatar.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Adopter Profile | FurEver Pawmily</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
<style>
body { 
    font-family: 'Poppins', sans-serif; 
    margin: 0; 
    background: #fffaf5; 
}

.container { 
    max-width: 900px; 
    margin: 40px auto; 
    padding: 20px; 
}

header { 
    background: #7ca982; 
    color: white; 
    padding: 25px; 
    border-radius: 12px; 
    margin-bottom: 30px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    box-shadow: 0 4px 6px rgba(0,0,0,0.08);
}
header h1 { margin: 0; font-size: 26px; }
.back-btn {
    text-decoration: none;
    color: #4b7a54;
    background: #fff;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
}
.back-btn:hover { 
    background: #e1f0e3; 
    transform: translateY(-2px); 
}

.profile-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 25px;
}

.profile-left {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
.profile-avatar-wrapper {
    position: relative;
    margin-bottom: 20px;
}
.profile-avatar {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #fffaf5;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}
.profile-avatar-upload {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: #7ca982;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: background 0.2s;
}
.profile-avatar-upload:hover {
    background: #5a7d60;
}
.profile-left h2 { 
    margin: 10px 0 5px 0; 
    color: #333; 
    font-size: 20px; 
}
.profile-left p { 
    color: #7ca982; 
    font-weight: 500; 
    margin: 0 0 15px 0; 
    font-size: 14px; 
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    background: #e9ecef;
    color: #495057;
    margin-bottom: 25px;
}
.badge-active {
    background: #d4edda;
    color: #155724;
}

.profile-right {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.06);
}
.profile-group {
    margin-bottom: 18px;
}
.profile-group label {
    font-weight: 600;
    color: #7ca982;
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
}
.profile-group input {
    font-size: 14px;
    color: #333;
    width: 100%;
    padding: 11px;
    box-sizing: border-box;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fafafa;
    transition: border 0.2s;
}
.profile-group input:focus {
    border-color: #7ca982;
    outline: none;
    background: #fff;
}

.alert {
    padding: 12px;
    background: #d4edda;
    color: #155724;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}
.alert-danger {
    padding: 12px;
    background: #f8d7da;
    color: #721c24;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.save-btn {
    background: #7ca982;
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: opacity 0.2s, background-color 0.2s;
    margin-top: 10px;
}
.save-btn:hover {
    background: #5a7d60;
}

@media(max-width: 768px) {
    .profile-container { grid-template-columns: 1fr; }
    .container { padding: 15px; }
    header { flex-direction: column; gap: 12px; align-items: flex-start; }
}
</style>
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1>Adopter Profile Information</h1>
            <p>Manage your account settings</p>
        </div>
        <a href="adopter_dashboard.php" class="back-btn">&larr; Back</a>
    </header>

    <?php if ($success): ?>
        <div class="alert"><?= $success; ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="profile-container">
        
        <div class="profile-left">
            <div class="profile-avatar-wrapper">
                <img src="<?= htmlspecialchars($profile_img) . '?' . time(); ?>" alt="Profile Picture" class="profile-avatar" id="profileImagePreview">
                <label for="profile_img" class="profile-avatar-upload" title="Change Photo">
                    &#9998;
                </label>
                <input type="file" id="profile_img" name="profile_img" accept="image/*" onchange="previewImage(event)" style="display: none;">
            </div>
            
            <h2><?= htmlspecialchars($fullname); ?></h2>
            <p><?= htmlspecialchars($user_type); ?></p>
            
            <span class="badge badge-active">Account Status: <?= htmlspecialchars($status); ?></span>

            <p style="font-size: 12px; color: #777; margin-top: auto; line-height: 1.4;">
                Update your photo and account details to keep your profile updated.
            </p>
        </div>

        <div class="profile-right">
            <h3>Personal Information</h3>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 12px 0 20px 0;">

            <div class="profile-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($fullname); ?>" required>
            </div>
            
            <div class="profile-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>" required>
            </div>

            <div class="profile-group">
                <label for="phone">Contact Number</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone); ?>" placeholder="09123456789" required>
            </div>

            <div class="profile-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?= htmlspecialchars($address); ?>" placeholder="Street, City, Province" required>
            </div>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 25px 0;">

            <h3>Change Password</h3>
            <p style="font-size:12px; color:#777; margin-bottom:15px;">Leave blank if you don't want to change your password.</p>
            
            <div class="profile-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="••••••••">
            </div>

            <button type="submit" class="save-btn">Save Changes</button>
        </div>
    </form>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const output = document.getElementById('profileImagePreview');
            output.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}
</script>

</body>
</html>