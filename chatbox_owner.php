<?php
session_start();
require_once "conn.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION["user_id"];

// Get variables from URL
$receiver_id_from_url = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : null;
$pet_id_from_url = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : null;
$receiver_info = null;
$pet_info = null;

if ($receiver_id_from_url) {
    $stmt = $pdo->prepare("SELECT fullname, profile_img FROM users WHERE id = :id");
    $stmt->execute(['id' => $receiver_id_from_url]);
    $receiver_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

/// If pet_id provided, fetch info and price from the pet_sales table
if ($pet_id_from_url) {
    $petStmt = $pdo->prepare("SELECT p.id, p.name, p.species, p.breed, p.sex, p.age, p.age_unit, p.location, p.city, p.status, p.image, p.description, pay.price 
                              FROM pets p 
                              LEFT JOIN pet_sales pay ON p.id = pay.pet_id 
                              WHERE p.id = :pet");
    $petStmt->execute(['pet' => $pet_id_from_url]);
    $pet_info = $petStmt->fetch(PDO::FETCH_ASSOC);
}

try {
    $imgStmt = $pdo->prepare("SELECT profile_img FROM users WHERE id = :uid");
    $imgStmt->execute(['uid' => $user_id]);
    $db_img = $imgStmt->fetchColumn();
    $profile_img = !empty($db_img) ? '../uploads/' . $db_img : '../uploads/default_avatar.png';
} catch(Exception $e) {
    $profile_img = '../uploads/default_avatar.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chatbox | FurEver Pawmily</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/chatbox_owner.css">
</head>

<body>

<div class="sidebar">
    <div style="text-align:center; margin-bottom:20px;">
        <img src="../img/logo.png" alt="FurEver Logo" style="width:150px;">
    </div>
    <a href="petowner_dashboard.php" class="<?= $current_page=='petowner_dashboard.php'?'active':'' ?>">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9h-3v9h-12v-9h-3z"/></svg> Dashboard
    </a>
    <!-- <a href="add_pet.php" class="<?= $current_page=='add_pet.php'?'active':'' ?>">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24"><path d="M19 13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg> Add Pet
    </a> -->
    <a href="pending_pets.php" class="<?= $current_page=='pending_pets.php'?'active':'' ?>">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24"><path d="M12 1a11 11 0 1 0 11 11A11 11 0 0 0 12 1zm1 12h-5V11h4V6h1z"/></svg> Pending Pets
    </a>
    <a href="my_pets.php" class="<?= $current_page=='my_pets.php'?'active':'' ?>">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/></svg> My Pets
    </a>
    <a href="owner_requests.php" class="<?= $current_page=='owner_requests.php'?'active':'' ?>">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24"><path d="M20 3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 7l-6 4-6-4V5l6 4 6-4v5z"/></svg> Requests
    </a>
    <a href="chatbox_owner.php" class="<?= $current_page=='chatbox_owner.php'?'active':'' ?>">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10 17v-3h4v-4h-4v-3l-5 5 5 5zm9-12h-8c-1.1 0-2 .9-2 2v4h2v-4h8v14h-8v-4h-2v4c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-14c0-1.1-.9-2-2-2z"/></svg> Chatbox
    </a>
    <a href="notifications_owner.php" class="<?= $current_page=='notifications_owner.php'?'active':'' ?>">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24"><path d="M12 24c1.3 0 2.3-1 2.3-2.3h-4.6c0 1.3 1 2.3 2.3 2.3zm6.3-6v-5c0-3.3-2.1-6.1-5-6.9v-.8c0-.8-.7-1.5-1.5-1.5s-1.5.7-1.5 1.5v.8c-2.9.8-5 3.6-5 6.9v5l-1.7 1.7v.8h16v-.8l-1.8-1.7z"/></svg> Notifications
    </a>
    <a href="../index.html">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24"><path d="M10 17v-3h4v-4h-4v-3l-5 5 5 5zm9-12h-8c-1.1 0-2 .9-2 2v4h2v-4h8v14h-8v-4h-2v4c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-14c0-1.1-.9-2-2-2z"/></svg> Logout
    </a>
</div>

<div class="main-content">
  <header>
    <h1> Chatbox</h1> <br>
    <p>🐾Chat with us about your favorite pets or the adoption process.</p>  
  </header>

  <div class="dashboard">
     <div class="chat-workspace">
        <div class="chat-sidebar">
            <div class="chat-search">
                <input type="text" id="chat-search-input" placeholder="Search chats...">
            </div>
            <ul class="chat-list" id="chat-list-container">
            </ul>
        </div>

        <div class="chat-main-panel">
            <div class="chat-box-header" id="chat-box-header" style="display: none;">
                <div class="chat-recipient-info">
                    <div class="chat-avatar-wrap">
                        <img src="../uploads/default_avatar.png" alt="Recipient" id="active-chat-img" class="chat-avatar">
                        <span class="chat-status-dot"></span>
                    </div>
                    <div>
                        <h4 id="active-chat-name">Select a conversation</h4>
                        <small style="color: #6c757d;" id="active-chat-status">Active now</small>
                    </div>
                </div>
            </div>

            <div class="chat-body" id="chat-body">
                <div style="padding: 60px 20px; text-align: center; color: #777;" id="placeholder-box">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px; opacity: 0.7;">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <h4>No conversation selected</h4>
                    <p style="font-size: 0.9em; color: #999; margin-top: 5px;">Click on a user from the sidebar to open the conversation.</p>
                </div>
            </div>

           <div id="pet-preview-container" style="<?= $pet_info ? 'display:block;' : 'display:none;'; ?> margin-bottom: 15px;">
    <?php if ($pet_info): ?>

        <input type="hidden" id="pending_pet_id" value="<?= htmlspecialchars($pet_info['id']); ?>">

        <div class="pet-attachment-card" 
            id="pet-preview-card"
            onclick="showPetModal(this)"
            data-name="<?= htmlspecialchars($pet_info['name'] ?? ''); ?>"
            data-img="<?= (!empty($pet_info['image']) && file_exists(__DIR__ . '/../uploads/' . $pet_info['image'])) ? '../uploads/' . htmlspecialchars($pet_info['image']) : '../assets/images/default_pet.png'; ?>"
            data-species="<?= htmlspecialchars($pet_info['species'] ?? ''); ?>"
            data-breed="<?= htmlspecialchars($pet_info['breed'] ?? ''); ?>"
            data-sex="<?= htmlspecialchars($pet_info['sex'] ?? ''); ?>"
            data-age="<?= htmlspecialchars($pet_info['age'] ?? '') . ' ' . htmlspecialchars($pet_info['age_unit'] ?? ''); ?>"
            data-location="<?= htmlspecialchars($pet_info['location'] ?? ''); ?>"
            data-city="<?= htmlspecialchars($pet_info['city'] ?? ''); ?>"
            data-status="<?= htmlspecialchars($pet_info['status'] ?? ''); ?>"
            data-desc="<?= htmlspecialchars($pet_info['description'] ?? ''); ?>"
            data-price="<?= htmlspecialchars($pet_info['price'] ?? ''); ?>"
            style="cursor: pointer; position: relative; display: flex; align-items: center; padding: 12px; border: 1px solid #eee; border-radius: 8px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            
            <img src="<?= (!empty($pet_info['image']) && file_exists(__DIR__ . '/../uploads/' . $pet_info['image'])) ? '../uploads/' . htmlspecialchars($pet_info['image']) : '../assets/images/default_pet.png'; ?>" alt="Pet Image" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; margin-right: 15px; border: 1px solid #ddd;">
            
            <div class="pet-card-attachment-info" style="display: flex; flex-direction: column; gap: 4px; font-size: 0.9em;">
                <strong style="color: #333; font-size: 1.1em;"><?= htmlspecialchars($pet_info['name'] ?? ''); ?></strong>
                <span style="color: #666;"><?= htmlspecialchars($pet_info['breed'] ?? ''); ?></span>
                <span style="color: #d47357; font-weight: 600;">Price: $<?= htmlspecialchars($pet_info['price'] ?? '0.00'); ?></span>
            </div>
            <span class="remove-pet" onclick="removePetAttachment(event)">×</span>
        </div>
    <?php endif; ?>
</div>

            <div class="chat-footer" id="chat-box-footer" style="display: none;">
                <input type="text" placeholder="Type a message..." class="chat-input" id="chat-input-field">
                <button class="chat-send-btn" title="Send Message" id="send-btn">
                    &#10148;
                </button>
            </div>
        </div>
    </div>
  </div>
</div>

<div id="petModal" class="modal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
    <div class="modal-content" style="background:#fff; padding:25px; border-radius:12px; width:90%; max-width:480px; position:relative; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        <span class="close-btn" onclick="closePetModal()" style="position:absolute; right:18px; top:15px; font-size:28px; cursor:pointer; color:#666;">&times;</span>
        
        <h3 id="modal-pet-name" style="color:#d47357; margin-bottom:15px; font-size: 1.4em;">Pet Name</h3>
        <img id="modal-pet-img" src="" alt="Pet Image" style="width:100%; height:220px; object-fit:cover; border-radius:8px; margin-bottom:15px; border:1px solid #eee;">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; font-size: 0.95em;">
            <div>
                <strong>Species:</strong> 
                <span id="modal-pet-species" style="color: #444; display:block;">—</span>
            </div>
            <div>
                <strong>Breed:</strong> 
                <span id="modal-pet-breed" style="color: #444; display:block;">—</span>
            </div>
            <div>
                <strong>Sex:</strong> 
                <span id="modal-pet-sex" style="color: #444; display:block;">—</span>
            </div>
            <div>
                <strong>Age:</strong> 
                <span id="modal-pet-age" style="color: #444; display:block;">—</span>
            </div>
            <div>
                <strong>Location:</strong> 
                <span id="modal-pet-location" style="color: #444; display:block;">—</span>
            </div>
            <div>
                <strong>City:</strong> 
                <span id="modal-pet-city" style="color: #444; display:block;">—</span>
            </div>
            <div>
                <strong>Status:</strong> 
                <span id="modal-pet-status" style="color: #d47357; font-weight: 600; display:block;">—</span>
            </div>
            <div>
                <strong>For Sale:</strong> 
                <span id="modal-pet-is-sale" style="color: #444; display:block;">No</span>
            </div>
            <div>
                <strong>Price:</strong> 
                <span id="modal-pet-price" style="color: #d47357; font-weight: 600; display:block;">—</span>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
        
        <p style="color: #666; font-size: 0.95em; line-height: 1.4; max-height: 150px; overflow-y: auto;" id="modal-pet-desc">—</p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    var currentReceiverId = null;

    function showPetModal(element) {
        openPetModal(element);
    }

    function loadChatList() {
        $.ajax({
            url: 'fetch_chats.php',
            method: 'GET',
            success: function(data) {
                $('#chat-list-container').html(data);
            }
        });
    }

    function switchChat(receiverId, name, avatar) {
        currentReceiverId = receiverId;
        
        $('#chat-box-header').show();
        $('#chat-box-footer').show();
        if($('#pet-preview-card').length > 0) {
            $('#pet-preview-container').show();
        }
        
        $('#active-chat-name').text(name);
        $('#active-chat-img').attr('src', avatar);
        
        loadMessages();
        $('.chat-user-item').removeClass('active');
    }

    function loadMessages() {
    if (!currentReceiverId) return;

    $.ajax({
        url: 'fetch_messages.php?receiver_id=' + currentReceiverId,
        method: 'GET',
        success: function(data) {
            $('#placeholder-box').hide();

            var chatBody = $('.chat-body');
            chatBody.html(data);

            setTimeout(function () {
                chatBody.scrollTop(chatBody[0].scrollHeight);
            }, 50);
        }
    });
}

    function removePetAttachment(e) {
        if (e) e.stopPropagation();
        $('#pet-preview-card').remove();
        $('#pet-preview-container').hide();
        window.history.replaceState({}, document.title, "chatbox.php?receiver_id=" + currentReceiverId);
    }

    function openPetModal(element) {

        // ✅ FIX: ensure correct element (handles inner div clicks)
        if (!element.getAttribute('data-name')) {
            element = element.closest('.message-pet-card-attachment') || element;
        }

        var name = element.getAttribute('data-name');
        var image = element.getAttribute('data-img') || '../assets/images/default_pet.png';
        var species = element.getAttribute('data-species');
        var breed = element.getAttribute('data-breed');
        var sex = element.getAttribute('data-sex');
        var age = element.getAttribute('data-age');
        var location = element.getAttribute('data-location');
        var city = element.getAttribute('data-city');
        var status = element.getAttribute('data-status');
        var desc = element.getAttribute('data-desc');
        var price = element.getAttribute('data-price');

        console.log("Reading data-price:", price);

        document.getElementById('modal-pet-name').textContent = 'Interested in ' + (name || 'N/A');
        document.getElementById('modal-pet-img').src = image;

        const fields = [
            { id: 'modal-pet-species', val: species },
            { id: 'modal-pet-breed', val: breed },
            { id: 'modal-pet-sex', val: sex },
            { id: 'modal-pet-age', val: age },
            { id: 'modal-pet-location', val: location },
            { id: 'modal-pet-city', val: city },
            { id: 'modal-pet-status', val: status },
            { id: 'modal-pet-desc', val: desc }
        ];

        fields.forEach(field => {
            document.getElementById(field.id).textContent =
                (field.val && field.val.trim() !== '') ? field.val : '—';
        });

        // ✅ FIXED price handling
        if (price !== null && price !== "") {
            document.getElementById('modal-pet-is-sale').textContent = 'Yes';

            var parsedPrice = parseFloat(price);
            if (!isNaN(parsedPrice)) {
                document.getElementById('modal-pet-price').textContent = '$' + parsedPrice.toFixed(2);
            } else {
                document.getElementById('modal-pet-price').textContent = price;
            }
        } else {
            document.getElementById('modal-pet-is-sale').textContent = 'No';
            document.getElementById('modal-pet-price').textContent = '—';
        }

        document.getElementById('petModal').style.setProperty('display', 'flex', 'important');
    }

    function closePetModal() {
        document.getElementById('petModal').style.setProperty('display', 'none', 'important');
    }

    window.onclick = function(e) {
        var modal = document.getElementById('petModal');
        if (e.target == modal) {
            modal.style.setProperty('display', 'none', 'important');
        }
    }

    // ❌ REMOVED BROKEN HANDLER (.pet-attachment-card)

    // ✅ CORRECT HANDLER (THIS FIXES YOUR BUG)
    $(document).on('click', '.message-pet-card-attachment', function(e) {
        openPetModal(this);
    });

    function sendMessage() {
        var msgText = $('#chat-input-field').val().trim();
        
        var petId = null;
        if($('#pending_pet_id').length > 0) {
            petId = $('#pending_pet_id').val();
        }

        if ((msgText !== '' || petId) && currentReceiverId) {
            $.ajax({
                url: 'send_message.php',
                method: 'POST',
                data: {
                    receiver_id: currentReceiverId,
                    message: msgText,
                    pet_id: petId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#chat-input-field').val('');
                        
                        if (petId) {
                            removePetAttachment();
                            window.history.replaceState({}, document.title, "chatbox.php?receiver_id=" + currentReceiverId);
                        }

                        loadMessages();
                        loadChatList();
                    }
                }
            });
        }
    }

    setInterval(function() {
        if (currentReceiverId) {
            loadMessages();
        }
        loadChatList();
    }, 2000);

    $(document).ready(function() {
        loadChatList();

        <?php if ($receiver_id_from_url && $receiver_info): ?>
            var autoReceiverId = <?= $receiver_id_from_url; ?>;
            var autoName = "<?= htmlspecialchars($receiver_info['fullname'], ENT_QUOTES); ?>";
            var autoAvatar = "<?= !empty($receiver_info['profile_img']) && file_exists('../uploads/' . $receiver_info['profile_img']) ? '../uploads/' . $receiver_info['profile_img'] : '../uploads/default_avatar.png'; ?>";
            
            switchChat(autoReceiverId, autoName, autoAvatar);
        <?php endif; ?>

        $('#chat-search-input').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#chat-list-container .chat-user-item').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });

        $('#send-btn').click(sendMessage);

        $('#chat-input-field').keypress(function(e) {
            if (e.which == 13) {
                sendMessage();
                return false;
            }
        });
    });
</script>
</body>
</html>