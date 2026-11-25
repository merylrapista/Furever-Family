<?php
session_start();
require_once "conn.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION["user_id"];

try {
    // === STATS ===
    $petsAvailable = $pdo->query("SELECT COUNT(*) FROM pets WHERE status='available'")->fetchColumn();
    $activeRequests = $pdo->prepare("SELECT COUNT(*) FROM adoption_requests WHERE user_id=:uid AND status='pending'");
    $activeRequests->execute(['uid'=>$user_id]);
    $activeRequests = $activeRequests->fetchColumn();

    $adoptedPets = $pdo->prepare("SELECT COUNT(*) FROM adoption_requests WHERE user_id=:uid AND status='approved'");
    $adoptedPets->execute(['uid'=>$user_id]);
    $adoptedPets = $adoptedPets->fetchColumn();

    $totalNotifs = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=:uid");
    $totalNotifs->execute(['uid'=>$user_id]);
    $totalNotifs = $totalNotifs->fetchColumn();

    // === RECENT ACTIVITIES ===
    $activityStmt = $pdo->prepare("SELECT message, created_at FROM notifications WHERE user_id=:uid ORDER BY created_at DESC LIMIT 5");
    $activityStmt->execute(['uid'=>$user_id]);
    $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

    // === FEATURED PETS ===
    $petStmt = $pdo->query("SELECT name, species, breed, age, age_unit, image FROM pets WHERE status='available' ORDER BY RAND() LIMIT 5");
    $featuredPets = $petStmt->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}

// Fetch profile image directly from the database
$imgStmt = $pdo->prepare("SELECT profile_img FROM users WHERE id = :uid");
$imgStmt->execute(['uid' => $user_id]);
$db_img = $imgStmt->fetchColumn();

// Fallback to default if no image is found
$profile_img = !empty($db_img) ? '../uploads/' . $db_img : '../uploads/default_avatar.png';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Adopter Dashboard | FurEver Pawmily</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/adopter_dashboard.css" class="rel">
</head>

<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
  <div style="text-align:center; margin-bottom:20px;">
    <img src="../img/logo.png" alt="FurEver Logo" style="width:150px;">
  </div>
  <a href="adopter_dashboard.php" class="<?= $current_page=='adopter_dashboard.php'?'active':'' ?>">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9h-3v9h-12v-9h-3z"/></svg> Dashboard
  </a>
  <a href="browse_pets.php" class="nav-link <?= $current_page=='browse_pets.php' ? 'active' : '' ?>">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 2c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 4c-.552 0-1-.449-1-1s.448-1 1-1 1 .449 1 1-.448 1-1 1zM6 9c-1.657 0-3 1.343-3 3s1.343 3 3 3c0 2 1.5 4 3.5 4s3.5-2 3.5-4c1.657 0 3-1.343 3-3s-1.343-3-3-3H6z"/>
    </svg> Browse Pets
</a>
  <a href="request.php" class="<?= $current_page=='request.php'?'active':'' ?>">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/></svg> My Requests
  </a>
  <a href="adopted_pets.php" class="<?= $current_page=='adopted_pets.php'?'active':'' ?>">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 6l-11 11-5-5 1.5-1.5 3.5 3.5 9.5-9.5z"/></svg> Adopted Pets
  </a>
  <a href="chatbox.php">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10 17v-3h4v-4h-4v-3l-5 5 5 5zm9-12h-8c-1.1 0-2 .9-2 2v4h2v-4h8v14h-8v-4h-2v4c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-14c0-1.1-.9-2-2-2z"/></svg> Chatbox
  </a>
  <a href="notifications.php" class="<?= $current_page=='notifications.php'?'active':'' ?>">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 24c1.3 0 2.3-1 2.3-2.3h-4.6c0 1.3 1 2.3 2.3 2.3zm6.3-6v-5c0-3.3-2.1-6.1-5-6.9v-.8c0-.8-.7-1.5-1.5-1.5s-1.5.7-1.5 1.5v.8c-2.9.8-5 3.6-5 6.9v5l-1.7 1.7v.8h16v-.8l-1.8-1.7z"/></svg> Notifications
  </a>
  <a href="../index.html">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10 17v-3h4v-4h-4v-3l-5 5 5 5zm9-12h-8c-1.1 0-2 .9-2 2v4h2v-4h8v14h-8v-4h-2v4c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-14c0-1.1-.9-2-2-2z"/></svg> Logout
  </a>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">
  <header>
    <h1>Welcome back, <?= htmlspecialchars($_SESSION["fullname"]); ?>!
        <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium'] == 1): ?>
            <span style="background-color: #222; color: #d47357; font-size: 0.42em; padding: 6px 14px; border-radius: 20px; margin-left: 8px; vertical-align: middle; display: inline-flex; align-items: center; gap: 6px; border: 1.5px solid #d47357; font-weight: 600; letter-spacing: 0.5px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="#d47357" stroke="#d47357" stroke-width="1" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                PREMIUM MEMBER
            </span>
        <?php endif; ?>
    </h1>    
    <p>🐾 “Where every paw finds a home.”</p>

    <!-- ROLE SWITCHER NOTICE -->
    <div class="user-notice">
        <p>Have a pet to rehome? <a href="petowner_dashboard.php">Switch to Owner Mode</a></p>
    </div>

    <a href="owner_profile.php" class="profile-header-link">    
    <style>
        @keyframes auraPulse {
            0% {
                box-shadow: 0 0 0 0px rgba(212, 115, 87, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(212, 115, 87, 0);
            }
            100% {
                box-shadow: 0 0 0 0px rgba(212, 115, 87, 0);
            }
        }
    </style>

    <img src="<?= htmlspecialchars($profile_img) . '?' . time(); ?>" 
         alt="Profile Picture" 
         class="profile-avatar" 
         <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium'] == 1): ?>
             style="border: 3px solid #d47357; 
                    border-radius: 50%; 
                    padding: 2px; 
                    animation: auraPulse 1s infinite; 
                    box-shadow: 0 0 12px rgba(212, 115, 87, 0.5);"
         <?php endif; ?>>            
    <span class="profile-text">View Profile</span>
</a>

  </header>

  <div class="dashboard">
   <!-- === STATS CARDS === -->
    <div class="stats">
    <!-- Pets Available -->
    <div class="stat-card">
        <div class="stat-icon">
        <!-- Dog Icon SVG -->
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C10.343 2 9 3.343 9 5s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 4c-.552 0-1-.449-1-1s.448-1 1-1 1 .449 1 1-.448 1-1 1zM6 9c-1.657 0-3 1.343-3 3s1.343 3 3 3c0 2 1.5 4 3.5 4s3.5-2 3.5-4c1.657 0 3-1.343 3-3s-1.343-3-3-3H6z"/>
        </svg>
        </div>
        <h3><?= $petsAvailable; ?> Pets</h3>
        <p>Available for adoption</p>
    </div>

    <!-- Active Requests -->
    <div class="stat-card">
        <div class="stat-icon">
        <!-- Envelope Icon SVG -->
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 24 24">
            <path d="M20 4H4c-1.103 0-2 .897-2 2v12c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
        </svg>
        </div>
        <h3><?= $activeRequests; ?> Active Requests</h3>
        <p>Awaiting approval</p>
    </div>

    <!-- Adopted Pets -->
    <div class="stat-card">
        <div class="stat-icon">
        <!-- Heart Icon SVG -->
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
        </svg>
        </div>
        <h3><?= $adoptedPets; ?> Adopted</h3>
        <p>Congratulations!</p>
    </div>

    <!-- Notifications -->
    <div class="stat-card">
        <div class="stat-icon">
        <!-- Bell Icon SVG -->
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 24c1.104 0 2-.896 2-2H10c0 1.104.896 2 2 2zm6.364-6V11c0-3.071-1.64-5.64-4.364-6.32V4a2 2 0 1 0-4 0v.68C7.276 5.36 5.636 7.929 5.636 11v7l-1.636 1.636V20h16v-.364L18.364 18z"/>
        </svg>
        </div>
        <h3><?= $totalNotifs; ?> Notifications</h3>
        <p>Recent updates</p>
    </div>
    </div>


    <!-- === FEATURED PETS === -->
    <div class="featured-section">
      <h2>🐾 Featured Pets</h2>
      <div class="pets-grid">
        <?php foreach($featuredPets as $pet): ?>
          <div class="pet-card">
            <img src="../uploads/<?= htmlspecialchars($pet['image']); ?>" alt="<?= htmlspecialchars($pet['name']); ?>">
            <h4><?= htmlspecialchars($pet['name']); ?></h4>
            <p><?= htmlspecialchars($pet['breed']); ?> • <?= htmlspecialchars($pet['age']); ?> <?= htmlspecialchars($pet['age_unit']); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- === RECENT ACTIVITY === -->
    <div class="activity-section">
      <h2>Recent Activity</h2>
      <ul class="activity-list">
        <?php if(!empty($activities)): foreach($activities as $a): ?>
          <li><?= htmlspecialchars($a['message']); ?> <small style="color:#999;">(<?= $a['created_at']; ?>)</small></li>
        <?php endforeach; else: ?>
          <li>No recent activities.</li>
        <?php endif; ?>
      </ul>
    </div>

  </div>
</div>

</body>
</html>
