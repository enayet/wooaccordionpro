/**
 * WooAccordion Pro - Admin JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main Admin Class
     */
    class WAPAdmin {
        constructor() {
            this.settings = wap_admin || {};
            this.currentTab = 'general';
            this.isPreviewOpen = false;
            
            this.init();
        }

        /**
         * Initialize admin functionality
         */
        init() {
            this.initTabs();
            this.initFormHandling();
            this.initPreview();
            this.initColorPickers();
            this.initSettingsReset();
            this.initLivePreview();
            this.initTooltips();
            this.bindEvents();
        }

        /**
         * Initialize tab functionality
         */
        initTabs() {
            const tabButtons = document.querySelectorAll('.wap-tab-button');
            const tabPanels = document.querySelectorAll('.wap-tab-panel');

            tabButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetTab = button.getAttribute('data-tab');
                    this.switchTab(targetTab);
                });
            });

            // Handle URL hash for direct tab access
            const hash = window.location.hash.replace('#', '');
            if (hash && document.querySelector(`[data-tab="${hash}"]`)) {
                this.switchTab(hash);
            }
        }

        /**
         * Switch to a specific tab
         */
        switchTab(tabId) {
            // Update buttons
            document.querySelectorAll('.wap-tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');

            // Update panels
            document.querySelectorAll('.wap-tab-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            document.querySelector(`[data-panel="${tabId}"]`).classList.add('active');

            this.currentTab = tabId;
            
            // Update URL hash
            window.history.replaceState(null, null, `#${tabId}`);

            // Trigger tab change event
            this.triggerEvent('wap:tab:changed', { tab: tabId });
        }

        /**
         * Initialize form handling
         */
        initFormHandling() {
            const form = document.getElementById('wap-settings-form');
            if (!form) return;

            // Auto-save functionality
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.type === 'color') {
                    input.addEventListener('change', () => this.handleColorChange(input));
                } else if (input.type === 'checkbox') {
                    input.addEventListener('change', () => this.handleCheckboxChange(input));
                } else {
                    input.addEventListener('change', () => this.handleInputChange(input));
                }
            });

            // Form submission
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        /**
         * Handle form submission
         */
        handleFormSubmit(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const submitButton = form.querySelector('input[type="submit"]');
            
            // Show loading state
            this.setLoadingState(submitButton, true);
            
            // Convert FormData to URLSearchParams for AJAX
            const params = new URLSearchParams();
            for (let [key, value] of formData.entries()) {
                params.append(key, value);
            }

            // Add action for WordPress
            params.append('action', 'wap_save_settings');
            params.append('nonce', this.settings.nonce);

            fetch(this.settings.ajax_url, {
                method: 'POST',
                body: params
            })
            .then(response => response.json())
            .then(data => {
                this.setLoadingState(submitButton, false);
                
                if (data.success) {
                    this.showNotice('success', this.settings.strings.save_success);
                    this.triggerEvent('wap:settings:saved', data);
                } else {
                    this.showNotice('error', data.message || this.settings.strings.save_error);
                }
            })
            .catch(error => {
                this.setLoadingState(submitButton, false);
                this.showNotice('error', this.settings.strings.save_error);
                console.error('WAPAdmin: Save error', error);
            });
        }

        /**
         * Handle color input changes
         */
        handleColorChange(input) {
            const value = input.value;
            
            // Update any live preview
            this.updateLivePreview(input.name, value);
            
            // Trigger change event
            this.triggerEvent('wap:setting:changed', {
                setting: input.name,
                value: value,
                type: 'color'
            });
        }

        /**
         * Handle checkbox changes
         */
        handleCheckboxChange(input) {
            const value = input.checked ? 'yes' : 'no';
            
            // Update any live preview
            this.updateLivePreview(input.name, value);
            
            // Handle dependencies
            this.handleSettingDependencies(input.name, value);
            
            // Trigger change event
            this.triggerEvent('wap:setting:changed', {
                setting: input.name,
                value: value,
                type: 'checkbox'
            });
        }

        /**
         * Handle general input changes
         */
        handleInputChange(input) {
            const value = input.value;
            
            // Update any live preview
            this.updateLivePreview(input.name, value);
            
            // Handle dependencies
            this.handleSettingDependencies(input.name, value);
            
            // Trigger change event
            this.triggerEvent('wap:setting:changed', {
                setting: input.name,
                value: value,
                type: input.type
            });
        }

        /**
         * Handle setting dependencies
         */
        handleSettingDependencies(settingName, value) {
            const dependencies = {
                'wap_enable_accordion': {
                    'no': ['wap_animation_type', 'wap_auto_expand_first', 'wap_layout_template'],
                    'yes': []
                },
                'wap_show_icons': {
                    'no': ['wap_icon_library'],
                    'yes': []
                },
                'wap_enable_analytics': {
                    'no': ['wap_analytics_retention'],
                    'yes': []
                }
            };

            if (dependencies[settingName]) {
                const disabledSettings = dependencies[settingName][value] || [];
                
                disabledSettings.forEach(depSetting => {
                    const element = document.getElementById(depSetting);
                    if (element) {
                        element.disabled = true;
                        element.closest('.wap-form-field').style.opacity = '0.5';
                    }
                });

                // Enable other settings
                Object.values(dependencies[settingName]).flat().forEach(depSetting => {
                    if (!disabledSettings.includes(depSetting)) {
                        const element = document.getElementById(depSetting);
                        if (element) {
                            element.disabled = false;
                            element.closest('.wap-form-field').style.opacity = '1';
                        }
                    }
                });
            }
        }

        /**
         * Initialize color pickers
         */
        initColorPickers() {
            const colorInputs = document.querySelectorAll('input[type="color"]');
            
            colorInputs.forEach(input => {
                // Wrap color input with preview
                this.wrapColorInput(input);
            });
        }

        /**
         * Wrap color input with preview
         */
        wrapColorInput(input) {
            const wrapper = document.createElement('div');
            wrapper.className = 'wap-color-input-wrapper';
            wrapper.style.cssText = 'display: flex; align-items: center; gap: 0.5rem;';
            
            const preview = document.createElement('div');
            preview.className = 'wap-color-preview';
            preview.style.cssText = `
                width: 20px; 
                height: 20px; 
                border-radius: 4px; 
                border: 1px solid #d1d5db;
                background-color: ${input.value};
            `;
            
            const valueDisplay = document.createElement('span');
            valueDisplay.className = 'wap-color-value';
            valueDisplay.textContent = input.value.toUpperCase();
            valueDisplay.style.cssText = 'font-family: monospace; font-size: 0.9rem; color: #6b7280;';
            
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            wrapper.appendChild(preview);
            wrapper.appendChild(valueDisplay);
            
            // Update preview on change
            input.addEventListener('input', () => {
                preview.style.backgroundColor = input.value;
                valueDisplay.textContent = input.value.toUpperCase();
            });
        }

        /**
         * Initialize preview functionality
         */
        initPreview() {
            const previewButton = document.getElementById('wap-preview-accordion');
            const modal = document.getElementById('wap-preview-modal');
            const closeButton = modal?.querySelector('.wap-modal-close');
            
            if (previewButton) {
                previewButton.addEventListener('click', () => this.openPreview());
            }
            
            if (closeButton) {
                closeButton.addEventListener('click', () => this.closePreview());
            }
            
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.closePreview();
                    }
                });
            }
        }

        /**
         * Open preview modal
         */
        openPreview() {
            const modal = document.getElementById('wap-preview-modal');
            const content = document.getElementById('wap-preview-content');
            
            if (!modal || !content) return;
            
            this.isPreviewOpen = true;
            modal.style.display = 'flex';
            content.innerHTML = this.settings.strings.preview_loading;
            
            // Add FontAwesome if not already loaded
            if (!document.getElementById('wap-preview-fontawesome')) {
                const fontAwesome = document.createElement('link');
                fontAwesome.id = 'wap-preview-fontawesome';
                fontAwesome.rel = 'stylesheet';
                fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
                document.head.appendChild(fontAwesome);
            }
            
            // Generate preview content
            this.generatePreviewContent()
                .then(html => {
                    content.innerHTML = html;
                    this.initPreviewAccordion();
                })
                .catch(error => {
                    content.innerHTML = '<p>Error loading preview</p>';
                    console.error('Preview error:', error);
                });
        }

        /**
         * Close preview modal
         */
        closePreview() {
            const modal = document.getElementById('wap-preview-modal');
            if (modal) {
                modal.style.display = 'none';
                this.isPreviewOpen = false;
            }
        }

        /**
         * Generate preview content
         */
        async generatePreviewContent() {
            const settings = this.getCurrentSettings();
            
            const previewHTML = `
                <div class="wap-accordion-container wap-template-${settings.layout_template}" style="
                    --wap-header-bg: ${settings.header_bg_color};
                    --wap-header-text: ${settings.header_text_color};
                    --wap-active-header-bg: ${settings.active_header_bg_color};
                    --wap-active-header-text: ${settings.active_header_text_color};
                    --wap-content-bg: ${settings.content_bg_color};
                    --wap-border-color: ${settings.border_color};
                    --wap-animation-duration: ${settings.animation_duration}ms;
                ">
                    <div class="wap-accordion">
                        <div class="wap-accordion-item">
                            <div class="wap-accordion-header ${settings.auto_expand_first === 'yes' ? 'wap-active' : ''}" data-target="description">
                                ${settings.show_icons === 'yes' ? '<span class="wap-accordion-icon"><i class="fas fa-align-left"></i></span>' : ''}
                                <span class="wap-accordion-title">Product Description</span>
                                <span class="wap-accordion-toggle">
                                    <i class="fas fa-${settings.auto_expand_first === 'yes' ? 'minus' : 'plus'}"></i>
                                </span>
                            </div>
                            <div class="wap-accordion-content ${settings.auto_expand_first === 'yes' ? 'wap-active' : ''}">
                                <div class="wap-accordion-content-inner">
                                    <p>This is a sample product description showing how your accordion will look with the current settings.</p>
                                    <p>The content expands smoothly with the <strong>${settings.animation_type}</strong> animation over <strong>${settings.animation_duration}ms</strong>.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wap-accordion-item">
                            <div class="wap-accordion-header" data-target="specifications">
                                ${settings.show_icons === 'yes' ? '<span class="wap-accordion-icon"><i class="fas fa-info-circle"></i></span>' : ''}
                                <span class="wap-accordion-title">Specifications</span>
                                <span class="wap-accordion-toggle">
                                    <i class="fas fa-plus"></i>
                                </span>
                            </div>
                            <div class="wap-accordion-content">
                                <div class="wap-accordion-content-inner">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr><td style="padding: 0.5rem; border: 1px solid #dee2e6;"><strong>Material:</strong></td><td style="padding: 0.5rem; border: 1px solid #dee2e6;">Premium Quality</td></tr>
                                        <tr><td style="padding: 0.5rem; border: 1px solid #dee2e6;"><strong>Size:</strong></td><td style="padding: 0.5rem; border: 1px solid #dee2e6;">One Size Fits All</td></tr>
                                        <tr><td style="padding: 0.5rem; border: 1px solid #dee2e6;"><strong>Color:</strong></td><td style="padding: 0.5rem; border: 1px solid #dee2e6;">Multiple Options</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wap-accordion-item">
                            <div class="wap-accordion-header" data-target="reviews">
                                ${settings.show_icons === 'yes' ? '<span class="wap-accordion-icon"><i class="fas fa-star"></i></span>' : ''}
                                <span class="wap-accordion-title">Customer Reviews</span>
                                <span class="wap-accordion-toggle">
                                    <i class="fas fa-plus"></i>
                                </span>
                            </div>
                            <div class="wap-accordion-content">
                                <div class="wap-accordion-content-inner">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                                        <span style="color: #fbbf24;">★★★★★</span>
                                        <span><strong>4.8/5</strong> (124 reviews)</span>
                                    </div>
                                    <p>"Amazing product quality and fast shipping!" - <em>Sarah M.</em></p>
                                    <p>"Exactly as described. Highly recommended!" - <em>John D.</em></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <style>
                    /* Include accordion CSS in preview */
                    .wap-accordion-container {
                        margin: 2rem 0;
                        max-width: 100%;
                    }
                    
                    .wap-accordion {
                        border: 1px solid var(--wap-border-color);
                        border-radius: 8px;
                        overflow: hidden;
                    }
                    
                    .wap-accordion-item {
                        border-bottom: 1px solid var(--wap-border-color);
                    }
                    
                    .wap-accordion-item:last-child {
                        border-bottom: none;
                    }
                    
                    .wap-accordion-header {
                        display: flex;
                        align-items: center;
                        padding: 1rem 1.5rem;
                        background-color: var(--wap-header-bg);
                        color: var(--wap-header-text);
                        cursor: pointer;
                        transition: all 0.3s ease;
                        font-weight: 500;
                        border: none;
                        width: 100%;
                        text-align: left;
                    }
                    
                    .wap-accordion-header:hover {
                        background-color: #e9ecef;
                    }
                    
                    .wap-accordion-header.wap-active {
                        background-color: var(--wap-active-header-bg);
                        color: var(--wap-active-header-text);
                    }
                    
                    .wap-accordion-icon {
                        margin-right: 0.75rem;
                        font-size: 1.1rem;
                        width: 20px;
                        text-align: center;
                    }
                    
                    .wap-accordion-title {
                        flex-grow: 1;
                        margin-right: 1rem;
                    }
                    
                    .wap-accordion-toggle {
                        font-size: 0.9rem;
                        transition: transform 0.3s ease;
                    }
                    
                    .wap-accordion-content {
                        background-color: var(--wap-content-bg);
                        max-height: 0;
                        overflow: hidden;
                        transition: max-height 0.3s ease-out;
                    }
                    
                    .wap-accordion-content.wap-active {
                        max-height: 1000px;
                    }
                    
                    .wap-accordion-content-inner {
                        padding: 1rem 1.5rem;
                        border-top: 1px solid var(--wap-border-color);
                    }
                </style>
            `;
            
            return previewHTML;
        }

        /**
         * Initialize preview accordion functionality
         */
        initPreviewAccordion() {
            const headers = document.querySelectorAll('#wap-preview-content .wap-accordion-header');
            
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const content = header.nextElementSibling;
                    const icon = header.querySelector('.wap-accordion-toggle i');
                    const settings = this.getCurrentSettings();
                    
                    // Close others if multiple open is disabled
                    if (settings.allow_multiple_open !== 'yes') {
                        headers.forEach(otherHeader => {
                            if (otherHeader !== header) {
                                otherHeader.classList.remove('wap-active');
                                otherHeader.nextElementSibling.classList.remove('wap-active');
                                const otherIcon = otherHeader.querySelector('.wap-accordion-toggle i');
                                if (otherIcon) otherIcon.className = 'fas fa-plus';
                            }
                        });
                    }
                    
                    // Toggle current
                    const isActive = header.classList.contains('wap-active');
                    header.classList.toggle('wap-active');
                    content.classList.toggle('wap-active');
                    
                    if (icon) {
                        icon.className = isActive ? 'fas fa-plus' : 'fas fa-minus';
                    }
                });
            });
        }

        /**
         * Get current form settings
         */
        getCurrentSettings() {
            const form = document.getElementById('wap-settings-form');
            if (!form) return {};
            
            const formData = new FormData(form);
            const settings = {};
            
            // Convert FormData to object
            for (let [key, value] of formData.entries()) {
                settings[key.replace('wap_', '')] = value;
            }
            
            // Handle checkboxes that aren't checked
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                const key = checkbox.name.replace('wap_', '');
                if (!settings.hasOwnProperty(key)) {
                    settings[key] = 'no';
                }
            });
            
            return settings;
        }

        /**
         * Initialize live preview updates
         */
        initLivePreview() {
            // This would update any live preview elements on the page
            // For MVP, we'll skip this and focus on the modal preview
        }

        /**
         * Update live preview
         */
        updateLivePreview(settingName, value) {
            if (!this.isPreviewOpen) return;
            
            const previewContainer = document.querySelector('#wap-preview-content .wap-accordion-container');
            if (!previewContainer) return;
            
            // Update CSS variables for colors
            const colorMappings = {
                'wap_header_bg_color': '--wap-header-bg',
                'wap_header_text_color': '--wap-header-text',
                'wap_active_header_bg_color': '--wap-active-header-bg',
                'wap_active_header_text_color': '--wap-active-header-text',
                'wap_content_bg_color': '--wap-content-bg',
                'wap_border_color': '--wap-border-color',
                'wap_animation_duration': '--wap-animation-duration'
            };
            
            if (colorMappings[settingName]) {
                const cssVar = colorMappings[settingName];
                const cssValue = settingName === 'wap_animation_duration' ? `${value}ms` : value;
                previewContainer.style.setProperty(cssVar, cssValue);
            }
            
            // Handle template changes
            if (settingName === 'wap_layout_template') {
                previewContainer.className = `wap-accordion-container wap-template-${value}`;
            }
        }

        /**
         * Initialize settings reset
         */
        initSettingsReset() {
            const resetButton = document.getElementById('wap-reset-settings');
            
            if (resetButton) {
                resetButton.addEventListener('click', () => {
                    if (confirm(this.settings.strings.confirm_reset)) {
                        this.resetToDefaults();
                    }
                });
            }
        }

        /**
         * Reset settings to defaults
         */
        resetToDefaults() {
            const defaults = {
                'wap_enable_accordion': 'yes',
                'wap_auto_expand_first': 'yes',
                'wap_allow_multiple_open': 'no',
                'wap_show_icons': 'yes',
                'wap_icon_library': 'fontawesome',
                'wap_layout_template': 'modern-card',
                'wap_header_bg_color': '#f8f9fa',
                'wap_header_text_color': '#495057',
                'wap_active_header_bg_color': '#6366f1',
                'wap_active_header_text_color': '#ffffff',
                'wap_content_bg_color': '#ffffff',
                'wap_border_color': '#dee2e6',
                'wap_animation_type': 'slide',
                'wap_animation_duration': '300',
                'wap_enable_touch_gestures': 'yes',
                'wap_enable_analytics': 'yes',
                'wap_analytics_retention': '90'
            };
            
            // Update form fields
            Object.entries(defaults).forEach(([key, value]) => {
                const element = document.getElementById(key);
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = value === 'yes';
                    } else {
                        element.value = value;
                    }
                    
                    // Trigger change event
                    element.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            
            this.showNotice('success', 'Settings reset to defaults');
        }

        /**
         * Initialize tooltips
         */
        initTooltips() {
            // Simple tooltip implementation
            const elements = document.querySelectorAll('[data-tooltip]');
            
            elements.forEach(element => {
                this.createTooltip(element);
            });
        }

        /**
         * Create tooltip for element
         */
        createTooltip(element) {
            let tooltip;
            
            element.addEventListener('mouseenter', () => {
                tooltip = document.createElement('div');
                tooltip.className = 'wap-tooltip';
                tooltip.textContent = element.getAttribute('data-tooltip');
                tooltip.style.cssText = `
                    position: absolute;
                    background: #1f2937;
                    color: white;
                    padding: 0.5rem;
                    border-radius: 4px;
                    font-size: 0.8rem;
                    z-index: 1000;
                    pointer-events: none;
                    opacity: 0;
                    transition: opacity 0.2s ease;
                `;
                
                document.body.appendChild(tooltip);
                
                // Position tooltip
                const rect = element.getBoundingClientRect();
                tooltip.style.left = rect.left + 'px';
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
                
                // Show tooltip
                setTimeout(() => tooltip.style.opacity = '1', 10);
            });
            
            element.addEventListener('mouseleave', () => {
                if (tooltip) {
                    tooltip.remove();
                }
            });
        }

        /**
         * Bind additional events
         */
        bindEvents() {
            // Handle window resize
            window.addEventListener('resize', () => this.handleResize());
            
            // Handle keyboard shortcuts
            document.addEventListener('keydown', (e) => this.handleKeyboard(e));
            
            // Handle before unload if there are unsaved changes
            window.addEventListener('beforeunload', (e) => this.handleBeforeUnload(e));
        }

        /**
         * Handle window resize
         */
        handleResize() {
            // Responsive adjustments if needed
            if (window.innerWidth < 768) {
                // Mobile adjustments
                this.handleMobileLayout();
            } else {
                // Desktop adjustments
                this.handleDesktopLayout();
            }
        }

        /**
         * Handle mobile layout
         */
        handleMobileLayout() {
            // Collapse sidebar on mobile if needed
            const sidebar = document.querySelector('.wap-admin-sidebar');
            if (sidebar) {
                sidebar.style.order = '-1';
            }
        }

        /**
         * Handle desktop layout
         */
        handleDesktopLayout() {
            // Reset any mobile-specific styles
            const sidebar = document.querySelector('.wap-admin-sidebar');
            if (sidebar) {
                sidebar.style.order = '';
            }
        }

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboard(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const form = document.getElementById('wap-settings-form');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
            
            // Escape to close modal
            if (e.key === 'Escape') {
                if (this.isPreviewOpen) {
                    this.closePreview();
                }
            }
        }

        /**
         * Handle before unload
         */
        handleBeforeUnload(e) {
            // Check if there are unsaved changes
            if (this.hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = '';
            }
        }

        /**
         * Check for unsaved changes
         */
        hasUnsavedChanges() {
            // For MVP, we'll skip this complex check
            return false;
        }

        /**
         * Show admin notice
         */
        showNotice(type, message) {
            // Remove existing notices
            document.querySelectorAll('.wap-notice').forEach(notice => notice.remove());
            
            const notice = document.createElement('div');
            notice.className = `wap-notice wap-notice-${type}`;
            notice.innerHTML = `
                <span>${message}</span>
                <button type="button" onclick="this.parentNode.remove()" style="
                    background: none; 
                    border: none; 
                    color: inherit; 
                    cursor: pointer; 
                    padding: 0 0 0 1rem;
                    font-size: 1.2rem;
                ">&times;</button>
            `;
            
            // Insert after header
            const header = document.querySelector('.wap-admin-header');
            if (header && header.nextSibling) {
                header.parentNode.insertBefore(notice, header.nextSibling);
            } else {
                document.querySelector('.wap-admin-wrap').prepend(notice);
            }
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notice.parentNode) {
                    notice.remove();
                }
            }, 5000);
        }

        /**
         * Set loading state for button
         */
        setLoadingState(button, loading) {
            if (loading) {
                button.disabled = true;
                button.setAttribute('data-original-text', button.value);
                button.value = 'Saving...';
                button.style.opacity = '0.7';
            } else {
                button.disabled = false;
                button.value = button.getAttribute('data-original-text') || 'Save Settings';
                button.style.opacity = '1';
            }
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
            
            document.dispatchEvent(event);
        }

        /**
         * Public API methods
         */
        switchToTab(tabId) {
            this.switchTab(tabId);
        }

        openPreviewModal() {
            this.openPreview();
        }

        getCurrentTabSettings() {
            return this.getCurrentSettings();
        }
    }

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        // Initialize WAPAdmin
        window.WAPAdmin = new WAPAdmin();
        
        // Expose public API
        window.wapAdminAPI = {
            switchTab: (tabId) => window.WAPAdmin.switchToTab(tabId),
            openPreview: () => window.WAPAdmin.openPreviewModal(),
            getSettings: () => window.WAPAdmin.getCurrentTabSettings()
        };
    });

})(jQuery);