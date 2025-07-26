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
            this.initCustomTabs();
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
        
        
        
        /**
         * Initialize custom tabs functionality
         */
        initCustomTabs() {
            
    // Check if we're on a page with custom tabs functionality
    if (!document.querySelector('#wap-add-custom-tab')) {
        console.log('Custom tabs elements not found, skipping initialization');
        return;
    }            
            
            
            // Only initialize if we're on the custom tabs section
            if (!$('#wap-add-custom-tab').length) {
                return; // Exit if custom tabs elements aren't present
            }

            // Add new tab button
            $('#wap-add-custom-tab').on('click', () => {
                this.openTabEditor();
            });

            // Edit tab buttons (use delegated events for dynamic content)
            $(document).on('click', '.wap-edit-tab', (e) => {
                const tabId = $(e.currentTarget).data('tab-id');
                this.openTabEditor(tabId);
            });

            // Delete tab buttons (use delegated events)
            $(document).on('click', '.wap-delete-tab', (e) => {
                const tabId = $(e.currentTarget).data('tab-id');
                this.deleteCustomTab(tabId);
            });

            // Modal close buttons
            $(document).on('click', '.wap-modal-close', () => {
                this.closeTabEditor();
            });

            // Save tab button
            $(document).on('click', '#wap-save-custom-tab', () => {
                this.saveCustomTab();
            });

            // Close modal on outside click
            $(document).on('click', '#wap-tab-editor-modal', (e) => {
                if (e.target.id === 'wap-tab-editor-modal') {
                    this.closeTabEditor();
                }
            });

            // Close modal on Escape key
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && $('#wap-tab-editor-modal').is(':visible')) {
                    this.closeTabEditor();
                }
            });
        }

        /**
         * Open tab editor modal
         */
        openTabEditor(tabId = null) {
            const $modal = $('#wap-tab-editor-modal');
            const $form = $('#wap-custom-tab-form');
            
            // Check if modal exists
            if (!$modal.length) {
                console.error('Custom tab modal not found in DOM');
                return;
            }
            
            // Check if form exists
            if (!$form.length) {
                console.error('Custom tab form not found in DOM');
                return;
            }
            
            // Reset form
            $form[0].reset();
            $('#wap-tab-id').val('');
            $('.wap-modal-message').hide();

            if (tabId) {
                // Edit existing tab
                $('#wap-modal-title').text('Edit Custom Tab');
                this.loadTabData(tabId);
            } else {
                // Add new tab
                $('#wap-modal-title').text('Add New Tab');
                $('#wap-tab-priority').val(50);
                $('#wap-tab-enabled').prop('checked', true);
            }

            $modal.show();
            
            // Focus on title field if it exists
            setTimeout(() => {
                $('#wap-tab-title').focus();
            }, 100);
        }

        /**
         * Close tab editor modal
         */
        closeTabEditor() {
            $('#wap-tab-editor-modal').hide();
        }

        /**
         * Load tab data for editing
         */
        loadTabData(tabId) {
            this.setLoadingState($('#wap-save-custom-tab'), true);

            $.post(this.settings.ajax_url, {
                action: 'wap_get_custom_tab',
                nonce: this.settings.nonce,
                tab_id: tabId
            })
            .done((response) => {
                this.setLoadingState($('#wap-save-custom-tab'), false);

                if (response.success && response.data.tab_data) {
                    const tabData = response.data.tab_data;
                    
                    $('#wap-tab-id').val(tabId);
                    $('#wap-tab-title').val(tabData.title || '');
                    $('#wap-tab-content').val(tabData.content || '');
                    $('#wap-tab-priority').val(tabData.priority || 50);
                    $('#wap-tab-enabled').prop('checked', tabData.enabled || false);

                    // Set conditions
                    if (tabData.conditions) {
                        if (tabData.conditions.categories) {
                            $('#wap-tab-categories').val(tabData.conditions.categories);
                        }
                        if (tabData.conditions.user_roles) {
                            $('#wap-tab-user-roles').val(tabData.conditions.user_roles);
                        }
                        if (tabData.conditions.product_types) {
                            $('#wap-tab-product-types').val(tabData.conditions.product_types);
                        }
                    }
                } else {
                    this.showModalMessage('error', response.data?.message || 'Failed to load tab data');
                }
            })
            .fail(() => {
                this.setLoadingState($('#wap-save-custom-tab'), false);
                this.showModalMessage('error', 'Network error occurred');
            });
        }

        /**
         * Save custom tab
         */
        saveCustomTab() {
            const $form = $('#wap-custom-tab-form');
            const $saveButton = $('#wap-save-custom-tab');
            
            // Validate required fields
            if (!$('#wap-tab-title').val().trim()) {
                this.showModalMessage('error', 'Tab title is required');
                $('#wap-tab-title').focus();
                return;
            }

            this.setLoadingState($saveButton, true);
            $('.wap-modal-message').hide();

            // Serialize form data
            const formData = $form.serialize();
            const postData = formData + '&action=wap_save_custom_tab&nonce=' + this.settings.nonce;

            $.post(this.settings.ajax_url, postData)
                .done((response) => {
                    this.setLoadingState($saveButton, false);

                    if (response.success) {
                        this.showModalMessage('success', response.data.message);
                        
                        // Close modal after short delay and reload page
                        setTimeout(() => {
                            this.closeTabEditor();
                            window.location.reload();
                        }, 1500);
                    } else {
                        this.showModalMessage('error', response.data?.message || 'Failed to save tab');
                    }
                })
                .fail(() => {
                    this.setLoadingState($saveButton, false);
                    this.showModalMessage('error', 'Network error occurred');
                });
        }

        /**
         * Delete custom tab
         */
        deleteCustomTab(tabId) {
            if (!confirm('Are you sure you want to delete this custom tab? This action cannot be undone.')) {
                return;
            }

            const $tabRow = $(`.wap-tab-row[data-tab-id="${tabId}"]`);
            $tabRow.addClass('wap-tabs-loading');

            $.post(this.settings.ajax_url, {
                action: 'wap_delete_custom_tab',
                nonce: this.settings.nonce,
                tab_id: tabId
            })
            .done((response) => {
                if (response.success) {
                    $tabRow.fadeOut(() => {
                        $tabRow.remove();
                        
                        // Show no tabs message if no tabs remain
                        if ($('.wap-tab-row').length === 0) {
                            $('.wap-tabs-table').hide();
                            $('.wap-no-tabs-message').show();
                        }
                    });
                    
                    this.showNotice('success', response.data.message);
                } else {
                    $tabRow.removeClass('wap-tabs-loading');
                    this.showNotice('error', response.data?.message || 'Failed to delete tab');
                }
            })
            .fail(() => {
                $tabRow.removeClass('wap-tabs-loading');
                this.showNotice('error', 'Network error occurred');
            });
        }

        /**
         * Show message in modal
         */
        showModalMessage(type, message) {
            const $message = $('.wap-modal-message');
            $message.removeClass('success error').addClass(type);
            $message.text(message).show();
            
            // Auto-hide error messages
            if (type === 'error') {
                setTimeout(() => {
                    $message.fadeOut();
                }, 5000);
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