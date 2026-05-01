console.log('Variables loaded - v1.5');
console.log('Price per kWh:', pricePerKwh);
console.log('Service Fee:', serviceFee);
console.log('Chargers:', allChargers);
console.log('Number of chargers:', allChargers.length);

// Helper function to safely get element
function safeGetElement(id) {
    const el = document.getElementById(id);
    if (!el) {
        console.error('Element not found:', id);
    }
    return el;
}

// Helper function to safely set innerHTML
function safeSetHTML(id, html) {
    const el = safeGetElement(id);
    if (el) {
        el.innerHTML = html;
        return true;
    }
    return false;
}

// Helper function to safely set textContent
function safeSetText(id, text) {
    const el = safeGetElement(id);
    if (el) {
        el.textContent = text;
        return true;
    }
    return false;
}

// Wait for DOM before adding event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Ready - v1.5, registering event listeners...');
    
    // Verify all required elements exist
    const requiredElements = [
        'vehicleSelect', 'chargerSelect', 'startTimeInput', 'durationSelect',
        'reservationSummary', 'summaryVehicle', 'summaryCharger', 'summaryStartTime',
        'summaryEndTime', 'summaryDuration', 'summaryEnergy', 'summaryEnergyCost',
        'summaryTotalCost', 'summaryServiceFee'
    ];
    
    const missingElements = [];
    requiredElements.forEach(id => {
        if (!document.getElementById(id)) {
            missingElements.push(id);
        }
    });
    
    if (missingElements.length > 0) {
        console.error('MISSING ELEMENTS:', missingElements);
        alert('Page error: Some elements are missing. Please refresh the page.');
        return;
    }
    console.log('All required elements found ✓');

// Vehicle selection handler
document.getElementById('vehicleSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const vehicleConnector = selectedOption.getAttribute('data-connector');
    const chargerSelect = safeGetElement('chargerSelect');
    
    if (!chargerSelect) {
        console.error('Charger select not found!');
        return;
    }
    
    // Update summary
    if (this.value) {
        const vehicleText = selectedOption.textContent.trim();
        safeSetHTML('summaryVehicle', '<strong>' + vehicleText + '</strong>');
        console.log('Summary vehicle updated to:', vehicleText);
    } else {
        safeSetHTML('summaryVehicle', '<small class="text-muted">Not selected</small>');
    }
    
    if (!vehicleConnector) {
        chargerSelect.innerHTML = '<option value="">First select your vehicle...</option>';
        chargerSelect.disabled = true;
        const chargerInfo = safeGetElement('chargerInfo');
        if (chargerInfo) chargerInfo.style.display = 'none';
        return;
    }
    
    // Filter compatible chargers
    const compatibleChargers = allChargers.filter(c => c.connector_type === vehicleConnector);
    
    if (compatibleChargers.length === 0) {
        chargerSelect.innerHTML = '<option value="">No compatible chargers available for ' + vehicleConnector + '</option>';
        chargerSelect.disabled = true;
        const chargerInfo = safeGetElement('chargerInfo');
        if (chargerInfo) chargerInfo.style.display = 'none';
        showValidationModal('<i class="fas fa-plug text-danger"></i> <strong>No Compatible Chargers</strong><br><br>Your vehicle uses <strong>' + vehicleConnector + '</strong> connector type, but this station does not have any compatible chargers available.<br><br>Please select a different station or vehicle.');
    } else {
        // Populate compatible chargers
        let options = '<option value="">Choose a charger...</option>';
        compatibleChargers.forEach(charger => {
            const type = charger.charger_type.charAt(0).toUpperCase() + charger.charger_type.slice(1);
            options += `<option value="${charger.charger_id}" 
                data-power="${charger.max_power_kw}" 
                data-type="${charger.charger_type}" 
                data-connector="${charger.connector_type}">
                ${type} Charger - ${charger.max_power_kw}kW - ${charger.connector_type}
            </option>`;
        });
        chargerSelect.innerHTML = options;
        chargerSelect.disabled = false;
    }
});

// Charger selection handler
document.getElementById('chargerSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const power = selectedOption.getAttribute('data-power');
    const type = selectedOption.getAttribute('data-type');
    const connector = selectedOption.getAttribute('data-connector');
    
    console.log('Charger changed:', {value: this.value, power, type, connector});
    
    if (this.value) {
        const chargerInfo = safeGetElement('chargerInfo');
        if (chargerInfo) chargerInfo.style.display = 'block';
        
        safeSetHTML('chargerDetails',
            `<strong>Selected:</strong> ${type.charAt(0).toUpperCase() + type.slice(1)} charger with ${power}kW max power`);
        
        // Update summary - FIX: Check if connector exists
        if (connector) {
            safeSetHTML('summaryCharger',
                `<strong>${type.charAt(0).toUpperCase() + type.slice(1)} - ${power}kW - ${connector}</strong>`);
            console.log('Summary charger updated to:', type, power, connector);
        } else {
            console.error('Connector attribute missing!');
            safeSetHTML('summaryCharger',
                `<strong>${type.charAt(0).toUpperCase() + type.slice(1)} - ${power}kW</strong>`);
        }
        
        updateCost();
    } else {
        const chargerInfo = safeGetElement('chargerInfo');
        if (chargerInfo) chargerInfo.style.display = 'none';
        
        safeSetHTML('summaryCharger', '<small class="text-muted">Not selected</small>');
    }
});

// Duration selection handler
document.getElementById('durationSelect').addEventListener('change', updateCost);

// Start time selection handler
document.getElementById('startTimeInput').addEventListener('change', updateSummary);

function updateSummary() {
    const startTimeInput = safeGetElement('startTimeInput');
    const durationSelect = safeGetElement('durationSelect');
    
    if (!startTimeInput || !durationSelect) {
        console.error('updateSummary: Required elements not found');
        return;
    }
    
    if (startTimeInput.value) {
        const startDate = new Date(startTimeInput.value);
        safeSetHTML('summaryStartTime',
            '<strong>' + startDate.toLocaleString('en-US', { 
                month: 'short', day: 'numeric', year: 'numeric', 
                hour: 'numeric', minute: '2-digit', hour12: true 
            }) + '</strong>');
        
        if (durationSelect.value) {
            const endDate = new Date(startDate.getTime() + (parseFloat(durationSelect.value) * 60 * 60 * 1000));
            safeSetHTML('summaryEndTime',
                '<strong>' + endDate.toLocaleString('en-US', { 
                    month: 'short', day: 'numeric', year: 'numeric', 
                    hour: 'numeric', minute: '2-digit', hour12: true 
                }) + '</strong>');
        }
    } else {
        safeSetHTML('summaryStartTime', '<small class="text-muted">Not selected</small>');
        safeSetHTML('summaryEndTime', '<small class="text-muted">-</small>');
    }
}

function updateCost() {
    console.log('updateCost called');
    const chargerSelect = safeGetElement('chargerSelect');
    const durationSelect = safeGetElement('durationSelect');
    
    if (!chargerSelect || !durationSelect) {
        console.error('Cannot update cost - selects not found');
        return;
    }
    
    const selectedCharger = chargerSelect.options[chargerSelect.selectedIndex];
    
    console.log('Charger selected:', chargerSelect.value);
    console.log('Duration selected:', durationSelect.value);
    
    // Update duration in summary
    if (durationSelect.value) {
        const hours = parseFloat(durationSelect.value);
        let durationText = '';
        if (hours < 1) {
            durationText = (hours * 60) + ' minutes';
        } else if (hours === 1) {
            durationText = '1 hour';
        } else {
            durationText = hours + ' hours';
        }
        safeSetHTML('summaryDuration', '<strong>' + durationText + '</strong>');
        updateSummary(); // Update end time
    } else {
        safeSetHTML('summaryDuration', '<small class="text-muted">Not selected</small>');
    }
    
    if (chargerSelect.value && durationSelect.value) {
        console.log('Both charger and duration selected, calculating cost...');
        
        const power = parseFloat(selectedCharger.getAttribute('data-power'));
        const hours = parseFloat(durationSelect.value);
        
        console.log('Power:', power, 'kW');
        console.log('Hours:', hours);
        console.log('Price per kWh:', pricePerKwh);
        console.log('Service Fee:', serviceFee);
        
        // Energy calculation with efficiency factor
        // EFFICIENCY_FACTOR: Accounts for real-world charging inefficiencies
        // - 0.8 (80%) = Industry standard (recommended)
        // - 0.85 (85%) = Optimistic estimate
        // - 0.75 (75%) = Conservative estimate
        // - 1.0 (100%) = Theoretical maximum (not realistic)
        const EFFICIENCY_FACTOR = 0.8;  // ← Change this value if needed
        
        const energyKwh = power * hours * EFFICIENCY_FACTOR;
        const energyCost = energyKwh * pricePerKwh;
        const cost = energyCost + serviceFee;
        
        console.log('Calculated energy:', energyKwh, 'kWh');
        console.log('Energy cost:', energyCost);
        console.log('Total cost:', cost);
        
        // Update summary card (this shows all the info the user needs)
        safeSetHTML('summaryEnergy', '<strong>' + energyKwh.toFixed(2) + ' kWh</strong>');
        safeSetHTML('summaryEnergyCost', '<strong>NPR ' + energyCost.toFixed(2) + '</strong>');
        safeSetText('summaryTotalCost', 'NPR ' + cost.toFixed(2));
        
        console.log('Showing reservation summary card');
        const summaryCard = safeGetElement('reservationSummary');
        if (summaryCard) {
            summaryCard.style.display = 'block';
            console.log('Summary card displayed successfully');
        } else {
            console.error('Summary card element not found!');
        }
    } else {
        console.log('Hiding reservation summary card (missing charger or duration)');
        const summaryCard = safeGetElement('reservationSummary');
        if (summaryCard) summaryCard.style.display = 'none';
    }
}

// Form validation to prevent past time submission
document.getElementById('reservationForm').addEventListener('submit', function(e) {
    const startTime = document.getElementById('startTimeInput').value;
    if (!startTime) {
        showValidationModal('Please select a start time for your reservation.');
        e.preventDefault();
        return false;
    }
    
    const selectedTime = new Date(startTime);
    const now = new Date();
    const oneHourLater = new Date(now.getTime() + (60 * 60 * 1000));
    
    if (selectedTime <= now) {
        showValidationModal('<i class="fas fa-exclamation-triangle text-danger"></i> <strong>Cannot Reserve Past Time</strong><br><br>Reservation start time must be in the future. You cannot reserve for past times. Please select a future date and time.');
        e.preventDefault();
        return false;
    }
    
    if (selectedTime < oneHourLater) {
        showValidationModal('<i class="fas fa-clock text-warning"></i> <strong>Advance Booking Required</strong><br><br>Reservations must be made at least 1 hour in advance to ensure station availability.');
        e.preventDefault();
        return false;
    }
    
    return true;
});

function showValidationModal(message) {
    document.getElementById('validationMessage').innerHTML = message;
    var modal = new bootstrap.Modal(document.getElementById('validationModal'));
    modal.show();
}

console.log('Event listeners registered successfully');
}); // End DOMContentLoaded