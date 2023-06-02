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
 */
class Bootloader implements Contract {
	use Conditionable;

	/**
	 * Current instance of the manager.
	 *
	 * @var Bootloader|null
	 */
	protected static ?Bootloader $instance = null;

	/**
	 * Application base path.
	 *
	 * @var string|null
	 */
	protected ?string $base_path = null;

	/**
	 * Retrieve the instance of the manager.
	 *
	 * @param Contracts\Application|null $app Application instance.
	 * @return Bootloader
	 */
	public static function get_instance( ?Contracts\Application $app = null ): Bootloader {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static( $app );
		}

		return static::$instance;
	}

	/**
	 * Alias to `get_instance()` method.
	 *
	 * @param Contracts\Application|null $app Application instance.
	 * @return Bootloader
	 */
	public static function instance( ?Contracts\Application $app = null ): Bootloader {
		return static::get_instance( $app );
	}

	/**
	 * Set the instance of the manager.
	 *
	 * @param Bootloader|null $instance Instance of the manager.
	 * @return void
	 */
	public static function set_instance( ?Bootloader $instance = null ): void {
		static::$instance = $instance;
	}

	/**
	 * Constructor.
	 *
	 * @param Contracts\Application|null $app Application instance.
	 */
	public function __construct( protected ?Contracts\Application $app = null ) {
		static::set_instance( $this );
	}

	/**
	 * Bind to the container before booting.
	 *
	 * @param string              $abstract Abstract to bind.
	 * @param Closure|string|null $concrete Concrete to bind.
	 * @return static
	 */
	public function bind( string $abstract, Closure|string|null $concrete ): static {
		if ( is_null( $this->app ) ) {
			$this->boot_application();
		}

		$this->app->bind( $abstract, $concrete );

		return $this;
	}

	/**
	 * Boot the application given the current context.
	 *
	 * @return static
	 */
	public function boot(): static {
		$this->boot_application();

		// Bail if the application is already booted.
		if ( $this->app->is_booted() ) {
			return $this;
		}

		if ( $this->app->is_running_in_console_isolation() ) {
			$this->boot_console();
		} elseif ( $this->app->is_running_in_console() ) {
			$this->boot_console_wp_cli();
		} else {
			$this->boot_http();
		}

		return $this;
	}

	/**
	 * Boot the application and attach the relevant container classes.
	 *
	 * @return void
	 */
	protected function boot_application(): void {
		if ( is_null( $this->app ) ) {
			$this->app = new Application( $this->get_base_path() );
		}

		if ( function_exists( 'do_action' ) ) {
			/**
			 * Fired before the application is booted.
			 *
			 * @param \Mantle\Contracts\Application $app Application instance.
			 */
			do_action( 'mantle_bootloader_before_boot', $this->app );
		}

		$this->app->singleton_if(
			Contracts\Console\Kernel::class,
			\Mantle\Framework\Console\Kernel::class,
		);

		$this->app->singleton_if(
			Contracts\Http\Kernel::class,
			\Mantle\Framework\Http\Kernel::class,
		);

		$this->app->singleton_if(
			Contracts\Exceptions\Handler::class,
			\Mantle\Framework\Exceptions\Handler::class,
		);

		/**
		 * Fired after the application is booted.
		 *
		 * @param \Mantle\Contracts\Application $app Application instance.
		 */
		$this->app['events']->dispatch( 'mantle_bootloader_booted', $this->app );
	}

	/**
	 * Retrieve the application instance.
	 *
	 * @return Contracts\Application|null
	 */
	public function get_application(): ?Contracts\Application {
		return $this->app;
	}

	/**
	 * Get the calculated base path for the application.
	 *
	 * @todo Calculate a better default path from the plugin file.
	 *
	 * @return string|null
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

	/**
	 * Set the base path for the application.
	 *
	 * @param string|null $base_path Base path for the application.
	 * @return static
	 */
	public function set_base_path( ?string $base_path = null ): static {
		$this->base_path = $base_path;

		return $this;
	}

	/**
	 * Boot the application in the console context.
	 *
	 * @return void
	 */
	protected function boot_console(): void {
		$kernel = $this->app->make( Contracts\Console\Kernel::class );

		$status    = $kernel->handle(
			$input = new \Symfony\Component\Console\Input\ArgvInput(),
			new \Symfony\Component\Console\Output\ConsoleOutput(),
		);

		$kernel->terminate( $input, $status );

		exit( (int) $status );
	}

	/**
	 * Boot the application in the WP-CLI context.
	 *
	 * @return void
	 */
	protected function boot_console_wp_cli(): void {
		\WP_CLI::add_command(
			/**
			 * Command prefix for Mantle WP-CLI commands.
			 *
			 * @param string $prefix The command prefix.
			 * @param \Mantle\Contracts\Application $app The application instance.
			 */
			(string) apply_filters( 'mantle_console_command_prefix', Command::PREFIX, $this->app ),
			function () {
				$kernel = $this->app->make( Contracts\Console\Kernel::class );

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
}
