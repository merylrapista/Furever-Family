<?php
session_start();
require_once "conn.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "admin") {
    header("Location: login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$admin_id = $_SESSION["user_id"];

try {
    // === STAT COUNTS ===
    $totalPets = $pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
    $pendingPets = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'pending'")->fetchColumn();
    $approvedPets = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'available'")->fetchColumn();
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalNotifs = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();

    // === RECENT ACTIVITIES ===
    $stmt = $pdo->prepare("SELECT message, created_at FROM notifications ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
/// === RECENT ADOPTION REQUESTS (MATCHING YOUR TABLE) ===
$recentRequests = $pdo->prepare("
    SELECT
        ar.id AS request_id,
        ar.status,
        ar.created_at,
        p.name AS pet_name,
        p.image AS pet_image,
        ad.fullname AS adopter_name
    FROM adoption_requests ar
    JOIN pets p ON ar.pet_id = p.id
    JOIN users ad ON ar.user_id = ad.id
    ORDER BY ar.created_at DESC
    LIMIT 5
");
$recentRequests->execute();
$requests = $recentRequests->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | FurEver Pawmily</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">

<style>
body {
  font-family: 'Poppins', sans-serif;
  margin: 0;
  background-color: #fffaf5;
  display: flex;
  height: 100vh;
}

/* ===== SIDEBAR ===== */
.sidebar {
  width: 230px;
  background: #d47357;
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30px 0;
  position: fixed;
  top: 0;
  height: 100%;
}
.sidebar h1 {
  margin-bottom: 40px;
  font-size: 30px;
  text-align: center;
  font-weight: 700;  
  letter-spacing: 1px;
  font-family: 'Dancing Script', cursive;
  color: black;
}

.sidebar a {
  text-decoration: none;
  color: white;
  width: 80%;
  background: rgba(255, 255, 255, 0.15);
  margin: 6px 0;
  padding: 12px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background 0.3s, transform 0.2s;
  font-weight: 500;
}
.sidebar a:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}
.sidebar a.active {
  background: rgba(255, 255, 255, 0.35);
  font-weight: bold;
  box-shadow: 0 3px 6px rgba(0,0,0,0.15);
}
.icon {
  width: 20px;
  height: 20px;
  fill: #000; /* Change to #fff for white icons */
}

/* ===== MAIN CONTENT ===== */
.main-content {
  flex: 1;
  margin-left: 230px;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}
header {
  background: #f0a08a;
  color: #fff;
  padding: 20px 30px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
header h1 {
  font-size: 24px;
  margin: 0;
}
.dashboard {
  padding: 30px;
}

/* ===== STATS ===== */
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}
.stat-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  padding: 20px;
  text-align: center;
  transition: transform 0.2s ease;
}
.stat-card:hover {
  transform: translateY(-5px);
}
.stat-icon {
  font-size: 35px;
  color: #d47357;
  margin-bottom: 10px;
}
.stat-card h3 {
  color: #333;
  margin: 10px 0 5px;
}
.stat-card p {
  color: #777;
}

/* ===== ACTIVITY SECTION ===== */
.activity-section {
  background: white;
  border-radius: 15px;
  box-shadow: 0 3px 6px rgba(0,0,0,0.1);
  padding: 20px 25px;
}
.activity-section h2 {
  color: #d47357;
  margin-bottom: 15px;
}
.activity-list {
  list-style: none;
  padding: 0;
}
.activity-list li {
  padding: 10px 0;
  border-bottom: 1px solid #eee;
  font-size: 15px;
  color: #555;
}
.activity-list li:last-child {
  border-bottom: none;
}

@media (max-width: 768px) {
  .sidebar {
    width: 180px;
  }
  .main-content {
    margin-left: 180px;
  }
}
</style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
  <div style="text-align:center; margin-bottom:20px;">
    <img src="../img/logo.png" alt="FurEver Logo" style="width:150px;">
  </div>
  <a href="admin_dashboard.php" class="<?= $current_page=='admin_dashboard.php'?'active':'' ?>">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000" width="20" height="20">
      <path d="M3 12l9-9 9 9h-3v9h-12v-9h-3z"/>
    </svg> Dashboard
  </a>
  <a href="pet_approval.php" class="<?= $current_page=='pet_approval.php'?'active':'' ?>">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000" width="20" height="20">
      <path d="M20 6l-11 11-5-5 1.5-1.5 3.5 3.5 9.5-9.5z"/>
    </svg> Pet Listings
  </a>
  <a href="admin_manage_users.php" class="<?= $current_page=='admin_manage_users.php'?'active':'' ?>">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000" width="20" height="20">
      <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
    </svg> Manage Users
  </a>
  <a href="admin_adoption_request.php" class="<?= $current_page=='admin_adoption_requests.php'?'active':'' ?>">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000" width="20" height="20">
      <path d="M19 3h-4.18c-.42-1.16-1.52-2-2.82-2s-2.4.84-2.82 2h-4.18c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-16c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm7 18h-14v-16h3v2h8v-2h3v16z"/>
    </svg> Adoption Requests
  </a>
  <a href="admin_notifications.php" class="<?= $current_page=='admin_notifications.php'?'active':'' ?>">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000" width="20" height="20">
      <path d="M12 24c1.3 0 2.3-1 2.3-2.3h-4.6c0 1.3 1 2.3 2.3 2.3zm6.3-6v-5c0-3.3-2.1-6.1-5-6.9v-.8c0-.8-.7-1.5-1.5-1.5s-1.5.7-1.5 1.5v.8c-2.9.8-5 3.6-5 6.9v5l-1.7 1.7v.8h16v-.8l-1.8-1.7z"/>
    </svg> Notifications
  </a>
  <a href="admin_login.php">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000" width="20" height="20">
      <path d="M10 17v-3h4v-4h-4v-3l-5 5 5 5zm9-12h-8c-1.1 0-2 .9-2 2v4h2v-4h8v14h-8v-4h-2v4c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-14c0-1.1-.9-2-2-2z"/>
    </svg> Logout
  </a>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">
  <header>
    <h1>Welcome back, <?= htmlspecialchars($_SESSION["fullname"]); ?>!</h1>
    <p> “Admin control center for every wag and purr.”</p>
  </header>

  <div class="dashboard">
    <!-- === STATS CARDS === -->
    <!-- === STATS CARDS === -->
<div class="stats">
  <div class="stat-card">
    <div class="stat-icon">
      <!-- Dog icon in black -->
      <svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24" width="35" height="35">
        <path d="M12 2C10 2 8 4 8 6s2 4 4 4 4-2 4-4-2-4-4-4zm6 16v2H6v-2c0-2 4-4 6-4s6 2 6 4z"/>
      </svg>
    </div>
    <h3><?= $totalPets; ?> Pets</h3>
    <p>Total pets listed in the system</p>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <!-- Clock / Pending icon in black -->
      <svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24" width="35" height="35">
        <path d="M12 1a11 11 0 1 0 11 11A11 11 0 0 0 12 1zm1 12h-5V11h4V6h1z"/>
      </svg>
    </div>
    <h3><?= $pendingPets; ?> Pending</h3>
    <p>Awaiting your approval</p>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <!-- Check / Approved icon in black -->
      <svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24" width="35" height="35">
        <path d="M20 6L9 17l-5-5 1.5-1.5L9 14l9.5-9.5z"/>
      </svg>
    </div>
    <h3><?= $approvedPets; ?> Approved</h3>
    <p>Pets available for adoption</p>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <!-- Users / People icon in black -->
      <svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24" width="35" height="35">
        <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
      </svg>
    </div>
    <h3><?= $totalUsers; ?> Users</h3>
    <p>Registered owners and adopters</p>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <!-- Bell / Notifications icon in black -->
      <svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24" width="35" height="35">
        <path d="M12 24c1.3 0 2.3-1 2.3-2.3h-4.6c0 1.3 1 2.3 2.3 2.3zm6.3-6v-5c0-3.3-2.1-6.1-5-6.9v-.8c0-.8-.7-1.5-1.5-1.5s-1.5.7-1.5 1.5v.8c-2.9.8-5 3.6-5 6.9v5l-1.7 1.7v.8h16v-.8l-1.8-1.7z"/>
      </svg>
    </div>
    <h3><?= $totalNotifs; ?> Notifications</h3>
    <p>System-wide updates & alerts</p>
  </div>
</div>


    <!-- === RECENT ADOPTION REQUESTS === -->
    <div class="activity-section" style="margin-top: 25px;">
      <h2>
        <svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24" width="20" height="20" style="vertical-align: middle; margin-right: 6px;">
          <path d="M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h12v2H3v-2zm0 4h12v2H3v-2zm0 4h12v2H3v-2z"/>
        </svg>
        Recent Adoption Requests
      </h2>


      <table style="
          width: 100%;
          border-collapse: collapse;
          margin-top: 10px;
          background: #fff;
          border-radius: 12px;
          overflow: hidden;
          box-shadow: 0 3px 8px rgba(0,0,0,0.1);
      ">
        <thead>
          <tr style="background: #f0a08a; color: white;">
            <th style="padding: 12px;">Pet</th>
            <th style="padding: 12px;">Adopter</th>
            <th style="padding: 12px;">Status</th>
            <th style="padding: 12px;">Requested At</th>
          </tr>
        </thead>

        <tbody>
          <?php if (!empty($requests)): ?>
            <?php foreach ($requests as $r): 
              $statusClass = strtolower($r['status']);
            ?>
            <tr style="text-align: center; border-bottom: 1px solid #eee;">
              <td style="padding: 12px;">
                <img src="../uploads/<?= htmlspecialchars($r['pet_image']) ?>" 
                    style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                <br><?= htmlspecialchars($r['pet_name']); ?>
              </td>

              <td style="padding: 12px;">
                <?= htmlspecialchars($r['adopter_name']); ?>
              </td>

              <td style="padding: 12px;">
                <span style="
                    padding: 6px 10px;
                    border-radius: 6px;
                    font-weight: bold;
                    font-size: 13px;
                    display: inline-block;
                    color: <?=
                      $r['status'] === 'approved' ? '#155724' :
                      ($r['status'] === 'rejected' ? '#721c24' : '#856404');
                    ?>;
                    background: <?=
                      $r['status'] === 'approved' ? '#d4edda' :
                      ($r['status'] === 'rejected' ? '#f8d7da' : '#fff3cd');
                    ?>;
                ">
                  <?= ucfirst($r['status']); ?>
                </span>
              </td>

              <td style="padding: 12px;">
                <?= date('M d, Y', strtotime($r['created_at'])); ?>
              </td>
            </tr>
            <?php endforeach; ?>
          
          <?php else: ?>
            <tr><td colspan="4" style="padding: 15px;">No recent adoption requests.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>



    <!-- === RECENT ACTIVITY === -->
    <div class="activity-section">
      <h2>
        <svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24" width="20" height="20" style="vertical-align: middle; margin-right: 6px;">
          <path d="M12 22c5.52 0 10-4.48 10-10S17.52 2 12 2 2 6.48 2 12s4.48 10 10 10zm-1-9h2v2h-2v-2zm0-8h2v6h-2V5z"/>
        </svg>
        Recent Activity
      </h2>
      <ul class="activity-list">
        <?php if (!empty($activities)): ?>
          <?php foreach ($activities as $activity): ?>
            <li><?= htmlspecialchars($activity['message']); ?> <small style="color:#999;">(<?= $activity['created_at']; ?>)</small></li>
          <?php endforeach; ?>
        <?php else: ?>
          <li>No recent activities yet.</li>
        <?php endif; ?>
      </ul>
    </div>

</div>

</body>
</html>
