<?php
/**
 * Alias_Loader file.
 *
 * @package Mantle
 */

namespace Mantle\Framework;

/**
 * Alias Loader
 *
 * Sets up the aliases for the application to allow
 */
class Alias_Loader {
	/**
	 * Indicates if a loader has been registered.
	 *
	 * @var bool
	 */
	protected $registered = false;

	/**
	 * The singleton instance of the loader.
	 */
	protected static ?Alias_Loader $instance = null;

	/**
	 * Create a new Alias_Loader instance.
	 *
	 * @param array $aliases Aliases to set.
	 */
	private function __construct(
					/**
					 * The array of class aliases.
					 */
					protected $aliases
				) {     }

	/**
	 * Get or create the singleton alias loader instance.
	 *
	 * @param array $aliases Alias to load.
	 */
	public static function get_instance( array $aliases = [] ): Alias_Loader {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static( $aliases );
			return static::$instance;
		}

		$aliases = array_merge( static::$instance->get_aliases(), $aliases );

		static::$instance->set_aliases( $aliases );

		return static::$instance;
	}

	/**
	 * Load a class alias if it is registered.
	 *
	 * @param  string $alias Alias to load.
	 */
	public function load( $alias ): void {
		if ( isset( $this->aliases[ $alias ] ) ) {
			class_alias( $this->aliases[ $alias ], $alias );
		}
	}

	/**
	 * Add an alias to the loader.
	 *
	 * @param  string $class Alias class.
	 * @param  string $alias Alias name.
	 */
	public function alias( $class, $alias ): void {
		$this->aliases[ $class ] = $alias;
	}

	/**
	 * Register the loader on the auto-loader stack.
	 */
	public function register(): void {
		if ( ! $this->registered ) {
			$this->prepend_to_loader_stack();

			$this->registered = true;
		}
	}

	/**
	 * Prepend the load method to the auto-loader stack.
	 *
	 * @return void
	 */
	protected function prepend_to_loader_stack() {
		spl_autoload_register( [ $this, 'load' ], true, true );
	}

	/**
	 * Get the registered aliases.
	 *
	 * @return array
	 */
	public function get_aliases() {
		return $this->aliases;
	}

	/**
	 * Set the registered aliases.
	 *
	 * @param array $aliases Alias to set.
	 */
	public function set_aliases( array $aliases ): void {
		$this->aliases = $aliases;
	}

	/**
	 * Indicates if the loader has been registered.
	 *
	 * @return bool
	 */
	public function is_registered() {
		return $this->registered;
	}

	/**
	 * Set the "registered" state of the loader.
	 *
	 * @param  bool $value Value to set.
	 */
	public function set_registered( $value ): void {
		$this->registered = $value;
	}

	/**
	 * Set the value of the singleton alias loader.
	 *
	 * @param Alias_Loader|null $loader Load to set.
	 */
	public static function set_instance( ?Alias_Loader $loader ): void {
		static::$instance = $loader;
	}
}
