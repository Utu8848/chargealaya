<?php
$page_title = "Payment Receipt";
include '../partials/header.php';
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

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card" id="receiptCard">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3><i class="fas fa-receipt"></i> Payment Receipt</h3>
                        <button onclick="window.print()" class="btn btn-light btn-sm">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body p-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-charging-station text-success"></i> ChargeAlaya</h2>
                        <p class="text-muted">Electric Vehicle Charging Management System</p>
                        <hr>
                    </div>
                    
                    <!-- Receipt Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Receipt Details</h5>
                            <p class="mb-1"><strong>Receipt No:</strong> #<?php echo str_pad($receipt['payment_id'], 6, '0', STR_PAD_LEFT); ?></p>
                            <p class="mb-1"><strong>Session ID:</strong> #<?php echo str_pad($receipt['session_id'], 6, '0', STR_PAD_LEFT); ?></p>
                            <p class="mb-1"><strong>Payment Date:</strong> <?php echo formatDateTime($receipt['transaction_time']); ?></p>
                            <p class="mb-1"><strong>Payment Method:</strong> <?php echo ucfirst($receipt['payment_method']); ?></p>
                            <p class="mb-1">
                                <strong>Status:</strong> 
                                <span class="badge bg-success">PAID</span>
                            </p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h5>Customer Details</h5>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($receipt['first_name'] . ' ' . $receipt['last_name']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($receipt['email']); ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($receipt['phone']); ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Charging Details -->
                    <h5 class="mb-3">Charging Session Details</h5>
                    <div class="table-responsive mb-4">
                        <table class="table">
                            <tr>
                                <th width="40%">Charging Station:</th>
                                <td><strong><?php echo htmlspecialchars($receipt['station_name']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td><?php echo htmlspecialchars($receipt['address'] . ', ' . $receipt['city']); ?></td>
                            </tr>
                            <tr>
                                <th>Vehicle:</th>
                                <td><?php echo htmlspecialchars($receipt['brand'] . ' ' . $receipt['model']); ?> 
                                    (<?php echo htmlspecialchars($receipt['license_plate']); ?>)</td>
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
                    </div>
                    
                    <hr>
                    
                    <!-- Payment Breakdown -->
                    <h5 class="mb-3">Payment Breakdown</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <th width="60%">Energy Charges (<?php echo number_format($receipt['energy_used_kwh'], 2); ?> kWh)</th>
                                <td class="text-end"><?php echo formatCurrency($receipt['cost']); ?></td>
                            </tr>
                            <tr class="table-active">
                                <th><h5 class="mb-0">Total Amount Paid</h5></th>
                                <td class="text-end"><h5 class="mb-0 text-success"><?php echo formatCurrency($receipt['amount']); ?></h5></td>
                            </tr>
                        </table>
                    </div>
                    
                    <hr>
                    
                    <!-- Footer -->
                    <div class="text-center mt-4">
                        <p class="text-muted small">
                            Thank you for using our EV Charging Service!<br>
                            For support, contact us at: support@evcharge.com.np | +977-1-4123456
                        </p>
                        <p class="text-muted small">
                            This is a computer-generated receipt and does not require a signature.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="text-center mt-3 no-print">
                <a href="charging_history.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to History
                </a>
                <a href="receipt_pdf.php?session_id=<?php echo $session_id; ?>" class="btn btn-custom" target="_blank">
                    <i class="fas fa-file-pdf"></i> Download as PDF
                </a>
            </div>
            
            <!-- PDF Instructions -->
            <div class="alert alert-info mt-3 no-print">
                <h6><i class="fas fa-info-circle"></i> How to Save as PDF:</h6>
                <ol class="mb-0" style="padding-left: 20px;">
                    <li>Click "Download as PDF" button above</li>
                    <li>In the print dialog, select "Save as PDF" or "Microsoft Print to PDF"</li>
                    <li>Click "Save" and choose your location</li>
                    <li>Your beautiful receipt will be saved with all colors!</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script src="\chargealaya\assets\js\scripts\view_receipt.js"></script>

<?php
$conn->close();
include '../partials/footer.php';
?>
