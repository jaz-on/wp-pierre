/**
 * Pierre's admin JavaScript - he makes his admin interface interactive! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Pierre's admin object! 🪨
    window.PierreAdmin = {
        
        /**
         * Pierre initializes his admin JavaScript! 🪨
         */
        init: function() {
            this.bindEvents();
            this.loadAdminStats();
            this.setupTabs();
            this.setupAutoRefresh();
            console.log('Pierre initialized his admin JavaScript! 🪨');
        },
        
        /**
         * Pierre binds his admin events! 🪨
         */
        bindEvents: function() {
            // Pierre handles admin button clicks! 🪨
            $(document).on('click', '.pierre-admin-button', this.handleButtonClick);
            
            // Pierre handles form submissions! 🪨
            $(document).on('submit', '.pierre-admin-form', this.handleFormSubmit);
            
            // Pierre handles user assignment! 🪨
            $(document).on('click', '.pierre-assign-user', this.assignUser);
            
            // Pierre handles user removal! 🪨
            $(document).on('click', '.pierre-remove-user', this.removeUser);
            
            // Pierre handles test notification! 🪨
            $(document).on('click', '.pierre-test-notification', this.testNotification);
            
            // Pierre handles settings save! 🪨
            $(document).on('click', '.pierre-save-settings', this.saveSettings);
            
            // Pierre handles refresh stats! 🪨
            $(document).on('click', '.pierre-refresh-stats', this.loadAdminStats);
        },
        
        /**
         * Pierre handles button clicks! 🪨
         */
        handleButtonClick: function(e) {
            e.preventDefault();
            var $button = $(this);
            var action = $button.data('action');
            
            // Pierre shows loading state! 🪨
            PierreAdmin.showLoading($button);
            
            // Pierre handles different actions! 🪨
            switch (action) {
                case 'refresh':
                    PierreAdmin.loadAdminStats();
                    break;
                case 'test-notification':
                    PierreAdmin.testNotification();
                    break;
                case 'save-settings':
                    PierreAdmin.saveSettings();
                    break;
                default:
                    console.log('Pierre says: Unknown admin action! 😢');
            }
            
            // Pierre hides loading state! 🪨
            setTimeout(function() {
                PierreAdmin.hideLoading($button);
            }, 1000);
        },
        
        /**
         * Pierre handles form submissions! 🪨
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            var $form = $(this);
            var formData = $form.serialize();
            
            // Pierre shows loading state! 🪨
            PierreAdmin.showLoading($form.find('button[type="submit"]'));
            
            // Pierre submits form data! 🪨
            $.ajax({
                url: pierre_admin_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=pierre_admin_save_settings&nonce=' + pierre_admin_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        PierreAdmin.showNotice('Pierre saved his settings! 🪨', 'success');
                    } else {
                        PierreAdmin.showNotice('Pierre failed to save settings! 😢', 'error');
                    }
                },
                error: function() {
                    PierreAdmin.showNotice('Pierre encountered an error saving settings! 😢', 'error');
                },
                complete: function() {
                    PierreAdmin.hideLoading($form.find('button[type="submit"]'));
                }
            });
        },
        
        /**
         * Pierre loads his admin statistics! 🪨
         */
        loadAdminStats: function() {
            $.ajax({
                url: pierre_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pierre_admin_get_stats',
                    nonce: pierre_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PierreAdmin.updateAdminStats(response.data);
                        PierreAdmin.showNotice('Pierre updated his admin statistics! 🪨', 'success');
                    } else {
                        PierreAdmin.showNotice('Pierre failed to load admin statistics! 😢', 'error');
                    }
                },
                error: function() {
                    PierreAdmin.showNotice('Pierre encountered an error loading admin statistics! 😢', 'error');
                }
            });
        },
        
        /**
         * Pierre assigns a user! 🪨
         */
        assignUser: function(e) {
            e.preventDefault();
            var $button = $(this);
            var userId = $button.data('user-id');
            var projectType = $button.data('project-type');
            var projectSlug = $button.data('project-slug');
            var localeCode = $button.data('locale-code');
            var role = $button.data('role');
            
            // Pierre shows loading state! 🪨
            PierreAdmin.showLoading($button);
            
            $.ajax({
                url: pierre_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pierre_admin_assign_user',
                    nonce: pierre_admin_ajax.nonce,
                    user_id: userId,
                    project_type: projectType,
                    project_slug: projectSlug,
                    locale_code: localeCode,
                    role: role
                },
                success: function(response) {
                    if (response.success) {
                        PierreAdmin.showNotice(response.data.message, 'success');
                        PierreAdmin.loadAdminStats();
                    } else {
                        PierreAdmin.showNotice('Pierre failed to assign user! 😢', 'error');
                    }
                },
                error: function() {
                    PierreAdmin.showNotice('Pierre encountered an error assigning user! 😢', 'error');
                },
                complete: function() {
                    PierreAdmin.hideLoading($button);
                }
            });
        },
        
        /**
         * Pierre removes a user! 🪨
         */
        removeUser: function(e) {
            e.preventDefault();
            var $button = $(this);
            var userId = $button.data('user-id');
            var projectSlug = $button.data('project-slug');
            var localeCode = $button.data('locale-code');
            
            // Pierre confirms removal! 🪨
            if (!confirm('Pierre asks: Are you sure you want to remove this user? 😢')) {
                return;
            }
            
            // Pierre shows loading state! 🪨
            PierreAdmin.showLoading($button);
            
            $.ajax({
                url: pierre_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pierre_admin_remove_user',
                    nonce: pierre_admin_ajax.nonce,
                    user_id: userId,
                    project_slug: projectSlug,
                    locale_code: localeCode
                },
                success: function(response) {
                    if (response.success) {
                        PierreAdmin.showNotice(response.data.message, 'success');
                        PierreAdmin.loadAdminStats();
                    } else {
                        PierreAdmin.showNotice('Pierre failed to remove user! 😢', 'error');
                    }
                },
                error: function() {
                    PierreAdmin.showNotice('Pierre encountered an error removing user! 😢', 'error');
                },
                complete: function() {
                    PierreAdmin.hideLoading($button);
                }
            });
        },
        
        /**
         * Pierre tests his notification system! 🪨
         */
        testNotification: function() {
            $.ajax({
                url: pierre_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pierre_admin_test_notification',
                    nonce: pierre_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PierreAdmin.showNotice('Pierre sent a test notification! 🪨', 'success');
                    } else {
                        PierreAdmin.showNotice('Pierre failed to send test notification! 😢', 'error');
                    }
                },
                error: function() {
                    PierreAdmin.showNotice('Pierre encountered an error testing notifications! 😢', 'error');
                }
            });
        },
        
        /**
         * Pierre saves his settings! 🪨
         */
        saveSettings: function() {
            var settings = {
                slack_webhook_url: $('#slack_webhook_url').val(),
                surveillance_interval: $('#surveillance_interval').val(),
                notifications_enabled: $('#notifications_enabled').is(':checked')
            };
            
            $.ajax({
                url: pierre_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pierre_admin_save_settings',
                    nonce: pierre_admin_ajax.nonce,
                    slack_webhook_url: settings.slack_webhook_url,
                    surveillance_interval: settings.surveillance_interval,
                    notifications_enabled: settings.notifications_enabled
                },
                success: function(response) {
                    if (response.success) {
                        PierreAdmin.showNotice('Pierre saved his settings! 🪨', 'success');
                    } else {
                        PierreAdmin.showNotice('Pierre failed to save settings! 😢', 'error');
                    }
                },
                error: function() {
                    PierreAdmin.showNotice('Pierre encountered an error saving settings! 😢', 'error');
                }
            });
        },
        
        /**
         * Pierre sets up tabs! 🪨
         */
        setupTabs: function() {
            $('.pierre-admin-tab').on('click', function() {
                var tabId = $(this).data('tab');
                
                // Pierre deactivates all tabs! 🪨
                $('.pierre-admin-tab').removeClass('active');
                $('.pierre-admin-tab-content').removeClass('active');
                
                // Pierre activates selected tab! 🪨
                $(this).addClass('active');
                $('#' + tabId).addClass('active');
            });
        },
        
        /**
         * Pierre updates his admin statistics! 🪨
         */
        updateAdminStats: function(stats) {
            $('.pierre-stats-grid').empty();
            
            stats.forEach(function(stat) {
                var $statBox = $('<div class="pierre-stat-box">' +
                    '<div class="pierre-stat-number">' + stat.value + '</div>' +
                    '<div class="pierre-stat-label">' + stat.label + '</div>' +
                    '</div>');
                
                $('.pierre-stats-grid').append($statBox);
            });
        },
        
        /**
         * Pierre shows a notice! 🪨
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="pierre-admin-notice ' + type + '">' +
                '<strong>Pierre says:</strong> ' + message +
                '</div>');
            
            // Pierre removes existing notices! 🪨
            $('.pierre-admin-notice').remove();
            
            // Pierre adds his new notice! 🪨
            $('.wrap').prepend($notice);
            
            // Pierre auto-hides his notice! 🪨
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
        },
        
        /**
         * Pierre shows loading state! 🪨
         */
        showLoading: function($element) {
            $element.addClass('loading');
            $element.prop('disabled', true);
            
            if ($element.hasClass('pierre-admin-button')) {
                $element.data('original-text', $element.text());
                $element.html('<span class="pierre-admin-spinner"></span>Loading...');
            }
        },
        
        /**
         * Pierre hides loading state! 🪨
         */
        hideLoading: function($element) {
            $element.removeClass('loading');
            $element.prop('disabled', false);
            
            if ($element.hasClass('pierre-admin-button')) {
                $element.text($element.data('original-text'));
            }
        },
        
        /**
         * Pierre sets up auto-refresh! 🪨
         */
        setupAutoRefresh: function() {
            // Pierre refreshes every 10 minutes! 🪨
            setInterval(function() {
                PierreAdmin.loadAdminStats();
            }, 10 * 60 * 1000);
        },
        
        /**
         * Pierre animates numbers! 🪨
         */
        animateNumber: function($element, targetValue, duration) {
            duration = duration || 1000;
            var startValue = parseInt($element.text()) || 0;
            var increment = (targetValue - startValue) / (duration / 16);
            var currentValue = startValue;
            
            var timer = setInterval(function() {
                currentValue += increment;
                
                if ((increment > 0 && currentValue >= targetValue) || 
                    (increment < 0 && currentValue <= targetValue)) {
                    currentValue = targetValue;
                    clearInterval(timer);
                }
                
                $element.text(Math.round(currentValue));
            }, 16);
        },
        
        /**
         * Pierre formats numbers! 🪨
         */
        formatNumber: function(number) {
            if (number >= 1000000) {
                return (number / 1000000).toFixed(1) + 'M';
            } else if (number >= 1000) {
                return (number / 1000).toFixed(1) + 'K';
            }
            return number.toString();
        },
        
        /**
         * Pierre gets his admin status! 🪨
         */
        getStatus: function() {
            return {
                initialized: true,
                autoRefresh: true,
                tabsSetup: true,
                message: 'Pierre\'s admin JavaScript is ready! 🪨'
            };
        }
    };
    
    // Pierre starts when document is ready! 🪨
    $(document).ready(function() {
        PierreAdmin.init();
    });
    
})(jQuery);
