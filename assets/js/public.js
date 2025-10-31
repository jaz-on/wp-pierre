/**
 * Pierre's public JavaScript - he makes his dashboard interactive! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

(function($) {
    'use strict';
    // Inject Pierre nonce automatically if missing
    $.ajaxPrefilter(function(options, originalOptions){
        try {
            var url = (options && options.url) || '';
            if (!/admin-ajax\.php/i.test(url)) { return; }
            if ((options.type || options.method || 'GET').toUpperCase() !== 'POST') { return; }
            if (originalOptions && originalOptions.data instanceof FormData) { return; }
            var dataStr = typeof originalOptions.data === 'string' ? originalOptions.data : '';
            if (/(?:^|&|=)nonce=/.test(dataStr)) { return; }
            var nonce = (window.pierre_ajax && window.pierre_ajax.nonce) || (window.pierreAjax && window.pierreAjax.nonce) || (window.pierre_ajax_nonce) || (window.pierreAjaxNonce) || (window.pierre_ajax && window.pierre_ajax.nonce);
            if (!nonce && typeof pierre_ajax !== 'undefined') { nonce = pierre_ajax.nonce; }
            if (!nonce) { return; }
            options.data = (dataStr ? dataStr + '&' : '') + 'nonce=' + encodeURIComponent(nonce);
        } catch (e) {}
    });
    
    // Pierre's main object! 🪨
    window.Pierre = {
        
        /**
         * Pierre initializes his JavaScript! 🪨
         */
        init: function() {
            this.bindEvents();
            this.loadStats();
            this.setupAutoRefresh();
            console.log('Pierre initialized his JavaScript! 🪨');
        },
        
        /**
         * Pierre binds his events! 🪨
         */
        bindEvents: function() {
            // Pierre handles button clicks! 🪨
            $(document).on('click', '.pierre-button', this.handleButtonClick);
            
            // Pierre handles test notification! 🪨
            $(document).on('click', '.pierre-test-notification', this.testNotification);
            
            // Pierre handles refresh stats! 🪨
            $(document).on('click', '.pierre-refresh-stats', this.loadStats);
            
            // Pierre handles project card clicks! 🪨
            $(document).on('click', '.pierre-project-card', this.handleProjectClick);
        },
        
        /**
         * Pierre handles button clicks! 🪨
         */
        handleButtonClick: function(e) {
            e.preventDefault();
            var $button = $(this);
            var action = $button.data('action');
            
            // Pierre shows loading state! 🪨
            Pierre.showLoading($button);
            
            // Pierre handles different actions! 🪨
            switch (action) {
                case 'refresh':
                    Pierre.loadStats();
                    break;
                case 'test-notification':
                    Pierre.testNotification();
                    break;
                default:
                    console.log('Pierre says: Unknown action! 😢');
            }
            
            // Pierre hides loading state! 🪨
            setTimeout(function() {
                Pierre.hideLoading($button);
            }, 1000);
        },
        
        /**
         * Pierre loads his statistics! 🪨
         */
        loadStats: function() {
            $.ajax({
                url: pierre_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pierre_get_stats',
                    nonce: pierre_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        Pierre.updateStats(response.data);
                        Pierre.showMessage('Pierre updated his statistics! 🪨', 'success');
                    } else {
                        Pierre.showMessage('Pierre failed to load statistics! 😢', 'error');
                    }
                },
                error: function() {
                    Pierre.showMessage('Pierre encountered an error loading statistics! 😢', 'error');
                }
            });
        },
        
        /**
         * Pierre tests his notification system! 🪨
         */
        testNotification: function() {
            $.ajax({
                url: pierre_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pierre_test_notification',
                    nonce: pierre_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        Pierre.showMessage('Pierre sent a test notification! 🪨', 'success');
                    } else {
                        Pierre.showMessage('Pierre failed to send test notification! 😢', 'error');
                    }
                },
                error: function() {
                    Pierre.showMessage('Pierre encountered an error testing notifications! 😢', 'error');
                }
            });
        },
        
        /**
         * Pierre handles project card clicks! 🪨
         */
        handleProjectClick: function(e) {
            var $card = $(this);
            var projectSlug = $card.data('project');
            var locale = $card.data('locale');
            
            if (projectSlug && locale) {
                // Pierre navigates to project page! 🪨
                window.location.href = '/pierre/' + locale + '/' + projectSlug + '/';
            }
        },
        
        /**
         * Pierre updates his statistics! 🪨
         */
        updateStats: function(stats) {
            $('.pierre-stats').empty();
            
            stats.forEach(function(stat) {
                var $statCard = $('<div class="pierre-stat-card">' +
                    '<div class="pierre-stat-number">' + stat.value + '</div>' +
                    '<div class="pierre-stat-label">' + stat.label + '</div>' +
                    '</div>');
                
                $('.pierre-stats').append($statCard);
            });
        },
        
        /**
         * Pierre shows a message! 🪨
         */
        showMessage: function(message, type) {
            type = type || 'info';
            
            var $message = $('<div class="pierre-message ' + type + '">' +
                '<strong>Pierre says:</strong> ' + message +
                '</div>');
            
            // Pierre removes existing messages! 🪨
            $('.pierre-message').remove();
            
            // Pierre adds his new message! 🪨
            $('.pierre-container').prepend($message);
            
            // Pierre auto-hides his message! 🪨
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 5000);
        },
        
        /**
         * Pierre shows loading state! 🪨
         */
        showLoading: function($element) {
            $element.addClass('loading');
            $element.prop('disabled', true);
            
            if ($element.hasClass('pierre-button')) {
                $element.data('original-text', $element.text());
                $element.html('<span class="pierre-spinner"></span>Loading...');
            }
        },
        
        /**
         * Pierre hides loading state! 🪨
         */
        hideLoading: function($element) {
            $element.removeClass('loading');
            $element.prop('disabled', false);
            
            if ($element.hasClass('pierre-button')) {
                $element.text($element.data('original-text'));
            }
        },
        
        /**
         * Pierre sets up auto-refresh! 🪨
         */
        setupAutoRefresh: function() {
            // Pierre refreshes every 5 minutes! 🪨
            setInterval(function() {
                Pierre.loadStats();
            }, 5 * 60 * 1000);
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
         * Pierre gets his status! 🪨
         */
        getStatus: function() {
            return {
                initialized: true,
                autoRefresh: true,
                message: 'Pierre\'s JavaScript is ready! 🪨'
            };
        }
    };
    
    // Pierre starts when document is ready! 🪨
    $(document).ready(function() {
        Pierre.init();
    });
    
})(jQuery);
