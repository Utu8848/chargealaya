<?php
$page_title = "Add Vehicle";
include '../partials/header.php';
requireLogin();

if (!hasRole('ev_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

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
        $stmt = $conn->prepare("INSERT INTO vehicles (user_id, brand, model, battery_capacity_kwh, connector_type, license_plate, manufacturing_year) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdssi", $user_id, $brand, $model, $battery_capacity, $connector_type, $license_plate, $manufacturing_year);
        
        if ($stmt->execute()) {
            setFlashMessage("Vehicle added successfully!", "success");
            header("Location: vehicles.php");
            exit();
        } else {
            $errors[] = "Failed to add vehicle. Please try again.";
        }
        $stmt->close();
    }
    
    foreach ($errors as $error) {
        setFlashMessage($error, "danger");
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Add New Vehicle</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="brand" class="form-label">Brand *</label>
                                <input type="text" class="form-control" id="brand" name="brand" required
                                       placeholder="e.g., Tesla, Nissan, BYD"
                                       value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="model" class="form-label">Model *</label>
                                <input type="text" class="form-control" id="model" name="model" required
                                       placeholder="e.g., Model 3, Leaf, Han"
                                       value="<?php echo isset($_POST['model']) ? htmlspecialchars($_POST['model']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="battery_capacity_kwh" class="form-label">Battery Capacity (kWh) *</label>
                                <input type="number" class="form-control" id="battery_capacity_kwh" 
                                       name="battery_capacity_kwh" step="0.01" min="1" required
                                       placeholder="e.g., 75"
                                       value="<?php echo isset($_POST['battery_capacity_kwh']) ? $_POST['battery_capacity_kwh'] : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="connector_type" class="form-label">Connector Type *</label>
                                <select class="form-control" id="connector_type" name="connector_type" required>
                                    <option value="">Select Connector Type</option>
                                    <option value="CCS2" <?php echo (isset($_POST['connector_type']) && $_POST['connector_type'] === 'CCS2') ? 'selected' : ''; ?>>CCS2 (Combined Charging System)</option>
                                    <option value="CHAdeMO" <?php echo (isset($_POST['connector_type']) && $_POST['connector_type'] === 'CHAdeMO') ? 'selected' : ''; ?>>CHAdeMO</option>
                                    <option value="Type2" <?php echo (isset($_POST['connector_type']) && $_POST['connector_type'] === 'Type2') ? 'selected' : ''; ?>>Type 2 (Mennekes)</option>
                                    <option value="GB/T" <?php echo (isset($_POST['connector_type']) && $_POST['connector_type'] === 'GB/T') ? 'selected' : ''; ?>>GB/T</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="license_plate" class="form-label">License Plate</label>
                                <input type="text" class="form-control" id="license_plate" name="license_plate"
                                       placeholder="e.g., BA-01-PA-1234"
                                       value="<?php echo isset($_POST['license_plate']) ? htmlspecialchars($_POST['license_plate']) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="manufacturing_year" class="form-label">Manufacturing Year *</label>
                                <input type="number" class="form-control" id="manufacturing_year" 
                                       name="manufacturing_year" min="2000" max="<?php echo date('Y') + 1; ?>" required
                                       placeholder="<?php echo date('Y'); ?>"
                                       value="<?php echo isset($_POST['manufacturing_year']) ? $_POST['manufacturing_year'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Tip:</strong> Make sure the connector type matches the chargers you'll be using at stations.
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between">
                            <a href="vehicles.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-custom">
                                <i class="fas fa-save"></i> Add Vehicle
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
