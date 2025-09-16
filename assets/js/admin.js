/**
 * AzuraCast Song History Plugin - Admin JavaScript
 * 
 * Handles admin interface functionality
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initializeTabs();
        initializeConnectionTest();
        initializeCacheManagement();
        initializeFormValidation();
        initializeHelpTooltips();
    });

    /**
     * Initialize tab navigation
     */
    function initializeTabs() {
        var $tabs = $('.azuracast-admin-tabs .nav-tab');
        var $content = $('.tab-content');

        // Handle tab clicks
        $tabs.on('click', function(e) {
            e.preventDefault();
            
            var targetTab = $(this).data('tab');
            
            // Update active tab
            $tabs.removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show corresponding content
            $content.removeClass('active');
            $('#tab-' + targetTab).addClass('active');
            
            // Update URL hash
            window.location.hash = targetTab;
        });

        // Handle initial tab from URL hash
        var hash = window.location.hash.substring(1);
        if (hash) {
            var $targetTab = $tabs.filter('[data-tab="' + hash + '"]');
            if ($targetTab.length) {
                $targetTab.click();
            }
        }
    }

    /**
     * Initialize connection test functionality
     */
    function initializeConnectionTest() {
        $('#test-connection').on('click', function() {
            var $button = $(this);
            var $result = $('.test-result');
            
            // Check if required fields are filled
            var apiUrl = $('#api_url').val().trim();
            if (!apiUrl) {
                showResult($result, 'error', azuracast_admin.strings.error + ': API URL is required');
                return;
            }

            // Show loading state
            $button.addClass('loading').prop('disabled', true);
            showResult($result, 'testing', azuracast_admin.strings.testing);

            // Make AJAX request
            $.ajax({
                url: azuracast_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'azuracast_test_connection',
                    nonce: azuracast_admin.nonce,
                    api_url: apiUrl,
                    station_id: $('#station_id').val()
                },
                success: function(response) {
                    if (response.success) {
                        showResult($result, 'success', azuracast_admin.strings.success);
                    } else {
                        showResult($result, 'error', azuracast_admin.strings.error + ': ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    showResult($result, 'error', azuracast_admin.strings.error + ': ' + error);
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize cache management
     */
    function initializeCacheManagement() {
        $('#clear-cache').on('click', function() {
            var $button = $(this);
            var $result = $('.clear-result');
            
            if (!confirm('Are you sure you want to clear all cached data?')) {
                return;
            }

            // Show loading state
            $button.addClass('loading').prop('disabled', true);
            showResult($result, 'clearing', azuracast_admin.strings.clearing);

            // Make AJAX request
            $.ajax({
                url: azuracast_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'azuracast_clear_cache',
                    nonce: azuracast_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showResult($result, 'success', azuracast_admin.strings.cleared);
                    } else {
                        showResult($result, 'error', azuracast_admin.strings.clear_error + ': ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    showResult($result, 'error', azuracast_admin.strings.clear_error + ': ' + error);
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize form validation
     */
    function initializeFormValidation() {
        var $form = $('.azuracast-admin-tabs form');
        
        // Real-time validation
        $('#api_url').on('blur', function() {
            var url = $(this).val().trim();
            var $field = $(this);
            
            if (url && !isValidUrl(url)) {
                showFieldError($field, 'Please enter a valid URL');
            } else {
                clearFieldError($field);
            }
        });

        $('#song_count').on('input', function() {
            var count = parseInt($(this).val());
            var $field = $(this);
            
            if (count < 1 || count > 50) {
                showFieldError($field, 'Song count must be between 1 and 50');
            } else {
                clearFieldError($field);
            }
        });

        $('#cache_duration').on('input', function() {
            var duration = parseInt($(this).val());
            var $field = $(this);
            
            if (duration < 1 || duration > 60) {
                showFieldError($field, 'Cache duration must be between 1 and 60 minutes');
            } else {
                clearFieldError($field);
            }
        });

        // Form submission validation
        $form.on('submit', function(e) {
            var hasErrors = false;
            
            // Validate API URL
            var apiUrl = $('#api_url').val().trim();
            if (!apiUrl) {
                showFieldError($('#api_url'), 'API URL is required');
                hasErrors = true;
            } else if (!isValidUrl(apiUrl)) {
                showFieldError($('#api_url'), 'Please enter a valid URL');
                hasErrors = true;
            }
            
            // Validate song count
            var songCount = parseInt($('#song_count').val());
            if (songCount < 1 || songCount > 50) {
                showFieldError($('#song_count'), 'Song count must be between 1 and 50');
                hasErrors = true;
            }
            
            // Validate cache duration
            var cacheDuration = parseInt($('#cache_duration').val());
            if (cacheDuration < 1 || cacheDuration > 60) {
                showFieldError($('#cache_duration'), 'Cache duration must be between 1 and 60 minutes');
                hasErrors = true;
            }
            
            if (hasErrors) {
                e.preventDefault();
                showNotice('Please correct the errors above before saving.', 'error');
            }
        });
    }

    /**
     * Initialize help tooltips
     */
    function initializeHelpTooltips() {
        // Add tooltips to description text
        $('.description').each(function() {
            var $desc = $(this);
            var text = $desc.text();
            
            if (text.length > 100) {
                var shortText = text.substring(0, 100) + '...';
                var fullText = text;
                
                $desc.text(shortText);
                $desc.attr('title', fullText);
                $desc.addClass('has-tooltip');
            }
        });

        // Enhanced tooltips for form fields
        $('input[type="url"], input[type="text"], input[type="number"], select').each(function() {
            var $field = $(this);
            var $label = $('label[for="' + $field.attr('id') + '"]');
            
            $field.on('focus', function() {
                var $description = $(this).siblings('.description');
                if ($description.length) {
                    $description.addClass('highlighted');
                }
            });
            
            $field.on('blur', function() {
                var $description = $(this).siblings('.description');
                if ($description.length) {
                    $description.removeClass('highlighted');
                }
            });
        });
    }

    /**
     * Show result message
     */
    function showResult($element, type, message) {
        $element.removeClass('success error testing clearing')
               .addClass(type)
               .text(message)
               .show();
        
        if (type === 'success' || type === 'error') {
            setTimeout(function() {
                $element.fadeOut();
            }, 5000);
        }
    }

    /**
     * Show field error
     */
    function showFieldError($field, message) {
        clearFieldError($field);
        
        var $error = $('<div class="field-error" style="color: #dc3545; font-size: 12px; margin-top: 5px;">' + message + '</div>');
        $field.after($error);
        $field.addClass('error');
    }

    /**
     * Clear field error
     */
    function clearFieldError($field) {
        $field.removeClass('error');
        $field.siblings('.field-error').remove();
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
    }

    /**
     * Validate URL
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Auto-save functionality
     */
    var autoSaveTimeout;
    function scheduleAutoSave() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            // Auto-save draft settings (could be implemented if needed)
            console.log('Auto-saving settings...');
        }, 2000);
    }

    // Monitor form changes for auto-save
    $('.azuracast-admin-tabs input, .azuracast-admin-tabs select, .azuracast-admin-tabs textarea').on('change input', function() {
        scheduleAutoSave();
    });

    /**
     * Keyboard shortcuts
     */
    $(document).on('keydown', function(e) {
        // Ctrl+S to save
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            $('.azuracast-admin-tabs form').submit();
        }
        
        // Ctrl+T to test connection
        if ((e.ctrlKey || e.metaKey) && e.which === 84) {
            e.preventDefault();
            $('#test-connection').click();
        }
    });

    /**
     * Copy debug information
     */
    $(document).on('click', '.azuracast-tools textarea', function() {
        $(this).select();
        document.execCommand('copy');
        showNotice('Debug information copied to clipboard', 'success');
    });

    /**
     * Toggle sections
     */
    $('.azuracast-admin-tabs h3').on('click', function() {
        var $section = $(this).next();
        if ($section.length) {
            $section.slideToggle();
            $(this).toggleClass('collapsed');
        }
    });

    /**
     * Advanced settings toggle
     */
    $('#enable_database_cache').on('change', function() {
        var $advancedSettings = $('.database-cache-settings');
        if ($(this).is(':checked')) {
            $advancedSettings.slideDown();
        } else {
            $advancedSettings.slideUp();
        }
    }).trigger('change');

    /**
     * Live preview functionality
     */
    function initializeLivePreview() {
        var $previewContainer = $('#settings-preview');
        if (!$previewContainer.length) return;

        function updatePreview() {
            var settings = {
                song_count: $('#song_count').val(),
                layout: $('#default_layout').val(),
                show_covers: $('#show_covers').is(':checked'),
                show_timestamps: $('#show_timestamps').is(':checked')
            };

            // Update preview based on settings
            // This would show a live preview of how the widget/shortcode would look
        }

        // Update preview when settings change
        $('#song_count, #default_layout, #show_covers, #show_timestamps').on('change', updatePreview);
        updatePreview(); // Initial preview
    }

    // Initialize live preview if container exists
    initializeLivePreview();

})(jQuery);