<?php
require_once '../config/database.php';
require_once '../partials/functions.php';
requireLogin();

if (!hasRole('station_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_reservations.php");
    exit();
}

$reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;

if ($reservation_id === 0) {
    setFlashMessage("Invalid reservation.", "danger");
    header("Location: manage_reservations.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Get reservation details with ownership verification
$verify_query = "SELECT r.*, 
                        cs.user_id as owner_id,
                        cs.station_id,
                        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                        c.max_power_kw
                 FROM reservations r
                 JOIN chargers c ON r.charger_id = c.charger_id
                 JOIN charging_stations cs ON c.station_id = cs.station_id
                 JOIN users u ON r.user_id = u.user_id
                 WHERE r.reservation_id = $reservation_id";
$verify_result = $conn->query($verify_query);

if ($verify_result->num_rows === 0) {
    setFlashMessage("Reservation not found.", "danger");
    header("Location: manage_reservations.php");
    exit();
}

$reservation = $verify_result->fetch_assoc();

// Check ownership
if ($reservation['owner_id'] != $user_id) {
    setFlashMessage("Unauthorized action.", "danger");
    header("Location: manage_reservations.php");
    exit();
}

// Check if already completed
if ($reservation['status'] === 'completed') {
    setFlashMessage("This reservation has already been completed.", "warning");
    header("Location: manage_reservations.php");
    exit();
}

// Check if already cancelled
if ($reservation['status'] === 'cancelled') {
    setFlashMessage("Cannot complete a cancelled reservation.", "danger");
    header("Location: manage_reservations.php");
    exit();
}

// CRITICAL: Check if end time has passed
$now = new DateTime();
$end_time = new DateTime($reservation['end_time']);
if ($now < $end_time) {
    setFlashMessage("Cannot complete reservation. The reservation end time has not been reached yet. Please wait until the reservation period is over.", "warning");
    header("Location: manage_reservations.php");
    exit();
}

// Calculate duration and energy
$start_time = new DateTime($reservation['start_time']);
$duration_seconds = $end_time->getTimestamp() - $start_time->getTimestamp();
$duration_hours = $duration_seconds / 3600;

// Estimate energy consumed (80% utilization of max power)
$energy_kwh = $reservation['max_power_kw'] * $duration_hours * 0.8;

// Get tariff for cost calculation
$tariff_query = "SELECT price_per_kwh, service_fee FROM tariffs 
                 WHERE station_id = " . $reservation['station_id'] . " LIMIT 1";
$tariff_result = $conn->query($tariff_query);
$tariff = $tariff_result->fetch_assoc();

$price_per_kwh = $tariff ? $tariff['price_per_kwh'] : 15.00;
$service_fee = $tariff ? $tariff['service_fee'] : 50.00;

// Calculate total cost
$energy_cost = $energy_kwh * $price_per_kwh;
$total_cost = $energy_cost + $service_fee;

// Start transaction
$conn->begin_transaction();

try {
    // 1. Update reservation status to completed
    $update_query = "UPDATE reservations SET status = 'completed' WHERE reservation_id = $reservation_id";
    if (!$conn->query($update_query)) {
        throw new Exception("Failed to update reservation status");
    }
    
    // 2. Create charging session
    $session_insert = "INSERT INTO charging_sessions 
                       (user_id, charger_id, vehicle_id, start_time, end_time, energy_used_kwh, cost, session_status) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')";
    $session_stmt = $conn->prepare($session_insert);
    $session_stmt->bind_param(
        "iiissdd",
        $reservation['user_id'],
        $reservation['charger_id'],
        $reservation['vehicle_id'],
        $reservation['start_time'],
        $reservation['end_time'],
        $energy_kwh,
        $total_cost
    );
    
    if (!$session_stmt->execute()) {
        throw new Exception("Failed to create charging session");
    }
    
    $session_id = $conn->insert_id;
    
    // 3. Create payment record with pending status
    $payment_insert = "INSERT INTO payments 
                       (session_id, user_id, amount, payment_method, payment_status) 
                       VALUES (?, ?, ?, 'pending', 'pending')";
    $payment_stmt = $conn->prepare($payment_insert);
    $payment_stmt->bind_param("iid", $session_id, $reservation['user_id'], $total_cost);
    
    if (!$payment_stmt->execute()) {
        throw new Exception("Failed to create payment record");
    }
    
    // Commit transaction
    $conn->commit();
    
    setFlashMessage("Reservation completed successfully! Charging session created for " . htmlspecialchars($reservation['customer_name']) . ". Session ID: #" . str_pad($session_id, 6, '0', STR_PAD_LEFT) . ". Total amount: NPR " . number_format($total_cost, 2) . " (Payment pending from customer).", "success");
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    setFlashMessage("Failed to complete reservation: " . $e->getMessage(), "danger");
}

header("Location: manage_reservations.php");
exit();

$conn->close();
?>
