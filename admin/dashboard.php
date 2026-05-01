<?php
$page_title = "Admin Dashboard";
include '../partials/header.php';
requireAdmin();

$db = new Database();
$conn = $db->connect();

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'ev_owner'")->fetch_assoc()['count'];
$total_owners = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'station_owner'")->fetch_assoc()['count'];
$total_stations = $conn->query("SELECT COUNT(*) as count FROM charging_stations")->fetch_assoc()['count'];
$total_chargers = $conn->query("SELECT COUNT(*) as count FROM chargers")->fetch_assoc()['count'];
$total_sessions = $conn->query("SELECT COUNT(*) as count FROM charging_sessions")->fetch_assoc()['count'];
$total_revenue_result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'paid'")->fetch_assoc();
$total_revenue = $total_revenue_result['total'] ?? 0;

// Get recent activities
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_sessions = $conn->query("SELECT cs.*, u.first_name, u.last_name, cst.station_name 
                                FROM charging_sessions cs
                                JOIN users u ON cs.user_id = u.user_id
                                JOIN chargers c ON cs.charger_id = c.charger_id
                                JOIN charging_stations cst ON c.station_id = cst.station_id
                                ORDER BY cs.start_time DESC LIMIT 5");
?>

<div class="container my-5">
    <h2><i class="fas fa-crown text-warning"></i> Admin Dashboard</h2>
    <p class="text-muted">System Overview and Management</p>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="stat-card">
                <i class="fas fa-users text-primary"></i>
                <h3><?php echo $total_users; ?></h3>
                <p>Users</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <i class="fas fa-user-tie text-info"></i>
                <h3><?php echo $total_owners; ?></h3>
                <p>Station Owners</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <i class="fas fa-charging-station text-success"></i>
                <h3><?php echo $total_stations; ?></h3>
                <p>Stations</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <i class="fas fa-plug text-warning"></i>
                <h3><?php echo $total_chargers; ?></h3>
                <p>Chargers</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <i class="fas fa-bolt text-danger"></i>
                <h3><?php echo $total_sessions; ?></h3>
                <p>Sessions</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <i class="fas fa-money-bill-wave text-success"></i>
                <h3><?php echo formatCurrency($total_revenue); ?></h3>
                <p>Revenue</p>
            </div>
        </div>
    </div>
    
    <!-- Management Links -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-cogs"></i> Management
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="users.php" class="btn btn-custom w-100">
                                <i class="fas fa-users"></i><br>Manage Users
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="stations.php" class="btn btn-custom w-100">
                                <i class="fas fa-charging-station"></i><br>Manage Stations
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="sessions.php" class="btn btn-custom w-100">
                                <i class="fas fa-history"></i><br>All Sessions
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="reports.php" class="btn btn-custom w-100">
                                <i class="fas fa-chart-bar"></i><br>Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i> Recent Users
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge bg-info"><?php echo $user['role']; ?></span></td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-charging-station"></i> Recent Sessions
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Station</th>
                                <th>Energy</th>
                                <th>Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($session = $recent_sessions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($session['station_name']); ?></td>
                                    <td><?php echo number_format($session['energy_used_kwh'], 2); ?> kWh</td>
                                    <td><?php echo formatCurrency($session['cost']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
