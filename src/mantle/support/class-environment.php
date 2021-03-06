<?php
/**
 * Environment class file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

use PhpOption\Option;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;

use function Mantle\Framework\Helpers\value;

/**
 * Storage of environment variables for the application.
 */
class Environment {
	/**
	 * Variable repository.
	 *
	 * @var array
	 */
	protected static $repository;

	/**
	 * Get the environment repository instance.
	 *
	 * @return \Dotenv\Repository\RepositoryInterface
	 */
	public static function get_repository(): RepositoryInterface {
		if ( null === static::$repository ) {
			$builder = RepositoryBuilder::createWithDefaultAdapters();

			static::$repository = $builder->immutable()->make();
		}

		return static::$repository;
	}

	/**
	 * Clear the environment repository instance.
	 *
	 * @return void
	 */
	public static function clear(): void {
		static::$repository = null;
	}

	/**
	 * Get the value of an environment variable.
	 *
	 * @param string $key Variable to retrieve.
	 * @param mixed  $default Default value. Supports a closure callback.
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		return Option::fromValue( static::get_repository()->get( $key ) )
			->map(
				function ( $value ) {
					switch ( strtolower( $value ) ) {
						case 'true':
						case '(true)':
							return true;
						case 'false':
						case '(false)':
							return false;
						case 'empty':
						case '(empty)':
							return '';
						case 'null':
						case '(null)':
							return;
					}

					if ( preg_match( '/\A([\'"])(.*)\1\z/', $value, $matches ) ) {
						return $matches[2];
					}

					return $value;
				}
			)
			->getOrCall(
				function() use ( $default ) {
					return value( $default );
				}
			);
	}
}
