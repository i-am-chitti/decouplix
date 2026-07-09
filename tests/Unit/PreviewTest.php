<?php
/**
 * Preview Test Case
 *
 * @package Decouplix
 */

namespace Decouplix\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Decouplix\Preview;
use WP_Post;

/**
 * Class PreviewTest
 *
 * Tests the Preview class.
 */
class PreviewTest extends TestCase {

	/**
	 * Setup mock variables before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		global $decouplix_registered_actions, $decouplix_mock_options;
		$decouplix_registered_actions = array();
		$decouplix_mock_options       = array();

		// Reset singleton instance for Preview.
		$ref  = new \ReflectionClass( Preview::class );
		$prop = $ref->getProperty( 'instance' );
		$prop->setValue( null, null );
	}

	/**
	 * Test hook registration on class instantiation.
	 *
	 * @return void
	 */
	public function test_instance_registration() {
		$instance = Preview::get_instance();
		$this->assertInstanceOf( Preview::class, $instance );

		global $decouplix_registered_actions;
		$preview_filter_registered = false;

		foreach ( $decouplix_registered_actions as $action ) {
			if ( 'preview_post_link' === $action['hook'] ) {
				$preview_filter_registered = true;
				$this->assertEquals( array( $instance, 'filter_preview_link' ), $action['callback'] );
			}
		}

		$this->assertTrue( $preview_filter_registered, 'Failed to assert preview_post_link filter registered.' );
	}

	/**
	 * Test modifying the preview link when frontend URL is configured.
	 *
	 * @return void
	 */
	public function test_filter_preview_link_success() {
		$instance = Preview::get_instance();

		global $decouplix_mock_options;
		$decouplix_mock_options['decouplix_settings'] = array(
			'frontend_url'   => 'https://frontend-preview.com',
			'webhook_secret' => 'previewsecret123',
		);

		$post            = new WP_Post();
		$post->ID        = 555;
		$post->post_type = 'page';

		$original_link = 'https://example.com/?p=555&preview=true';
		$filtered_link = $instance->filter_preview_link( $original_link, $post );

		// Expected output url: https://frontend-preview.com/api/preview?id=555&secret=previewsecret123&type=page
		$this->assertStringStartsWith( 'https://frontend-preview.com/api/preview', $filtered_link );
		$this->assertStringContainsString( 'id=555', $filtered_link );
		$this->assertStringContainsString( 'secret=previewsecret123', $filtered_link );
		$this->assertStringContainsString( 'type=page', $filtered_link );
	}

	/**
	 * Test fallback behavior when frontend URL is not configured.
	 *
	 * @return void
	 */
	public function test_filter_preview_link_empty_url() {
		$instance = Preview::get_instance();

		global $decouplix_mock_options;
		$decouplix_mock_options['decouplix_settings'] = array(
			'frontend_url'   => '',
			'webhook_secret' => 'previewsecret123',
		);

		$post            = new WP_Post();
		$post->ID        = 555;
		$post->post_type = 'page';

		$original_link = 'https://example.com/?p=555&preview=true';
		$filtered_link = $instance->filter_preview_link( $original_link, $post );

		$this->assertEquals( $original_link, $filtered_link );
	}
}
