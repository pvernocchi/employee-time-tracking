/**
 * Employee Time Tracker - Frontend JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Theme toggle
    initThemeToggle();

    // Live duration counter for active clock-in
    initLiveDuration();

    // Auto-dismiss alerts after 5 seconds
    initAlertDismissal();

    // Date validation for leave requests
    initDateValidation();
});

/**
 * Theme toggle (light/dark)
 */
function initThemeToggle() {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    const root = document.documentElement;
    const iconPath = toggle.querySelector('.theme-toggle-icon path');
    const storageKey = 'theme';
    const moonPath = 'M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z';
    const sunPath = 'M12 4a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1Zm0 13a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0v-1a1 1 0 0 1 1-1Zm8-5a1 1 0 0 1 0 2h-1a1 1 0 1 1 0-2h1ZM6 12a1 1 0 1 1 0 2H5a1 1 0 1 1 0-2h1Zm10.243-5.657a1 1 0 0 1 1.414 1.414l-.707.707a1 1 0 1 1-1.414-1.414l.707-.707ZM8.464 14.12a1 1 0 0 1 0 1.415l-.707.707a1 1 0 0 1-1.414-1.415l.707-.707a1 1 0 0 1 1.414 0Zm9.193 2.122a1 1 0 0 1-1.414 0l-.707-.707a1 1 0 0 1 1.414-1.414l.707.707a1 1 0 0 1 0 1.414ZM8.464 8.465A1 1 0 0 1 7.05 8.465l-.707-.708A1 1 0 0 1 7.757 6.34l.707.707a1 1 0 0 1 0 1.414ZM12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z';

    function setTheme(theme) {
        const isDark = theme === 'dark';
        if (isDark) {
            root.setAttribute('data-theme', 'dark');
        } else {
            root.removeAttribute('data-theme');
        }

        const label = isDark ? toggle.dataset.labelLight : toggle.dataset.labelDark;
        if (label) {
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
        }
        if (iconPath) {
            iconPath.setAttribute('d', isDark ? sunPath : moonPath);
        }
        toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    }

    let savedTheme = 'light';
    try {
        savedTheme = localStorage.getItem(storageKey) === 'dark' ? 'dark' : 'light';
    } catch (e) {
        savedTheme = 'light';
    }
    setTheme(savedTheme);

    toggle.addEventListener('click', function() {
        const nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        setTheme(nextTheme);
        try {
            localStorage.setItem(storageKey, nextTheme);
        } catch (e) {
            // Ignore storage failures
        }
    });
}

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
