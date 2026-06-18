<?php
/**
 * Environment class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Attributes;

use Attribute;

/**
 * Environment to set for the test.
 *
 * @see wp_get_environment_type()
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )]
class Environment {
	public const PRODUCTION = 'production';

	public const STAGING = 'staging';

	public const DEVELOPMENT = 'development';

	public const LOCAL = 'local';

	/**
	 * Get the environment type.
	 *
	 * @return string[] The environment types.
	 */
	public static function types(): array {
		return [
			self::PRODUCTION,
			self::STAGING,
			self::DEVELOPMENT,
			self::LOCAL,
		];
	}

	/**
	 * Constructor.
	 *
	 * @throws \InvalidArgumentException If the environment type is invalid.
	 *
	 * @param string|null $environment The environment type to set.
	 */
	public function __construct( public readonly ?string $environment ) {
		if ( $this->environment && ! in_array( $environment, self::types(), true ) ) {
			throw new \InvalidArgumentException( 'Invalid environment type: ' . $this->environment );
		}
	}
}
