<?php
$page_title = "All Sessions";
include '../partials/header.php';
requireAdmin();

$db = new Database();
$conn = $db->connect();

// Get filter parameters
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : date('Y-m-d');
$station_filter = isset($_GET['station_id']) ? intval($_GET['station_id']) : 0;
$payment_filter = isset($_GET['payment_status']) ? sanitize($_GET['payment_status']) : '';

// Build query
$query = "SELECT cs.*, 
          cst.station_name, cst.city,
          c.charger_type,
          v.brand, v.model,
          u.first_name, u.last_name, u.email,
          owner.first_name as owner_first, owner.last_name as owner_last,
          p.payment_status, p.payment_method
          FROM charging_sessions cs
          JOIN chargers c ON cs.charger_id = c.charger_id
          JOIN charging_stations cst ON c.station_id = cst.station_id
          JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
          JOIN users u ON cs.user_id = u.user_id
          JOIN users owner ON cst.user_id = owner.user_id
          LEFT JOIN payments p ON cs.session_id = p.session_id
          WHERE DATE(cs.start_time) BETWEEN '$date_from' AND '$date_to'";

if ($station_filter > 0) {
    $query .= " AND cst.station_id = $station_filter";
}
if ($payment_filter) {
    if ($payment_filter === 'pending') {
        $query .= " AND (p.payment_status IS NULL OR p.payment_status = 'pending')";
    } else {
        $query .= " AND p.payment_status = '$payment_filter'";
    }
}

$query .= " ORDER BY cs.start_time DESC";
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

// Get all stations for filter
$stations_query = "SELECT station_id, station_name, city FROM charging_stations ORDER BY station_name";
$all_stations = $conn->query($stations_query);
?>

<div class="container my-5">
    <h2><i class="fas fa-bolt text-success"></i> All Charging Sessions</h2>
    <p class="text-muted">Monitor all charging sessions across the platform</p>
    
    <!-- Statistics -->
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
                    <p class="mb-0"><i class="fas fa-bolt"></i> Total Energy</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <label for="station_id" class="form-label">Station</label>
                    <select class="form-control" id="station_id" name="station_id">
                        <option value="">All Stations</option>
                        <?php while ($st = $all_stations->fetch_assoc()): ?>
                            <option value="<?php echo $st['station_id']; ?>" 
                                    <?php echo $station_filter == $st['station_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($st['station_name'] . ' - ' . $st['city']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="payment_status" class="form-label">Payment Status</label>
                    <select class="form-control" id="payment_status" name="payment_status">
                        <option value="">All</option>
                        <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-custom">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Sessions Table -->
    <?php if ($total_sessions > 0): ?>
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Sessions (<?php echo $total_sessions; ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Station</th>
                                <th>Owner</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Duration</th>
                                <th>Energy</th>
                                <th>Cost</th>
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
                                        <small><?php echo htmlspecialchars($session['owner_first'] . ' ' . $session['owner_last']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($session['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($session['brand'] . ' ' . $session['model']); ?></td>
                                    <td><?php echo number_format($duration, 2); ?> hrs</td>
                                    <td><?php echo number_format($session['energy_used_kwh'], 2); ?> kWh</td>
                                    <td><strong><?php echo formatCurrency($session['cost']); ?></strong></td>
                                    <td>
                                        <?php
                                        if ($session['payment_status'] === 'paid') {
                                            echo '<span class="badge bg-success">Paid</span>';
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
                <h4>No Sessions Found</h4>
                <p class="text-muted">No charging sessions match your filter criteria.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
