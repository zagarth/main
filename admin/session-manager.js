/**
 * Session Timeout Management
 * Monitors session expiration and prompts user to extend session
 * Uses embedded PHP data instead of AJAX to work with IP restrictions
 */
class SessionManager {
    constructor(options = {}) {
        this.warningTime = options.warningTime || 5 * 60; // 5 minutes before expiration
        this.checkInterval = options.checkInterval || 30 * 1000; // Check every 30 seconds
        this.sessionData = options.sessionData || null;
        
        this.warningShown = false;
        this.intervalId = null;
        this.modal = null;
        this.sessionStartTime = Date.now() / 1000;
        this.maxLifetime = this.sessionData?.max_lifetime || 3600; // Default 1 hour
        
        this.init();
    }
    
    init() {
        this.createModal();
        this.startMonitoring();
        
        // Also track user activity
        this.trackActivity();
    }
    
    createModal() {
        // Create session warning modal
        this.modal = document.createElement('div');
        this.modal.className = 'session-modal';
        this.modal.style.display = 'none';
        this.modal.innerHTML = `
            <div class="session-modal-overlay">
                <div class="session-modal-content">
                    <h3 style="color: #e74c3c; margin-top: 0;">⏰ Session Expiring Soon</h3>
                    <p>Your session will expire in <strong id="session-countdown">5:00</strong>.</p>
                    <p>Would you like to stay logged in?</p>
                    <div class="session-modal-buttons">
                        <button id="extend-session" class="session-btn session-btn-primary">
                            🔄 Stay Logged In
                        </button>
                        <button id="logout-now" class="session-btn session-btn-secondary">
                            🚪 Logout Now
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .session-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
            }
            
            .session-modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.3s ease;
            }
            
            .session-modal-content {
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                max-width: 400px;
                width: 90%;
                text-align: center;
                animation: slideIn 0.3s ease;
            }
            
            .session-modal-buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
                margin-top: 20px;
            }
            
            .session-btn {
                padding: 12px 24px;
                border: none;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .session-btn-primary {
                background: linear-gradient(145deg, #27ae60, #229954);
                color: white;
            }
            
            .session-btn-primary:hover {
                background: linear-gradient(145deg, #229954, #1e8449);
                transform: translateY(-1px);
            }
            
            .session-btn-secondary {
                background: linear-gradient(145deg, #95a5a6, #7f8c8d);
                color: white;
            }
            
            .session-btn-secondary:hover {
                background: linear-gradient(145deg, #7f8c8d, #6c7b7d);
                transform: translateY(-1px);
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes slideIn {
                from { transform: translateY(-20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            #session-countdown {
                color: #e74c3c;
                font-size: 18px;
            }
        `;
        
        document.head.appendChild(style);
        document.body.appendChild(this.modal);
        
        // Bind events
        this.modal.querySelector('#extend-session').addEventListener('click', () => {
            this.extendSession();
        });
        
        this.modal.querySelector('#logout-now').addEventListener('click', () => {
            this.logout();
        });
    }
    
    startMonitoring() {
        this.intervalId = setInterval(() => {
            this.checkSession();
        }, this.checkInterval);
        
        // Check immediately
        this.checkSession();
    }
    
    checkSession() {
        // Use embedded session data if available
        if (window.adminSessionData) {
            const sessionData = window.adminSessionData;
            const currentTime = Date.now() / 1000;
            const sessionStart = sessionData.session_start;
            const maxLifetime = sessionData.max_lifetime;
            const sessionAge = currentTime - sessionStart;
            const timeRemaining = maxLifetime - sessionAge;
            
            // Show warning if session is expiring soon
            if (timeRemaining <= this.warningTime && timeRemaining > 0 && !this.warningShown) {
                this.showWarning(Math.floor(timeRemaining));
            } else if (timeRemaining <= 0) {
                // Session expired
                this.handleExpiredSession();
            }
        } else {
            // Fallback to simple time-based calculation
            const currentTime = Date.now() / 1000;
            const sessionAge = currentTime - this.sessionStartTime;
            const timeRemaining = this.maxLifetime - sessionAge;
            
            if (timeRemaining <= this.warningTime && timeRemaining > 0 && !this.warningShown) {
                this.showWarning(Math.floor(timeRemaining));
            } else if (timeRemaining <= 0) {
                this.handleExpiredSession();
            }
        }
    }
    
    showWarning(timeRemaining) {
        this.warningShown = true;
        this.modal.style.display = 'block';
        
        // Start countdown
        this.startCountdown(timeRemaining);
    }
    
    startCountdown(seconds) {
        const countdownEl = document.getElementById('session-countdown');
        
        const updateCountdown = () => {
            if (seconds <= 0) {
                this.handleExpiredSession();
                return;
            }
            
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            countdownEl.textContent = `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
            
            seconds--;
        };
        
        // Update immediately
        updateCountdown();
        
        // Update every second
        const countdownInterval = setInterval(() => {
            if (this.modal.style.display === 'none') {
                clearInterval(countdownInterval);
                return;
            }
            updateCountdown();
        }, 1000);
    }
    
    extendSession() {
        // Since we can't use AJAX due to IP restrictions, we'll use a form submission
        // to extend the session and refresh the page
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'extend_session';
        actionInput.value = '1';
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
    }
    
    hideWarning() {
        this.modal.style.display = 'none';
        this.warningShown = false;
    }
    
    logout() {
        window.location.href = 'login.php?action=logout';
    }
    
    handleExpiredSession() {
        // Clear the interval
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        
        // Show expiration message and redirect
        alert('Your session has expired. You will be redirected to the login page.');
        window.location.href = 'login.php?expired=1';
    }
    
    trackActivity() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                // Update last activity timestamp locally
                this.lastActivity = Date.now();
            }, true);
        });
    }
    
    showNotification(message, type = 'info') {
        // Create temporary notification
        const notification = document.createElement('div');
        notification.className = `session-notification session-notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'error' ? '#e74c3c' : '#27ae60'};
            color: white;
            padding: 15px 20px;
            border-radius: 6px;
            z-index: 10001;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s ease;
            font-weight: 500;
        `;
        notification.textContent = message;
        
        // Add slide-in animation
        const slideStyle = document.createElement('style');
        slideStyle.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(slideStyle);
        
        document.body.appendChild(notification);
        
        // Remove after 4 seconds
        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
    
    destroy() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        if (this.modal && this.modal.parentNode) {
            this.modal.parentNode.removeChild(this.modal);
        }
    }
}

// Auto-initialize on admin pages
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize on admin pages (check if we're in admin directory)
    if (window.location.pathname.includes('/admin/') && window.adminSessionData) {
        window.sessionManager = new SessionManager({
            warningTime: 5 * 60,      // Warn 5 minutes before expiration
            checkInterval: 30 * 1000, // Check every 30 seconds
            sessionData: window.adminSessionData
        });
    }
});