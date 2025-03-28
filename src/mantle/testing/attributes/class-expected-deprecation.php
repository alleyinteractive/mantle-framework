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
