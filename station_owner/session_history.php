<?php
$page_title = "Session History";
include '../partials/header.php';
requireStationOwner();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];


// Get all sessions for owner's stations
$query = "SELECT cs_session.*, 
          cst.station_name, cst.city,
          c.charger_type, c.max_power_kw,
          v.brand, v.model,
          u.first_name, u.last_name, u.email, u.phone,
          p.payment_status, p.payment_method
          FROM charging_sessions cs_session
          JOIN chargers c ON cs_session.charger_id = c.charger_id
          JOIN charging_stations cst ON c.station_id = cst.station_id
          JOIN vehicles v ON cs_session.vehicle_id = v.vehicle_id
          JOIN users u ON cs_session.user_id = u.user_id
          LEFT JOIN payments p ON cs_session.session_id = p.session_id
          WHERE cst.user_id = $user_id
          ORDER BY cs_session.start_time DESC";
$sessions = $conn->query($query);

// Get statistics
$total_sessions = $sessions->num_rows;
$total_revenue = 0;
$total_energy = 0;

if ($total_sessions > 0) {
    $sessions->data_seek(0);
    while ($session = $sessions->fetch_assoc()) {
        $total_revenue += $session['cost'];
        $total_energy += $session['energy_used_kwh'];
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-history text-success"></i> Session History</h2>
            <p class="text-muted">View all charging sessions at your stations</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h4><?php echo $total_sessions; ?></h4>
                    <p class="mb-0"><i class="fas fa-charging-station"></i> Total Sessions</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h4><?php echo formatCurrency($total_revenue); ?></h4>
                    <p class="mb-0"><i class="fas fa-money-bill-wave"></i> Total Revenue</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h4><?php echo number_format($total_energy, 2); ?> kWh</h4>
                    <p class="mb-0"><i class="fas fa-bolt"></i> Total Energy Dispensed</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($total_sessions > 0): ?>
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> All Sessions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom" id="sessionsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date & Time</th>
                                <th>Station</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Charger</th>
                                <th>Duration</th>
                                <th>Energy (kWh)</th>
                                <th>Revenue</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sessions->data_seek(0);
                            while ($session = $sessions->fetch_assoc()):
                                $duration = 0;
                                if ($session['end_time']) {
                                    $duration = (strtotime($session['end_time']) - strtotime($session['start_time'])) / 3600;
                                }
                            ?>
                                <tr>
                                    <td>#<?php echo $session['session_id']; ?></td>
                                    <td><?php echo formatDateTime($session['start_time']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($session['station_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($session['city']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($session['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($session['brand'] . ' ' . $session['model']); ?></td>
                                    <td>
                                        <?php echo ucfirst($session['charger_type']); ?><br>
                                        <small><?php echo $session['max_power_kw']; ?> kW</small>
                                    </td>
                                    <td><?php echo number_format($duration, 2); ?> hrs</td>
                                    <td><?php echo number_format($session['energy_used_kwh'], 2); ?></td>
                                    <td><strong><?php echo formatCurrency($session['cost']); ?></strong></td>
                                    <td>
                                        <?php
                                        if ($session['payment_status'] === 'paid') {
                                            echo '<span class="badge bg-success">Paid</span><br>';
                                            echo '<small>' . ucfirst($session['payment_method']) . '</small>';
                                        } elseif ($session['payment_status'] === 'failed') {
                                            echo '<span class="badge bg-danger">Failed</span>';
                                        } else {
                                            echo '<span class="badge bg-warning">Pending</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-5x text-muted mb-3"></i>
                <h4>No Sessions Yet</h4>
                <p class="text-muted">No charging sessions have been recorded at your stations yet.</p>
            </div>
        </div>
    <?php endif; ?>
</div>


<?php
$conn->close();
include '../partials/footer.php';
?>
