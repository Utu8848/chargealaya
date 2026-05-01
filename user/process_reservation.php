<?php
require_once '../config/database.php';
require_once '../partials/functions.php';
requireLogin();

if (!hasRole('ev_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: make_reservation.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Get form data
$charger_id = isset($_POST['charger_id']) ? intval($_POST['charger_id']) : 0;
$vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
$start_time = isset($_POST['start_time']) ? $_POST['start_time'] : '';
$duration = isset($_POST['duration']) ? floatval($_POST['duration']) : 0;
$station_id = isset($_POST['station_id']) ? intval($_POST['station_id']) : 0;

// Validate inputs
if ($charger_id === 0 || $vehicle_id === 0 || empty($start_time) || $duration === 0) {
    setFlashMessage("Please fill all required fields.", "danger");
    header("Location: make_reservation.php?station_id=" . $station_id);
    exit();
}

// Validate start time is in the future
$start = new DateTime($start_time);
$now = new DateTime();
if ($start <= $now) {
    setFlashMessage("Reservation start time must be in the future. You cannot reserve for past times.", "danger");
    header("Location: make_reservation.php?station_id=" . $station_id);
    exit();
}

// Calculate end time
$end = clone $start;
$hours = floor($duration);
$minutes = ($duration - $hours) * 60;
$end->add(new DateInterval('PT' . $hours . 'H' . $minutes . 'M'));

$end_time = $end->format('Y-m-d H:i:s');
$start_time_formatted = $start->format('Y-m-d H:i:s');

// Verify charger exists and is available
$charger_query = "SELECT c.*, cs.station_name 
                  FROM chargers c 
                  JOIN charging_stations cs ON c.station_id = cs.station_id
                  WHERE c.charger_id = $charger_id AND c.status = 'available'";
$charger_result = $conn->query($charger_query);

if ($charger_result->num_rows === 0) {
    setFlashMessage("Selected charger is not available.", "danger");
    header("Location: /chargealaya/public/stations.php");
    exit();
}

$charger = $charger_result->fetch_assoc();

// Verify vehicle belongs to user
$vehicle_query = "SELECT * FROM vehicles WHERE vehicle_id = $vehicle_id AND user_id = $user_id";
$vehicle_result = $conn->query($vehicle_query);

if ($vehicle_result->num_rows === 0) {
    setFlashMessage("Invalid vehicle selected.", "danger");
    header("Location: make_reservation.php?station_id=" . $station_id);
    exit();
}

$vehicle = $vehicle_result->fetch_assoc();

// CRITICAL: Verify connector type matches
if ($vehicle['connector_type'] !== $charger['connector_type']) {
    setFlashMessage("Invalid charger selected. Please select a charger that matches connector type with vehicle. Your vehicle uses " . htmlspecialchars($vehicle['connector_type']) . " but selected charger uses " . htmlspecialchars($charger['connector_type']) . ".", "danger");
    header("Location: make_reservation.php?station_id=" . $station_id);
    exit();
}

// Check 1: Charger conflict - NO other user (or same user) can reserve same charger at same time
$charger_conflict_query = "SELECT r.reservation_id, CONCAT(u.first_name, ' ', u.last_name) as user_name
                           FROM reservations r
                           JOIN users u ON r.user_id = u.user_id
                           WHERE r.charger_id = $charger_id 
                           AND r.status IN ('pending', 'confirmed')
                           AND (
                               ('$start_time_formatted' >= r.start_time AND '$start_time_formatted' < r.end_time)
                               OR ('$end_time' > r.start_time AND '$end_time' <= r.end_time)
                               OR ('$start_time_formatted' <= r.start_time AND '$end_time' >= r.end_time)
                           )";
$charger_conflict_result = $conn->query($charger_conflict_query);

if ($charger_conflict_result->num_rows > 0) {
    $conflict = $charger_conflict_result->fetch_assoc();
    if ($conflict['user_name']) {
        setFlashMessage("This charger is already reserved during your selected time slot. Please choose a different charger or time.", "warning");
    } else {
        setFlashMessage("This charger is already reserved during your selected time slot. Please choose a different charger or time.", "warning");
    }
    header("Location: make_reservation.php?station_id=" . $station_id);
    exit();
}

// Check 2: Vehicle/User conflict - Same user cannot have overlapping reservations
$vehicle_conflict_query = "SELECT r.reservation_id, cs.station_name, c.charger_type
                           FROM reservations r
                           JOIN chargers c ON r.charger_id = c.charger_id
                           JOIN charging_stations cs ON c.station_id = cs.station_id
                           WHERE r.user_id = $user_id
                           AND r.status IN ('pending', 'confirmed')
                           AND (
                               ('$start_time_formatted' >= r.start_time AND '$start_time_formatted' < r.end_time)
                               OR ('$end_time' > r.start_time AND '$end_time' <= r.end_time)
                               OR ('$start_time_formatted' <= r.start_time AND '$end_time' >= r.end_time)
                           )";
$vehicle_conflict_result = $conn->query($vehicle_conflict_query);

if ($vehicle_conflict_result->num_rows > 0) {
    $conflict = $vehicle_conflict_result->fetch_assoc();
    setFlashMessage("You already have a reservation during this time slot at " . htmlspecialchars($conflict['station_name']) . ". You cannot have multiple reservations at the same time.", "warning");
    header("Location: make_reservation.php?station_id=" . $station_id);
    exit();
}

// Create reservation
$insert_query = "INSERT INTO reservations (user_id, charger_id, vehicle_id, start_time, end_time, status) 
                 VALUES ($user_id, $charger_id, $vehicle_id, '$start_time_formatted', '$end_time', 'confirmed')";

if ($conn->query($insert_query)) {
    $reservation_id = $conn->insert_id;
    setFlashMessage("Reservation created successfully! Reservation ID: #" . str_pad($reservation_id, 6, '0', STR_PAD_LEFT), "success");
    header("Location: reservations.php");
} else {
    setFlashMessage("Failed to create reservation. Please try again. Error: " . $conn->error, "danger");
    header("Location: make_reservation.php?station_id=" . $station_id);
}

exit();

$conn->close();
?>
