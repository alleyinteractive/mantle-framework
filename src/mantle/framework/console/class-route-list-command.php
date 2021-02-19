<?php
/**
 * Route_List_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Framework\Contracts\Http\Routing\Router;
use Mantle\Framework\Http\Routing\Route;
use Mantle\Support\Collection;

use function Mantle\Framework\Helpers\collect;

/**
 * Route_List_Command Controller
 */
class Route_List_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'route:list';

	/**
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = 'List the registered routes in the application.';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'List the registered routes in the application.';

	/**
	 * Command synopsis.
	 *
	 * Supports registering command arguments in a string or array format.
	 * For example:
	 *
	 *     <argument> --example-flag
	 *
	 * @var string|array
	 */
	protected $synopsis = [
		[
			'description' => 'Output format.',
			'name'        => 'format',
			'optional'    => true,
			'options'     => [ 'table', 'json', 'csv', 'count' ],
			'type'        => 'flag',
		],
	];

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
	 * Callback for the command.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		$routes = $this->collect_routes();

		\WP_CLI\Utils\format_items(
			$this->get_flag( 'format', 'table' ),
			$routes->to_array(),
			[
				'method',
				'url',
				'name',
				'action',
				'middleware',
			]
		);
	}

	/**
	 * Collect the routes in the application.
	 *
	 * @return Collection
	 */
	protected function collect_routes(): Collection {
		$routes = $this->router->get_routes()->all();

		if ( empty( $routes ) ) {
			return collect();
		}

		return collect( $routes )
			->map(
				function ( Route $route, string $name ) {
					return [
						'action'     => $route->get_callback_name(),
						'method'     => implode( '|', $route->getMethods() ),
						'middleware' => implode( '|', $route->middleware() ),
						'name'       => $name,
						'url'        => $route->getPath(),
					];
				}
			);
	}
}
