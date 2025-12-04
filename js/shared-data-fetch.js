/**
 * Shared Data Fetching Module for Spendy Application
 * Ensures consistent data fetching across all pages
 */

const DataFetcher = {
    /**
     * Base API URL configuration
     */
    apiBase: {
        profile: 'get_profile.php',
        savings: 'get_savings.php',
        savingsById: 'get_savings_by_id.php',
        transactions: 'get_transactions.php',
        activityLog: 'get_activity_log.php'
    },

    /**
     * Get user profile data
     * @param {string} userId - Optional user ID (uses session if not provided)
     * @returns {Promise<Object>} User profile data
     */
    async getProfile(userId = null) {
        try {
            const url = userId ? `${this.apiBase.profile}?user_id=${userId}` : this.apiBase.profile;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin' // Include session cookies
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            // Validate response structure
            if (!data || typeof data !== 'object') {
                throw new Error('Invalid response format');
            }

            return data;
        } catch (error) {
            console.error('Error fetching profile:', error);
            throw error;
        }
    },

    /**
     * Get savings plans for a user
     * @param {string} userId - Optional user ID (uses session if not provided)
     * @returns {Promise<Array>} Array of savings plans
     */
    async getSavings(userId = null) {
        try {
            // If no userId provided, fetch from profile first to get user_id
            if (!userId) {
                const profile = await this.getProfile();
                userId = profile.userId || profile.user_id;
            }

            if (!userId) {
                throw new Error('User ID is required');
            }

            const response = await fetch(`${this.apiBase.savings}?user_id=${userId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            // Validate response structure
            if (data.success && Array.isArray(data.plans)) {
                return data.plans;
            } else if (Array.isArray(data)) {
                return data;
            } else {
                throw new Error('Invalid savings data format');
            }
        } catch (error) {
            console.error('Error fetching savings:', error);
            throw error;
        }
    },

    /**
     * Get a single savings plan by ID
     * @param {string} savingsId - Savings plan ID
     * @returns {Promise<Object>} Savings plan data
     */
    async getSavingsById(savingsId) {
        try {
            if (!savingsId) {
                throw new Error('Savings ID is required');
            }

            const response = await fetch(`${this.apiBase.savingsById}?savingsId=${encodeURIComponent(savingsId)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success && data.plan) {
                return data.plan;
            } else {
                throw new Error(data.error || 'Invalid savings plan data');
            }
        } catch (error) {
            console.error('Error fetching savings by ID:', error);
            throw error;
        }
    },

    /**
     * Get activity log for a user
     * @param {string} userId - Optional user ID (uses session if not provided)
     * @param {number} limit - Optional limit on number of entries
     * @returns {Promise<Array>} Array of activity log entries
     */
    async getActivityLog(userId = null, limit = null) {
        try {
            if (!userId) {
                const profile = await this.getProfile();
                userId = profile.userId || profile.user_id;
            }

            if (!userId) {
                throw new Error('User ID is required');
            }

            let url = `${this.apiBase.activityLog}?user_id=${userId}`;
            if (limit) {
                url += `&limit=${limit}`;
            }

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            // Validate response structure
            if (data.success && Array.isArray(data.activities)) {
                return data.activities;
            } else if (Array.isArray(data)) {
                return data;
            } else {
                throw new Error('Invalid activity log data format');
            }
        } catch (error) {
            console.error('Error fetching activity log:', error);
            throw error;
        }
    },

    /**
     * Check if user is logged in
     * @returns {Promise<boolean>} True if user is logged in
     */
    async isLoggedIn() {
        try {
            const profile = await this.getProfile();
            return profile && (profile.userId || profile.user_id);
        } catch (error) {
            return false;
        }
    },

    /**
     * Get current user ID from session
     * @returns {Promise<string|null>} User ID or null
     */
    async getCurrentUserId() {
        try {
            const profile = await this.getProfile();
            return profile.userId || profile.user_id || null;
        } catch (error) {
            console.error('Error getting current user ID:', error);
            return null;
        }
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DataFetcher;
}

// Make available globally
window.DataFetcher = DataFetcher;

