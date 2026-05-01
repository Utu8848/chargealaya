<?php
$page_title = "Manage Chargers";
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

// Handle add charger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_charger'])) {
    $charger_type = sanitize($_POST['charger_type']);
    $max_power_kw = floatval($_POST['max_power_kw']);
    $connector_type = sanitize($_POST['connector_type']);
    $status = sanitize($_POST['status']);
    
    $insert_stmt = $conn->prepare("INSERT INTO chargers (station_id, charger_type, max_power_kw, connector_type, status) VALUES (?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("isdss", $station_id, $charger_type, $max_power_kw, $connector_type, $status);
    
    if ($insert_stmt->execute()) {
        setFlashMessage("Charger added successfully!", "success");
    }
    $insert_stmt->close();
    header("Location: manage_chargers.php?station_id=$station_id");
    exit();
}

// Handle delete charger
if (isset($_GET['delete_charger'])) {
    $charger_id = intval($_GET['delete_charger']);
    
    $delete_stmt = $conn->prepare("DELETE FROM chargers WHERE charger_id = ? AND station_id = ?");
    $delete_stmt->bind_param("ii", $charger_id, $station_id);
    
    if ($delete_stmt->execute()) {
        setFlashMessage("Charger deleted successfully!", "success");
    }
    $delete_stmt->close();
    header("Location: manage_chargers.php?station_id=$station_id");
    exit();
}

// Handle status change
if (isset($_GET['change_charger_status'])) {
    $charger_id = intval($_GET['change_charger_status']);
    $new_status = sanitize($_GET['new_status']);
    
    $update_stmt = $conn->prepare("UPDATE chargers SET status = ? WHERE charger_id = ? AND station_id = ?");
    $update_stmt->bind_param("sii", $new_status, $charger_id, $station_id);
    
    if ($update_stmt->execute()) {
        setFlashMessage("Charger status updated!", "success");
    }
    $update_stmt->close();
    header("Location: manage_chargers.php?station_id=$station_id");
    exit();
}

// Get chargers
$chargers_query = "SELECT * FROM chargers WHERE station_id = $station_id ORDER BY charger_id";
$chargers = $conn->query($chargers_query);
?>

<div class="container my-5">
    <div class="mb-4">
        <a href="manage_stations.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Stations
        </a>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-plug text-success"></i> Manage Chargers</h2>
            <p class="text-muted">Station: <strong><?php echo htmlspecialchars($station['station_name']); ?></strong></p>
        </div>
    </div>
    
    <!-- Add Charger Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-plus"></i> Add New Charger</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="charger_type" class="form-label">Charger Type *</label>
                        <select class="form-control" id="charger_type" name="charger_type" required>
                            <option value="fast">Fast Charger</option>
                            <option value="normal">Normal Charger</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="max_power_kw" class="form-label">Max Power (kW) *</label>
                        <input type="number" class="form-control" id="max_power_kw" name="max_power_kw" 
                               step="0.01" min="1" required placeholder="e.g., 50">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="connector_type" class="form-label">Connector Type *</label>
                        <select class="form-control" id="connector_type" name="connector_type" required>
                            <option value="CCS2">CCS2</option>
                            <option value="CHAdeMO">CHAdeMO</option>
                            <option value="Type2">Type 2</option>
                            <option value="GB/T">GB/T</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="available">Available</option>
                            <option value="in_use">In Use</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_charger" class="btn btn-custom">
                    <i class="fas fa-plus"></i> Add Charger
                </button>
            </form>
        </div>
    </div>
    
    <!-- Chargers List -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> Current Chargers</h5>
        </div>
        <div class="card-body">
            <?php if ($chargers->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Charger ID</th>
                                <th>Type</th>
                                <th>Max Power</th>
                                <th>Connector</th>
                                <th>Status</th>
                                <th style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($charger = $chargers->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $charger['charger_id']; ?></td>
                                    <td><?php echo ucfirst($charger['charger_type']); ?></td>
                                    <td><?php echo $charger['max_power_kw']; ?> kW</td>
                                    <td><?php echo htmlspecialchars($charger['connector_type']); ?></td>
                                    <td>
                                        <?php
                                        $status_badge = '';
                                        switch($charger['status']) {
                                            case 'available': $status_badge = 'bg-success'; break;
                                            case 'in_use': $status_badge = 'bg-warning'; break;
                                            case 'maintenance': $status_badge = 'bg-danger'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_badge; ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $charger['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-grid gap-2" style="min-width: 150px;">
                                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                Change Status
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="?station_id=<?php echo $station_id; ?>&change_charger_status=<?php echo $charger['charger_id']; ?>&new_status=available">
                                                    <i class="fas fa-check-circle text-success"></i> Available
                                                </a></li>
                                                <li><a class="dropdown-item" href="?station_id=<?php echo $station_id; ?>&change_charger_status=<?php echo $charger['charger_id']; ?>&new_status=in_use">
                                                    <i class="fas fa-charging-station text-primary"></i> In Use
                                                </a></li>
                                                <li><a class="dropdown-item" href="?station_id=<?php echo $station_id; ?>&change_charger_status=<?php echo $charger['charger_id']; ?>&new_status=maintenance">
                                                    <i class="fas fa-tools text-warning"></i> Maintenance
                                                </a></li>
                                            </ul>
                                            <a href="?station_id=<?php echo $station_id; ?>&delete_charger=<?php echo $charger['charger_id']; ?>" 
                                               class="btn btn-sm btn-danger-custom"
                                               onclick="return confirm('Delete this charger?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4">No chargers added yet. Add your first charger above.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
