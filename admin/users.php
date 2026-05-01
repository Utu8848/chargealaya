<?php
$page_title = "Manage Users";
include '../partials/header.php';
requireAdmin();

$db = new Database();
$conn = $db->connect();

// Handle block/unblock
if (isset($_GET['toggle_status'])) {
    $user_id = intval($_GET['toggle_status']);
    $new_status = ($_GET['current_status'] === 'active') ? 'blocked' : 'active';
    
    $update_stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $update_stmt->bind_param("si", $new_status, $user_id);
    
    if ($update_stmt->execute()) {
        setFlashMessage("User status updated successfully!", "success");
    }
    $update_stmt->close();
    header("Location: users.php");
    exit();
}

// Handle delete
if (isset($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    
    if ($user_id == $_SESSION['user_id']) {
        setFlashMessage("You cannot delete your own account!", "danger");
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            setFlashMessage("User deleted successfully!", "success");
        }
        $delete_stmt->close();
    }
    header("Location: users.php");
    exit();
}

// Get filter
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
if ($role_filter) {
    $query .= " AND role = '" . $conn->real_escape_string($role_filter) . "'";
}
if ($status_filter) {
    $query .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query .= " ORDER BY created_at DESC";

$users = $conn->query($query);

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'ev_owner'")->fetch_assoc()['count'];
$total_owners = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'station_owner'")->fetch_assoc()['count'];
$blocked_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'blocked'")->fetch_assoc()['count'];
?>

<div class="container my-5">
    <h2><i class="fas fa-users text-success"></i> Manage Users</h2>
    <p class="text-muted">View and manage all system users</p>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h4><?php echo $total_users; ?></h4>
                    <p class="mb-0"><i class="fas fa-user"></i> Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h4><?php echo $total_owners; ?></h4>
                    <p class="mb-0"><i class="fas fa-store"></i> Station Owners</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h4><?php echo $blocked_users; ?></h4>
                    <p class="mb-0"><i class="fas fa-ban"></i> Blocked Users</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="role" class="form-label">Filter by Role</label>
                    <select class="form-control" id="role" name="role">
                        <option value="">All Roles</option>
                        <option value="ev_owner" <?php echo $role_filter === 'ev_owner' ? 'selected' : ''; ?>>EV Owner</option>
                        <option value="station_owner" <?php echo $role_filter === 'station_owner' ? 'selected' : ''; ?>>Station Owner</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-custom w-100">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> All Users</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $user['user_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td>
                                    <?php
                                    $role_badge = '';
                                    switch($user['role']) {
                                        case 'admin': $role_badge = 'bg-dark'; break;
                                        case 'station_owner': $role_badge = 'bg-info'; break;
                                        case 'ev_owner': $role_badge = 'bg-primary'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $role_badge; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <div class="d-grid gap-2" style="min-width: 150px;">
                                            <a href="?toggle_status=<?php echo $user['user_id']; ?>&current_status=<?php echo $user['status']; ?>" 
                                               class="btn btn-sm <?php echo $user['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>">
                                                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                                <?php echo $user['status'] === 'active' ? 'Block' : 'Unblock'; ?>
                                            </a>
                                            <a href="?delete_user=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-danger-custom"
                                               onclick="return confirm('Delete this user permanently?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
