<?php
session_start();
require_once "conn.php";

// Restrict access to admin only
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "admin") {
    header("Location: login.php");
    exit;
}

$current_page = basename($_SERVER["PHP_SELF"]);

// Fetch all adoption requests
$stmt = $pdo->prepare("
    SELECT 
        ar.id AS request_id,
        ar.status,
        ar.created_at,
        p.name AS pet_name,
        p.species,
        p.breed,
        p.age,
        p.image AS pet_image,
        ad.fullname AS adopter_name,
        o.fullname AS owner_name
    FROM adoption_requests ar
    JOIN pets p ON ar.pet_id = p.id
    JOIN users ad ON ar.user_id = ad.id
    JOIN users o ON p.owner_id = o.id
    ORDER BY ar.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Adoption Requests | FurEver Pawmily</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
<style>
body { font-family:'Poppins',sans-serif; margin:0; display:flex; min-height:100vh; background:#fffaf5; }
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
.main-content { flex:1; margin-left:230px; display:flex; flex-direction:column; overflow-y:auto; padding:20px 30px; }
header { background:#f0a08a; color:white; padding:20px 30px; box-shadow:0 2px 8px rgba(0,0,0,0.1); display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
header h1 { font-size:24px; margin:0; }
table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 4px 8px rgba(0,0,0,0.1); }
th, td { padding:12px 15px; text-align:center; border-bottom:1px solid #f1f1f1; }
th { background-color:#f0a08a; color:#fff; text-transform:uppercase; font-size:13px; }
tr:hover { background-color:#fff5ef; }
.pet-img { width:70px; height:70px; border-radius:8px; object-fit:cover; }
.status { padding:6px 12px; border-radius:6px; font-size:13px; font-weight:bold; display:inline-block; }
.status.pending { background:#fff3cd; color:#856404; }
.status.approved { background:#d4edda; color:#155724; }
.status.rejected { background:#f8d7da; color:#721c24; }
</style>
</head>
<body>

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
    </svg> Pet Approvals
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
  <a href="logout.php">
    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000" width="20" height="20">
      <path d="M10 17v-3h4v-4h-4v-3l-5 5 5 5zm9-12h-8c-1.1 0-2 .9-2 2v4h2v-4h8v14h-8v-4h-2v4c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-14c0-1.1-.9-2-2-2z"/>
    </svg> Logout
  </a>
</div>

<div class="main-content">
  <header>
    <h1>📋 Adoption Requests</h1>
    <p>Track all adoption requests submitted by adopters.</p>
  </header>

  <?php if(count($requests) > 0): ?>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Pet</th>
        <th>Adopter</th>
        <th>Owner</th>
        <th>Status</th>
        <th>Requested At</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($requests as $i => $r): 
        $status_class = strtolower($r['status'] ?? 'pending');
      ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td>
          <img src="../uploads/<?= htmlspecialchars($r['pet_image'] ?: 'default.png') ?>" alt="Pet Image" class="pet-img">
          <br><?= htmlspecialchars($r['pet_name']) ?>
        </td>
        <td><?= htmlspecialchars($r['adopter_name']) ?></td>
        <td><?= htmlspecialchars($r['owner_name']) ?></td>
        <td><span class="status <?= $status_class ?>"><?= ucfirst($status_class) ?></span></td>
        <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p>No adoption requests found.</p>
  <?php endif; ?>
</div>

</body>
</html>
