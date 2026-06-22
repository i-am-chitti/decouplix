<?php
/**
 * Admin Test Case
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion\Tests\Unit;

use PHPUnit\Framework\TestCase;
use HeadlessCompanion\Admin;

/**
 * Class AdminTest
 *
 * Tests the Admin class registration and basic functions.
 */
class AdminTest extends TestCase {

	/**
	 * Setup mock filters and functions before test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		global $hc_registered_actions;
		$hc_registered_actions = array();

		// Reset singleton instance for Admin.
		$ref  = new \ReflectionClass( Admin::class );
		$prop = $ref->getProperty( 'instance' );
		$prop->setValue( null, null );
	}

	/**
	 * Test that the Admin singleton is instantiated correctly and registers hooks.
	 *
	 * @return void
	 */
	public function test_instance_registration() {
		$instance = Admin::get_instance();
		$this->assertInstanceOf( Admin::class, $instance );

		global $hc_registered_actions;

		// Verify action hooks were registered.
		$admin_menu_registered            = false;
		$admin_enqueue_scripts_registered = false;

		foreach ( $hc_registered_actions as $action ) {
			if ( 'admin_menu' === $action['hook'] ) {
				$admin_menu_registered = true;
				$this->assertEquals( array( $instance, 'register_settings_page' ), $action['callback'] );
			}
			if ( 'admin_enqueue_scripts' === $action['hook'] ) {
				$admin_enqueue_scripts_registered = true;
				$this->assertEquals( array( $instance, 'enqueue_admin_assets' ), $action['callback'] );
			}
		}

		$this->assertTrue( $admin_menu_registered, 'Failed to assert that admin_menu action was registered.' );
		$this->assertTrue( $admin_enqueue_scripts_registered, 'Failed to assert that admin_enqueue_scripts action was registered.' );
	}
}
