<?php
/**
 * Entity_Router class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing;

use InvalidArgumentException;
use Mantle\Framework\Database\Model\Model;

/**
 * Provide routing to a WordPress data object: post or term.
 */
class Entity_Router {

	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Constructor.
	 *
	 * @param Router $router Router instance.
	 */
	public function __construct( Router $router ) {
		$this->router = $router;
	}

	/**
	 * Add a entity to the router.
	 *
	 * @param string $entity
	 * @param string $controller
	 * @return void
	 */
	public function add( string $entity, string $controller ) {
		if ( ! is_subclass_of( $entity, Model::class ) ) {
			throw new InvalidArgumentException( "Unknown entity type: [{$entity}]" );
		}

		$index    = $this->get_index_endpoint( $entity );
		$singular = $this->get_singular_endpoint( $entity );

		dd($singular);
	}

	protected function get_index_endpoint( string $model ): ?string {

	}

	protected function get_singular_endpoint( string $model ): string {
		$post_type_object = \get_post_type_object( $model::get_object_name() );
		dd($post_type_object);
	}

}
