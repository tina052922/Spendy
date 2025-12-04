/**
 * Universal Smooth Page Transitions
 * Provides smooth fade and slide effects for all page navigations
 */

(function() {
    'use strict';
    
    /**
     * Smooth page transition effect
     * @param {string} url - The URL to navigate to
     * @param {number} duration - Transition duration in milliseconds (default: 300)
     */
    window.smoothPageTransition = function(url, duration = 300) {
        if (!url || url === '#' || url.startsWith('javascript:')) return;
        
        // Normalize path
        url = url.replace(/\/SPENDY\//gi, '/Spendy/').replace(/\/spendy\//gi, '/Spendy/');
        
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
        
        // Fade out current page with slight upward movement
        document.body.style.transition = `opacity ${duration}ms ease-in-out, transform ${duration}ms ease-in-out`;
        document.body.style.opacity = '0';
        document.body.style.transform = 'translateY(-10px)';
        
        // Fade in overlay
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
        });
        
        // Navigate after transition
        setTimeout(() => {
            window.location.href = url;
        }, duration);
    };
    
    /**
     * Enhanced redirect function with smooth transition
     * @param {string} url - The URL to redirect to
     */
    window.smoothRedirect = function(url) {
        if (url) {
            window.smoothPageTransition(url);
        }
    };
    
    /**
     * Initialize smooth transitions for all links
     */
    function initSmoothTransitions() {
        // Add smooth transitions to all HTML links
        document.querySelectorAll('a[href$=".html"]').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !href.startsWith('http')) {
                    // Don't prevent default for same-page anchors
                    if (!href.includes('#')) {
                        e.preventDefault();
                        window.smoothPageTransition(href);
                    }
                }
            });
        });
        
        // Override onclick handlers that use window.location.href
        document.querySelectorAll('[onclick*="window.location.href"]').forEach(element => {
            const originalOnclick = element.getAttribute('onclick');
            if (originalOnclick) {
                element.removeAttribute('onclick');
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    const match = originalOnclick.match(/window\.location\.href\s*=\s*['"]([^'"]+)['"]/);
                    if (match && match[1]) {
                        window.smoothPageTransition(match[1]);
                    }
                });
            }
        });
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
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initPageFadeIn();
            initSmoothTransitions();
        });
    } else {
        initPageFadeIn();
        initSmoothTransitions();
    }
})();

