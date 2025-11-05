=== Keylouds ===
Contributors: webforagency
Tags: keywords, word cloud, scraper, visualization, gutenberg
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create visual keyword clouds from any URL. Display the most frequently used words from any webpage with responsive, flexible sizing.

== Description ==

Keylouds is a powerful webpage keyword cloud generator that scrapes websites and creates beautiful visual representations of keyword frequencies. Perfect for content analysis, SEO research, and creating engaging visual displays.

Now powered by **wordcloud2.js** for stunning, interactive word cloud visualizations using HTML elements (SEO-friendly!).

== Features ==

- **Web Scraping**: Extract content from any URL
- **Keyword Analysis**: Automatically analyze and rank keywords by frequency
- **Interactive Visualizations**: Powered by wordcloud2.js for beautiful, dynamic word clouds
- **SEO-Friendly**: Uses HTML elements instead of canvas for search engine indexing
- **Multiple Display Options**: Use shortcodes or Gutenberg blocks
- **Responsive Design**: Automatically scales to fit any container width
- **Admin Interface**: Easy-to-use interface for creating and managing keyword clouds
- **Live Previews**: See word clouds rendered in real-time in the admin panel
- **Shuffle Layouts**: Try different word arrangements with the shuffle button
- **Consistent Layouts**: Word clouds display the same way every time (deterministic)
- **Color Customization**: Customize colors for small, medium, and large words
- **Smart Sizing**: Most used words are dramatically larger than less common words

== Installation ==

1. Upload the `keylouds` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Keylouds' in the admin menu to start creating keyword clouds

== Usage ==

= Creating a Keyword Cloud =

1. Go to **Keylouds** in the WordPress admin menu
2. Enter a title for your keyword cloud
3. Enter the URL of the webpage you want to analyze
4. Click **Create Keyword Cloud**
5. Wait while the plugin scrapes and analyzes the content

= Displaying Keyword Clouds =

Using Shortcode:

Copy the shortcode from the admin page and paste it anywhere:

```
[keycloud id="1"]
```

Using Gutenberg Block:

1. Add a new block in the editor
2. Search for "Keyword Cloud"
3. Select your saved keyword cloud from the dropdown
4. The cloud will display in the editor and on the frontend

== How It Works ==

The plugin:
1. Fetches content from the provided URL using WordPress HTTP API
2. Strips HTML tags and extracts text content
3. Removes common stop words (a, an, the, etc.)
4. Counts word frequencies
5. Normalizes frequencies to a scale of 1-10 for sizing
6. Displays keywords alphabetically with size based on frequency

== Responsive Sizing ==

The keyword clouds automatically resize based on:
- Container width (perfect for columns and sidebars)
- Screen size (mobile-friendly)
- Uses CSS container queries for optimal flexibility

== Technical Details ==

- **wordcloud2.js Integration**: v1.2.2 included in plugin
- **HTML Mode**: Words rendered as HTML elements for SEO and accessibility
- No jQuery dependencies (pure vanilla JavaScript)
- Uses modern Fetch API for AJAX requests
- Server-side rendering for Gutenberg blocks
- Database table: `wp_keylouds`
- Supports up to 50 keywords per cloud
- Responsive visualization with automatic resize handling
- Noscript fallback for non-JavaScript environments

== Frequently Asked Questions ==

= Can I use this with any website? =
Yes, Keylouds can scrape and analyze content from any publicly accessible webpage.

= How many keywords are displayed? =
Up to 50 keywords per cloud, ranked by frequency.

= Can I customize the colors? =
Yes, you can set default colors in the settings page or override them per block/shortcode.

= Why does the word cloud look the same every time? =
Word clouds use deterministic layouts (v1.1.0+) so they appear consistently across page loads. This improves user experience and SEO. Use the "Shuffle" button in the admin panel to generate a new layout.

= How do I change the layout of a word cloud? =
Click the "Shuffle" button next to any word cloud in the admin panel. The preview will update immediately, and the new layout will be saved for the frontend.

= Are the largest words really the most common? =
Yes! The word sizing algorithm ensures that high-frequency words are significantly larger. Words are also sorted by frequency before positioning, giving common words priority for center placement.

== Browser Support ==

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Fallback clipboard support for older browsers
- Responsive on all device sizes

== Changelog ==

= 1.1.0 =
* Added wordcloud2.js library integration (v1.2.2)
* HTML-based word cloud rendering for SEO benefits
* **Deterministic layouts** - word clouds look the same on every page load
* **Shuffle button** - preview and generate different layouts in admin
* **Improved word sizing** - most used words are now much larger
* Interactive word clouds with hover effects
* Responsive visualization that auto-scales on resize
* Enhanced admin previews with live wordcloud2 rendering
* Improved editor experience with real-time wordcloud preview
* Noscript fallback for accessibility
* Better color distribution across word weights
* Automatic database migration for existing installations

= 1.0.0 =
* Initial release
* Web scraping functionality
* Keyword analysis and visualization
* Shortcode support
* Gutenberg block integration
* Color customization options

== Upgrade Notice ==

= 1.1.0 =
Major visualization upgrade! Now using wordcloud2.js for interactive, SEO-friendly HTML word clouds. Existing keyword clouds will automatically display with the new visualization.

= 1.0.0 =
Initial release of Keylouds - Webpage Keyword Cloud Generator.

