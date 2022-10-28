<?php
/**
 * This file contains the Str class
 *
 * @package Mantle
 */

namespace Mantle\Support;

use JsonException;
use Mantle\Support\Traits\Macroable;
use voku\helper\ASCII;

/**
 * String helpers.
 */
class Str {
	use Macroable;

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
	 * @param string $subject String to search.
	 * @param string $search  Value for which to search.
	 * @return string
	 */
	public static function after_last( $subject, $search ) {
		if ( '' === $search ) {
			return $subject;
		}

		$position = strrpos( $subject, (string) $search );

		if ( false === $position ) {
			return $subject;
		}

		return substr( $subject, $position + strlen( $search ) );
	}

	/**
	 * Transliterate a UTF-8 value to ASCII.
	 *
	 * @param string $value    String to transliterate to ASCII.
	 * @param string $language Language of the string.
	 * @return string
	 */
	public static function ascii( $value, $language = 'en' ) {
		return ASCII::to_ascii( (string) $value, $language );
	}

	/**
	 * Get the portion of a string before the first occurrence of a given value.
	 *
	 * @param string $subject String to search.
	 * @param string $search  Value for which to search.
	 * @return string
	 */
	public static function before( $subject, $search ) {
		return '' === $search ? $subject : explode( $search, $subject )[0];
	}

	/**
	 * Get the portion of a string between two given values.
	 *
	 * @param string $subject String to search.
	 * @param string $from    Value to slice from.
	 * @param string $to      Value to slice to.
	 * @return string
	 */
	public static function between( $subject, $from, $to ) {
		if ( '' === $from || '' === $to ) {
			return $subject;
		}

		return static::before_last( static::after( $subject, $from ), $to );
	}

	/**
	 * Get the portion of a string before the last occurrence of a given value.
	 *
	 * @param string $subject String to search.
	 * @param string $search  Value for which to search.
	 * @return string
	 */
	public static function before_last( $subject, $search ) {
		if ( '' === $search ) {
			return $subject;
		}

		$pos = mb_strrpos( $subject, $search );

		if ( false === $pos ) {
			return $subject;
		}

		return static::substr( $subject, 0, $pos );
	}

	/**
	 * Returns the portion of string specified by the start and length parameters.
	 *
	 * @param string   $string String from which to cut.
	 * @param int      $start  Start position.
	 * @param int|null $length Number of characters to extract.
	 * @return string
	 */
	public static function substr( $string, $start, $length = null ) {
		return mb_substr( $string, $start, $length, 'UTF-8' );
	}

	/**
	 * Return the remainder of a string after the first occurrence of a given
	 * value.
	 *
	 * @param string $subject String to search.
	 * @param string $search Value for which to search.
	 * @return string
	 */
	public static function after( $subject, $search ) {
		return '' === $search ? $subject : array_reverse( explode( $search, $subject, 2 ) )[0];
	}

	/**
	 * Convert a value to camel case.
	 *
	 * @param string $value Value to camelcase.
	 * @return string
	 */
	public static function camel( $value ) {
		if ( ! isset( static::$camel_cache[ $value ] ) ) {
			static::$camel_cache[ $value ] = lcfirst( static::studly( $value ) );
		}

		return static::$camel_cache[ $value ];
	}

	/**
	 * Convert a value to studly caps case.
	 *
	 * @param string $value Value to studly.
	 * @return string
	 */
	public static function studly( $value ) {
		$key = $value;

		if ( ! isset( static::$studly_cache[ $key ] ) ) {
			$value = ucwords( str_replace( [ '-', '_' ], ' ', $value ) );

			static::$studly_cache[ $key ] = str_replace( ' ', '', $value );
		}

		return static::$studly_cache[ $key ];
	}

	/**
	 * Convert a value to studly caps case while preserving spaces as underscores.
	 *
	 * @param string $value Value to studly.
	 * @return string
	 */
	public static function studly_underscore( $value ) {
		$value = ucwords( str_replace( [ '-', '_' ], ' ', $value ) );
		return str_replace( ' ', '_', $value );
	}

	/**
	 * Determine if a given string contains all array values.
	 *
	 * @param string   $haystack String to search.
	 * @param string[] $needles  Values for which to search.
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
	 * @param string          $haystack String to search.
	 * @param string|string[] $needles  Value(s) for which to search.
	 * @return bool
	 */
	public static function contains( $haystack, $needles ) {
		foreach ( (array) $needles as $needle ) {
			if ( '' !== $needle && mb_strpos( $haystack, $needle ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if a given string ends with a given substring.
	 *
	 * @param string          $haystack String to search.
	 * @param string|string[] $needles  Value(s) for which to search.
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
	 * @param string $value String to cap.
	 * @param string $cap   Value with which to cap.
	 * @return string
	 */
	public static function finish( $value, $cap ) {
		$quoted = preg_quote( $cap, '/' );

		return preg_replace( '/(?:' . $quoted . ')+$/u', '', $value ) . $cap;
	}

	/**
	 * Determine if a given string matches a given pattern.
	 *
	 * @param string|array $pattern Pattern(s) for which to search.
	 * @param string       $value   String to search.
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
	 * @param string $value String to check.
	 * @return bool
	 */
	public static function is_ascii( $value ) {
		return ASCII::is_ascii( (string) $value );
	}

	/**
	 * Determine if a given string is valid JSON.
	 *
	 * @param  mixed $value
	 * @return bool
	 */
	public static function is_json( $value ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}

		try {
			json_decode( $value, true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			unset( $e );
			return false;
		}

		return true;
	}

	/**
	 * Convert a string to kebab case.
	 *
	 * @param string $value String to kebab.
	 * @return string
	 */
	public static function kebab( $value ) {
		return static::snake( $value, '-' );
	}

	/**
	 * Convert a string to snake case.
	 *
	 * @param string $value     String to snake.
	 * @param string $delimiter Word delimiter.
	 * @return string
	 */
	public static function snake( $value, $delimiter = '_' ) {
		$key = $value;

		if ( ! isset( static::$snake_cache[ $key ][ $delimiter ] ) ) {
			if ( ! ctype_lower( $value ) ) {
				$value = preg_replace( '/\s+/u', '', ucwords( $value ) );

				$value = static::lower( preg_replace( '/(.)(?=[A-Z])/u', '$1' . $delimiter, $value ) );
			}

			static::$snake_cache[ $key ][ $delimiter ] = $value;
		}

		return static::$snake_cache[ $key ][ $delimiter ];
	}

	/**
	 * Convert the given string to lower-case.
	 *
	 * @param string $value String to lower.
	 * @return string
	 */
	public static function lower( $value ) {
		return mb_strtolower( $value, 'UTF-8' );
	}

	/**
	 * Limit the number of characters in a string.
	 *
	 * @param string $value String to limit.
	 * @param int    $limit Character limit.
	 * @param string $end   If the value is truncated, string to append.
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
	 * @param string $value String to limit.
	 * @param int    $words Number of words to which to limit.
	 * @param string $end   If the value is truncated, string to append.
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
	 * @param string      $value    String to measure.
	 * @param string|null $encoding Encoding to assume when counting.
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
	 * @param string      $callback Pseudo-syntax to parse.
	 * @param string|null $default  Default method to use if one isn't provided.
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
	 * @throws \Exception {@see random_bytes()}.
	 *
	 * @param int $length Length of random string to build.
	 * @return string
	 */
	public static function random( $length = 16 ) {
		$string = '';

		// phpcs:ignore
		while ( ( $len = strlen( $string ) ) < $length ) {
			$size = $length - $len;

			$bytes = random_bytes( $size );

			$string .= substr( str_replace( [ '/', '+', '=' ], '', base64_encode( $bytes ) ), 0, $size );
		}

		return $string;
	}

	/**
	 * Replace a given value in the string sequentially with an array.
	 *
	 * @param string                    $search  String for which to search.
	 * @param array<int|string, string> $replace Values with which to replce.
	 * @param string                    $subject String to replace within.
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
	 * Replace the given value in the given string.
	 *
	 * @param string|string[] $search
	 * @param string|string[] $replace
	 * @param string|string[] $subject
	 * @return string|string[]
	 */
	public static function replace( $search, $replace, $subject ) {
		return str_replace( $search, $replace, $subject );
	}

	/**
	 * Replace the first occurrence of a given value in the string.
	 *
	 * @param string $search  String for which to search.
	 * @param string $replace Value with which to replace.
	 * @param string $subject String in which to search/replace.
	 * @return string
	 */
	public static function replace_first( $search, $replace, $subject ) {
		if ( '' == $search ) {
			return $subject;
		}

		$position = strpos( $subject, $search );

		if ( false !== $position ) {
			return substr_replace( $subject, $replace, $position, strlen( $search ) );
		}

		return $subject;
	}

	/**
	 * Replace the last occurrence of a given value in the string.
	 *
	 * @param string $search  String for which to search.
	 * @param string $replace Value with which to replace.
	 * @param string $subject String in which to search/replace.
	 * @return string
	 */
	public static function replace_last( $search, $replace, $subject ) {
		if ( '' === $search ) {
			return $subject;
		}

		$position = strrpos( $subject, $search );

		if ( false !== $position ) {
			return substr_replace( $subject, $replace, $position, strlen( $search ) );
		}

		return $subject;
	}

	/**
	 * Begin a string with a single instance of a given value.
	 *
	 * @param string $value  String to which to start.
	 * @param string $prefix Prefix to prepend.
	 * @return string
	 */
	public static function start( $value, $prefix ) {
		$quoted = preg_quote( $prefix, '/' );

		return $prefix . preg_replace( '/^(?:' . $quoted . ')+/u', '', $value );
	}

	/**
	 * Convert the given string to title case.
	 *
	 * @param string $value String to titlecase.
	 * @return string
	 */
	public static function title( $value ) {
		return mb_convert_case( $value, MB_CASE_TITLE, 'UTF-8' );
	}

	/**
	 * Generate a URL friendly "slug" from a given string.
	 *
	 * @param string $title String to slugify.
	 * @param string $separator Separator to use.
	 * @param string $language Language to use.
	 * @return string
	 */
	public static function slug( $title, $separator = '-', $language = 'en' ) {
		$title = $language ? static::ascii( $title, $language ) : $title;

		// Convert all dashes/underscores into separator.
		$flip = '-' === $separator ? '_' : '-';

		$title = preg_replace( '![' . preg_quote( $flip, null ) . ']+!u', $separator, $title );

		// Replace @ with the word 'at'.
		$title = str_replace( '@', $separator . 'at' . $separator, $title );

		// Remove all characters that are not the separator, letters, numbers, or whitespace..
		$title = preg_replace( '![^' . preg_quote( $separator, null ) . '\pL\pN\s]+!u', '', static::lower( $title ) );

		// Replace all separator characters and whitespace by a single separator.
		$title = preg_replace( '![' . preg_quote( $separator, null ) . '\s]+!u', $separator, $title );

		return trim( $title, $separator );
	}

	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @param string          $haystack Search to search.
	 * @param string|string[] $needles  Values for which to search.
	 * @return bool
	 */
	public static function starts_with( $haystack, $needles ) {
		foreach ( (array) $needles as $needle ) {
			if ( '' !== (string) $needle && strncmp( $haystack, $needle, strlen( $needle ) ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the number of substring occurrences.
	 *
	 * @param string   $haystack String to search.
	 * @param string   $needle   Value for which to search.
	 * @param int      $offset   Offset to begin at.
	 * @param int|null $length   The maximum length after the specified offset to
	 *                           search for the substring. It outputs a warning if
	 *                           the offset plus the length is greater than the
	 *                           haystack length.
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
	 * @param string $string The string to modify.
	 * @return string
	 */
	public static function ucfirst( string $string ) {
		return static::upper( static::substr( $string, 0, 1 ) ) . static::substr( $string, 1 );
	}

	/**
	 * Split a string into pieces by uppercase characters.
	 *
	 * @param  string $string
	 * @return array
	 */
	public static function ucsplit( string $string ) {
			return preg_split( '/(?=\p{Lu})/u', $string, -1, PREG_SPLIT_NO_EMPTY );
	}

	/**
	 * Convert the given string to upper-case.
	 *
	 * @param string $value The string to modify.
	 * @return string
	 */
	public static function upper( $value ) {
		return mb_strtoupper( $value, 'UTF-8' );
	}

	/**
	 * Convert the given string to title case for each word.
	 *
	 * @param  string $value
	 * @return string
	 */
	public static function headline( string $value ): string {
		$parts = explode( ' ', $value );

		$parts = count( $parts ) > 1
			? array_map( [ static::class, 'title' ], $parts )
			: array_map( [ static::class, 'title' ], static::ucsplit( implode( '_', $parts ) ) );

		$collapsed = static::replace( [ '-', '_', ' ' ], '_', implode( '_', $parts ) );

		return implode( ' ', array_filter( explode( '_', $collapsed ) ) );
	}

	/**
	 * Get the line number for a match from a character position.
	 *
	 * Useful inside of a regex match to determine the line number of the
	 * matched pair.
	 *
	 * The character position can be retrieved when matching against a string
	 * by passing `PREG_OFFSET_CAPTURE` to `preg_match_all()` as a flag.
	 *
	 * @param string $contents Contents used to match against.
	 * @param int    $char_pos Character position.
	 * @return int
	 */
	public static function line_number( string $contents, int $char_pos ): int {
		[ $before ] = str_split( $contents, $char_pos );
		return strlen( $before ) - strlen( str_replace( PHP_EOL, '', $before ) ) + 1;
	}

	/**
	 * Add a trailing slash to a string.
	 *
	 * @param string $string String to trail.
	 * @return string
	 */
	public static function trailing_slash( string $string ): string {
		return rtrim( $string, '/' ) . '/';
	}

	/**
	 * Remove a trailing slash from a string.
	 *
	 * @param string $string String to untrail.
	 * @return string
	 */
	public static function untrailing_slash( string $string ): string {
		return rtrim( $string, '/' );
	}

	/**
	 * Add a preceding slash to a string.
	 *
	 * @param string $string String to proceed.
	 * @return string
	 */
	public static function preceding_slash( string $string ): string {
		return '/' . static::unpreceding_slash( $string );
	}

	/**
	 * Remove a preceding slash from a string.
	 *
	 * @param string $string String to proceed.
	 * @return string
	 */
	public static function unpreceding_slash( string $string ): string {
		return ltrim( $string, '/\\' );
	}
}
