document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Initialize WordPress color pickers
    // Note: wpColorPicker is a jQuery plugin provided by WordPress
    if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
        const colorPickers = document.querySelectorAll('.keylouds-color-picker');
        colorPickers.forEach(function(picker) {
            jQuery(picker).wpColorPicker({
                change: function(event, ui) {
                    // Update preview when color changes
                    updateColorPreview();
                }
            });
        });
    }
    
    function updateColorPreview() {
        const colorSmallInput = document.getElementById('keylouds_color_small');
        const colorMediumInput = document.getElementById('keylouds_color_medium');
        const colorLargeInput = document.getElementById('keylouds_color_large');
        
        if (!colorSmallInput || !colorMediumInput || !colorLargeInput) return;
        
        const colorSmall = colorSmallInput.value;
        const colorMedium = colorMediumInput.value;
        const colorLarge = colorLargeInput.value;
        
        const preview = document.querySelector('.keylouds-color-preview');
        if (preview) {
            const spans = preview.querySelectorAll('span');
            if (spans.length >= 6) {
                spans[0].style.color = colorSmall;
                spans[1].style.color = colorSmall;
                spans[2].style.color = colorMedium;
                spans[3].style.color = colorMedium;
                spans[4].style.color = colorLarge;
                spans[5].style.color = colorLarge;
            }
        }
    }
});

