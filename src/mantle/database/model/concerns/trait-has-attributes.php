<?php
/**
 * Has_Attributes trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Concerns;

use LogicException;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Relations\Relation;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\tap;

/**
 * Model Attributes
 *
 * @template TModel of \Mantle\Database\Model\Model
 */
trait Has_Attributes {
	use Has_Guarded_Attributes;
	use Hides_Attributes;

	/**
	 * Attributes for the model from the object
	 *
	 * @var array<string, mixed>
	 */
	protected array $attributes = [];

	/**
	 * Keep track of attributes that have been modified.
	 *
	 * @var array<string>
	 */
	protected array $modified_attributes = [];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected array $casts = [];

	/**
	 * The accessors to append to the model's array form.
	 *
	 * @var array<string>
	 */
	protected $appends = [];

	/**
	 * The built-in, primitive cast types supported by the model.
	 *
	 * @var array<string>
	 */
	protected static $supported_cast_types = [
		'array',
		'bool',
		'boolean',
		'double',
		'float',
		'int',
		'integer',
		'json',
		'object',
		'real',
		'string',
		'timestamp',
	];

	/**
	 * Get an attribute from the model.
	 *
	 * @param string $attribute Attribute name.
	 * @return mixed
	 */
	public function get_attribute( string $attribute ) {
		if ( isset( $this->attributes[ $attribute ] ) || $this->has_get_mutator( $attribute ) ) {
			$value = $this->attributes[ $attribute ] ?? null;

			if ( isset( $this->casts[ $attribute ] ) ) {
				$value = $this->get_casted_attribute_value( $value, $this->casts[ $attribute ] );
			}

			if ( $this->has_get_mutator( $attribute ) ) {
				$value = $this->mutate_attribute( $attribute, $value );
			}
		} elseif ( 'ID' !== $attribute ) {
			$value = $this->get_relation_value( $attribute );
		}

		return $value ?? null;
	}

	/**
	 * Retrieve a relationship value.
	 *
	 * @param string $key Relation name.
	 */
	public function get_relation_value( string $key ): mixed {
		if ( 'ID' === $key ) {
			return null;
		}

		if ( array_key_exists( $key, $this->relations ) ) {
			return $this->relations[ $key ];
		}

		if ( method_exists( $this, $key ) ) {
			return $this->get_relationship_from_method( $key );
		}

		return null;
	}

	/**
	 * Retrieve a relationship from a method.
	 *
	 * @param string $method Relationship method name.
	 *
	 * @throws LogicException Thrown if the relationship method is not an instance
	 *                        of Relation.
	 */
	protected function get_relationship_from_method( string $method ): mixed {
		$relation = $this->$method();

		if ( ! $relation instanceof Relation ) {
			throw new LogicException(
				sprintf(
					'%s::%s must return a relationship instance.',
					static::class,
					$method
				)
			);
		}

		return tap(
			$relation->get_results(),
			function ( $relation ) use ( $method ): void {
				$this->set_relation( $method, $relation );
			}
		);
	}

	/**
	 * Set a model attribute.
	 *
	 * @param string $attribute Attribute name.
	 * @param mixed  $value Value to set.
	 *
	 * @throws Model_Exception Thrown when trying to set 'id'.
	 */
	public function set_attribute( string $attribute, mixed $value ): mixed {
		if ( $this->is_guarded( $attribute ) ) {
			throw new Model_Exception( "Unable to set '{$attribute} on model." );
		}

		if ( $this->has_set_mutator( $attribute ) ) {
			return $this->mutate_set_attribute( $attribute, $value );
		}

		if ( $this->is_enum_castable( $attribute ) ) {
			$this->set_enum_castable( $attribute, $value );

			return $this;
		}

		if ( $this->has_attribute_cast( $attribute ) ) {
			$value = $this->get_storable_cast_value( $attribute, $value );
		}

		if ( $value instanceof \Stringable ) {
			$value = (string) $value;
		}

		$this->attributes[ $attribute ] = $value;

		$this->modified_attributes[] = $attribute;

		return $this;
	}

	/**
	 * Set a raw attribute on the model.
	 *
	 * @param string $attribute Attribute name.
	 * @param mixed  $value Value to set.
	 */
	public function set_raw_attribute( string $attribute, mixed $value ): static {
		$this->attributes[ $attribute ] = $value;

		return $this;
	}

	/**
	 * Get all model attributes.
	 *
	 * @return array<string, mixed>
	 */
	public function get_attributes(): array {
		$attributes = [];

		foreach ( $this->attributes as $key => $value ) {
			$attributes[ $key ] = $this->get_attribute( $key );
		}

		return $attributes;
	}

	/**
	 * Retrieve the attributes for insertion into the database.
	 *
	 * @return array<string, mixed>
	 */
	public function get_attributes_for_insert(): array {
		return $this->attributes;
	}

	/**
	 * Get an attribute array of all arrayable attributes.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_arrayable_attributes(): array {
		return $this->get_arrayable_items( $this->get_attributes() );
	}

	/**
	 * Convert the models' attributes to an array.
	 *
	 * @return array<string, mixed>
	 */
	public function attributes_to_array(): array {
		// Retrieve all attributes, passing them through the mutators.
		$attributes = collect( $this->get_arrayable_attributes() )
			->map(
				fn ( $value, string $attribute ) => $this->get_attribute( $attribute )
			)
			->merge( $this->get_arrayable_appends() );

		return $attributes->to_array();
	}

	/**
	 * Get an attribute array of all arrayable values.
	 *
	 * Filters out any hidden attribute that shouldn't appear and only includes
	 * visible attributes if set.
	 *
	 * @param string[] $values Values to check.
	 * @return array<string, mixed>
	 */
	protected function get_arrayable_items( array $values ): array {
		$visible = $this->get_visible();
		$hidden  = $this->get_hidden();

		if ( ! empty( $visible ) ) {
			$values = array_intersect_key( $values, array_flip( $visible ) );
		}

		if ( ! empty( $hidden ) ) {
			return array_diff_key( $values, array_flip( $hidden ) );
		}

		return $values;
	}

	/**
	 * Get the raw model attributes.
	 *
	 * @return array<string, mixed>
	 */
	public function get_raw_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Get all modified attributes.
	 *
	 * @return array<string, mixed>
	 */
	public function get_modified_attributes(): array {
		if ( empty( $this->modified_attributes ) ) {
			return [];
		}

		$attributes = [];
		foreach ( array_unique( $this->modified_attributes ) as $attribute ) {
			$attributes[ $attribute ] = $this->attributes[ $attribute ] ?? null;
		}

		return $attributes;
	}

	/**
	 * Check if an attribute has been modified.
	 *
	 * @param string $attribute Attribute to check.
	 */
	public function is_attribute_modified( string $attribute ): bool {
		return in_array( $attribute, $this->modified_attributes, true );
	}

	/**
	 * Set an array of attributes.
	 *
	 * @param array<string, mixed> $attributes Attributes to set.
	 */
	public function set_attributes( array $attributes ): static {
		foreach ( $attributes as $key => $value ) {
			$this->set( $key, $value );
		}

		return $this;
	}

	/**
	 * Set the raw attributes on the model.
	 *
	 * @param array<string, mixed> $attributes Raw attributes to set.
	 */
	public function set_raw_attributes( array $attributes ): static {
		$this->attributes = $attributes;
		return $this;
	}

	/**
	 * Reset the modified attributes.
	 */
	protected function reset_modified_attributes(): void {
		$this->modified_attributes = [];
	}

	/**
	 * Check if the attribute is castable to an enum.
	 *
	 * @param string $key Attribute key.
	 */
	protected function is_enum_castable( string $key ): bool {
		if ( ! array_key_exists( $key, $this->casts ) ) {
			return false;
		}

		$type = $this->casts[ $key ];

		if ( in_array( $type, static::$supported_cast_types, true ) ) {
			return false;
		}

		return enum_exists( $type );
	}

	/**
	 * Set the value of an attribute from a castable enumeration.
	 *
	 * @throws Model_Exception If the enum class does not exist or the value is invalid.
	 *
	 * @param string $key Attribute key.
	 * @param mixed  $value Value to set.
	 */
	protected function set_enum_castable( string $key, mixed $value ): void {
		$class = $this->casts[ $key ];

		if ( ! class_exists( $class ) ) {
			throw new Model_Exception(
				sprintf(
					'Enum class [%s] does not exist for attribute [%s].',
					$class,
					$key
				)
			);
		}

		if ( ! isset( $value ) ) {
			$this->attributes[ $key ] = null;
		} elseif ( is_object( $value ) ) {
			$this->attributes[ $key ] = $this->get_storable_enum_value( $class, $value );
		} else {
			$this->attributes[ $key ] = $this->get_storable_enum_value(
				$class,
				$this->get_enum_case_from_value( $class, $value )
			);
		}

		$this->modified_attributes[] = $key;
	}

	/**
	 * Retrieve the storable value for an enum attribute.
	 *
	 * @throws Model_Exception If the value is not of the expected enum type.
	 *
	 * @param string $expected Expected enum class.
	 * @phpstan-param class-string<\UnitEnum> $expected
	 * @param mixed  $value Value to check.
	 */
	protected function get_storable_enum_value( string $expected, mixed $value ): string|int {
		if ( ! $value instanceof $expected ) {
			throw new Model_Exception(
				sprintf(
					'Value [%s] is not of the expected enum type [%s]. Got %s.',
					$value,
					$expected,
					get_debug_type( $value ),
				)
			);
		}

		return match ( true ) {
			$value instanceof \BackedEnum => $value->value,
			$value instanceof \UnitEnum => $value->name, // @phpstan-ignore-line instanceof.alwaysTrue
			default => throw new Model_Exception(
				sprintf(
					'Value [%s] is not a valid enum type.',
					$value,
				)
			),
		};
	}

	/**
	 * Get the enum case from a value.
	 *
	 * @param string $enum Enum class.
	 * @param mixed  $value Value to check.
	 */
	protected function get_enum_case_from_value( string $enum, mixed $value ): \UnitEnum {
		if ( is_subclass_of( $enum, \BackedEnum::class ) ) {
			return $enum::from( $value );
		}

		return constant( $enum . '::' . $value );
	}

	/**
	 * Cast an attribute to a specific value.
	 *
	 * @todo Add date, collection cast types.
	 *
	 * @param mixed  $value Attribute value.
	 * @param string $cast_type Cast type.
	 */
	protected function get_casted_attribute_value( mixed $value, string $cast_type ): mixed {
		if ( in_array( $cast_type, static::$supported_cast_types, true ) ) {
			return match ( $cast_type ) {
				'int', 'integer' => (int) $value,
				'real', 'float', 'double' => $this->from_float( $value ),
				'string' => (string) $value,
				'bool', 'boolean' => (bool) $value,
				'object' => $this->from_json( $value, true ),
				'array', 'json' => $this->from_json( $value ),
				default => $value,
			};
		}

		if ( class_exists( $cast_type ) && is_subclass_of( $cast_type, \UnitEnum::class ) ) {
			return $this->get_enum_case_from_value( $cast_type, $value );
		}

		return $value;
	}

	/**
	 * Check if the attribute has a cast.
	 *
	 * @param string $attribute Attribute to check.
	 */
	protected function has_attribute_cast( string $attribute ): bool {
		return array_key_exists( $attribute, $this->casts );
	}

	/**
	 * Get the storable value for an attribute based on its cast type.
	 *
	 * @param string $attribute Attribute name.
	 * @param mixed  $value Value to convert.
	 */
	protected function get_storable_cast_value( string $attribute, mixed $value ): mixed {
		$cast_type = $this->casts[ $attribute ];

		if ( ! in_array( $cast_type, static::$supported_cast_types, true ) ) {
			return $value;
		}

		return match ( $cast_type ) {
			'int', 'integer' => (int) $value,
			'real', 'float', 'double' => (float) $value,
			'string' => (string) $value,
			'bool', 'boolean' => (bool) $value,
			'array', 'json' => $this->get_storable_array_cast_value( $value ),
			default => $value,
		};
	}

	/**
	 * Get the storable value for an array cast.
	 *
	 * @param mixed $value Value to convert.
	 */
	protected function get_storable_array_cast_value( mixed $value ): string {
		return match ( true ) {
			$value instanceof \JsonSerializable => $this->as_json( $value->jsonSerialize() ),
			$value instanceof \Stringable => (string) $value,
			is_array( $value ) => $this->as_json( $value ),
			default => (string) $value,
		};
	}

	/**
	 * Decode the given float.
	 *
	 * @param mixed $value Value to decode.
	 */
	public function from_float( mixed $value ): float {
		return match ( (string) $value ) {
			'Infinity' => INF,
			'-Infinity' => -INF,
			'NaN' => NAN,
			default => (float) $value,
		};
	}

	/**
	 * Encode the given value as JSON.
	 *
	 * @param mixed $value Value to encode.
	 */
	protected function as_json( mixed $value ): string {
		return \json_encode( $value, JSON_THROW_ON_ERROR ) ?: ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}

	/**
	 * Decode the given JSON back into an array or object.
	 *
	 * @param string $value Value to convert.
	 * @param bool   $as_object Flag as an object.
	 */
	public function from_json( string $value, bool $as_object = false ): mixed {
		return json_decode( $value, ! $as_object, 512, JSON_THROW_ON_ERROR );
	}

	/**
	 * Get the mutator method name for an attribute.
	 *
	 * @param string $attribute Attribute name.
	 */
	public function get_mutator_method_name( string $attribute ): string {
		return 'get_' . strtolower( $attribute ) . '_attribute';
	}

	/**
	 * Get the set mutator method name for an attribute.
	 *
	 * @param string $attribute Attribute name.
	 */
	public function get_set_mutator_method_name( string $attribute ): string {
		return 'set_' . strtolower( $attribute ) . '_attribute';
	}

	/**
	 * Check if the attribute has a get mutator.
	 *
	 * @param string $attribute Attribute to check.
	 */
	public function has_get_mutator( string $attribute ): bool {
		return method_exists( $this, $this->get_mutator_method_name( $attribute ) );
	}

	/**
	 * Check if the attribute has a set mutator.
	 *
	 * @param string $attribute Attribute to check.
	 */
	public function has_set_mutator( string $attribute ): bool {
		return method_exists( $this, $this->get_set_mutator_method_name( $attribute ) );
	}

	/**
	 * Pass an attribute through a get mutator.
	 *
	 * @param string $attribute Attribute to check.
	 * @param mixed  $value Attribute value.
	 */
	public function mutate_attribute( string $attribute, $value ): mixed {
		return $this->{ $this->get_mutator_method_name( $attribute ) }( $value );
	}

	/**
	 * Pass an attribute through a set mutator.
	 *
	 * @param string $attribute Attribute to check.
	 * @param mixed  $value Attribute value.
	 */
	public function mutate_set_attribute( string $attribute, $value ): mixed {
		return $this->{ $this->get_set_mutator_method_name( $attribute ) }( $value );
	}

	/**
	 * Set the accessors to append to model arrays.
	 *
	 * @param string|string[] ...$appends Accessors to append.
	 * @return static
	 */
	public function set_appends( ...$appends ) {
		$this->appends = $appends;
		return $this;
	}

	/**
	 * Check if an attribute is being appended.
	 *
	 * @param string $attribute Attribute to check.
	 */
	public function has_appended( string $attribute ): bool {
		return in_array( $attribute, $this->appends, true );
	}

	/**
	 * Append attributes to the model arrays.
	 *
	 * @param string ...$attributes Attributes to append.
	 */
	public function append( string ...$attributes ): static {
		$this->appends = array_unique(
			array_merge( $this->appends, $attributes )
		);

		return $this;
	}

	/**
	 * Retrieve all the appendable values in an array.
	 *
	 * @return array<string, mixed>
	 */
	public function get_arrayable_appends(): array {
		if ( empty( $this->appends ) ) {
			return [];
		}

		return $this->get_arrayable_items(
			collect( $this->appends )
				->combine(
					collect( $this->appends )->map(
						fn ( string $attribute ) => $this->get_attribute( $attribute )
					)
				)
				->to_array()
		);
	}
}
