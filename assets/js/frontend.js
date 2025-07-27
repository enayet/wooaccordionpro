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
            const toggleStyle = $('.wap-accordion-container').data('toggle-style') || 'plus_minus';

            // Close others if multiple open is not allowed
            if (this.settings.allow_multiple_open !== 'yes' && !isActive) {
                this.closeAllAccordions();
            }

            // Toggle current accordion
            if (isActive) {
                this.closeAccordion($header, $content, $toggle, toggleStyle);
            } else {
                this.openAccordion($header, $content, $toggle, toggleStyle);
            }
        }

        openAccordion($header, $content, $toggle, toggleStyle) {
            $header.addClass('wap-active');
            $content.addClass('wap-active');

            // Update icon based on toggle style
            this.updateToggleIcon($toggle, toggleStyle, true);

            // Set max-height to content height for smooth animation
            const contentHeight = $content.find('.wap-accordion-content-inner')[0].scrollHeight;
            $content.css('max-height', contentHeight + 'px');
        }

        closeAccordion($header, $content, $toggle, toggleStyle) {
            $header.removeClass('wap-active');
            $content.removeClass('wap-active');

            // Update icon based on toggle style
            this.updateToggleIcon($toggle, toggleStyle, false);

            // Reset max-height for animation
            $content.css('max-height', '0px');
        }
        
        
        updateToggleIcon($toggle, style, isActive) {
            // Remove all toggle classes
            $toggle.removeClass('wap-plus wap-minus wap-arrow-down wap-arrow-up wap-chevron-down wap-chevron-up wap-triangle-right wap-triangle-down');

            switch (style) {
                case 'arrow_down':
                    $toggle.addClass('wap-arrow');
                    $toggle.addClass(isActive ? 'wap-arrow-up' : 'wap-arrow-down');
                    break;

                case 'chevron':
                    $toggle.addClass('wap-chevron');
                    $toggle.addClass(isActive ? 'wap-chevron-up' : 'wap-chevron-down');
                    break;

                case 'triangle':
                    $toggle.addClass('wap-triangle');
                    $toggle.addClass(isActive ? 'wap-triangle-down' : 'wap-triangle-right');
                    break;

                case 'plus_minus':
                default:
                    $toggle.addClass(isActive ? 'wap-minus' : 'wap-plus');
                    break;
            }
        }        
        

        closeAllAccordions() {
            const toggleStyle = $('.wap-accordion-container').data('toggle-style') || 'plus_minus';

            $('.wap-accordion-header.wap-active').each((index, element) => {
                const $header = $(element);
                const $content = $header.next('.wap-accordion-content');
                const $toggle = $header.find('.wap-accordion-toggle .wap-icon');
                this.closeAccordion($header, $content, $toggle, toggleStyle);
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