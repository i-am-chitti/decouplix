<?php
/**
 * Cache Class
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion;

defined( 'ABSPATH' ) || exit;

use WP_Post;

/**
 * Class Cache
 *
 * Handles smart cache invalidation for the headless frontend.
 */
class Cache {

	/**
	 * Singleton instance of Cache.
	 *
	 * @var Cache|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Cache
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
		add_action( 'transition_post_status', array( $this, 'handle_post_purge' ), 10, 3 );
		add_action( 'hc_purge_paths_async', array( $this, 'purge_paths' ) );
		add_action( 'hc_purge_paths_cron', array( $this, 'purge_paths' ) );
	}

	/**
	 * Inspect post transition and queue cache invalidation if needed.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       Post object.
	 * @return void
	 */
	public function handle_post_purge( $new_status, $old_status, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( 'revision' === $post->post_type ) {
			return;
		}

		// Ensure it is a public post type.
		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! $post_type_obj || ! $post_type_obj->public ) {
			return;
		}

		// Determine if content has changed publicly.
		$should_purge = false;
		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			$should_purge = true;
		}

		if ( ! $should_purge ) {
			return;
		}

		$paths = $this->gather_paths( $post );

		if ( empty( $paths ) ) {
			return;
		}

		$this->enqueue_purge( $paths );
	}

	/**
	 * Gather related frontend paths for a given post.
	 *
	 * @param WP_Post $post Post object.
	 * @return array List of relative paths.
	 */
	public function gather_paths( $post ) {
		$paths = array();

		// 1. Post permalink path.
		$permalink = get_permalink( $post->ID );
		if ( ! empty( $permalink ) ) {
			$path    = wp_make_link_relative( $permalink );
			$paths[] = '/' . ltrim( $path, '/' );
		}

		// 2. Always purge the home page.
		$paths[] = '/';

		// 3. Purge taxonomies (categories, tags, custom taxonomies).
		$taxonomies = get_object_taxonomies( $post->post_type );
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				$terms = get_the_terms( $post->ID, $taxonomy );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$term_link = get_term_link( $term );
						if ( ! is_wp_error( $term_link ) && ! empty( $term_link ) ) {
							$term_path = wp_make_link_relative( $term_link );
							$paths[]   = '/' . ltrim( $term_path, '/' );
						}
					}
				}
			}
		}

		return array_values( array_unique( array_filter( $paths ) ) );
	}

	/**
	 * Enqueue paths to be purged in the background.
	 *
	 * @param array $paths Array of relative paths.
	 * @return void
	 */
	public function enqueue_purge( $paths ) {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( 'hc_purge_paths_async', array( $paths ), 'headless-companion' );
		} else {
			wp_schedule_single_event( time(), 'hc_purge_paths_cron', array( $paths ) );
		}
	}

	/**
	 * Send purge request to configured cache revalidation endpoints.
	 *
	 * @param array $paths Array of relative paths to purge.
	 * @return void
	 */
	public function purge_paths( $paths ) {
		$settings         = get_option( 'hc_settings', array() );
		$endpoints_string = isset( $settings['cache_endpoints'] ) ? $settings['cache_endpoints'] : '';
		$secret           = isset( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : '';

		if ( empty( $endpoints_string ) ) {
			return;
		}

		// Parse endpoints line by line.
		$endpoints = array_map( 'trim', explode( "\n", str_replace( "\r", '', $endpoints_string ) ) );
		$endpoints = array_filter( $endpoints, 'esc_url_raw' );

		if ( empty( $endpoints ) ) {
			return;
		}

		$payload = array(
			'event' => 'cache_purge',
			'paths' => $paths,
		);
		$body    = wp_json_encode( $payload );

		$signature = '';
		if ( ! empty( $secret ) ) {
			$signature = hash_hmac( 'sha256', $body, $secret );
		}

		$headers = array(
			'Content-Type' => 'application/json',
		);
		if ( ! empty( $signature ) ) {
			$headers['X-Hub-Signature-256'] = 'sha256=' . $signature;
		}

		foreach ( $endpoints as $endpoint ) {
			wp_remote_post(
				$endpoint,
				array(
					'method'      => 'POST',
					'headers'     => $headers,
					'body'        => $body,
					'timeout'     => 15,
					'redirection' => 5,
					'blocking'    => true,
				)
			);

			do_action( 'hc_cache_purged', $endpoint, $paths );
		}
	}
}
