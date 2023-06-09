<?php
/**
 * View_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\View;

use Illuminate\Filesystem\Filesystem;
use Mantle\Http\View\Factory;
use Mantle\Http\View\View_Finder;
use Mantle\Support\Service_Provider;
use Mantle\View\Engines\Engine_Resolver;
use Mantle\View\Engines\File_Engine;
use Mantle\View\Engines\Php_Engine;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;

use function Mantle\Support\Helpers\tap;

/**
 * View Service Provider
 */
class View_Service_Provider extends Service_Provider {

	/**
	 * Register the service provider.
	 */
	public function register() {
		$this->register_blade_compiler();
		$this->register_engine_resolver();
		$this->register_loader();
		$this->register_factory();
	}

	/**
	 * Register the Blade Compiler Engine
	 */
	protected function register_blade_compiler() {
		$this->app->singleton(
			'blade.compiler',
			fn( $app ) => new BladeCompiler( new Filesystem(), $app['config']['view.compiled'] ),
		);
	}

	/**
	 * Register the view engine resolver.
	 */
	protected function register_engine_resolver() {
		$this->app->singleton(
			'view.engine.resolver',
			fn() => tap(
				new Engine_Resolver(),
				function( Engine_Resolver $resolver ) {
					// Register the various view engines.
					$this->register_php_engine( $resolver );
					$this->register_file_engine( $resolver );
					$this->register_compiler_engine( $resolver );
				}
			),
		);
	}

	/**
	 * Register the PHP (WordPress template) view engine.
	 *
	 * @param Engine_Resolver $resolver Engine resolver.
	 */
	protected function register_php_engine( Engine_Resolver $resolver ) {
		$resolver->register(
			'php',
			fn () => new Php_Engine(),
		);
	}

	/**
	 * Register the file view engine.
	 *
	 * @param Engine_Resolver $resolver Engine resolver.
	 */
	protected function register_file_engine( Engine_Resolver $resolver ) {
		$resolver->register(
			'file',
			fn () => new File_Engine(),
		);
	}

	/**
	 * Register the compiler view engine.
	 *
	 * @param Engine_Resolver $resolver Engine resolver.
	 */
	protected function register_compiler_engine( Engine_Resolver $resolver ) {
		$resolver->register(
			'blade',
			fn () => new CompilerEngine( $this->app['blade.compiler'] ),
		);
	}

	/**
	 * Register the view loader.
	 */
	protected function register_loader() {
		$this->app->singleton(
			'view.loader',
			fn ( $app ) => tap(
				new View_Finder( $app->get_base_path() ),
				function ( View_Finder $loader ) {
					// Register the base view folder for the project.
					$loader->add_path( $this->app->get_base_path( 'views/' ) );
				}
			),
		);
	}

	/**
	 * Register the view factory.
	 */
	protected function register_factory() {
		$this->app->singleton(
			'view',
			function( $app ) {
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
}
