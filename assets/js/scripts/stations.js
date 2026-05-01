function showStationOfflineModal(status) {
    let message = '';
    if (status === 'offline') {
        message = '<i class="fas fa-power-off text-danger"></i> <strong>This station is currently offline.</strong><br><br>Reservations are not available at this time. Please try another station or check back later.';
    } else if (status === 'under_maintenance') {
        message = '<i class="fas fa-tools text-warning"></i> <strong>This station is currently under maintenance.</strong><br><br>Reservations are not available at this time. Please try another station or check back later.';
    }
    
    document.getElementById('stationOfflineMessage').innerHTML = message;
    
    const modalElement = document.getElementById('stationOfflineModal');
    
    // Dispose any existing instance
    const existingInstance = bootstrap.Modal.getInstance(modalElement);
    if (existingInstance) {
        existingInstance.dispose();
    }
    
    // Remove any lingering backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    var modal = new bootstrap.Modal(modalElement);
    modal.show();
}