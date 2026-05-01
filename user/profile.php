<?php
$page_title = "My Profile";
include '../partials/header.php';
requireLogin();

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $phone = sanitize($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate inputs
    if (empty($first_name) || empty($last_name)) {
        $errors[] = "First name and last name are required.";
    }
    
    // If changing password
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Please enter your current password.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        }
    }
    
    if (empty($errors)) {
        // Verify current password if changing password
        if (!empty($new_password)) {
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!password_verify($current_password, $user['password_hash'])) {
                $errors[] = "Current password is incorrect.";
            }
            $stmt->close();
        }
        
        if (empty($errors)) {
            // Update profile
            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("ssssi", $first_name, $last_name, $phone, $password_hash, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['name'] = $first_name . ' ' . $last_name;
                setFlashMessage("Profile updated successfully!", "success");
                header("Location: profile.php");
                exit();
            } else {
                $errors[] = "Failed to update profile.";
            }
            $stmt->close();
        }
    }
    
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setFlashMessage($error, "danger");
        }
    }
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-circle"></i> My Profile</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <h5 class="mb-3">Personal Information</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Account Type</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo ucwords(str_replace('_', ' ', $user['role'])); ?>" disabled>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Change Password (Optional)</h5>
                        <p class="text-muted">Leave blank if you don't want to change your password</p>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo hasRole('admin') ? '/chargealaya/admin/dashboard.php' : 
                                               (hasRole('station_owner') ? '/chargealaya/station_owner/dashboard.php' : 
                                                'dashboard.php'); ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-custom">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Account Info -->
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Account Information
                </div>
                <div class="card-body">
                    <p><strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?></p>
                    <p><strong>Account Status:</strong> 
                        <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../partials/footer.php';
?>
