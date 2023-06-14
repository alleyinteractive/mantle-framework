<?php
/**
 * Str class file
 *
 * @package Mantle
 */

namespace Mantle\Support;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;

/**
 * Interface with Doctrine Inflector to pluralize and singularize words.
 */
class Pluralizer {

	/**
	 * The cached inflector instance.
	 *
	 * @var Inflector|null
	 */
	protected static ?Inflector $inflector = null;

	/**
	 * The language that should be used by the inflector.
	 *
	 * @var string
	 */
	protected static $language = 'english';

	/**
	 * Uncountable non-nouns word forms.
	 *
	 * Contains words supported by Doctrine/Inflector/Rules/English/Uninflected.php
	 *
	 * @var string[]
	 */
	public static $uncountable = [
		'recommended',
		'related',
	];

	/**
	 * Get the plural form of an English word.
	 *
	 * @param  string               $value
	 * @param  int|array|\Countable $count
	 * @return string
	 */
	public static function plural( string $value, int|array|\Countable $count = 2 ): string {
		if ( is_countable( $count ) ) {
			$count = count( $count );
		}

		if ( (int) abs( $count ) === 1 || static::uncountable( $value ) || preg_match( '/^(.*)[A-Za-z0-9\x{0080}-\x{FFFF}]$/u', $value ) == 0 ) {
			return $value;
		}

		$plural = static::inflector()->pluralize( $value );

		return static::match_case( $plural, $value );
	}

	/**
	 * Get the singular form of an English word.
	 *
	 * @param  string $value
	 * @return string
	 */
	public static function singular( string $value ): string {
		$singular = static::inflector()->singularize( $value );

		return static::match_case( $singular, $value );
	}

	/**
	 * Determine if the given value is uncountable.
	 *
	 * @param  string $value
	 * @return bool
	 */
	protected static function uncountable( $value ) {
		return in_array( strtolower( $value ), static::$uncountable );
	}

	/**
	 * Attempt to match the case on two strings.
	 *
	 * @param  string $value
	 * @param  string $comparison
	 * @return string
	 */
	protected static function match_case( string $value, string $comparison ) {
		$functions = [ 'mb_strtolower', 'mb_strtoupper', 'ucfirst', 'ucwords' ];

		foreach ( $functions as $function ) {
			if ( $function( $comparison ) === $comparison ) {
				return $function( $value );
			}
		}

		return $value;
	}

	/**
	 * Get the inflector instance.
	 *
	 * @return \Doctrine\Inflector\Inflector
	 */
	public static function inflector(): Inflector {
		if ( ! isset( static::$inflector ) ) {
			static::$inflector = InflectorFactory::createForLanguage( static::$language )->build();
		}

		return static::$inflector;
	}

	/**
	 * Specify the language that should be used by the inflector.
	 *
	 * @param  string $language
	 * @return void
	 */
	public static function use_language( string $language ): void {
		static::$language = $language;

		static::$inflector = null;
	}
}
