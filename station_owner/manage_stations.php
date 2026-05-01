<?php
$page_title = "Manage Stations";
include '../partials/header.php';
requireStationOwner();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Handle status change
if (isset($_GET['change_status']) && isset($_GET['status'])) {
    $station_id = intval($_GET['change_status']);
    $new_status = sanitize($_GET['status']);
    
    $update_stmt = $conn->prepare("UPDATE charging_stations SET status = ? WHERE station_id = ? AND user_id = ?");
    $update_stmt->bind_param("sii", $new_status, $station_id, $user_id);
    
    if ($update_stmt->execute()) {
        setFlashMessage("Station status updated successfully!", "success");
    }
    $update_stmt->close();
    header("Location: manage_stations.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $station_id = intval($_GET['delete']);
    
    $delete_stmt = $conn->prepare("DELETE FROM charging_stations WHERE station_id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $station_id, $user_id);
    
    if ($delete_stmt->execute()) {
        setFlashMessage("Station deleted successfully!", "success");
    }
    $delete_stmt->close();
    header("Location: manage_stations.php");
    exit();
}

// Get stations
$query = "SELECT cs.*, COUNT(DISTINCT c.charger_id) as charger_count,
          SUM(CASE WHEN c.status = 'available' THEN 1 ELSE 0 END) as available_chargers,
          COUNT(DISTINCT sess.session_id) as session_count
          FROM charging_stations cs
          LEFT JOIN chargers c ON cs.station_id = c.station_id
          LEFT JOIN charging_sessions sess ON c.charger_id = sess.charger_id
          WHERE cs.user_id = $user_id
          GROUP BY cs.station_id
          ORDER BY cs.station_name";
$stations = $conn->query($query);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-charging-station text-success"></i> Manage Stations</h2>
            <p class="text-muted">View and manage all your charging stations</p>
        </div>
        <a href="add_station.php" class="btn btn-custom">
            <i class="fas fa-plus"></i> Add New Station
        </a>
    </div>
    
    <?php if ($stations->num_rows > 0): ?>
        <div class="row">
            <?php while ($station = $stations->fetch_assoc()): ?>
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4><?php echo htmlspecialchars($station['station_name']); ?></h4>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($station['address'] . ', ' . $station['city'] . ', ' . $station['province']); ?>
                                    </p>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <small class="text-muted">Total Chargers</small>
                                            <h5><?php echo $station['charger_count']; ?></h5>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Available</small>
                                            <h5 class="text-success"><?php echo $station['available_chargers']; ?></h5>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Total Sessions</small>
                                            <h5 class="text-primary"><?php echo $station['session_count']; ?></h5>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <strong>Operating Hours:</strong> <?php echo htmlspecialchars($station['operating_hours']); ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 text-end">
                                    <?php
                                    $status_class = '';
                                    switch($station['status']) {
                                        case 'online': $status_class = 'bg-success'; break;
                                        case 'offline': $status_class = 'bg-secondary'; break;
                                        case 'under_maintenance': $status_class = 'bg-warning'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?> mb-3">
                                        <?php echo ucwords(str_replace('_', ' ', $station['status'])); ?>
                                    </span>
                                    
                                    <div class="btn-group-vertical w-100" role="group">
                                        <a href="edit_station.php?id=<?php echo $station['station_id']; ?>" class="btn btn-sm btn-custom">
                                            <i class="fas fa-edit"></i> Edit Details
                                        </a>
                                        <a href="manage_chargers.php?station_id=<?php echo $station['station_id']; ?>" class="btn btn-sm btn-custom">
                                            <i class="fas fa-plug"></i> Manage Chargers
                                        </a>
                                        <a href="set_tariff.php?station_id=<?php echo $station['station_id']; ?>" class="btn btn-sm btn-secondary-custom">
                                            <i class="fas fa-dollar-sign"></i> Set Pricing
                                        </a>
                                        
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i> Status
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="?change_status=<?php echo $station['station_id']; ?>&status=online">
                                                    <i class="fas fa-check-circle text-success"></i> Set Online
                                                </a></li>
                                                <li><a class="dropdown-item" href="?change_status=<?php echo $station['station_id']; ?>&status=offline">
                                                    <i class="fas fa-times-circle text-secondary"></i> Set Offline
                                                </a></li>
                                                <li><a class="dropdown-item" href="?change_status=<?php echo $station['station_id']; ?>&status=under_maintenance">
                                                    <i class="fas fa-wrench text-warning"></i> Under Maintenance
                                                </a></li>
                                            </ul>
                                        </div>
                                        
                                        <a href="?delete=<?php echo $station['station_id']; ?>" class="btn btn-sm btn-danger-custom"
                                           onclick="return confirm('Are you sure? This will delete all chargers and sessions!')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-charging-station fa-5x text-muted mb-3"></i>
                <h4>No Stations Yet</h4>
                <p class="text-muted">Add your first charging station to get started.</p>
                <a href="add_station.php" class="btn btn-custom btn-lg">
                    <i class="fas fa-plus"></i> Add Your First Station
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
