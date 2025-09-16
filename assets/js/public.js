/**
 * AzuraCast Song History Plugin - Public JavaScript
 * 
 * Handles front-end functionality for widgets and shortcodes
 */

(function() {
    'use strict';

    // Plugin initialization
    document.addEventListener('DOMContentLoaded', function() {
        initializeAzuraCastComponents();
        setupAutoRefresh();
        setupResponsive();
    });

    /**
     * Initialize all AzuraCast components
     */
    function initializeAzuraCastComponents() {
        // Initialize tooltips for cover images
        var coverImages = document.querySelectorAll('.azuracast-cover-image');
        coverImages.forEach(function(img) {
            img.addEventListener('error', handleImageError);
            img.addEventListener('load', handleImageLoad);
        });

        // Initialize lazy loading for cover images
        if ('IntersectionObserver' in window) {
            setupLazyLoading();
        }

        // Initialize audio players
        initializeAudioPlayers();
    }

    /**
     * Handle image loading errors
     */
    function handleImageError(event) {
        var img = event.target;
        var container = img.closest('.azuracast-song-cover');
        
        if (container && !container.querySelector('.azuracast-no-cover')) {
            img.style.display = 'none';
            
            var noCover = document.createElement('div');
            noCover.className = 'azuracast-no-cover ' + (img.className.includes('small') ? 'small' : 
                                img.className.includes('large') ? 'large' : 'medium');
            noCover.innerHTML = '<span class="azuracast-music-icon">♪</span>';
            
            container.appendChild(noCover);
        }
    }

    /**
     * Handle successful image loading
     */
    function handleImageLoad(event) {
        var img = event.target;
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s ease';
        
        setTimeout(function() {
            img.style.opacity = '1';
        }, 50);
    }

    /**
     * Setup lazy loading for cover images
     */
    function setupLazyLoading() {
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        });

        var lazyImages = document.querySelectorAll('.azuracast-cover-image[data-src]');
        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    }

    /**
     * Setup auto-refresh functionality
     */
    function setupAutoRefresh() {
        // Check if azuracast_ajax is available (localized from WordPress)
        if (typeof azuracast_ajax === 'undefined') {
            return;
        }

        var refreshInterval = parseInt(azuracast_ajax.auto_refresh) * 1000;
        
        if (refreshInterval > 0) {
            setInterval(function() {
                refreshAllComponents();
            }, refreshInterval);
        }
    }

    /**
     * Refresh all AzuraCast components
     */
    function refreshAllComponents() {
        var widgets = document.querySelectorAll('.azuracast-song-history-widget[data-auto-refresh="true"]');
        var shortcodes = document.querySelectorAll('.azuracast-song-history-shortcode[data-auto-refresh="true"]');
        var nowPlaying = document.querySelectorAll('.azuracast-nowplaying-shortcode[data-auto-refresh="true"]');

        widgets.forEach(refreshWidget);
        shortcodes.forEach(refreshShortcode);
        nowPlaying.forEach(refreshNowPlaying);
    }

    /**
     * Refresh widget content
     */
    function refreshWidget(widget) {
        var indicator = widget.querySelector('.azuracast-refresh-indicator');
        var container = widget.querySelector('.azuracast-songs-container');
        
        if (indicator) {
            indicator.style.display = 'block';
        }

        var data = {
            action: 'azuracast_refresh_widget',
            nonce: azuracast_ajax.nonce,
            widget_id: widget.id
        };

        makeAjaxRequest(data, function(response) {
            if (indicator) {
                indicator.style.display = 'none';
            }
            
            if (response.success && response.data.html && container) {
                container.innerHTML = response.data.html;
                updateTimestamp(widget);
                reinitializeComponents(container);
            }
        });
    }

    /**
     * Refresh shortcode content
     */
    function refreshShortcode(shortcode) {
        var indicator = shortcode.querySelector('.azuracast-refresh-indicator');
        var container = shortcode.querySelector('.azuracast-songs-container');
        
        if (indicator) {
            indicator.style.display = 'block';
        }

        var data = {
            action: 'azuracast_refresh_shortcode',
            nonce: azuracast_ajax.nonce,
            shortcode: 'azuracast_history',
            container_id: shortcode.id
        };

        makeAjaxRequest(data, function(response) {
            if (indicator) {
                indicator.style.display = 'none';
            }
            
            if (response.success && response.data.html && container) {
                container.innerHTML = response.data.html;
                updateTimestamp(shortcode);
                reinitializeComponents(container);
            }
        });
    }

    /**
     * Refresh now playing content
     */
    function refreshNowPlaying(nowPlaying) {
        var indicator = nowPlaying.querySelector('.azuracast-refresh-indicator');
        var container = nowPlaying.querySelector('.azuracast-nowplaying-container');
        
        if (indicator) {
            indicator.style.display = 'block';
        }

        var data = {
            action: 'azuracast_refresh_shortcode',
            nonce: azuracast_ajax.nonce,
            shortcode: 'azuracast_nowplaying',
            container_id: nowPlaying.id
        };

        makeAjaxRequest(data, function(response) {
            if (indicator) {
                indicator.style.display = 'none';
            }
            
            if (response.success && response.data.html && container) {
                container.innerHTML = response.data.html;
                reinitializeComponents(container);
            }
        });
    }

    /**
     * Make AJAX request
     */
    function makeAjaxRequest(data, callback) {
        if (typeof azuracast_ajax === 'undefined') {
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', azuracast_ajax.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    callback(response);
                } catch (e) {
                    console.error('AzuraCast: Invalid JSON response', e);
                }
            }
        };

        // Convert data object to URL-encoded string
        var params = Object.keys(data).map(function(key) {
            return encodeURIComponent(key) + '=' + encodeURIComponent(data[key]);
        }).join('&');

        xhr.send(params);
    }

    /**
     * Update timestamp
     */
    function updateTimestamp(container) {
        var timestamp = container.querySelector('.timestamp');
        if (timestamp) {
            var now = new Date();
            timestamp.textContent = now.toLocaleTimeString();
        }
    }

    /**
     * Reinitialize components after content update
     */
    function reinitializeComponents(container) {
        var coverImages = container.querySelectorAll('.azuracast-cover-image');
        coverImages.forEach(function(img) {
            img.addEventListener('error', handleImageError);
            img.addEventListener('load', handleImageLoad);
        });

        // Setup lazy loading for new images
        if ('IntersectionObserver' in window) {
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            });

            var lazyImages = container.querySelectorAll('.azuracast-cover-image[data-src]');
            lazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Setup responsive behavior
     */
    function setupResponsive() {
        // Handle grid layout on small screens
        function handleGridResponsive() {
            var grids = document.querySelectorAll('.azuracast-song-grid');
            
            grids.forEach(function(grid) {
                var items = grid.querySelectorAll('.azuracast-song-card');
                var containerWidth = grid.offsetWidth;
                var minItemWidth = 200;
                var columns = Math.floor(containerWidth / minItemWidth);
                
                if (columns < 1) columns = 1;
                
                grid.style.gridTemplateColumns = 'repeat(' + columns + ', 1fr)';
            });
        }

        // Handle table overflow on small screens
        function handleTableResponsive() {
            var tables = document.querySelectorAll('.azuracast-song-table');
            
            tables.forEach(function(table) {
                var wrapper = table.parentElement;
                if (!wrapper.classList.contains('table-responsive')) {
                    var tableWrapper = document.createElement('div');
                    tableWrapper.className = 'table-responsive';
                    tableWrapper.style.overflowX = 'auto';
                    table.parentNode.insertBefore(tableWrapper, table);
                    tableWrapper.appendChild(table);
                }
            });
        }

        // Run on load and resize
        handleGridResponsive();
        handleTableResponsive();
        
        window.addEventListener('resize', debounce(function() {
            handleGridResponsive();
            handleTableResponsive();
        }, 250));
    }

    /**
     * Initialize audio players
     */
    function initializeAudioPlayers() {
        var players = document.querySelectorAll('.azuracast-audio-player audio');
        
        players.forEach(function(player) {
            // Set default volume if specified
            var volume = player.dataset.volume;
            if (volume) {
                player.volume = parseFloat(volume);
            }

            // Handle play/pause events
            player.addEventListener('play', function() {
                // Pause other players
                players.forEach(function(otherPlayer) {
                    if (otherPlayer !== player && !otherPlayer.paused) {
                        otherPlayer.pause();
                    }
                });
            });

            // Handle loading errors
            player.addEventListener('error', function() {
                var container = player.closest('.azuracast-audio-player');
                if (container && !container.querySelector('.audio-error')) {
                    var errorMsg = document.createElement('div');
                    errorMsg.className = 'audio-error azuracast-error';
                    errorMsg.textContent = 'Unable to load audio stream';
                    container.appendChild(errorMsg);
                }
            });
        });
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Utility functions
     */
    window.AzuraCast = {
        /**
         * Manually refresh a specific component
         */
        refresh: function(containerId) {
            var container = document.getElementById(containerId);
            if (!container) return;

            if (container.classList.contains('azuracast-song-history-widget')) {
                refreshWidget(container);
            } else if (container.classList.contains('azuracast-song-history-shortcode')) {
                refreshShortcode(container);
            } else if (container.classList.contains('azuracast-nowplaying-shortcode')) {
                refreshNowPlaying(container);
            }
        },

        /**
         * Toggle auto-refresh for a component
         */
        toggleAutoRefresh: function(containerId, enable) {
            var container = document.getElementById(containerId);
            if (container) {
                container.setAttribute('data-auto-refresh', enable ? 'true' : 'false');
            }
        },

        /**
         * Get component data
         */
        getData: function(containerId) {
            var container = document.getElementById(containerId);
            if (!container) return null;

            var songs = [];
            var songItems = container.querySelectorAll('.azuracast-song-item');
            
            songItems.forEach(function(item) {
                var title = item.querySelector('.azuracast-song-title');
                var artist = item.querySelector('.azuracast-song-artist');
                var album = item.querySelector('.azuracast-song-album');
                var time = item.querySelector('.azuracast-song-time');
                var cover = item.querySelector('.azuracast-cover-image');

                songs.push({
                    title: title ? title.textContent : '',
                    artist: artist ? artist.textContent : '',
                    album: album ? album.textContent : '',
                    time: time ? time.textContent : '',
                    cover: cover ? cover.src : ''
                });
            });

            return songs;
        }
    };

})();