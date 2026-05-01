<?php
$page_title = "My Vehicles";
include '../partials/header.php';
requireLogin();

if (!hasRole('ev_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $vehicle_id = intval($_GET['delete']);
    
    // Check if vehicle belongs to user
    $check_stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE vehicle_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $vehicle_id, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
        $delete_stmt->bind_param("i", $vehicle_id);
        
        if ($delete_stmt->execute()) {
            setFlashMessage("Vehicle deleted successfully!", "success");
        } else {
            setFlashMessage("Failed to delete vehicle.", "danger");
        }
        $delete_stmt->close();
    }
    $check_stmt->close();
    
    header("Location: vehicles.php");
    exit();
}

// Get user vehicles
$query = "SELECT * FROM vehicles WHERE user_id = $user_id ORDER BY vehicle_id DESC";
$vehicles = $conn->query($query);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-car text-success"></i> My Vehicles</h2>
            <p class="text-muted">Manage your electric vehicles</p>
        </div>
        <a href="add_vehicle.php" class="btn btn-custom">
            <i class="fas fa-plus"></i> Add New Vehicle
        </a>
    </div>
    
    <?php if ($vehicles->num_rows > 0): ?>
        <div class="row">
            <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h4 class="mb-1"><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></h4>
                                    <span class="badge bg-primary"><?php echo $vehicle['manufacturing_year']; ?></span>
                                </div>
                                <i class="fas fa-car fa-3x text-success"></i>
                            </div>
                            
                            <div class="mb-2">
                                <strong><i class="fas fa-battery-full text-warning"></i> Battery Capacity:</strong>
                                <?php echo $vehicle['battery_capacity_kwh']; ?> kWh
                            </div>
                            
                            <div class="mb-2">
                                <strong><i class="fas fa-plug text-info"></i> Connector Type:</strong>
                                <?php echo htmlspecialchars($vehicle['connector_type']); ?>
                            </div>
                            
                            <div class="mb-2">
                                <strong><i class="fas fa-id-card text-secondary"></i> License Plate:</strong>
                                <?php echo htmlspecialchars($vehicle['license_plate']); ?>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex gap-2">
                                <a href="edit_vehicle.php?id=<?php echo $vehicle['vehicle_id']; ?>" class="btn btn-sm btn-custom">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="vehicles.php?delete=<?php echo $vehicle['vehicle_id']; ?>" 
                                   class="btn btn-sm btn-danger-custom"
                                   onclick="return confirm('Are you sure you want to delete this vehicle?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-car fa-5x text-muted mb-3"></i>
                <h4>No Vehicles Added Yet</h4>
                <p class="text-muted">Add your first electric vehicle to start using our charging stations.</p>
                <a href="add_vehicle.php" class="btn btn-custom btn-lg">
                    <i class="fas fa-plus"></i> Add Your First Vehicle
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
