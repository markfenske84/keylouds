document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Initialize WordPress color pickers
    if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
        jQuery('.keylouds-color-picker').wpColorPicker({
            change: function(event, ui) {
                // Update preview when color changes
                updateColorPreview();
            }
        });
    }
    
    function updateColorPreview() {
        if (typeof jQuery === 'undefined') return;
        
        const colorSmall = jQuery('#keylouds_color_small').val();
        const colorMedium = jQuery('#keylouds_color_medium').val();
        const colorLarge = jQuery('#keylouds_color_large').val();
        
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

