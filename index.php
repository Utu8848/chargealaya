<?php
$page_title = "Home";
include 'partials/header.php';

$db = new Database();
$conn = $db->connect();

// Get statistics
$total_stations = $conn->query("SELECT COUNT(*) as count FROM charging_stations WHERE status = 'online'")->fetch_assoc()['count'];
$total_chargers = $conn->query("SELECT COUNT(*) as count FROM chargers WHERE status = 'available'")->fetch_assoc()['count'];
$total_sessions = $conn->query("SELECT COUNT(*) as count FROM charging_sessions")->fetch_assoc()['count'];
?>

<!-- Hero Section -->
<div class="hero-enhanced">
    <div class="container hero-content">
        <div class="row align-items-center">
            <div class="col-lg-6 fadeIn-1s">
                <h1>
                    Charge Your Future <span class="pulse-animation">⚡</span>
                </h1>
                <p>
                    Nepal's premier EV charging network. Find stations, book slots, and power up your electric journey.
                </p>
                <div class="d-flex gap-3">
                    <a href="public/stations.php" class="btn btn-light btn-lg px-5 py-3">
                        <i class="fas fa-search"></i> Find Stations
                    </a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="auth/register.php" class="btn btn-outline-light btn-lg px-5 py-3">
                        <i class="fas fa-user-plus"></i> Get Started
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 slideInRight-1s">
                <div class="stats-box delay-03s">
                    <div class="row">
                        <div class="col-4">
                            <div class="stats-number"><?php echo $total_stations; ?>+</div>
                            <p>Stations</p>
                        </div>
                        <div class="col-4">
                            <div class="stats-number"><?php echo $total_chargers; ?>+</div>
                            <p>Chargers</p>
                        </div>
                        <div class="col-4">
                            <div class="stats-number"><?php echo $total_sessions; ?>+</div>
                            <p>Sessions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="container my-5 py-5">
    <div class="text-center mb-5 fadeIn-1s">
        <h2 class="features-title">Why Choose ChargeAlaya?</h2>
        <p class="features-subtitle">The complete solution for electric vehicle charging</p>
    </div>
    
    <div class="row g-4">
        <div class="col-md-4 delay-01s">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h4>Find Stations</h4>
                <p>Locate nearby charging stations with real-time availability. Filter by charger type and power rating.</p>
            </div>
        </div>
        
        <div class="col-md-4 delay-02s">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h4>Easy Booking</h4>
                <p>Reserve charging slots in advance. Never wait in line again with our smart reservation system.</p>
            </div>
        </div>
        
        <div class="col-md-4 delay-03s">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h4>Fast Charging</h4>
                <p>Access to fast DC chargers. Get back on the road quickly with speeds up to 150kW.</p>
            </div>
        </div>
        
        <div class="col-md-4 delay-04s">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h4>Multiple Payment Options</h4>
                <p>Pay with eSewa, Khalti, card, or cash. Flexible payment methods for your convenience.</p>
            </div>
        </div>
        
        <div class="col-md-4 delay-05s">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4>Track Usage</h4>
                <p>Monitor your charging history, energy consumption, and spending with detailed analytics.</p>
            </div>
        </div>
        
        <div class="col-md-4 delay-06s">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h4>24/7 Support</h4>
                <p>Report issues and get help anytime. Our support team is always ready to assist you.</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="cta-section">
    <div class="container text-center">
        <h2>Ready to Go Electric?</h2>
        <p>Join thousands of EV drivers powering Nepal's green future</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="auth/register.php" class="btn btn-custom btn-lg px-5 py-3">
            <i class="fas fa-rocket"></i> Sign Up Now
        </a>
        <?php else: ?>
        <a href="public/stations.php" class="btn btn-custom btn-lg px-5 py-3">
            <i class="fas fa-charging-station"></i> Browse Stations
        </a>
        <?php endif; ?>
    </div>
</div>

<?php
$conn->close();
include 'partials/footer.php';
?>
