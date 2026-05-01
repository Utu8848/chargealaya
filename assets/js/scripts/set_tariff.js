document.addEventListener('DOMContentLoaded', function() {
    const serviceFee = document.getElementById('service_fee');
    const pricePerKwh = document.getElementById('price_per_kwh');
    
    function updateExample() {
        const service = parseFloat(serviceFee.value) || 0;
        const price = parseFloat(pricePerKwh.value) || 0;
        const kwh = 50;
        const total = service + (price * kwh);
        
        const alertBox = document.querySelector('.alert-warning');
        alertBox.innerHTML = `
            <strong><i class="fas fa-info-circle"></i> Pricing Example:</strong><br>
            For a session using <strong>50 kWh</strong>:<br>
            Service Fee: NPR ${service.toFixed(2)}<br>
            Energy Cost: NPR ${price.toFixed(2)} × 50 = NPR ${(price * kwh).toFixed(2)}<br>
            <strong>Total: NPR ${total.toFixed(2)}</strong>
        `;
    }
    
    serviceFee.addEventListener('input', updateExample);
    pricePerKwh.addEventListener('input', updateExample);
});