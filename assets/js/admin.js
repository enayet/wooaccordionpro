/**
 * WooAccordion Pro - Admin JavaScript (Simplified)
 */

(function($) {
    'use strict';

    class WAPAdmin {
        constructor() {
            this.settings = wap_admin || {};
            this.init();
        }

        init() {
            this.initTabs();
            this.initFormHandling();
            this.initResetButton();
        }

        /**
         * Initialize tab functionality
         */
        initTabs() {
            $('.wap-tab-button').on('click', (e) => {
                e.preventDefault();
                const targetTab = $(e.currentTarget).data('tab');
                this.switchTab(targetTab);
            });

            // Handle URL hash for direct tab access
            const hash = window.location.hash.replace('#', '');
            if (hash && $('.wap-tab-button[data-tab="' + hash + '"]').length) {
                this.switchTab(hash);
            }
        }

        /**
         * Switch to a specific tab
         */
        switchTab(tabId) {
            // Update buttons
            $('.wap-tab-button').removeClass('active');
            $('.wap-tab-button[data-tab="' + tabId + '"]').addClass('active');

            // Update panels
            $('.wap-tab-panel').removeClass('active');
            $('.wap-tab-panel[data-panel="' + tabId + '"]').addClass('active');

            // Update URL hash
            window.history.replaceState(null, null, '#' + tabId);
        }

        /**
         * Initialize form handling
         */
        initFormHandling() {
            $('#wap-settings-form').on('submit', (e) => {
                // Try AJAX first, fallback to traditional submission
                if (this.settings.ajax_url && this.settings.nonce) {
                    e.preventDefault();
                    this.saveSettings();
                }
                // If AJAX fails or isn't available, allow traditional form submission
            });

            // Auto-save on certain field changes
            $('input[type="color"]').on('change', () => {
                this.updatePreview();
            });
        }

        /**
         * Save settings via AJAX
         */
        saveSettings() {
            const $form = $('#wap-settings-form');
            const $submitButton = $form.find('input[type="submit"]');
            
            // Show loading state
            this.setLoadingState($submitButton, true);
            
            // Get form data
            let formData = $form.serialize();
            formData += '&action=wap_save_settings&nonce=' + this.settings.nonce;

            $.post(this.settings.ajax_url, formData)
                .done((response) => {
                    this.setLoadingState($submitButton, false);
                    
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                    } else {
                        this.showNotice('error', response.data.message || this.settings.strings.save_error);
                    }
                })
                .fail(() => {
                    this.setLoadingState($submitButton, false);
                    // Fallback to traditional form submission
                    $form.off('submit').submit();
                });
        }

        /**
         * Initialize reset button
         */
        initResetButton() {
            $('#wap-reset-settings').on('click', () => {
                if (confirm('Are you sure you want to reset all settings to defaults?')) {
                    this.resetSettings();
                }
            });
        }

        /**
         * Reset settings to defaults
         */
        resetSettings() {
            const defaults = {
                'wap_enable_accordion': true,
                'wap_auto_expand_first': true,
                'wap_allow_multiple_open': false,
                'wap_show_icons': true,
                'wap_template': 'modern',
                'wap_icon_library': 'css',
                'wap_animation_duration': '300',
                'wap_enable_mobile_gestures': true,
                'wap_header_bg_color': '#f8f9fa',
                'wap_header_text_color': '#495057',
                'wap_active_header_bg_color': '#0073aa',
                'wap_border_color': '#dee2e6'
            };

            // Update form fields
            Object.entries(defaults).forEach(([key, value]) => {
                const $element = $('#' + key);
                
                if ($element.length) {
                    if ($element.is(':checkbox')) {
                        $element.prop('checked', value);
                    } else {
                        $element.val(value);
                    }
                }
            });

            this.showNotice('success', 'Settings reset to defaults');
        }

        /**
         * Update live preview (basic color changes)
         */
        updatePreview() {
            // Simple live preview for color changes
            const headerBg = $('#wap_header_bg_color').val();
            const activeBg = $('#wap_active_header_bg_color').val();
            
            // Update CSS variables if preview exists
            if ($('.wap-preview').length) {
                $('.wap-preview').css({
                    '--wap-header-bg': headerBg,
                    '--wap-active-bg': activeBg
                });
            }
        }

        /**
         * Show admin notice
         */
        showNotice(type, message) {
            // Remove existing notices
            $('.wap-notice').remove();
            
            const notice = $('<div class="wap-notice wap-notice-' + type + '"><p>' + message + '</p></div>');
            
            // Insert after header
            $('.wap-admin-header').after(notice);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notice.fadeOut(() => notice.remove());
            }, 5000);
        }

        /**
         * Set loading state for button
         */
        setLoadingState($button, loading) {
            if (loading) {
                $button.prop('disabled', true);
                $button.data('original-text', $button.val());
                $button.val('Saving...');
                $button.css('opacity', '0.7');
            } else {
                $button.prop('disabled', false);
                $button.val($button.data('original-text') || 'Save Settings');
                $button.css('opacity', '1');
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.wap-admin-wrap').length) {
            new WAPAdmin();
        }
    });

})(jQuery);