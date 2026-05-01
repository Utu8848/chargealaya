<?php
$page_title = "Report Issue";
include '../partials/header.php';
requireLogin();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Get available chargers from stations
$chargers_query = "SELECT c.charger_id, c.charger_type, c.max_power_kw, c.connector_type,
                   cs.station_name, cs.city
                   FROM chargers c
                   JOIN charging_stations cs ON c.station_id = cs.station_id
                   ORDER BY cs.station_name, c.charger_id";
$chargers = $conn->query($chargers_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $charger_id = intval($_POST['charger_id']);
    $issue_description = sanitize($_POST['issue_description']);
    
    $errors = [];
    
    if ($charger_id === 0) {
        $errors[] = "Please select a charger.";
    }
    
    if (empty($issue_description)) {
        $errors[] = "Please describe the issue.";
    } elseif (strlen($issue_description) < 10) {
        $errors[] = "Issue description must be at least 10 characters.";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO maintenance (charger_id, reported_by, issue_description, status) VALUES (?, ?, ?, 'open')");
        $stmt->bind_param("iis", $charger_id, $user_id, $issue_description);
        
        if ($stmt->execute()) {
            // Update charger status to maintenance
            $update_charger = $conn->prepare("UPDATE chargers SET status = 'maintenance' WHERE charger_id = ?");
            $update_charger->bind_param("i", $charger_id);
            $update_charger->execute();
            $update_charger->close();
            
            setFlashMessage("Issue reported successfully! The station owner has been notified.", "success");
            header("Location: my_reports.php");
            exit();
        } else {
            $errors[] = "Failed to report issue. Please try again.";
        }
        $stmt->close();
    }
    
    foreach ($errors as $error) {
        setFlashMessage($error, "danger");
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h3><i class="fas fa-exclamation-triangle"></i> Report Charger Issue</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Report any issues you encounter with charging stations. 
                        The station owner will be notified and work to resolve it.
                    </div>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="charger_id" class="form-label">Select Charger *</label>
                            <select class="form-control" id="charger_id" name="charger_id" required>
                                <option value="">Choose a charger...</option>
                                <?php while ($charger = $chargers->fetch_assoc()): ?>
                                    <option value="<?php echo $charger['charger_id']; ?>">
                                        <?php echo htmlspecialchars($charger['station_name']); ?> - 
                                        <?php echo ucfirst($charger['charger_type']); ?> Charger 
                                        (<?php echo $charger['max_power_kw']; ?> kW, 
                                        <?php echo htmlspecialchars($charger['connector_type']); ?>) - 
                                        <?php echo htmlspecialchars($charger['city']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="issue_description" class="form-label">Issue Description *</label>
                            <textarea class="form-control" id="issue_description" name="issue_description" 
                                      rows="6" required placeholder="Please describe the issue in detail...
Example: Charger not starting, display not working, connector damaged, etc."><?php echo isset($_POST['issue_description']) ? htmlspecialchars($_POST['issue_description']) : ''; ?></textarea>
                            <small class="text-muted">Minimum 10 characters</small>
                        </div>
                        
                        <div class="alert alert-warning">
                            <strong><i class="fas fa-lightbulb"></i> Tips for reporting:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Be specific about what's not working</li>
                                <li>Mention any error messages you see</li>
                                <li>Note the time when the issue occurred</li>
                                <li>Include any unusual sounds or behaviors</li>
                            </ul>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-paper-plane"></i> Report Issue
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
