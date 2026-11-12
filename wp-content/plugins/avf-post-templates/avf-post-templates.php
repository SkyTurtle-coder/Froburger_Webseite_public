<?php
/**
 * Plugin Name:       AV Froburger Post Templates
 * Description:       Stellt standardisierte Beitragsvorlagen und ein einfaches Bild-Karussell fuer WordPress-Beitraege bereit.
 * Version:           1.1.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            AV Froburger
 * Text Domain:       avf-post-templates
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'AVF_POST_TEMPLATES_VERSION', '1.1.1' );
define( 'AVF_POST_TEMPLATES_DIR', plugin_dir_path( __FILE__ ) );
define( 'AVF_POST_TEMPLATES_URL', plugin_dir_url( __FILE__ ) );

require_once AVF_POST_TEMPLATES_DIR . 'includes/class-avf-post-templates.php';
require_once AVF_POST_TEMPLATES_DIR . 'includes/class-avf-post-carousel-shortcode.php';

/**
 * Bootstraps the plugin once all plugins are loaded.
 *
 * @return void
 */
function avf_post_templates_init() {
	AVF_Post_Templates::instance();
	AVF_Post_Carousel_Shortcode::instance();
}
add_action( 'plugins_loaded', 'avf_post_templates_init' );
