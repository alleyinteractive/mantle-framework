<?php
/**
 * Entity_Router class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use InvalidArgumentException;
use Mantle\Contracts\Http\Routing\Url_Routable;
use Mantle\Database\Model\Model;
use Mantle\Http\Routing\Events\Entity_Route_Added;

use function Mantle\Framework\Helpers\event;

/**
 * Provide routing to a WordPress data entity: post or term.
 */
class Entity_Router {
	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	protected static $router;

	/**
	 * Set the router for the entity router.
	 *
	 * @param Router $router Router instance.
	 */
	public static function set_router( Router $router ) {
		static::$router = $router;
	}

	/**
	 * Add an entity to the router.
	 *
	 * @param Router $router Router instance.
	 * @param string $entity Entity class name.
	 * @param string $controller Controller class name.
	 * @return void
	 *
	 * @throws InvalidArgumentException Thrown on invalid entity.
	 */
	public static function add( Router $router, string $entity, string $controller ): void {
		if ( ! is_subclass_of( $entity, Model::class ) ) {
			throw new InvalidArgumentException( "Unknown entity type: [{$entity}]" );
		}

		if ( ! in_array( Url_Routable::class, class_implements( $entity ) ) ) {
			throw new InvalidArgumentException( "Unroutable entity: [{$entity}]" );
		}

		static::resolve_entity_endpoints( $router, $entity, $controller );

		event( new Entity_Route_Added( $entity, $controller ) );
	}

	/**
	 * Resolve the endpoints to add for an entity.
	 *
	 * @param Router $router Router instance.
	 * @param string $entity Entity class name.
	 * @param string $controller Controller class name.
	 * @return void
	 */
	protected static function resolve_entity_endpoints( Router $router, string $entity, string $controller ): void {
		// Singular endpoint.
		$single_route = $entity::get_route();

		if ( $single_route ) {
			$router->get( trailingslashit( $single_route ), [ $controller, 'show' ] );
		}

		$archive_route = $entity::get_archive_route();

		if ( $archive_route ) {
			$router->get( trailingslashit( $archive_route ), [ $controller, 'index' ] );
		}
	}
}
