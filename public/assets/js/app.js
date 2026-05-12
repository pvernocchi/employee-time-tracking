/**
 * Employee Time Tracker - Frontend JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Live duration counter for active clock-in
    initLiveDuration();

    // Auto-dismiss alerts after 5 seconds
    initAlertDismissal();

    // Date validation for leave requests
    initDateValidation();
});

/**
 * Live duration counter
 */
function initLiveDuration() {
    const el = document.getElementById('live-duration');
    if (!el) return;

    const startTime = el.dataset.start;
    if (!startTime) return;

    // Parse the server timestamp as-is (matches server timezone set in PHP config)
    const start = new Date(startTime.replace(' ', 'T')).getTime();

    function update() {
        const now = Date.now();
        const diff = Math.floor((now - start) / 1000);
        
        const hours = Math.floor(diff / 3600);
        const minutes = Math.floor((diff % 3600) / 60);
        const seconds = diff % 60;
        
        el.textContent = 
            String(hours).padStart(2, '0') + ':' + 
            String(minutes).padStart(2, '0') + ':' + 
            String(seconds).padStart(2, '0');
    }

    update();
    setInterval(update, 1000);
}

/**
 * Auto-dismiss flash alerts
 */
function initAlertDismissal() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 300);
        }, 5000);
    });
}

/**
 * Date validation for leave request form
 */
function initDateValidation() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    if (!startDate || !endDate) return;

    // Set min date to today
    const today = new Date().toISOString().split('T')[0];
    startDate.min = today;
    endDate.min = today;

    startDate.addEventListener('change', function() {
        endDate.min = this.value;
        if (endDate.value && endDate.value < this.value) {
            endDate.value = this.value;
        }
    });
}
