<?php
/**
 * GraphQL Test Case
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion\Tests\Unit;

use PHPUnit\Framework\TestCase;
use HeadlessCompanion\GraphQL;

/**
 * Class GraphQLTest
 *
 * Tests the GraphQL class.
 */
class GraphQLTest extends TestCase {

	/**
	 * Setup mock variables before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		global $hc_registered_actions, $hc_mock_options, $hc_registered_graphql_fields;
		$hc_registered_actions        = array();
		$hc_mock_options              = array();
		$hc_registered_graphql_fields = array();

		// Clean up HTTP headers
		unset( $_SERVER['HTTP_X_HC_SECRET'] );

		// Reset singleton instance for GraphQL.
		$ref  = new \ReflectionClass( GraphQL::class );
		$prop = $ref->getProperty( 'instance' );
		$prop->setValue( null, null );
	}

	/**
	 * Test hook registration on class instantiation.
	 *
	 * @return void
	 */
	public function test_instance_registration() {
		$instance = GraphQL::get_instance();
		$this->assertInstanceOf( GraphQL::class, $instance );

		global $hc_registered_actions;
		$determine_user_registered = false;
		$register_types_registered = false;

		foreach ( $hc_registered_actions as $action ) {
			if ( 'determine_current_user' === $action['hook'] ) {
				$determine_user_registered = true;
				$this->assertEquals( array( $instance, 'authenticate_preview_request' ), $action['callback'] );
			}
			if ( 'graphql_register_types' === $action['hook'] ) {
				$register_types_registered = true;
				$this->assertEquals( array( $instance, 'register_graphql_fields' ), $action['callback'] );
			}
		}

		$this->assertTrue( $determine_user_registered, 'Failed to assert determine_current_user filter registered.' );
		$this->assertTrue( $register_types_registered, 'Failed to assert graphql_register_types action registered.' );
	}

	/**
	 * Test successful authentication override with valid header.
	 *
	 * @return void
	 */
	public function test_authenticate_preview_request_success() {
		$instance = GraphQL::get_instance();

		global $hc_mock_options;
		$hc_mock_options['hc_settings'] = array(
			'webhook_secret' => 'super-graphql-secret',
		);

		// Define mock constant and header to simulate GraphQL request
		if ( ! defined( 'GRAPHQL_HTTP_REQUEST' ) ) {
			define( 'GRAPHQL_HTTP_REQUEST', true );
		}
		$_SERVER['HTTP_X_HC_SECRET'] = 'super-graphql-secret';

		$original_user_id = false;
		$authenticated_id = $instance->authenticate_preview_request( $original_user_id );

		// Should authenticate as user ID 1 (mock admin user from bootstrap.php)
		$this->assertEquals( 1, $authenticated_id );
	}

	/**
	 * Test that authentication is not overridden when the secret header is invalid.
	 *
	 * @return void
	 */
	public function test_authenticate_preview_request_invalid_secret() {
		$instance = GraphQL::get_instance();

		global $hc_mock_options;
		$hc_mock_options['hc_settings'] = array(
			'webhook_secret' => 'super-graphql-secret',
		);

		$_SERVER['HTTP_X_HC_SECRET'] = 'wrong-secret';

		$original_user_id = 42;
		$authenticated_id = $instance->authenticate_preview_request( $original_user_id );

		// Should remain unchanged
		$this->assertEquals( 42, $authenticated_id );
	}

	/**
	 * Test that custom fields register correctly in schema definition.
	 *
	 * @return void
	 */
	public function test_register_graphql_fields() {
		$instance = GraphQL::get_instance();
		$instance->register_graphql_fields();

		global $hc_registered_graphql_fields;

		$this->assertCount( 1, $hc_registered_graphql_fields );
		$field = $hc_registered_graphql_fields[0];

		$this->assertEquals( 'ContentNode', $field['type_name'] );
		$this->assertEquals( 'headlessPreviewUrl', $field['field_name'] );
		$this->assertEquals( 'String', $field['config']['type'] );
	}
}
