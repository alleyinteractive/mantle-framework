<?php
/**
 * Route Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Route
 *
 * @method static \Mantle\Http\Routing\Route get(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route post(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route put(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route delete(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route patch(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route options(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route|null add_route(array $methods, string $uri, mixed $action)
 * @method static \Symfony\Component\Routing\RouteCollection get_routes()
 * @method static \Mantle\Contracts\Container get_container()
 * @method static \Symfony\Component\HttpFoundation\Response|null dispatch(\Mantle\Http\Request $request)
 * @method static array get_middleware()
 * @method static \Mantle\Http\Routing\Router alias_middleware(string $name, string $class)
 * @method static array get_middleware_groups()
 * @method static \Mantle\Http\Routing\Router middleware_group(string $name, array $middleware)
 * @method static \Mantle\Http\Routing\Router prepend_middleware_to_group(string $group, string $middleware)
 * @method static \Mantle\Http\Routing\Router push_middleware_to_group(string $group, string $middleware)
 * @method static array gather_route_middleware(\Mantle\Http\Routing\Route $route)
 * @method static void bind(string $key, string|callable $binder)
 * @method static void bind_model(string $key, string $class, \Closure|null $callback = null)
 * @method static void substitute_bindings(\Mantle\Http\Request $request)
 * @method static void substitute_implicit_bindings(\Mantle\Http\Request $request)
 * @method static \Mantle\Http\Routing\Rest_Route_Registrar rest_api(string $namespace, Closure|string $route, array|Closure $args = [])
 * @method static void model(string $model, string $controller)
 * @method static void sync_routes_to_url_generator()
 * @method static \Mantle\Http\Routing\Router rename_route(string $old_name, string $new_name)
 * @method static bool has_group_stack()
 * @method static array get_group_stack()
 * @method static void group(array $attributes, \Closure|string $routes)
 * @method static array merge_with_last_group(array $new, bool $prepend_existing_prefix = true)
 * @method static array merge(array $new, array $old, bool $prepend_existing_prefix = true)
 *
 * @see \Mantle\Http\Routing\Router
 */
class Route extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'router';
	}
}
