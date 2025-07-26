/**
 * WooAccordion Pro - Frontend JavaScript (Simplified)
 */

(function($) {
    'use strict';

    class WooAccordionPro {
        
        constructor() {
            this.settings = wap_settings || {};
            this.init();
        }

        init() {
            this.setupAccordions();
            this.autoExpandFirst();
        }

        setupAccordions() {
            $('.wap-accordion-header').on('click', (e) => {
                this.handleAccordionClick(e);
            });
        }

        handleAccordionClick(e) {
            e.preventDefault();
            
            const $header = $(e.currentTarget);
            const $content = $header.next('.wap-accordion-content');
            const $toggle = $header.find('.wap-accordion-toggle .wap-icon');
            const isActive = $header.hasClass('wap-active');

            // Close others if multiple open is not allowed
            if (this.settings.allow_multiple_open !== 'yes' && !isActive) {
                this.closeAllAccordions();
            }

            // Toggle current accordion
            if (isActive) {
                this.closeAccordion($header, $content, $toggle);
            } else {
                this.openAccordion($header, $content, $toggle);
            }
        }

        openAccordion($header, $content, $toggle) {
            $header.addClass('wap-active');
            $content.addClass('wap-active');
            
            // Update icon
            $toggle.removeClass('wap-plus').addClass('wap-minus');
            
            // Set max-height to content height for smooth animation
            const contentHeight = $content.find('.wap-accordion-content-inner')[0].scrollHeight;
            $content.css('max-height', contentHeight + 'px');
        }

        closeAccordion($header, $content, $toggle) {
            $header.removeClass('wap-active');
            $content.removeClass('wap-active');
            
            // Update icon
            $toggle.removeClass('wap-minus').addClass('wap-plus');
            
            // Reset max-height for animation
            $content.css('max-height', '0px');
        }

        closeAllAccordions() {
            $('.wap-accordion-header.wap-active').each((index, element) => {
                const $header = $(element);
                const $content = $header.next('.wap-accordion-content');
                const $toggle = $header.find('.wap-accordion-toggle .wap-icon');
                this.closeAccordion($header, $content, $toggle);
            });
        }

        autoExpandFirst() {
            if (this.settings.auto_expand_first === 'yes') {
                const $firstHeader = $('.wap-accordion-header').first();
                if ($firstHeader.length && !$firstHeader.hasClass('wap-active')) {
                    setTimeout(() => {
                        $firstHeader.trigger('click');
                    }, 100);
                }
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.wap-accordion').length > 0) {
            new WooAccordionPro();
        }
    });

})(jQuery);