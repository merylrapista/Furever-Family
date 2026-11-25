<?php
session_start();
require_once "conn.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "admin") {
    header("Location: login.php");
    exit;
}

$current_page = basename($_SERVER["PHP_SELF"]);
$admin_name = $_SESSION["fullname"];

/* ======= TOGGLE USER ACTIVE/INACTIVE ======= */
if (isset($_GET["toggle_user"])) {
    $uid = intval($_GET["toggle_user"]);

    $stmt = $pdo->prepare("SELECT is_active, user_type FROM users WHERE id = :id");
    $stmt->execute(['id' => $uid]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u && $u["user_type"] !== "admin") {
        $newStatus = $u["is_active"] ? 0 : 1;
        $update = $pdo->prepare("UPDATE users SET is_active = :s WHERE id = :id");
        $update->execute(['s'=>$newStatus, 'id'=>$uid]);
    }

    header("Location: admin_manage_users.php");
    exit;
}

/* ======= SEARCH FILTER ======= */
$search = "";
$where = "";
$params = [];

if (isset($_GET["search"]) && trim($_GET["search"]) !== "") {
    $search = trim($_GET["search"]);
    $where = " AND (fullname LIKE :s OR email LIKE :s) ";
    $params["s"] = "%$search%";
}

// Fetch admins
$admin_stmt = $pdo->prepare("SELECT * FROM users WHERE user_type = 'admin' ORDER BY id DESC");
$admin_stmt->execute();
$admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch normal users with search
$search_sql = "";
$params = [];
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $search_sql = " AND (fullname LIKE :search OR email LIKE :search)";
    $params['search'] = "%$search%";
}

$user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_type != 'admin' $search_sql ORDER BY id DESC");
$user_stmt->execute($params);
$users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | FurEver Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: "Poppins", sans-serif;
  margin: 0;
  background: #fffaf5;
  display: flex;
  min-height: 100vh;
}

/* SIDEBAR */
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

/* MAIN CONTENT */
.main-content {
  flex: 1;
  margin-left: 230px;
  padding: 25px;
}

header { 
  background:#f0a08a; 
  margin-left: 3px; 
  color:white; 
  padding:28px 30px; 
  box-shadow:0 2px 8px rgba(0,0,0,0.1); 
  display:flex; 
  justify-content:space-between; 
  align-items:center; 
  margin-bottom:20px; }

header h1 { font-size:26px; 
  margin:0; }

/* TABLES */
table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  margin-top: 12px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}
th {
  background: #d47357;
  color: white;
  padding: 12px;
  text-align: left;
}
td {
  padding: 12px;
  border-bottom: 1px solid #eee;
}
tr:hover {
  background: #fff1ea;
}

/* SEARCH BAR */
.search-container {
  margin: 15px 0;
}
.search-container input {
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid #d5d5d5;
  font-size: 14px;
}
.search-container button {
  padding: 8px 15px;
  border: none;
  background: #d47357;
  color: white;
  border-radius: 6px;
  cursor: pointer;
  margin-left: 5px;
}
.search-container button:hover {
  background: #bb6247;
}

/* ACTION BUTTONS */
.action-btn {
  padding: 7px 14px;
  border-radius: 8px;
  color: white;
  text-decoration: none;
  font-size: 13px;
  transition: 0.2s;
  display: inline-block;
}
.activate { background: #e9b766ff; }
.activate:hover { background: #e9ae4fff; }

.deactivate { background: #d9534f; }
.deactivate:hover { background: #b8413d; }

.admin-badge {
  color: #d47357;
  font-weight: bold;
}
</style>
</head>
<body>

<!-- SIDEBAR -->
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

<!-- MAIN CONTENT -->
<div class="main-content">
  <header>
    <h1> Manage Users</h1>
  </header>

  <h2> Admin Account</h2>
  <table>
    <tr>
      <th>#</th>
      <th>Full Name</th>
      <th>Email</th>
      <th>Role</th>
      <th>Status</th>
    </tr>

    <?php if (empty($admins)): ?>
      <tr><td colspan="5" style="text-align:center;color:#999;">No admin accounts found.</td></tr>
    <?php else: foreach ($admins as $i => $a): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($a["fullname"]) ?></td>
        <td><?= htmlspecialchars($a["email"]) ?></td>
        <td class="admin-badge">Admin</td>
        <td><?= $a["is_active"] ? "Active" : "Inactive" ?></td>
      </tr>
    <?php endforeach; endif; ?>
  </table>

  <h2> Users</h2>

  <div class="search-container">
    <form method="GET">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or email...">
      <button type="submit">Search</button>
    </form>
  </div>

  <table>
    <tr>
      <th>#</th>
      <th>Full Name</th>
      <th>Email</th>
      <th>Role</th>
      <th>Status</th>
      <th>Action</th>
    </tr>

    <?php if (empty($users)): ?>
      <tr><td colspan="6" style="text-align:center;color:#999;">No users found.</td></tr>
    <?php else: foreach ($users as $i => $u): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($u["fullname"]) ?></td>
        <td><?= htmlspecialchars($u["email"]) ?></td>
        <td><?= ucfirst($u["user_type"]) ?></td>
        <td><?= $u["is_active"] ? "Active" : "Inactive" ?></td>
        <td>
          <a href="admin_manage_users.php?toggle_user=<?= $u['id'] ?>"
             class="action-btn <?= $u['is_active'] ? 'deactivate' : 'activate' ?>">
             <?= $u['is_active'] ? "Deactivate" : "Activate" ?>
          </a>
        </td>
      </tr>
    <?php endforeach; endif; ?>
  </table>

</div>
</body>
</html>
