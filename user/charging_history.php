<?php
$page_title = "Charging History";
include '../partials/header.php';
requireLogin();

if (!hasRole('ev_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];


// Get charging sessions
$query = "SELECT cs.*, cst.station_name, cst.city, v.brand, v.model, c.charger_type, p.payment_status, p.payment_method
          FROM charging_sessions cs
          JOIN chargers c ON cs.charger_id = c.charger_id
          JOIN charging_stations cst ON c.station_id = cst.station_id
          JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
          LEFT JOIN payments p ON cs.session_id = p.session_id
          WHERE cs.user_id = $user_id
          ORDER BY cs.start_time DESC";
$sessions = $conn->query($query);

// Get statistics
$total_sessions = $sessions->num_rows;
$total_energy = 0;
$total_cost = 0;

if ($total_sessions > 0) {
    $sessions->data_seek(0);
    while ($session = $sessions->fetch_assoc()) {
        $total_energy += $session['energy_used_kwh'];
        $total_cost += $session['cost'];
    }
}
?>

<div class="container my-5">
    <h2><i class="fas fa-history text-success"></i> Charging History</h2>
    <p class="text-muted">View all your charging sessions</p>
    
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
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h4><?php echo number_format($total_energy, 2); ?> kWh</h4>
                    <p class="mb-0"><i class="fas fa-bolt"></i> Total Energy Used</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h4><?php echo formatCurrency($total_cost); ?></h4>
                    <p class="mb-0"><i class="fas fa-money-bill-wave"></i> Total Spent</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($total_sessions > 0): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> All Charging Sessions
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom" id="sessionsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Station</th>
                                <th>Vehicle</th>
                                <th>Charger</th>
                                <th>Duration</th>
                                <th>Energy (kWh)</th>
                                <th>Cost</th>
                                <th>Payment</th>
                                <th>Action</th>
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
                                    <td><?php echo formatDateTime($session['start_time']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($session['station_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($session['city']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($session['brand'] . ' ' . $session['model']); ?></td>
                                    <td><?php echo ucfirst($session['charger_type']); ?></td>
                                    <td><?php echo number_format($duration, 1); ?> hrs</td>
                                    <td><?php echo number_format($session['energy_used_kwh'], 2); ?></td>
                                    <td><strong><?php echo formatCurrency($session['cost']); ?></strong></td>
                                    <td>
                                        <?php
                                        if ($session['payment_status'] === 'paid') {
                                            echo '<span class="badge bg-success">Paid</span>';
                                        } elseif ($session['payment_status'] === 'failed') {
                                            echo '<span class="badge bg-danger">Failed</span>';
                                        } elseif ($session['payment_status'] === 'pending') {
                                            echo '<span class="badge bg-warning">Pending</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">Unknown</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($session['payment_status'] === 'pending'): ?>
                                            <a href="make_payment.php?session_id=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-custom text-center" style="min-width: 70px;">
                                                <i class="fas fa-credit-card d-block mb-1"></i>
                                                <small class="d-block">Pay</small>
                                            </a>
                                        <?php elseif ($session['payment_status'] === 'paid'): ?>
                                            <a href="receipt_pdf.php?session_id=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-secondary-custom" target="_blank">
                                                <i class="fas fa-receipt"></i> Receipt
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
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
                <i class="fas fa-battery-empty fa-5x text-muted mb-3"></i>
                <h4>No Charging History</h4>
                <p class="text-muted">You haven't used any charging stations yet.</p>
                <a href="/chargealaya/public/stations.php" class="btn btn-custom btn-lg">
                    <i class="fas fa-search"></i> Find Stations
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
