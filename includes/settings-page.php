<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current color settings
$color_small = get_option('keylouds_color_small', '#a3d0e0');
$color_medium = get_option('keylouds_color_medium', '#3498db');
$color_large = get_option('keylouds_color_large', '#2271b1');
?>

<div class="wrap keylouds-settings-wrap">
    <h1><?php esc_html_e('Keylouds Settings', 'keylouds'); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('keylouds_settings'); ?>
        
        <div class="keylouds-settings-container">
            <h2><?php esc_html_e('Default Word Colors', 'keylouds'); ?></h2>
            <p><?php esc_html_e('Set default colors for keyword clouds based on font size. These colors will be used unless overridden in individual blocks.', 'keylouds'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="keylouds_color_small"><?php esc_html_e('Small Words Color', 'keylouds'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="keylouds_color_small" name="keylouds_color_small" value="<?php echo esc_attr($color_small); ?>" class="keylouds-color-picker" data-default-color="#a3d0e0">
                        <p class="description"><?php esc_html_e('Color for words with font size less than 1em', 'keylouds'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keylouds_color_medium"><?php esc_html_e('Medium Words Color', 'keylouds'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="keylouds_color_medium" name="keylouds_color_medium" value="<?php echo esc_attr($color_medium); ?>" class="keylouds-color-picker" data-default-color="#3498db">
                        <p class="description"><?php esc_html_e('Color for words with font size between 1em and 1.5em', 'keylouds'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keylouds_color_large"><?php esc_html_e('Large Words Color', 'keylouds'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="keylouds_color_large" name="keylouds_color_large" value="<?php echo esc_attr($color_large); ?>" class="keylouds-color-picker" data-default-color="#2271b1">
                        <p class="description"><?php esc_html_e('Color for words with font size 1.5em and larger', 'keylouds'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(esc_html__('Save Settings', 'keylouds')); ?>
        </div>
    </form>
    
    <div class="keylouds-info-box" style="margin-top: 30px;">
        <h3><?php esc_html_e('Color Preview Examples', 'keylouds'); ?></h3>
        <div class="keylouds-color-preview">
            <span style="font-size: 0.8em; color: <?php echo esc_attr($color_small); ?>; font-weight: 600;">small</span>
            <span style="font-size: 0.9em; color: <?php echo esc_attr($color_small); ?>; font-weight: 600;">words</span>
            <span style="font-size: 1.2em; color: <?php echo esc_attr($color_medium); ?>; font-weight: 600;">medium</span>
            <span style="font-size: 1.4em; color: <?php echo esc_attr($color_medium); ?>; font-weight: 600;">sized</span>
            <span style="font-size: 2.0em; color: <?php echo esc_attr($color_large); ?>; font-weight: 600;">large</span>
            <span style="font-size: 2.5em; color: <?php echo esc_attr($color_large); ?>; font-weight: 600;">words</span>
        </div>
        
        <h3><?php esc_html_e('Block-Level Overrides', 'keylouds'); ?></h3>
        <p><?php esc_html_e('When using the Gutenberg block, you can override these default colors in the block settings sidebar. Each block can have its own custom colors.', 'keylouds'); ?></p>
        
        <h3><?php esc_html_e('Shortcode Usage', 'keylouds'); ?></h3>
        <p><?php esc_html_e('To use custom colors in a shortcode, add color attributes:', 'keylouds'); ?></p>
        <code>[keycloud id="1" color_small="#999999" color_medium="#0073aa" color_large="#d63638"]</code>
    </div>
</div>

