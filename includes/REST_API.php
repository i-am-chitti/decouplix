<?php
/**
 * REST API Class
 *
 * @package Decouplix
 */

namespace Decouplix;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class REST_API
 *
 * Exposes secure endpoints to manage plugin settings.
 */
class REST_API {

	/**
	 * Namespace for the REST API routes.
	 *
	 * @var string
	 */
	private $namespace = 'decouplix/v1';

	/**
	 * Route path for settings.
	 *
	 * @var string
	 */
	private $route = '/settings';

	/**
	 * Singleton instance of REST_API.
	 *
	 * @var REST_API|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return REST_API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->route,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'frontend_url'    => array(
							'required'          => false,
							'sanitize_callback' => 'esc_url_raw',
							'validate_callback' => array( $this, 'validate_url' ),
						),
						'webhook_url'     => array(
							'required'          => false,
							'sanitize_callback' => 'esc_url_raw',
							'validate_callback' => array( $this, 'validate_url' ),
						),
						'webhook_secret'  => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'cache_endpoints' => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Check if the current user has permissions to manage options.
	 *
	 * @return bool
	 */
	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Validate Frontend URL.
	 *
	 * @param mixed           $value   The value being validated.
	 * @param WP_REST_Request $request The REST request.
	 * @param string          $param   The parameter name.
	 * @return bool|WP_Error
	 */
	public function validate_url( $value, $request, $param ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( empty( $value ) ) {
			return true;
		}

		if ( ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
			return new WP_Error(
				'rest_invalid_url',
				__( 'The provided Front-end URL is invalid.', 'decouplix' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Retrieve plugin settings.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function get_settings( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$settings = get_option( 'decouplix_settings', array() );

		$defaults = array(
			'frontend_url'    => '',
			'webhook_url'     => '',
			'webhook_secret'  => '',
			'cache_endpoints' => '',
		);

		$settings = wp_parse_args( $settings, $defaults );

		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update plugin settings.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( $request ) {
		$settings = get_option( 'decouplix_settings', array() );

		if ( $request->has_param( 'frontend_url' ) ) {
			$settings['frontend_url'] = $request->get_param( 'frontend_url' );
		}
		if ( $request->has_param( 'webhook_url' ) ) {
			$settings['webhook_url'] = $request->get_param( 'webhook_url' );
		}
		if ( $request->has_param( 'webhook_secret' ) ) {
			$settings['webhook_secret'] = $request->get_param( 'webhook_secret' );
		}
		if ( $request->has_param( 'cache_endpoints' ) ) {
			$settings['cache_endpoints'] = $request->get_param( 'cache_endpoints' );
		}

		$updated = update_option( 'decouplix_settings', $settings );

		if ( ! $updated ) {
			$current = get_option( 'decouplix_settings', array() );
			if ( $current !== $settings ) {
				return new WP_Error(
					'rest_save_failed',
					__( 'Failed to update settings in the database.', 'decouplix' ),
					array( 'status' => 500 )
				);
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Settings updated successfully.', 'decouplix' ),
				'data'    => $settings,
			),
			200
		);
	}
}
