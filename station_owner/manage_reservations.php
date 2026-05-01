<?php
$page_title = "Manage Reservations";
include '../partials/header.php';
requireStationOwner();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Get all reservations for this owner's stations
$query = "SELECT r.*,
          CONCAT(u.first_name, ' ', u.last_name) as customer_name,
          u.email as customer_email,
          u.phone as customer_phone,
          c.charger_type,
          c.max_power_kw,
          cs.station_id,
          cs.station_name,
          cs.address,
          cs.city,
          CONCAT(v.brand, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info
          FROM reservations r
          JOIN users u ON r.user_id = u.user_id
          LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
          JOIN chargers c ON r.charger_id = c.charger_id
          JOIN charging_stations cs ON c.station_id = cs.station_id
          WHERE cs.user_id = $user_id
          AND r.status IN ('confirmed', 'cancelled', 'completed')
          AND r.end_time > DATE_SUB(NOW(), INTERVAL 30 DAY)
          ORDER BY r.start_time DESC";
$result = $conn->query($query);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-calendar-alt text-primary"></i> Manage Reservations</h2>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if ($result->num_rows === 0): ?>
                <div class="card glass-card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h4>No Active Reservations</h4>
                        <p class="text-muted">There are no upcoming reservations at your stations.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card glass-card">
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> You can cancel any reservation at your stations. The customer will be notified.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Station</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($reservation = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($reservation['customer_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($reservation['customer_email']); ?>
                                                    <?php if ($reservation['customer_phone']): ?>
                                                        <br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($reservation['customer_phone']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($reservation['vehicle_info']): ?>
                                                    <i class="fas fa-car"></i> <?php echo htmlspecialchars($reservation['vehicle_info']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($reservation['station_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($reservation['charger_type']); ?> - <?php echo $reservation['max_power_kw']; ?>kW
                                                </small>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($reservation['start_time'])); ?></small><br>
                                                <strong><?php echo date('g:i A', strtotime($reservation['start_time'])); ?></strong>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($reservation['end_time'])); ?></small><br>
                                                <strong><?php echo date('g:i A', strtotime($reservation['end_time'])); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($reservation['status'] === 'confirmed'): ?>
                                                    <span class="badge bg-success">Confirmed</span>
                                                <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                                    <span class="badge bg-danger">Cancelled</span>
                                                <?php elseif ($reservation['status'] === 'completed'): ?>
                                                    <span class="badge bg-primary">Completed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($reservation['status'] === 'confirmed'): ?>
                                                    <div class="d-grid gap-2" style="min-width: 150px;">
                                                        <button class="btn btn-sm btn-danger" style="white-space: nowrap;"
                                                                onclick="cancelReservation(<?php echo $reservation['reservation_id']; ?>, '<?php echo addslashes(htmlspecialchars($reservation['customer_name'])); ?>')">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                        <button class="btn btn-sm btn-primary" style="white-space: nowrap;"
                                                                onclick="completeReservation(<?php echo $reservation['reservation_id']; ?>, '<?php echo addslashes(htmlspecialchars($reservation['customer_name'])); ?>', '<?php echo $reservation['end_time']; ?>')">
                                                            <i class="fas fa-check"></i> Complete
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Cancel Reservation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this reservation?</p>
                <div class="alert alert-warning">
                    <strong>Customer:</strong> <span id="modalCustomerName"></span><br>
                    <strong>Reservation ID:</strong> #<span id="modalReservationId"></span>
                </div>
                <p class="text-danger">
                    <i class="fas fa-info-circle"></i> 
                    The customer will be notified of this cancellation.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> No, Keep It
                </button>
                <form id="cancelForm" method="POST" action="cancel_reservation.php" style="display: inline;">
                    <input type="hidden" name="reservation_id" id="formReservationId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-check"></i> Yes, Cancel Reservation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Complete Confirmation Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-check-circle"></i> Complete Reservation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark this reservation as completed?</p>
                <div class="alert alert-info">
                    <strong>Customer:</strong> <span id="modalCompleteCustomerName"></span><br>
                    <strong>Reservation ID:</strong> #<span id="modalCompleteReservationId"></span>
                </div>
                <p class="text-primary">
                    <i class="fas fa-info-circle"></i> 
                    This will create a charging session and payment record for the customer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> No, Cancel
                </button>
                <form id="completeForm" method="POST" action="complete_reservation.php" style="display: inline;">
                    <input type="hidden" name="reservation_id" id="formCompleteReservationId">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Yes, Mark as Completed
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- End Time Not Passed Modal -->
<div class="modal fade" id="endTimeNotPassedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-clock"></i> Cannot Complete Yet</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="endTimeMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="fas fa-check"></i> OK, Got It
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/chargealaya/assets/js/scripts/manage_reservations.js"></script>

<?php
$conn->close();
include '../partials/footer.php';
?>
