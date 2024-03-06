<?php
/**
 * Str class file
 *
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 *
 * @package Mantle
 */

namespace Mantle\Support;

use JsonException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use League\CommonMark\MarkdownConverter;
use Mantle\Support\Traits\Macroable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;
use Traversable;
use voku\helper\ASCII;

use function Mantle\Support\Helpers\collect;

/**
 * String Support class
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
	 * The callback that should be used to generate random strings.
	 *
	 * @var callable|null
	 */
	protected static $random_string_factory;

	/**
	 * Get a new stringable object from the given string.
	 *
	 * @param  string $string
	 * @return \Mantle\Support\Stringable
	 */
	public static function of( $string ) {
		return new Stringable( $string );
	}

	/**
	 * Return the remainder of a string after the first occurrence of a given value.
	 *
	 * @param  string $subject
	 * @param  string $search
	 * @return string
	 */
	public static function after( $subject, $search ) {
		return $search === '' ? $subject : array_reverse( explode( $search, $subject, 2 ) )[0];
	}

	/**
	 * Return the remainder of a string after the last occurrence of a given value.
	 *
	 * @param  string $subject
	 * @param  string $search
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
	 * @param  string|null $value
	 * @param  string      $language
	 * @return string
	 */
	public static function ascii( ?string $value, string $language = 'en' ) {
		return ASCII::to_ascii( (string) $value, $language );
	}

	/**
	 * Transliterate a string to its closest ASCII representation.
	 *
	 * @param  string      $string
	 * @param  string|null $unknown
	 * @param  bool|null   $strict
	 * @return string
	 */
	public static function transliterate( $string, $unknown = '?', $strict = false ) {
		return ASCII::to_transliterate( $string, $unknown, $strict );
	}

	/**
	 * Get the portion of a string before the first occurrence of a given value.
	 *
	 * @param  string $subject
	 * @param  string $search
	 * @return string
	 */
	public static function before( $subject, $search ) {
		if ( '' === $search ) {
			return $subject;
		}

		$result = strstr( $subject, (string) $search, true );

		return $result === false ? $subject : $result;
	}

	/**
	 * Get the portion of a string before the last occurrence of a given value.
	 *
	 * @param  string $subject
	 * @param  string $search
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
	 * Get the portion of a string between two given values.
	 *
	 * @param  string $subject
	 * @param  string $from
	 * @param  string $to
	 * @return string
	 */
	public static function between( $subject, $from, $to ) {
		if ( $from === '' || $to === '' ) {
			return $subject;
		}

		return static::before_last( static::after( $subject, $from ), $to );
	}

	/**
	 * Get the smallest possible portion of a string between two given values.
	 *
	 * @param  string $subject
	 * @param  string $from
	 * @param  string $to
	 * @return string
	 */
	public static function between_first( $subject, $from, $to ) {
		if ( $from === '' || $to === '' ) {
			return $subject;
		}

		return static::before( static::after( $subject, $from ), $to );
	}

	/**
	 * Convert a value to camel case.
	 *
	 * @param  string $value
	 * @return string
	 */
	public static function camel( $value ) {
		if ( isset( static::$camel_cache[ $value ] ) ) {
			return static::$camel_cache[ $value ];
		}

		return static::$camel_cache[ $value ] = lcfirst( static::studly( $value ) );
	}

	/**
	 * Get the character at the specified index.
	 *
	 * @param  string $subject
	 * @param  int    $index
	 * @return string|false
	 */
	public static function char_at( $subject, $index ) {
		$length = mb_strlen( $subject );

		if ( $index < 0 ? $index < -$length : $index > $length - 1 ) {
			return false;
		}

		return mb_substr( $subject, $index, 1 );
	}

	/**
	 * Determine if a given string contains a given substring.
	 *
	 * @param  string                  $haystack
	 * @param  string|iterable<string> $needles
	 * @param  bool                    $ignore_case
	 * @return bool
	 */
	public static function contains( $haystack, $needles, $ignore_case = false ) {
		if ( $ignore_case ) {
			$haystack = mb_strtolower( $haystack );
		}

		if ( ! is_iterable( $needles ) ) {
			$needles = (array) $needles;
		}

		foreach ( $needles as $needle ) {
			if ( $ignore_case ) {
				$needle = mb_strtolower( $needle );
			}

			if ( $needle !== '' && str_contains( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if a given string contains all array values.
	 *
	 * @param  string           $haystack
	 * @param  iterable<string> $needles
	 * @param  bool             $ignore_case
	 * @return bool
	 */
	public static function contains_all( $haystack, $needles, bool $ignore_case = false ) {
		foreach ( $needles as $needle ) {
			if ( ! static::contains( $haystack, $needle, $ignore_case ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if a given string ends with a given substring.
	 *
	 * @param  string                  $haystack
	 * @param  string|iterable<string> $needles
	 * @return bool
	 */
	public static function ends_with( $haystack, $needles ) {
		if ( ! is_iterable( $needles ) ) {
			$needles = (array) $needles;
		}

		foreach ( $needles as $needle ) {
			if ( (string) $needle !== '' && str_ends_with( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extracts an excerpt from text that matches the first instance of a phrase.
	 *
	 * @param  string $text
	 * @param  string $phrase
	 * @param  array  $options
	 * @return string|null
	 */
	public static function excerpt( $text, $phrase = '', $options = [] ) {
		$radius   = $options['radius'] ?? 100;
		$omission = $options['omission'] ?? '...';

		preg_match( '/^(.*?)(' . preg_quote( (string) $phrase, null ) . ')(.*)$/iu', (string) $text, $matches );

		if ( empty( $matches ) ) {
			return null;
		}

		$start = ltrim( $matches[1] );

		$start = str( mb_substr( $start, max( mb_strlen( $start, 'UTF-8' ) - $radius, 0 ), $radius, 'UTF-8' ) )->ltrim()->unless(
			fn ( $start_with_radius ) => $start_with_radius->exactly( $start ),
			fn ( $start_with_radius ) => $start_with_radius->prepend( $omission ),
		);

		$end = rtrim( $matches[3] );

		$end = str( mb_substr( $end, 0, $radius, 'UTF-8' ) )->rtrim()->unless(
			fn ( $end_with_radius ) => $end_with_radius->exactly( $end ),
			fn ( $end_with_radius ) => $end_with_radius->append( $omission ),
		);

		return $start->append( $matches[2], $end )->toString();
	}

	/**
	 * Cap a string with a single instance of a given value.
	 *
	 * @param  string $value
	 * @param  string $cap
	 * @return string
	 */
	public static function finish( $value, $cap ) {
		$quoted = preg_quote( $cap, '/' );

		return preg_replace( '/(?:' . $quoted . ')+$/u', '', $value ) . $cap;
	}

	/**
	 * Wrap the string with the given strings.
	 *
	 * @param  string      $value
	 * @param  string      $before
	 * @param  string|null $after
	 * @return string
	 */
	public static function wrap( $value, $before, $after = null ) {
		return $before . $value . ( $after ??= $before );
	}

	/**
	 * Determine if a given string matches a given pattern.
	 *
	 * @param  string|iterable<string> $pattern
	 * @param  string                  $value
	 * @return bool
	 */
	public static function is( $pattern, $value ) {
		$value = (string) $value;

		if ( ! is_iterable( $pattern ) ) {
			$pattern = [ $pattern ];
		}

		foreach ( $pattern as $pattern ) {
			$pattern = (string) $pattern;

			// If the given value is an exact match we can of course return true right
			// from the beginning. Otherwise, we will translate asterisks and do an
			// actual pattern match against the two strings to see if they match.
			if ( $pattern === $value ) {
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
	 * @param  string $value
	 * @return bool
	 */
	public static function is_ascii( $value ) {
		return ASCII::is_ascii( (string) $value );
	}

	/**
	 * Determine if a given string is valid JSON.
	 *
	 * @param  string $value
	 * @return bool
	 */
	public static function is_json( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		try {
			json_decode( $value, true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine if a given string is a valid UUID.
	 *
	 * @param  string $value
	 * @return bool
	 */
	public static function is_uuid( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		return preg_match( '/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/iD', $value ) > 0;
	}

	/**
	 * Convert a string to kebab case.
	 *
	 * @param  string $value
	 * @return string
	 */
	public static function kebab( $value ) {
		return static::snake( $value, '-' );
	}

	/**
	 * Return the length of the given string.
	 *
	 * @param  string      $value
	 * @param  string|null $encoding
	 * @return int
	 */
	public static function length( $value, $encoding = null ) {
		return mb_strlen( $value, $encoding );
	}

	/**
	 * Limit the number of characters in a string.
	 *
	 * @param  string $value
	 * @param  int    $limit
	 * @param  string $end
	 * @return string
	 */
	public static function limit( $value, $limit = 100, $end = '...' ) {
		if ( mb_strwidth( $value, 'UTF-8' ) <= $limit ) {
			return $value;
		}

		return rtrim( mb_strimwidth( $value, 0, $limit, '', 'UTF-8' ) ) . $end;
	}

	/**
	 * Convert the given string to lower-case.
	 *
	 * @param  string $value
	 * @return string
	 */
	public static function lower( $value ) {
		return mb_strtolower( $value, 'UTF-8' );
	}

	/**
	 * Limit the number of words in a string.
	 *
	 * @param  string $value
	 * @param  int    $words
	 * @param  string $end
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
	 * Converts GitHub flavored Markdown into HTML.
	 *
	 * @param  string $string
	 * @param  array  $options
	 * @return string
	 */
	public static function markdown( $string, array $options = [] ) {
		$converter = new GithubFlavoredMarkdownConverter( $options );

		return (string) $converter->convert( $string );
	}

	/**
	 * Converts inline Markdown into HTML.
	 *
	 * @param  string $string
	 * @param  array  $options
	 * @return string
	 */
	public static function inline_markdown( $string, array $options = [] ) {
		$environment = new Environment( $options );

		$environment->addExtension( new GithubFlavoredMarkdownExtension() );
		$environment->addExtension( new InlinesOnlyExtension() );

		$converter = new MarkdownConverter( $environment );

		return (string) $converter->convert( $string );
	}

	/**
	 * Masks a portion of a string with a repeated character.
	 *
	 * @param  string   $string
	 * @param  string   $character
	 * @param  int      $index
	 * @param  int|null $length
	 * @param  string   $encoding
	 * @return string
	 */
	public static function mask( $string, $character, $index, $length = null, $encoding = 'UTF-8' ) {
		if ( '' === $character ) {
			return $string;
		}

		$segment = mb_substr( $string, $index, $length, $encoding );

		if ( '' === $segment ) {
			return $string;
		}

		$strlen      = mb_strlen( $string, $encoding );
		$start_index = $index;

		if ( $index < 0 ) {
			$start_index = $index < -$strlen ? 0 : $strlen + $index;
		}

		$start       = mb_substr( $string, 0, $start_index, $encoding );
		$segment_len = mb_strlen( $segment, $encoding );
		$end         = mb_substr( $string, $start_index + $segment_len );

		return $start . str_repeat( mb_substr( $character, 0, 1, $encoding ), $segment_len ) . $end;
	}

	/**
	 * Get the string matching the given pattern.
	 *
	 * @param  string $pattern
	 * @param  string $subject
	 * @return string
	 */
	public static function match( $pattern, $subject ) {
		preg_match( $pattern, $subject, $matches );

		if ( ! $matches ) {
			return '';
		}

		return $matches[1] ?? $matches[0];
	}

	/**
	 * Determine if a given string matches a given pattern.
	 *
	 * @param  string|iterable<string> $pattern
	 * @param  string                  $value
	 * @return bool
	 */
	public static function is_match( $pattern, $value ) {
		$value = (string) $value;

		if ( ! is_iterable( $pattern ) ) {
			$pattern = [ $pattern ];
		}

		foreach ( $pattern as $pattern ) {
			$pattern = (string) $pattern;

			if ( preg_match( $pattern, $value ) === 1 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the string matching the given pattern.
	 *
	 * @param  string $pattern
	 * @param  string $subject
	 * @return \Mantle\Support\Collection
	 */
	public static function match_all( $pattern, $subject ) {
		preg_match_all( $pattern, $subject, $matches );

		if ( empty( $matches[0] ) ) {
			return collect();
		}

		return collect( $matches[1] ?? $matches[0] );
	}

	/**
	 * Pad both sides of a string with another.
	 *
	 * @param  string $value
	 * @param  int    $length
	 * @param  string $pad
	 * @return string
	 */
	public static function pad_both( $value, $length, $pad = ' ' ) {
		$short       = max( 0, $length - mb_strlen( $value ) );
		$short_left  = (int) floor( $short / 2 );
		$short_right = (int) ceil( $short / 2 );

		return mb_substr( str_repeat( $pad, $short_left ), 0, $short_left ) .
			$value .
			mb_substr( str_repeat( $pad, $short_right ), 0, $short_right );
	}

	/**
	 * Pad the left side of a string with another.
	 *
	 * @param  string $value
	 * @param  int    $length
	 * @param  string $pad
	 * @return string
	 */
	public static function pad_left( $value, $length, $pad = ' ' ) {
		$short = max( 0, $length - mb_strlen( $value ) );

		return mb_substr( str_repeat( $pad, $short ), 0, $short ) . $value;
	}

	/**
	 * Pad the right side of a string with another.
	 *
	 * @param  string $value
	 * @param  int    $length
	 * @param  string $pad
	 * @return string
	 */
	public static function pad_right( $value, $length, $pad = ' ' ) {
		$short = max( 0, $length - mb_strlen( $value ) );

		return $value . mb_substr( str_repeat( $pad, $short ), 0, $short );
	}

	/**
	 * Parse a Class[@]method style callback into class and method.
	 *
	 * @param  string      $callback
	 * @param  string|null $default
	 * @return array<int, string|null>
	 */
	public static function parse_callback( $callback, $default = null ) {
		return static::contains( $callback, '@' ) ? explode( '@', $callback, 2 ) : [ $callback, $default ];
	}

	/**
	 * Get the plural form of an English word.
	 *
	 * @param  string               $value
	 * @param  int|array|\Countable $count
	 * @return string
	 */
	public static function plural( $value, $count = 2 ) {
		return Pluralizer::plural( $value, $count );
	}

	/**
	 * Pluralize the last word of an English, studly caps case string.
	 *
	 * @param  string               $value
	 * @param  int|array|\Countable $count
	 * @return string
	 */
	public static function plural_studly( $value, $count = 2 ) {
		$parts = preg_split( '/(.)(?=[A-Z])/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE );

		$last_word = array_pop( $parts );

		return implode( '', $parts ) . self::plural( $last_word, $count );
	}

	/**
	 * Generate a random, secure password.
	 *
	 * @param  int  $length
	 * @param  bool $letters
	 * @param  bool $numbers
	 * @param  bool $symbols
	 * @param  bool $spaces
	 * @return string
	 */
	public static function password( $length = 32, $letters = true, $numbers = true, $symbols = true, $spaces = false ) {
		return ( new Collection() )
				->when(
					$letters,
					fn ( $c ) => $c->merge(
						[
							'a',
							'b',
							'c',
							'd',
							'e',
							'f',
							'g',
							'h',
							'i',
							'j',
							'k',
							'l',
							'm',
							'n',
							'o',
							'p',
							'q',
							'r',
							's',
							't',
							'u',
							'v',
							'w',
							'x',
							'y',
							'z',
							'A',
							'B',
							'C',
							'D',
							'E',
							'F',
							'G',
							'H',
							'I',
							'J',
							'K',
							'L',
							'M',
							'N',
							'O',
							'P',
							'Q',
							'R',
							'S',
							'T',
							'U',
							'V',
							'W',
							'X',
							'Y',
							'Z',
						]
					)
				)
				->when(
					$numbers,
					fn ( $c) => $c->merge(
						[
							'0',
							'1',
							'2',
							'3',
							'4',
							'5',
							'6',
							'7',
							'8',
							'9',
						]
					)
				)
				->when(
					$symbols,
					fn ( $c) => $c->merge(
						[
							'~',
							'!',
							'#',
							'$',
							'%',
							'^',
							'&',
							'*',
							'(',
							')',
							'-',
							'_',
							'.',
							',',
							'<',
							'>',
							'?',
							'/',
							'\\',
							'{',
							'}',
							'[',
							']',
							'|',
							':',
							';',
						]
					)
				)
				->when( $spaces, fn ( $c) => $c->merge( [ ' ' ] ) )
				->pipe( fn ( $c) => Collection::times( $length, fn () => $c[ random_int( 0, $c->count() - 1 ) ] ) )
				->implode( '' );
	}

	/**
	 * Generate a more truly "random" alpha-numeric string.
	 *
	 * @param  int $length
	 * @return string
	 */
	public static function random( $length = 16 ) {
		return ( static::$random_string_factory ?? function ( $length ) {
			$string = '';

			while ( ( $len = strlen( $string ) ) < $length ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition, Squiz.PHP.DisallowSizeFunctionsInLoops.Found
				$size = $length - $len;

				$bytes_size = (int) ceil( $size / 3 ) * 3;

				$bytes = random_bytes( $bytes_size );

				$string .= substr( str_replace( [ '/', '+', '=' ], '', base64_encode( $bytes ) ), 0, $size );
			}

			return $string;
		} )( $length );
	}

	/**
	 * Set the callable that will be used to generate random strings.
	 *
	 * @param  callable|null $factory
	 */
	public static function create_random_strings_using( callable $factory = null ): void {
		static::$random_string_factory = $factory;
	}

	/**
	 * Set the sequence that will be used to generate random strings.
	 *
	 * @param  array         $sequence
	 * @param  callable|null $when_missing
	 */
	public static function create_random_strings_using_sequence( array $sequence, $when_missing = null ): void {
		$next = 0;

		$when_missing ??= function ( $length ) use ( &$next ) {
			$factory_cache = static::$random_string_factory;

			static::$random_string_factory = null;

			$random_string = static::random( $length );

			static::$random_string_factory = $factory_cache;

			$next++;

			return $random_string;
		};

		static::create_random_strings_using(
			function ( $length ) use ( &$next, $sequence, $when_missing ) {
				if ( array_key_exists( $next, $sequence ) ) {
					return $sequence[ $next++ ];
				}

				return $when_missing( $length );
			}
		);
	}

	/**
	 * Indicate that random strings should be created normally and not using a custom factory.
	 */
	public static function create_random_strings_normally(): void {
		static::$random_string_factory = null;
	}

	/**
	 * Repeat the given string.
	 *
	 * @param  string $string
	 * @param  int    $times
	 * @return string
	 */
	public static function repeat( string $string, int $times ) {
		return str_repeat( $string, $times );
	}

	/**
	 * Replace a given value in the string sequentially with an array.
	 *
	 * @param  string           $search
	 * @param  iterable<string> $replace
	 * @param  string           $subject
	 * @return string
	 */
	public static function replace_array( $search, $replace, $subject ) {
		if ( $replace instanceof Traversable ) {
			$replace = collect( $replace )->all();
		}

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
	 * @param  string|iterable<string> $search
	 * @param  string|iterable<string> $replace
	 * @param  string|iterable<string> $subject
	 * @param  bool                    $case_sensitive
	 * @return string
	 */
	public static function replace( $search, $replace, $subject, bool $case_sensitive = true ) {
		if ( $search instanceof Traversable ) {
			$search = collect( $search )->all();
		}

		if ( $replace instanceof Traversable ) {
			$replace = collect( $replace )->all();
		}

		if ( $subject instanceof Traversable ) {
			$subject = collect( $subject )->all();
		}

		return $case_sensitive
				? str_replace( $search, $replace, $subject )
				: str_ireplace( $search, $replace, $subject );
	}

	/**
	 * Replace the first occurrence of a given value in the string.
	 *
	 * @param  string $search
	 * @param  string $replace
	 * @param  string $subject
	 * @return string
	 */
	public static function replace_first( $search, $replace, $subject ) {
		$search = (string) $search;

		if ( '' === $search ) {
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
	 * @param  string $search
	 * @param  string $replace
	 * @param  string $subject
	 * @return string
	 */
	public static function replace_last( $search, $replace, $subject ) {
		if ( '' === $search ) {
			return $subject;
		}

		$position = strrpos( $subject, $search );

		if ( $position !== false ) {
			return substr_replace( $subject, $replace, $position, strlen( $search ) );
		}

		return $subject;
	}

	/**
	 * Remove any occurrence of the given string in the subject.
	 *
	 * @param  string|iterable<string> $search
	 * @param  string                  $subject
	 * @param  bool                    $case_sensitive
	 * @return string
	 */
	public static function remove( $search, $subject, bool $case_sensitive = true ) {
		if ( $search instanceof Traversable ) {
			$search = collect( $search )->all();
		}

		return $case_sensitive
					? str_replace( $search, '', $subject )
					: str_ireplace( $search, '', $subject );
	}

	/**
	 * Reverse the given string.
	 *
	 * @param  string $value
	 * @return string
	 */
	public static function reverse( string $value ) {
		return implode( array_reverse( mb_str_split( $value ) ) );
	}

	/**
	 * Begin a string with a single instance of a given value.
	 *
	 * @param  string $value
	 * @param  string $prefix
	 * @return string
	 */
	public static function start( $value, $prefix ) {
		$quoted = preg_quote( $prefix, '/' );

		return $prefix . preg_replace( '/^(?:' . $quoted . ')+/u', '', $value );
	}

	/**
	 * Convert the given string to upper-case.
	 *
	 * @param  string $value
	 * @return string
	 */
	public static function upper( $value ) {
		return mb_strtoupper( $value, 'UTF-8' );
	}

	/**
	 * Convert the given string to title case.
	 *
	 * @param  string $value
	 */
	public static function title( string $value ): string {
		return mb_convert_case( $value, MB_CASE_TITLE, 'UTF-8' );
	}

	/**
	 * Convert the given string to title case for each word.
	 *
	 * @param  string $value
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
	 * Get the singular form of an English word.
	 *
	 * @param  string $value
	 */
	public static function singular( string $value ): string {
		return Pluralizer::singular( $value );
	}

	/**
	 * Generate a URL friendly "slug" from a given string.
	 *
	 * @param  string|null           $title
	 * @param  string                $separator
	 * @param  string|null           $language
	 * @param  array<string, string> $dictionary
	 * @return string
	 */
	public static function slug( ?string $title, string $separator = '-', ?string $language = 'en', array $dictionary = [ '@' => 'at' ] ) {
		$title = $language ? static::ascii( $title, $language ) : $title;

		// Convert all dashes/underscores into separator.
		$flip = '-' === $separator ? '_' : '-';

		$title = preg_replace( '![' . preg_quote( $flip, null ) . ']+!u', $separator, $title );

		// Replace dictionary words.
		foreach ( $dictionary as $key => $value ) {
			$dictionary[ $key ] = $separator . $value . $separator;
		}

		$title = str_replace( array_keys( $dictionary ), array_values( $dictionary ), $title );

		// Remove all characters that are not the separator, letters, numbers, or whitespace.
		$title = preg_replace( '![^' . preg_quote( $separator, null ) . '\pL\pN\s]+!u', '', static::lower( $title ) );

		// Replace all separator characters and whitespace by a single separator.
		$title = preg_replace( '![' . preg_quote( $separator, null ) . '\s]+!u', $separator, $title );

		return trim( $title, $separator );
	}

	/**
	 * Convert a string to snake case.
	 *
	 * @param  string $value
	 * @param  string $delimiter
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
	 * Remove all "extra" blank space from the given string.
	 *
	 * @param  string $value
	 * @return string
	 */
	public static function squish( $value ) {
		return preg_replace( '~(\s|\x{3164}|\x{1160})+~u', ' ', preg_replace( '~^[\s\x{FEFF}]+|[\s\x{FEFF}]+$~u', '', $value ) );
	}

	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @param  string                  $haystack
	 * @param  string|iterable<string> $needles
	 * @return bool
	 */
	public static function starts_with( $haystack, $needles ) {
		if ( ! is_iterable( $needles ) ) {
			$needles = [ $needles ];
		}

		foreach ( $needles as $needle ) {
			if ( '' !== (string) $needle && str_starts_with( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Convert a value to studly caps case.
	 *
	 * @param  string $value
	 * @return string
	 */
	public static function studly( $value ) {
		$key = $value;

		if ( isset( static::$studly_cache[ $key ] ) ) {
			return static::$studly_cache[ $key ];
		}

		$words = explode( ' ', static::replace( [ '-', '_' ], ' ', $value ) );

		$study_words = array_map( fn ( $word) => static::ucfirst( $word ), $words );

		return static::$studly_cache[ $key ] = implode( $study_words );
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
	 * Returns the portion of the string specified by the start and length parameters.
	 *
	 * @param  string   $string
	 * @param  int      $start
	 * @param  int|null $length
	 * @param  string   $encoding
	 * @return string
	 */
	public static function substr( $string, $start, $length = null, $encoding = 'UTF-8' ) {
		return mb_substr( $string, $start, $length, $encoding );
	}

	/**
	 * Returns the number of substring occurrences.
	 *
	 * @param  string   $haystack
	 * @param  string   $needle
	 * @param  int      $offset
	 * @param  int|null $length
	 * @return int
	 */
	public static function substr_count( $haystack, $needle, $offset = 0, $length = null ) {
		if ( ! is_null( $length ) ) {
			return substr_count( $haystack, $needle, $offset, $length );
		}

		return substr_count( $haystack, $needle, $offset );
	}

	/**
	 * Replace text within a portion of a string.
	 *
	 * @param  string|string[] $string
	 * @param  string|string[] $replace
	 * @param  int|int[]       $offset
	 * @param  int|int[]|null  $length
	 * @return string|string[]
	 */
	public static function substr_replace( $string, $replace, $offset = 0, $length = null ) {
		if ( is_null( $length ) ) {
			$length = strlen( $string );
		}

		return substr_replace( $string, $replace, $offset, $length );
	}

	/**
	 * Swap multiple keywords in a string with other keywords.
	 *
	 * @param  array  $map
	 * @param  string $subject
	 * @return string
	 */
	public static function swap( array $map, $subject ) {
		return strtr( $subject, $map );
	}

	/**
	 * Make a string's first character lowercase.
	 *
	 * @param  string $string
	 * @return string
	 */
	public static function lcfirst( $string ) {
		return static::lower( static::substr( $string, 0, 1 ) ) . static::substr( $string, 1 );
	}

	/**
	 * Make a string's first character uppercase.
	 *
	 * @param  string $string
	 * @return string
	 */
	public static function ucfirst( $string ) {
		return static::upper( static::substr( $string, 0, 1 ) ) . static::substr( $string, 1 );
	}

	/**
	 * Split a string into pieces by uppercase characters.
	 *
	 * @param  string $string
	 * @return string[]
	 */
	public static function ucsplit( $string ) {
		return preg_split( '/(?=\p{Lu})/u', $string, -1, PREG_SPLIT_NO_EMPTY );
	}

	/**
	 * Get the number of words a string contains.
	 *
	 * @param  string      $string
	 * @param  string|null $characters
	 * @return int
	 */
	public static function word_count( $string, $characters = null ) {
		return str_word_count( $string, 0, $characters );
	}

	/**
	 * Generate a UUID (version 4).
	 */
	public static function uuid(): UuidInterface {
		return Uuid::uuid4();
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
	 */
	public static function line_number( string $contents, int $char_pos ): int {
		[ $before ] = str_split( $contents, $char_pos );
		return strlen( $before ) - strlen( str_replace( PHP_EOL, '', $before ) ) + 1;
	}

	/**
	 * Add a trailing slash to a string.
	 *
	 * @param string $string String to trail.
	 */
	public static function trailing_slash( string $string ): string {
		return rtrim( $string, '/' ) . '/';
	}

	/**
	 * Remove a trailing slash from a string.
	 *
	 * @param string $string String to untrail.
	 */
	public static function untrailing_slash( string $string ): string {
		return rtrim( $string, '/' );
	}

	/**
	 * Add a preceding slash to a string.
	 *
	 * @param string $string String to proceed.
	 */
	public static function preceding_slash( string $string ): string {
		return '/' . static::unpreceding_slash( $string );
	}

	/**
	 * Remove a preceding slash from a string.
	 *
	 * @param string $string String to proceed.
	 */
	public static function unpreceding_slash( string $string ): string {
		return ltrim( $string, '/\\' );
	}

	/**
	 * Remove all strings from the casing caches.
	 */
	public static function flush_cache(): void {
		static::$snake_cache  = [];
		static::$camel_cache  = [];
		static::$studly_cache = [];
	}
}
