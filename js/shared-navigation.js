/**
 * Shared Navigation System for Spendy Application
 * Provides consistent navigation across all pages
 */

// Navigation configuration
const NAVIGATION_CONFIG = {
    home: {
        name: 'Home',
        url: 'index.html',
        appUrl: 'savings.html'
    },
    features: {
        name: 'Features',
        url: 'views/KeyFeatures.html'
    },
    about: {
        name: 'About',
        url: 'views/aboutus.html'
    },
    signup: {
        name: 'Sign Up',
        url: 'views/SignUp.html'
    },
    login: {
        name: 'Sign In',
        url: 'views/login.html'
    },
    settings: {
        name: 'Settings',
        url: 'views/settings.html'
    },
    dashboard: {
        name: 'Dashboard',
        url: 'views/savings.html'
    },
    profile: {
        name: 'Profile',
        url: 'views/profile.html'
    }
};

/**
 * Smooth page transition effect
 * @param {string} url - The URL to navigate to
 * @param {number} duration - Transition duration in milliseconds (default: 300)
 */
function smoothPageTransition(url, duration = 300) {
    if (!url || url === '#' || url.startsWith('javascript:')) return;
    
    // Create overlay for smooth fade effect
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #ffffff;
        z-index: 99999;
        opacity: 0;
        transition: opacity ${duration}ms ease-in-out;
        pointer-events: none;
    `;
    document.body.appendChild(overlay);
    
    // Fade out current page
    document.body.style.transition = `opacity ${duration}ms ease-in-out, transform ${duration}ms ease-in-out`;
    document.body.style.opacity = '0';
    document.body.style.transform = 'translateY(-10px)';
    
    // Fade in overlay
    requestAnimationFrame(() => {
        overlay.style.opacity = '1';
    });
    
    // Navigate after transition
    setTimeout(() => {
        window.location.href = normalizeSpendyPath(url);
    }, duration);
}

/**
 * Initialize navigation for pages that need it
 */
function initNavigation() {
    // Add smooth page transitions to all links
    document.querySelectorAll('a[href$=".html"]').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !href.startsWith('http')) {
                // Don't prevent default for same-page anchors
                if (!href.includes('#')) {
                    e.preventDefault();
                    smoothPageTransition(href);
                }
            }
        });
    });
    
    // Override window.location.href for smooth transitions
    const originalLocationHref = Object.getOwnPropertyDescriptor(window, 'location')?.get;
    if (originalLocationHref) {
        let isTransitioning = false;
        
        // Create a proxy for smooth redirects
        window.smoothLocationRedirect = function(url) {
            if (isTransitioning) return;
            if (url && typeof url === 'string' && (url.includes('.html') || url.startsWith('/'))) {
                isTransitioning = true;
                smoothPageTransition(url);
            } else {
                window.location.href = url;
            }
        };
    }
}

/**
 * Get navigation link based on page context
 * @param {string} page - The page identifier
 * @returns {string} - The URL to navigate to
 */
function getNavUrl(page) {
    // Check if user is logged in (has session)
    const isLoggedIn = checkUserSession();
    
    // If logged in, use app URLs; otherwise use landing page URLs
    if (isLoggedIn) {
        if (page === 'home' && NAVIGATION_CONFIG.dashboard) {
            return NAVIGATION_CONFIG.dashboard.url;
        }
    }
    
    return NAVIGATION_CONFIG[page]?.url || '#';
}

/**
 * Check if user session exists
 * @returns {boolean}
 */
function checkUserSession() {
    // Try to check if session exists via a lightweight API call
    // For now, check if we're on an app page (savings.html, profile.html, etc.)
    const currentPage = window.location.pathname;
    return currentPage.includes('savings.html') || 
           currentPage.includes('profile.html') || 
           currentPage.includes('edit-savings.html') ||
           currentPage.includes('drawsavemoney.html');
}

/**
 * Normalize path to ensure "Spendy" (not "SPENDY" or "spendy")
 * @param {string} path - The path to normalize
 * @returns {string} - Normalized path
 */
function normalizeSpendyPath(path) {
    if (!path) return path;
    // Replace any case variation of /SPENDY/ or /spendy/ with /Spendy/
    return path.replace(/\/SPENDY\//gi, '/Spendy/').replace(/\/spendy\//gi, '/Spendy/');
}

/**
 * Navigate to a specific page with smooth transition
 * @param {string} page - The page identifier
 */
function navigateTo(page) {
    const url = getNavUrl(page);
    if (url && url !== '#') {
        smoothPageTransition(normalizeSpendyPath(url));
    }
}

/**
 * Enhanced redirect function with smooth transition
 * @param {string} url - The URL to redirect to
 */
function smoothRedirect(url) {
    if (url) {
        smoothPageTransition(normalizeSpendyPath(url));
    }
}

/**
 * Create navigation menu HTML for landing pages
 * @returns {string} - HTML string for navigation menu
 */
function createLandingNavMenu() {
    return `
        <nav class="nav">
            <a href="index.html">Home</a>
            <a href="views/aboutus.html">About</a>
            <a href="views/KeyFeatures.html">Features</a>
            <a class="signup-btn" href="views/SignUp.html">Sign up</a>
        </nav>
    `;
}

/**
 * Initialize navigation when DOM is ready
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavigation);
} else {
    initNavigation();
}

/**
 * Initialize page fade-in on load
 */
function initPageFadeIn() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.4s ease-in-out';
    
    // Fade in after a brief delay
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 50);
}

// Initialize page fade-in when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPageFadeIn);
} else {
    initPageFadeIn();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        NAVIGATION_CONFIG,
        getNavUrl,
        navigateTo,
        smoothRedirect,
        smoothPageTransition,
        checkUserSession,
        createLandingNavMenu
    };
}

