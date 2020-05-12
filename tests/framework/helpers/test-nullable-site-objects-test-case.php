<?php
/**
 * Class file for Nullable_Site_Objects_Test_Case
 *
 * @package SML
 */

namespace Mantle\Tests\Framework\Helpers;

use WP_UnitTestCase;
use function Mantle\Framework\Helpers\get_site_object;

if ( \is_multisite() ) {
	/**
	 * Unit tests for nullable site object functions.
	 */
	class Nullable_Site_Objects_Test_Case extends WP_UnitTestCase {
		/**
		 * Test that `get_site_object()` returns a site object.
		 */
		public function test_site_object_returns_site() {
			$known_id = self::factory()->blog->create();

			$found_object = get_site_object( $known_id );

			$this->assertInstanceOf( \WP_Site::class, $found_object );

			$this->assertSame( $known_id, (int) $found_object->blog_id );
		}

		/**
		 * Test that `get_site_object()` returns null when there is no site.
		 */
		public function test_site_object_returns_null() {
			$found_object = get_site_object( -99 );

			$this->assertNull( $found_object );
		}
	}
}
