<?php
$page_title = "Station Details";
include '../partials/header.php';

$db = new Database();
$conn = $db->connect();

$station_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($station_id === 0) {
    header("Location: stations.php");
    exit();
}

// Get station details
$station_query = "SELECT cs.*, u.first_name, u.last_name, u.phone, u.email,
                  AVG(f.rating) as avg_rating,
                  COUNT(DISTINCT f.feedback_id) as review_count
                  FROM charging_stations cs
                  LEFT JOIN users u ON cs.user_id = u.user_id
                  LEFT JOIN feedback f ON cs.station_id = f.station_id
                  WHERE cs.station_id = $station_id
                  GROUP BY cs.station_id";
$station_result = $conn->query($station_query);

if ($station_result->num_rows === 0) {
    header("Location: stations.php");
    exit();
}

$station = $station_result->fetch_assoc();

// Get chargers
$chargers_query = "SELECT * FROM chargers WHERE station_id = $station_id";
$chargers = $conn->query($chargers_query);

// Get tariff
$tariff_query = "SELECT * FROM tariffs WHERE station_id = $station_id LIMIT 1";
$tariff_result = $conn->query($tariff_query);
$tariff = $tariff_result->fetch_assoc();

// Get reviews
$reviews_query = "SELECT f.*, u.first_name, u.last_name, u.email
                  FROM feedback f
                  JOIN users u ON f.user_id = u.user_id
                  WHERE f.station_id = $station_id
                  ORDER BY f.created_at DESC
                  LIMIT 10";
$reviews = $conn->query($reviews_query);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <!-- Station Info Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($station['station_name']); ?></h3>
                    <?php
                    $status_class = '';
                    switch($station['status']) {
                        case 'online': $status_class = 'badge-online'; break;
                        case 'offline': $status_class = 'badge-offline'; break;
                        case 'under_maintenance': $status_class = 'badge-maintenance'; break;
                    }
                    ?>
                    <span class="<?php echo $status_class; ?>"><?php echo ucwords(str_replace('_', ' ', $station['status'])); ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5><i class="fas fa-map-marker-alt text-success"></i> Location</h5>
                        <p><?php echo htmlspecialchars($station['address']); ?><br>
                           <?php echo htmlspecialchars($station['city'] . ', ' . $station['province']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h5><i class="fas fa-clock text-warning"></i> Operating Hours</h5>
                        <p><?php echo htmlspecialchars($station['operating_hours']); ?></p>
                    </div>
                    
                    <?php if ($station['avg_rating']): ?>
                        <div class="mb-3">
                            <h5><i class="fas fa-star text-warning"></i> Rating</h5>
                            <div class="rating">
                                <?php
                                $rating = round($station['avg_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                                ?>
                                <span class="ms-2"><?php echo number_format($station['avg_rating'], 1); ?> 
                                (<?php echo $station['review_count']; ?> reviews)</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <h5><i class="fas fa-user text-info"></i> Station Owner</h5>
                        <p><?php echo htmlspecialchars($station['first_name'] . ' ' . $station['last_name']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Available Chargers -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-plug"></i> Available Chargers
                </div>
                <div class="card-body">
                    <?php if ($chargers->num_rows > 0): ?>
                        <div class="row">
                            <?php while ($charger = $chargers->fetch_assoc()): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="charger-item">
                                        <div>
                                            <strong><?php echo ucfirst($charger['charger_type']); ?> Charger</strong><br>
                                            <small class="text-muted">
                                                <?php echo $charger['max_power_kw']; ?> kW | 
                                                <?php echo htmlspecialchars($charger['connector_type']); ?>
                                            </small>
                                        </div>
                                        <?php
                                        $charger_status_class = '';
                                        switch($charger['status']) {
                                            case 'available': $charger_status_class = 'badge-available'; break;
                                            case 'in_use': $charger_status_class = 'badge-in-use'; break;
                                            case 'maintenance': $charger_status_class = 'badge-maintenance'; break;
                                        }
                                        ?>
                                        <span class="<?php echo $charger_status_class; ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $charger['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No chargers available at this station.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reviews -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-comments"></i> Customer Reviews
                </div>
                <div class="card-body">
                    <?php if ($reviews->num_rows > 0): ?>
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></strong>
                                    <div class="rating">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo formatDateTime($review['created_at']); ?></small>
                                <p class="mt-2 mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted">No reviews yet. Be the first to review!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Pricing -->
            <?php if ($tariff): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-dollar-sign"></i> Pricing
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Price per kWh:</strong>
                            <span class="float-end"><?php echo formatCurrency($tariff['price_per_kwh']); ?></span>
                        </div>
                        <div class="mb-2">
                            <strong>Service Fee:</strong>
                            <span class="float-end"><?php echo formatCurrency($tariff['service_fee']); ?></span>
                        </div>
                        <?php if ($tariff['peak_start_time']): ?>
                            <hr>
                            <small class="text-muted">
                                Peak Hours: <?php echo date('g:i A', strtotime($tariff['peak_start_time'])); ?> - 
                                <?php echo date('g:i A', strtotime($tariff['peak_end_time'])); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <?php if (isLoggedIn() && hasRole('ev_owner')): ?>
                        <?php if ($station['status'] === 'online'): ?>
                            <a href="../user/make_reservation.php?station_id=<?php echo $station_id; ?>" class="btn btn-custom w-100 mb-2">
                                <i class="fas fa-calendar-plus"></i> Make Reservation
                            </a>
                        <?php else: ?>
                            <button class="btn btn-custom w-100 mb-2" onclick="showStationOfflineModal('<?php echo $station['status']; ?>')">
                                <i class="fas fa-calendar-plus"></i> Make Reservation
                            </button>
                        <?php endif; ?>
                        <a href="../user/leave_feedback.php?station_id=<?php echo $station_id; ?>" class="btn btn-secondary-custom w-100 mb-2">
                            <i class="fas fa-star"></i> Leave Review
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-custom w-100 mb-2">
                            <i class="fas fa-sign-in-alt"></i> Login to Reserve
                        </a>
                    <?php endif; ?>
                    <a href="stations.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left"></i> Back to Stations
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Station Offline/Maintenance Modal -->
<div class="modal fade" id="stationOfflineModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Station Unavailable</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="stationOfflineMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/chargealaya/assets/js/scripts/station_detail.js"></script>

<?php
$conn->close();
include '../partials/footer.php';
?>
