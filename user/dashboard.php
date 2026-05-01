<?php
$page_title = "User Dashboard";
include '../partials/header.php';
requireLogin();

if (!hasRole('ev_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];


// Get user statistics
$total_sessions_query = "SELECT COUNT(*) as count FROM charging_sessions WHERE user_id = $user_id";
$total_sessions = $conn->query($total_sessions_query)->fetch_assoc()['count'];

$total_spent_query = "SELECT SUM(amount) as total FROM payments WHERE user_id = $user_id AND payment_status = 'paid'";
$total_spent_result = $conn->query($total_spent_query)->fetch_assoc();
$total_spent = $total_spent_result['total'] ?? 0;

$total_energy_query = "SELECT SUM(energy_used_kwh) as total FROM charging_sessions WHERE user_id = $user_id";
$total_energy_result = $conn->query($total_energy_query)->fetch_assoc();
$total_energy = $total_energy_result['total'] ?? 0;

$vehicles_count_query = "SELECT COUNT(*) as count FROM vehicles WHERE user_id = $user_id";
$vehicles_count = $conn->query($vehicles_count_query)->fetch_assoc()['count'];

// Get recent sessions
$recent_sessions_query = "SELECT cs.*, cst.station_name, v.brand, v.model, c.charger_type, p.payment_status
                          FROM charging_sessions cs
                          LEFT JOIN chargers c ON cs.charger_id = c.charger_id
                          LEFT JOIN charging_stations cst ON c.station_id = cst.station_id
                          LEFT JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
                          LEFT JOIN payments p ON cs.session_id = p.session_id
                          WHERE cs.user_id = $user_id
                          ORDER BY cs.start_time DESC
                          LIMIT 5";
$recent_sessions = $conn->query($recent_sessions_query);

// Get upcoming reservations
$reservations_query = "SELECT r.*, cs.station_name, c.charger_type
                       FROM reservations r
                       JOIN chargers c ON r.charger_id = c.charger_id
                       JOIN charging_stations cs ON c.station_id = cs.station_id
                       WHERE r.user_id = $user_id AND r.status = 'confirmed' AND r.start_time > NOW()
                       ORDER BY r.start_time ASC
                       LIMIT 5";
$reservations = $conn->query($reservations_query);
?>

<div class="container my-5">
    <h2><i class="fas fa-tachometer-alt text-success"></i> Dashboard</h2>
    <p class="text-muted">Welcome back, <?php echo $_SESSION['name']; ?>!</p>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3" style="animation-delay: 0.1s;">
            <div class="stat-card glass-card">
                <i class="fas fa-charging-station text-primary icon-pulse"></i>
                <h3><?php echo $total_sessions; ?></h3>
                <p>Charging Sessions</p>
            </div>
        </div>
        <div class="col-md-3" style="animation-delay: 0.2s;">
            <div class="stat-card glass-card">
                <i class="fas fa-wallet text-success icon-pulse"></i>
                <h3><?php echo formatCurrency($total_spent); ?></h3>
                <p>Total Spent</p>
            </div>
        </div>
        <div class="col-md-3" style="animation-delay: 0.3s;">
            <div class="stat-card glass-card">
                <i class="fas fa-bolt text-warning icon-pulse"></i>
                <h3><?php echo number_format($total_energy, 2); ?> kWh</h3>
                <p>Energy Used</p>
            </div>
        </div>
        <div class="col-md-3" style="animation-delay: 0.4s;">
            <div class="stat-card glass-card">
                <i class="fas fa-car text-info icon-pulse"></i>
                <h3><?php echo $vehicles_count; ?></h3>
                <p>My Vehicles</p>
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
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="/chargealaya/public/stations.php" class="btn btn-custom w-100 quick-action-btn">
                                <i class="fas fa-search fa-2x mb-2"></i><br>Find Stations
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="vehicles.php" class="btn btn-custom w-100 quick-action-btn">
                                <i class="fas fa-car fa-2x mb-2"></i><br>My Vehicles
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="reservations.php" class="btn btn-custom w-100 quick-action-btn">
                                <i class="fas fa-calendar fa-2x mb-2"></i><br>My Reservations
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="charging_history.php" class="btn btn-custom w-100 quick-action-btn">
                                <i class="fas fa-history fa-2x mb-2"></i><br>Charging History
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="report_issue.php" class="btn btn-warning-custom w-100 quick-action-btn">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>Report Issue
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="my_reports.php" class="btn btn-secondary-custom w-100 quick-action-btn">
                                <i class="fas fa-clipboard-list fa-2x mb-2"></i><br>My Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Two Column Layout -->
    <div class="row">
        <!-- Recent Sessions -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Recent Charging Sessions
                </div>
                <div class="card-body">
                    <?php if ($recent_sessions->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Station</th>
                                        <th>Date</th>
                                        <th>Energy</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($session = $recent_sessions->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($session['station_name']); ?></td>
                                            <td><?php echo formatDate($session['start_time']); ?></td>
                                            <td><?php echo number_format($session['energy_used_kwh'], 2); ?> kWh</td>
                                            <td><?php echo formatCurrency($session['cost']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="charging_history.php" class="btn btn-sm btn-custom">View All</a>
                    <?php else: ?>
                        <p class="text-muted">No charging sessions yet.</p>
                        <a href="/chargealaya/public/stations.php" class="btn btn-custom">Find Stations</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Reservations -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-check"></i> Upcoming Reservations
                </div>
                <div class="card-body">
                    <?php if ($reservations->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Station</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($reservation = $reservations->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reservation['station_name']); ?></td>
                                            <td><?php echo formatDateTime($reservation['start_time']); ?></td>
                                            <td><span class="badge bg-success">Confirmed</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="reservations.php" class="btn btn-sm btn-custom">View All</a>
                    <?php else: ?>
                        <p class="text-muted">No upcoming reservations.</p>
                        <a href="/chargealaya/public/stations.php" class="btn btn-custom">Make Reservation</a>
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
