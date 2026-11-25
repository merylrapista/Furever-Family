<?php
session_start();
require_once "conn.php";

$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// --- Sync the session directly with the database ---
$stmt = $pdo->prepare("SELECT is_premium, city, barangay FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$db_user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($db_user) {
    $_SESSION['is_premium'] = $db_user['is_premium'];
    $_SESSION['city'] = $db_user['city'] ?? '';
    $_SESSION['barangay'] = $db_user['barangay'] ?? '';
}

// Initialize filters
$species = $_GET['species'] ?? '';
$breed = $_GET['breed'] ?? '';
$status = $_GET['status'] ?? '';

// Base query
$query = "
SELECT 
    pets.*, 
    pet_sales.price, 
    pet_sales.payment_method, 
    u.fullname as owner_name, 
    u.id as owner_id, 
    u.profile_img as owner_profile_img
FROM pets
LEFT JOIN pet_sales ON pets.id = pet_sales.pet_id
LEFT JOIN users u ON pets.owner_id = u.id
WHERE LOWER(TRIM(pets.status)) = 'available'
";

$params = [];

// Species filter
if (!empty($species)) {
    $query .= " AND pets.species = :species";
    $params['species'] = $species;
}

// Breed filter
if (!empty($breed)) {
    $query .= " AND pets.breed = :breed";
    $params['breed'] = $breed;
}

/// Status & Nearby filter
if ($status === 'for_sale') {
    $query .= " AND pet_sales.price IS NOT NULL";
} elseif ($status === 'free') {
    $query .= " AND (pet_sales.price IS NULL OR pet_sales.price = 0)";
} elseif ($status === 'nearest' && isset($_SESSION['is_premium']) && $_SESSION['is_premium'] == 1) {
    $user_city = $_SESSION['city'] ?? '';

    // If their city is not set, default to Janiuay
    if (empty($user_city)) {
        $user_city = 'Janiuay';
    }

    // Check both city and location columns to make the match more forgiving
    $query .= " AND (LOWER(TRIM(pets.city)) LIKE :user_city OR LOWER(TRIM(pets.location)) LIKE :user_city)";
    $params['user_city'] = '%' . strtolower(trim($user_city)) . '%';
}

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX Request for Real-Time Updating
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    if (empty($pets)) {
        echo '<p>No pets found for the selected filters or location. 🐾 <br><small>Query debug: ' . htmlspecialchars($stmt->queryString) . '</small></p>';
    } else {
        foreach ($pets as $pet) {
            ?>
            <div class="pet-card">
                <div class="pet-image-wrapper">
                    <img src="../uploads/<?= htmlspecialchars($pet['image'] ?? 'default.jpg'); ?>" alt="<?= htmlspecialchars($pet['name']); ?>">
                    
                    <?php if (isset($pet['price']) && $pet['price'] !== null): ?>
                        <div class="price-badge">
                            ₱<?= number_format($pet['price'], 2); ?>
                        </div>
                    <?php else: ?>
                        <div class="price-badge free">Free Pet</div>
                    <?php endif; ?>
                </div>

                <div class="pet-content">
                    <div class="pet-header">
                        <h3><?= htmlspecialchars($pet['name']); ?></h3>
                        <span style="font-size: 11px; color: #888;"><?= htmlspecialchars($pet['location'] . ', ' . $pet['city']); ?></span>
                    </div>

                    <div class="pet-meta-tags">
                        <span><?= htmlspecialchars($pet['breed']); ?></span>
                        <span><?= htmlspecialchars($pet['age']); ?> <?= htmlspecialchars($pet['age_unit']); ?></span>
                        <span><?= htmlspecialchars($pet['sex']); ?></span>
                        <?php if (isset($pet['price']) && $pet['price'] !== null): ?>
                            <span><?= htmlspecialchars($pet['payment_method']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="owner-info">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php 
                                $owner_img = !empty($pet['owner_profile_img']) ? $pet['owner_profile_img'] : 'default_avatar.png';
                            ?>
                            <img src="../uploads/<?= htmlspecialchars($owner_img); ?>" 
                                alt="Owner Profile" 
                                class="owner-avatar" 
                                onerror="this.onerror=null; this.src='../uploads/default_avatar.png';">
                            <div class="owner-details">
                                <span style="color:#888; font-size:9px;">Posted by</span>
                                <a href="view_owner.php?owner_id=<?= $pet['owner_id']; ?>" class="owner-name">
                                    <?= htmlspecialchars($pet['owner_name'] ?? 'Unknown Owner'); ?>
                                </a>
                            </div>
                        </div>
                        
                        <a href="chatbox.php?receiver_id=<?= $pet['owner_id']; ?>&pet_id=<?= $pet['id']; ?>" class="message-owner-btn" title="Message Owner">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Chat
                        </a>
                    </div>

                    <div class="pet-description" title="<?= htmlspecialchars($pet['description']); ?>">
                        <?= htmlspecialchars($pet['description']); ?>
                    </div>

                    <div class="card-footer-actions">
                        <a href="pet_medical_view2.php?id=<?= $pet['id'] ?>" class="view-med-link">
                            View Medical Records
                        </a>

                        <?php
                        $check_request = $pdo->prepare("SELECT * FROM adoption_requests WHERE pet_id = :pet_id AND user_id = :user_id");
                        $check_request->execute(['pet_id' => $pet['id'], 'user_id' => $_SESSION['user_id']]);
                        $already_requested = $check_request->fetch();
                        ?>

                        <form method="POST" action="request_adoption.php" style="display:flex; flex-direction:column;">
                            <input type="hidden" name="pet_id" value="<?= $pet['id']; ?>">

                            <?php if ($already_requested): ?>
                                <button type="button" class="adopt-btn requested" disabled>✅ Requested</button>
                            <?php else: ?>
                                <a href="agreement.php?pet_id=<?= $pet['id']; ?>" class="adopt-btn"
                                onclick="event.preventDefault(); checkAdoptionFlow(<?= $pet['id']; ?>, '<?= htmlspecialchars($pet['name']); ?>', <?= ($pet['price'] !== null) ? 'true' : 'false'; ?>, <?= isset($_SESSION['is_premium']) && $_SESSION['is_premium'] == 1 ? 'true' : 'false'; ?>)">
                                    💌 Adopt Me
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    exit;
}

// Fetch distinct species and breeds for filters
$species_list = $pdo->query("SELECT DISTINCT species FROM pets WHERE species != 'Fish'")->fetchAll(PDO::FETCH_COLUMN);
$breed_list = $pdo->query("SELECT DISTINCT breed FROM pets")->fetchAll(PDO::FETCH_COLUMN);

$success_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'premium_activated') {
    $success_message = "Your premium account has been activated! You can now adopt for-sale pets.";
    $_SESSION['is_premium'] = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Pets | FurEver Pawmily</title>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/browse_pets.css" class="rel">
</head>
<body>

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
</div>

<div class="main-content">
   <header>
        <h1>Browse Pets 
            <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium'] == 1): ?>
                <span style="background-color: #222; color: #d47357; font-size: 0.4em; padding: 6px 14px; border-radius: 20px; margin-left: 10px; vertical-align: middle; display: inline-flex; align-items: center; gap: 6px; border: 1.5px solid #d47357; font-weight: 600; letter-spacing: 0.5px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#d47357" stroke="#d47357" stroke-width="1" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    PREMIUM MEMBER
                </span>
            <?php endif; ?>
        </h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION["fullname"]); ?>!</p>
    </header>
        
    <div class="filter-container">
        <form id="filter-form" class="top-filters">
            <select name="breed" onchange="applyFilters()">
                <option value="">All Breeds</option>
                <?php foreach($breed_list as $b): ?>
                    <option value="<?= htmlspecialchars($b); ?>" <?= $b==$breed?'selected':''; ?>>
                        <?= htmlspecialchars($b); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="species" onchange="applyFilters()">
                <option value="">All Categories</option>
                <?php
                $categories = ['Dog', 'Cat', 'Rabbit', 'Bird'];
                foreach($categories as $cat):
                ?>
                    <option value="<?= $cat; ?>" <?= $species==$cat?'selected':''; ?>>
                        <?= $cat; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="status-container">
            <button type="button" onclick="applyStatus('')" class="<?= ($status=='' ? 'active' : ''); ?>">All</button>
            <button type="button" onclick="applyStatus('free')" class="<?= ($status=='free' ? 'active' : ''); ?>">Free Pets</button>
            <button type="button" onclick="applyStatus('for_sale')" class="<?= ($status=='for_sale' ? 'active' : ''); ?>">For Sale</button>
            <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium'] == 1): ?>
                <button type="button" onclick="applyStatus('nearest')" class="<?= ($status=='nearest' ? 'active' : ''); ?>"> Nearest Pets</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="pets-grid">
        <?php if (empty($pets)): ?>
            <p>No pets found for the selected filters or location. 🐾</p>
        <?php else: ?>
            <?php foreach ($pets as $pet): ?>
                <div class="pet-card">
                    <div class="pet-image-wrapper">
                        <img src="../uploads/<?= htmlspecialchars($pet['image'] ?? 'default.jpg'); ?>" alt="<?= htmlspecialchars($pet['name']); ?>">
                        
                        <?php if (isset($pet['price']) && $pet['price'] !== null): ?>
                            <div class="price-badge">
                                ₱<?= number_format($pet['price'], 2); ?>
                            </div>
                        <?php else: ?>
                            <div class="price-badge free">Free Pet</div>
                        <?php endif; ?>
                    </div>

                    <div class="pet-content">
                        <div class="pet-header">
                            <h3><?= htmlspecialchars($pet['name']); ?></h3>
                            <span style="font-size: 11px; color: #888;"><?= htmlspecialchars($pet['location'] . ', ' . $pet['city']); ?></span>
                        </div>

                        <div class="pet-meta-tags">
                            <span><?= htmlspecialchars($pet['breed']); ?></span>
                            <span><?= htmlspecialchars($pet['age']); ?> <?= htmlspecialchars($pet['age_unit']); ?></span>
                            <span><?= htmlspecialchars($pet['sex']); ?></span>
                            <?php if (isset($pet['price']) && $pet['price'] !== null): ?>
                                <span><?= htmlspecialchars($pet['payment_method']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="owner-info">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <?php 
                                    $owner_img = !empty($pet['owner_profile_img']) ? $pet['owner_profile_img'] : 'default_avatar.png';
                                ?>
                                <img src="../uploads/<?= htmlspecialchars($owner_img); ?>" 
                                    alt="Owner Profile" 
                                    class="owner-avatar" 
                                    onerror="this.onerror=null; this.src='../uploads/default_avatar.png';">
                                <div class="owner-details">
                                    <span style="color:#888; font-size:9px;">Posted by</span>
                                    <a href="view_owner.php?owner_id=<?= $pet['owner_id']; ?>" class="owner-name">
                                        <?= htmlspecialchars($pet['owner_name'] ?? 'Unknown Owner'); ?>
                                    </a>
                                </div>
                            </div>
                            
                            <a href="chatbox.php?receiver_id=<?= $pet['owner_id']; ?>&pet_id=<?= $pet['id']; ?>" class="message-owner-btn" title="Message Owner">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                Chat
                            </a>
                        </div>

                        <div class="pet-description" title="<?= htmlspecialchars($pet['description']); ?>">
                            <?= htmlspecialchars($pet['description']); ?>
                        </div>

                        <div class="card-footer-actions">
                            <a href="pet_medical_view2.php?id=<?= $pet['id'] ?>" class="view-med-link">
                                View Medical Records
                            </a>

                            <?php
                            $check_request = $pdo->prepare("SELECT * FROM adoption_requests WHERE pet_id = :pet_id AND user_id = :user_id");
                            $check_request->execute(['pet_id' => $pet['id'], 'user_id' => $_SESSION['user_id']]);
                            $already_requested = $check_request->fetch();
                            ?>

                            <form method="POST" action="request_adoption.php" style="display:flex; flex-direction:column;">
                                <input type="hidden" name="pet_id" value="<?= $pet['id']; ?>">

                                <?php if ($already_requested): ?>
                                    <button type="button" class="adopt-btn requested" disabled>✅ Requested</button>
                                <?php else: ?>
                                    <a href="agreement.php?pet_id=<?= $pet['id']; ?>" class="adopt-btn"
                                    onclick="event.preventDefault(); checkAdoptionFlow(<?= $pet['id']; ?>, '<?= htmlspecialchars($pet['name']); ?>', <?= ($pet['price'] !== null) ? 'true' : 'false'; ?>, <?= isset($_SESSION['is_premium']) && $_SESSION['is_premium'] == 1 ? 'true' : 'false'; ?>)">
                                        💌 Adopt Me
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
let currentStatus = '<?= htmlspecialchars($status) ?>';

function applyStatus(status) {
    currentStatus = status;
    
    document.querySelectorAll('.status-container button').forEach(btn => {
        btn.classList.remove('active');
        if (btn.innerText.toLowerCase().includes(status.replace('_', ' ')) || (status === '' && btn.innerText.toLowerCase() === 'all')) {
            btn.classList.add('active');
        }
    });

    sendAjaxRequest();
}

function applyFilters() {
    sendAjaxRequest();
}

function sendAjaxRequest() {
    const form = document.getElementById('filter-form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);

    params.append('status', currentStatus);

    fetch('browse_pets.php?ajax=1&' + params.toString())
        .then(response => response.text())
        .then(html => {
            document.querySelector('.pets-grid').innerHTML = html;
        });
}

function checkAdoptionFlow(petId, petName, isForSale, isPremium) {
    if (isForSale && !isPremium) {
        Swal.fire({
            title: `<span style="color:#d47357;">Premium Registration Required</span>`,
            html: `<p>To adopt pets that are for sale, you must be a premium user. Would you like to register or upgrade your account now?</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Upgrade Now',
            cancelButtonText: 'Cancel',
            background: '#fffaf5',
            confirmButtonColor: '#d47357',
            cancelButtonColor: '#aaa'
        }).then((result) => {
            if (result.isConfirmed) {
                sessionStorage.setItem('pendingPetId', petId);
                sessionStorage.setItem('pendingPetName', petName);
                window.location.href = 'register_premium.php'; 
            }
        });
    } else {
        confirmAdoption(petId, petName);
    }
}

function confirmAdoption(petId, petName) {
    Swal.fire({
        title: `<span style="color:#d47357;">Confirm Adoption</span>`,
        html: `<p>Are you sure you want to send an adoption request for <strong>${petName}</strong>?</p>`,
        showCancelButton: true,
        confirmButtonText: 'Yes, adopt me! 💖',
        cancelButtonText: 'No, cancel ❌',
        background: '#fffaf5',
        icon: 'question',
        iconColor: '#d47357',
        customClass: {
            confirmButton: 'swal2-confirm-custom',
            cancelButton: 'swal2-cancel-custom'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'agreement.php?pet_id=' + petId;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const pendingPetId = sessionStorage.getItem('pendingPetId');
    const pendingPetName = sessionStorage.getItem('pendingPetName');

    if (pendingPetId && pendingPetName) {
        sessionStorage.removeItem('pendingPetId');
        sessionStorage.removeItem('pendingPetName');

        setTimeout(() => {
            confirmAdoption(pendingPetId, pendingPetName);
        }, 500);
    }
});
</script>

<?php if (!empty($success_message)): ?>
    <script>
        Swal.fire({
            title: 'Success!',
            text: '<?= htmlspecialchars($success_message); ?>',
            icon: 'success',
            confirmButtonColor: '#d47357',
            background: '#fffaf5'
        });
    </script>
<?php endif; ?>
</body>
</html>