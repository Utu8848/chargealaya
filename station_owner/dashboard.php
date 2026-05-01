<?php
$page_title = "Station Owner Dashboard";
include '../partials/header.php';
requireStationOwner();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];


// Get statistics
$my_stations = $conn->query("SELECT COUNT(*) as count FROM charging_stations WHERE user_id = $user_id")->fetch_assoc()['count'];
$my_chargers = $conn->query("SELECT COUNT(*) as count FROM chargers c 
                             JOIN charging_stations cs ON c.station_id = cs.station_id 
                             WHERE cs.user_id = $user_id")->fetch_assoc()['count'];
$total_sessions = $conn->query("SELECT COUNT(*) as count FROM charging_sessions cs
                               JOIN chargers c ON cs.charger_id = c.charger_id
                               JOIN charging_stations cst ON c.station_id = cst.station_id
                               WHERE cst.user_id = $user_id")->fetch_assoc()['count'];
$revenue_result = $conn->query("SELECT SUM(cs.cost) as total FROM charging_sessions cs
                               JOIN chargers c ON cs.charger_id = c.charger_id
                               JOIN charging_stations cst ON c.station_id = cst.station_id
                               WHERE cst.user_id = $user_id AND cs.session_status = 'completed'")->fetch_assoc();
$total_revenue = $revenue_result['total'] ?? 0;

// Get my stations
$stations_query = "SELECT cs.*, COUNT(DISTINCT c.charger_id) as charger_count,
                   SUM(CASE WHEN c.status = 'available' THEN 1 ELSE 0 END) as available_chargers
                   FROM charging_stations cs
                   LEFT JOIN chargers c ON cs.station_id = c.station_id
                   WHERE cs.user_id = $user_id
                   GROUP BY cs.station_id";
$stations = $conn->query($stations_query);

// Get recent sessions
$recent_sessions = $conn->query("SELECT cs.*, u.first_name, u.last_name, cst.station_name, v.brand, v.model
                                FROM charging_sessions cs
                                JOIN users u ON cs.user_id = u.user_id
                                JOIN chargers c ON cs.charger_id = c.charger_id
                                JOIN charging_stations cst ON c.station_id = cst.station_id
                                JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
                                WHERE cst.user_id = $user_id
                                ORDER BY cs.start_time DESC LIMIT 10");
?>

<div class="container my-5">
    <h2><i class="fas fa-store text-success"></i> Station Owner Dashboard</h2>
    <p class="text-muted">Manage your charging stations</p>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3" style="animation-delay: 0.1s;">
            <div class="stat-card glass-card">
                <i class="fas fa-charging-station text-success icon-pulse"></i>
                <h3><?php echo $my_stations; ?></h3>
                <p>My Stations</p>
            </div>
        </div>
        <div class="col-md-3" style="animation-delay: 0.2s;">
            <div class="stat-card glass-card">
                <i class="fas fa-plug text-primary icon-pulse"></i>
                <h3><?php echo $my_chargers; ?></h3>
                <p>Total Chargers</p>
            </div>
        </div>
        <div class="col-md-3" style="animation-delay: 0.3s;">
            <div class="stat-card glass-card">
                <i class="fas fa-bolt text-warning icon-pulse"></i>
                <h3><?php echo $total_sessions; ?></h3>
                <p>Total Sessions</p>
            </div>
        </div>
        <div class="col-md-3" style="animation-delay: 0.4s;">
            <div class="stat-card glass-card">
                <i class="fas fa-money-bill-wave text-info icon-pulse"></i>
                <h3><?php echo formatCurrency($total_revenue); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bolt"></i> Quick Actions
                </div>
                <div class="card-body">
                    <a href="add_station.php" class="btn btn-custom me-2 mb-2">
                        <i class="fas fa-plus"></i> Add New Station
                    </a>
                    <a href="manage_stations.php" class="btn btn-custom me-2 mb-2">
                        <i class="fas fa-cog"></i> Manage Stations
                    </a>
                    <a href="manage_reservations.php" class="btn btn-custom me-2 mb-2">
                        <i class="fas fa-calendar-alt"></i> Manage Reservations
                    </a>
                    <a href="maintenance.php" class="btn btn-warning-custom me-2 mb-2">
                        <i class="fas fa-tools"></i> Maintenance
                    </a>
                    <a href="session_history.php" class="btn btn-custom me-2 mb-2">
                        <i class="fas fa-history"></i> Session History
                    </a>
                    <a href="reports.php" class="btn btn-custom mb-2">
                        <i class="fas fa-chart-line"></i> View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- My Stations -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-charging-station"></i> My Charging Stations
                </div>
                <div class="card-body">
                    <?php if ($stations->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Station Name</th>
                                        <th>Location</th>
                                        <th>Chargers</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($station = $stations->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($station['station_name']); ?></td>
                                            <td><?php echo htmlspecialchars($station['city'] . ', ' . $station['province']); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $station['available_chargers']; ?> / <?php echo $station['charger_count']; ?> Available</span>
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
                                                <span class="badge <?php echo $status_class; ?>"><?php echo ucwords(str_replace('_', ' ', $station['status'])); ?></span>
                                            </td>
                                            <td>
                                                <a href="edit_station.php?id=<?php echo $station['station_id']; ?>" class="btn btn-sm btn-custom">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="manage_chargers.php?station_id=<?php echo $station['station_id']; ?>" class="btn btn-sm btn-secondary-custom">
                                                    <i class="fas fa-plug"></i> Chargers
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You haven't added any charging stations yet.</p>
                        <a href="add_station.php" class="btn btn-custom">
                            <i class="fas fa-plus"></i> Add Your First Station
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Sessions -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Recent Charging Sessions
                </div>
                <div class="card-body">
                    <?php if ($recent_sessions->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Station</th>
                                        <th>Vehicle</th>
                                        <th>Date</th>
                                        <th>Energy</th>
                                        <th>Revenue</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($session = $recent_sessions->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($session['station_name']); ?></td>
                                            <td><?php echo htmlspecialchars($session['brand'] . ' ' . $session['model']); ?></td>
                                            <td><?php echo formatDateTime($session['start_time']); ?></td>
                                            <td><?php echo number_format($session['energy_used_kwh'], 2); ?> kWh</td>
                                            <td><?php echo formatCurrency($session['cost']); ?></td>
                                            <td><span class="badge bg-success"><?php echo ucwords($session['session_status']); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No charging sessions yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
