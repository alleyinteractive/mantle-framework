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
		if ( ! isset( static::$instance ) || ( $app instanceof \Mantle\Contracts\Application && $app !== static::$instance->get_application() ) ) {
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
	 * @param string|null                $base_path Base path for the application.
	 */
	public function __construct( protected ?Contracts\Application $app = null, ?string $base_path = null ) {
		static::set_instance( $this );

		$this
			->with_application( new Application( $base_path ) )
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
	 * @param array<string, array<mixed>> $config Configuration to merge.
	 */
	public function with_config( array $config ): static {
		Load_Configuration::merge( $config );

		return $this;
	}

	/**
	 * Bind the application with the default kernels.
	 *
	 * @throws \InvalidArgumentException If the console or HTTP kernel does not implement the correct interface.
	 *
	 * @param class-string<Contracts\Console\Kernel>|null $console Console kernel class.
	 * @param class-string<Contracts\Http\Kernel>|null    $http    HTTP kernel class.
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
	 * @throws \InvalidArgumentException If the handler does not implement the correct interface.
	 *
	 * @param class-string<Contracts\Exceptions\Handler>|null $handler Exception handler class.
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
	 * @param string|null                                              $web Web routes file.
	 * @param string|null                                              $rest_api REST API routes file.
	 * @param bool|callable(\Mantle\Http\Request):bool|null            $pass_through Pass requests through to WordPress (or a callback to determine if it should).
	 */
	public function with_routing(
		?Closure $callback = null,
		?string $web = null,
		?string $rest_api = null,
		bool|callable|null $pass_through = null,
	): static {
		$this->app->booted(
			function ( Application $app ) use ( $callback, $web, $rest_api, $pass_through ): void {
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
	 * Boot the application in the console context.
	 */
	protected function boot_console(): void {
		$kernel = $this->app->make( Contracts\Console\Kernel::class );

		$kernel->bootstrap();

		$status    = $kernel->handle(
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
	 * Pass through any unknown methods to the application.
	 *
	 * Previously bootstrap/app.php would have return an application instance. To
	 * preserve backwards compatibility, we need to pass through any unknown
	 * methods to the application instance.
	 *
	 * @param string $method Method to call.
	 * @param array  $args Arguments to pass to the method.
	 */
	public function __call( string $method, array $args ): mixed {
		return $this->get_application()?->$method( ...$args );
	}
}
