<?php
/**
 * Route Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Route Facade
 *
 * @method static \Mantle\Http\Routing\Route get(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route post(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route put(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route delete(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route patch(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route options(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route|null any(string $uri, mixed $action = '')
 * @method static \Mantle\Http\Routing\Route|null add_route(array $methods, string $uri, mixed $action)
 * @method static \Symfony\Component\Routing\RouteCollection get_routes()
 * @method static \Mantle\Contracts\Container get_container()
 * @method static \Symfony\Component\HttpFoundation\Response|null dispatch(\Mantle\Http\Request $request)
 * @method static \Symfony\Component\HttpFoundation\Response to_response(\Mantle\Http\Request $request, mixed $response)
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
 * @method static void rest_api(string $namespace, callable|string $callback, callable|array|string $args = [])
 * @method static void model(string $model, string $controller)
 * @method static void sync_routes_to_url_generator()
 * @method static \Mantle\Http\Routing\Router rename_route(string $old_name, string $new_name)
 * @method static \Mantle\Http\Routing\Router pass_requests_to_wordpress(callable|bool $callback)
 * @method static bool should_pass_through_request(\Mantle\Http\Request $request)
 * @method static bool has_group_stack()
 * @method static array get_group_stack()
 * @method static void group(array $attributes, \Closure|string $routes)
 * @method static array merge_with_last_group(array $new, bool $prepend_existing_prefix = true)
 * @method static array merge(array $new, array $old, bool $prepend_existing_prefix = true)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool has_macro(string $name)
 * @method static mixed macro_call(string $method, array $parameters)
 * @method static \Mantle\Http\Routing\Route_Registrar attribute(string $key, mixed $value)
 * @method static \Mantle\Http\Routing\Route_Registrar as(string $value)
 * @method static \Mantle\Http\Routing\Route_Registrar domain(string $value)
 * @method static \Mantle\Http\Routing\Route_Registrar middleware(array|string|null $middleware)
 * @method static \Mantle\Http\Routing\Route_Registrar name(string $value)
 * @method static \Mantle\Http\Routing\Route_Registrar namespace(string $value)
 * @method static \Mantle\Http\Routing\Route_Registrar prefix(string $value)
 * @method static \Mantle\Http\Routing\Route_Registrar where(array $where)
 *
 * @see \Mantle\Http\Routing\Router
 */
class Route extends Facade {
	/**
	 * Get the registered name of the component.
	 */
	protected static function get_facade_accessor(): string {
		return 'router';
	}
}
