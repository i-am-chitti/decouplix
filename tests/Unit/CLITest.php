<?php
/**
 * CLI Test Case
 *
 * @package Decouplix
 */

namespace Decouplix\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Decouplix\CLI;
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
		global $decouplix_registered_actions, $decouplix_mock_options, $decouplix_mock_posts, $decouplix_action_scheduler_events;
		$decouplix_registered_actions      = array();
		$decouplix_mock_options            = array();
		$decouplix_mock_posts              = array();
		$decouplix_action_scheduler_events = array();

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

		$this->assertArrayHasKey( 'decouplix status', WP_CLI::$commands );
		$this->assertArrayHasKey( 'decouplix webhook-trigger', WP_CLI::$commands );
		$this->assertArrayHasKey( 'decouplix purge', WP_CLI::$commands );
	}

	/**
	 * Test status command output.
	 *
	 * @return void
	 */
	public function test_status_command() {
		$instance = CLI::get_instance();

		global $decouplix_mock_options;
		$decouplix_mock_options['decouplix_settings'] = array(
			'frontend_url'    => 'https://my-headless-frontend.com',
			'webhook_secret'  => 'supersecrettoken',
			'cache_endpoints' => "https://my-headless-frontend.com/api/revalidate-1\nhttps://my-headless-frontend.com/api/revalidate-2",
		);

		$instance->status( array(), array() );

		$this->assertContains( 'Decouplix Configuration Status:', WP_CLI::$lines );
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

		global $decouplix_mock_posts;
		$decouplix_mock_posts[999] = $post;

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

		global $decouplix_action_scheduler_events;
		$this->assertCount( 1, $decouplix_action_scheduler_events );
		$this->assertEquals( 'decouplix_purge_paths_async', $decouplix_action_scheduler_events[0]['hook'] );
		$this->assertEquals( array( '/some-relative-page' ), $decouplix_action_scheduler_events[0]['args'][0] );

		$this->assertContains( 'Cache purge queued successfully.', WP_CLI::$successes );
	}
}
