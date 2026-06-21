<?php
/**
 * Plugin Name:       Headless Companion & Smart Purge
 * Description:       Optimize the content editing experience for headless (decoupled) sites. Handles secure previewing, webhook management, automated CDN cache invalidation, and custom WP-CLI tools.
 * Version:           0.1.0
 * Author:            Deepak Kumar
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       headless-companion
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 6.0
 *
 * @package HeadlessCompanion
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

// Define plugin-wide constants.
define( 'HC_VERSION', '0.1.0' );
define( 'HC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load Autoloader.
require_once HC_PLUGIN_DIR . 'includes/Autoloader.php';
\HeadlessCompanion\Autoloader::register();

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'HeadlessCompanion\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HeadlessCompanion\Plugin', 'deactivate' ) );

// Initialize the plugin on plugins_loaded.
add_action( 'plugins_loaded', array( 'HeadlessCompanion\Plugin', 'get_instance' ) );
