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
}
