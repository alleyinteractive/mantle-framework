<?php
/**
 * View_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\View;

use Illuminate\Filesystem\Filesystem as Illuminate_Filesystem;
use Mantle\Http\View\Factory;
use Mantle\Http\View\View_Finder;
use Mantle\Support\Service_Provider;
use Mantle\View\Engines\Engine_Resolver;
use Mantle\View\Engines\File_Engine;
use Mantle\View\Engines\Php_Engine;
use Illuminate\View\Compilers\BladeCompiler;
use Mantle\Filesystem\Filesystem;
use Mantle\View\Engines\Blade_Engine;

use function Mantle\Support\Helpers\mixed;
use function Mantle\Support\Helpers\tap;

/**
 * View Service Provider
 */
class View_Service_Provider extends Service_Provider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->register_blade_compiler();
		$this->register_engine_resolver();
		$this->register_loader();
		$this->register_factory();
	}

	/**
	 * Register the Blade Compiler Engine
	 */
	protected function register_blade_compiler(): void {
		$this->app->singleton(
			'blade.compiler',
			fn ( \Mantle\Contracts\Application $app ) => new BladeCompiler(
				new Illuminate_Filesystem(),
				$app['config']['view.compiled'],
				$app->get_base_path(),
				$this->should_cache_views(),
			),
		);
	}

	/**
	 * Register the view engine resolver.
	 */
	protected function register_engine_resolver(): void {
		$this->app->singleton(
			'view.engine.resolver',
			fn () => tap(
				new Engine_Resolver(),
				function ( Engine_Resolver $resolver ): void {
					// Register the various view engines.
					$this->register_php_engine( $resolver );
					$this->register_file_engine( $resolver );
					$this->register_blade_engine( $resolver );
				}
			),
		);
	}

	/**
	 * Register the PHP (WordPress template) view engine.
	 *
	 * @param Engine_Resolver $resolver Engine resolver.
	 */
	protected function register_php_engine( Engine_Resolver $resolver ): void {
		$resolver->register( 'php', fn () => new Php_Engine( $this->app['files'] ) );
	}

	/**
	 * Register the file view engine.
	 *
	 * @param Engine_Resolver $resolver Engine resolver.
	 */
	protected function register_file_engine( Engine_Resolver $resolver ): void {
		$resolver->register( 'file', fn () => new File_Engine() );
	}

	/**
	 * Register the compiler view engine.
	 *
	 * @param Engine_Resolver $resolver Engine resolver.
	 */
	protected function register_blade_engine( Engine_Resolver $resolver ): void {
		$resolver->register(
			'blade',
			fn () => new Blade_Engine(
				filesystem: $this->app['files'],
				compiler: $this->app['blade.compiler'],
				should_write_files: $this->should_cache_views(),
			),
		);
	}

	/**
	 * Register the view loader.
	 */
	protected function register_loader(): void {
		$this->app->singleton(
			'view.loader',
			fn ( $app ) => tap(
				new View_Finder( $app->get_base_path(), new Filesystem() ),
				function ( View_Finder $loader ): void {
					// Register the base view folder for the project.
					$loader->add_path( $this->app->get_base_path( 'views/' ) );
				}
			),
		);
	}

	/**
	 * Register the view factory.
	 */
	protected function register_factory(): void {
		$this->app->singleton(
			'view',
			function ( \Mantle\Contracts\Application $app ): \Mantle\Http\View\Factory {
				$factory = new Factory(
					$app,
					$app['view.engine.resolver'],
					$app['view.loader']
				);

				$factory->share( 'app', $app );

				return $factory;
			}
		);
	}

	/**
	 * Check if views should be cached (written to the filesystem).
	 */
	protected function should_cache_views(): bool {
		$compiled_path = $this->app['config']['view.compiled'];

		static $is_writeable = null;

		if ( is_null( $is_writeable ) ) {
			$filesystem = new Filesystem();

			$filesystem->ensure_directory_exists( $compiled_path, 0777, true );

			// Trigger a notice if the compiled view path is not writeable.
			if ( ! $filesystem->is_writable( $compiled_path ) ) {
				_doing_it_wrong(
					self::class . '::' . __FUNCTION__,
					esc_html( sprintf(
						/* translators: %s: path to the compiled views directory. */
						__( 'The compiled views directory (%1$s) is not writable.', 'mantle' ),
						$compiled_path
					) ),
					'1.0.0',
				);

				$is_writeable = false;
			}

			$is_writeable = $filesystem->is_directory( $compiled_path );
		}

		/**
		 * Filter to determine if views should be cached.
		 *
		 * @param bool   $is_writeable Whether the compiled path is writeable.
		 * @param string $compiled_path The path to the compiled views directory.
		 */
		return mixed( apply_filters( 'mantle_should_cache_views', $is_writeable, $compiled_path ) )->bool();
	}
}
