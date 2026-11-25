<?php
session_start();
require_once "conn.php";

$current_page = basename($_SERVER['PHP_SELF']); // <-- Get the current filename

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Ensure fullname is set to avoid undefined index warnings
$fullname = isset($_SESSION["fullname"]) ? $_SESSION["fullname"] : 'Adopter';

// Fetch adopted pets for this user
$stmt = $pdo->prepare("
    SELECT p.*
    FROM pets p
    JOIN adoption_requests ar ON p.id = ar.pet_id
    WHERE ar.user_id = :user_id AND ar.status = 'approved'
");
$stmt->execute(['user_id' => $_SESSION["user_id"]]);
$adopted_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Adopted Pets | FurEver Pawmily</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/adopted_pets.css" class="rel">
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
        <h1>Adopted Pets 
            <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium'] == 1): ?>
                <span style="background-color: #222; color: #d47357; font-size: 0.4em; padding: 6px 14px; border-radius: 20px; margin-left: 10px; vertical-align: middle; display: inline-flex; align-items: center; gap: 6px; border: 1.5px solid #d47357; font-weight: 600; letter-spacing: 0.5px;">
                    
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#d47357" stroke="#d47357" stroke-width="1" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    
                    PREMIUM MEMBER
                </span>
            <?php endif; ?>
        </h1>
        <p>Welcome, <?= htmlspecialchars($fullname); ?>!</p>
    </header>

    <?php if (empty($adopted_pets)): ?>
        <p>You haven’t adopted any pets yet. 🐾</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Photo</th>
                    <th>Pet Name</th>
                    <th>Species</th>
                    <th>Breed</th>
                    <th>Age</th>
                    <th>Sex</th> 
                    <th>Location</th> 
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($adopted_pets as $i => $pet): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><img src="../uploads/<?= htmlspecialchars($pet['image'] ?? 'default.jpg'); ?>" alt="Pet Image" class="pet-thumb"></td>
                        <td><?= htmlspecialchars($pet['name']); ?></td>
                        <td><?= htmlspecialchars($pet['species']); ?></td>
                        <td><?= htmlspecialchars($pet['breed']); ?></td>
                        <td><?= htmlspecialchars($pet['age'] . ' ' . ($pet['age_unit'] ?? '')); ?></td>
                        <td><?= htmlspecialchars($pet['sex']); ?></td>
                        <td><?= htmlspecialchars($pet['location']); ?></td>
                        <td>
                            <button class="view-btn" data-pet='<?= json_encode($pet); ?>'>View Details</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    <?php endif; ?>
</div>

<div class="modal" id="petModal">
    <div class="modal-content">
        <span id="closeModal">&times;</span>
        <h2 id="modalName"></h2>
        <img id="modalImage" src="" alt="Pet Image">
        <p><strong>Species:</strong> <span id="modalSpecies"></span></p>
        <p><strong>Breed:</strong> <span id="modalBreed"></span></p>
        <p><strong>Age:</strong> <span id="modalAge"></span></p>
        <p><strong>Location:</strong> <span id="modalLocation"></span></p>
        <p id="modalDescription"></p>
    </div>
</div>

<script>
const modal = document.getElementById('petModal');
const closeModal = document.getElementById('closeModal');

document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const pet = JSON.parse(btn.getAttribute('data-pet'));

        document.getElementById('modalName').textContent = pet.name;
        document.getElementById('modalImage').src = "../uploads/" + (pet.image || 'default.jpg');
        document.getElementById('modalSpecies').textContent = pet.species;
        document.getElementById('modalBreed').textContent = pet.breed;
        document.getElementById('modalAge').textContent = `${pet.age} ${pet.age_unit || ''}`;
        document.getElementById('modalLocation').textContent = pet.location;
        
        // Include sex and description
        document.getElementById('modalDescription').innerHTML = `
            <strong>Sex:</strong> ${pet.sex}<br>
            <strong>Description:</strong> ${pet.description}
        `;

        modal.style.display = 'flex';
    });
});

closeModal.onclick = () => modal.style.display = 'none';
window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; };

</script>

</body>
</html>