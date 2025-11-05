/**
 * Keylouds Frontend - Wordcloud2 Initialization
 * Uses HTML mode for SEO-friendly word clouds
 */

(function() {
    'use strict';
    
    /**
     * Initialize all word clouds on the page
     */
    function initWordClouds() {
        const wordCloudElements = document.querySelectorAll('.keylouds-cloud-container');
        
        wordCloudElements.forEach(function(container) {
            // Skip if already initialized
            if (container.getAttribute('data-wordcloud-initialized') === 'true') {
                return;
            }
            
            const cloudData = container.getAttribute('data-cloud');
            const colorSmall = container.getAttribute('data-color-small') || '#a3d0e0';
            const colorMedium = container.getAttribute('data-color-medium') || '#3498db';
            const colorLarge = container.getAttribute('data-color-large') || '#2271b1';
            const seed = parseInt(container.getAttribute('data-seed')) || 0;
            
            // Debug: Log seed value
            if (seed === 0) {
                console.warn('Keylouds: Word cloud has no seed (will render randomly). Use the Shuffle button in admin to set a seed.');
            }
            
            if (!cloudData) {
                return;
            }
            
            try {
                const keywords = JSON.parse(cloudData);
                
                // Convert keywords object to wordcloud2 format: [[word, weight], ...]
                // Sort by weight descending to ensure most used words are processed first
                const wordList = Object.entries(keywords)
                    .sort(function(a, b) { return b[1] - a[1]; })
                    .map(function(entry) {
                        return [entry[0], entry[1]];
                    });
                
                // Get min and max weights for better scaling
                const weights = wordList.map(function(item) { return item[1]; });
                const maxWeight = Math.max.apply(null, weights);
                const minWeight = Math.min.apply(null, weights);
                
                // Get container dimensions for responsive sizing
                const containerWidth = container.offsetWidth || 600;
                const containerHeight = Math.min(containerWidth * 0.6, 400); // Aspect ratio
                
                // Set container height
                container.style.height = containerHeight + 'px';
                
                // Color function based on weight
                function getColorForWeight(weight) {
                    if (weight <= 3) {
                        return colorSmall;
                    } else if (weight <= 6) {
                        return colorMedium;
                    } else {
                        return colorLarge;
                    }
                }
                
                // Simple seeded random number generator for deterministic layout
                function seededRandom(seed) {
                    var m = 2147483648;
                    var a = 1103515245;
                    var c = 12345;
                    
                    return function() {
                        seed = (a * seed + c) % m;
                        return seed / m;
                    };
                }
                
                // Initialize wordcloud2 with HTML mode
                if (typeof WordCloud !== 'undefined') {
                    // Mark as initialized
                    container.setAttribute('data-wordcloud-initialized', 'true');
                    
                    // Override Math.random if seed is provided for deterministic layout
                    var originalRandom = Math.random;
                    var restoreTimer = null;
                    if (seed > 0) {
                        Math.random = seededRandom(seed);
                    }
                    
                    WordCloud(container, {
                        list: wordList,
                        // Use HTML elements for SEO
                        weightFactor: function(size) {
                            // Improved scaling: larger difference between min and max sizes
                            // Maps weight 1-10 to actual visual size based on container
                            var normalizedWeight = (size - minWeight) / (maxWeight - minWeight);
                            var minSizePx = containerWidth / 80;  // Minimum font size
                            var maxSizePx = containerWidth / 8;   // Maximum font size (much larger)
                            return minSizePx + (normalizedWeight * (maxSizePx - minSizePx));
                        },
                        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
                        color: function(word, weight) {
                            return getColorForWeight(weight);
                        },
                        rotateRatio: 0.3,
                        rotationSteps: 2,
                        backgroundColor: 'transparent',
                        // HTML mode for indexable text
                        drawOutOfBound: false,
                        shrinkToFit: true,
                        minSize: 8,
                        // Grid size affects collision detection and placement
                        gridSize: 8,
                        // Wait time for placement
                        wait: 0,
                        // Hover effect
                        hover: function(item, dimension, event) {
                            if (item) {
                                // Add hover styling
                                if (event.type === 'mouseover') {
                                    if (item && item[0] && typeof item[0] === 'object') {
                                        const textNode = item[0];
                                        if (textNode.style) {
                                            textNode.style.textShadow = '2px 2px 4px rgba(0,0,0,0.3)';
                                            textNode.style.transform = 'scale(1.1)';
                                            textNode.style.transition = 'all 0.2s ease';
                                        }
                                    }
                                } else if (event.type === 'mouseout') {
                                    if (item && item[0] && typeof item[0] === 'object') {
                                        const textNode = item[0];
                                        if (textNode.style) {
                                            textNode.style.textShadow = '';
                                            textNode.style.transform = '';
                                        }
                                    }
                                }
                            }
                        },
                        // Click handler (optional - can link to search or filter)
                        click: function(item, dimension, event) {
                            if (item && item[0]) {
                                // Optional: Add click handling
                                // For now, just log the word
                                console.log('Clicked word:', item[0]);
                            }
                        },
                        // Callback when drawing is complete
                        drawOutOfBound: false
                    });
                    
                    // Restore original Math.random after a delay to ensure wordcloud2 finishes
                    // WordCloud2 does layout calculations asynchronously
                    if (seed > 0) {
                        restoreTimer = setTimeout(function() {
                            Math.random = originalRandom;
                        }, 2000);  // Give wordcloud2 time to finish all random calculations
                    }
                }
            } catch (e) {
                console.error('Error initializing word cloud:', e);
                // Fallback: show simple word list
                container.innerHTML = '<p style="text-align: center; color: #999;">Word cloud could not be loaded.</p>';
            }
        });
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWordClouds);
    } else {
        initWordClouds();
    }
    
    // Re-initialize on window resize (debounced)
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Clear existing clouds
            document.querySelectorAll('.keylouds-cloud-container').forEach(function(container) {
                container.innerHTML = '';
                container.removeAttribute('data-wordcloud-initialized');
            });
            // Re-initialize
            initWordClouds();
        }, 250);
    });
    
    // Watch for dynamically added content (for Gutenberg editor)
    if (typeof MutationObserver !== 'undefined') {
        let mutationTimer;
        const observer = new MutationObserver(function(mutations) {
            let shouldInit = false;
            
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check if the added node is a wordcloud container
                        if (node.classList && node.classList.contains('keylouds-cloud-container')) {
                            shouldInit = true;
                        }
                        // Check if the added node contains wordcloud containers
                        if (node.querySelectorAll) {
                            const containers = node.querySelectorAll('.keylouds-cloud-container');
                            if (containers.length > 0) {
                                shouldInit = true;
                            }
                        }
                    }
                });
            });
            
            // Debounce the initialization
            if (shouldInit) {
                clearTimeout(mutationTimer);
                mutationTimer = setTimeout(function() {
                    initWordClouds();
                }, 100);
            }
        });
        
        // Start observing the document for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Expose function globally for manual initialization (useful for editor)
    window.initKeyloudsWordClouds = initWordClouds;
    
})();

