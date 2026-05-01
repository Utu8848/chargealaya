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

// Verify this reservation belongs to owner's station
$verify_query = "SELECT r.*, 
                        cs.user_id as owner_id,
                        CONCAT(u.first_name, ' ', u.last_name) as customer_name
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

// Check if already cancelled
if ($reservation['status'] === 'cancelled') {
    setFlashMessage("This reservation is already cancelled.", "warning");
    header("Location: manage_reservations.php");
    exit();
}

// Check if reservation has already started
$now = new DateTime();
$start_time = new DateTime($reservation['start_time']);
if ($now >= $start_time) {
    setFlashMessage("Cannot cancel a reservation that has already started.", "danger");
    header("Location: manage_reservations.php");
    exit();
}

// Cancel the reservation
$update_query = "UPDATE reservations 
                 SET status = 'cancelled'
                 WHERE reservation_id = $reservation_id";

if ($conn->query($update_query)) {
    setFlashMessage("Reservation cancelled successfully. Customer '{$reservation['customer_name']}' will be notified.", "success");
} else {
    setFlashMessage("Failed to cancel reservation: " . $conn->error, "danger");
}

header("Location: manage_reservations.php");
exit();

$conn->close();
?>
