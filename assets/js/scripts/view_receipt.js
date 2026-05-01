// Auto-trigger print dialog if print parameter is present
if (window.location.search.includes('print=1')) {
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
}