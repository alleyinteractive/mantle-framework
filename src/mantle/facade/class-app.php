<?php
/**
 * App Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * App Facade
 *
 * @method static \Mantle\Application\Application set_base_path(string $path)
 * @method static string get_base_path(string $path = '')
 * @method static string get_app_path(string $path = '')
 * @method static \Mantle\Application\Application set_app_path(string $path)
 * @method static string get_bootstrap_path(string $path = '')
 * @method static string get_storage_path(string $path = '')
 * @method static void set_root_url(string|null $url = null)
 * @method static string get_root_url(string $path = '')
 * @method static string get_cache_path(string|null $path = null)
 * @method static string get_cached_packages_path()
 * @method static string get_cached_models_path()
 * @method static bool is_configuration_cached()
 * @method static string get_cached_config_path()
 * @method static bool is_events_cached()
 * @method static string get_cached_events_path()
 * @method static string get_config_path()
 * @method static bool has_been_bootstrapped()
 * @method static void register_base_services()
 * @method static void flush()
 * @method static void bootstrap_with(array $bootstrappers, \Mantle\Contracts\Kernel $kernel)
 * @method static bool is_booted()
 * @method static \Mantle\Application\Application boot()
 * @method static string environment_file(string $file = null)
 * @method static string environment_path(string $path = null)
 * @method static string environment()
 * @method static bool is_environment(string|array ...$environments)
 * @method static string get_namespace()
 * @method static string namespace()
 * @method static bool is_running_in_console()
 * @method static bool is_running_in_console_isolation()
 * @method static \Mantle\Application\Application set_environment(string $environment)
 * @method static void abort(int $code, string $message = '', array $headers = [])
 * @method static \static booting(callable $callback)
 * @method static \static booted(callable $callback)
 * @method static \static terminating(callable $callback)
 * @method static void terminate()
 * @method static bool bound(string $abstract)
 * @method static bool has(string $id)
 * @method static bool resolved(string $abstract)
 * @method static bool is_shared(string $abstract)
 * @method static bool is_alias(string $name)
 * @method static void bind(string $abstract, \Closure|string|null $concrete = null, bool $shared = false)
 * @method static bool has_method_binding(string $method)
 * @method static void bind_method(array|string $method, \Closure $callback)
 * @method static mixed call_method_binding(string $method, mixed $instance)
 * @method static void bind_if(string $abstract, \Closure|string|null $concrete = null, bool $shared = false)
 * @method static void singleton(string $abstract, \Closure|string|null $concrete = null)
 * @method static void singleton_if(string $abstract, \Closure|string|null $concrete = null)
 * @method static void extend(string $abstract, \Closure $closure)
 * @method static mixed instance(string $abstract, mixed $instance)
 * @method static void alias(string $abstract, string $alias)
 * @method static mixed rebinding(string $abstract, \Closure $callback)
 * @method static mixed refresh(string $abstract, mixed $target, string $method)
 * @method static \Closure wrap(\Closure $callback, array $parameters = [])
 * @method static mixed call(callable|string $callback, string[] $parameters = [], string|null $default_method = null)
 * @method static \Closure factory(string $abstract)
 * @method static mixed make_with(string $abstract, array $parameters = [])
 * @method static mixed make(string $abstract, array $parameters = [])
 * @method static void get(string $id)
 * @method static mixed build(\Closure|string $concrete)
 * @method static void resolving(\Closure|string $abstract, \Closure|null $callback = null)
 * @method static void after_resolving(\Closure|string $abstract, \Closure|null $callback = null)
 * @method static array get_bindings()
 * @method static string get_alias(string $abstract)
 * @method static void forget_extenders(string $abstract)
 * @method static void forget_instance(string $abstract)
 * @method static void forget_instances()
 * @method static \Mantle\Contracts\Container getInstance()
 * @method static \Mantle\Contracts\Container get_instance()
 * @method static \Mantle\Contracts\Container|null set_instance(\Mantle\Contracts\Container|null $container = null)
 * @method static void load_base_configuration()
 * @method static void load_environment_variables()
 * @method static void load_facades()
 * @method static void register_configured_providers()
 * @method static \Mantle\Support\Service_Provider|null get_provider(string $name)
 * @method static \Mantle\Support\Service_Provider[] get_providers()
 * @method static \static register(\Mantle\Support\Service_Provider|string $provider)
 *
 * @see \Mantle\Application\Application
 */
class App extends Facade {
	/**
	 * Facade Accessor
	 */
	protected static function get_facade_accessor(): string {
		return 'app';
	}
}
