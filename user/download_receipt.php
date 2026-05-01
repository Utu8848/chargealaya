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
    header("Location: charging_history.php");
    exit();
}

$receipt = $result->fetch_assoc();
$duration = (strtotime($receipt['end_time']) - strtotime($receipt['start_time'])) / 3600;

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="ChargeAlaya_Receipt_' . str_pad($receipt['payment_id'], 6, '0', STR_PAD_LEFT) . '.pdf"');

// For now, redirect to print view and let browser handle PDF generation
// This is the most compatible solution without external libraries
header('Location: view_receipt.php?session_id=' . $session_id . '&print=1');
exit();
?>

