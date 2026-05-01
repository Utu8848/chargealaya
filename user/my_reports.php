<?php
$page_title = "My Reports";
include '../partials/header.php';
requireLogin();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Get user's reported issues
$query = "SELECT m.*, c.charger_type, c.connector_type, cs.station_name, cs.city,
          reporter.first_name as reporter_first, reporter.last_name as reporter_last
          FROM maintenance m
          JOIN chargers c ON m.charger_id = c.charger_id
          JOIN charging_stations cs ON c.station_id = cs.station_id
          JOIN users reporter ON m.reported_by = reporter.user_id
          WHERE m.reported_by = $user_id
          ORDER BY m.reported_date DESC";
$reports = $conn->query($query);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-clipboard-list text-warning"></i> My Issue Reports</h2>
            <p class="text-muted">Track the status of issues you've reported</p>
        </div>
        <a href="report_issue.php" class="btn btn-warning">
            <i class="fas fa-plus"></i> Report New Issue
        </a>
    </div>
    
    <?php if ($reports->num_rows > 0): ?>
        <div class="row">
            <?php while ($report = $reports->fetch_assoc()): ?>
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-9">
                                    <h5>
                                        <i class="fas fa-charging-station text-primary"></i>
                                        <?php echo htmlspecialchars($report['station_name']); ?>
                                    </h5>
                                    <p class="text-muted mb-2">
                                        <?php echo ucfirst($report['charger_type']); ?> Charger - 
                                        <?php echo htmlspecialchars($report['connector_type']); ?> - 
                                        <?php echo htmlspecialchars($report['city']); ?>
                                    </p>
                                    
                                    <div class="mb-2">
                                        <strong>Issue Description:</strong><br>
                                        <p class="mt-1"><?php echo nl2br(htmlspecialchars($report['issue_description'])); ?></p>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <small class="text-muted">Reported Date</small><br>
                                            <strong><?php echo formatDateTime($report['reported_date']); ?></strong>
                                        </div>
                                        <?php if ($report['fixed_date']): ?>
                                            <div class="col-md-4">
                                                <small class="text-muted">Fixed Date</small><br>
                                                <strong><?php echo formatDateTime($report['fixed_date']); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 text-end">
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    switch($report['status']) {
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
                                    <span class="badge <?php echo $status_class; ?> mb-3">
                                        <i class="fas fa-<?php echo $status_icon; ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $report['status'])); ?>
                                    </span>
                                    
                                    <div class="small text-muted">
                                        Report #<?php echo $report['maintenance_id']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-clipboard fa-5x text-muted mb-3"></i>
                <h4>No Issues Reported</h4>
                <p class="text-muted">You haven't reported any issues yet.</p>
                <a href="report_issue.php" class="btn btn-warning btn-lg">
                    <i class="fas fa-exclamation-triangle"></i> Report an Issue
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
