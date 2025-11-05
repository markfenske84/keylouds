document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Helper function for fade out effect
    function fadeOut(element, duration, callback) {
        element.style.transition = `opacity ${duration}ms`;
        element.style.opacity = '0';
        setTimeout(function() {
            element.style.display = 'none';
            if (callback) callback();
        }, duration);
    }
    
    // Create Keyword Cloud
    const createForm = document.getElementById('keylouds-create-form');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('keylouds-create-btn');
            const loader = document.getElementById('keylouds-loader');
            const message = document.getElementById('keylouds-message');
            const title = document.getElementById('keylouds-title').value;
            const url = document.getElementById('keylouds-url').value;
            
            // Disable form
            btn.disabled = true;
            loader.style.display = 'block';
            message.style.display = 'none';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'keylouds_scrape');
            formData.append('nonce', keyloudsAdmin.nonce);
            formData.append('title', title);
            formData.append('url', url);
            
            // Send AJAX request
            fetch(keyloudsAdmin.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    message.classList.remove('error');
                    message.classList.add('success');
                    message.innerHTML = '<p><strong>Success!</strong> Keyword cloud created successfully. Reloading page...</p>';
                    message.style.display = 'block';
                    
                    // Reload page after 1.5 seconds
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    message.classList.remove('success');
                    message.classList.add('error');
                    message.innerHTML = '<p><strong>Error:</strong> ' + (data.data || 'Failed to create keyword cloud') + '</p>';
                    message.style.display = 'block';
                    
                    btn.disabled = false;
                    loader.style.display = 'none';
                }
            })
            .catch(error => {
                message.classList.remove('success');
                message.classList.add('error');
                message.innerHTML = '<p><strong>Error:</strong> ' + error.message + '</p>';
                message.style.display = 'block';
                
                btn.disabled = false;
                loader.style.display = 'none';
            });
        });
    }
    
    // Copy Shortcode
    document.addEventListener('click', function(e) {
        if (e.target.closest('.keylouds-copy-btn')) {
            const btn = e.target.closest('.keylouds-copy-btn');
            const shortcode = btn.getAttribute('data-shortcode');
            
            // Use modern clipboard API if available
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    // Visual feedback
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="dashicons dashicons-yes"></span> Copied!';
                    
                    setTimeout(function() {
                        btn.innerHTML = originalText;
                    }, 2000);
                });
            } else {
                // Fallback for older browsers
                const temp = document.createElement('input');
                document.body.appendChild(temp);
                temp.value = shortcode;
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                
                // Visual feedback
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="dashicons dashicons-yes"></span> Copied!';
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                }, 2000);
            }
        }
    });
    
    // Preview Toggle
    document.addEventListener('click', function(e) {
        if (e.target.closest('.keylouds-preview-btn')) {
            const btn = e.target.closest('.keylouds-preview-btn');
            const cloudId = btn.getAttribute('data-id');
            const previewRow = document.getElementById('keylouds-preview-' + cloudId);
            const icon = btn.querySelector('.dashicons');
            
            if (previewRow.style.display !== 'none' && previewRow.style.display !== '') {
                previewRow.style.display = 'none';
                icon.classList.remove('dashicons-hidden');
                icon.classList.add('dashicons-visibility');
            } else {
                // Hide all other previews
                document.querySelectorAll('.keylouds-preview-row').forEach(function(row) {
                    row.style.display = 'none';
                });
                document.querySelectorAll('.keylouds-preview-btn .dashicons').forEach(function(ico) {
                    ico.classList.remove('dashicons-hidden');
                    ico.classList.add('dashicons-visibility');
                });
                
                // Show this preview
                previewRow.style.display = 'table-row';
                icon.classList.remove('dashicons-visibility');
                icon.classList.add('dashicons-hidden');
                
                // Re-initialize wordcloud for this preview
                setTimeout(function() {
                    const container = previewRow.querySelector('.keylouds-cloud-container');
                    if (container && typeof WordCloud !== 'undefined') {
                        const cloudData = container.getAttribute('data-cloud');
                        const colorSmall = container.getAttribute('data-color-small') || '#a3d0e0';
                        const colorMedium = container.getAttribute('data-color-medium') || '#3498db';
                        const colorLarge = container.getAttribute('data-color-large') || '#2271b1';
                        
                        if (cloudData) {
                            try {
                                const keywords = JSON.parse(cloudData);
                                const wordList = Object.entries(keywords)
                                    .sort(function(a, b) { return b[1] - a[1]; })
                                    .map(function(entry) { return [entry[0], entry[1]]; });
                                
                                const weights = wordList.map(function(item) { return item[1]; });
                                const maxWeight = Math.max.apply(null, weights);
                                const minWeight = Math.min.apply(null, weights);
                                
                                const containerWidth = container.offsetWidth || 600;
                                const containerHeight = Math.min(containerWidth * 0.6, 400);
                                container.style.height = containerHeight + 'px';
                                
                                function getColorForWeight(weight) {
                                    if (weight <= 3) return colorSmall;
                                    else if (weight <= 6) return colorMedium;
                                    else return colorLarge;
                                }
                                
                                // Seeded random for deterministic layout
                                function seededRandom(s) {
                                    var m = 2147483648, a = 1103515245, c = 12345;
                                    return function() {
                                        s = (a * s + c) % m;
                                        return s / m;
                                    };
                                }
                                
                                // Clear previous cloud
                                container.innerHTML = '';
                                
                                var originalRandom = Math.random;
                                var seed = parseInt(container.getAttribute('data-seed')) || 0;
                                if (seed > 0) {
                                    Math.random = seededRandom(seed);
                                }
                                
                                WordCloud(container, {
                                    list: wordList,
                                    weightFactor: function(size) {
                                        var normalizedWeight = (size - minWeight) / (maxWeight - minWeight);
                                        var minSizePx = containerWidth / 80;
                                        var maxSizePx = containerWidth / 8;
                                        return minSizePx + (normalizedWeight * (maxSizePx - minSizePx));
                                    },
                                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
                                    color: function(word, weight) {
                                        return getColorForWeight(weight);
                                    },
                                    rotateRatio: 0.3,
                                    rotationSteps: 2,
                                    backgroundColor: 'transparent',
                                    drawOutOfBound: false,
                                    shrinkToFit: true,
                                    minSize: 8,
                                    gridSize: 8,
                                    wait: 0
                                });
                                
                                // Delay restoration to let wordcloud2 finish async calculations
                                if (seed > 0) {
                                    setTimeout(function() {
                                        Math.random = originalRandom;
                                    }, 2000);
                                }
                            } catch (e) {
                                console.error('Error initializing preview cloud:', e);
                            }
                        }
                    }
                }, 100);
            }
        }
    });
    
    // Shuffle Layout
    document.addEventListener('click', function(e) {
        if (e.target.closest('.keylouds-shuffle-btn')) {
            const btn = e.target.closest('.keylouds-shuffle-btn');
            const cloudId = btn.getAttribute('data-id');
            const previewRow = document.getElementById('keylouds-preview-' + cloudId);
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="dashicons dashicons-update"></span> Shuffling...';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'keylouds_shuffle');
            formData.append('nonce', keyloudsAdmin.nonce);
            formData.append('id', cloudId);
            
            fetch(keyloudsAdmin.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the seed and re-render
                    const container = previewRow.querySelector('.keylouds-cloud-container');
                    if (container) {
                        container.setAttribute('data-seed', data.data.seed);
                        container.innerHTML = '';
                        container.removeAttribute('data-wordcloud-initialized');
                        
                        // Show preview if hidden
                        if (previewRow.style.display === 'none' || previewRow.style.display === '') {
                            previewRow.style.display = 'table-row';
                            const previewBtn = document.querySelector('.keylouds-preview-btn[data-id="' + cloudId + '"]');
                            if (previewBtn) {
                                const icon = previewBtn.querySelector('.dashicons');
                                if (icon) {
                                    icon.classList.remove('dashicons-visibility');
                                    icon.classList.add('dashicons-hidden');
                                }
                            }
                        }
                        
                        // Re-initialize wordcloud with new seed
                        setTimeout(function() {
                            if (typeof WordCloud !== 'undefined') {
                                const cloudData = container.getAttribute('data-cloud');
                                const colorSmall = container.getAttribute('data-color-small') || '#a3d0e0';
                                const colorMedium = container.getAttribute('data-color-medium') || '#3498db';
                                const colorLarge = container.getAttribute('data-color-large') || '#2271b1';
                                const seed = parseInt(data.data.seed);
                                
                                if (cloudData) {
                                    try {
                                        const keywords = JSON.parse(cloudData);
                                        const wordList = Object.entries(keywords)
                                            .sort(function(a, b) { return b[1] - a[1]; })
                                            .map(function(entry) { return [entry[0], entry[1]]; });
                                        
                                        const weights = wordList.map(function(item) { return item[1]; });
                                        const maxWeight = Math.max.apply(null, weights);
                                        const minWeight = Math.min.apply(null, weights);
                                        
                                        const containerWidth = container.offsetWidth || 600;
                                        const containerHeight = Math.min(containerWidth * 0.6, 400);
                                        container.style.height = containerHeight + 'px';
                                        
                                        function getColorForWeight(weight) {
                                            if (weight <= 3) return colorSmall;
                                            else if (weight <= 6) return colorMedium;
                                            else return colorLarge;
                                        }
                                        
                                        // Seeded random for deterministic layout
                                        function seededRandom(s) {
                                            var m = 2147483648, a = 1103515245, c = 12345;
                                            return function() {
                                                s = (a * s + c) % m;
                                                return s / m;
                                            };
                                        }
                                        
                                        container.setAttribute('data-wordcloud-initialized', 'true');
                                        var originalRandom = Math.random;
                                        if (seed > 0) {
                                            Math.random = seededRandom(seed);
                                        }
                                        
                                        WordCloud(container, {
                                            list: wordList,
                                            weightFactor: function(size) {
                                                var normalizedWeight = (size - minWeight) / (maxWeight - minWeight);
                                                var minSizePx = containerWidth / 80;
                                                var maxSizePx = containerWidth / 8;
                                                return minSizePx + (normalizedWeight * (maxSizePx - minSizePx));
                                            },
                                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
                                            color: function(word, weight) {
                                                return getColorForWeight(weight);
                                            },
                                            rotateRatio: 0.3,
                                            rotationSteps: 2,
                                            backgroundColor: 'transparent',
                                            drawOutOfBound: false,
                                            shrinkToFit: true,
                                            minSize: 8,
                                            gridSize: 8,
                                            wait: 0
                                        });
                                        
                                        // Delay restoration to let wordcloud2 finish async calculations
                                        if (seed > 0) {
                                            setTimeout(function() {
                                                Math.random = originalRandom;
                                            }, 2000);
                                        }
                                    } catch (e) {
                                        console.error('Error shuffling cloud:', e);
                                    }
                                }
                            }
                            
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }, 100);
                    }
                } else {
                    alert('Error: Failed to shuffle layout');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(error => {
                alert('Error: Failed to shuffle layout');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }
    });
    
    // Delete Keyword Cloud
    document.addEventListener('click', function(e) {
        if (e.target.closest('.keylouds-delete-btn')) {
            const btn = e.target.closest('.keylouds-delete-btn');
            const cloudId = btn.getAttribute('data-id');
            
            if (!confirm('Are you sure you want to delete this keyword cloud?')) {
                return;
            }
            
            btn.disabled = true;
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'keylouds_delete');
            formData.append('nonce', keyloudsAdmin.nonce);
            formData.append('id', cloudId);
            
            fetch(keyloudsAdmin.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the row with animation
                    const row = btn.closest('tr');
                    const previewRow = document.getElementById('keylouds-preview-' + cloudId);
                    
                    fadeOut(row, 400, function() {
                        row.remove();
                        if (previewRow) {
                            previewRow.remove();
                        }
                        
                        // Check if table is empty
                        const remainingRows = document.querySelectorAll('.wp-list-table tbody tr:not(.keylouds-preview-row)');
                        if (remainingRows.length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert('Error: Failed to delete keyword cloud');
                    btn.disabled = false;
                }
            })
            .catch(error => {
                alert('Error: Failed to delete keyword cloud');
                btn.disabled = false;
            });
        }
    });
});

