<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h5 class="mb-2">
                    <i class="fas fa-charging-station text-success"></i>
                    <?php echo htmlspecialchars($res['station_name']); ?>
                </h5>
                
                <p class="text-muted mb-2">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo htmlspecialchars($res['address'] . ', ' . $res['city']); ?>
                </p>
                
                <div class="mb-2">
                    <strong>Charger:</strong>
                    <?php echo ucfirst($res['charger_type']); ?> - 
                    <?php echo $res['max_power_kw']; ?> kW - 
                    <?php echo htmlspecialchars($res['connector_type']); ?>
                </div>
                
                <?php if (isset($res['vehicle_info']) && $res['vehicle_info']): ?>
                <div class="mb-2">
                    <strong><i class="fas fa-car"></i> Vehicle:</strong>
                    <?php echo htmlspecialchars($res['vehicle_info']); ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-2">
                    <strong><i class="fas fa-calendar"></i> Start:</strong>
                    <?php echo formatDateTime($res['start_time']); ?>
                </div>
                
                <div class="mb-2">
                    <strong><i class="fas fa-calendar"></i> End:</strong>
                    <?php echo formatDateTime($res['end_time']); ?>
                </div>
                
                <?php
                $duration = (strtotime($res['end_time']) - strtotime($res['start_time'])) / 3600;
                ?>
                <div class="mb-2">
                    <strong><i class="fas fa-clock"></i> Duration:</strong>
                    <?php echo number_format($duration, 1); ?> hours
                </div>
            </div>
            
            <div class="col-md-4 text-end">
                <?php
                $status_class = '';
                $status_text = '';
                if ($res['status'] === 'confirmed') {
                    $status_class = $is_upcoming ? 'bg-success' : 'bg-secondary';
                    $status_text = $is_upcoming ? 'Upcoming' : 'Confirmed';
                } elseif ($res['status'] === 'completed') {
                    $status_class = 'bg-primary';
                    $status_text = 'Completed';
                } else {
                    $status_class = 'bg-danger';
                    $status_text = 'Cancelled';
                }
                ?>
                <span class="badge <?php echo $status_class; ?> mb-3"><?php echo $status_text; ?></span>
                
                <?php if ($is_upcoming && $res['status'] === 'confirmed'): ?>
                    <div class="mt-3">
                        <a href="reservations.php?cancel=<?php echo $res['reservation_id']; ?>" 
                           class="btn btn-sm btn-danger-custom"
                           onclick="return confirm('Are you sure you want to cancel this reservation?')">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
