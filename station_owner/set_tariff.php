<?php
$page_title = "Set Pricing";
include '../partials/header.php';
requireStationOwner();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

$station_id = isset($_GET['station_id']) ? intval($_GET['station_id']) : 0;

if ($station_id === 0) {
    header("Location: manage_stations.php");
    exit();
}

// Verify station ownership
$station_check = $conn->prepare("SELECT station_name FROM charging_stations WHERE station_id = ? AND user_id = ?");
$station_check->bind_param("ii", $station_id, $user_id);
$station_check->execute();
$station_result = $station_check->get_result();

if ($station_result->num_rows === 0) {
    setFlashMessage("Station not found or access denied.", "danger");
    header("Location: manage_stations.php");
    exit();
}

$station = $station_result->fetch_assoc();
$station_check->close();

// Get existing tariff
$tariff_query = "SELECT * FROM tariffs WHERE station_id = $station_id";
$tariff_result = $conn->query($tariff_query);
$existing_tariff = $tariff_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_fee = floatval($_POST['service_fee']);
    $price_per_kwh = floatval($_POST['price_per_kwh']);
    $peak_start_time = sanitize($_POST['peak_start_time']);
    $peak_end_time = sanitize($_POST['peak_end_time']);
    
    $errors = [];
    
    if ($service_fee < 0 || $price_per_kwh <= 0) {
        $errors[] = "Invalid pricing values.";
    }
    
    if (empty($errors)) {
        if ($existing_tariff) {
            // Update existing tariff
            $stmt = $conn->prepare("UPDATE tariffs SET service_fee = ?, price_per_kwh = ?, peak_start_time = ?, peak_end_time = ? WHERE station_id = ?");
            $stmt->bind_param("ddssi", $service_fee, $price_per_kwh, $peak_start_time, $peak_end_time, $station_id);
        } else {
            // Insert new tariff
            $stmt = $conn->prepare("INSERT INTO tariffs (station_id, service_fee, price_per_kwh, peak_start_time, peak_end_time) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iddss", $station_id, $service_fee, $price_per_kwh, $peak_start_time, $peak_end_time);
        }
        
        if ($stmt->execute()) {
            setFlashMessage("Pricing updated successfully!", "success");
            header("Location: manage_stations.php");
            exit();
        } else {
            $errors[] = "Failed to update pricing. Please try again.";
        }
        $stmt->close();
    }
    
    foreach ($errors as $error) {
        setFlashMessage($error, "danger");
    }
}

// Set form values
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $existing_tariff) {
    $_POST = $existing_tariff;
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3><i class="fas fa-dollar-sign"></i> Set Pricing</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong><i class="fas fa-charging-station"></i> Station:</strong> <?php echo htmlspecialchars($station['station_name']); ?>
                    </div>
                    
                    <form method="POST" action="">
                        <h5 class="mb-3">Pricing Structure</h5>
                        
                        <div class="mb-3">
                            <label for="service_fee" class="form-label">Service Fee (NPR) *</label>
                            <input type="number" class="form-control" id="service_fee" name="service_fee" 
                                   step="0.01" min="0" required
                                   value="<?php echo isset($_POST['service_fee']) ? $_POST['service_fee'] : '50.00'; ?>"
                                   placeholder="e.g., 50.00">
                            <small class="text-muted">Fixed fee per charging session</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price_per_kwh" class="form-label">Price per kWh (NPR) *</label>
                            <input type="number" class="form-control" id="price_per_kwh" name="price_per_kwh" 
                                   step="0.01" min="0.01" required
                                   value="<?php echo isset($_POST['price_per_kwh']) ? $_POST['price_per_kwh'] : '15.00'; ?>"
                                   placeholder="e.g., 15.00">
                            <small class="text-muted">Cost per kilowatt-hour of energy</small>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Peak Hours (Optional)</h5>
                        <p class="text-muted">Define peak hours for time-based pricing (future feature)</p>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="peak_start_time" class="form-label">Peak Start Time</label>
                                <input type="time" class="form-control" id="peak_start_time" name="peak_start_time"
                                       value="<?php echo isset($_POST['peak_start_time']) ? htmlspecialchars($_POST['peak_start_time']) : '18:00'; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="peak_end_time" class="form-label">Peak End Time</label>
                                <input type="time" class="form-control" id="peak_end_time" name="peak_end_time"
                                       value="<?php echo isset($_POST['peak_end_time']) ? htmlspecialchars($_POST['peak_end_time']) : '22:00'; ?>">
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <strong><i class="fas fa-info-circle"></i> Pricing Example:</strong><br>
                            <?php 
                            $example_service = isset($_POST['service_fee']) ? $_POST['service_fee'] : 50;
                            $example_price = isset($_POST['price_per_kwh']) ? $_POST['price_per_kwh'] : 15;
                            $example_kwh = 50;
                            $example_total = $example_service + ($example_price * $example_kwh);
                            ?>
                            For a session using <strong>50 kWh</strong>:<br>
                            Service Fee: NPR <?php echo number_format($example_service, 2); ?><br>
                            Energy Cost: NPR <?php echo number_format($example_price, 2); ?> × 50 = NPR <?php echo number_format($example_price * $example_kwh, 2); ?><br>
                            <strong>Total: NPR <?php echo number_format($example_total, 2); ?></strong>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between">
                            <a href="manage_stations.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> <?php echo $existing_tariff ? 'Update' : 'Set'; ?> Pricing
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/chargealaya/assets/js/scripts/set_tariff.js"></script>

<?php
$conn->close();
include '../partials/footer.php';
?>
