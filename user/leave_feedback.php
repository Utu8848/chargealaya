<?php
$page_title = "Leave Feedback";
include '../partials/header.php';
requireLogin();

if (!hasRole('ev_owner')) {
    header("Location: /chargealaya/index.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

$station_id = isset($_GET['station_id']) ? intval($_GET['station_id']) : 0;

if ($station_id === 0) {
    header("Location: /chargealaya/public/stations.php");
    exit();
}

// Get station details
$station_query = "SELECT * FROM charging_stations WHERE station_id = $station_id";
$station_result = $conn->query($station_query);

if ($station_result->num_rows === 0) {
    setFlashMessage("Station not found.", "danger");
    header("Location: /chargealaya/public/stations.php");
    exit();
}

$station = $station_result->fetch_assoc();

// Check if user has already reviewed
$check_query = "SELECT feedback_id FROM feedback WHERE user_id = $user_id AND station_id = $station_id";
$existing_feedback = $conn->query($check_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $comment = sanitize($_POST['comment']);
    
    $errors = [];
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a rating between 1 and 5 stars.";
    }
    
    if (empty($comment)) {
        $errors[] = "Please provide a comment.";
    } elseif (strlen($comment) < 10) {
        $errors[] = "Comment must be at least 10 characters long.";
    }
    
    if (empty($errors)) {
        if ($existing_feedback->num_rows > 0) {
            // Update existing feedback
            $feedback = $existing_feedback->fetch_assoc();
            $update_stmt = $conn->prepare("UPDATE feedback SET rating = ?, comment = ?, created_at = NOW() WHERE feedback_id = ?");
            $update_stmt->bind_param("isi", $rating, $comment, $feedback['feedback_id']);
            
            if ($update_stmt->execute()) {
                setFlashMessage("Your feedback has been updated successfully!", "success");
                header("Location: /chargealaya/public/station_detail.php?id=$station_id");
                exit();
            }
            $update_stmt->close();
        } else {
            // Insert new feedback
            $insert_stmt = $conn->prepare("INSERT INTO feedback (user_id, station_id, rating, comment) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("iiis", $user_id, $station_id, $rating, $comment);
            
            if ($insert_stmt->execute()) {
                setFlashMessage("Thank you for your feedback!", "success");
                header("Location: /chargealaya/public/station_detail.php?id=$station_id");
                exit();
            }
            $insert_stmt->close();
        }
    }
    
    foreach ($errors as $error) {
        setFlashMessage($error, "danger");
    }
}

// Get existing feedback if any
$existing_rating = 0;
$existing_comment = '';
if ($existing_feedback->num_rows > 0) {
    $existing_feedback->data_seek(0);
    $feedback_data = $existing_feedback->fetch_assoc();
    $feedback_query = "SELECT rating, comment FROM feedback WHERE feedback_id = " . $feedback_data['feedback_id'];
    $feedback_result = $conn->query($feedback_query);
    if ($feedback_result->num_rows > 0) {
        $feedback = $feedback_result->fetch_assoc();
        $existing_rating = $feedback['rating'];
        $existing_comment = $feedback['comment'];
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-star text-warning"></i> 
                        <?php echo $existing_feedback->num_rows > 0 ? 'Update' : 'Leave'; ?> Feedback
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Station Info -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-charging-station"></i> <?php echo htmlspecialchars($station['station_name']); ?></h5>
                        <p class="mb-0">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($station['address'] . ', ' . $station['city']); ?>
                        </p>
                    </div>
                    
                    <?php if ($existing_feedback->num_rows > 0): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> You have already reviewed this station. Submitting again will update your previous review.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label">Your Rating *</label>
                            <div class="star-rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" 
                                           <?php echo ($existing_rating == $i) ? 'checked' : ''; ?> required>
                                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> stars">
                                        <i class="fas fa-star"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <small class="text-muted">Click on stars to rate (1-5 stars)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comment" class="form-label">Your Review *</label>
                            <textarea class="form-control" id="comment" name="comment" rows="6" required 
                                      placeholder="Share your experience at this charging station..."><?php echo htmlspecialchars($existing_comment); ?></textarea>
                            <small class="text-muted">Minimum 10 characters</small>
                        </div>
                        
                        <div class="alert alert-light">
                            <strong><i class="fas fa-lightbulb"></i> Tips for writing a helpful review:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Mention the charger type you used</li>
                                <li>Comment on charging speed and reliability</li>
                                <li>Describe the location and accessibility</li>
                                <li>Note any amenities nearby</li>
                                <li>Be honest and constructive</li>
                            </ul>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between">
                            <a href="/chargealaya/public/station_detail.php?id=<?php echo $station_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-custom">
                                <i class="fas fa-paper-plane"></i> Submit Feedback
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
