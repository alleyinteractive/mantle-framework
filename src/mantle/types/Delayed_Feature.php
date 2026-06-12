<?php
/**
 * Delayed_Feature class file.
 *
 * @package Mantle
 */

namespace Mantle\Types;

use Alley\WP\Features\Quick_Feature;
use Alley\WP\Types\Feature;
use Closure;

/**
 * Feature that will boot on a delayed hook.
 */
class Delayed_Feature extends Validator_Group {
	/**
	 * Constructor.
	 *
	 * @param string                         $hook Hook to delay the feature on.
	 * @param array<Feature>|Feature|Closure $features Features to boot.
	 * @param int                            $priority Priority of the hook.
	 */
	public function __construct( private readonly string $hook, array|Feature|Closure $features, private readonly int $priority = 10 ) {
		$this->features = match ( true ) {
			is_array( $features ) => $features,
			$features instanceof Feature => [ $features ],
			$features instanceof Closure => [ new Quick_Feature( $features ) ],
		};
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( $this->hook, parent::boot( ... ), $this->priority );
	}
}
