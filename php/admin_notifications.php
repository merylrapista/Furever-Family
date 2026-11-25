<?php
session_start();
require_once "conn.php";

// Only allow admin
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "admin") {
    header("Location: login.php");
    exit;
}

// Fetch all adoption requests with pet info
$stmt = $pdo->prepare("
    SELECT ar.id, p.name AS pet_name, ar.status, ar.created_at, ar.read_status,
           p.species, p.breed, p.age, p.sex, p.description,
           u.fullname AS adopter_name
    FROM adoption_requests ar
    JOIN pets p ON ar.pet_id = p.id
    JOIN users u ON ar.user_id = u.id
    ORDER BY ar.created_at DESC
");

$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications | FurEver Admin</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; margin:0; background:#fffaf5; }
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

.main-content { margin-left:230px; padding:20px 40px; }
header { 
  background:#f0a08a; 
  margin-left: 3px; 
  color:white; 
  padding:30px 30px; 
  box-shadow:0 2px 8px rgba(0,0,0,0.1); 
  display:flex; 
  justify-content:space-between; 
  align-items:center; 
  margin-bottom:20px; }

header h1 {  
  margin:0; 
}

.notifications { display:flex; flex-direction:column; gap:15px; }
.notification-card {
    background:white; border-radius:15px; padding:20px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1); transition: transform 0.2s;
    display:flex; justify-content:space-between; align-items:center; cursor:pointer;
}
.notification-card:hover { transform: translateY(-3px); }
.notification-info { display:flex; flex-direction:column; }
.notification-info p { margin:3px 0; font-size:14px; }
.notification-info .status { font-weight:bold; color:#d47357; }
.notification-info .time { font-size:12px; color:#999; }
.notification-card.read { opacity:0.6; }

.swal2-border-radius { border-radius: 15px !important; }
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
    <h1>🔔 Notifications</h1>
    <!-- <p>Welcome, <?= htmlspecialchars($_SESSION["fullname"]); ?>!</p> -->
</header>

<div class="notifications">
    <?php if (empty($notifications)): ?>
        <p>No notifications yet. 🐾</p>
    <?php else: ?>
        <?php foreach ($notifications as $note): ?>
            <div class="notification-card <?= $note['read_status'] === '1' ? 'read' : ''; ?>"
                data-id="<?= $note['id']; ?>"
                data-pet="<?= htmlspecialchars($note['pet_name']); ?>"
                data-status="<?= htmlspecialchars($note['status']); ?>"
                data-time="<?= date("M d, Y H:i", strtotime($note['created_at'])); ?>"
                data-species="<?= htmlspecialchars($note['species']); ?>"
                data-breed="<?= htmlspecialchars($note['breed']); ?>"
                data-age="<?= htmlspecialchars($note['age']); ?>"
                data-sex="<?= htmlspecialchars($note['sex']); ?>"
                data-desc="<?= htmlspecialchars($note['description']); ?>"
                data-adopter="<?= htmlspecialchars($note['adopter_name']); ?>"
            >

                <div class="notification-info">
                    <p><?= htmlspecialchars($note['adopter_name']); ?> requested <strong><?= htmlspecialchars($note['pet_name']); ?></strong></p>
                    <p>Status: <span class="status"><?= ucfirst($note['status']); ?></span></p>
                    <p class="time"><?= date("M d, Y H:i", strtotime($note['created_at'])); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.notification-card');

    cards.forEach(card => {
        card.addEventListener('click', function() {
            const id = this.dataset.id;
            const petName = this.dataset.pet;
            const status = this.dataset.status;
            const time = this.dataset.time;
            const species = this.dataset.species;
            const breed = this.dataset.breed;
            const sex = this.dataset.sex;
            const age = this.dataset.age;
            const desc = this.dataset.desc;
            const adopter = this.dataset.adopter;

            const content = `
                <strong>${adopter}</strong> requested adoption for <strong>${petName}</strong>.<br><br>
                Status: <strong>${status}</strong><br>
                Pet Details: ${age} year(s) old ${species}, Sex: ${sex}, Breed: ${breed}.<br>
                Description: ${desc}<br><br>
                Requested on: ${time}.
            `;



            Swal.fire({
                html: `<div style="text-align:left; font-size:14px; line-height:1.6;">${content}</div>`,
                showCloseButton: true,
                showConfirmButton: false,
                closeButtonHtml: '&times;',
                background: '#fffaf5',
                width: '500px',
                padding: '25px',
                customClass: {
                    popup: 'swal2-border-radius'
                },
                willClose: () => {
                    if (!card.classList.contains('read')) {
                        fetch(`mark_read_admin.php?id=${id}`)
                            .then(res => res.text())
                            .then(() => card.classList.add('read'));
                    }
                }
            });
        });
    });
});
</script>

</body>
</html>
