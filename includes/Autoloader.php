<?php
/**
 * PSR-4 Autoloader for Headless Companion
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion;

defined( 'ABSPATH' ) || exit;

/**
 * Class Autoloader
 *
 * Handles automatic loading of class files based on their PSR-4 namespace.
 */
class Autoloader {

	/**
	 * Register the autoloader with the SPL autoload stack.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload class files matching the HeadlessCompanion namespace prefix.
	 *
	 * @param string $class_name The fully-qualified class name.
	 * @return void
	 */
	public static function autoload( $class_name ) {
		// Project-specific namespace prefix.
		$prefix = 'HeadlessCompanion\\';

		// Base directory for the namespace prefix.
		$base_dir = __DIR__ . '/';

		// Does the class use the namespace prefix?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			// No, move to the next registered autoloader.
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $class_name, $len );

		// Map namespace separators to directory separators, then append '.php'.
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// If the file exists, load it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
