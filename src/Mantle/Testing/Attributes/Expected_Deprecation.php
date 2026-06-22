<?php
/**
 * Expected_Deprecation class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Attributes;

use Attribute;

/**
 * Expected Deprecation
 *
 * Used to mark a test as expecting a deprecation notice.
 *
 * Using this attribute will make an assertion count increase by one for each
 * expected deprecation that is caught. If an expected deprecation is not
 * caught, the test will fail.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE )]
class Expected_Deprecation {
	/**
	 * Constructor.
	 *
	 * @param string $deprecation The expected deprecation method.
	 */
	public function __construct( public string $deprecation ) {}
}
