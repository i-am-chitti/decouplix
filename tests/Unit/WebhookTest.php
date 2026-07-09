<?php
/**
 * Webhook Test Case
 *
 * @package Decouplix
 */

namespace Decouplix\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Decouplix\Webhook;
use WP_Post;

/**
 * Class WebhookTest
 *
 * Tests the Webhook class.
 */
class WebhookTest extends TestCase {

	/**
	 * Setup mock variables before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		global $decouplix_registered_actions, $decouplix_fired_actions, $decouplix_mock_post_type_public;
		$decouplix_registered_actions    = array();
		$decouplix_fired_actions         = array();
		$decouplix_mock_post_type_public = true;

		// Reset singleton instance for Webhook.
		$ref  = new \ReflectionClass( Webhook::class );
		$prop = $ref->getProperty( 'instance' );
		$prop->setValue( null, null );
	}

	/**
	 * Test hook registration on class instantiation.
	 *
	 * @return void
	 */
	public function test_instance_registration() {
		$instance = Webhook::get_instance();
		$this->assertInstanceOf( Webhook::class, $instance );

		global $decouplix_registered_actions;
		$transition_hook_registered = false;

		foreach ( $decouplix_registered_actions as $action ) {
			if ( 'transition_post_status' === $action['hook'] ) {
				$transition_hook_registered = true;
				$this->assertEquals( array( $instance, 'handle_post_transition' ), $action['callback'] );
			}
		}

		$this->assertTrue( $transition_hook_registered, 'Failed to assert transition_post_status was registered.' );
	}

	/**
	 * Test transition: draft to publish.
	 *
	 * @return void
	 */
	public function test_transition_draft_to_publish() {
		$instance = Webhook::get_instance();

		$post                    = new WP_Post();
		$post->ID                = 123;
		$post->post_type         = 'post';
		$post->post_title        = 'Test Post';
		$post->post_name         = 'test-post';
		$post->post_modified_gmt = '2026-06-21 00:00:00';

		$instance->handle_post_transition( 'publish', 'draft', $post );

		global $decouplix_fired_actions;
		$this->assertCount( 1, $decouplix_fired_actions );
		$this->assertEquals( 'decouplix_webhook_triggered', $decouplix_fired_actions[0]['tag'] );

		$payload = $decouplix_fired_actions[0]['args'][0];
		$this->assertEquals( 'post_publish', $payload['event'] );
		$this->assertEquals( 123, $payload['post_id'] );
		$this->assertEquals( 'Test Post', $payload['title'] );
		$this->assertEquals( 'publish', $payload['status'] );
		$this->assertEquals( 'draft', $payload['old_status'] );
	}

	/**
	 * Test transition: publish to publish (update).
	 *
	 * @return void
	 */
	public function test_transition_publish_to_publish() {
		$instance = Webhook::get_instance();

		$post                    = new WP_Post();
		$post->ID                = 123;
		$post->post_type         = 'post';
		$post->post_title        = 'Updated Post';
		$post->post_name         = 'updated-post';
		$post->post_modified_gmt = '2026-06-21 01:00:00';

		$instance->handle_post_transition( 'publish', 'publish', $post );

		global $decouplix_fired_actions;
		$this->assertCount( 1, $decouplix_fired_actions );
		$payload = $decouplix_fired_actions[0]['args'][0];
		$this->assertEquals( 'post_update', $payload['event'] );
		$this->assertEquals( 'Updated Post', $payload['title'] );
	}

	/**
	 * Test transition: publish to draft (unpublish).
	 *
	 * @return void
	 */
	public function test_transition_publish_to_draft() {
		$instance = Webhook::get_instance();

		$post            = new WP_Post();
		$post->ID        = 123;
		$post->post_type = 'post';

		$instance->handle_post_transition( 'draft', 'publish', $post );

		global $decouplix_fired_actions;
		$this->assertCount( 1, $decouplix_fired_actions );
		$payload = $decouplix_fired_actions[0]['args'][0];
		$this->assertEquals( 'post_unpublish', $payload['event'] );
		$this->assertEquals( 'draft', $payload['status'] );
		$this->assertEquals( 'publish', $payload['old_status'] );
	}

	/**
	 * Test transitions that should be ignored (e.g. draft to pending).
	 *
	 * @return void
	 */
	public function test_transition_ignored() {
		$instance = Webhook::get_instance();

		$post            = new WP_Post();
		$post->ID        = 123;
		$post->post_type = 'post';

		$instance->handle_post_transition( 'pending', 'draft', $post );

		global $decouplix_fired_actions;
		$this->assertCount( 0, $decouplix_fired_actions );
	}

	/**
	 * Test transition for non-public post types (should be ignored).
	 *
	 * @return void
	 */
	public function test_transition_non_public_post_type() {
		$instance = Webhook::get_instance();

		global $decouplix_mock_post_type_public;
		$decouplix_mock_post_type_public = false;

		$post            = new WP_Post();
		$post->ID        = 123;
		$post->post_type = 'private_log';

		$instance->handle_post_transition( 'publish', 'draft', $post );

		global $decouplix_fired_actions;
		$this->assertCount( 0, $decouplix_fired_actions );
	}

	/**
	 * Test transition for revisions (should be ignored).
	 *
	 * @return void
	 */
	public function test_transition_revision() {
		$instance = Webhook::get_instance();

		$post            = new WP_Post();
		$post->ID        = 123;
		$post->post_type = 'revision';

		$instance->handle_post_transition( 'publish', 'draft', $post );

		global $decouplix_fired_actions;
		$this->assertCount( 0, $decouplix_fired_actions );
	}
}
