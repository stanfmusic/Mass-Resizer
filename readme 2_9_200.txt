=== Mass Resizer ===
Contributors: yourwordpressusername
Tags: image compression, WebP, bulk image resizing, WordPress optimization, multilingual
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 2.9.200
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Mass Resizer is a powerful WordPress plugin that allows users to easily bulk replace images with compressed WebP formats, optimizing your site for faster load times and better performance. It also offers options for deleting original images after conversion, making your site even more efficient.

Mass Resizer

WordPress Plugin 2.9.200
Image Cropping (Enable image cropping):
Crops images to the specified maximum width and height. Limitation: When cropping is enabled, image replacement with WebP is not allowed to avoid issues with thumbnails and their count.
Maximum Image Width / Maximum Image Height:
Sets the maximum dimensions for images that will be cropped.
WebP Compression (Enable WebP Compression):
Converts images to WebP format to improve compression.
When enabled, additional settings are available: compression level, replacing old images, and removing originals.
Compression Percentage:
Sets the compression level for WebP images (from 0% to 100%).
Replace Old Images with WebP (Replace photos on all pages):
Replaces old images with WebP across the site.
Limitation: This feature is not available when image cropping is enabled, as it may cause thumbnail errors.
Remove Old Images After Conversion (Remove old images after conversion):
Deletes original images after converting them to WebP. Works even with image cropping enabled.
Important Notes:
When image cropping is enabled, only the "Remove Old Images" function is available. Image replacement with WebP will not be accessible.
Make sure to back up your site before using the plugin to avoid data loss.


== Changelog ==

= 2.9.200 (10.11.2024) =
* **New Features and Improvements**:
  - Optimized major and minor bugs to enhance stability and performance.
  - Added multilingual interface (RUS and ENG) for better usability for users from different countries.
  - Introduced error logging for detailed tracking of issues and quick resolution of problems.
  - Added process logs for easy monitoring of operations and plugin performance analysis.
  - Updated interface design to improve the user experience.

* **Key Features**:
  - Bulk image replacement with compressed WebP formats on pages and posts, optimized for themes compatible with WordPress standards.
  - Option to delete original images after conversion to WebP to free up space and optimize page load times.

== Installation ==

1. Upload the `mass-resizer` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure the plugin settings through the 'Mass Resizer' menu in the dashboard.

== Frequently Asked Questions ==

= How do I use the Mass Resizer plugin? =
1. After activation, go to the 'Mass Resizer' settings page.
2. Choose the images you want to convert and replace with WebP.
3. Select whether to delete original images after conversion.
4. Click 'Start Process' to begin the bulk resizing and replacement.

= Can I revert to the original images after converting them to WebP? =
No, once the original images are deleted (if you choose to delete them), they cannot be restored. Make sure to back up your images before using the conversion feature.


== License ==

GPLv2 or later
