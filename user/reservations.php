<?php
$page_title = "My Reservations";
include '../partials/header.php';
requireLogin();

if (!hasRole('ev_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Handle cancel reservation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $reservation_id = intval($_GET['cancel']);
    
    $check_stmt = $conn->prepare("SELECT reservation_id FROM reservations WHERE reservation_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $reservation_id, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $cancel_stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ?");
        $cancel_stmt->bind_param("i", $reservation_id);
        
        if ($cancel_stmt->execute()) {
            setFlashMessage("Reservation cancelled successfully!", "success");
        } else {
            setFlashMessage("Failed to cancel reservation.", "danger");
        }
        $cancel_stmt->close();
    }
    $check_stmt->close();
    
    header("Location: reservations.php");
    exit();
}

// Get reservations with vehicle info
$query = "SELECT r.*, 
          cs.station_name, cs.address, cs.city, 
          c.charger_type, c.max_power_kw, c.connector_type,
          CONCAT(v.brand, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info
          FROM reservations r
          JOIN chargers c ON r.charger_id = c.charger_id
          JOIN charging_stations cs ON c.station_id = cs.station_id
          LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
          WHERE r.user_id = $user_id
          AND r.status IN ('confirmed', 'cancelled', 'completed')
          ORDER BY r.start_time DESC";
$reservations = $conn->query($query);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-calendar-check text-success"></i> My Reservations</h2>
            <p class="text-muted">Manage your charging station bookings</p>
        </div>
        <a href="/chargealaya/public/stations.php" class="btn btn-custom">
            <i class="fas fa-plus"></i> New Reservation
        </a>
    </div>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-pills mb-4" id="reservationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all" type="button">
                All Reservations
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="upcoming-tab" data-bs-toggle="pill" data-bs-target="#upcoming" type="button">
                Upcoming
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="past-tab" data-bs-toggle="pill" data-bs-target="#past" type="button">
                Past
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cancelled-tab" data-bs-toggle="pill" data-bs-target="#cancelled" type="button">
                Cancelled
            </button>
        </li>
    </ul>
    
    <?php if ($reservations->num_rows > 0): ?>
        <div class="tab-content" id="reservationTabContent">
            <!-- All Reservations -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <?php
                $reservations->data_seek(0);
                while ($res = $reservations->fetch_assoc()):
                    $is_upcoming = strtotime($res['start_time']) > time() && $res['status'] === 'confirmed';
                    $is_past = strtotime($res['end_time']) < time();
                ?>
                    <?php include 'reservation_card.php'; ?>
                <?php endwhile; ?>
            </div>
            
            <!-- Upcoming -->
            <div class="tab-pane fade" id="upcoming" role="tabpanel">
                <?php
                $reservations->data_seek(0);
                $has_upcoming = false;
                while ($res = $reservations->fetch_assoc()):
                    if (strtotime($res['start_time']) > time() && $res['status'] === 'confirmed'):
                        $has_upcoming = true;
                        $is_upcoming = true;
                        $is_past = false;
                ?>
                    <?php include 'reservation_card.php'; ?>
                <?php 
                    endif;
                endwhile;
                if (!$has_upcoming):
                ?>
                    <div class="alert alert-info">No upcoming reservations.</div>
                <?php endif; ?>
            </div>
            
            <!-- Past -->
            <div class="tab-pane fade" id="past" role="tabpanel">
                <?php
                $reservations->data_seek(0);
                $has_past = false;
                while ($res = $reservations->fetch_assoc()):
                    if (strtotime($res['end_time']) < time() && $res['status'] !== 'cancelled'):
                        $has_past = true;
                        $is_upcoming = false;
                        $is_past = true;
                ?>
                    <?php include 'reservation_card.php'; ?>
                <?php 
                    endif;
                endwhile;
                if (!$has_past):
                ?>
                    <div class="alert alert-info">No past reservations.</div>
                <?php endif; ?>
            </div>
            
            <!-- Cancelled -->
            <div class="tab-pane fade" id="cancelled" role="tabpanel">
                <?php
                $reservations->data_seek(0);
                $has_cancelled = false;
                while ($res = $reservations->fetch_assoc()):
                    if ($res['status'] === 'cancelled'):
                        $has_cancelled = true;
                        $is_upcoming = false;
                        $is_past = false;
                ?>
                    <?php include 'reservation_card.php'; ?>
                <?php 
                    endif;
                endwhile;
                if (!$has_cancelled):
                ?>
                    <div class="alert alert-info">No cancelled reservations.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-calendar-times fa-5x text-muted mb-3"></i>
                <h4>No Reservations Yet</h4>
                <p class="text-muted">Make your first reservation to secure a charging slot.</p>
                <a href="/chargealaya/public/stations.php" class="btn btn-custom btn-lg">
                    <i class="fas fa-search"></i> Find Stations
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
