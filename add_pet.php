<?php
session_start();
require_once "conn.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$showSuccess = false;
$showError = false;
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $species = trim($_POST["species"]);
    $breed = trim($_POST["breed"]);
    $sex = $_POST["sex"];
    $age = (int)$_POST["age"];
    $age_unit = $_POST["age_unit"];
    $description = trim($_POST["description"]);
    $owner_id = $_SESSION["user_id"];
    $imageName = null;

    // Health info
    $vaccinated = $_POST['vaccinated'];
    $dewormed = $_POST['dewormed'];
    $neutered = $_POST['neutered'];
    $medical_notes = $_POST['medical_notes'] ?? null;

    // Handle pet image upload
    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $imageName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $imageName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg","jpeg","png","gif"];

        if (!in_array($fileType, $allowedTypes)) {
            $showError = true;
            $errorMessage = "Invalid image type. Only JPG, PNG, GIF allowed.";
        } else {
            move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile);
        }
    } else {
        $showError = true;
        $errorMessage = "Please upload an image of the pet.";
    }

    if (!$showError) {
        // Insert pet record
        $location = trim($_POST["location"]);
        $city = trim($_POST["city"]);

        $stmt = $pdo->prepare("
            INSERT INTO pets (name, species, breed, sex, age, age_unit, description, image, owner_id, location, city, status) 
            VALUES (:name, :species, :breed, :sex, :age, :age_unit, :description, :image, :owner_id, :location, :city, 'pending')
        ");

        $stmt->execute([
            "name" => $name,
            "species" => $species,
            "breed" => $breed,
            "sex" => $sex,
            "age" => $age,
            "age_unit" => $age_unit,
            "description" => $description,
            "image" => $imageName,
            "owner_id" => $owner_id,
            "location" => $location,
            "city" => $city
        ]);

        $pet_id = $pdo->lastInsertId();

        // Handle pet sale only if checked
        if (isset($_POST['for_sale'])) {
            $amount = floatval($_POST['amount'] ?? 0);
            $payment_method = $_POST['payment_method'] ?? '';

            if ($amount > 0 && !empty($payment_method)) {
                $sale = $pdo->prepare("
                    INSERT INTO pet_sales (pet_id, price, payment_method)
                    VALUES (:pet_id, :price, :payment_method)
                ");
                $sale->execute([
                    ':pet_id' => $pet_id,
                    ':price' => $amount,
                    ':payment_method' => $payment_method
                ]);
            } else {
                // Invalid sale data
                $showError = true;
                $errorMessage = "Please provide a valid price and payment method for pets for sale.";
            }
        }

        // Handle optional medical file
        $medical_file_name = null;
        if (!empty($_FILES['medical_file']['name'])) {
            $targetDir = "../uploads/medical/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $medical_file_name = time() . "_" . uniqid() . "_" . basename($_FILES["medical_file"]["name"]);
            $targetFile = $targetDir . $medical_file_name;
            move_uploaded_file($_FILES["medical_file"]["tmp_name"], $targetFile);
        }

        // Insert health + medical info (without last_checkup)
        $health = $pdo->prepare("
            INSERT INTO pet_health_records 
            (pet_id, vaccinated, dewormed, neutered, medical_notes, medical_file)
            VALUES (:pet_id, :vaccinated, :dewormed, :neutered, :medical_notes, :medical_file)
        ");
        $health->execute([
            ':pet_id' => $pet_id,
            ':vaccinated' => $vaccinated,
            ':dewormed' => $dewormed,
            ':neutered' => $neutered,
            ':medical_notes' => $medical_notes,
            ':medical_file' => $medical_file_name
        ]);

        if (!$showError) {
            $showSuccess = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Pet | FurEver Pawmily</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/sidebar_owner.css">
<link rel="stylesheet" href="../css/add_pet.css">

</head>
<body>

<?php include 'sidebar_owner.php'; ?>

<div class="main-content">
  <header>
    <h1>➕ Add New Pet</h1>
    <p>Fill in the details below to submit a pet for adoption. Payment is required for Selling.</p>
  </header>

  <div class="container">
    <h2>🐾 Add Pet</h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="text" name="name" placeholder="Pet Name" required>

      <select name="species" id="species" required>
        <option value="">Select Species</option>
        <option value="Dog">Dog</option>
        <option value="Cat">Cat</option>
        <option value="Rabbit">Rabbit</option>
      </select>

      <select name="breed" id="breed" required>
        <option value="">Select Breed</option>
      </select>

      <select name="sex" id="sex" required>
        <option value="">Select Sex</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
      </select>

      <div style="display:flex; gap:10px; align-items:center;">
        <input type="number" name="age" placeholder="Age" min="0" required>
        <select name="age_unit" required>
            <option value="years">Years</option>
            <option value="months">Months</option>
        </select>
      </div>

      <div class="form-group">
          <label for="location">Pet's Current Location (Street/District)</label>
          <input type="text" name="location" id="location" placeholder="e.g., 123 Animal St., Brgy. San Jose" required>
      </div>

      <div class="form-group">
          <label for="city">City/Province</label>
          <input type="text" name="city" id="city" placeholder="e.g., Quezon City" required>
      </div>

      <textarea name="description" rows="3" placeholder="Pet description..." required></textarea>

      <label for="image">Upload Pet Image</label>
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif" required>

      <h3>🩺 Health Record</h3>

      <select name="vaccinated" required>
        <option value="">Vaccinated?</option>
        <option value="Yes">Yes</option>
        <option value="No">No</option>
      </select>

      <select name="dewormed" required>
        <option value="">Dewormed?</option>
        <option value="Yes">Yes</option>
        <option value="No">No</option>
      </select>

      <select name="neutered" required>
        <option value="">Spayed / Neutered?</option>
        <option value="Yes">Yes</option>
        <option value="No">No</option>
      </select>

      <textarea name="medical_notes" rows="3" placeholder="Additional medical notes..."></textarea>

      <label for="medical_file">Upload Medical Document (optional)</label>
      <input type="file" name="medical_file" accept=".pdf,.jpg,.png,.doc,.docx">

      <label class="checkbox-label">
        <input type="checkbox" id="forSaleCheckbox" name="for_sale">
        <span class="label-text">For Sale</span>
      </label>

        <div id="paymentSection" style="display:none;">
          <h3 style="margin-top:35px;">Payment Details</h3>
          
          <label>Amount:</label>
          <input type="number" name="amount" placeholder="Php">

          <label>Payment Method:</label>
          <select name="payment_method">
              <option value="gcash">GCash</option>
              <option value="paypal">PayPal</option>
          </select>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-submit">Add Pet</button>
          <button type="button" class="btn-cancel" onclick="window.location.href='pending_pets.php'">Cancel</button>
        </div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const breeds = {
        Dog: ["Aspin","Labrador", "Bulldog", "Poodle", "Shih Tzu", "Golden Retriever", "Beagle", "German Shepherd"],
        Cat: ["Pusin","Persian", "Siamese", "Maine Coon", "British Shorthair", "Ragdoll","Calico","Other"],
        Rabbit: ["Holland Lop", "Netherland Dwarf", "Mini Rex", "Lionhead"]
    };
    const speciesSelect = document.getElementById("species");
    const breedSelect = document.getElementById("breed");

    speciesSelect.addEventListener("change", () => {
        const selected = speciesSelect.value;
        breedSelect.innerHTML = "<option value=''>Select Breed</option>";
        if (breeds[selected]) {
            breeds[selected].forEach(b => {
                const opt = document.createElement("option");
                opt.value = b;
                opt.textContent = b;
                breedSelect.appendChild(opt);
            });
        }
    });

    <?php if($showSuccess): ?>
    Swal.fire({
        icon: 'success',
        title: 'Pet Added!',
        text: 'Your pet has been submitted successfully and is waiting for admin approval.',
        confirmButtonColor: '#d47357'
    });
    <?php endif; ?>

    <?php if($showError): ?>
    Swal.fire({
        icon: 'error',
        title: 'Oops!',
        text: '<?= $errorMessage; ?>',
        confirmButtonColor: '#d47357'
    });
    <?php endif; ?>
});
</script>

<script>
document.getElementById("forSaleCheckbox").addEventListener("change", function() {
    var paymentSection = document.getElementById("paymentSection");

    if (this.checked) {
        paymentSection.style.display = "block";
    } else {
        paymentSection.style.display = "none";
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>