<?php
$page_title = "Make Payment";
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

// Get session details
$session_query = "SELECT cs.*, cst.station_name, cst.address, v.brand, v.model, c.charger_type, 
                  p.payment_id, p.payment_status
                  FROM charging_sessions cs
                  JOIN chargers c ON cs.charger_id = c.charger_id
                  JOIN charging_stations cst ON c.station_id = cst.station_id
                  JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
                  LEFT JOIN payments p ON cs.session_id = p.session_id
                  WHERE cs.session_id = $session_id AND cs.user_id = $user_id";
$session_result = $conn->query($session_query);

if ($session_result->num_rows === 0) {
    setFlashMessage("Session not found.", "danger");
    header("Location: charging_history.php");
    exit();
}

$session = $session_result->fetch_assoc();

// Check if already paid
if ($session['payment_status'] === 'paid') {
    setFlashMessage("This session has already been paid.", "info");
    header("Location: charging_history.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = sanitize($_POST['payment_method']);
    $amount = floatval($session['cost']);
    
    // Direct payment processing - no credentials needed
    $payment_success = true; // All methods work directly
    
    if ($payment_success) {
        // Insert or update payment
        if ($session['payment_id']) {
            // Update existing payment
            $update_stmt = $conn->prepare("UPDATE payments SET payment_status = 'paid', payment_method = ?, transaction_time = NOW() WHERE payment_id = ?");
            $update_stmt->bind_param("si", $payment_method, $session['payment_id']);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert new payment
            $insert_stmt = $conn->prepare("INSERT INTO payments (session_id, user_id, amount, payment_method, payment_status) VALUES (?, ?, ?, ?, 'paid')");
            $insert_stmt->bind_param("iids", $session_id, $user_id, $amount, $payment_method);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        
        setFlashMessage("Payment successful! Thank you for your payment.", "success");
        header("Location: view_receipt.php?session_id=$session_id");
        exit();
    } else {
        setFlashMessage("Payment failed. Please check your details and try again.", "danger");
    }
}

$duration = 0;
if ($session['end_time']) {
    $duration = (strtotime($session['end_time']) - strtotime($session['start_time'])) / 3600;
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3><i class="fas fa-credit-card"></i> Make Payment</h3>
                </div>
                <div class="card-body">
                    <!-- Session Summary -->
                    <h5 class="mb-3">Charging Session Summary</h5>
                    <div class="table-responsive mb-4">
                        <table class="table">
                            <tr>
                                <th width="40%">Station:</th>
                                <td><?php echo htmlspecialchars($session['station_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Vehicle:</th>
                                <td><?php echo htmlspecialchars($session['brand'] . ' ' . $session['model']); ?></td>
                            </tr>
                            <tr>
                                <th>Charger Type:</th>
                                <td><?php echo ucfirst($session['charger_type']); ?></td>
                            </tr>
                            <tr>
                                <th>Date & Time:</th>
                                <td><?php echo formatDateTime($session['start_time']); ?></td>
                            </tr>
                            <tr>
                                <th>Duration:</th>
                                <td><?php echo number_format($duration, 2); ?> hours</td>
                            </tr>
                            <tr>
                                <th>Energy Used:</th>
                                <td><?php echo number_format($session['energy_used_kwh'], 2); ?> kWh</td>
                            </tr>
                            <tr class="table-active">
                                <th><h5 class="mb-0">Total Amount:</h5></th>
                                <td><h5 class="mb-0 text-success"><?php echo formatCurrency($session['cost']); ?></h5></td>
                            </tr>
                        </table>
                    </div>
                    
                    <hr>
                    
                    <!-- Payment Form -->
                    <h5 class="mb-3">Select Payment Method</h5>
                    <form method="POST" action="" id="paymentForm">
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="esewa" value="esewa" required>
                                        <label class="form-check-label w-100" for="esewa">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <img src="/chargealaya/assets/img/esewa_logo.png" 
                                                         alt="eSewa" 
                                                         style="height: 60px; width: auto; margin-bottom: 10px;"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                    <i class="fas fa-wallet fa-3x text-success mb-2" style="display: none;"></i>
                                                    <h6>eSewa</h6>
                                                    <small class="text-muted">Digital wallet</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="khalti" value="khalti">
                                        <label class="form-check-label w-100" for="khalti">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <img src="/chargealaya/assets/img/khalti_logo.webp" 
                                                         alt="Khalti" 
                                                         style="height: 60px; width: auto; margin-bottom: 10px;"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                    <i class="fas fa-mobile-alt fa-3x text-primary mb-2" style="display: none;"></i>
                                                    <h6>Khalti</h6>
                                                    <small class="text-muted">Digital payment</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="card" value="card">
                                        <label class="form-check-label w-100" for="card">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-credit-card fa-3x text-info mb-2"></i>
                                                    <h6>Card Payment</h6>
                                                    <small class="text-muted">Credit/Debit card</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="cash" value="cash">
                                        <label class="form-check-label w-100" for="cash">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-money-bill-wave fa-3x text-warning mb-2"></i>
                                                    <h6>Cash</h6>
                                                    <small class="text-muted">Pay in cash</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Quick Payment:</strong> Simply select your payment method and click Pay. No additional details required!
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between">
                            <a href="charging_history.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check"></i> Pay <?php echo formatCurrency($session['cost']); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
