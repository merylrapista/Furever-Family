<?php
session_start();
require_once "conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['pet_id'])) {
    die("Pet not selected.");
}

$pet_id = $_GET['pet_id'];

// Fetch pet info
$stmt = $pdo->prepare("SELECT * FROM pets WHERE id = :pid");
$stmt->execute([':pid' => $pet_id]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pet) {
    die("Pet not found.");
}

// Fetch price from pet_sales to be accurate
$price_stmt = $pdo->prepare("SELECT price FROM pet_sales WHERE pet_id = :pet_id LIMIT 1");
$price_stmt->execute(['pet_id' => $pet_id]);
$sale_record = $price_stmt->fetch(PDO::FETCH_ASSOC);

// Populate the price so it evaluates correctly in JavaScript
$pet['price'] = isset($sale_record['price']) ? (float)$sale_record['price'] : 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Adoption Agreement | FurEver Pawmily</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/agreement.css">
</head>
<body>

<div class="agreement-box">
    <h2>🐾 Pet Adoption Agreement</h2>

    <form id="agreementForm">
        <input type="hidden" name="pet_id" value="<?= $pet_id ?>">

        <label><strong>Agreement Terms:</strong></label>

        <div style="max-height:400px; overflow-y:auto; padding:12px; border:1px solid #ccc; border-radius:8px; margin-bottom:20px; background:#fdf8f2;">
            <p>Welcome to FurEver Pawmily! By adopting this pet, you agree to the following terms and conditions. Please read carefully before proceeding.</p>

            <h4>1. Care Responsibilities</h4>
            <ul>
                <li>I will provide adequate food, clean water, and a safe shelter for the pet at all times.</li>
                <li>I will provide regular veterinary care, vaccinations, and preventive treatments as recommended for the pet’s breed and age.</li>
                <li>I will not neglect, abandon, sell, or intentionally harm the pet.</li>
                <li>I will ensure the pet is exercised, socialized, and mentally stimulated according to its needs.</li>
                <li>I understand that adopting a pet is a long-term commitment that may last for the pet's entire lifetime.</li>
            </ul>

            <h4>2. Legal Compliance</h4>
            <ul>
                <li>I will comply with all applicable local, regional, and national animal welfare laws and regulations.</li>
                <li>I understand that violation of any law, ordinance, or adoption agreement may result in legal action and/or repossession of the pet.</li>
                <li>I will not engage in illegal breeding, selling, or trading of pets obtained through this platform.</li>
            </ul>

            <h4>3. Adoption & Ownership Policies</h4>
            <ul>
                <li>I acknowledge that FurEver Pawmily is a facilitator for pet adoptions and that ownership transfer occurs between me and the pet’s current owner.</li>
                <li>I accept all medical records and histories provided at the time of adoption but understand that they may not guarantee future health outcomes.</li>
                <li>I will update my contact information with FurEver Pawmily in case of relocation or change in circumstances that may affect pet care.</li>
            </ul>

            <h4>4. Responsibility for Pet Behavior</h4>
            <ul>
                <li>I accept full responsibility for the pet’s behavior and understand that any aggressive or destructive acts are my responsibility.</li>
                <li>I will take appropriate precautions to prevent harm to people, other animals, or property.</li>
                <li>I will report any lost or injured pets to the relevant authorities and the platform immediately.</li>
            </ul>

            <h4>5. Limitation of Liability</h4>
            <ul>
                <li>I understand that FurEver Pawmily is not liable for any injury, illness, or damages caused by the pet after adoption.</li>
                <li>I release FurEver Pawmily and its affiliates from any claims arising from the pet’s health, behavior, or property damage following adoption.</li>
            </ul>

            <h4>6. Ethical & Humane Treatment</h4>
            <ul>
                <li>I will provide humane treatment and respect the pet as a living being with physical and emotional needs.</li>
                <li>I will not use the pet for any form of abuse, neglect, entertainment that causes distress, or experimental purposes.</li>
                <li>I understand that cruelty or neglect will result in immediate revocation of the adoption agreement and legal consequences.</li>
            </ul>

            <h4>7. Fees and Expenses</h4>
            <ul>
                <li>I acknowledge any adoption fees, donations, or mandatory payments associated with the pet and agree to pay them as required.</li>
                <li>I accept responsibility for ongoing expenses including food, medical care, grooming, and emergency care.</li>
            </ul>

            <h4>8. Return or Repossession</h4>
            <ul>
                <li>If I am unable to care for the pet, I will notify FurEver Pawmily to arrange a safe return or rehoming.</li>
                <li>I understand that abandoning the pet or failing to comply with this agreement may result in repossession without compensation.</li>
            </ul>

            <h4>9. Data and Privacy</h4>
            <ul>
                <li>I consent to the collection and use of my personal information for adoption processing, communication, and legal compliance.</li>
                <li>I agree that my information may be shared with relevant authorities or organizations if required by law.</li>
            </ul>

            <h4>10. Acknowledgment</h4>
            <ul>
                <li>By checking the boxes below and submitting this agreement, I confirm that I have read, understood, and agree to all the terms and conditions stated above.</li>
                <li>I understand that this agreement is legally binding and enforceable under applicable law.</li>
            </ul>
            
            <h4>11. Privacy</h4>
            <ul>
                <li>Our use of personal data is governed by our Privacy Policy. By using the platform, you consent to data collection and processing as outlined in the Privacy Policy.</li>
            </ul>

            <h4>12. Third-Party Links and Services</h4>
            <ul>
                <li>The platform may contain links to third-party websites or services.</li>
                <li>FurEver Pawmily is not responsible for third-party content, policies, or practices.</li>
            </ul>

            <h4>13. Dispute Resolution</h4>
            <ul>
                <li>Any disputes arising from these Terms will be governed by the laws of [Insert Country/Region].</li>
                <li>Users agree to resolve disputes through amicable negotiation before pursuing legal action.</li>
            </ul>

            <h4>14. Changes to Terms</h4>
            <ul>
                <li>FurEver Pawmily may update these Terms at any time.</li>
                <li>Continued use of the platform constitutes acceptance of the updated Terms.</li>
            </ul>

            <h4>15. Contact</h4>
            <p>For questions regarding these Terms, please contact us at: [Insert contact email or form]</p>
        </div>

        <div style="margin-bottom:20px;">
            <label>
                <input type="checkbox" class="terms-checkbox" name="care_responsibilities" value="1"> I have read and understand the Care Responsibilities.
            </label><br>
            <label>
                <input type="checkbox" class="terms-checkbox" name="legal_compliance" value="1"> I have read and understand the Legal Compliance terms.
            </label><br>
            <label>
                <input type="checkbox" class="terms-checkbox" name="adoption_sale" value="1"> I have read and understand the Adoption & Sale Policies.
            </label><br>
            <label>
                <input type="checkbox" class="terms-checkbox" name="pet_behavior" value="1"> I accept Responsibility for Pet Behavior.
            </label><br>
            <label>
                <input type="checkbox" class="terms-checkbox" name="limitation_liability" value="1"> I understand the Limitation of Liability.
            </label>
        </div>

        <div class="buttons-wrapper">
            <button type="submit" class="btn btn-agree" id="agreeBtn" disabled>I Agree & Submit</button>
            <button type="button" class="btn btn-cancel" onclick="window.location.href='browse_pets.php'">Cancel</button>
        </div>
    </form>
</div>

<script>
const checkboxes = document.querySelectorAll('.terms-checkbox');
const agreeBtn = document.getElementById('agreeBtn');

// Enable/Disable agreement button
checkboxes.forEach(cb => {
    cb.addEventListener('change', () => {
        const allChecked = Array.from(checkboxes).every(c => c.checked);
        agreeBtn.disabled = !allChecked;
    });
});

document.getElementById('agreementForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);

    fetch('save_agreement.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            Swal.fire({
                title: '✅ Agreement Submitted!',
                text: 'Your adoption request has been successfully recorded.',
                icon: 'success',
                confirmButtonText: 'OK',
                background: '#fffaf5',
                customClass: { confirmButton: 'swal2-confirm-custom' }
            }).then(() => {
                const petPrice = parseFloat(<?= json_encode($pet['price'] ?? 0); ?>);
                const petId = <?= $pet_id; ?>;

                if (petPrice > 0) {
                    // Redirect to payment gateway with correct ?id= parameter
                    window.location.href = 'pet_payment.php?id=' + petId;
                } else {
                    // Go straight to the request board for free adoptions
                    window.location.href = 'request.php';
                }
            });
        } else {
            Swal.fire({
                title: '❌ Error',
                text: data.message || 'Something went wrong.',
                icon: 'error',
                confirmButtonText: 'OK',
                background: '#fffaf5',
                customClass: { confirmButton: 'swal2-confirm-custom' }
            });
        }
    })
    .catch(err => {
        Swal.fire({
            title: '❌ Error',
            text: 'Network or server error.',
            icon: 'error',
            confirmButtonText: 'OK',
            background: '#fffaf5',
            customClass: { confirmButton: 'swal2-confirm-custom' }
        });
    });
});
</script>

<style>
.swal2-confirm-custom {
    background: #d47357 !important;
    color: white !important;
    font-weight: bold;
    border-radius: 8px !important;
    padding: 10px 20px !important;
    transition: 0.3s;
}
.swal2-confirm-custom:hover { background: #b85b3f !important; }
</style>

</body>
</html>