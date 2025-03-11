<?php
/**
 * Option class file
 *
 * @package mantle-framework
 */

namespace Mantle\Support;

use ArrayAccess;
use Carbon\Carbon;
use DateTimeZone;
use InvalidArgumentException;
use Mantle\Contracts\Support\Jsonable;
use Mantle\Support\Traits\Conditionable;
use Mantle\Support\Traits\Macroable;
use Mantle\Support\Traits\Tappable;

use function Mantle\Support\Helpers\data_get;
use function Mantle\Support\Helpers\data_set;
use function Mantle\Support\Helpers\value;

/**
 * Fluent class for retrieving options as type-safe objects.
 *
 * When retrieving options from the database, get_option() has a return value of
 * mixed. This class allows you to retrieve options with a specific type.
 */
class Option implements ArrayAccess, Jsonable, \JsonSerializable, \Stringable {
	use Conditionable;
	use Macroable;
	use Tappable;

	/**
	 * Retrieve an option from the database.
	 *
	 * @param string $option Option name.
	 * @param mixed  $default Default value. Default is null.
	 */
	public static function of( ?string $option, mixed $default = null ): static {
		return new static( $option, get_option( $option, $default ) );
	}

	/**
	 * Constructor
	 *
	 * @param string|null $option Option name.
	 * @param mixed       $value Option value.
	 * @param bool        $throw Whether to throw an exception if the option is not a compatible type.
	 */
	public function __construct(
		protected readonly ?string $option,
		protected mixed $value,
		protected bool $throw = false,
	) {}

	/**
	 * Retrieve the option as a string.
	 *
	 * @throws InvalidArgumentException If the option value is not scalar and $throw is true.
	 */
	public function string(): string {
		if ( ! is_scalar( $this->value ) && $this->throw ) {
			throw new InvalidArgumentException( "Option value of {$this->option} is not scalar and cannot be cast to a string." );
		}

		return (string) $this->value;
	}

	/**
	 * Retrieve the option as a Stringable object.
	 */
	public function stringable(): Stringable {
		return new Stringable( $this->string() );
	}

	/**
	 * Retrieve the option as an integer.
	 *
	 * @throws InvalidArgumentException If the option value is not numeric and $throw is true.
	 */
	public function int(): int {
		if ( ! is_numeric( $this->value ) && $this->throw ) {
			throw new InvalidArgumentException( "Option value of {$this->option} is not numeric and cannot be cast to an integer." );
		}

		return (int) $this->value;
	}

	/**
	 * Alias for int().
	 */
	public function integer(): int {
		return $this->int();
	}

	/**
	 * Retrieve the option as a float.
	 *
	 * @throws InvalidArgumentException If the option value is not numeric and $throw is true.
	 */
	public function float(): float {
		if ( ! is_numeric( $this->value ) && $this->throw ) {
			throw new InvalidArgumentException( "Option value of {$this->option} is not numeric and cannot be cast to a float." );
		}

		return (float) $this->value;
	}

	/**
	 * Retrieve the option as a boolean.
	 */
	public function bool(): bool {
		if ( is_bool( $this->value ) ) {
			return (bool) $this->value;
		}

		return ! empty( $this->value );
	}

	/**
	 * Retrieve the option as an array.
	 */
	public function array(): array {
		return (array) $this->value;
	}

	/**
	 * Retrieve the option as a collection.
	 */
	public function collection(): Collection {
		return new Collection( $this->value );
	}

	/**
	 * Alias for collection().
	 */
	public function collect(): Collection {
		return $this->collection();
	}

	/**
	 * Retrieve the option as a Carbon instance.
	 *
	 * @param string|null                  $format Date format.
	 * @param DateTimeZone|string|int|null $timezone Timezone.
	 */
	public function date( ?string $format = null, DateTimeZone|string|int|null $timezone = null ): ?Carbon {
		if ( $this->is_empty() ) {
			return null;
		}

		if ( $format ) {
			return Carbon::createFromFormat( $format, $this->string(), $timezone );
		}

		return Carbon::parse( $this->string(), $timezone );
	}

	/**
	 * Retrieve the option as an object.
	 */
	public function object(): object {
		return (object) $this->value;
	}

	/**
	 * Check if the option is empty.
	 */
	public function is_empty(): bool {
		return empty( $this->value );
	}

	/**
	 * Retrieve the raw value of the option.
	 */
	public function value(): mixed {
		return $this->value;
	}

	/**
	 * Dump the option value.
	 */
	public function dump(): static {
		dump( $this->value );

		return $this;
	}

	/**
	 * Dump the option value and exit.
	 */
	public function dd(): never {
		dd( $this->value );
	}

	/**
	 * Set whether to throw an exception if the option is not a compatible type.
	 *
	 * @param bool $throw Whether to throw an exception.
	 */
	public function throw( bool $throw = true ): static {
		$this->throw = $throw;

		return $this;
	}

	/**
	 * Set whether to throw an exception if the condition is met.
	 *
	 * @param (callable(): bool)|bool $condition Condition to check.
	 */
	public function throw_if( callable|bool $condition ): static {
		$this->throw = (bool) value( $condition );

		return $this;
	}

	/**
	 * Retrieve a property from an array option.
	 *
	 * @throws InvalidArgumentException If the option value is not an array and $throw is true.
	 *
	 * @param string $property Property name. Supports dot notation.
	 * @param mixed  $default Default value. Default is null.
	 */
	public function get( string $property, mixed $default = null ): static {
		if ( ( ! is_array( $this->value ) && ! is_object( $this->value ) ) && $this->throw ) {
			throw new InvalidArgumentException( "Option value of {$this->option} is not an array." );
		}

		return new static( null, data_get( $this->value, $property, $default ) );
	}

	/**
	 * Check if a property or a set of properties exists in the option's value.
	 *
	 * @param string ...$property Property name. Supports dot notation.
	 */
	public function has( string ...$property ): bool {
		foreach ( $property as $prop ) {
			if ( $this->get( $prop )->is_empty() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if any of the properties exist in the option's value.
	 *
	 * @param string ...$property Property name. Supports dot notation.
	 */
	public function has_any( string ...$property ): bool {
		foreach ( $property as $prop ) {
			if ( ! $this->get( $prop )->is_empty() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Set the option value.
	 *
	 * @throws InvalidArgumentException If the option is a sub-property of an option and the option name is not passed.
	 *
	 * @param mixed $value Option value.
	 */
	public function set( mixed $value ): static {
		if ( ! $this->option ) {
			throw new InvalidArgumentException( 'Unable to update option on a sub-property of an option.' );
		}

		update_option( $this->option, $value );

		$this->value = get_option( $this->option );

		return $this;
	}

	/**
	 * Delete the option.
	 *
	 * @throws InvalidArgumentException If the option is a sub-property of an option.
	 */
	public function delete(): void {
		if ( ! $this->option ) {
			throw new InvalidArgumentException( 'Unable to delete option on a sub-property of an option.' );
		}

		delete_option( $this->option );
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param int $options json_encode() options.
	 */
	public function to_json( $options = 0 ): string {
		return json_encode( $this->value, $options ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}

	/**
	 * Convert the object to its JSON representation.
	 */
	public function jsonSerialize(): mixed {
		return $this->value();
	}

	/**
	 * Convert the object to its string representation.
	 */
	public function __toString(): string {
		return $this->string();
	}

	/**
	 * Check if a property exists in a option's value.
	 *
	 * @param mixed $offset
	 */
	public function offsetExists( mixed $offset ): bool {
		return '__not_found__' !== $this->get( $offset, '__not_found__' )->value();
	}

	/**
	 * Retrieve an offset from the option's value.
	 *
	 * @param mixed $offset Option name.
	 */
	public function offsetGet( mixed $offset ): mixed {
		return data_get( $this->value, $offset );
	}

	/**
	 * Set an offset in the option's value.
	 *
	 * Note: This will update the option in the database.
	 *
	 * @param mixed $offset Option name.
	 * @param mixed $value Option value.
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		data_set( $this->value, $offset, $value );

		$this->set( $this->value );
	}

	/**
	 * Unset an offset in the option's value.
	 *
	 * Note: This will update the option in the database.
	 *
	 * @param mixed $offset Option name.
	 */
	public function offsetUnset( mixed $offset ): void {
		unset( $this->value[ $offset ] );

		$this->set( $this->value );
	}
}
