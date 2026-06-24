<?php
/**
 * Preview Class
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion;

defined( 'ABSPATH' ) || exit;

use WP_Post;

/**
 * Class Preview
 *
 * Directs WordPress preview requests to the decoupled frontend.
 */
class Preview {

	/**
	 * Singleton instance of Preview.
	 *
	 * @var Preview|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Preview
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
		add_filter( 'preview_post_link', array( $this, 'filter_preview_link' ), 10, 2 );
		add_filter( 'determine_current_user', array( $this, 'authenticate_preview_request' ), 99 );
	}

	/**
	 * Filter the preview link to target the headless frontend.
	 *
	 * @param string  $preview_link The original WordPress preview link.
	 * @param WP_Post $post         The post object.
	 * @return string Modified preview link.
	 */
	public function filter_preview_link( $preview_link, $post ) {
		if ( ! $post instanceof WP_Post ) {
			return $preview_link;
		}

		$settings     = get_option( 'hc_settings', array() );
		$frontend_url = isset( $settings['frontend_url'] ) ? $settings['frontend_url'] : '';
		$secret       = isset( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : '';

		if ( empty( $frontend_url ) ) {
			return $preview_link;
		}

		$preview_base = rtrim( $frontend_url, '/' ) . '/api/preview';

		$args = array(
			'id'     => $post->ID,
			'secret' => $secret,
			'type'   => $post->post_type,
		);

		return add_query_arg( $args, $preview_base );
	}

	/**
	 * Authenticate the preview request if a valid secret is present.
	 *
	 * @param int|false $user_id The current authenticated user ID, or false.
	 * @return int|false Modified user ID.
	 */
	public function authenticate_preview_request( $user_id ) {
		// If already authenticated, do not override.
		if ( ! empty( $user_id ) ) {
			return $user_id;
		}

		$request_secret = '';

		// 1. Check HTTP Header 'X-HC-Secret'
		if ( isset( $_SERVER['HTTP_X_HC_SECRET'] ) && ! empty( $_SERVER['HTTP_X_HC_SECRET'] ) ) {
			$request_secret = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_HC_SECRET'] ) );
		} elseif ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) && ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			// 2. Check Authorization Header (Bearer token)
			$auth_header = sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) );
			if ( preg_match( '/Bearer\s+(.+)/i', $auth_header, $matches ) ) {
				$request_secret = $matches[1];
			}
		} elseif ( function_exists( 'apache_request_headers' ) ) {
			// 3. Fallback to Apache request headers if HTTP_AUTHORIZATION is missing due to server config
			$headers = apache_request_headers();
			if ( isset( $headers['Authorization'] ) && ! empty( $headers['Authorization'] ) ) {
				$auth_header = sanitize_text_field( wp_unslash( $headers['Authorization'] ) );
				if ( preg_match( '/Bearer\s+(.+)/i', $auth_header, $matches ) ) {
					$request_secret = $matches[1];
				}
			}
		}

		if ( empty( $request_secret ) ) {
			return $user_id;
		}

		$settings = get_option( 'hc_settings', array() );
		$secret   = isset( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : '';

		if ( empty( $secret ) || ! hash_equals( $secret, $request_secret ) ) {
			return $user_id;
		}

		// Retrieve an administrator user to authenticate the request.
		$admin_users = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		);

		if ( ! empty( $admin_users ) && isset( $admin_users[0]->ID ) ) {
			return $admin_users[0]->ID;
		}

		return $user_id;
	}
}
