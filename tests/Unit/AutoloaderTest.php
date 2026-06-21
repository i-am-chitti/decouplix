<?php
/**
 * Autoloader Test Case
 *
 * @package HeadlessCompanion
 */

namespace HeadlessCompanion\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class AutoloaderTest
 *
 * Tests the autoloader functionality.
 */
class AutoloaderTest extends TestCase {

	/**
	 * Test that the autoloader successfully loads existing classes.
	 *
	 * @return void
	 */
	public function test_autoload_loads_existing_class() {
		$this->assertTrue( class_exists( 'HeadlessCompanion\Plugin' ) );
	}
}
