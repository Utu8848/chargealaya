<?php
$page_title = "Reports & Analytics";
include '../partials/header.php';
requireStationOwner();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Get date range filter
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : date('Y-m-d');

// Revenue by Station
$revenue_query = "SELECT cst.station_name, cst.city,
                  COUNT(cs.session_id) as session_count,
                  SUM(cs.energy_used_kwh) as total_energy,
                  SUM(cs.cost) as total_revenue
                  FROM charging_stations cst
                  LEFT JOIN chargers c ON cst.station_id = c.station_id
                  LEFT JOIN charging_sessions cs ON c.charger_id = cs.charger_id
                    AND DATE(cs.start_time) BETWEEN '$date_from' AND '$date_to'
                  WHERE cst.user_id = $user_id
                  GROUP BY cst.station_id
                  ORDER BY total_revenue DESC";
$revenue_data = $conn->query($revenue_query);

// Daily Revenue Trend
$daily_query = "SELECT DATE(cs.start_time) as date,
                COUNT(cs.session_id) as sessions,
                SUM(cs.cost) as revenue,
                SUM(cs.energy_used_kwh) as energy
                FROM charging_sessions cs
                JOIN chargers c ON cs.charger_id = c.charger_id
                JOIN charging_stations cst ON c.station_id = cst.station_id
                WHERE cst.user_id = $user_id
                AND DATE(cs.start_time) BETWEEN '$date_from' AND '$date_to'
                GROUP BY DATE(cs.start_time)
                ORDER BY date DESC
                LIMIT 30";
$daily_data = $conn->query($daily_query);

// Charger Performance
$charger_query = "SELECT cst.station_name, c.charger_id, c.charger_type, c.max_power_kw,
                  COUNT(cs.session_id) as session_count,
                  SUM(cs.energy_used_kwh) as total_energy,
                  SUM(cs.cost) as total_revenue,
                  AVG(cs.energy_used_kwh) as avg_energy
                  FROM chargers c
                  JOIN charging_stations cst ON c.station_id = cst.station_id
                  LEFT JOIN charging_sessions cs ON c.charger_id = cs.charger_id
                    AND DATE(cs.start_time) BETWEEN '$date_from' AND '$date_to'
                  WHERE cst.user_id = $user_id
                  GROUP BY c.charger_id
                  ORDER BY total_revenue DESC";
$charger_data = $conn->query($charger_query);

// Payment Methods
$payment_query = "SELECT p.payment_method,
                  COUNT(*) as payment_count,
                  SUM(p.amount) as total_amount
                  FROM payments p
                  JOIN charging_sessions cs ON p.session_id = cs.session_id
                  JOIN chargers c ON cs.charger_id = c.charger_id
                  JOIN charging_stations cst ON c.station_id = cst.station_id
                  WHERE cst.user_id = $user_id
                  AND DATE(p.transaction_time) BETWEEN '$date_from' AND '$date_to'
                  GROUP BY p.payment_method
                  ORDER BY total_amount DESC";
$payment_data = $conn->query($payment_query);

// Overall Statistics
$stats_query = "SELECT 
                COUNT(DISTINCT cs.session_id) as total_sessions,
                SUM(cs.cost) as total_revenue,
                SUM(cs.energy_used_kwh) as total_energy,
                COUNT(DISTINCT cs.user_id) as unique_customers,
                AVG(cs.cost) as avg_revenue_per_session
                FROM charging_sessions cs
                JOIN chargers c ON cs.charger_id = c.charger_id
                JOIN charging_stations cst ON c.station_id = cst.station_id
                WHERE cst.user_id = $user_id
                AND DATE(cs.start_time) BETWEEN '$date_from' AND '$date_to'";
$stats = $conn->query($stats_query)->fetch_assoc();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-chart-line text-success"></i> Reports & Analytics</h2>
            <p class="text-muted">Business insights and performance metrics</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-4">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-custom w-100">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Overall Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h4><?php echo $stats['total_sessions'] ?? 0; ?></h4>
                    <p class="mb-0"><i class="fas fa-charging-station"></i> Total Sessions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h4><?php echo formatCurrency($stats['total_revenue'] ?? 0); ?></h4>
                    <p class="mb-0"><i class="fas fa-money-bill-wave"></i> Total Revenue</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h4><?php echo number_format($stats['total_energy'] ?? 0, 2); ?> kWh</h4>
                    <p class="mb-0"><i class="fas fa-bolt"></i> Energy Dispensed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h4><?php echo $stats['unique_customers'] ?? 0; ?></h4>
                    <p class="mb-0"><i class="fas fa-users"></i> Unique Customers</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Revenue by Station -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-chart-bar"></i> Revenue by Station</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Station Name</th>
                            <th>City</th>
                            <th>Sessions</th>
                            <th>Energy (kWh)</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $revenue_data->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['station_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['city']); ?></td>
                                <td><?php echo $row['session_count']; ?></td>
                                <td><?php echo number_format($row['total_energy'], 2); ?></td>
                                <td><strong><?php echo formatCurrency($row['total_revenue']); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Daily Revenue Trend -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-calendar-alt"></i> Daily Performance (Last 30 Days)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sessions</th>
                            <th>Energy (kWh)</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $daily_data->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($row['date']); ?></td>
                                <td><?php echo $row['sessions']; ?></td>
                                <td><?php echo number_format($row['energy'], 2); ?></td>
                                <td><strong><?php echo formatCurrency($row['revenue']); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Charger Performance -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-plug"></i> Charger Performance</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Station</th>
                            <th>Charger ID</th>
                            <th>Type</th>
                            <th>Power (kW)</th>
                            <th>Sessions</th>
                            <th>Avg Energy</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $charger_data->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['station_name']); ?></td>
                                <td>#<?php echo $row['charger_id']; ?></td>
                                <td><?php echo ucfirst($row['charger_type']); ?></td>
                                <td><?php echo $row['max_power_kw']; ?></td>
                                <td><?php echo $row['session_count']; ?></td>
                                <td><?php echo number_format($row['avg_energy'], 2); ?> kWh</td>
                                <td><strong><?php echo formatCurrency($row['total_revenue']); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-credit-card"></i> Payment Methods Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th>Transactions</th>
                            <th>Total Amount</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_payments = 0;
                        $payment_data->data_seek(0);
                        while ($row = $payment_data->fetch_assoc()) {
                            $total_payments += $row['total_amount'];
                        }
                        $payment_data->data_seek(0);
                        while ($row = $payment_data->fetch_assoc()): 
                            $percentage = $total_payments > 0 ? ($row['total_amount'] / $total_payments) * 100 : 0;
                        ?>
                            <tr>
                                <td><strong><?php echo ucfirst($row['payment_method']); ?></strong></td>
                                <td><?php echo $row['payment_count']; ?></td>
                                <td><?php echo formatCurrency($row['total_amount']); ?></td>
                                <td>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </div>
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
