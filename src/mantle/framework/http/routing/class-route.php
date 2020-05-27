<?php
/**
 * Route class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing;

use Mantle\Framework\Contracts\Container;
use Mantle\Framework\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route as Symfony_Route;

/**
 * Route Class
 */
class Route extends Symfony_Route {
	/**
	 * Route action.
	 *
	 * @var array
	 */
	protected $action;

	/**
	 * Constructor.
	 *
	 * @param array $methods
	 * @param string $path
	 * @param [type] $action
	 */
	public function __construct( array $methods, string $path, $action ) {
		parent::__construct( $path );

		$this->setMethods( $methods );
		$this->setDefault( 'route', $this );

		if ( is_callable( $action ) || is_string( $action ) ) {
			$action = [
				'callback' => $action,
			];
		}

		$this->action = $action;
	}

	/**
	 * Get the route's name.
	 *
	 * @return string
	 */
	public function get_route_name(): string {
		if ( is_array( $this->action ) && ! empty( $this->action['name'] ) ) {
			return $this->action['name'];
		}

		$uri = $this->getPath();
		return implode( ':', $this->getMethods() ) . ":{$uri}";
	}

	/**
	 * Render a route.
	 *
	 * @param Container $container Service Container.
	 * @return Response|null
	 */
	public function render( Container $container ): ?Response {
		if ( $this->has_callback() ) {
			$response = $container->make( $this->get_callback() );
		}

		if ( $this->has_controller_callback() ) {
			var_dump($this);exit;
		}

		return $response ? $this->ensure_response( $response ) : null;
	}

	/**
	 * Determine if the route has a closure callback.
	 *
	 * @return bool
	 */
	protected function has_callback(): bool {
		return ! empty( $this->action['callback'] ) && is_callable( $this->action['callback'] );
	}

	/**
	 * Determine if the route has a controller callback.
	 *
	 * @return bool
	 */
	protected function has_controller_callback(): bool {
		return ! empty( $this->action['callback'] ) && Str::contains( $this->action['callback'], '@' );
	}

	/**
	 * Get the controller's closure callback.
	 *
	 * @return callable:null;
	 */
	protected function get_callback(): ?callable {
		return $this->has_callback() ? $this->action : null;
	}

	/**
	 * Ensure a proper response object.
	 *
	 * @param mixed $response Response to send.
	 * @return Response
	 */
	protected function ensure_response( $response ): Response {
		if ( $response instanceof Response ) {
			return $response;
		}

		return new Response( $response );
	}
}
