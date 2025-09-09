<?php
/**
 * Hookable_Feature class file.
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Types;

use Alley\WP\Types\Feature;
use Mantle\Support\Traits\Hookable;

/**
 * Hookable Feature
 *
 * An abstract feature that will automatically register hooks with WordPress on boot.
 */
abstract class Hookable_Feature implements Feature {
	use Hookable;

	/**
	 * Constructor (override only).
	 */
	public function __construct() {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		$this->register_hooks();
	}
}
