<?php
require_once '../config/database.php';
require_once '../partials/functions.php';
requireLogin();

if (!hasRole('ev_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

if ($session_id === 0) {
    header("Location: charging_history.php");
    exit();
}

// Get session and payment details
$query = "SELECT cs.*, cst.station_name, cst.address, cst.city, v.brand, v.model, v.license_plate,
          c.charger_type, c.max_power_kw, p.*, u.first_name, u.last_name, u.email, u.phone
          FROM charging_sessions cs
          JOIN chargers c ON cs.charger_id = c.charger_id
          JOIN charging_stations cst ON c.station_id = cst.station_id
          JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
          JOIN payments p ON cs.session_id = p.session_id
          JOIN users u ON cs.user_id = u.user_id
          WHERE cs.session_id = $session_id AND cs.user_id = $user_id";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    setFlashMessage("Receipt not found.", "danger");
    header("Location: charging_history.php");
    exit();
}

$receipt = $result->fetch_assoc();
$duration = (strtotime($receipt['end_time']) - strtotime($receipt['start_time'])) / 3600;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo str_pad($receipt['payment_id'], 6, '0', STR_PAD_LEFT); ?> - ChargeAlaya</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/chargealaya/assets/css/receipt.css?v=1.0">
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="icon"><i class="fas fa-charging-station"></i></div>
            <h1>ChargeAlaya</h1>
            <p>Electric Vehicle Charging Management System</p>
        </div>
        
        <!-- Receipt & Customer Info -->
        <div class="two-columns">
            <div class="column">
                <div class="section-title">Receipt Details</div>
                <div class="info-row">
                    <span class="info-label">Receipt No:</span>
                    <span class="info-value">#<?php echo str_pad($receipt['payment_id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Session ID:</span>
                    <span class="info-value">#<?php echo str_pad($receipt['session_id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value"><?php echo formatDateTime($receipt['transaction_time']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value"><?php echo ucfirst($receipt['payment_method']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="badge">PAID</span>
                </div>
            </div>
            
            <div class="column">
                <div class="section-title">Customer Details</div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($receipt['first_name'] . ' ' . $receipt['last_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($receipt['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($receipt['phone']); ?></span>
                </div>
            </div>
        </div>
        
        <hr>
        
        <!-- Charging Session Details -->
        <div class="section-title">Charging Session Details</div>
        <table>
            <tr>
                <th style="width: 40%;">Charging Station:</th>
                <td><strong><?php echo htmlspecialchars($receipt['station_name']); ?></strong></td>
            </tr>
            <tr>
                <th>Location:</th>
                <td><?php echo htmlspecialchars($receipt['address'] . ', ' . $receipt['city']); ?></td>
            </tr>
            <tr>
                <th>Vehicle:</th>
                <td><?php echo htmlspecialchars($receipt['brand'] . ' ' . $receipt['model']); ?> (<?php echo htmlspecialchars($receipt['license_plate']); ?>)</td>
            </tr>
            <tr>
                <th>Charger Type:</th>
                <td><?php echo ucfirst($receipt['charger_type']); ?> - <?php echo $receipt['max_power_kw']; ?> kW</td>
            </tr>
            <tr>
                <th>Start Time:</th>
                <td><?php echo formatDateTime($receipt['start_time']); ?></td>
            </tr>
            <tr>
                <th>End Time:</th>
                <td><?php echo formatDateTime($receipt['end_time']); ?></td>
            </tr>
            <tr>
                <th>Duration:</th>
                <td><?php echo number_format($duration, 2); ?> hours</td>
            </tr>
            <tr>
                <th>Energy Consumed:</th>
                <td><strong><?php echo number_format($receipt['energy_used_kwh'], 2); ?> kWh</strong></td>
            </tr>
        </table>
        
        <hr>
        
        <!-- Payment Breakdown -->
        <div class="section-title">Payment Breakdown</div>
        <table>
            <tr>
                <th style="width: 70%;">Energy Charges (<?php echo number_format($receipt['energy_used_kwh'], 2); ?> kWh)</th>
                <td class="text-right">NPR <?php echo number_format($receipt['cost'], 2); ?></td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Amount Paid</strong></td>
                <td class="text-right"><strong>NPR <?php echo number_format($receipt['amount'], 2); ?></strong></td>
            </tr>
        </table>
        
        <hr>
        
        <!-- Footer -->
        <div class="receipt-footer">
            <p><strong>Thank you for using ChargeAlaya!</strong></p>
            <p>For support, contact us at: support@chargealaya.com.np | +977-1-4123456</p>
            <p>This is a computer-generated receipt and does not require a signature.</p>
        </div>
        
        <!-- Action Buttons (Hidden on Print) -->
        <div class="action-buttons">
            <a href="charging_history.php" class="btn btn-secondary">← Back to History</a>
            <button onclick="window.print()" class="btn btn-primary">📄 Download as PDF</button>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
