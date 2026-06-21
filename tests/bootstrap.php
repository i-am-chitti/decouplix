<?php
/**
 * PHPUnit Bootstrap
 *
 * @package HeadlessCompanion
 */

// Define path constants.
define( 'HC_TESTS_DIR', __DIR__ );
define( 'HC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );

// Define mock ABSPATH and functions if not running inside real WordPress test suite.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', HC_PLUGIN_DIR );
}

// Load Composer autoloader.
if ( file_exists( HC_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once HC_PLUGIN_DIR . 'vendor/autoload.php';
}

// Load the autoloader.
require_once HC_PLUGIN_DIR . 'includes/Autoloader.php';
\HeadlessCompanion\Autoloader::register();

// Minimal mocks for basic unit tests.
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
}
if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $file, $callback ) {}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		return true;
	}
}
if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
		return substr( bin2hex( random_bytes( $length ) ), 0, $length );
	}
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return dirname( $file ) . '/';
	}
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'https://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}
if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}
