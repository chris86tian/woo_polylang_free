/**
 * WooCommerce Polylang Integration Admin JavaScript
 */

(function($) {
    'use strict';
    
    var WCPolylangAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initDashboard();
        },
        
        bindEvents: function() {
            // Sync translations button
            $('#sync-translations').on('click', this.syncTranslations);
            
            // Settings form validation
            $('.wc-polylang-settings-form').on('submit', this.validateSettings);
            
            // Real-time stats updates
            this.updateStatsInterval = setInterval(this.updateStats, 30000); // Every 30 seconds
        },
        
        initDashboard: function() {
            // Animate stat boxes on load
            $('.stat-box').each(function(index) {
                $(this).delay(index * 100).animate({
                    opacity: 1,
                    transform: 'translateY(0)'
                }, 500);
            });
            
            // Initialize tooltips if available
            if ($.fn.tooltip) {
                $('[data-tooltip]').tooltip();
            }
        },
        
        syncTranslations: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $progress = $('#sync-progress');
            var $progressFill = $('.progress-fill');
            var $progressText = $('.progress-text');
            
            // Disable button and show progress
            $button.prop('disabled', true).text(wcPolylangAdmin.strings.syncing);
            $progress.show();
            $progressFill.css('width', '0%');
            $progressText.text('Initializing...');
            
            // Simulate progress
            var progress = 0;
            var progressInterval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                $progressFill.css('width', progress + '%');
                $progressText.text('Processing... ' + Math.round(progress) + '%');
            }, 200);
            
            // Make AJAX request
            $.ajax({
                url: wcPolylangAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_polylang_sync_translations',
                    nonce: wcPolylangAdmin.nonce
                },
                success: function(response) {
                    clearInterval(progressInterval);
                    $progressFill.css('width', '100%');
                    $progressText.text('Complete!');
                    
                    if (response.success) {
                        WCPolylangAdmin.showMessage(wcPolylangAdmin.strings.syncComplete, 'success');
                        WCPolylangAdmin.updateStats();
                    } else {
                        WCPolylangAdmin.showMessage(wcPolylangAdmin.strings.syncError, 'error');
                    }
                    
                    setTimeout(function() {
                        $progress.hide();
                        $button.prop('disabled', false).text('Sync All Translations');
                    }, 2000);
                },
                error: function() {
                    clearInterval(progressInterval);
                    WCPolylangAdmin.showMessage(wcPolylangAdmin.strings.syncError, 'error');
                    $progress.hide();
                    $button.prop('disabled', false).text('Sync All Translations');
                }
            });
        },
        
        validateSettings: function(e) {
            var isValid = true;
            var $form = $(this);
            
            // Remove previous error messages
            $('.wc-polylang-error').remove();
            
            // Validate default language selection
            var defaultLang = $form.find('select[name="wc_polylang_default_language"]').val();
            if (!defaultLang) {
                WCPolylangAdmin.showFieldError(
                    $form.find('select[name="wc_polylang_default_language"]'),
                    'Please select a default language.'
                );
                isValid = false;
            }
            
            // Validate at least one feature is enabled
            var featuresEnabled = $form.find('input[name^="wc_polylang_enable_"]:checked').length;
            if (featuresEnabled === 0) {
                WCPolylangAdmin.showMessage('Please enable at least one translation feature.', 'error');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('.wc-polylang-message.error').first().offset().top - 50
                }, 500);
            }
            
            return isValid;
        },
        
        updateStats: function() {
            $.ajax({
                url: wcPolylangAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_polylang_get_stats',
                    nonce: wcPolylangAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        WCPolylangAdmin.updateStatBoxes(response.data);
                    }
                }
            });
        },
        
        updateStatBoxes: function(stats) {
            $('.stat-box').each(function() {
                var $box = $(this);
                var $number = $box.find('h3');
                var $label = $box.find('p').text().toLowerCase();
                
                var newValue = 0;
                if ($label.includes('total products')) {
                    newValue = stats.products.total;
                } else if ($label.includes('translated products')) {
                    newValue = stats.products.translated;
                } else if ($label.includes('total categories')) {
                    newValue = stats.categories.total;
                } else if ($label.includes('translated categories')) {
                    newValue = stats.categories.translated;
                }
                
                // Animate number change
                WCPolylangAdmin.animateNumber($number, parseInt($number.text()), newValue);
            });
        },
        
        animateNumber: function($element, from, to) {
            var duration = 1000;
            var start = Date.now();
            
            function update() {
                var now = Date.now();
                var progress = Math.min((now - start) / duration, 1);
                var current = Math.floor(from + (to - from) * progress);
                
                $element.text(current);
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            update();
        },
        
        showMessage: function(message, type) {
            var $message = $('<div class="wc-polylang-message ' + type + '">' + message + '</div>');
            $('.wc-polylang-admin-container').prepend($message);
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },
        
        showFieldError: function($field, message) {
            var $error = $('<div class="wc-polylang-error" style="color: #d63638; font-size: 12px; margin-top: 5px;">' + message + '</div>');
            $field.closest('td').append($error);
            $field.css('border-color', '#d63638');
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        WCPolylangAdmin.init();
    });
    
})(jQuery);
