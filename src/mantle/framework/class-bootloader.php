<?php
/**
 * Bootloader class file
 *
 * @package Mantle
 */

namespace Mantle\Framework;

use Closure;
use Mantle\Application\Application;
use Mantle\Console\Command;
use Mantle\Contracts;
use Mantle\Contracts\Framework\Bootloader as Contract;
use Mantle\Framework\Bootstrap\Load_Configuration;
use Mantle\Framework\Bootstrap\Register_Providers;
use Mantle\Http\Request;
use Mantle\Support\Str;
use Mantle\Support\Traits\Conditionable;

/**
 * Boot Manager
 *
 * Used to instantiate the application and load the framework given the current
 * context. Removes the need for boilerplate code to be included in projects
 * (ala laravel/laravel) but still allows for the flexibility to do so if they
 * so choose.
 *
 * @todo Add support for console commands.
 * @todo Ensure only one app service provider is loaded.
 */
class Bootloader implements Contract {
	use Conditionable;

	/**
	 * Current instance of the manager.
	 */
	protected static ?Bootloader $instance;

	/**
	 * Retrieve the instance of the manager.
	 *
	 * @param Contracts\Application|null $app Application instance.
	 */
	public static function get_instance( ?Contracts\Application $app = null ): Bootloader {
		if ( ! isset( static::$instance ) || ( $app && $app !== static::$instance->get_application() ) ) {
			static::$instance = new static( $app );
		}

		return static::$instance;
	}

	/**
	 * Alias to `get_instance()` method.
	 *
	 * @param Contracts\Application|null $app Application instance.
	 */
	public static function instance( ?Contracts\Application $app = null ): Bootloader {
		return static::get_instance( $app );
	}

	/**
	 * Set the instance of the manager.
	 *
	 * @param Bootloader|null $instance Instance of the manager.
	 */
	public static function set_instance( ?Bootloader $instance = null ): void {
		static::$instance = $instance;
	}

	/**
	 * Clear the instance of the manager.
	 */
	public static function clear_instance(): void {
		static::$instance = null;
	}

	/**
	 * Constructor.
	 *
	 * @param Contracts\Application|null $app Application instance.
	 */
	public function __construct( protected ?Contracts\Application $app = null ) {
		static::set_instance( $this );

		$this
			->with_application( new Application() )
			->with_kernels()
			->with_exception_handler();
	}

	/**
	 * Set the application instance to be booted.
	 *
	 * @param Contracts\Application $app Application instance.
	 */
	public function with_application( Contracts\Application $app ): static {
		$this->app = $app;

		return $this;
	}

	/**
	 * Merge additional configuration to the existing configuration.
	 *
	 * Configuration passed will be merged recursively with the existing
	 * configuration after all application configuration has been loaded.
	 *
	 * @param array<string, mixed> $config Configuration to merge.
	 * @return static
	 */
	public function with_config( array $config ): static {
		Load_Configuration::merge( $config );

		return $this;
	}

	/**
	 * Bind the application with the default kernels.
	 *
	 * @param class-string<Contracts\Console\Kernel>|null $console Console kernel class.
	 * @param class-string<Contracts\Http\Kernel>|null    $http    HTTP kernel class.
	 * @return static
	 */
	public function with_kernels( string $console = null, string $http = null ): static {
		if ( $console && ! in_array( Contracts\Console\Kernel::class, class_implements( $console ), true ) ) {
			throw new \InvalidArgumentException(
				'Console kernel must implement the Contracts\Console\Kernel interface.',
			);
		}

		if ( $http && ! in_array( Contracts\Http\Kernel::class, class_implements( $http ), true ) ) {
			throw new \InvalidArgumentException(
				'HTTP kernel must implement the Contracts\Http\Kernel interface.',
			);
		}

		$this->app->singleton(
			Contracts\Console\Kernel::class,
			$console ?? \Mantle\Framework\Console\Kernel::class,
		);

		$this->app->singleton(
			Contracts\Http\Kernel::class,
			$http ?? \Mantle\Framework\Http\Kernel::class,
		);

		return $this;
	}

	/**
	 * Bind the application with a exception handler.
	 *
	 * @param class-string<Contracts\Exceptions\Handler>|null $handler Exception handler class.
	 * @return static
	 */
	public function with_exception_handler( string $handler = null ): static {
		if ( $handler && ! in_array( Contracts\Exceptions\Handler::class, class_implements( $handler ), true ) ) {
			throw new \InvalidArgumentException(
				'Exception handler must implement the Contracts\Exceptions\Handler interface.',
			);
		}

		$this->app->singleton(
			Contracts\Exceptions\Handler::class,
			$handler ?? \Mantle\Framework\Exceptions\Handler::class,
		);

		return $this;
	}

	/**
	 * Merge additional service providers to the list of providers.
	 *
	 * @param array<class-string<\Mantle\Support\Service_Provider>> $providers List of service providers.
	 */
	public function with_providers( array $providers ): static {
		Register_Providers::merge( $providers );

		return $this;
	}

	/**
	 * Setup routing from files for the application.
	 *
	 * @param Closure(\Mantle\Contracts\Http\Routing\Router):void|null $callback Callback to setup routes.
	 * @param string|null $web Web routes file.
	 * @param string|null $rest_api REST API routes file.
	 * @param bool|callable(\Mantle\Http\Request):bool|null $pass_through Pass requests through to WordPress (or a callback to determine if it should).
	 * @return static
	 */
	public function with_routing(
		?Closure $callback = null,
		?string $web = null,
		?string $rest_api = null,
		bool|callable|null $pass_through = null,
	): static {
		$this->app->booted(
			function ( Application $app ) use ( $callback, $web, $rest_api, $pass_through ) {
				$router = $app['router'];

				if ( $callback ) {
					$callback( $router );
				}

				if ( $web ) {
					$router->middleware( 'web' )->group( $web );
				}

				if ( $rest_api ) {
					$router->middleware( 'rest-api' )->group( $rest_api );
				}

				if ( ! is_null( $pass_through ) ) {
					$router->pass_requests_to_wordpress( $pass_through );
				}
			}
		);

		return $this;
	}

	/**
	 * Bind to the container before booting.
	 *
	 * @param string              $abstract Abstract to bind.
	 * @param Closure|string|null $concrete Concrete to bind.
	 */
	public function bind( string $abstract, Closure|string|null $concrete ): static {
		$this->app->bind( $abstract, $concrete );

		return $this;
	}

	/**
	 * Boot the application given the current context.
	 */
	public function boot(): static {
		$this->boot_application();

		match ( true ) {
			$this->app->is_running_in_console_isolation() => $this->boot_console(),
			$this->app->is_running_in_console() => $this->boot_console_wp_cli(),
			default => $this->boot_http(),
		};

		return $this;
	}

	/**
	 * Boot the application and attach the relevant container classes.
	 */
	protected function boot_application(): void {
		if ( $this->app->is_booted() ) {
			return;
		}

		if ( function_exists( 'do_action' ) ) {
			/**
			 * Fired before the application is booted.
			 *
			 * @param \Mantle\Contracts\Application $app Application instance.
			 */
			do_action( 'mantle_bootloader_before_boot', $this->app );
		}

		/**
		 * Fired after the application is booted.
		 *
		 * @param \Mantle\Contracts\Application $app Application instance.
		 */
		$this->app['events']->dispatch( 'mantle_bootloader_booted', $this->app );
	}

	/**
	 * Retrieve the application instance.
	 */
	public function get_application(): ?Contracts\Application {
		return $this->app;
	}

	/**
	 * Set the base path for the application.
	 *
	 * @param string|null $base_path Base path for the application.
	 */
	public function set_base_path( ?string $base_path = null ): static {
		$this->app->set_base_path( $base_path );

		return $this;
	}

	/**
	 * Alias to `set_base_path()` method.
	 *
	 * @param string|null $base_path Base path for the application.
	 */
	public function with_base_path( ?string $base_path = null ): static {
		return $this->set_base_path( $base_path );
	}

	/**
	 * Boot the application in the console context.
	 */
	protected function boot_console(): void {
		$kernel = $this->app->make( Contracts\Console\Kernel::class );

		$kernel->bootstrap();

		$status = $kernel->handle(
			$input = new \Symfony\Component\Console\Input\ArgvInput(),
			new \Symfony\Component\Console\Output\ConsoleOutput(),
		);

		$kernel->terminate( $input, $status );

		exit( (int) $status );
	}

	/**
	 * Boot the application in the WP-CLI context.
	 */
	protected function boot_console_wp_cli(): void {
		$kernel = $this->app->make( Contracts\Console\Kernel::class );

		$kernel->bootstrap();

		\WP_CLI::add_command(
			/**
			 * Command prefix for Mantle WP-CLI commands.
			 *
			 * @param string $prefix The command prefix.
			 * @param \Mantle\Contracts\Application $app The application instance.
			 */
			(string) apply_filters( 'mantle_console_command_prefix', Command::PREFIX, $this->app ),
			function () use ( $kernel ): void {
				$status    = $kernel->handle(
					$input = new \Symfony\Component\Console\Input\ArgvInput(
						collect( (array) ( $_SERVER['argv'] ?? [] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							// Remove the `wp` prefix from argv and any invalid arguments (such as --url).
							->filter( fn ( $value, $index ) => 0 !== $index && ! Str::starts_with( $value, '--url=' ) )
							->all()
					),
					new \Symfony\Component\Console\Output\ConsoleOutput(),
				);

				$kernel->terminate( $input, $status );

				exit( (int) $status );
			},
			[
				'shortdesc' => __( 'Mantle Framework Command Line Interface', 'mantle' ),
			]
		);
	}

	/**
	 * Boot the application in the HTTP context.
	 *
	 * @return void
	 */
	protected function boot_http() {
		$kernel = $this->app->make( Contracts\Http\Kernel::class );

		$kernel->handle( Request::capture() );
	}

	/**
	 * Get the calculated base path for the application.
	 */
	public function get_base_path(): ?string {
		if ( ! empty( $this->base_path ) ) {
			return $this->base_path;
		}

		return match ( true ) {
			! empty( $_ENV['MANTLE_BASE_PATH'] ) => $_ENV['MANTLE_BASE_PATH'],
			defined( 'MANTLE_BASE_PATH' ) => constant( 'MANTLE_BASE_PATH' ),
			default => dirname( __DIR__, 3 ),
		};
	}
}
