<?php
$page_title = "Edit Vehicle";
include '../partials/header.php';
requireLogin();

if (!hasRole('ev_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

$vehicle_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($vehicle_id === 0) {
    header("Location: vehicles.php");
    exit();
}

// Get vehicle
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicle_id = ? AND user_id = ?");
$stmt->bind_param("ii", $vehicle_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage("Vehicle not found.", "danger");
    header("Location: vehicles.php");
    exit();
}

$vehicle = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = sanitize($_POST['brand']);
    $model = sanitize($_POST['model']);
    $battery_capacity = floatval($_POST['battery_capacity_kwh']);
    $connector_type = sanitize($_POST['connector_type']);
    $license_plate = sanitize($_POST['license_plate']);
    $manufacturing_year = intval($_POST['manufacturing_year']);
    
    $errors = [];
    
    if (empty($brand) || empty($model)) {
        $errors[] = "Brand and model are required.";
    }
    
    if ($battery_capacity <= 0) {
        $errors[] = "Battery capacity must be greater than 0.";
    }
    
    if ($manufacturing_year < 2000 || $manufacturing_year > date('Y') + 1) {
        $errors[] = "Invalid manufacturing year.";
    }
    
    if (empty($errors)) {
        $update_stmt = $conn->prepare("UPDATE vehicles SET brand = ?, model = ?, battery_capacity_kwh = ?, connector_type = ?, license_plate = ?, manufacturing_year = ? WHERE vehicle_id = ? AND user_id = ?");
        $update_stmt->bind_param("ssdssiii", $brand, $model, $battery_capacity, $connector_type, $license_plate, $manufacturing_year, $vehicle_id, $user_id);
        
        if ($update_stmt->execute()) {
            setFlashMessage("Vehicle updated successfully!", "success");
            header("Location: vehicles.php");
            exit();
        } else {
            $errors[] = "Failed to update vehicle. Please try again.";
        }
        $update_stmt->close();
    }
    
    foreach ($errors as $error) {
        setFlashMessage($error, "danger");
    }
} else {
    $_POST = $vehicle;
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Edit Vehicle</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="brand" class="form-label">Brand *</label>
                                <input type="text" class="form-control" id="brand" name="brand" required
                                       value="<?php echo htmlspecialchars($_POST['brand']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="model" class="form-label">Model *</label>
                                <input type="text" class="form-control" id="model" name="model" required
                                       value="<?php echo htmlspecialchars($_POST['model']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="battery_capacity_kwh" class="form-label">Battery Capacity (kWh) *</label>
                                <input type="number" class="form-control" id="battery_capacity_kwh" 
                                       name="battery_capacity_kwh" step="0.01" min="1" required
                                       value="<?php echo $_POST['battery_capacity_kwh']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="connector_type" class="form-label">Connector Type *</label>
                                <select class="form-control" id="connector_type" name="connector_type" required>
                                    <option value="">Select Connector Type</option>
                                    <option value="CCS2" <?php echo ($_POST['connector_type'] === 'CCS2') ? 'selected' : ''; ?>>CCS2 (Combined Charging System)</option>
                                    <option value="CHAdeMO" <?php echo ($_POST['connector_type'] === 'CHAdeMO') ? 'selected' : ''; ?>>CHAdeMO</option>
                                    <option value="Type2" <?php echo ($_POST['connector_type'] === 'Type2') ? 'selected' : ''; ?>>Type 2 (Mennekes)</option>
                                    <option value="GB/T" <?php echo ($_POST['connector_type'] === 'GB/T') ? 'selected' : ''; ?>>GB/T</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="license_plate" class="form-label">License Plate</label>
                                <input type="text" class="form-control" id="license_plate" name="license_plate"
                                       value="<?php echo htmlspecialchars($_POST['license_plate']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="manufacturing_year" class="form-label">Manufacturing Year *</label>
                                <input type="number" class="form-control" id="manufacturing_year" 
                                       name="manufacturing_year" min="2000" max="<?php echo date('Y') + 1; ?>" required
                                       value="<?php echo $_POST['manufacturing_year']; ?>">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between">
                            <a href="vehicles.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-custom">
                                <i class="fas fa-save"></i> Update Vehicle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
