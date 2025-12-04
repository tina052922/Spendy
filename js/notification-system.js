/**
 * Notification System - Shared Component
 * Can be included on any page to add notification functionality
 * 
 * Requirements:
 * 1. Include this script after the notification HTML is loaded
 * 2. Call initNotificationSystem() after DOM is ready
 * 3. Make sure get_notifications.php is accessible
 */

const NotificationSystem = {
    isOpen: false,
    notifications: [],
    refreshInterval: null,
        //Wesley was here
    /**
     * Initialize the notification system
     */
    init() {
        // Check if notification elements exist
        const iconWrapper = document.querySelector('.notification-icon-wrapper');
        const dropdown = document.getElementById('notificationDropdown');
        
        if (!iconWrapper || !dropdown) {
            console.warn('Notification system: Required elements not found');
            return;
        }

        // Load initial notifications
        this.loadNotifications();

        // Refresh every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.loadNotifications();
        }, 30000);

        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            const wrapper = document.querySelector('.notification-wrapper');
            if (wrapper && !wrapper.contains(event.target)) {
                this.closeDropdown();
            }
        });
    },

    /**
     * Toggle notification dropdown
     */
    toggle() {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;

        if (this.isOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    },

    /**
     * Open notification dropdown
     */
    openDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;

        this.isOpen = true;
        dropdown.classList.add('show');
        this.loadNotifications();
    },

    /**
     * Close notification dropdown
     */
    closeDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;

        this.isOpen = false;
        dropdown.classList.remove('show');
    },

    /**
     * Load notifications from API
     */
    async loadNotifications() {
        const notificationList = document.getElementById('notificationList');
        const notificationBadge = document.getElementById('notificationBadge');

        if (!notificationList) return;

        try {
            const response = await fetch('../api/get_notifications.php?limit=10');
            const data = await response.json();

            if (data.success) {
                this.notifications = data.notifications || [];
                this.renderNotifications(this.notifications);

                // Update badge
                if (notificationBadge) {
                    const count = data.count || 0;
                    if (count > 0) {
                        notificationBadge.textContent = count > 99 ? '99+' : count;
                        notificationBadge.classList.add('show');
                    } else {
                        notificationBadge.classList.remove('show');
                    }
                }
            } else {
                notificationList.innerHTML = '<div class="notification-empty">Failed to load notifications</div>';
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            const notificationList = document.getElementById('notificationList');
            if (notificationList) {
                notificationList.innerHTML = '<div class="notification-empty">Failed to load notifications</div>';
            }
        }
    },

    /**
     * Render notifications in the dropdown
     */
    renderNotifications(notifications) {
        const notificationList = document.getElementById('notificationList');
        if (!notificationList) return;

        if (notifications.length === 0) {
            notificationList.innerHTML = '<div class="notification-empty">No notifications yet</div>';
            return;
        }

        notificationList.innerHTML = notifications.map(notif => `
            <div class="notification-item">
                <div class="notification-icon-large">${notif.icon || '🔔'}</div>
                <div class="notification-content">
                    <div class="notification-message">${this.escapeHtml(notif.message)}</div>
                    <div class="notification-time">${notif.time}</div>
                </div>
            </div>
        `).join('');
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Cleanup on page unload
     */
    cleanup() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
};

// Global function for onclick handlers
function toggleNotifications() {
    NotificationSystem.toggle();
}

function loadNotifications() {
    NotificationSystem.loadNotifications();
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        NotificationSystem.init();
    });
} else {
    NotificationSystem.init();
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    NotificationSystem.cleanup();
});

