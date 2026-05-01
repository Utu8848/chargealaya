<?php
$page_title = "Make Reservation";
require_once '../partials/header.php';
require_once '../partials/functions.php';
requireLogin();

if (!hasRole('ev_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Get station_id from URL
$station_id = isset($_GET['station_id']) ? intval($_GET['station_id']) : 0;

if ($station_id === 0) {
    setFlashMessage("Invalid station selected.", "danger");
    header("Location: /chargealaya/public/stations.php");
    exit();
}

// Get station details
$station_query = "SELECT cs.*, t.price_per_kwh, t.service_fee
                  FROM charging_stations cs
                  LEFT JOIN tariffs t ON cs.station_id = t.station_id
                  WHERE cs.station_id = $station_id";
$station_result = $conn->query($station_query);

if ($station_result->num_rows === 0) {
    setFlashMessage("Station not found.", "danger");
    header("Location: /chargealaya/public/stations.php");
    exit();
}

$station = $station_result->fetch_assoc();

// Set default values if tariff not found
if (!isset($station['price_per_kwh']) || $station['price_per_kwh'] === null) {
    $station['price_per_kwh'] = 15.00; // Default price
}
if (!isset($station['service_fee']) || $station['service_fee'] === null) {
    $station['service_fee'] = 50.00; // Default fee
}

// Check if station is online
if ($station['status'] !== 'online') {
    $status_text = $station['status'] === 'offline' ? 'offline' : 'under maintenance';
    setFlashMessage("This station is currently {$status_text}. Reservations are not available at this time.", "warning");
    header("Location: /chargealaya/public/station_detail.php?station_id=" . $station_id);
    exit();
}

// Get available chargers for this station
$chargers_query = "SELECT * FROM chargers 
                   WHERE station_id = $station_id 
                   AND status = 'available'
                   ORDER BY charger_type DESC, max_power_kw DESC";
$chargers = $conn->query($chargers_query);

if ($chargers->num_rows === 0) {
    setFlashMessage("No available chargers at this station.", "warning");
    header("Location: /chargealaya/public/stations.php");
    exit();
}

// Get user's vehicles
$vehicles_query = "SELECT * FROM vehicles WHERE user_id = $user_id";
$vehicles = $conn->query($vehicles_query);

if ($vehicles->num_rows === 0) {
    setFlashMessage("Please add a vehicle first before making a reservation.", "warning");
    header("Location: /chargealaya/user/my_vehicles.php");
    exit();
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Station Details Card -->
            <div class="card glass-card mb-4">
                <div class="card-body">
                    <h4 class="card-title"><i class="fas fa-charging-station text-success"></i> <?php echo htmlspecialchars($station['station_name']); ?></h4>
                    <p class="text-muted mb-3">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($station['address'] . ', ' . $station['city']); ?>
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Available Chargers:</strong> <?php echo $chargers->num_rows; ?></p>
                            <p><strong>Operating Hours:</strong> <?php echo htmlspecialchars($station['operating_hours']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Price:</strong> NPR <?php echo number_format($station['price_per_kwh'], 2); ?>/kWh</p>
                            <?php if ($station['service_fee'] > 0): ?>
                                <p><strong>Service Fee:</strong> NPR <?php echo number_format($station['service_fee'], 2); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservation Form -->
            <div class="card glass-card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-calendar-check text-primary"></i> Make Reservation
                    </h5>

                    <form action="process_reservation.php" method="POST" id="reservationForm">
                        <input type="hidden" name="station_id" value="<?php echo $station_id; ?>">
                        
                        <!-- Vehicle Selection -->
                        <div class="mb-3">
                            <label class="form-label">Select Vehicle <span class="text-danger">*</span></label>
                            <select name="vehicle_id" class="form-select" id="vehicleSelect" required>
                                <option value="">Choose your vehicle...</option>
                                <?php 
                                $vehicles->data_seek(0); // Reset pointer
                                while ($vehicle = $vehicles->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $vehicle['vehicle_id']; ?>" 
                                            data-connector="<?php echo htmlspecialchars($vehicle['connector_type']); ?>">
                                        <?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>
                                        (<?php echo htmlspecialchars($vehicle['license_plate']); ?>)
                                        - <?php echo htmlspecialchars($vehicle['connector_type']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Select your vehicle to see compatible chargers</small>
                        </div>

                        <!-- Charger Selection -->
                        <div class="mb-3">
                            <label class="form-label">Select Charger <span class="text-danger">*</span></label>
                            <select name="charger_id" class="form-select" id="chargerSelect" required disabled>
                                <option value="">First select your vehicle...</option>
                            </select>
                            <div id="chargerInfo" class="mt-2" style="display: none;">
                                <div class="alert alert-info mb-0">
                                    <small id="chargerDetails"></small>
                                </div>
                            </div>
                        </div>

                        <!-- Start Time -->
                        <div class="mb-3">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="start_time" id="startTimeInput" class="form-control" 
                                   min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>" required>
                            <small class="text-muted">Reservation must start at least 1 hour in the future</small>
                        </div>

                        <!-- Duration -->
                        <div class="mb-3">
                            <label class="form-label">Duration (hours) <span class="text-danger">*</span></label>
                            <select name="duration" class="form-select" id="durationSelect" required>
                                <option value="">Select duration...</option>
                                <option value="0.5">30 minutes</option>
                                <option value="1">1 hour</option>
                                <option value="1.5">1.5 hours</option>
                                <option value="2">2 hours</option>
                                <option value="3">3 hours</option>
                                <option value="4">4 hours</option>
                                <option value="5">5 hours</option>
                                <option value="6">6 hours</option>
                            </select>
                        </div>

                        <!-- Reservation Summary -->
                        <div class="card border-primary" id="reservationSummary" style="display: none;">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-file-invoice"></i> Reservation Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Station:</div>
                                    <div class="col-6 text-end"><strong><?php echo htmlspecialchars($station['station_name']); ?></strong></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Vehicle:</div>
                                    <div class="col-6 text-end" id="summaryVehicle"><small class="text-muted">Not selected</small></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Charger:</div>
                                    <div class="col-6 text-end" id="summaryCharger"><small class="text-muted">Not selected</small></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Start Time:</div>
                                    <div class="col-6 text-end" id="summaryStartTime"><small class="text-muted">Not selected</small></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Duration:</div>
                                    <div class="col-6 text-end" id="summaryDuration"><small class="text-muted">Not selected</small></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">End Time:</div>
                                    <div class="col-6 text-end" id="summaryEndTime"><small class="text-muted">-</small></div>
                                </div>
                                <hr>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Energy (estimated):</div>
                                    <div class="col-6 text-end" id="summaryEnergy"><small class="text-muted">-</small></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Energy Cost:</div>
                                    <div class="col-6 text-end" id="summaryEnergyCost">NPR 0.00</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Service Fee:</div>
                                    <div class="col-6 text-end" id="summaryServiceFee">NPR <?php echo number_format($station['service_fee'], 2); ?></div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6"><strong>Total Cost:</strong></div>
                                    <div class="col-6 text-end"><strong class="text-success" style="font-size: 1.2em;" id="summaryTotalCost">NPR 0.00</strong></div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Final cost may vary based on actual energy consumed
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info" id="costAlert" style="display: none;">
                            <h6><i class="fas fa-calculator"></i> Estimated Cost</h6>
                            <p class="mb-0">
                                <strong id="estimatedCost">NPR 0.00</strong>
                                <br>
                                <small class="text-muted" id="costBasis">Select charger and duration</small>
                            </p>
                        </div>

                        <!-- Important Notice -->
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-info-circle"></i> Important Information</h6>
                            <ul class="mb-0">
                                <li>Please ensure your vehicle's connector type matches the charger</li>
                                <li>Arrive on time - late arrivals may result in cancellation</li>
                                <li>You can cancel your reservation from "My Reservations"</li>
                                <li>Station owner may cancel reservations for maintenance</li>
                            </ul>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-center">
                            <a href="/chargealaya/public/station_detail.php?station_id=<?php echo $station_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <button type="submit" class="btn btn-custom">
                                <i class="fas fa-check"></i> Create Reservation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Declare global variables for use throughout page
<?php $chargers->data_seek(0); // Reset to beginning ?>
const allChargers = <?php echo json_encode($chargers->fetch_all(MYSQLI_ASSOC)); ?>;
const pricePerKwh = <?php echo floatval($station['price_per_kwh']); ?>;
const serviceFee = <?php echo floatval($station['service_fee']); ?>;
</script>

<script src="/chargealaya/assets/js/scripts/make_reservation.js"></script>

<!-- Validation Modal -->
<div class="modal fade" id="validationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-exclamation-circle"></i> Invalid Reservation Time</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="validationMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="fas fa-check"></i> OK, Got It
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
