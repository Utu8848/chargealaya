<?php
$page_title = "Edit Station";
include '../partials/header.php';
requireStationOwner();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

$station_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($station_id === 0) {
    header("Location: manage_stations.php");
    exit();
}

// Verify station ownership
$station_check = $conn->prepare("SELECT * FROM charging_stations WHERE station_id = ? AND user_id = ?");
$station_check->bind_param("ii", $station_id, $user_id);
$station_check->execute();
$result = $station_check->get_result();

if ($result->num_rows === 0) {
    setFlashMessage("Station not found or access denied.", "danger");
    header("Location: manage_stations.php");
    exit();
}

$station = $result->fetch_assoc();
$station_check->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $station_name = sanitize($_POST['station_name']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $province = sanitize($_POST['province']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $operating_hours = sanitize($_POST['operating_hours']);
    $status = sanitize($_POST['status']);
    
    $errors = [];
    
    if (empty($station_name) || empty($address) || empty($city) || empty($province)) {
        $errors[] = "Please fill in all required fields.";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE charging_stations SET station_name = ?, address = ?, city = ?, province = ?, latitude = ?, longitude = ?, operating_hours = ?, status = ? WHERE station_id = ? AND user_id = ?");
        $stmt->bind_param("sssddsssii", $station_name, $address, $city, $province, $latitude, $longitude, $operating_hours, $status, $station_id, $user_id);
        
        if ($stmt->execute()) {
            setFlashMessage("Station updated successfully!", "success");
            header("Location: manage_stations.php");
            exit();
        } else {
            $errors[] = "Failed to update station. Please try again.";
        }
        $stmt->close();
    }
    
    foreach ($errors as $error) {
        setFlashMessage($error, "danger");
    }
} else {
    $_POST = $station;
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Edit Charging Station</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <h5 class="mb-3">Basic Information</h5>
                        
                        <div class="mb-3">
                            <label for="station_name" class="form-label">Station Name *</label>
                            <input type="text" class="form-control" id="station_name" name="station_name" required
                                   value="<?php echo htmlspecialchars($_POST['station_name']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Full Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required><?php echo htmlspecialchars($_POST['address']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" required
                                       value="<?php echo htmlspecialchars($_POST['city']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="province" class="form-label">Province *</label>
                                <select class="form-control" id="province" name="province" required>
                                    <option value="">Select Province</option>
                                    <option value="Koshi" <?php echo ($_POST['province'] === 'Koshi') ? 'selected' : ''; ?>>Koshi</option>
                                    <option value="Madhesh" <?php echo ($_POST['province'] === 'Madhesh') ? 'selected' : ''; ?>>Madhesh</option>
                                    <option value="Bagmati" <?php echo ($_POST['province'] === 'Bagmati') ? 'selected' : ''; ?>>Bagmati</option>
                                    <option value="Gandaki" <?php echo ($_POST['province'] === 'Gandaki') ? 'selected' : ''; ?>>Gandaki</option>
                                    <option value="Lumbini" <?php echo ($_POST['province'] === 'Lumbini') ? 'selected' : ''; ?>>Lumbini</option>
                                    <option value="Karnali" <?php echo ($_POST['province'] === 'Karnali') ? 'selected' : ''; ?>>Karnali</option>
                                    <option value="Sudurpashchim" <?php echo ($_POST['province'] === 'Sudurpashchim') ? 'selected' : ''; ?>>Sudurpashchim</option>
                                </select>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Location Coordinates (Optional)</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" class="form-control" id="latitude" name="latitude" step="0.000001"
                                       value="<?php echo $_POST['latitude']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" class="form-control" id="longitude" name="longitude" step="0.000001"
                                       value="<?php echo $_POST['longitude']; ?>">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Operation Details</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="operating_hours" class="form-label">Operating Hours *</label>
                                <input type="text" class="form-control" id="operating_hours" name="operating_hours" required
                                       value="<?php echo htmlspecialchars($_POST['operating_hours']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="online" <?php echo ($_POST['status'] === 'online') ? 'selected' : ''; ?>>Online</option>
                                    <option value="offline" <?php echo ($_POST['status'] === 'offline') ? 'selected' : ''; ?>>Offline</option>
                                    <option value="under_maintenance" <?php echo ($_POST['status'] === 'under_maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                                </select>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between">
                            <a href="manage_stations.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-custom btn-lg">
                                <i class="fas fa-save"></i> Update Station
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
