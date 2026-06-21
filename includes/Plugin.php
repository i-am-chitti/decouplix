<?php
/**
 * Main Plugin Class
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Main orchestrator of the Headless Companion plugin.
 */
class Plugin {

	/**
	 * Singleton instance of the plugin.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin components.
	 */
	private function init() {
		if ( is_admin() ) {
			Admin::get_instance();
		}

		REST_API::get_instance();
	}

	/**
	 * Plugin activation callback.
	 *
	 * Sets up default option values if they do not exist.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false === get_option( 'hc_settings' ) ) {
			update_option(
				'hc_settings',
				array(
					'frontend_url'    => '',
					'webhook_secret'  => wp_generate_password( 32, false ),
					'cache_endpoints' => '',
				)
			);
		}
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * Performs cleanup activities on deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Clear rewrite rules or scheduled actions if any are added in later steps.
	}
}
