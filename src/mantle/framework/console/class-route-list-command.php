<?php
/**
 * Route_List_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Contracts\Http\Routing\Router;
use Mantle\Http\Routing\Route;
use Mantle\Support\Collection;

use function Mantle\Support\Helpers\collect;

/**
 * Route_List_Command Controller
 */
class Route_List_Command extends Command {
	/**
	 * The console signature.
	 *
	 * @var string
	 */
	protected $signature = 'route:list {--format=table}';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'List the registered routes in the application.';

	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Callback for the command.
	 *
	 * @param Router $router Router instance.
	 */
	public function handle( Router $router ) {
		$this->router = $router;

		return $this->format_data(
			$this->option( 'format', 'table' ),
			[
				'method',
				'url',
				'name',
				'action',
				'middleware',
			],
			$this->collect_routes()->to_array(),
		);
	}

	/**
	 * Collect the routes in the application.
	 */
	protected function collect_routes(): Collection {
		/**
		 * Retrieve all routes.
		 *
		 * @var \Mantle\Http\Routing\Route[]
		 */
		$routes = $this->router->get_routes()->all();

		if ( empty( $routes ) ) {
			return collect();
		}

		return collect( $routes )
			->map(
				function ( Route $route, string $name ) {
					return [
						implode( '|', $route->getMethods() ),
						$route->getPath(),
						$name,
						$route->get_callback_name(),
						implode( '|', $route->middleware() ),
					];
				}
			);
	}
}
