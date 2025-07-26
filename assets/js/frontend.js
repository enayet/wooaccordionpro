/**
 * WooAccordion Pro - Frontend JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main WooAccordion Pro Class
     */
    class WooAccordionPro {
        constructor() {
            this.settings = wap_frontend.settings || {};
            this.accordionContainer = null;
            this.touchStartY = 0;
            this.touchEndY = 0;
            this.isAnimating = false;
            
            this.init();
        }

        /**
         * Initialize the accordion
         */
        init() {
            this.accordionContainer = document.querySelector('.wap-accordion');
            
            if (!this.accordionContainer) {
                return;
            }

            this.setupEventListeners();
            this.setupTouchGestures();
            this.setupAnalytics();
            this.setupAccessibility();
            
            // Auto-expand first accordion if enabled
            if (this.settings.auto_expand_first === 'yes') {
                this.autoExpandFirst();
            }

            // Trigger ready event
            this.triggerEvent('wap:ready');
        }

        /**
         * Setup click event listeners
         */
        setupEventListeners() {
            const headers = this.accordionContainer.querySelectorAll('.wap-accordion-header');
            
            headers.forEach(header => {
                header.addEventListener('click', (e) => this.handleHeaderClick(e));
                header.addEventListener('keydown', (e) => this.handleKeydown(e));
            });

            // Window resize handler for responsive adjustments
            window.addEventListener('resize', () => this.handleResize());
        }

        /**
         * Handle accordion header click
         */
        handleHeaderClick(event) {
            event.preventDefault();
            
            if (this.isAnimating) {
                return;
            }

            const header = event.currentTarget;
            const accordionItem = header.closest('.wap-accordion-item');
            const content = accordionItem.querySelector('.wap-accordion-content');
            const isActive = header.classList.contains('wap-active');
            const section = accordionItem.dataset.section;

            // Close other accordions if multiple open is not allowed
            if (this.settings.allow_multiple_open !== 'yes' && !isActive) {
                this.closeAllAccordions();
            }

            // Toggle current accordion
            if (isActive) {
                this.closeAccordion(header, content);
                this.trackInteraction(section, 'close');
            } else {
                this.openAccordion(header, content);
                this.trackInteraction(section, 'open');
            }
        }

        /**
         * Handle keyboard navigation
         */
        handleKeydown(event) {
            const header = event.currentTarget;
            
            switch (event.key) {
                case 'Enter':
                case ' ':
                    event.preventDefault();
                    header.click();
                    break;
                case 'ArrowDown':
                    event.preventDefault();
                    this.focusNextHeader(header);
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.focusPreviousHeader(header);
                    break;
                case 'Home':
                    event.preventDefault();
                    this.focusFirstHeader();
                    break;
                case 'End':
                    event.preventDefault();
                    this.focusLastHeader();
                    break;
            }
        }

        /**
         * Open accordion
         */
        openAccordion(header, content) {
            this.isAnimating = true;
            
            // Add active classes
            header.classList.add('wap-active');
            content.classList.add('wap-active');
            
            // Update toggle icon
            this.updateToggleIcon(header, true);
            
            // Handle animation based on type
            this.animateOpen(content, () => {
                this.isAnimating = false;
                this.triggerEvent('wap:accordion:opened', { header, content });
            });
        }

        /**
         * Close accordion
         */
        closeAccordion(header, content) {
            this.isAnimating = true;
            
            // Remove active classes
            header.classList.remove('wap-active');
            content.classList.remove('wap-active');
            
            // Update toggle icon
            this.updateToggleIcon(header, false);
            
            // Handle animation based on type
            this.animateClose(content, () => {
                this.isAnimating = false;
                this.triggerEvent('wap:accordion:closed', { header, content });
            });
        }

        /**
         * Close all accordions
         */
        closeAllAccordions() {
            const activeHeaders = this.accordionContainer.querySelectorAll('.wap-accordion-header.wap-active');
            
            activeHeaders.forEach(header => {
                const accordionItem = header.closest('.wap-accordion-item');
                const content = accordionItem.querySelector('.wap-accordion-content');
                this.closeAccordion(header, content);
            });
        }

        /**
         * Update toggle icon
         */
        updateToggleIcon(header, isOpen) {
            const toggle = header.querySelector('.wap-accordion-toggle i');
            if (toggle) {
                toggle.className = isOpen ? 'fas fa-minus' : 'fas fa-plus';
            }
        }

        /**
         * Animate accordion open
         */
        animateOpen(content, callback) {
            const animationType = this.settings.animation_type || 'slide';
            const duration = parseInt(this.settings.animation_duration) || 300;
            
            switch (animationType) {
                case 'fade':
                    this.animateFadeIn(content, duration, callback);
                    break;
                case 'bounce':
                    this.animateBounceIn(content, duration, callback);
                    break;
                case 'slide':
                default:
                    this.animateSlideDown(content, duration, callback);
                    break;
            }
        }

        /**
         * Animate accordion close
         */
        animateClose(content, callback) {
            const animationType = this.settings.animation_type || 'slide';
            const duration = parseInt(this.settings.animation_duration) || 300;
            
            switch (animationType) {
                case 'fade':
                    this.animateFadeOut(content, duration, callback);
                    break;
                case 'bounce':
                case 'slide':
                default:
                    this.animateSlideUp(content, duration, callback);
                    break;
            }
        }

        /**
         * Slide down animation
         */
        animateSlideDown(content, duration, callback) {
            const inner = content.querySelector('.wap-accordion-content-inner');
            const height = inner.scrollHeight;
            
            content.style.maxHeight = height + 'px';
            
            setTimeout(() => {
                if (callback) callback();
            }, duration);
        }

        /**
         * Slide up animation
         */
        animateSlideUp(content, duration, callback) {
            content.style.maxHeight = '0px';
            
            setTimeout(() => {
                if (callback) callback();
            }, duration);
        }

        /**
         * Fade in animation
         */
        animateFadeIn(content, duration, callback) {
            const inner = content.querySelector('.wap-accordion-content-inner');
            const height = inner.scrollHeight;
            
            content.style.maxHeight = height + 'px';
            content.style.opacity = '1';
            
            setTimeout(() => {
                if (callback) callback();
            }, duration);
        }

        /**
         * Fade out animation
         */
        animateFadeOut(content, duration, callback) {
            content.style.opacity = '0';
            content.style.maxHeight = '0px';
            
            setTimeout(() => {
                if (callback) callback();
            }, duration);
        }

        /**
         * Bounce in animation
         */
        animateBounceIn(content, duration, callback) {
            const inner = content.querySelector('.wap-accordion-content-inner');
            const height = inner.scrollHeight;
            
            content.style.maxHeight = height + 'px';
            
            setTimeout(() => {
                if (callback) callback();
            }, duration);
        }

        /**
         * Setup touch gestures for mobile
         */
        setupTouchGestures() {
            if (this.settings.enable_touch_gestures !== 'yes') {
                return;
            }

            const headers = this.accordionContainer.querySelectorAll('.wap-accordion-header');
            
            headers.forEach(header => {
                header.addEventListener('touchstart', (e) => this.handleTouchStart(e), { passive: true });
                header.addEventListener('touchend', (e) => this.handleTouchEnd(e), { passive: true });
            });
        }

        /**
         * Handle touch start
         */
        handleTouchStart(event) {
            this.touchStartY = event.touches[0].clientY;
        }

        /**
         * Handle touch end
         */
        handleTouchEnd(event) {
            this.touchEndY = event.changedTouches[0].clientY;
            const swipeDistance = this.touchStartY - this.touchEndY;
            const threshold = 50; // Minimum swipe distance
            
            if (Math.abs(swipeDistance) > threshold) {
                const header = event.currentTarget;
                const accordionItem = header.closest('.wap-accordion-item');
                const content = accordionItem.querySelector('.wap-accordion-content');
                const isActive = header.classList.contains('wap-active');
                
                // Swipe up to close, swipe down to open
                if (swipeDistance > 0 && isActive) {
                    // Swipe up - close
                    this.closeAccordion(header, content);
                } else if (swipeDistance < 0 && !isActive) {
                    // Swipe down - open
                    this.openAccordion(header, content);
                }
            }
        }

        /**
         * Setup analytics tracking
         */
        setupAnalytics() {
            if (this.settings.enable_analytics !== 'yes') {
                return;
            }

            // Track initial page view
            this.trackInteraction('page_view', 'view');
            
            // Track scroll to accordion
            this.setupScrollTracking();
        }

        /**
         * Setup scroll tracking
         */
        setupScrollTracking() {
            let tracked = false;
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !tracked) {
                        this.trackInteraction('accordion_view', 'scroll');
                        tracked = true;
                    }
                });
            }, { threshold: 0.5 });
            
            observer.observe(this.accordionContainer);
        }

        /**
         * Track user interaction
         */
        trackInteraction(section, action) {
            if (this.settings.enable_analytics !== 'yes' || !wap_frontend.ajax_url) {
                return;
            }

            const data = {
                action: 'wap_track_interaction',
                nonce: wap_frontend.nonce,
                product_id: this.settings.product_id,
                section: section,
                action: action,
                timestamp: Date.now(),
                user_agent: navigator.userAgent,
                device_type: this.getDeviceType()
            };

            // Send analytics data
            fetch(wap_frontend.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            }).catch(error => {
                console.warn('WooAccordion Pro: Analytics tracking failed', error);
            });
        }

        /**
         * Get device type
         */
        getDeviceType() {
            const width = window.innerWidth;
            
            if (width <= 480) return 'mobile';
            if (width <= 768) return 'tablet';
            return 'desktop';
        }

        /**
         * Setup accessibility features
         */
        setupAccessibility() {
            const headers = this.accordionContainer.querySelectorAll('.wap-accordion-header');
            
            headers.forEach((header, index) => {
                const accordionItem = header.closest('.wap-accordion-item');
                const content = accordionItem.querySelector('.wap-accordion-content');
                const contentId = 'wap-content-' + index;
                const headerId = 'wap-header-' + index;
                
                // Set ARIA attributes
                header.setAttribute('role', 'button');
                header.setAttribute('aria-expanded', 'false');
                header.setAttribute('aria-controls', contentId);
                header.setAttribute('id', headerId);
                header.setAttribute('tabindex', '0');
                
                content.setAttribute('role', 'region');
                content.setAttribute('aria-labelledby', headerId);
                content.setAttribute('id', contentId);
                
                // Update aria-expanded on state change
                const observer = new MutationObserver(() => {
                    const isExpanded = header.classList.contains('wap-active');
                    header.setAttribute('aria-expanded', isExpanded.toString());
                });
                
                observer.observe(header, { attributes: true, attributeFilter: ['class'] });
            });
        }

        /**
         * Auto expand first accordion
         */
        autoExpandFirst() {
            const firstHeader = this.accordionContainer.querySelector('.wap-accordion-header');
            if (firstHeader && !firstHeader.classList.contains('wap-active')) {
                setTimeout(() => {
                    firstHeader.click();
                }, 100);
            }
        }

        /**
         * Focus navigation helpers
         */
        focusNextHeader(currentHeader) {
            const headers = Array.from(this.accordionContainer.querySelectorAll('.wap-accordion-header'));
            const currentIndex = headers.indexOf(currentHeader);
            const nextHeader = headers[currentIndex + 1] || headers[0];
            nextHeader.focus();
        }

        focusPreviousHeader(currentHeader) {
            const headers = Array.from(this.accordionContainer.querySelectorAll('.wap-accordion-header'));
            const currentIndex = headers.indexOf(currentHeader);
            const prevHeader = headers[currentIndex - 1] || headers[headers.length - 1];
            prevHeader.focus();
        }

        focusFirstHeader() {
            const firstHeader = this.accordionContainer.querySelector('.wap-accordion-header');
            if (firstHeader) firstHeader.focus();
        }

        focusLastHeader() {
            const headers = this.accordionContainer.querySelectorAll('.wap-accordion-header');
            const lastHeader = headers[headers.length - 1];
            if (lastHeader) lastHeader.focus();
        }

        /**
         * Handle window resize
         */
        handleResize() {
            // Recalculate heights for open accordions
            const activeContents = this.accordionContainer.querySelectorAll('.wap-accordion-content.wap-active');
            
            activeContents.forEach(content => {
                const inner = content.querySelector('.wap-accordion-content-inner');
                content.style.maxHeight = inner.scrollHeight + 'px';
            });
        }

        /**
         * Trigger custom event
         */
        triggerEvent(eventName, detail = {}) {
            const event = new CustomEvent(eventName, {
                detail: detail,
                bubbles: true,
                cancelable: true
            });
            
            this.accordionContainer.dispatchEvent(event);
        }

        /**
         * Public API methods
         */
        openAll() {
            const headers = this.accordionContainer.querySelectorAll('.wap-accordion-header:not(.wap-active)');
            headers.forEach(header => header.click());
        }

        closeAll() {
            this.closeAllAccordions();
        }

        toggle(section) {
            const accordionItem = this.accordionContainer.querySelector(`[data-section="${section}"]`);
            if (accordionItem) {
                const header = accordionItem.querySelector('.wap-accordion-header');
                header.click();
            }
        }
    }

    /**
     * Initialize when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize WooAccordion Pro
        window.WooAccordionPro = new WooAccordionPro();
        
        // Expose public API
        window.wapAPI = {
            openAll: () => window.WooAccordionPro.openAll(),
            closeAll: () => window.WooAccordionPro.closeAll(),
            toggle: (section) => window.WooAccordionPro.toggle(section)
        };
    });

})(jQuery || window.jQuery || function(sel, ctx) {
    // Minimal jQuery fallback for basic selector functionality
    return (ctx || document).querySelectorAll(sel);
});