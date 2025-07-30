/**
 * WooAccordion Pro - Admin JavaScript (Simplified - No TinyMCE)
 * Replace your existing admin.js file with this version
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
            
            $(".wap-admin-wrap .notice.notice-error").remove();
            
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
            });

            // Auto-save on certain field changes
            $('input[type="color"]').on('change', () => {
                this.updatePreview();
            });

            // Individual color reset buttons
            $(document).on('click', '.wap-reset-color', (e) => {
                e.preventDefault();
                const $button = $(e.currentTarget);
                const fieldId = $button.data('field');
                const defaultValue = $button.data('default');

                $('#' + fieldId).val(defaultValue);
                this.updatePreview();
                this.showNotice('success', 'Color reset to default');
            });

            // Template change handler - reset colors to template defaults
            $('#wap_template').on('change', (e) => {
                const template = $(e.target).val();
                this.resetColorsToTemplate(template);
            });
        }
        
        
        /**
         * Reset colors to template defaults
         */
        resetColorsToTemplate(template) {
            const templateDefaults = {
                'modern': {
                    'wap_header_bg_color': '#f8f9fa',
                    'wap_header_text_color': '#495057',
                    'wap_active_header_bg_color': '#0073aa',
                    'wap_active_header_text_color': '#ffffff',
                    'wap_border_color': '#dee2e6'
                },
                'minimal': {
                    'wap_header_bg_color': '#ffffff',
                    'wap_header_text_color': '#333333',
                    'wap_active_header_bg_color': '#6366f1',
                    'wap_active_header_text_color': '#ffffff',
                    'wap_border_color': '#e5e7eb'
                },
                'classic': {
                    'wap_header_bg_color': '#f1f1f1',
                    'wap_header_text_color': '#333333',
                    'wap_active_header_bg_color': '#333333',
                    'wap_active_header_text_color': '#ffffff',
                    'wap_border_color': '#cccccc'
                }
            };

            const defaults = templateDefaults[template] || templateDefaults['modern'];

            // Update color fields
            Object.entries(defaults).forEach(([field, color]) => {
                $('#' + field).val(color);

                // Update reset button default values
                $('.wap-reset-color[data-field="' + field + '"]').attr('data-default', color);
            });

            this.updatePreview();
            this.showNotice('info', 'Colors updated to match template defaults');
        }        

        /**
         * Save settings via AJAX
         */
        saveSettings() {
            const $form = $('#wap-settings-form');
            const $submitButton = $form.find('input[type="submit"]');
            
            this.setLoadingState($submitButton, true);
            
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
         * Update live preview
         */
        updatePreview() {
            const headerBg = $('#wap_header_bg_color').val();
            const activeBg = $('#wap_active_header_bg_color').val();
            
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
            $('.wap-notice').remove();
            
            const notice = $('<div class="wap-notice wap-notice-' + type + '"><p>' + message + '</p></div>');
            $('.wap-admin-header').after(notice);
            
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
            if (!$('#wap-add-custom-tab').length) {
                return;
            }

            $('#wap-add-custom-tab').on('click', () => {
                this.openTabEditor();
            });

            $(document).on('click', '.wap-edit-tab', (e) => {
                const tabId = $(e.currentTarget).data('tab-id');
                this.openTabEditor(tabId);
            });

            $(document).on('click', '.wap-delete-tab', (e) => {
                const tabId = $(e.currentTarget).data('tab-id');
                this.deleteCustomTab(tabId);
            });

            $(document).on('click', '.wap-modal-close', () => {
                this.closeTabEditor();
            });

            $(document).on('click', '#wap-save-custom-tab', () => {
                this.saveCustomTab();
            });

            $(document).on('click', '#wap-tab-editor-modal', (e) => {
                if (e.target.id === 'wap-tab-editor-modal') {
                    this.closeTabEditor();
                }
            });

            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && $('#wap-tab-editor-modal').is(':visible')) {
                    this.closeTabEditor();
                }
            });
        }

        /**
         * Open tab editor modal - SIMPLIFIED VERSION
         */
        openTabEditor(tabId = null) {
            const $modal = $('#wap-tab-editor-modal');
            const $form = $('#wap-custom-tab-form');
            
            if (!$modal.length || !$form.length) {
                console.error('Modal or form not found');
                return;
            }
            
            // Reset form
            $form[0].reset();
            $('#wap-tab-id').val('');
            $('.wap-modal-message').hide();

            if (tabId) {
                $('#wap-modal-title').text('Edit Custom Tab');
                this.loadTabData(tabId);
            } else {
                $('#wap-modal-title').text('Add New Tab');
                $('#wap-tab-priority').val(50);
                $('#wap-tab-enabled').prop('checked', true);
                
                // Clear editor content for new tab
                this.setEditorContent('');
            }

            // Show modal
            $modal.show();
            
            // Focus on title field            
            // Initialize simple editor formatting buttons
            setTimeout(() => {
                $('#wap-tab-title').focus();
                this.initSimpleEditor();
                if (!tabId) {
                    $('#wap-tab-title').focus();
                }
            }, 100);            
            
                        
        }

        /**
         * Close tab editor modal - SIMPLIFIED VERSION
         */
        closeTabEditor() {
            $('#wap-tab-editor-modal').hide();
        }

        /**
         * Set content in editor - SIMPLIFIED VERSION (REPLACE TinyMCE)
         */
        setEditorContent(content) {
            $('#wap-tab-content').val(content || '');
        }

        /**
         * Get content from editor - SIMPLIFIED VERSION (REPLACE TinyMCE)
         */
        getEditorContent() {
            return $('#wap-tab-content').val() || '';
        }

        /**
         * Load tab data for editing - SIMPLIFIED VERSION
         */
        loadTabData(tabId) {
            const $modalBody = $('.wap-modal-body');
            const $form = $('#wap-custom-tab-form');
            
            $modalBody.addClass('wap-loading');
            $form.css('opacity', '0.6');
            this.showModalMessage('info', 'Loading tab data...');

            $.post(this.settings.ajax_url, {
                action: 'wap_get_custom_tab',
                nonce: this.settings.nonce,
                tab_id: tabId
            })
            .done((response) => {
                $modalBody.removeClass('wap-loading');
                $form.css('opacity', '1');
                $('.wap-modal-message').hide();

                if (response.success && response.data.tab_data) {
                    const tabData = response.data.tab_data;
                    
                    // Populate basic fields
                    $('#wap-tab-id').val(tabId);
                    $('#wap-tab-title').val(tabData.title || '');
                    $('#wap-tab-priority').val(tabData.priority || 50);
                    $('#wap-tab-enabled').prop('checked', !!tabData.enabled);

                    // Set content using simplified method
                    this.setEditorContent(tabData.content || '');

                    // Populate conditions
                    if (tabData.conditions) {
                        if (tabData.conditions.categories && Array.isArray(tabData.conditions.categories)) {
                            $('#wap-tab-categories').val(tabData.conditions.categories.map(String));
                        }
                        if (tabData.conditions.user_roles && Array.isArray(tabData.conditions.user_roles)) {
                            $('#wap-tab-user-roles').val(tabData.conditions.user_roles);
                        }
                        if (tabData.conditions.product_types && Array.isArray(tabData.conditions.product_types)) {
                            $('#wap-tab-product-types').val(tabData.conditions.product_types);
                        }
                    }

                    console.log('Tab data loaded successfully');
                } else {
                    this.showModalMessage('error', response.data?.message || 'Failed to load tab data');
                }
            })
            .fail((xhr, status, error) => {
                $modalBody.removeClass('wap-loading');
                $form.css('opacity', '1');
                this.showModalMessage('error', 'Network error occurred: ' + error);
            });
        }

        /**
         * Save custom tab - SIMPLIFIED VERSION
         */
        saveCustomTab() {
            const $saveButton = $('#wap-save-custom-tab');
            
            const title = $('#wap-tab-title').val().trim();
            if (!title) {
                this.showModalMessage('error', 'Tab title is required');
                $('#wap-tab-title').focus();
                return;
            }

            this.setLoadingState($saveButton, true);
            $('.wap-modal-message').hide();

            const tabId = $('#wap-tab-id').val();
            const formData = {
                action: 'wap_save_custom_tab',
                nonce: this.settings.nonce,
                tab_id: tabId,
                tab_data: {
                    title: title,
                    content: this.getEditorContent(), // Get content using simplified method
                    priority: parseInt($('#wap-tab-priority').val()) || 50,
                    enabled: $('#wap-tab-enabled').is(':checked'),
                    conditions: {
                        categories: $('#wap-tab-categories').val() || [],
                        user_roles: $('#wap-tab-user-roles').val() || [],
                        product_types: $('#wap-tab-product-types').val() || []
                    }
                }
            };

            $.post(this.settings.ajax_url, formData)
                .done((response) => {
                    this.setLoadingState($saveButton, false);

                    if (response.success) {
                        this.showModalMessage('success', response.data.message);
                        
                        setTimeout(() => {
                            this.closeTabEditor();
                            window.location.reload();
                        }, 1500);
                    } else {
                        this.showModalMessage('error', response.data?.message || 'Failed to save tab');
                    }
                })
                .fail((xhr, status, error) => {
                    this.setLoadingState($saveButton, false);
                    this.showModalMessage('error', 'Network error occurred: ' + error);
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
            $message.removeClass('success error info').addClass(type);
            $message.text(message).show();
            
            if (type === 'error' || type === 'info') {
                setTimeout(() => {
                    $message.fadeOut();
                }, type === 'info' ? 3000 : 5000);
            }
        }
                  
                  

                  
                  
        initSimpleEditor() {
            // Remove existing event handlers to prevent duplicates
            $('.wap-format-btn, .wap-link-btn').off('click.wap-editor');

            // Simple formatting buttons
            $('.wap-format-btn').on('click.wap-editor', function(e) {
                e.preventDefault();
                var tag = $(this).data('tag');
                var textarea = $('#wap-tab-content')[0];
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                var selectedText = textarea.value.substring(start, end);
                var replacement = '';

                if (tag === 'ul') {
                    replacement = '<ul>\n<li>' + (selectedText || 'List item') + '</li>\n</ul>';
                } else {
                    replacement = '<' + tag + '>' + (selectedText || 'Text') + '</' + tag + '>';
                }

                textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
                textarea.focus();

                // Set cursor position after the inserted text
                var newPos = start + replacement.length;
                textarea.setSelectionRange(newPos, newPos);
            });

            // Link button
            $('.wap-link-btn').on('click.wap-editor', function(e) {
                e.preventDefault();
                var url = prompt('Enter URL:');
                if (url) {
                    var textarea = $('#wap-tab-content')[0];
                    var start = textarea.selectionStart;
                    var end = textarea.selectionEnd;
                    var selectedText = textarea.value.substring(start, end);
                    var linkText = selectedText || 'Link text';
                    var replacement = '<a href="' + url + '">' + linkText + '</a>';

                    textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
                    textarea.focus();

                    // Set cursor position after the inserted text
                    var newPos = start + replacement.length;
                    textarea.setSelectionRange(newPos, newPos);
                }
            });

            console.log('Simple editor initialized');
        }                  
                  
                  
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.wap-admin-wrap').length) {
            new WAPAdmin();
        }
    });

})(jQuery);