<?php
/**
 * Validator_Group class file.
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Types;

use Alley\WP\Features\Quick_Feature;
use Alley\WP\Types\Feature;
use Alley\WP\Types\Features;
use Closure;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Feature Group with Validation
 *
 * Manage multiple features that only boot features that have been validated
 * with attributes.
 *
 * Validators are defined as attributes on the feature classes that extend
 * {@see \Mantle\Types\Validator}.
 */
class Validator_Group implements Features {
	/**
	 * Features to include.
	 *
	 * @var array<Feature>
	 */
	protected array $features = [];

	/**
	 * Constructor.
	 *
	 * @param array<Feature|Closure>|Feature|Closure ...$features Features.
	 */
	public function __construct( ...$features ) {
		foreach ( $features as $feature ) {
			if ( is_array( $feature ) ) {
				array_push( $this->features, ...$feature );
			} elseif ( $feature instanceof Closure ) {
				$this->features[] = new Quick_Feature( $feature );
			} else {
				$this->features[] = $feature;
			}
		}
	}

	/**
	 * Boot the feature.
	 *
	 * @throws InvalidArgumentException If a feature is not an instance of Feature.
	 */
	public function boot(): void {
		foreach ( $this->features as $feature ) {
			if ( ! $feature instanceof Feature ) { // @phpstan-ignore-line instanceof.alwaysTrue
				throw new InvalidArgumentException( esc_html( sprintf( 'Invalid feature type: %s', get_debug_type( $feature ) ) ) );
			}

			$this->boot_feature( $feature );
		}
	}

	/**
	 * Boot a feature.
	 *
	 * @param Feature $feature Feature to boot.
	 */
	protected function boot_feature( Feature $feature ): void {
		$attributes = ( new ReflectionClass( $feature ) )->getAttributes( Validator::class, \ReflectionAttribute::IS_INSTANCEOF );

		foreach ( $attributes as $attribute ) {
			if ( ! $attribute->newInstance()->validate() ) {
				return;
			}
		}

		$feature->boot();
	}

	/**
	 * Include features.
	 *
	 * @param Feature ...$features Features to include.
	 */
	public function include( Feature ...$features ): void {
		array_push( $this->features, ...$features );
	}
}
