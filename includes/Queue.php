<?php
/**
 * Queue Class
 *
 * @package Decouplix
 */

namespace Decouplix;

defined( 'ABSPATH' ) || exit;

/**
 * Class Queue
 *
 * Handles asynchronous execution of webhooks using Action Scheduler or WP Cron.
 */
class Queue {

	/**
	 * Singleton instance of Queue.
	 *
	 * @var Queue|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Queue
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
		add_action( 'decouplix_webhook_triggered', array( $this, 'enqueue_webhook' ) );
		add_action( 'decouplix_deliver_webhook_async', array( $this, 'deliver_webhook' ) );
		add_action( 'decouplix_deliver_webhook_cron', array( $this, 'deliver_webhook' ) );
	}

	/**
	 * Enqueue the webhook payload for background execution.
	 *
	 * Checks if Action Scheduler is available, otherwise falls back to WP Cron.
	 *
	 * @param array $payload Webhook payload.
	 * @return void
	 */
	public function enqueue_webhook( $payload ) {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( 'decouplix_deliver_webhook_async', array( $payload ), 'decouplix' );
		} else {
			wp_schedule_single_event( time(), 'decouplix_deliver_webhook_cron', array( $payload ) );
		}
	}

	/**
	 * Deliver the webhook payload to the configured frontend.
	 *
	 * Signs the payload with HMAC-SHA256 using the configured secret key.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool|\WP_Error True on success, WP_Error or false on failure.
	 */
	public function deliver_webhook( $payload ) {
		$settings = get_option( 'hc_settings', array() );
		$url      = isset( $settings['webhook_url'] ) ? $settings['webhook_url'] : '';
		$secret   = isset( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : '';

		if ( empty( $url ) ) {
			return false;
		}

		$body = wp_json_encode( $payload );

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

		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'body'        => $body,
				'timeout'     => 15,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
			)
		);

		do_action( 'decouplix_webhook_delivered', $response, $payload );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			return new \WP_Error(
				'hc_webhook_http_error',
				sprintf( 'Webhook delivery failed with HTTP status code %d', $status_code )
			);
		}

		return true;
	}
}
