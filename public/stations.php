<?php
$page_title = "Charging Stations";
include '../partials/header.php';

$db = new Database();
$conn = $db->connect();

// Get filter parameters
$city_filter = isset($_GET['city']) ? sanitize($_GET['city']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT cs.*, u.first_name, u.last_name, 
          COALESCE(charger_stats.total_chargers, 0) as total_chargers,
          COALESCE(charger_stats.available_chargers, 0) as available_chargers,
          COALESCE(feedback_stats.avg_rating, 0) as avg_rating,
          COALESCE(feedback_stats.review_count, 0) as review_count
          FROM charging_stations cs 
          LEFT JOIN users u ON cs.user_id = u.user_id
          LEFT JOIN (
              SELECT station_id, 
                     COUNT(*) as total_chargers,
                     SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_chargers
              FROM chargers
              GROUP BY station_id
          ) charger_stats ON cs.station_id = charger_stats.station_id
          LEFT JOIN (
              SELECT station_id,
                     AVG(rating) as avg_rating,
                     COUNT(*) as review_count
              FROM feedback
              GROUP BY station_id
          ) feedback_stats ON cs.station_id = feedback_stats.station_id
          WHERE 1=1";

if ($city_filter) {
    $query .= " AND cs.city = '" . $conn->real_escape_string($city_filter) . "'";
}

if ($status_filter) {
    $query .= " AND cs.status = '" . $conn->real_escape_string($status_filter) . "'";
}

if ($search) {
    $query .= " AND (cs.station_name LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR cs.address LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$query .= " GROUP BY cs.station_id ORDER BY cs.station_name ASC";

$result = $conn->query($query);

// Get unique cities for filter
$cities_query = "SELECT DISTINCT city FROM charging_stations ORDER BY city";
$cities_result = $conn->query($cities_query);
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-map-marker-alt text-success"></i> Find Charging Stations</h2>
            <p class="text-muted">Browse and find the nearest EV charging station</p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Station name or address" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="city" class="form-label">City</label>
                    <select class="form-control" id="city" name="city">
                        <option value="">All Cities</option>
                        <?php while ($city = $cities_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($city['city']); ?>"
                                    <?php echo $city_filter === $city['city'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city['city']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="online" <?php echo $status_filter === 'online' ? 'selected' : ''; ?>>Online</option>
                        <option value="offline" <?php echo $status_filter === 'offline' ? 'selected' : ''; ?>>Offline</option>
                        <option value="under_maintenance" <?php echo $status_filter === 'under_maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-custom w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Results -->
    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($station = $result->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="station-card" data-city="<?php echo htmlspecialchars($station['city']); ?>" 
                         data-status="<?php echo htmlspecialchars($station['status']); ?>">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h4 class="mb-0"><?php echo htmlspecialchars($station['station_name']); ?></h4>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch($station['status']) {
                                case 'online':
                                    $status_class = 'badge-online';
                                    $status_text = 'Online';
                                    break;
                                case 'offline':
                                    $status_class = 'badge-offline';
                                    $status_text = 'Offline';
                                    break;
                                case 'under_maintenance':
                                    $status_class = 'badge-maintenance';
                                    $status_text = 'Maintenance';
                                    break;
                            }
                            ?>
                            <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                        
                        <div class="station-info">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($station['address']); ?></span>
                        </div>
                        
                        <div class="station-info">
                            <i class="fas fa-city"></i>
                            <span><?php echo htmlspecialchars($station['city'] . ', ' . $station['province']); ?></span>
                        </div>
                        
                        <div class="station-info">
                            <i class="fas fa-clock"></i>
                            <span><?php echo htmlspecialchars($station['operating_hours']); ?></span>
                        </div>
                        
                        <div class="station-info">
                            <i class="fas fa-plug"></i>
                            <span><strong><?php echo $station['available_chargers'] ? $station['available_chargers'] : 0; ?></strong> / <?php echo $station['total_chargers']; ?> chargers available</span>
                        </div>
                        
                        <?php if ($station['avg_rating']): ?>
                            <div class="rating mt-2">
                                <?php
                                $rating = round($station['avg_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                                ?>
                                <span class="text-muted ms-2">
                                    <?php echo number_format($station['avg_rating'], 1); ?> 
                                    (<?php echo $station['review_count']; ?> reviews)
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="station_detail.php?id=<?php echo $station['station_id']; ?>" class="btn btn-custom btn-sm">
                                <i class="fas fa-info-circle"></i> View Details
                            </a>
                            <?php if (isLoggedIn() && hasRole('ev_owner')): ?>
                                <?php if ($station['status'] === 'online'): ?>
                                    <a href="../user/make_reservation.php?station_id=<?php echo $station['station_id']; ?>" class="btn btn-secondary-custom btn-sm">
                                        <i class="fas fa-calendar-plus"></i> Reserve
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary-custom btn-sm" onclick="showStationOfflineModal('<?php echo $station['status']; ?>', '<?php echo htmlspecialchars($station['station_name']); ?>')">
                                        <i class="fas fa-calendar-plus"></i> Reserve
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> No charging stations found matching your criteria.
                </div>
            </div>
        <?php endif; ?>
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
                <p id="stationOfflineMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/chargealaya/assets/js/scripts/stations.js"></script>

<?php
$conn->close();
include '../partials/footer.php';
?>
