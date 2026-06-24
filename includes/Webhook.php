<?php
/**
 * Webhook Class
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion;

defined( 'ABSPATH' ) || exit;

use WP_Post;

/**
 * Class Webhook
 *
 * Catches post content changes and orchestrates webhook dispatching.
 */
class Webhook {

	/**
	 * Singleton instance of Webhook.
	 *
	 * @var Webhook|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Webhook
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
		add_action( 'transition_post_status', array( $this, 'handle_post_transition' ), 10, 3 );
	}

	/**
	 * Handle post status transitions to determine if a webhook should trigger.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 * @return void
	 */
	public function handle_post_transition( $new_status, $old_status, $post ) {
		// Prevent infinite loops or running on autosaves/revisions.
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

		// Determine action type.
		$action = '';
		if ( 'publish' === $new_status && 'publish' !== $old_status ) {
			$action = 'publish';
		} elseif ( 'publish' === $new_status && 'publish' === $old_status ) {
			$action = 'update';
		} elseif ( 'publish' === $old_status && 'publish' !== $new_status ) {
			$action = 'unpublish';
		}

		// If no relevant action occurred, do not trigger.
		if ( empty( $action ) ) {
			return;
		}

		// Build the webhook payload.
		$payload = $this->build_payload( $post, $action, $new_status, $old_status );

		// Dispatch webhook.
		$this->dispatch_webhook( $payload );
	}

	/**
	 * Build webhook payload data from the post object.
	 *
	 * @param WP_Post $post       Post object.
	 * @param string  $action     Action type (publish, update, unpublish).
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @return array
	 */
	public function build_payload( $post, $action, $new_status, $old_status ) {
		return array(
			'event'      => 'post_' . $action,
			'post_id'    => $post->ID,
			'post_type'  => $post->post_type,
			'title'      => $post->post_title,
			'slug'       => $post->post_name,
			'status'     => $new_status,
			'permalink'  => get_permalink( $post->ID ),
			'modified'   => $post->post_modified_gmt,
			'old_status' => $old_status,
		);
	}

	/**
	 * Dispatch the webhook payload.
	 *
	 * Note: Asynchronous queueing is hooked into 'headless_companion_webhook_triggered'.
	 *
	 * @param array $payload The webhook payload array.
	 * @return bool
	 */
	public function dispatch_webhook( $payload ) {
		do_action( 'headless_companion_webhook_triggered', $payload );
		return true;
	}
}
