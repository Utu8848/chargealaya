<?php
$page_title = "System Reports";
include '../partials/header.php';
requireAdmin();

$db = new Database();
$conn = $db->connect();

// Get date range
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : date('Y-m-d');

// Overall System Statistics
$system_stats = $conn->query("SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'ev_owner') as total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'station_owner') as total_owners,
    (SELECT COUNT(*) FROM charging_stations) as total_stations,
    (SELECT COUNT(*) FROM chargers) as total_chargers,
    (SELECT COUNT(*) FROM charging_sessions WHERE DATE(start_time) BETWEEN '$date_from' AND '$date_to') as total_sessions,
    (SELECT SUM(cost) FROM charging_sessions WHERE DATE(start_time) BETWEEN '$date_from' AND '$date_to') as total_revenue,
    (SELECT SUM(energy_used_kwh) FROM charging_sessions WHERE DATE(start_time) BETWEEN '$date_from' AND '$date_to') as total_energy,
    (SELECT COUNT(*) FROM payments WHERE payment_status = 'paid' AND DATE(transaction_time) BETWEEN '$date_from' AND '$date_to') as paid_payments
")->fetch_assoc();

// Top Performing Stations
$top_stations = $conn->query("SELECT cs_station.station_name, cs_station.city,
    COUNT(cs.session_id) as session_count,
    SUM(cs.cost) as revenue,
    SUM(cs.energy_used_kwh) as energy
    FROM charging_stations cs_station
    LEFT JOIN chargers c ON cs_station.station_id = c.station_id
    LEFT JOIN charging_sessions cs ON c.charger_id = cs.charger_id 
        AND DATE(cs.start_time) BETWEEN '$date_from' AND '$date_to'
    GROUP BY cs_station.station_id
    ORDER BY revenue DESC
    LIMIT 10");

// Revenue by Date
$daily_revenue = $conn->query("SELECT DATE(start_time) as date,
    COUNT(session_id) as sessions,
    SUM(cost) as revenue,
    SUM(energy_used_kwh) as energy
    FROM charging_sessions
    WHERE DATE(start_time) BETWEEN '$date_from' AND '$date_to'
    GROUP BY DATE(start_time)
    ORDER BY date DESC
    LIMIT 30");

// Top Customers
$top_customers = $conn->query("SELECT u.first_name, u.last_name, u.email,
    COUNT(cs.session_id) as session_count,
    SUM(cs.cost) as total_spent,
    SUM(cs.energy_used_kwh) as total_energy
    FROM users u
    JOIN charging_sessions cs ON u.user_id = cs.user_id
    WHERE DATE(cs.start_time) BETWEEN '$date_from' AND '$date_to'
    GROUP BY u.user_id
    ORDER BY total_spent DESC
    LIMIT 10");

// Payment Methods Distribution
$payment_methods = $conn->query("SELECT payment_method,
    COUNT(*) as count,
    SUM(amount) as total_amount
    FROM payments
    WHERE DATE(transaction_time) BETWEEN '$date_from' AND '$date_to'
    GROUP BY payment_method
    ORDER BY total_amount DESC");

// Station Status Overview
$station_status = $conn->query("SELECT status, COUNT(*) as count
    FROM charging_stations
    GROUP BY status");

// User Registration Trend
$user_registrations = $conn->query("SELECT DATE(created_at) as date,
    COUNT(*) as new_users
    FROM users
    WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 30");
?>

<div class="container my-5">
    <h2><i class="fas fa-chart-pie text-success"></i> System Reports & Analytics</h2>
    <p class="text-muted">Comprehensive system insights and performance metrics</p>
    
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
    <h4 class="mb-3">System Overview</h4>
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h4><?php echo $system_stats['total_users']; ?></h4>
                    <p class="mb-0"><i class="fas fa-users"></i> Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h4><?php echo $system_stats['total_owners']; ?></h4>
                    <p class="mb-0"><i class="fas fa-store"></i> Station Owners</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h4><?php echo $system_stats['total_stations']; ?></h4>
                    <p class="mb-0"><i class="fas fa-charging-station"></i> Stations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h4><?php echo $system_stats['total_chargers']; ?></h4>
                    <p class="mb-0"><i class="fas fa-plug"></i> Chargers</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Period Statistics -->
    <h4 class="mb-3">Period Performance (<?php echo formatDate($date_from) . ' - ' . formatDate($date_to); ?>)</h4>
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h4><?php echo $system_stats['total_sessions']; ?></h4>
                    <p class="mb-0"><i class="fas fa-bolt"></i> Total Sessions</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-success">
                <div class="card-body">
                    <h4><?php echo formatCurrency($system_stats['total_revenue']); ?></h4>
                    <p class="mb-0"><i class="fas fa-money-bill-wave"></i> Total Revenue</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h4><?php echo number_format($system_stats['total_energy'], 2); ?> kWh</h4>
                    <p class="mb-0"><i class="fas fa-bolt"></i> Energy Dispensed</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Performing Stations -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-trophy"></i> Top Performing Stations</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Station Name</th>
                            <th>City</th>
                            <th>Sessions</th>
                            <th>Energy (kWh)</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while ($station = $top_stations->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><strong>#<?php echo $rank++; ?></strong></td>
                                <td><?php echo htmlspecialchars($station['station_name']); ?></td>
                                <td><?php echo htmlspecialchars($station['city']); ?></td>
                                <td><?php echo $station['session_count']; ?></td>
                                <td><?php echo number_format($station['energy'], 2); ?></td>
                                <td><strong><?php echo formatCurrency($station['revenue']); ?></strong></td>
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
            <h5><i class="fas fa-chart-line"></i> Daily Revenue Trend</h5>
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
                        <?php while ($daily = $daily_revenue->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($daily['date']); ?></td>
                                <td><?php echo $daily['sessions']; ?></td>
                                <td><?php echo number_format($daily['energy'], 2); ?></td>
                                <td><strong><?php echo formatCurrency($daily['revenue']); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Top Customers -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-user-tie"></i> Top Customers</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Sessions</th>
                            <th>Energy (kWh)</th>
                            <th>Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while ($customer = $top_customers->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><strong>#<?php echo $rank++; ?></strong></td>
                                <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo $customer['session_count']; ?></td>
                                <td><?php echo number_format($customer['total_energy'], 2); ?></td>
                                <td><strong><?php echo formatCurrency($customer['total_spent']); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods Distribution -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-credit-card"></i> Payment Methods Distribution</h5>
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
                        $total_payment_amount = 0;
                        $payment_methods->data_seek(0);
                        while ($pm = $payment_methods->fetch_assoc()) {
                            $total_payment_amount += $pm['total_amount'];
                        }
                        $payment_methods->data_seek(0);
                        while ($pm = $payment_methods->fetch_assoc()): 
                            $percentage = $total_payment_amount > 0 ? ($pm['total_amount'] / $total_payment_amount) * 100 : 0;
                        ?>
                            <tr>
                                <td><strong><?php echo ucfirst($pm['payment_method']); ?></strong></td>
                                <td><?php echo $pm['count']; ?></td>
                                <td><?php echo formatCurrency($pm['total_amount']); ?></td>
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
