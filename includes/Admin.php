<?php
/**
 * Admin Class
 *
 * @package Decouplix
 */

namespace Decouplix;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 *
 * Handles the registration of the admin settings page and asset loading.
 */
class Admin {

	/**
	 * Singleton instance of Admin.
	 *
	 * @var Admin|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Admin
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
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register the Settings menu page under Options/Settings.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'Decouplix Settings', 'decouplix' ),
			__( 'Decouplix', 'decouplix' ),
			'manage_options',
			'decouplix',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings page HTML container.
	 *
	 * This serves as the mounting point for our React-based admin app.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<div id="decouplix-settings-root"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue assets for the settings page in the admin dashboard.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Only enqueue on our plugin's settings page.
		if ( 'settings_page_decouplix' !== $hook_suffix ) {
			return;
		}

		// Path to compiled scripts.
		$script_path = DECOUPLIX_PLUGIN_DIR . 'build/admin.js';
		$style_path  = DECOUPLIX_PLUGIN_DIR . 'build/admin.css';

		// Register and enqueue JS/CSS if they exist.
		$js_url  = file_exists( $script_path ) ? DECOUPLIX_PLUGIN_URL . 'build/admin.js' : '';
		$css_url = file_exists( $style_path ) ? DECOUPLIX_PLUGIN_URL . 'build/admin.css' : '';

		if ( ! empty( $js_url ) ) {
			$asset_file   = DECOUPLIX_PLUGIN_DIR . 'build/admin.asset.php';
			$dependencies = array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' );
			$version      = DECOUPLIX_VERSION;

			if ( file_exists( $asset_file ) ) {
				$asset        = include $asset_file;
				$dependencies = isset( $asset['dependencies'] ) ? $asset['dependencies'] : $dependencies;
				$version      = isset( $asset['version'] ) ? $asset['version'] : $version;
			}

			wp_enqueue_script(
				'decouplix-admin-js',
				$js_url,
				$dependencies,
				$version,
				true
			);

			// Localize script with REST API settings and security nonce.
			wp_localize_script(
				'decouplix-admin-js',
				'decouplixSettings',
				array(
					'root'  => esc_url_raw( rest_url() ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
				)
			);
		}

		if ( ! empty( $css_url ) ) {
			wp_enqueue_style(
				'decouplix-admin-css',
				$css_url,
				array( 'wp-components' ),
				DECOUPLIX_VERSION
			);
		}
	}
}
