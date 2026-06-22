<?php
/**
 * Queue Test Case
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion\Tests\Unit;

use PHPUnit\Framework\TestCase;
use HeadlessCompanion\Queue;
use WP_Error;

/**
 * Class QueueTest
 *
 * Tests the Queue class.
 */
class QueueTest extends TestCase {

	/**
	 * Setup mock variables before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		global $hc_registered_actions, $hc_action_scheduler_events, $hc_scheduled_events, $hc_mock_options, $hc_mock_remote_post_result;
		$hc_registered_actions      = array();
		$hc_action_scheduler_events = array();
		$hc_scheduled_events        = array();
		$hc_mock_options            = array();
		$hc_mock_remote_post_result = null;

		// Reset singleton instance for Queue.
		$ref  = new \ReflectionClass( Queue::class );
		$prop = $ref->getProperty( 'instance' );
		$prop->setValue( null, null );
	}

	/**
	 * Test hook registration on class instantiation.
	 *
	 * @return void
	 */
	public function test_instance_registration() {
		$instance = Queue::get_instance();
		$this->assertInstanceOf( Queue::class, $instance );

		global $hc_registered_actions;

		$triggered_registered     = false;
		$deliver_async_registered = false;
		$deliver_cron_registered  = false;

		foreach ( $hc_registered_actions as $action ) {
			if ( 'hc_webhook_triggered' === $action['hook'] ) {
				$triggered_registered = true;
				$this->assertEquals( array( $instance, 'enqueue_webhook' ), $action['callback'] );
			}
			if ( 'hc_deliver_webhook_async' === $action['hook'] ) {
				$deliver_async_registered = true;
				$this->assertEquals( array( $instance, 'deliver_webhook' ), $action['callback'] );
			}
			if ( 'hc_deliver_webhook_cron' === $action['hook'] ) {
				$deliver_cron_registered = true;
				$this->assertEquals( array( $instance, 'deliver_webhook' ), $action['callback'] );
			}
		}

		$this->assertTrue( $triggered_registered, 'Failed to assert hc_webhook_triggered hook registered.' );
		$this->assertTrue( $deliver_async_registered, 'Failed to assert hc_deliver_webhook_async hook registered.' );
		$this->assertTrue( $deliver_cron_registered, 'Failed to assert hc_deliver_webhook_cron hook registered.' );
	}

	/**
	 * Test enqueuing webhook using Action Scheduler.
	 *
	 * @return void
	 */
	public function test_enqueue_webhook_action_scheduler() {
		$instance = Queue::get_instance();
		$payload  = array( 'event' => 'test_event' );

		$instance->enqueue_webhook( $payload );

		global $hc_action_scheduler_events, $hc_scheduled_events;

		// Since as_enqueue_async_action is defined in bootstrap.php, it should be used.
		$this->assertCount( 1, $hc_action_scheduler_events );
		$this->assertEquals( 'hc_deliver_webhook_async', $hc_action_scheduler_events[0]['hook'] );
		$this->assertEquals( array( $payload ), $hc_action_scheduler_events[0]['args'] );
		$this->assertEquals( 'headless-companion', $hc_action_scheduler_events[0]['group'] );

		// WP Cron should not be called.
		$this->assertCount( 0, $hc_scheduled_events );
	}

	/**
	 * Test successful webhook delivery and cryptographic signature.
	 *
	 * @return void
	 */
	public function test_deliver_webhook_success() {
		$instance = Queue::get_instance();

		global $hc_mock_options;
		$hc_mock_options['hc_settings'] = array(
			'frontend_url'   => 'https://frontend-api.com/webhook',
			'webhook_secret' => 'mysecretkey',
		);

		$payload = array(
			'event'   => 'post_publish',
			'post_id' => 456,
		);

		// Run delivery
		$result = $instance->deliver_webhook( $payload );

		$this->assertTrue( $result );
	}

	/**
	 * Test delivery with empty URL configuration (should fail early).
	 *
	 * @return void
	 */
	public function test_deliver_webhook_empty_url() {
		$instance = Queue::get_instance();

		global $hc_mock_options;
		$hc_mock_options['hc_settings'] = array(
			'frontend_url'   => '',
			'webhook_secret' => 'mysecretkey',
		);

		$payload = array( 'event' => 'post_publish' );
		$result  = $instance->deliver_webhook( $payload );

		$this->assertFalse( $result );
	}

	/**
	 * Test delivery HTTP error handling.
	 *
	 * @return void
	 */
	public function test_deliver_webhook_http_error() {
		$instance = Queue::get_instance();

		global $hc_mock_options, $hc_mock_remote_post_result;
		$hc_mock_options['hc_settings'] = array(
			'frontend_url'   => 'https://frontend-api.com/webhook',
			'webhook_secret' => 'mysecretkey',
		);

		// Mock a 500 server error response.
		$hc_mock_remote_post_result = array(
			'response' => array(
				'code' => 500,
			),
		);

		$payload = array( 'event' => 'post_publish' );
		$result  = $instance->deliver_webhook( $payload );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'hc_webhook_http_error', $result->get_error_code() );
	}
}
