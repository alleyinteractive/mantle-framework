<?php
/**
 * Interacts_With_Environment trait file
 *
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Attributes\Environment;

use function Mantle\Support\Helpers\collect;

/**
 * Interactions with WordPress Environment.
 *
 * @mixin \Mantle\Testing\TestCase
 */
trait Interacts_With_Environment {
	use Reads_Annotations;

	/**
	 * Flag if the environment has been set for the test.
	 */
	private bool $environment_set = false;

	/**
	 * Set the environment type for the test.
	 */
	protected function interacts_with_environment_set_up(): void {
		$environment = $this->get_environment_for_test_method();

		if ( $environment ) {
			$this->environment_set = true;

			putenv( "WP_ENVIRONMENT_TYPE={$environment}" );
		} else {
			$this->environment_set = false;
		}
	}

	/**
	 * Clean up the environment type after the test.
	 */
	protected function interacts_with_environment_tear_down(): void {
		if ( $this->environment_set ) {
			putenv( 'WP_ENVIRONMENT_TYPE=' );

			$this->environment_set = false;
		}
	}

	/**
	 * Retrieve the environment type for the test method.
	 */
	private function get_environment_for_test_method(): ?string {
		$attributes = collect( $this->get_attributes_for_method( Environment::class ) );

		if ( $attributes->is_empty() ) {
			return null;
		}

		$attribute = $attributes->first();

		return $attribute->newInstance()->environment;
	}

	/**
	 * Set the environment type for the test.
	 *
	 * @param string $environment The environment type to set.
	 *
	 * @throws \InvalidArgumentException If the environment type is invalid.
	 */
	public function set_environment_type( string $environment ): void {
		if ( ! in_array( $environment, Environment::types(), true ) ) {
			throw new \InvalidArgumentException( 'Invalid environment type: ' . $environment );
		}

		putenv( "WP_ENVIRONMENT_TYPE={$environment}" );

		$this->environment_set = true;
	}
}
