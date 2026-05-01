<?php
$page_title = "Add Charging Station";
include '../partials/header.php';
requireStationOwner();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

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
        $stmt = $conn->prepare("INSERT INTO charging_stations (station_name, user_id, address, city, province, latitude, longitude, operating_hours, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssddss", $station_name, $user_id, $address, $city, $province, $latitude, $longitude, $operating_hours, $status);

        if ($stmt->execute()) {
            $station_id = $stmt->insert_id;
            setFlashMessage("Station added successfully! Now add chargers to your station.", "success");
            header("Location: manage_chargers.php?station_id=$station_id");
            exit();
        } else {
            $errors[] = "Failed to add station. Please try again.";
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
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Add New Charging Station</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <h5 class="mb-3">Basic Information</h5>

                        <div class="mb-3">
                            <label for="station_name" class="form-label">Station Name *</label>
                            <input type="text" class="form-control" id="station_name" name="station_name" required
                                placeholder="e.g., TU Campus Charging Hub"
                                value="<?php echo isset($_POST['station_name']) ? htmlspecialchars($_POST['station_name']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Full Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required
                                placeholder="Street address, landmarks"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" required
                                    placeholder="e.g., Kathmandu"
                                    value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="province" class="form-label">Province *</label>
                                <select class="form-control" id="province" name="province" required>
                                    <option value="">Select Province</option>
                                    <option value="Koshi">Koshi</option>
                                    <option value="Madhesh">Madhesh</option>
                                    <option value="Bagmati">Bagmati</option>
                                    <option value="Gandaki">Gandaki</option>
                                    <option value="Lumbini">Lumbini</option>
                                    <option value="Karnali">Karnali</option>
                                    <option value="Sudurpashchim">Sudurpashchim</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Location Coordinates (Optional)</h5>
                        <p class="text-muted small">Add GPS coordinates for map integration</p>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" class="form-control" id="latitude" name="latitude" step="0.000001"
                                    placeholder="27.7172"
                                    value="<?php echo isset($_POST['latitude']) ? $_POST['latitude'] : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" class="form-control" id="longitude" name="longitude"
                                    step="0.000001" placeholder="85.3240"
                                    value="<?php echo isset($_POST['longitude']) ? $_POST['longitude'] : ''; ?>">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Operation Details</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="operating_hours" class="form-label">Operating Hours *</label>
                                <input type="text" class="form-control" id="operating_hours" name="operating_hours"
                                    required placeholder="e.g., 24/7 or 06:00-22:00"
                                    value="<?php echo isset($_POST['operating_hours']) ? htmlspecialchars($_POST['operating_hours']) : '24/7'; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Initial Status *</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="online">Online</option>
                                    <option value="offline">Offline</option>
                                    <option value="under_maintenance">Under Maintenance</option>
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Next Step:</strong> After adding the station,
                            you'll be able to add chargers and set pricing.
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-custom btn-lg">
                                <i class="fas fa-save"></i> Add Station
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