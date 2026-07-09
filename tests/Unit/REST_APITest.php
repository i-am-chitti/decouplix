<?php
/**
 * REST_API Test Case
 *
 * @package Decouplix
 */

namespace Decouplix\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Decouplix\REST_API;
use WP_REST_Request;
use WP_Error;

/**
 * Class REST_APITest
 *
 * Tests the REST_API class.
 */
class REST_APITest extends TestCase {

	/**
	 * Setup mock filters and functions before test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		global $decouplix_registered_actions, $hc_registered_routes, $decouplix_current_user_can_result, $decouplix_mock_options;
		$decouplix_registered_actions    = array();
		$hc_registered_routes     = array();
		$decouplix_current_user_can_result = true;
		$decouplix_mock_options          = array();

		// Reset singleton instance for REST_API.
		$ref  = new \ReflectionClass( REST_API::class );
		$prop = $ref->getProperty( 'instance' );
		$prop->setValue( null, null );
	}

	/**
	 * Test that the REST_API singleton registers the rest_api_init hook.
	 *
	 * @return void
	 */
	public function test_instance_registration() {
		$instance = REST_API::get_instance();
		$this->assertInstanceOf( REST_API::class, $instance );

		global $decouplix_registered_actions;
		$rest_init_registered = false;

		foreach ( $decouplix_registered_actions as $action ) {
			if ( 'rest_api_init' === $action['hook'] ) {
				$rest_init_registered = true;
				$this->assertEquals( array( $instance, 'register_routes' ), $action['callback'] );
			}
		}

		$this->assertTrue( $rest_init_registered, 'Failed to assert that rest_api_init hook was registered.' );
	}

	/**
	 * Test route registration.
	 *
	 * @return void
	 */
	public function test_register_routes() {
		$instance = REST_API::get_instance();
		$instance->register_routes();

		global $hc_registered_routes;

		$this->assertCount( 1, $hc_registered_routes );
		$route = $hc_registered_routes[0];

		$this->assertEquals( 'decouplix/v1', $route['namespace'] );
		$this->assertEquals( '/settings', $route['route'] );
		$this->assertCount( 2, $route['args'] ); // GET and POST

		$this->assertEquals( 'GET', $route['args'][0]['methods'] );
		$this->assertEquals( 'POST', $route['args'][1]['methods'] );
	}

	/**
	 * Test check_permission function.
	 *
	 * @return void
	 */
	public function test_check_permission() {
		$instance = REST_API::get_instance();

		global $decouplix_current_user_can_result;

		$decouplix_current_user_can_result = true;
		$this->assertTrue( $instance->check_permission() );

		$decouplix_current_user_can_result = false;
		$this->assertFalse( $instance->check_permission() );
	}

	/**
	 * Test URL validation.
	 *
	 * @return void
	 */
	public function test_validate_url() {
		$instance = REST_API::get_instance();
		$request  = new WP_REST_Request( 'POST', '/settings' );

		// Valid URL
		$this->assertTrue( $instance->validate_url( 'https://example.com', $request, 'frontend_url' ) );
		// Empty URL is valid (optional)
		$this->assertTrue( $instance->validate_url( '', $request, 'frontend_url' ) );

		// Invalid URL
		$result = $instance->validate_url( 'not-a-url', $request, 'frontend_url' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_invalid_url', $result->get_error_code() );
	}

	/**
	 * Test fetching settings (get_settings).
	 *
	 * @return void
	 */
	public function test_get_settings() {
		$instance = REST_API::get_instance();
		$request  = new WP_REST_Request( 'GET', '/settings' );

		// Prepare mock database option
		global $decouplix_mock_options;
		$decouplix_mock_options['decouplix_settings'] = array(
			'frontend_url'    => 'https://frontend.com',
			'webhook_secret'  => 'supersecret',
			'cache_endpoints' => "https://frontend.com/api/revalidate\nhttps://cdn.com/purge",
		);

		$response = $instance->get_settings( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'https://frontend.com', $data['frontend_url'] );
		$this->assertEquals( 'supersecret', $data['webhook_secret'] );
		$this->assertEquals( "https://frontend.com/api/revalidate\nhttps://cdn.com/purge", $data['cache_endpoints'] );
	}

	/**
	 * Test updating settings (update_settings).
	 *
	 * @return void
	 */
	public function test_update_settings() {
		$instance = REST_API::get_instance();
		$request  = new WP_REST_Request( 'POST', '/settings' );

		$request->set_param( 'frontend_url', 'https://new-frontend.com' );
		$request->set_param( 'webhook_secret', 'newsecret' );
		$request->set_param( 'cache_endpoints', 'https://new-frontend.com/purge' );

		$response = $instance->update_settings( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'https://new-frontend.com', $data['data']['frontend_url'] );

		// Verify database was updated
		global $decouplix_mock_options;
		$this->assertEquals( 'https://new-frontend.com', $decouplix_mock_options['decouplix_settings']['frontend_url'] );
		$this->assertEquals( 'newsecret', $decouplix_mock_options['decouplix_settings']['webhook_secret'] );
		$this->assertEquals( 'https://new-frontend.com/purge', $decouplix_mock_options['decouplix_settings']['cache_endpoints'] );
	}
}
