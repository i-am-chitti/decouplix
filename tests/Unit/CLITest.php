<?php
/**
 * CLI Test Case
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion\Tests\Unit;

use PHPUnit\Framework\TestCase;
use HeadlessCompanion\CLI;
use WP_Post;
use WP_CLI;

/**
 * Class CLITest
 *
 * Tests the CLI class.
 */
class CLITest extends TestCase {

	/**
	 * Setup mock variables before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		global $hc_registered_actions, $hc_mock_options, $hc_mock_posts, $hc_action_scheduler_events;
		$hc_registered_actions      = array();
		$hc_mock_options            = array();
		$hc_mock_posts              = array();
		$hc_action_scheduler_events = array();

		// Clean up WP_CLI mocks
		WP_CLI::$commands  = array();
		WP_CLI::$lines     = array();
		WP_CLI::$errors    = array();
		WP_CLI::$successes = array();

		// Reset singleton instance for CLI.
		$ref  = new \ReflectionClass( CLI::class );
		$prop = $ref->getProperty( 'instance' );
		$prop->setValue( null, null );
	}

	/**
	 * Test command registration on class instantiation.
	 *
	 * @return void
	 */
	public function test_instance_registration() {
		$instance = CLI::get_instance();
		$this->assertInstanceOf( CLI::class, $instance );

		$this->assertArrayHasKey( 'headless-companion status', WP_CLI::$commands );
		$this->assertArrayHasKey( 'headless-companion webhook-trigger', WP_CLI::$commands );
		$this->assertArrayHasKey( 'headless-companion purge', WP_CLI::$commands );
	}

	/**
	 * Test status command output.
	 *
	 * @return void
	 */
	public function test_status_command() {
		$instance = CLI::get_instance();

		global $hc_mock_options;
		$hc_mock_options['hc_settings'] = array(
			'frontend_url'    => 'https://my-headless-frontend.com',
			'webhook_secret'  => 'supersecrettoken',
			'cache_endpoints' => "https://my-headless-frontend.com/api/revalidate-1\nhttps://my-headless-frontend.com/api/revalidate-2",
		);

		$instance->status( array(), array() );

		$this->assertContains( 'Headless Companion Configuration Status:', WP_CLI::$lines );
		$this->assertContains( 'Frontend URL:   https://my-headless-frontend.com', WP_CLI::$lines );
		$this->assertContains( 'Webhook Secret: Configured (Secret Hidden)', WP_CLI::$lines );
		$this->assertContains( 'Cache Purge Endpoints (2):', WP_CLI::$lines );
		$this->assertContains( '  - https://my-headless-frontend.com/api/revalidate-1', WP_CLI::$lines );
	}

	/**
	 * Test manual webhook trigger command success path.
	 *
	 * @return void
	 */
	public function test_webhook_trigger_success() {
		$instance = CLI::get_instance();

		$post                    = new WP_Post();
		$post->ID                = 999;
		$post->post_type         = 'post';
		$post->post_status       = 'publish';
		$post->post_title        = 'Manual CLI Post';
		$post->post_name         = 'manual-cli-post';
		$post->post_modified_gmt = '2026-06-21 02:00:00';

		global $hc_mock_posts;
		$hc_mock_posts[999] = $post;

		$instance->webhook_trigger( array( 999 ), array() );

		$this->assertContains( 'Webhook triggered successfully.', WP_CLI::$successes );
	}

	/**
	 * Test manual webhook trigger command with invalid post ID (should throw exception).
	 *
	 * @return void
	 */
	public function test_webhook_trigger_not_found() {
		$instance = CLI::get_instance();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Post with ID 888 not found.' );

		$instance->webhook_trigger( array( 888 ), array() );
	}

	/**
	 * Test manual cache purge command enqueuing path.
	 *
	 * @return void
	 */
	public function test_purge_command() {
		$instance = CLI::get_instance();

		$instance->purge( array( '/some-relative-page' ), array() );

		global $hc_action_scheduler_events;
		$this->assertCount( 1, $hc_action_scheduler_events );
		$this->assertEquals( 'hc_purge_paths_async', $hc_action_scheduler_events[0]['hook'] );
		$this->assertEquals( array( '/some-relative-page' ), $hc_action_scheduler_events[0]['args'][0] );

		$this->assertContains( 'Cache purge queued successfully.', WP_CLI::$successes );
	}
}
