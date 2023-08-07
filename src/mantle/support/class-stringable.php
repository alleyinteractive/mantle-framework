<?php
/**
 * Stringable class file
 *
 * @package Mantle
 */

namespace Mantle\Support;

use ArrayAccess;
use Closure;
use Carbon\Carbon as Date;
use Mantle\Support\Traits\Conditionable;
use Mantle\Support\Traits\Macroable;
use JsonSerializable;
use Mantle\Support\Traits\Tappable;
use Symfony\Component\VarDumper\VarDumper;

use function Mantle\Support\Helpers\collect;

/**
 * Stringable Class
 *
 * Allows for the chaining of string methods.
 */
class Stringable implements ArrayAccess, JsonSerializable, \Stringable {

	use Conditionable, Macroable, Tappable;

	/**
	 * The underlying string value.
	 *
	 * @var string
	 */
	protected string $value = '';

	/**
	 * Create a new instance of the class.
	 *
	 * @param  string $value
	 * @return void
	 */
	public function __construct( $value = '' ) {
		$this->value = (string) $value;
	}

	/**
	 * Return the remainder of a string after the first occurrence of a given value.
	 *
	 * @param  string $search
	 * @return static
	 */
	public function after( string $search ): static {
		return new static( Str::after( $this->value, $search ) );
	}

	/**
	 * Return the remainder of a string after the last occurrence of a given value.
	 *
	 * @param  string $search
	 * @return static
	 */
	public function after_last( string $search ): static {
		return new static( Str::after_last( $this->value, $search ) );
	}

	/**
	 * Append the given values to the string.
	 *
	 * @param  array<string>|string ...$values
	 * @return static
	 */
	public function append( ...$values ): static {
		return new static( $this->value . implode( '', $values ) ); // @phpstan-ignore-line implode expects array<string>
	}

	/**
	 * Append a new line to the string.
	 *
	 * @param  int $count
	 * @return static
	 */
	public function newLine( int $count = 1 ): static {
		return $this->append( str_repeat( PHP_EOL, $count ) );
	}

	/**
	 * Transliterate a UTF-8 value to ASCII.
	 *
	 * @param  string $language
	 * @return static
	 */
	public function ascii( string $language = 'en' ): static {
		return new static( Str::ascii( $this->value, $language ) );
	}

	/**
	 * Get the trailing name component of the path.
	 *
	 * @param  string $suffix
	 * @return static
	 */
	public function basename( string $suffix = '' ): static {
		return new static( basename( $this->value, $suffix ) );
	}

	/**
	 * Get the character at the specified index.
	 *
	 * @param  int $index
	 * @return string|false
	 */
	public function char_at( int $index ): string|false {
		return Str::char_at( $this->value, $index );
	}

	/**
	 * Get the basename of the class path.
	 *
	 * @return static
	 */
	public function class_basename(): static {
		return new static( class_basename( $this->value ) );
	}

	/**
	 * Get the portion of a string before the first occurrence of a given value.
	 *
	 * @param  string $search
	 * @return static
	 */
	public function before( string $search ): static {
		return new static( Str::before( $this->value, $search ) );
	}

	/**
	 * Get the portion of a string before the last occurrence of a given value.
	 *
	 * @param  string $search
	 * @return static
	 */
	public function before_last( $search ) {
		return new static( Str::before_last( $this->value, $search ) );
	}

	/**
	 * Get the portion of a string between two given values.
	 *
	 * @param  string $from
	 * @param  string $to
	 * @return static
	 */
	public function between( $from, $to ) {
		return new static( Str::between( $this->value, $from, $to ) );
	}

	/**
	 * Get the smallest possible portion of a string between two given values.
	 *
	 * @param  string $from
	 * @param  string $to
	 * @return static
	 */
	public function between_first( $from, $to ) {
		return new static( Str::between_first( $this->value, $from, $to ) );
	}

	/**
	 * Convert a value to camel case.
	 *
	 * @return static
	 */
	public function camel() {
		return new static( Str::camel( $this->value ) );
	}

	/**
	 * Determine if a given string contains a given substring.
	 *
	 * @param  string|iterable<string> $needles
	 * @param  bool                    $ignore_case
	 * @return bool
	 */
	public function contains( string|iterable $needles, bool $ignore_case = false ) {
		return Str::contains( $this->value, $needles, $ignore_case );
	}

	/**
	 * Determine if a given string contains all array values.
	 *
	 * @param  iterable<string> $needles
	 * @param  bool             $ignore_case
	 * @return bool
	 */
	public function contains_all( iterable $needles, bool $ignore_case = false ): bool {
		return Str::contains_all( $this->value, $needles, $ignore_case );
	}

	/**
	 * Get the parent directory's path.
	 *
	 * @param  int $levels
	 * @return static
	 */
	public function dirname( int $levels = 1 ): static {
		return new static( dirname( $this->value, $levels ) );
	}

	/**
	 * Determine if a given string ends with a given substring.
	 *
	 * @param  string|iterable<string> $needles
	 * @return bool
	 */
	public function ends_with( string|iterable $needles ): bool {
		return Str::ends_with( $this->value, $needles );
	}

	/**
	 * Determine if the string is an exact match with the given value.
	 *
	 * @param  mixed $value
	 * @return bool
	 */
	public function exactly( mixed $value ): bool {
		if ( $value instanceof \Stringable ) {
			$value = $value->__toString();
		}

		return $this->value === $value;
	}

	/**
	 * Extracts an excerpt from text that matches the first instance of a phrase.
	 *
	 * @param  string $phrase
	 * @param  array  $options
	 * @return string|null
	 */
	public function excerpt( string $phrase = '', array $options = [] ): ?string {
		return Str::excerpt( $this->value, $phrase, $options );
	}

	/**
	 * Explode the string into an array.
	 *
	 * @param  string $delimiter
	 * @param  int    $limit
	 * @return \Mantle\Support\Collection<int, string>
	 */
	public function explode( string $delimiter, int $limit = PHP_INT_MAX ): \Mantle\Support\Collection {
		return collect( explode( $delimiter, $this->value, $limit ) );
	}

	/**
	 * Split a string using a regular expression or by length.
	 *
	 * @param  string|int $pattern
	 * @param  int        $limit
	 * @param  int        $flags
	 * @return \Mantle\Support\Collection<int, string>
	 */
	public function split( string|int $pattern, int $limit = -1, int $flags = 0 ): \Mantle\Support\Collection {
		if ( filter_var( $pattern, FILTER_VALIDATE_INT ) !== false ) {
			return collect( mb_str_split( $this->value, $pattern ) );
		}

		$segments = preg_split( $pattern, $this->value, $limit, $flags );

		return ! empty( $segments ) ? collect( $segments ) : collect();
	}

	/**
	 * Cap a string with a single instance of a given value.
	 *
	 * @param  string $cap
	 * @return static
	 */
	public function finish( string $cap ): static {
		return new static( Str::finish( $this->value, $cap ) );
	}

	/**
	 * Determine if a given string matches a given pattern.
	 *
	 * @param  string|iterable<string> $pattern
	 * @return bool
	 */
	public function is( string|iterable $pattern ): bool {
		return Str::is( $pattern, $this->value );
	}

	/**
	 * Determine if a given string is 7 bit ASCII.
	 *
	 * @return bool
	 */
	public function is_ascii(): bool {
		return Str::is_ascii( $this->value );
	}

	/**
	 * Determine if a given string is valid JSON.
	 *
	 * @return bool
	 */
	public function is_json(): bool {
		return Str::is_json( $this->value );
	}

	/**
	 * Determine if a given string is a valid UUID.
	 *
	 * @return bool
	 */
	public function is_uuid(): bool {
		return Str::is_uuid( $this->value );
	}

	/**
	 * Determine if the given string is empty.
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return '' === $this->value;
	}

	/**
	 * Determine if the given string is not empty.
	 *
	 * @return bool
	 */
	public function is_not_empty(): bool {
		return ! $this->is_empty();
	}

	/**
	 * Convert a string to kebab case.
	 *
	 * @return static
	 */
	public function kebab(): static {
		return new static( Str::kebab( $this->value ) );
	}

	/**
	 * Return the length of the given string.
	 *
	 * @param  string|null $encoding
	 * @return int
	 */
	public function length( ?string $encoding = null ): int {
		return Str::length( $this->value, $encoding );
	}

	/**
	 * Limit the number of characters in a string.
	 *
	 * @param  int    $limit
	 * @param  string $end
	 * @return static
	 */
	public function limit( int $limit = 100, string $end = '...' ): static {
		return new static( Str::limit( $this->value, $limit, $end ) );
	}

	/**
	 * Convert the given string to lower-case.
	 *
	 * @return static
	 */
	public function lower(): static {
		return new static( Str::lower( $this->value ) );
	}

	/**
	 * Convert GitHub flavored Markdown into HTML.
	 *
	 * @param  array $options
	 * @return static
	 */
	public function markdown( array $options = [] ): static {
		return new static( Str::markdown( $this->value, $options ) );
	}

	/**
	 * Convert inline Markdown into HTML.
	 *
	 * @param  array $options
	 * @return static
	 */
	public function inline_markdown( array $options = [] ): static {
		return new static( Str::inline_markdown( $this->value, $options ) );
	}

	/**
	 * Masks a portion of a string with a repeated character.
	 *
	 * @param  string   $character
	 * @param  int      $index
	 * @param  int|null $length
	 * @param  string   $encoding
	 * @return static
	 */
	public function mask( string $character, int $index, ?int $length = null, string $encoding = 'UTF-8' ): static {
		return new static( Str::mask( $this->value, $character, $index, $length, $encoding ) );
	}

	/**
	 * Get the string matching the given pattern.
	 *
	 * @param  string $pattern
	 * @return static
	 */
	public function match( string $pattern ): static {
		return new static( Str::match( $pattern, $this->value ) );
	}

	/**
	 * Determine if a given string matches a given pattern.
	 *
	 * @param  string|iterable<string> $pattern
	 * @return bool
	 */
	public function is_match( string|iterable $pattern ): bool {
		return Str::is_match( $pattern, $this->value );
	}

	/**
	 * Get the string matching the given pattern.
	 *
	 * @param  string $pattern
	 * @return \Mantle\Support\Collection
	 */
	public function match_all( $pattern ): \Mantle\Support\Collection {
		return Str::match_all( $pattern, $this->value );
	}

	/**
	 * Determine if the string matches the given pattern.
	 *
	 * @param  string $pattern
	 * @return bool
	 */
	public function test( $pattern ): bool {
		return $this->is_match( $pattern );
	}

	/**
	 * Pad both sides of the string with another.
	 *
	 * @param  int    $length
	 * @param  string $pad
	 * @return static
	 */
	public function pad_both( $length, $pad = ' ' ): static {
		return new static( Str::pad_both( $this->value, $length, $pad ) );
	}

	/**
	 * Pad the left side of the string with another.
	 *
	 * @param  int    $length
	 * @param  string $pad
	 * @return static
	 */
	public function pad_left( $length, $pad = ' ' ): static {
		return new static( Str::pad_left( $this->value, $length, $pad ) );
	}

	/**
	 * Pad the right side of the string with another.
	 *
	 * @param  int    $length
	 * @param  string $pad
	 * @return static
	 */
	public function pad_right( $length, $pad = ' ' ): static {
		return new static( Str::pad_right( $this->value, $length, $pad ) );
	}

	/**
	 * Parse a Class@method style callback into class and method.
	 *
	 * @param  string|null $default
	 * @return array<int, string|null>
	 */
	public function parse_callback( $default = null ): array {
		return Str::parse_callback( $this->value, $default );
	}

	/**
	 * Call the given callback and return a new string.
	 *
	 * @param  callable $callback
	 * @return static
	 */
	public function pipe( callable $callback ): static {
		return new static( $callback( $this ) );
	}

	/**
	 * Get the plural form of an English word.
	 *
	 * @param  int|array|\Countable $count
	 * @return static
	 */
	public function plural( $count = 2 ): static {
		return new static( Str::plural( $this->value, $count ) );
	}

	/**
	 * Pluralize the last word of an English, studly caps case string.
	 *
	 * @param  int|array|\Countable $count
	 * @return static
	 */
	public function plural_studly( $count = 2 ): static {
		return new static( Str::plural_studly( $this->value, $count ) );
	}

	/**
	 * Prepend the given values to the string.
	 *
	 * @param  string ...$values
	 * @return static
	 */
	public function prepend( ...$values ): static {
		return new static( implode( '', $values ) . $this->value );
	}

	/**
	 * Remove any occurrence of the given string in the subject.
	 *
	 * @param  string|iterable<string> $search
	 * @param  bool                    $case_sensitive
	 * @return static
	 */
	public function remove( $search, bool $case_sensitive = true ) {
		return new static( Str::remove( $search, $this->value, $case_sensitive ) );
	}

	/**
	 * Reverse the string.
	 *
	 * @return static
	 */
	public function reverse() {
		return new static( Str::reverse( $this->value ) );
	}

	/**
	 * Repeat the string.
	 *
	 * @param  int $times
	 * @return static
	 */
	public function repeat( int $times ) {
		return new static( str_repeat( $this->value, $times ) );
	}
	/**
	 * Replace the given value in the given string.
	 *
	 * @param  string|iterable<string> $search
	 * @param  string|iterable<string> $replace
	 * @param  bool                    $case_sensitive
	 * @return static
	 */
	public function replace( $search, $replace, bool $case_sensitive = true ): static {
		return new static( Str::replace( $search, $replace, $this->value, $case_sensitive ) );
	}

	/**
	 * Replace a given value in the string sequentially with an array.
	 *
	 * @param  string           $search
	 * @param  iterable<string> $replace
	 * @return static
	 */
	public function replace_array( $search, $replace ): static {
		return new static( Str::replace_array( $search, $replace, $this->value ) );
	}

	/**
	 * Replace the first occurrence of a given value in the string.
	 *
	 * @param  string $search
	 * @param  string $replace
	 * @return static
	 */
	public function replace_first( $search, $replace ): static {
		return new static( Str::replace_first( $search, $replace, $this->value ) );
	}

	/**
	 * Replace the last occurrence of a given value in the string.
	 *
	 * @param  string $search
	 * @param  string $replace
	 * @return static
	 */
	public function replace_last( $search, $replace ): static {
		return new static( Str::replace_last( $search, $replace, $this->value ) );
	}

	/**
	 * Replace the patterns matching the given regular expression.
	 *
	 * @param  string          $pattern
	 * @param  \Closure|string $replace
	 * @param  int             $limit
	 * @return static
	 */
	public function replace_matches( $pattern, $replace, $limit = -1 ): static {
		if ( $replace instanceof Closure ) {
			return new static( preg_replace_callback( $pattern, $replace, $this->value, $limit ) );
		}

		return new static( preg_replace( $pattern, $replace, $this->value, $limit ) );
	}

	/**
	 * Parse input from a string to a collection, according to a format.
	 *
	 * @param  string $format
	 * @return \Mantle\Support\Collection
	 */
	public function scan( $format ): \Mantle\Support\Collection {
		return collect( sscanf( $this->value, $format ) );
	}

	/**
	 * Remove all "extra" blank space from the given string.
	 *
	 * @return static
	 */
	public function squish(): static {
		return new static( Str::squish( $this->value ) );
	}

	/**
	 * Begin a string with a single instance of a given value.
	 *
	 * @param  string $prefix
	 * @return static
	 */
	public function start( $prefix ): static {
		return new static( Str::start( $this->value, $prefix ) );
	}

	/**
	 * Strip HTML and PHP tags from the given string.
	 *
	 * @param  array|string $allowed_tags
	 * @return static
	 */
	public function strip_tags( array|string $allowed_tags = null ): static {
		return new static( strip_tags( $this->value, $allowed_tags ) ); // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsTwoParameters
	}

	/**
	 * Convert the given string to upper-case.
	 *
	 * @return static
	 */
	public function upper(): static {
		return new static( Str::upper( $this->value ) );
	}

	/**
	 * Convert the given string to title case.
	 *
	 * @return static
	 */
	public function title(): static {
		return new static( Str::title( $this->value ) );
	}

	/**
	 * Convert the given string to title case for each word.
	 *
	 * @return static
	 */
	public function headline(): static {
		return new static( Str::headline( $this->value ) );
	}

	/**
	 * Get the singular form of an English word.
	 *
	 * @return static
	 */
	public function singular(): static {
		return new static( Str::singular( $this->value ) );
	}

	/**
	 * Generate a URL friendly "slug" from a given string.
	 *
	 * @param  string                $separator
	 * @param  string|null           $language
	 * @param  array<string, string> $dictionary
	 * @return static
	 */
	public function slug( $separator = '-', $language = 'en', $dictionary = [ '@' => 'at' ] ): static {
		return new static( Str::slug( $this->value, $separator, $language, $dictionary ) );
	}

	/**
	 * Convert a string to snake case.
	 *
	 * @param  string $delimiter
	 * @return static
	 */
	public function snake( $delimiter = '_' ): static {
		return new static( Str::snake( $this->value, $delimiter ) );
	}

	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @param  string|iterable<string> $needles
	 * @return bool
	 */
	public function startsWith( $needles ): bool {
		return Str::starts_with( $this->value, $needles );
	}

	/**
	 * Convert a value to studly caps case.
	 *
	 * @return static
	 */
	public function studly(): static {
		return new static( Str::studly( $this->value ) );
	}

	/**
	 * Returns the portion of the string specified by the start and length parameters.
	 *
	 * @param  int      $start
	 * @param  int|null $length
	 * @param  string   $encoding
	 * @return static
	 */
	public function substr( $start, $length = null, $encoding = 'UTF-8' ): static {
		return new static( Str::substr( $this->value, $start, $length, $encoding ) );
	}

	/**
	 * Returns the number of substring occurrences.
	 *
	 * @param  string   $needle
	 * @param  int      $offset
	 * @param  int|null $length
	 * @return int
	 */
	public function substr_count( $needle, $offset = 0, $length = null ): int {
		return Str::substr_count( $this->value, $needle, $offset, $length );
	}

	/**
	 * Replace text within a portion of a string.
	 *
	 * @param  string|string[] $replace
	 * @param  int|int[]       $offset
	 * @param  int|int[]|null  $length
	 * @return static
	 */
	public function substr_replace( $replace, $offset = 0, $length = null ): static {
		return new static( Str::substr_replace( $this->value, $replace, $offset, $length ) );
	}

	/**
	 * Swap multiple keywords in a string with other keywords.
	 *
	 * @param  array $map
	 * @return static
	 */
	public function swap( array $map ): static {
		return new static( strtr( $this->value, $map ) );
	}

	/**
	 * Trim the string of the given characters.
	 *
	 * @param  string $characters
	 * @return static
	 */
	public function trim( ?string $characters = null ): static {
		return new static( trim( ...array_merge( [ $this->value ], func_get_args() ) ) );
	}

	/**
	 * Left trim the string of the given characters.
	 *
	 * @param  string $characters
	 * @return static
	 */
	public function ltrim( ?string $characters = null ): static {
		return new static( ltrim( ...array_merge( [ $this->value ], func_get_args() ) ) );
	}

	/**
	 * Right trim the string of the given characters.
	 *
	 * @param  string $characters
	 * @return static
	 */
	public function rtrim( ?string $characters = null ): static {
		return new static( rtrim( ...array_merge( [ $this->value ], func_get_args() ) ) );
	}

	/**
	 * Make a string's first character lowercase.
	 *
	 * @return static
	 */
	public function lcfirst(): static {
		return new static( Str::lcfirst( $this->value ) );
	}

	/**
	 * Make a string's first character uppercase.
	 *
	 * @return static
	 */
	public function ucfirst(): static {
		return new static( Str::ucfirst( $this->value ) );
	}

	/**
	 * Split a string by uppercase characters.
	 *
	 * @return \Mantle\Support\Collection<int, string>
	 */
	public function ucsplit(): \Mantle\Support\Collection {
		return collect( Str::ucsplit( $this->value ) );
	}

	/**
	 * Execute the given callback if the string contains a given substring.
	 *
	 * @template TReturnValue
	 *
	 * @param  string|iterable<string>                      $needles
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  callable|null                                $default
	 * @return static|TReturnValue
	 */
	public function when_contains( string|iterable $needles, callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->contains( $needles ), $callback, $default );
	}

	/**
	 * Execute the given callback if the string contains all array values.
	 *
	 * @template TReturnValue
	 *
	 * @param  array<string>                                $needles
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  callable|null                                $default
	 * @return static|TReturnValue
	 */
	public function when_contains_all( array $needles, callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->contains_all( $needles ), $callback, $default );
	}

	/**
	 * Execute the given callback if the string is empty.
	 *
	 * @template TReturnValue
	 *
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  mixed                                        $default
	 * @return static|TReturnValue
	 */
	public function when_empty( callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->is_empty(), $callback, $default );
	}

	/**
	 * Execute the given callback if the string is not empty.
	 *
	 * @template TReturnValue
	 *
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  mixed                                        $default
	 * @return static|TReturnValue
	 */
	public function when_not_empty( callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->is_not_empty(), $callback, $default );
	}

	/**
	 * Execute the given callback if the string ends with a given substring.
	 *
	 * @template TReturnValue
	 *
	 * @param  string|iterable<string>                      $needles
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  mixed|null                                   $default
	 * @return static|TReturnValue
	 */
	public function when_ends_with( string|iterable $needles, callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->ends_with( $needles ), $callback, $default );
	}

	/**
	 * Execute the given callback if the string is an exact match with the given value.
	 *
	 * @template TReturnValue
	 *
	 * @param  string                                       $value
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  mixed|null                                   $default
	 * @return static|TReturnValue
	 */
	public function when_exactly( string $value, callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->exactly( $value ), $callback, $default );
	}

	/**
	 * Execute the given callback if the string is not an exact match with the given value.
	 *
	 * @template TReturnValue
	 *
	 * @param  string                                       $value
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  mixed|null                                   $default
	 * @return static|TReturnValue
	 */
	public function when_not_exactly( string $value, callable $callback, mixed $default = null ): mixed {
		return $this->when( ! $this->exactly( $value ), $callback, $default );
	}

	/**
	 * Execute the given callback if the string matches a given pattern.
	 *
	 * @template TReturnValue
	 *
	 * @param  string|iterable<string>                      $pattern
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  mixed|null                                   $default
	 * @return static|TReturnValue
	 */
	public function when_is( string|iterable $pattern, callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->is( $pattern ), $callback, $default );
	}

	/**
	 * Execute the given callback if the string is 7 bit ASCII.
	 *
	 * @template TReturnValue
	 *
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  mixed|null                                   $default
	 * @return static|TReturnValue
	 */
	public function when_is_ascii( callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->is_ascii(), $callback, $default );
	}

	/**
	 * Execute the given callback if the string is a valid UUID.
	 *
	 * @template TReturnValue
	 *
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  mixed|null                                   $default
	 * @return static|TReturnValue
	 */
	public function when_is_uuid( callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->is_uuid(), $callback, $default );
	}

	/**
	 * Execute the given callback if the string starts with a given substring.
	 *
	 * @template TReturnValue
	 *
	 * @param  string|iterable<string>                      $needles
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  mixed|null                                   $default
	 * @return static|TReturnValue
	 */
	public function when_starts_with( string|iterable $needles, callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->startsWith( $needles ), $callback, $default );
	}

	/**
	 * Execute the given callback if the string matches the given pattern.
	 *
	 * @template TReturnValue
	 *
	 * @param  string                                       $pattern
	 * @param  (callable(\Stringable, mixed): TReturnValue) $callback
	 * @param  mixed|null                                   $default
	 * @return static|TReturnValue
	 */
	public function when_test( string $pattern, callable $callback, mixed $default = null ): mixed {
		return $this->when( $this->test( $pattern ), $callback, $default );
	}

	/**
	 * Limit the number of words in a string.
	 *
	 * @param  int    $words
	 * @param  string $end
	 * @return static
	 */
	public function words( int $words = 100, string $end = '...' ): static {
		return new static( Str::words( $this->value, $words, $end ) );
	}

	/**
	 * Get the number of words a string contains.
	 *
	 * @param  string|null $characters
	 * @return int
	 */
	public function word_count( ?string $characters = null ): int {
		return Str::word_count( $this->value, $characters );
	}

	/**
	 * Wrap the string with the given strings.
	 *
	 * @param  string      $before
	 * @param  string|null $after
	 * @return static
	 */
	public function wrap( string $before, ?string $after = null ): static {
		return new static( Str::wrap( $this->value, $before, $after ) );
	}

	/**
	 * Dump the string.
	 *
	 * @return $this
	 */
	public function dump(): static {
		VarDumper::dump( $this->value );

		return $this;
	}

	/**
	 * Dump the string and end the script.
	 *
	 * @return void
	 */
	public function dd(): void {
		$this->dump();

		exit( 1 );
	}

	/**
	 * Get the underlying string value.
	 *
	 * @return string
	 */
	public function value(): string {
		return $this->toString();
	}

	/**
	 * Get the underlying string value.
	 *
	 * @return string
	 */
	public function toString(): string {
		return $this->value;
	}

	/**
	 * Get the underlying string value as an integer.
	 *
	 * @return int
	 */
	public function to_integer(): int {
		return intval( $this->value );
	}

	/**
	 * Get the underlying string value as a float.
	 *
	 * @return float
	 */
	public function to_float(): float {
		return floatval( $this->value );
	}

	/**
	 * Get the underlying string value as a boolean.
	 *
	 * Returns true when value is "1", "true", "on", and "yes". Otherwise, returns false.
	 *
	 * @return bool
	 */
	public function to_boolean(): bool {
		return filter_var( $this->value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Get the underlying string value as a Carbon instance.
	 *
	 * @param  string|null $format
	 * @param  string|null $tz
	 * @return \Carbon\Carbon
	 */
	public function to_date( ?string $format = null, ?string $tz = null ): \Carbon\Carbon {
		if ( is_null( $format ) ) {
			return Date::parse( $this->value, $tz );
		}

		return Date::createFromFormat( $format, $this->value, $tz );
	}

	/**
	 * Convert the object to a string when JSON encoded.
	 *
	 * @return string
	 */
	public function jsonSerialize(): string {
		return $this->__toString();
	}

	/**
	 * Determine if the given offset exists.
	 *
	 * @param  mixed $offset
	 * @return bool
	 */
	public function offsetExists( mixed $offset ): bool {
		return isset( $this->value[ $offset ] );
	}

	/**
	 * Get the value at the given offset.
	 *
	 * @param  mixed $offset
	 * @return string
	 */
	public function offsetGet( mixed $offset ): string {
		return $this->value[ $offset ];
	}

	/**
	 * Set the value at the given offset.
	 *
	 * @param  mixed $offset
	 * @param  mixed $value
	 * @return void
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		$this->value[ $offset ] = $value;
	}

	/**
	 * Unset the value at the given offset.
	 *
	 * @param  mixed $offset
	 * @return void
	 */
	public function offsetUnset( mixed $offset ): void {
		unset( $this->value[ $offset ] );
	}

	/**
	 * Proxy dynamic properties onto methods.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->{$key}();
	}

	/**
	 * Get the raw string value.
	 *
	 * @return string
	 */
	public function __toString() {
		return (string) $this->value;
	}
}
