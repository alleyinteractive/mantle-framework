<?php
/**
 * Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

use Mantle\Console\Command;
use Mantle\Console\Application as Console_Application;
use Mantle\Contracts\Application;
use Mantle\Support\Attributes\Action;
use Mantle\Support\Str;
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait};
use ReflectionClass;
use function Mantle\Support\Helpers\add_action;
use function Mantle\Support\Helpers\collect;

/**
 * Application Service Provider
 */
abstract class Service_Provider implements LoggerAwareInterface {
	use LoggerAwareTrait;

	/**
	 * The paths that should be published.
	 *
	 * @var array
	 */
	public static array $publishes = [];

	/**
	 * The paths that should be published by group.
	 *
	 * @var array
	 */
	public static array $publish_tags = [];


	/**
	 * The application instance.
	 *
	 * @var Application|\Mantle\Container\Container
	 */
	protected $app;

	/**
	 * Commands to register.
	 * Register commands through `Service_Provider::add_command()`.
	 *
	 * @var \Mantle\Console\Command[]
	 */
	protected $commands;

	/**
	 * Create a new service provider instance.
	 *
	 * @param Application $app Application Instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Register the service provider.
	 */
	public function register() {}

	/**
	 * Boot the service provider.
	 */
	public function boot() {}

	/**
	 * Bootstrap services.
	 */
	public function boot_provider() {
		if ( isset( $this->app['log'] ) ) {
			$this->setLogger( $this->app['log']->driver() );
		}

		$this->boot_action_hooks();
		$this->boot_attribute_hooks();
		$this->boot();
	}

	/**
	 * Boot all actions on the service provider.
	 *
	 * Allow methods in the 'on_{hook}_at_priority' and 'on_{hook}' format
	 * to automatically register WordPress hooks.
	 */
	protected function boot_action_hooks() {
		collect( get_class_methods( static::class ) )
			->filter(
				function( string $method ) {
					return Str::starts_with( $method, 'on_' );
				}
			)
			->each(
				function( string $method ) {
					$hook     = Str::after( $method, 'on_' );
					$priority = 10;

					if ( Str::contains( $hook, '_at_' ) ) {
						// Strip the priority from the hook name.
						$priority = (int) Str::after_last( $hook, '_at_' );
						$hook     = Str::before_last( $hook, '_at_' );
					}

					add_action( $hook, [ $this, $method ], $priority );
				}
			);
	}

	/**
	 * Boot all attribute actions on the service provider.
	 */
	protected function boot_attribute_hooks() {
		$class = new ReflectionClass( static::class );

		foreach ( $class->getMethods() as $method ) {
			$action_attributes = $method->getAttributes( Action::class );

			if ( empty( $action_attributes ) ) {
				continue;
			}

			foreach ( $action_attributes as $attribute ) {
				$instance = $attribute->newInstance();

				add_action( $instance->action, [ $this, $method->name ], $instance->priority );
			}
		}
	}

	/**
	 * Register a console command.
	 *
	 * @param Command[]|string[]|Command|string $command Command instance or class name to register.
	 * @return Service_Provider
	 */
	public function add_command( $command ): Service_Provider {
		Console_Application::starting(
			fn ( Console_Application $console ) => $console->resolve_commands( $command )
		);

		return $this;
	}

	/**
	 * Register paths to be published by the publish command.
	 *
	 * @param string[]                  $paths Paths to publish.
	 * @param string|array<string>|null $tags Tags to publish.
	 */
	public function publishes( array $paths, $tags = null ): void {
		$class = static::class;

		if ( ! array_key_exists( $class, static::$publishes ) ) {
			static::$publishes[ $class ] = [];
		}

		static::$publishes[ $class ] = array_merge( static::$publishes[ $class ], $paths );

		foreach ( (array) $tags as $tag ) {
			if ( ! array_key_exists( $tag, static::$publish_tags ) ) {
				static::$publish_tags[ $tag ] = [];
			}

			static::$publish_tags[ $tag ] = array_merge(
				static::$publish_tags[ $tag ],
				$paths,
			);
		}
	}

	/**
	 * Get the service providers available for publishing.
	 *
	 * @return array<class-string<Service_Provider>>
	 */
	public static function publishable_providers() {
		return array_keys( static::$publishes );
	}

	/**
	 * Get the groups available for publishing.
	 *
	 * @return array<string>
	 */
	public static function publishable_tags() {
		return array_keys( static::$publish_tags );
	}

	/**
	 * Get the paths to publish.
	 *
	 * Passing both a provider and a tag will return all paths that are
	 * published by that provider and tag.
	 *
	 * @param  class-string<Service_Provider>|array<class-string<Service_Provider>>|null $providers The service provider class name.
	 * @param  string|array<string>|null                                                 $tags      The tag name.
	 * @return array<string, string> The paths to publish. Index is the source path, value is the destination path.
	 */
	public static function paths_to_publish( array|string|null $providers = null, array|string|null $tags = null ): array {
		if ( ! $providers && ! $tags ) {
			return [];
		}

		$provider_paths = collect();
		$tag_paths      = collect();

		if ( $providers ) {
			foreach ( (array) $providers as $item ) {
				$provider_paths = $provider_paths->merge( static::$publishes[ $item ] ?? [] );
			}
		}

		if ( $tags ) {
			foreach ( (array) $tags as $item ) {
				$tag_paths = $tag_paths->merge( static::$publish_tags[ $item ] ?? [] );
			}
		}

		// If both are passed, find the intersection.
		if ( $providers && $tags ) {
			return $provider_paths->intersect_by_keys( $tag_paths )->all();
		} elseif ( $providers ) {
			return $provider_paths->all();
		} elseif ( $tags ) {
			return $tag_paths->all();
		}
	}
}
