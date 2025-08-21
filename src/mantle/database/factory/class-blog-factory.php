<?php
/**
 * Blog_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Mantle\Database\Model\Site;
use WP_Network;

use function Mantle\Support\Helpers\get_site_object;

/**
 * Blog Factory
 *
 * @template TModel of \Mantle\Database\Model\Site
 * @template TObject of \WP_Site
 * @template TReturnValue
 *
 * @extends Factory<TModel, TObject, TReturnValue>
 */
class Blog_Factory extends Factory {
	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string<TModel>
	 */
	protected string $model = Site::class;

	/**
	 * Definition of the factory.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		global $current_site, $base;

		assert( $current_site instanceof WP_Network, 'Expected $current_site to be an instance of WP_Network' );

		return [
			'domain'     => $current_site->domain,
			'path'       => $base . $this->faker->slug(),
			'title'      => $this->faker->text(),
			'network_id' => $current_site->id,
		];
	}

	/**
	 * Create a subdomain of the current site.
	 */
	public function subdomain(): static {
		global $current_site;

		assert( $current_site instanceof WP_Network, 'Expected $current_site to be an instance of WP_Network' );

		return $this->state( [
			'domain' => $this->faker->domainWord() . '.' . $current_site->domain,
			'path'   => '/',
		] );
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 */
	public function get_object_by_id( int $object_id ): ?\WP_Site {
		return get_site_object( $object_id );
	}
}
