<?php
/**
 * This file contains the Str class
 *
 * @package Mantle
 */

namespace Mantle\Framework\Support;
use voku\helper\ASCII;

/**
 * String helpers.
 */
class Str {

	/**
	 * The cache of snake-cased words.
	 *
	 * @var array
	 */
	protected static $snake_cache = [];

	/**
	 * The cache of camel-cased words.
	 *
	 * @var array
	 */
	protected static $camel_cache = [];

	/**
	 * The cache of studly-cased words.
	 *
	 * @var array
	 */
	protected static $studly_cache = [];

	/**
	 * Return the remainder of a string after the last occurrence of a given
	 * value.
	 *
	 * @param string $subject
	 * @param string $search
	 * @return string
	 */
	public static function after_last( $subject, $search ) {
		if ( $search === '' ) {
			return $subject;
		}

		$position = strrpos( $subject, (string) $search );

		if ( $position === false ) {
			return $subject;
		}

		return substr( $subject, $position + strlen( $search ) );
	}

	/**
	 * Transliterate a UTF-8 value to ASCII.
	 *
	 * @param string $value
	 * @param string $language
	 * @return string
	 */
	public static function ascii( $value, $language = 'en' ) {
		return ASCII::to_ascii( (string) $value, $language );
	}

	/**
	 * Get the portion of a string before the first occurrence of a given value.
	 *
	 * @param string $subject
	 * @param string $search
	 * @return string
	 */
	public static function before( $subject, $search ) {
		return $search === '' ? $subject : explode( $search, $subject )[0];
	}

	/**
	 * Get the portion of a string between two given values.
	 *
	 * @param string $subject
	 * @param string $from
	 * @param string $to
	 * @return string
	 */
	public static function between( $subject, $from, $to ) {
		if ( $from === '' || $to === '' ) {
			return $subject;
		}

		return static::before_last( static::after( $subject, $from ), $to );
	}

	/**
	 * Get the portion of a string before the last occurrence of a given value.
	 *
	 * @param string $subject
	 * @param string $search
	 * @return string
	 */
	public static function before_last( $subject, $search ) {
		if ( $search === '' ) {
			return $subject;
		}

		$pos = mb_strrpos( $subject, $search );

		if ( $pos === false ) {
			return $subject;
		}

		return static::substr( $subject, 0, $pos );
	}

	/**
	 * Returns the portion of string specified by the start and length parameters.
	 *
	 * @param string   $string
	 * @param int      $start
	 * @param int|null $length
	 * @return string
	 */
	public static function substr( $string, $start, $length = null ) {
		return mb_substr( $string, $start, $length, 'UTF-8' );
	}

	/**
	 * Return the remainder of a string after the first occurrence of a given
	 * value.
	 *
	 * @param string $subject
	 * @param string $search
	 * @return string
	 */
	public static function after( $subject, $search ) {
		return $search === '' ? $subject : array_reverse( explode( $search, $subject, 2 ) )[0];
	}

	/**
	 * Convert a value to camel case.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function camel( $value ) {
		if ( isset( static::$camel_cache[ $value ] ) ) {
			return static::$camel_cache[ $value ];
		}

		return static::$camel_cache[ $value ] = lcfirst( static::studly( $value ) );
	}

	/**
	 * Convert a value to studly caps case.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function studly( $value ) {
		$key = $value;

		if ( isset( static::$studly_cache[ $key ] ) ) {
			return static::$studly_cache[ $key ];
		}

		$value = ucwords( str_replace( [ '-', '_' ], ' ', $value ) );

		return static::$studly_cache[ $key ] = str_replace( ' ', '', $value );
	}

	/**
	 * Determine if a given string contains all array values.
	 *
	 * @param string   $haystack
	 * @param string[] $needles
	 * @return bool
	 */
	public static function contains_all( $haystack, array $needles ) {
		foreach ( $needles as $needle ) {
			if ( ! static::contains( $haystack, $needle ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if a given string contains a given substring.
	 *
	 * @param string          $haystack
	 * @param string|string[] $needles
	 * @return bool
	 */
	public static function contains( $haystack, $needles ) {
		foreach ( (array) $needles as $needle ) {
			if ( $needle !== '' && mb_strpos( $haystack, $needle ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if a given string ends with a given substring.
	 *
	 * @param string          $haystack
	 * @param string|string[] $needles
	 * @return bool
	 */
	public static function ends_with( $haystack, $needles ) {
		foreach ( (array) $needles as $needle ) {
			if ( substr( $haystack, - strlen( $needle ) ) === (string) $needle ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cap a string with a single instance of a given value.
	 *
	 * @param string $value
	 * @param string $cap
	 * @return string
	 */
	public static function finish( $value, $cap ) {
		$quoted = preg_quote( $cap, '/' );

		return preg_replace( '/(?:' . $quoted . ')+$/u', '', $value ) . $cap;
	}

	/**
	 * Determine if a given string matches a given pattern.
	 *
	 * @param string|array $pattern
	 * @param string       $value
	 * @return bool
	 */
	public static function is( $pattern, $value ) {
		$patterns = Arr::wrap( $pattern );

		if ( empty( $patterns ) ) {
			return false;
		}

		foreach ( $patterns as $pattern ) {
			// If the given value is an exact match we can of course return true right
			// from the beginning. Otherwise, we will translate asterisks and do an
			// actual pattern match against the two strings to see if they match.
			if ( $pattern == $value ) {
				return true;
			}

			$pattern = preg_quote( $pattern, '#' );

			// Asterisks are translated into zero-or-more regular expression wildcards
			// to make it convenient to check if the strings starts with the given
			// pattern such as "library/*", making any string check convenient.
			$pattern = str_replace( '\*', '.*', $pattern );

			if ( preg_match( '#^' . $pattern . '\z#u', $value ) === 1 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if a given string is 7 bit ASCII.
	 *
	 * @param string $value
	 * @return bool
	 */
	public static function is_ascii( $value ) {
		return ASCII::is_ascii( (string) $value );
	}

	/**
	 * Convert a string to kebab case.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function kebab( $value ) {
		return static::snake( $value, '-' );
	}

	/**
	 * Convert a string to snake case.
	 *
	 * @param string $value
	 * @param string $delimiter
	 * @return string
	 */
	public static function snake( $value, $delimiter = '_' ) {
		$key = $value;

		if ( isset( static::$snake_cache[ $key ][ $delimiter ] ) ) {
			return static::$snake_cache[ $key ][ $delimiter ];
		}

		if ( ! ctype_lower( $value ) ) {
			$value = preg_replace( '/\s+/u', '', ucwords( $value ) );

			$value = static::lower( preg_replace( '/(.)(?=[A-Z])/u', '$1' . $delimiter, $value ) );
		}

		return static::$snake_cache[ $key ][ $delimiter ] = $value;
	}

	/**
	 * Convert the given string to lower-case.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function lower( $value ) {
		return mb_strtolower( $value, 'UTF-8' );
	}

	/**
	 * Limit the number of characters in a string.
	 *
	 * @param string $value
	 * @param int    $limit
	 * @param string $end
	 * @return string
	 */
	public static function limit( $value, $limit = 100, $end = '...' ) {
		if ( mb_strwidth( $value, 'UTF-8' ) <= $limit ) {
			return $value;
		}

		return rtrim( mb_strimwidth( $value, 0, $limit, '', 'UTF-8' ) ) . $end;
	}

	/**
	 * Limit the number of words in a string.
	 *
	 * @param string $value
	 * @param int    $words
	 * @param string $end
	 * @return string
	 */
	public static function words( $value, $words = 100, $end = '...' ) {
		preg_match( '/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches );

		if ( ! isset( $matches[0] ) || static::length( $value ) === static::length( $matches[0] ) ) {
			return $value;
		}

		return rtrim( $matches[0] ) . $end;
	}

	/**
	 * Return the length of the given string.
	 *
	 * @param string      $value
	 * @param string|null $encoding
	 * @return int
	 */
	public static function length( $value, $encoding = null ) {
		if ( $encoding ) {
			return mb_strlen( $value, $encoding );
		}

		return mb_strlen( $value );
	}

	/**
	 * Parse a Class[@]method style callback into class and method.
	 *
	 * @param string      $callback
	 * @param string|null $default
	 * @return array<int, string|null>
	 */
	public static function parse_callback( $callback, $default = null ) {
		return static::contains( $callback, '@' ) ? explode( '@', $callback, 2 ) : [
			$callback,
			$default,
		];
	}

	/**
	 * Generate a more truly "random" alpha-numeric string.
	 *
	 * @throws \Exception {@see random_bytes()}
	 *
	 * @param int $length
	 * @return string
	 */
	public static function random( $length = 16 ) {
		$string = '';

		while ( ( $len = strlen( $string ) ) < $length ) {
			$size = $length - $len;

			$bytes = random_bytes( $size );

			$string .= substr( str_replace( [
				'/',
				'+',
				'=',
			], '', base64_encode( $bytes ) ), 0, $size );
		}

		return $string;
	}

	/**
	 * Replace a given value in the string sequentially with an array.
	 *
	 * @param string                    $search
	 * @param array<int|string, string> $replace
	 * @param string                    $subject
	 * @return string
	 */
	public static function replace_array( $search, array $replace, $subject ) {
		$segments = explode( $search, $subject );

		$result = array_shift( $segments );

		foreach ( $segments as $segment ) {
			$result .= ( array_shift( $replace ) ?? $search ) . $segment;
		}

		return $result;
	}

	/**
	 * Replace the first occurrence of a given value in the string.
	 *
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 * @return string
	 */
	public static function replace_first( $search, $replace, $subject ) {
		if ( $search == '' ) {
			return $subject;
		}

		$position = strpos( $subject, $search );

		if ( $position !== false ) {
			return substr_replace( $subject, $replace, $position, strlen( $search ) );
		}

		return $subject;
	}

	/**
	 * Replace the last occurrence of a given value in the string.
	 *
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 * @return string
	 */
	public static function replace_last( $search, $replace, $subject ) {
		$position = strrpos( $subject, $search );

		if ( $position !== false ) {
			return substr_replace( $subject, $replace, $position, strlen( $search ) );
		}

		return $subject;
	}

	/**
	 * Begin a string with a single instance of a given value.
	 *
	 * @param string $value
	 * @param string $prefix
	 * @return string
	 */
	public static function start( $value, $prefix ) {
		$quoted = preg_quote( $prefix, '/' );

		return $prefix . preg_replace( '/^(?:' . $quoted . ')+/u', '', $value );
	}

	/**
	 * Convert the given string to title case.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function title( $value ) {
		return mb_convert_case( $value, MB_CASE_TITLE, 'UTF-8' );
	}

	/**
	 * Generate a URL friendly "slug" from a given string.
	 *
	 * @param string      $title
	 * @return string
	 */
	public static function slug( $title ) {
		return sanitize_title( $title );
	}

	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @param string          $haystack
	 * @param string|string[] $needles
	 * @return bool
	 */
	public static function starts_with( $haystack, $needles ) {
		foreach ( (array) $needles as $needle ) {
			if ( (string) $needle !== '' && strncmp( $haystack, $needle, strlen( $needle ) ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the number of substring occurrences.
	 *
	 * @param string   $haystack
	 * @param string   $needle
	 * @param int      $offset
	 * @param int|null $length
	 * @return int
	 */
	public static function substr_count( $haystack, $needle, $offset = 0, $length = null ) {
		if ( ! is_null( $length ) ) {
			return substr_count( $haystack, $needle, $offset, $length );
		} else {
			return substr_count( $haystack, $needle, $offset );
		}
	}

	/**
	 * Make a string's first character uppercase.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function ucfirst( $string ) {
		return static::upper( static::substr( $string, 0, 1 ) ) . static::substr( $string, 1 );
	}

	/**
	 * Convert the given string to upper-case.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function upper( $value ) {
		return mb_strtoupper( $value, 'UTF-8' );
	}
}
