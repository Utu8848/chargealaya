// Main JavaScript File for EV Charging System

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Confirm delete actions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Format currency
function formatCurrency(amount) {
    return 'NPR ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Validate form before submission
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }
    return true;
}

// Calculate charging cost
function calculateChargingCost(energyKwh, pricePerKwh, serviceFee) {
    const energyCost = energyKwh * pricePerKwh;
    return energyCost + serviceFee;
}

// Filter table rows
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        let row = rows[i];
        let cells = row.getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            let cell = cells[j];
            if (cell) {
                let textValue = cell.textContent || cell.innerText;
                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        row.style.display = found ? '' : 'none';
    }
}

// Show loading spinner
function showLoading() {
    const loadingHtml = `
        <div class="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
            <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', loadingHtml);
}

// Hide loading spinner
function hideLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Real-time search for stations
function searchStations() {
    const searchInput = document.getElementById('searchStation');
    const cityFilter = document.getElementById('cityFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    if (!searchInput) return;
    
    const searchTerm = searchInput.value.toLowerCase();
    const city = cityFilter ? cityFilter.value : '';
    const status = statusFilter ? statusFilter.value : '';
    
    const stationCards = document.querySelectorAll('.station-card');
    
    stationCards.forEach(function(card) {
        const name = card.querySelector('h4') ? card.querySelector('h4').textContent.toLowerCase() : '';
        const cardCity = card.dataset.city ? card.dataset.city.toLowerCase() : '';
        const cardStatus = card.dataset.status ? card.dataset.status.toLowerCase() : '';
        
        const matchesSearch = name.includes(searchTerm);
        const matchesCity = !city || cardCity === city.toLowerCase();
        const matchesStatus = !status || cardStatus === status.toLowerCase();
        
        if (matchesSearch && matchesCity && matchesStatus) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    return strength;
}

// Display password strength
function updatePasswordStrength() {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('passwordStrength');
    
    if (!passwordInput || !strengthBar) return;
    
    passwordInput.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        let strengthText = '';
        let strengthClass = '';
        
        switch(strength) {
            case 0:
            case 1:
                strengthText = 'Weak';
                strengthClass = 'bg-danger';
                break;
            case 2:
            case 3:
                strengthText = 'Medium';
                strengthClass = 'bg-warning';
                break;
            case 4:
            case 5:
                strengthText = 'Strong';
                strengthClass = 'bg-success';
                break;
        }
        
        strengthBar.className = 'progress-bar ' + strengthClass;
        strengthBar.style.width = (strength * 20) + '%';
        strengthBar.textContent = strengthText;
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePasswordStrength();
});
