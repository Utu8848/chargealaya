<?php
$page_title = "Maintenance Management";
include '../partials/header.php';
requireStationOwner();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Handle status update
if (isset($_POST['update_status'])) {
    $maintenance_id = intval($_POST['maintenance_id']);
    $new_status = sanitize($_POST['new_status']);
    $charger_id = intval($_POST['charger_id']);
    
    $update_stmt = $conn->prepare("UPDATE maintenance SET status = ?, fixed_date = ? WHERE maintenance_id = ?");
    $fixed_date = ($new_status === 'resolved') ? date('Y-m-d H:i:s') : null;
    $update_stmt->bind_param("ssi", $new_status, $fixed_date, $maintenance_id);
    
    if ($update_stmt->execute()) {
        // Update charger status
        if ($new_status === 'resolved') {
            $charger_update = $conn->prepare("UPDATE chargers SET status = 'available' WHERE charger_id = ?");
        } else {
            $charger_update = $conn->prepare("UPDATE chargers SET status = 'maintenance' WHERE charger_id = ?");
        }
        $charger_update->bind_param("i", $charger_id);
        $charger_update->execute();
        $charger_update->close();
        
        setFlashMessage("Maintenance status updated successfully!", "success");
    }
    $update_stmt->close();
    header("Location: maintenance.php");
    exit();
}

// Get maintenance issues for owner's stations
$query = "SELECT m.*, c.charger_type, c.max_power_kw, c.connector_type, c.status as charger_status,
          cs.station_name, cs.city,
          reporter.first_name as reporter_first, reporter.last_name as reporter_last, reporter.email as reporter_email, reporter.phone as reporter_phone
          FROM maintenance m
          JOIN chargers c ON m.charger_id = c.charger_id
          JOIN charging_stations cs ON c.station_id = cs.station_id
          JOIN users reporter ON m.reported_by = reporter.user_id
          WHERE cs.user_id = $user_id
          ORDER BY 
            CASE m.status
              WHEN 'open' THEN 1
              WHEN 'in_progress' THEN 2
              WHEN 'resolved' THEN 3
            END,
            m.reported_date DESC";
$maintenance_issues = $conn->query($query);

// Get statistics
$open_count = $conn->query("SELECT COUNT(*) as count FROM maintenance m
                            JOIN chargers c ON m.charger_id = c.charger_id
                            JOIN charging_stations cs ON c.station_id = cs.station_id
                            WHERE cs.user_id = $user_id AND m.status = 'open'")->fetch_assoc()['count'];

$in_progress_count = $conn->query("SELECT COUNT(*) as count FROM maintenance m
                                   JOIN chargers c ON m.charger_id = c.charger_id
                                   JOIN charging_stations cs ON c.station_id = cs.station_id
                                   WHERE cs.user_id = $user_id AND m.status = 'in_progress'")->fetch_assoc()['count'];

$resolved_count = $conn->query("SELECT COUNT(*) as count FROM maintenance m
                                JOIN chargers c ON m.charger_id = c.charger_id
                                JOIN charging_stations cs ON c.station_id = cs.station_id
                                WHERE cs.user_id = $user_id AND m.status = 'resolved'")->fetch_assoc()['count'];
?>

<div class="container my-5">
    <h2><i class="fas fa-tools text-warning"></i> Maintenance Management</h2>
    <p class="text-muted">Manage maintenance issues for your charging stations</p>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h4><?php echo $open_count; ?></h4>
                    <p class="mb-0"><i class="fas fa-exclamation-circle"></i> Open Issues</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h4><?php echo $in_progress_count; ?></h4>
                    <p class="mb-0"><i class="fas fa-wrench"></i> In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h4><?php echo $resolved_count; ?></h4>
                    <p class="mb-0"><i class="fas fa-check-circle"></i> Resolved</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Maintenance Issues -->
    <?php if ($maintenance_issues->num_rows > 0): ?>
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> All Maintenance Issues</h5>
            </div>
            <div class="card-body">
                <?php while ($issue = $maintenance_issues->fetch_assoc()): ?>
                    <div class="card mb-3 <?php echo $issue['status'] === 'resolved' ? 'border-success' : ($issue['status'] === 'in_progress' ? 'border-warning' : 'border-danger'); ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between">
                                        <h5>
                                            <i class="fas fa-charging-station text-primary"></i>
                                            <?php echo htmlspecialchars($issue['station_name']); ?>
                                        </h5>
                                        <?php
                                        $status_class = '';
                                        $status_icon = '';
                                        switch($issue['status']) {
                                            case 'open':
                                                $status_class = 'bg-danger';
                                                $status_icon = 'exclamation-circle';
                                                break;
                                            case 'in_progress':
                                                $status_class = 'bg-warning';
                                                $status_icon = 'wrench';
                                                break;
                                            case 'resolved':
                                                $status_class = 'bg-success';
                                                $status_icon = 'check-circle';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <i class="fas fa-<?php echo $status_icon; ?>"></i>
                                            <?php echo ucwords(str_replace('_', ' ', $issue['status'])); ?>
                                        </span>
                                    </div>
                                    
                                    <p class="text-muted mb-2">
                                        <strong>Charger:</strong> 
                                        <?php echo ucfirst($issue['charger_type']); ?> - 
                                        <?php echo $issue['max_power_kw']; ?> kW - 
                                        <?php echo htmlspecialchars($issue['connector_type']); ?>
                                    </p>
                                    
                                    <div class="alert alert-light mb-2">
                                        <strong><i class="fas fa-exclamation-triangle text-warning"></i> Issue Description:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($issue['issue_description'])); ?>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Reported By:</small><br>
                                            <strong><?php echo htmlspecialchars($issue['reporter_first'] . ' ' . $issue['reporter_last']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($issue['reporter_email']); ?></small><br>
                                            <small><?php echo htmlspecialchars($issue['reporter_phone']); ?></small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Reported Date:</small><br>
                                            <strong><?php echo formatDateTime($issue['reported_date']); ?></strong>
                                            <?php if ($issue['fixed_date']): ?>
                                                <br><small class="text-muted">Fixed Date:</small><br>
                                                <strong><?php echo formatDateTime($issue['fixed_date']); ?></strong>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <form method="POST" action="">
                                        <input type="hidden" name="maintenance_id" value="<?php echo $issue['maintenance_id']; ?>">
                                        <input type="hidden" name="charger_id" value="<?php echo $issue['charger_id']; ?>">
                                        
                                        <label class="form-label">Update Status</label>
                                        <select class="form-control mb-2" name="new_status" required>
                                            <option value="open" <?php echo $issue['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                            <option value="in_progress" <?php echo $issue['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="resolved" <?php echo $issue['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        </select>
                                        
                                        <button type="submit" name="update_status" class="btn btn-sm btn-custom w-100">
                                            <i class="fas fa-sync"></i> Update Status
                                        </button>
                                        
                                        <div class="small text-muted mt-2">
                                            Issue #<?php echo $issue['maintenance_id']; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                <h4>No Maintenance Issues</h4>
                <p class="text-muted">Great! All your chargers are working fine.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
