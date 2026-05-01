<?php
$page_title = "Manage Stations";
include '../partials/header.php';
requireAdmin();

$db = new Database();
$conn = $db->connect();

// Handle status change
if (isset($_GET['change_status'])) {
    $station_id = intval($_GET['change_status']);
    $new_status = sanitize($_GET['new_status']);
    
    $update_stmt = $conn->prepare("UPDATE charging_stations SET status = ? WHERE station_id = ?");
    $update_stmt->bind_param("si", $new_status, $station_id);
    
    if ($update_stmt->execute()) {
        setFlashMessage("Station status updated successfully!", "success");
    }
    $update_stmt->close();
    header("Location: stations.php");
    exit();
}

// Handle delete
if (isset($_GET['delete_station'])) {
    $station_id = intval($_GET['delete_station']);
    
    $delete_stmt = $conn->prepare("DELETE FROM charging_stations WHERE station_id = ?");
    $delete_stmt->bind_param("i", $station_id);
    
    if ($delete_stmt->execute()) {
        setFlashMessage("Station deleted successfully!", "success");
    }
    $delete_stmt->close();
    header("Location: stations.php");
    exit();
}

// Get filter
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$city_filter = isset($_GET['city']) ? sanitize($_GET['city']) : '';

// Build query
$query = "SELECT cs.*, u.first_name, u.last_name, u.email,
          COUNT(DISTINCT c.charger_id) as charger_count,
          SUM(CASE WHEN c.status = 'available' THEN 1 ELSE 0 END) as available_chargers
          FROM charging_stations cs
          JOIN users u ON cs.user_id = u.user_id
          LEFT JOIN chargers c ON cs.station_id = c.station_id
          WHERE 1=1";

if ($status_filter) {
    $query .= " AND cs.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($city_filter) {
    $query .= " AND cs.city = '" . $conn->real_escape_string($city_filter) . "'";
}

$query .= " GROUP BY cs.station_id ORDER BY cs.station_name";
$stations = $conn->query($query);

// Get statistics
$total_stations = $conn->query("SELECT COUNT(*) as count FROM charging_stations")->fetch_assoc()['count'];
$online_stations = $conn->query("SELECT COUNT(*) as count FROM charging_stations WHERE status = 'online'")->fetch_assoc()['count'];
$offline_stations = $conn->query("SELECT COUNT(*) as count FROM charging_stations WHERE status = 'offline'")->fetch_assoc()['count'];

// Get all cities for filter
$cities_query = "SELECT DISTINCT city FROM charging_stations ORDER BY city";
$cities = $conn->query($cities_query);
?>

<div class="container my-5">
    <h2><i class="fas fa-charging-station text-success"></i> Manage Charging Stations</h2>
    <p class="text-muted">View and manage all charging stations in the system</p>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h4><?php echo $total_stations; ?></h4>
                    <p class="mb-0"><i class="fas fa-charging-station"></i> Total Stations</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h4><?php echo $online_stations; ?></h4>
                    <p class="mb-0"><i class="fas fa-check-circle"></i> Online</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h4><?php echo $offline_stations; ?></h4>
                    <p class="mb-0"><i class="fas fa-times-circle"></i> Offline</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="online" <?php echo $status_filter === 'online' ? 'selected' : ''; ?>>Online</option>
                        <option value="offline" <?php echo $status_filter === 'offline' ? 'selected' : ''; ?>>Offline</option>
                        <option value="under_maintenance" <?php echo $status_filter === 'under_maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="city" class="form-label">Filter by City</label>
                    <select class="form-control" id="city" name="city">
                        <option value="">All Cities</option>
                        <?php while ($city = $cities->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($city['city']); ?>" 
                                    <?php echo $city_filter === $city['city'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city['city']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-custom w-100">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Stations Table -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> All Stations</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Station Name</th>
                            <th>Owner</th>
                            <th>Location</th>
                            <th>Chargers</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($station = $stations->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $station['station_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($station['station_name']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($station['first_name'] . ' ' . $station['last_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($station['email']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($station['city'] . ', ' . $station['province']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($station['address']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo $station['available_chargers']; ?> / <?php echo $station['charger_count']; ?> Available
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($station['status']) {
                                        case 'online': $status_class = 'bg-success'; break;
                                        case 'offline': $status_class = 'bg-secondary'; break;
                                        case 'under_maintenance': $status_class = 'bg-warning'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $station['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-grid gap-2" style="min-width: 150px;">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-exchange-alt"></i> Change Status
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="?change_status=<?php echo $station['station_id']; ?>&new_status=online"><i class="fas fa-check-circle text-success"></i> Set Online</a></li>
                                            <li><a class="dropdown-item" href="?change_status=<?php echo $station['station_id']; ?>&new_status=offline"><i class="fas fa-times-circle text-secondary"></i> Set Offline</a></li>
                                            <li><a class="dropdown-item" href="?change_status=<?php echo $station['station_id']; ?>&new_status=under_maintenance"><i class="fas fa-tools text-warning"></i> Under Maintenance</a></li>
                                        </ul>
                                        <a href="?delete_station=<?php echo $station['station_id']; ?>" 
                                           class="btn btn-sm btn-danger-custom"
                                           onclick="return confirm('Delete this station? This will also delete all chargers and sessions!')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
