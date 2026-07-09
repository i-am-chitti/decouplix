<?php
/**
 * Cache Test Case
 *
 * @package Decouplix
 */

namespace Decouplix\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Decouplix\Cache;
use WP_Post;

/**
 * Class CacheTest
 *
 * Tests the Cache class.
 */
class CacheTest extends TestCase {

	/**
	 * Setup mock variables before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		global $decouplix_registered_actions, $decouplix_action_scheduler_events, $decouplix_scheduled_events, $decouplix_mock_options, $decouplix_mock_terms, $decouplix_mock_taxonomies, $decouplix_mock_post_type_public;
		$decouplix_registered_actions      = array();
		$decouplix_action_scheduler_events = array();
		$decouplix_scheduled_events        = array();
		$decouplix_mock_options            = array();
		$decouplix_mock_terms              = array();
		$decouplix_mock_taxonomies         = array( 'category', 'post_tag' );
		$decouplix_mock_post_type_public   = true;

		// Reset singleton instance for Cache.
		$ref  = new \ReflectionClass( Cache::class );
		$prop = $ref->getProperty( 'instance' );
		$prop->setValue( null, null );
	}

	/**
	 * Test hook registration on class instantiation.
	 *
	 * @return void
	 */
	public function test_instance_registration() {
		$instance = Cache::get_instance();
		$this->assertInstanceOf( Cache::class, $instance );

		global $decouplix_registered_actions;

		$transition_registered  = false;
		$purge_async_registered = false;
		$purge_cron_registered  = false;

		foreach ( $decouplix_registered_actions as $action ) {
			if ( 'transition_post_status' === $action['hook'] ) {
				$transition_registered = true;
				$this->assertEquals( array( $instance, 'handle_post_purge' ), $action['callback'] );
			}
			if ( 'decouplix_purge_paths_async' === $action['hook'] ) {
				$purge_async_registered = true;
				$this->assertEquals( array( $instance, 'purge_paths' ), $action['callback'] );
			}
			if ( 'decouplix_purge_paths_cron' === $action['hook'] ) {
				$purge_cron_registered = true;
				$this->assertEquals( array( $instance, 'purge_paths' ), $action['callback'] );
			}
		}

		$this->assertTrue( $transition_registered, 'Failed to assert transition_post_status registered.' );
		$this->assertTrue( $purge_async_registered, 'Failed to assert decouplix_purge_paths_async registered.' );
		$this->assertTrue( $purge_cron_registered, 'Failed to assert decouplix_purge_paths_cron registered.' );
	}

	/**
	 * Test gathering paths for a post including its taxonomies.
	 *
	 * @return void
	 */
	public function test_gather_paths() {
		$instance = Cache::get_instance();

		$post            = new WP_Post();
		$post->ID        = 789;
		$post->post_type = 'post';

		// Set mock terms for taxonomy category and tag
		global $decouplix_mock_terms;
		$term1           = new \stdClass();
		$term1->slug     = 'news';
		$term1->taxonomy = 'category';

		$term2           = new \stdClass();
		$term2->slug     = 'featured';
		$term2->taxonomy = 'post_tag';

		$decouplix_mock_terms = array( $term1, $term2 );

		$paths = $instance->gather_paths( $post );

		// Expected paths:
		// 1. Post permalink relative: '/?p=789' (based on bootstrap get_permalink mock)
		// 2. Home page: '/'
		// 3. Category term relative: '/tag/news' (based on bootstrap get_term_link mock)
		// 4. Tag term relative: '/tag/featured' (based on bootstrap get_term_link mock)
		$this->assertContains( '/?p=789', $paths );
		$this->assertContains( '/', $paths );
		$this->assertContains( '/tag/news', $paths );
		$this->assertContains( '/tag/featured', $paths );
	}

	/**
	 * Test transition logic queues a purge.
	 *
	 * @return void
	 */
	public function test_handle_post_purge_transition() {
		$instance = Cache::get_instance();

		$post            = new WP_Post();
		$post->ID        = 789;
		$post->post_type = 'post';

		$instance->handle_post_purge( 'publish', 'draft', $post );

		global $decouplix_action_scheduler_events;
		$this->assertCount( 1, $decouplix_action_scheduler_events );
		$this->assertEquals( 'decouplix_purge_paths_async', $decouplix_action_scheduler_events[0]['hook'] );

		$paths = $decouplix_action_scheduler_events[0]['args'][0];
		$this->assertContains( '/?p=789', $paths );
		$this->assertContains( '/', $paths );
	}

	/**
	 * Test sending purge requests to multiple endpoints.
	 *
	 * @return void
	 */
	public function test_purge_paths_dispatch() {
		$instance = Cache::get_instance();

		global $decouplix_mock_options;
		$decouplix_mock_options['decouplix_settings'] = array(
			'cache_endpoints' => "https://frontend.com/api/revalidate-1\nhttps://frontend.com/api/revalidate-2",
			'webhook_secret'  => 'secret',
		);

		global $decouplix_fired_actions;
		$decouplix_fired_actions = array();

		$paths = array( '/', '/about' );
		$instance->purge_paths( $paths );

		// Verify actions fired (one for each endpoint)
		$delivered_events = array();
		foreach ( $decouplix_fired_actions as $action ) {
			if ( 'decouplix_cache_purged' === $action['tag'] ) {
				$delivered_events[] = $action['args'];
			}
		}

		$this->assertCount( 2, $delivered_events );
		$this->assertEquals( 'https://frontend.com/api/revalidate-1', $delivered_events[0][0] );
		$this->assertEquals( $paths, $delivered_events[0][1] );
		$this->assertEquals( 'https://frontend.com/api/revalidate-2', $delivered_events[1][0] );
		$this->assertEquals( $paths, $delivered_events[1][1] );
	}
}
