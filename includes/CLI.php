<?php
/**
 * CLI Class
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion;

defined( 'ABSPATH' ) || exit;

use WP_CLI;

/**
 * Class CLI
 *
 * Implements WP-CLI commands for Headless Companion management.
 */
class CLI {

	/**
	 * Singleton instance of CLI.
	 *
	 * @var CLI|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return CLI
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
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'headless status', array( $this, 'status' ) );
			WP_CLI::add_command( 'headless webhook-trigger', array( $this, 'webhook_trigger' ) );
			WP_CLI::add_command( 'headless purge', array( $this, 'purge' ) );
		}
	}

	/**
	 * Perform a health check on the configured endpoints.
	 *
	 * ## EXAMPLES
	 *
	 *     wp headless status
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 * @when after_wp_load
	 */
	public function status( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$settings    = get_option( 'hc_settings', array() );
		$url         = isset( $settings['frontend_url'] ) ? $settings['frontend_url'] : '';
		$webhook_url = isset( $settings['webhook_url'] ) ? $settings['webhook_url'] : '';
		$secret      = isset( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : '';
		$purge       = isset( $settings['cache_endpoints'] ) ? $settings['cache_endpoints'] : '';

		WP_CLI::line( 'Headless Companion Configuration Status:' );
		WP_CLI::line( '----------------------------------------' );
		WP_CLI::line( sprintf( 'Frontend URL:   %s', ! empty( $url ) ? $url : 'Not Configured' ) );
		WP_CLI::line( sprintf( 'Webhook URL:    %s', ! empty( $webhook_url ) ? $webhook_url : 'Not Configured' ) );
		WP_CLI::line( sprintf( 'Webhook Secret: %s', ! empty( $secret ) ? 'Configured (Secret Hidden)' : 'Not Configured' ) );

		if ( empty( $purge ) ) {
			WP_CLI::line( 'Cache Purge Endpoints: Not Configured' );
			return;
		}

		$endpoints = array_map( 'trim', explode( "\n", str_replace( "\r", '', $purge ) ) );
		$endpoints = array_filter( $endpoints, 'esc_url_raw' );

		WP_CLI::line( sprintf( 'Cache Purge Endpoints (%d):', count( $endpoints ) ) );
		foreach ( $endpoints as $endpoint ) {
			WP_CLI::line( sprintf( '  - %s', $endpoint ) );
		}
	}

	/**
	 * Manually trigger a webhook event for a specific post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The ID of the post to trigger the webhook for.
	 *
	 * ## EXAMPLES
	 *
	 *     wp headless webhook-trigger 123
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 * @when after_wp_load
	 */
	public function webhook_trigger( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		list( $post_id ) = $args;
		$post            = get_post( $post_id );

		if ( ! $post ) {
			WP_CLI::error( sprintf( 'Post with ID %d not found.', $post_id ) );
		}

		WP_CLI::log( sprintf( 'Building webhook payload for post ID %d...', $post_id ) );
		$webhook = Webhook::get_instance();
		$payload = $webhook->build_payload( $post, 'update', $post->post_status, $post->post_status );

		WP_CLI::log( 'Dispatching webhook event...' );
		$webhook->dispatch_webhook( $payload );

		WP_CLI::success( 'Webhook triggered successfully.' );
	}

	/**
	 * Manually purge a custom path on the headless frontend cache.
	 *
	 * ## OPTIONS
	 *
	 * <path>
	 * : The relative path to purge, e.g. /about or /blog.
	 *
	 * ## EXAMPLES
	 *
	 *     wp headless purge /about
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 * @when after_wp_load
	 */
	public function purge( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		list( $path ) = $args;
		$path         = '/' . ltrim( $path, '/' );

		WP_CLI::log( sprintf( 'Queueing purge for relative path: %s...', $path ) );
		Cache::get_instance()->enqueue_purge( array( $path ) );

		WP_CLI::success( 'Cache purge queued successfully.' );
	}
}
