<?php
/**
 * PermalinkStructure class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Attributes;

use Attribute;

/**
 * Permalink structure to set for the test.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )]
class PermalinkStructure {
	/**
	 * Constructor.
	 *
	 * @param string $structure The permalink structure to set.
	 */
	public function __construct( public readonly string $structure ) {}
}
