// Clean up any lingering modal backdrops on page load
document.addEventListener('DOMContentLoaded', function() {
    // Remove any leftover backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Reset body styles
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
});

function cancelReservation(reservationId, customerName) {
    // Get modal element
    const modalElement = document.getElementById('cancelModal');
    
    // Dispose any existing instance
    const existingModal = bootstrap.Modal.getInstance(modalElement);
    if (existingModal) {
        existingModal.dispose();
    }
    
    // Remove any lingering backdrops
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
    
    // Populate modal content immediately (elements exist in DOM)
    const idEl = document.getElementById('modalReservationId');
    const nameEl = document.getElementById('modalCustomerName');
    const formEl = document.getElementById('formReservationId');
    
    if (idEl) idEl.textContent = String(reservationId).padStart(6, '0');
    if (nameEl) nameEl.textContent = customerName;
    if (formEl) formEl.value = reservationId;
    
    // Create and show modal
    const myModal = new bootstrap.Modal(modalElement);
    myModal.show();
}

function completeReservation(reservationId, customerName, endTime) {
    // Check if end time has passed
    const now = new Date();
    const reservationEndTime = new Date(endTime);
    
    if (now < reservationEndTime) {
        // Show warning modal
        const warningModalElement = document.getElementById('endTimeNotPassedModal');
        
        // Dispose any existing instance
        const existingWarning = bootstrap.Modal.getInstance(warningModalElement);
        if (existingWarning) {
            existingWarning.dispose();
        }
        
        // Remove any lingering backdrops
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        
        // Calculate time remaining
        const timeDiff = reservationEndTime - now;
        const hoursRemaining = Math.floor(timeDiff / (1000 * 60 * 60));
        const minutesRemaining = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
        
        let timeRemainingText = '';
        if (hoursRemaining > 0) {
            timeRemainingText = hoursRemaining + ' hour' + (hoursRemaining > 1 ? 's' : '') + ' and ' + minutesRemaining + ' minute' + (minutesRemaining > 1 ? 's' : '');
        } else {
            timeRemainingText = minutesRemaining + ' minute' + (minutesRemaining > 1 ? 's' : '');
        }
        
        const msgEl = document.getElementById('endTimeMessage');
        if (msgEl) {
            msgEl.innerHTML = 
                '<i class="fas fa-clock text-warning"></i> <strong>The reservation end time has not been reached yet.</strong><br><br>' +
                'End Time: ' + reservationEndTime.toLocaleString() + '<br>' +
                'Time Remaining: ' + timeRemainingText + '<br><br>' +
                'Please wait until the reservation period is over before marking it as completed.';
        }
        
        // Create and show warning modal
        const warningModal = new bootstrap.Modal(warningModalElement);
        warningModal.show();
        return;
    }
    
    // If end time has passed, show confirmation modal
    const completeModalElement = document.getElementById('completeModal');
    
    // Dispose any existing instance
    const existingComplete = bootstrap.Modal.getInstance(completeModalElement);
    if (existingComplete) {
        existingComplete.dispose();
    }
    
    // Remove any lingering backdrops
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
    
    // Populate modal content immediately
    const idEl = document.getElementById('modalCompleteReservationId');
    const nameEl = document.getElementById('modalCompleteCustomerName');
    const formEl = document.getElementById('formCompleteReservationId');
    
    if (idEl) idEl.textContent = String(reservationId).padStart(6, '0');
    if (nameEl) nameEl.textContent = customerName;
    if (formEl) formEl.value = reservationId;
    
    // Create and show modal
    const completeModal = new bootstrap.Modal(completeModalElement);
    completeModal.show();
}