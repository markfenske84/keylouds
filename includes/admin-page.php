<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'keylouds';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$clouds = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}keylouds ORDER BY created_at DESC"
);
?>

<div class="wrap keylouds-admin-wrap">
    <h1><?php esc_html_e('Keylouds - Keyword Cloud Generator', 'keylouds'); ?></h1>
    
    <div class="keylouds-admin-container">
        <div class="keylouds-create-section">
            <h2><?php esc_html_e('Create New Keyword Cloud', 'keylouds'); ?></h2>
            <p><?php esc_html_e('Enter a URL to scrape and generate a keyword cloud. The plugin will analyze the content and create a visual representation of the most frequently used words.', 'keylouds'); ?></p>
            
            <form id="keylouds-create-form">
                <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="keylouds-title"><?php esc_html_e('Title', 'keylouds'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="keylouds-title" name="title" class="regular-text" placeholder="<?php esc_attr_e('My Keyword Cloud', 'keylouds'); ?>" required>
                        <p class="description"><?php esc_html_e('Give your keyword cloud a descriptive name', 'keylouds'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="keylouds-url"><?php esc_html_e('URL', 'keylouds'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="keylouds-url" name="url" class="regular-text" placeholder="https://example.com" required>
                        <p class="description"><?php esc_html_e('Enter the URL of the webpage you want to analyze', 'keylouds'); ?></p>
                    </td>
                </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large" id="keylouds-create-btn">
                        <span class="dashicons dashicons-tag"></span>
                        <?php esc_html_e('Create Keyword Cloud', 'keylouds'); ?>
                    </button>
                </p>
            </form>
            
            <div id="keylouds-message" style="display: none;"></div>
            <div id="keylouds-loader" style="display: none;">
                <span class="spinner is-active"></span>
                <p><?php esc_html_e('Scraping and analyzing content... This may take a moment.', 'keylouds'); ?></p>
            </div>
        </div>
        
        <div class="keylouds-list-section">
            <h2><?php esc_html_e('Saved Keyword Clouds', 'keylouds'); ?></h2>
            
            <?php if (empty($clouds)): ?>
                <p class="keylouds-empty-state"><?php esc_html_e('No keyword clouds yet. Create your first one above!', 'keylouds'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-title"><?php esc_html_e('Title', 'keylouds'); ?></th>
                            <th scope="col" class="manage-column column-url"><?php esc_html_e('URL', 'keylouds'); ?></th>
                            <th scope="col" class="manage-column column-date"><?php esc_html_e('Created', 'keylouds'); ?></th>
                            <th scope="col" class="manage-column column-shortcode"><?php esc_html_e('Shortcode', 'keylouds'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php esc_html_e('Actions', 'keylouds'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clouds as $cloud): ?>
                            <tr data-cloud-id="<?php echo esc_attr($cloud->id); ?>">
                                <td class="column-title">
                                    <strong><?php echo esc_html($cloud->title); ?></strong>
                                </td>
                                <td class="column-url">
                                    <a href="<?php echo esc_url($cloud->url); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html(wp_trim_words($cloud->url, 8, '...')); ?>
                                    </a>
                                </td>
                                <td class="column-date">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($cloud->created_at))); ?>
                                </td>
                                <td class="column-shortcode">
                                    <code class="keylouds-shortcode-copy" data-shortcode='[keycloud id="<?php echo esc_attr($cloud->id); ?>"]'>
                                        [keycloud id="<?php echo esc_attr($cloud->id); ?>"]
                                    </code>
                                    <button type="button" class="button button-small keylouds-copy-btn" data-shortcode='[keycloud id="<?php echo esc_attr($cloud->id); ?>"]'>
                                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'keylouds'); ?>
                                    </button>
                                </td>
                                <td class="column-actions">
                                    <button type="button" class="button button-small keylouds-preview-btn" data-id="<?php echo esc_attr($cloud->id); ?>">
                                        <span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Preview', 'keylouds'); ?>
                                    </button>
                                    <button type="button" class="button button-small keylouds-shuffle-btn" data-id="<?php echo esc_attr($cloud->id); ?>">
                                        <span class="dashicons dashicons-randomize"></span> <?php esc_html_e('Shuffle', 'keylouds'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete keylouds-delete-btn" data-id="<?php echo esc_attr($cloud->id); ?>">
                                        <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete', 'keylouds'); ?>
                                    </button>
                                </td>
                            </tr>
                            <tr class="keylouds-preview-row" id="keylouds-preview-<?php echo esc_attr($cloud->id); ?>" style="display: none;">
                                <td colspan="5">
                                    <div class="keylouds-preview-wrapper">
                                        <h3><?php esc_html_e('Preview', 'keylouds'); ?></h3>
                                        <?php
                                        $keywords = json_decode($cloud->keywords, true);
                                        $color_small = get_option('keylouds_color_small', '#a3d0e0');
                                        $color_medium = get_option('keylouds_color_medium', '#3498db');
                                        $color_large = get_option('keylouds_color_large', '#2271b1');
                                        $layout_seed = isset($cloud->layout_seed) ? $cloud->layout_seed : 0;
                                        
                                        if (!empty($keywords)):
                                        ?>
                                            <div class="keylouds-cloud-container" 
                                                 data-cloud="<?php echo esc_attr(wp_json_encode($keywords)); ?>"
                                                 data-color-small="<?php echo esc_attr($color_small); ?>"
                                                 data-color-medium="<?php echo esc_attr($color_medium); ?>"
                                                 data-color-large="<?php echo esc_attr($color_large); ?>"
                                                 data-seed="<?php echo esc_attr($layout_seed); ?>">
                                                <!-- Wordcloud2 will render here -->
                                            </div>
                                        <?php else: ?>
                                            <p><?php esc_html_e('No keywords available', 'keylouds'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="keylouds-info-box">
        <h3><?php esc_html_e('How to Use', 'keylouds'); ?></h3>
        <ul>
            <li><strong><?php esc_html_e('Shortcode:', 'keylouds'); ?></strong> <?php esc_html_e('Copy the shortcode from the list above and paste it into any post, page, or widget.', 'keylouds'); ?></li>
            <li><strong><?php esc_html_e('Gutenberg Block:', 'keylouds'); ?></strong> <?php esc_html_e('In the block editor, search for "Keyword Cloud" and select your saved cloud from the dropdown.', 'keylouds'); ?></li>
        </ul>
        <p><em><?php esc_html_e('The keyword cloud will automatically resize to fit its container width, making it perfect for columns and responsive layouts.', 'keylouds'); ?></em></p>
    </div>
</div>

