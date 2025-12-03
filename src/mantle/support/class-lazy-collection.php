<?php
/**
 * Lazy_Collection class file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

use ArrayIterator;
use Carbon\Carbon;
use Closure;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use Mantle\Support\Traits\Enumerates_Values;
use Mantle\Support\Traits\Macroable;
use stdClass;
use Traversable;

/**
 * Lazy Collection.
 *
 * @template TKey of array-key
 * @template-covariant TValue
 *
 * @implements \Mantle\Support\Enumerable<TKey, TValue>
 */
class Lazy_Collection implements Enumerable {
	use Enumerates_Values;
	use Macroable;

	/**
	 * The source from which to generate items.
	 *
	 * @var (Closure(): \Generator<TKey, TValue, mixed, void>)|static|array<TKey, TValue>
	 */
	public $source;

	/**
	 * Create a new lazy collection instance.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>|(Closure(): \Generator<TKey, TValue, mixed, void>)|self<TKey, TValue>|array<TKey, TValue>|null $source
	 */
	public function __construct( $source = null ) {
		if ( $source instanceof Closure || $source instanceof self ) {
			$this->source = $source;
		} elseif ( is_null( $source ) ) {
			$this->source = static::empty();
		} elseif ( $source instanceof Generator ) {
			throw new InvalidArgumentException(
				'Generators should not be passed directly to LazyCollection. Instead, pass a generator function.'
			);
		} else {
			$this->source = $this->get_arrayable_items( $source );
		}
	}

	/**
	 * Create a new collection instance if the value isn't one already.
	 *
	 * @template TMakeKey of array-key
	 * @template TMakeValue
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TMakeKey, TMakeValue>|iterable<TMakeKey, TMakeValue>|(Closure(): \Generator<TMakeKey, TMakeValue, mixed, void>)|self<TMakeKey, TMakeValue>|array<TMakeKey, TMakeValue>|null $items
	 * @return static<TMakeKey, TMakeValue>
	 */
	public static function make( $items = [] ): static {
		return new static( $items );
	}

	/**
	 * Create a collection with the given range.
	 *
	 * @param  int $from
	 * @param  int $to
	 * @param  int $step
	 * @return static<int, int>
	 */
	public static function range( $from, $to, $step = 1 ): static {
		if ( $step === 0 ) {
			throw new InvalidArgumentException( 'Step value cannot be zero.' );
		}

		return new static( function () use ( $from, $to, $step ) {
			if ( $from <= $to ) {
				for ( ; $from <= $to; $from += abs( $step ) ) {
					yield $from;
				}
			} else {
				for ( ; $from >= $to; $from -= abs( $step ) ) {
					yield $from;
				}
			}
		} );
	}

	/**
	 * Get all items in the enumerable.
	 *
	 * @return array<TKey, TValue>
	 */
	public function all(): array {
		if ( is_array( $this->source ) ) {
			return $this->source;
		}

		return iterator_to_array( $this->getIterator() );
	}

	/**
	 * Eager load all items into a new lazy collection backed by an array.
	 *
	 * @return static<TKey, TValue>
	 */
	public function eager(): static {
		return new static( $this->all() );
	}

	/**
	 * Cache values as they're enumerated.
	 *
	 * @return static<TKey, TValue>
	 */
	public function remember(): static {
		$iterator = $this->getIterator();

		$iterator_index = 0;

		$cache = [];

		return new static( function () use ( $iterator, &$iterator_index, &$cache ) {
			for ( $index = 0; true; $index++ ) {
				if ( array_key_exists( $index, $cache ) ) {
					yield $cache[ $index ][0] => $cache[ $index ][1];

					continue;
				}

				if ( $iterator_index < $index ) {
					$iterator->next();

					$iterator_index++;
				}

				if ( ! $iterator->valid() ) {
					break;
				}

				$cache[ $index ] = [ $iterator->key(), $iterator->current() ];

				yield $cache[ $index ][0] => $cache[ $index ][1];
			}
		} );
	}

	/**
	 * Get the median of a given key.
	 *
	 * @param  string|array<array-key, string>|null $key
	 * @return float|int|null
	 */
	public function median( $key = null ) {
		return $this->collect()->median( $key );
	}

	/**
	 * Get the mode of a given key.
	 *
	 * @param  string|array<string>|null $key
	 * @return array<int, float|int>|null
	 */
	public function mode( $key = null ) {
		return $this->collect()->mode( $key );
	}

	/**
	 * Collapse the collection of items into a single array.
	 *
	 * @return static<int, mixed>
	 */
	public function collapse(): static {
		return new static( function () {
			foreach ( $this as $values ) {
				if ( is_array( $values ) || $values instanceof Enumerable ) {
					foreach ( $values as $value ) {
						yield $value;
					}
				}
			}
		} );
	}

	/**
	 * Collapse the collection of items into a single array while preserving its keys.
	 *
	 * @return static<mixed, mixed>
	 */
	public function collapse_with_keys(): static {
		return new static( function () {
			foreach ( $this as $values ) {
				if ( is_array( $values ) || $values instanceof Enumerable ) {
					foreach ( $values as $key => $value ) {
						yield $key => $value;
					}
				}
			}
		} );
	}

	/**
	 * Determine if an item exists in the enumerable.
	 *
	 * @param  (callable(TValue, TKey): bool)|TValue|string $key
	 * @param  mixed                                        $operator
	 * @param  mixed                                        $value
	 * @return bool
	 */
	public function contains( $key, $operator = null, $value = null ) {
		if ( func_num_args() === 1 && $this->use_as_callable( $key ) ) {
			$placeholder = new stdClass();

			/** @var callable $key */
			return $this->first( $key, $placeholder ) !== $placeholder;
		}

		if ( func_num_args() === 1 ) {
			$needle = $key;

			foreach ( $this as $value ) {
				if ( $value === $needle ) {
					return true;
				}
			}

			return false;
		}

		return $this->contains( $this->operator_for_where( ...func_get_args() ) );
	}

	/**
	 * Determine if an item exists, using strict comparison.
	 *
	 * @param  (callable(TValue): bool)|TValue|array-key $key
	 * @param  TValue|null                               $value
	 * @return bool
	 */
	public function contains_strict( $key, $value = null ) {
		if ( func_num_args() === 2 ) {
			return $this->contains( fn ( $item ) => data_get( $item, $key ) === $value );
		}

		if ( $this->use_as_callable( $key ) ) {
			return ! is_null( $this->first( $key ) );
		}

		foreach ( $this as $item ) {
			if ( $item === $key ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if an item is not contained in the enumerable.
	 *
	 * @param  mixed $key
	 * @param  mixed $operator
	 * @param  mixed $value
	 */
	public function doesnt_contain( $key, $operator = null, $value = null ): bool {
		return ! $this->contains( ...func_get_args() );
	}

	/**
	 * Determine if an item is not contained in the enumerable, using strict comparison.
	 *
	 * @param  mixed $key
	 * @param  mixed $operator
	 * @param  mixed $value
	 */
	public function doesnt_contain_strict( $key, $operator = null, $value = null ): bool {
		return ! $this->contains_strict( ...func_get_args() );
	}

	/**
	 * Cross join the given iterables, returning all possible permutations.
	 *
	 * @template TCrossJoinKey
	 * @template TCrossJoinValue
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TCrossJoinKey, TCrossJoinValue>|iterable<TCrossJoinKey, TCrossJoinValue> ...$arrays
	 * @return static<int, array<int, TValue|TCrossJoinValue>>
	 */
	public function cross_join( ...$arrays ): static {
		return $this->passthru( 'cross_join', func_get_args() );
	}

	/**
	 * Count the number of items in the collection by a field or using a callback.
	 *
	 * @param  (callable(TValue, TKey): array-key|\UnitEnum)|string|null $count_by
	 * @return static<array-key, int>
	 */
	public function count_by( $count_by = null ): static {
		$count_by = is_null( $count_by )
			? $this->identity()
			: $this->value_retriever( $count_by );

		return new static( function () use ( $count_by ) {
			$counts = [];

			foreach ( $this as $key => $value ) {
				$group = enum_value( $count_by( $value, $key ) );

				if ( empty( $counts[ $group ] ) ) {
					$counts[ $group ] = 0;
				}

				$counts[ $group ]++;
			}

			yield from $counts;
		} );
	}

	/**
	 * Get the items that are not present in the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue> $items
	 * @return static<TKey, TValue>
	 */
	public function diff( $items ): static {
		return $this->passthru( 'diff', func_get_args() );
	}

	/**
	 * Get the items that are not present in the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue> $items
	 * @param  callable(TValue, TValue): int                                                      $callback
	 */
	public function diff_using( $items, callable $callback ): static {
		return $this->passthru( 'diff_using', func_get_args() );
	}

	/**
	 * Get the items whose keys and values are not present in the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 */
	public function diff_assoc( $items ): static {
		return $this->passthru( 'diff_assoc', func_get_args() );
	}

	/**
	 * Get the items whose keys and values are not present in the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 * @param  callable(TKey, TKey): int                                                $callback
	 */
	public function diff_assoc_using( $items, callable $callback ): static {
		return $this->passthru( 'diff_assoc_using', func_get_args() );
	}

	/**
	 * Get the items whose keys are not present in the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, mixed>|iterable<TKey, mixed> $items
	 */
	public function diff_keys( $items ): static {
		return $this->passthru( 'diff_keys', func_get_args() );
	}

	/**
	 * Get the items whose keys are not present in the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, mixed>|iterable<TKey, mixed> $items
	 * @param  callable(TKey, TKey): int                                              $callback
	 */
	public function diff_keys_using( $items, callable $callback ): static {
		return $this->passthru( 'diff_keys_using', func_get_args() );
	}

	/**
	 * Retrieve duplicate items.
	 *
	 * @template TMapValue
	 *
	 * @param  (callable(TValue): TMapValue)|string|null $callback
	 * @param  bool                                      $strict
	 */
	public function duplicates( $callback = null, $strict = false ): static {
		return $this->passthru( 'duplicates', func_get_args() );
	}

	/**
	 * Retrieve duplicate items using strict comparison.
	 *
	 * @template TMapValue
	 *
	 * @param  (callable(TValue): TMapValue)|string|null $callback
	 */
	public function duplicates_strict( $callback = null ): static {
		return $this->passthru( 'duplicates_strict', func_get_args() );
	}

	/**
	 * Get all items except for those with the specified keys.
	 *
	 * @param  \Mantle\Support\Enumerable<array-key, TKey>|array<array-key, TKey> $keys
	 */
	public function except( $keys ): static {
		return $this->passthru( 'except', func_get_args() );
	}

	/**
	 * Run a filter over each of the items.
	 *
	 * @param  (callable(TValue, TKey): bool)|null $callback
	 */
	public function filter( ?callable $callback = null ): static {
		if ( is_null( $callback ) ) {
			$callback = fn ( $value ) => (bool) $value;
		}

		return new static( function () use ( $callback ) {
			foreach ( $this as $key => $value ) {
				if ( $callback( $value, $key ) ) {
					yield $key => $value;
				}
			}
		} );
	}

	/**
	 * Get the first item from the enumerable passing the given truth test.
	 *
	 * @template TFirstDefault
	 *
	 * @param  (callable(TValue): bool)|null             $callback
	 * @param  TFirstDefault|(\Closure(): TFirstDefault) $default
	 * @return TValue|TFirstDefault
	 */
	public function first( ?callable $callback = null, $default = null ) {
		$iterator = $this->getIterator();

		if ( is_null( $callback ) ) {
			if ( ! $iterator->valid() ) {
				return value( $default );
			}

			return $iterator->current();
		}

		foreach ( $iterator as $key => $value ) {
			if ( $callback( $value, $key ) ) {
				return $value;
			}
		}

		return value( $default );
	}

	/**
	 * Get a flattened list of the items in the collection.
	 *
	 * @param  int $depth
	 * @return static<int, mixed>
	 */
	public function flatten( $depth = INF ): static {
		$instance = new static( function () use ( $depth ) {
			foreach ( $this as $item ) {
				if ( ! is_array( $item ) && ! $item instanceof Enumerable ) {
					yield $item;
				} elseif ( $depth === 1 ) {
					yield from $item;
				} else {
					yield from (new static( $item ))->flatten( $depth - 1 );
				}
			}
		} );

		return $instance->values();
	}

	/**
	 * Flip the items in the collection.
	 *
	 * @return static<TValue, TKey>
	 */
	public function flip(): static {
		return new static( function () {
			foreach ( $this as $key => $value ) {
				yield $value => $key;
			}
		} );
	}

	/**
	 * Get an item by key.
	 *
	 * @template TGetDefault
	 *
	 * @param  TKey|null                             $key
	 * @param  TGetDefault|(\Closure(): TGetDefault) $default
	 * @return TValue|TGetDefault
	 */
	public function get( $key, $default = null ) {
		if ( is_null( $key ) ) {
			return;
		}

		foreach ( $this as $outerKey => $outerValue ) {
			if ( $outerKey === $key ) {
				return $outerValue;
			}
		}

		return value( $default );
	}

	/**
	 * Group an associative array by a field or using a callback.
	 *
	 * @template TGroupKey of array-key
	 *
	 * @param  (callable(TValue, TKey): TGroupKey)|array|string $group_by
	 * @param  bool                                             $preserve_keys
	 * @return static<($group_by is string ? array-key : ($group_by is array ? array-key : TGroupKey)), static<($preserve_keys is true ? TKey : int), ($group_by is array ? mixed : TValue)>>
	 */
	public function group_by( $group_by, $preserve_keys = false ): static {
		return $this->passthru( 'group_by', func_get_args() );
	}

	/**
	 * Key an associative array by a field or using a callback.
	 *
	 * @template TNewKey of array-key
	 *
	 * @param  (callable(TValue, TKey): TNewKey)|array|string $key_by
	 * @return static<($key_by is string ? array-key : ($key_by is array ? array-key : TNewKey)), TValue>
	 */
	public function key_by( $key_by ): static {
		return new static( function () use ( $key_by ) {
			$key_by = $this->value_retriever( $key_by );

			foreach ( $this as $key => $item ) {
				$resolved_key = $key_by( $item, $key );

				if ( is_object( $resolved_key ) ) {
					$resolved_key = (string) $resolved_key;
				}

				yield $resolved_key => $item;
			}
		} );
	}

	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param  mixed $key
	 */
	public function has( $key ): bool {
		$keys  = array_flip( is_array( $key ) ? $key : func_get_args() );
		$count = count( $keys );

		foreach ( $this as $key => $value ) {
			if ( array_key_exists( $key, $keys ) && --$count === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if any of the keys exist in the collection.
	 *
	 * @param  mixed $key
	 */
	public function has_any( $key ): bool {
		$keys = array_flip( is_array( $key ) ? $key : func_get_args() );

		foreach ( $this as $key => $value ) {
			if ( array_key_exists( $key, $keys ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Concatenate values of a given key as a string.
	 *
	 * @param  (callable(TValue, TKey): mixed)|string $value
	 * @param  string|null                            $glue
	 */
	public function implode( $value, $glue = null ): string {
		return $this->collect()->implode( ...func_get_args() );
	}

	/**
	 * Intersect the collection with the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 */
	public function intersect( $items ): static {
		return $this->passthru( 'intersect', func_get_args() );
	}

	/**
	 * Intersect the collection with the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue> $items
	 * @param  callable(TValue, TValue): int                                                      $callback
	 */
	public function intersect_using( $items, callable $callback ): static {
		return $this->passthru( 'intersect_using', func_get_args() );
	}

	/**
	 * Intersect the collection with the given items with additional index check.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 */
	public function intersect_assoc( $items ): static {
		return $this->passthru( 'intersect_assoc', func_get_args() );
	}

	/**
	 * Intersect the collection with the given items with additional index check, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue> $items
	 * @param  callable(TValue, TValue): int                                                      $callback
	 */
	public function intersect_assoc_using( $items, callable $callback ): static {
		return $this->passthru( 'intersect_assoc_using', func_get_args() );
	}

	/**
	 * Intersect the collection with the given items by key.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, mixed>|iterable<TKey, mixed> $items
	 */
	public function intersect_by_keys( $items ): static {
		return $this->passthru( 'intersect_by_keys', func_get_args() );
	}

	/**
	 * Determine if the items are empty or not.
	 */
	public function is_empty(): bool {
		return ! $this->getIterator()->valid();
	}

	/**
	 * Determine if the collection contains a single item.
	 */
	public function contains_one_item(): bool {
		return $this->take( 2 )->count() === 1;
	}

	/**
	 * Join all items from the collection using a string. The final items can use a separate glue string.
	 *
	 * @param  string $glue
	 * @param  string $final_glue
	 */
	public function join( $glue, $final_glue = '' ): string {
		return $this->collect()->join( ...func_get_args() );
	}

	/**
	 * Get the keys of the collection items.
	 *
	 * @return static<int, TKey>
	 */
	public function keys(): static {
		return new static( function () {
			foreach ( $this as $key => $value ) {
				yield $key;
			}
		} );
	}

	/**
	 * Get the last item from the collection.
	 *
	 * @template TLastDefault
	 *
	 * @param  (callable(TValue, TKey): bool)|null     $callback
	 * @param  TLastDefault|(\Closure(): TLastDefault) $default
	 * @return TValue|TLastDefault
	 */
	public function last( ?callable $callback = null, $default = null ) {
		$needle                              = new stdClass();
								$placeholder = $needle;
		foreach ( $this as $key => $value ) {
			if ( is_null( $callback ) || $callback( $value, $key ) ) {
				$needle = $value;
			}
		}

		return $needle === $placeholder ? value( $default ) : $needle;
	}

	/**
	 * Get the values of a given key.
	 *
	 * @param  string|array<array-key, string> $value
	 * @param  string|null                     $key
	 * @return static<array-key, mixed>
	 */
	public function pluck( $value, $key = null ): static {
		return new static( function () use ( $value, $key ) {
			[$value, $key] = $this->explode_pluck_parameters( $value, $key );

			foreach ( $this as $item ) {
				$itemValue = $value instanceof Closure
					? $value( $item )
					: data_get( $item, $value );

				if ( is_null( $key ) ) {
					yield $itemValue;
				} else {
					$itemKey = $key instanceof Closure
						? $key( $item )
						: data_get( $item, $key );

					if ( is_object( $itemKey ) && method_exists( $itemKey, '__toString' ) ) {
						$itemKey = (string) $itemKey;
					}

					yield $itemKey => $itemValue;
				}
			}
		} );
	}

	/**
	 * Run a map over each of the items.
	 *
	 * @template TMapValue
	 *
	 * @param  callable(TValue, TKey): TMapValue $callback
	 * @return static<TKey, TMapValue>
	 */
	public function map( callable $callback ): static {
		return new static( function () use ( $callback ) {
			foreach ( $this as $key => $value ) {
				yield $key => $callback( $value, $key );
			}
		} );
	}

	/**
	 * Run a dictionary map over the items.
	 *
	 * The callback should return an associative array with a single key/value pair.
	 *
	 * @template TMapToDictionaryKey of array-key
	 * @template TMapToDictionaryValue
	 *
	 * @param  callable(TValue, TKey): array<TMapToDictionaryKey, TMapToDictionaryValue> $callback
	 * @return static<TMapToDictionaryKey, array<int, TMapToDictionaryValue>>
	 */
	public function map_to_dictionary( callable $callback ): static {
		return $this->passthru( 'map_to_dictionary', func_get_args() );
	}

	/**
	 * Run an associative map over each of the items.
	 *
	 * The callback should return an associative array with a single key/value pair.
	 *
	 * @template TMapWithKeysKey of array-key
	 * @template TMapWithKeysValue
	 *
	 * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue> $callback
	 * @return static<TMapWithKeysKey, TMapWithKeysValue>
	 */
	public function map_with_keys( callable $callback ): static {
		return new static( function () use ( $callback ) {
			foreach ( $this as $key => $value ) {
				yield from $callback( $value, $key );
			}
		} );
	}

	/**
	 * Merge the collection with the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 */
	public function merge( $items ): static {
		return $this->passthru( 'merge', func_get_args() );
	}

	/**
	 * Recursively merge the collection with the given items.
	 *
	 * @template TMergeRecursiveValue
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TMergeRecursiveValue>|iterable<TKey, TMergeRecursiveValue> $items
	 * @return static<TKey, TValue|TMergeRecursiveValue>
	 */
	public function merge_recursive( $items ): static {
		return $this->passthru( 'merge_recursive', func_get_args() );
	}

	/**
	 * Multiply the items in the collection by the multiplier.
	 *
	 * @param  int $multiplier
	 */
	public function multiply( int $multiplier ): static {
		return $this->passthru( 'multiply', func_get_args() );
	}

	/**
	 * Create a collection by using this collection for keys and another for its values.
	 *
	 * @template TCombineValue
	 *
	 * @param  \IteratorAggregate<array-key, TCombineValue>|array<array-key, TCombineValue>|(callable(): \Generator<array-key, TCombineValue>) $values
	 * @return static<TValue, TCombineValue>
	 */
	public function combine( $values ): static {
		return new static( function () use ( $values ) {
			$values = $this->make_iterator( $values );

			$errorMessage = 'Both parameters should have an equal number of elements';

			foreach ( $this as $key ) {
				if ( ! $values->valid() ) {
					trigger_error( $errorMessage, E_USER_WARNING );

					break;
				}

				yield $key => $values->current();

				$values->next();
			}

			if ( $values->valid() ) {
				trigger_error( $errorMessage, E_USER_WARNING );
			}
		} );
	}

	/**
	 * Union the collection with the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 */
	public function union( $items ): static {
		return $this->passthru( 'union', func_get_args() );
	}

	/**
	 * Create a new collection consisting of every n-th element.
	 *
	 * @param  int $step
	 * @param  int $offset
	 */
	public function nth( $step, $offset = 0 ): static {
		return new static( function () use ( $step, $offset ) {
			$position = 0;

			foreach ( $this->slice( $offset ) as $item ) {
				if ( $position % $step === 0 ) {
					yield $item;
				}

				$position++;
			}
		} );
	}

	/**
	 * Get the items with the specified keys.
	 *
	 * @param  \Mantle\Support\Enumerable<array-key, TKey>|array<array-key, TKey>|string $keys
	 */
	public function only( $keys ): static {
		if ( $keys instanceof Enumerable ) {
			$keys = $keys->all();
		} elseif ( ! is_null( $keys ) ) {
			$keys = is_array( $keys ) ? $keys : func_get_args();
		}

		return new static( function () use ( $keys ) {
			if ( is_null( $keys ) ) {
				yield from $this;
			} else {
				$keys = array_flip( $keys );

				foreach ( $this as $key => $value ) {
					if ( array_key_exists( $key, $keys ) ) {
						yield $key => $value;

						unset( $keys[ $key ] );

						if ( empty( $keys ) ) {
							break;
						}
					}
				}
			}
		} );
	}

	/**
	 * Select specific values from the items within the collection.
	 *
	 * @param  \Mantle\Support\Enumerable<array-key, TKey>|array<array-key, TKey>|string $keys
	 */
	public function select( $keys ): static {
		if ( $keys instanceof Enumerable ) {
			$keys = $keys->all();
		} elseif ( ! is_null( $keys ) ) {
			$keys = is_array( $keys ) ? $keys : func_get_args();
		}

		return new static( function () use ( $keys ) {
			if ( is_null( $keys ) ) {
				yield from $this;
			} else {
				foreach ( $this as $item ) {
					$result = [];

					foreach ( $keys as $key ) {
						if ( Arr::accessible( $item ) && Arr::exists( $item, $key ) ) {
							$result[ $key ] = $item[ $key ];
						} elseif ( is_object( $item ) && isset( $item->{$key} ) ) {
							$result[ $key ] = $item->{$key};
						}
					}

					yield $result;
				}
			}
		} );
	}

	/**
	 * Push all of the given items onto the collection.
	 *
	 * @template TConcatKey of array-key
	 * @template TConcatValue
	 *
	 * @param  iterable<TConcatKey, TConcatValue> $source
	 * @return static<TKey|TConcatKey, TValue|TConcatValue>
	 */
	public function concat( $source ): static {
		return ( new static( function () use ( $source ) {
			yield from $this;
			yield from $source;
		} ) )->values();
	}

	/**
	 * Get one or a specified number of items randomly from the collection.
	 *
	 * @param  int|null $number
	 * @return static<int, TValue>|TValue
	 *
	 * @throws \InvalidArgumentException
	 */
	public function random( $number = null ) {
		$result = $this->collect()->random( ...func_get_args() );

		return is_null( $number ) ? $result : new static( $result );
	}

	/**
	 * Replace the collection items with the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 */
	public function replace( $items ): static {
		return new static( function () use ( $items ) {
			$items = $this->get_arrayable_items( $items );

			foreach ( $this as $key => $value ) {
				if ( array_key_exists( $key, $items ) ) {
					yield $key => $items[ $key ];

					unset( $items[ $key ] );
				} else {
					yield $key => $value;
				}
			}

			foreach ( $items as $key => $value ) {
				yield $key => $value;
			}
		} );
	}

	/**
	 * Recursively replace the collection items with the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 */
	public function replace_recursive( $items ): static {
		return $this->passthru( 'replace_recursive', func_get_args() );
	}

	/**
	 * Reverse items order.
	 *
	 * @return static<TKey, TValue>
	 */
	public function reverse(): static {
		return $this->passthru( 'reverse', func_get_args() );
	}

	/**
	 * Search the collection for a given value and return the corresponding key if successful.
	 *
	 * @param  TValue|(callable(TValue,TKey): bool) $value
	 * @param  bool                                 $strict
	 * @return TKey|false
	 */
	public function search( $value, $strict = false ): int|string|false {
		/** @var (callable(TValue,TKey): bool) $predicate */
		$predicate = $this->use_as_callable( $value )
			? $value
			: ( fn( $item ) => $strict ? $item === $value : $item === $value );

		foreach ( $this as $key => $item ) {
			if ( $predicate( $item, $key ) ) {
				return $key;
			}
		}

		return false;
	}

	/**
	 * Get the item before the given item.
	 *
	 * @param  TValue|(callable(TValue,TKey): bool) $value
	 * @param  bool                                 $strict
	 * @return TValue|null
	 */
	public function before( $value, $strict = false ) {
		$previous = null;

		/** @var (callable(TValue,TKey): bool) $predicate */
		$predicate = $this->use_as_callable( $value )
			? $value
			: ( fn( $item ) => $strict ? $item === $value : $item === $value );

		foreach ( $this as $key => $item ) {
			if ( $predicate( $item, $key ) ) {
				return $previous;
			}

			$previous = $item;
		}

		return null;
	}

	/**
	 * Get the item after the given item.
	 *
	 * @param  TValue|(callable(TValue,TKey): bool) $value
	 * @param  bool                                 $strict
	 * @return TValue|null
	 */
	public function after( $value, $strict = false ) {
		$found = false;

		/** @var (callable(TValue,TKey): bool) $predicate */
		$predicate = $this->use_as_callable( $value )
			? $value
			: ( fn( $item ) => $strict ? $item === $value : $item === $value );

		foreach ( $this as $key => $item ) {
			if ( $found ) {
				return $item;
			}

			if ( $predicate( $item, $key ) ) {
				$found = true;
			}
		}

		return null;
	}

	/**
	 * Shuffle the items in the collection.
	 *
	 * @return static<TKey, TValue>
	 */
	public function shuffle(): static {
		return $this->passthru( 'shuffle', [] );
	}

	/**
	 * Create chunks representing a "sliding window" view of the items in the collection.
	 *
	 * @param  positive-int $size
	 * @param  positive-int $step
	 * @return static<int, static>
	 *
	 * @throws \InvalidArgumentException
	 */
	public function sliding( $size = 2, $step = 1 ): static {
		if ( $size < 1 ) {
			throw new InvalidArgumentException( 'Size value must be at least 1.' );
		}

		if ( $step < 1 ) {
			throw new InvalidArgumentException( 'Step value must be at least 1.' );
		}

		return new static( function () use ( $size, $step ) {
			$iterator = $this->getIterator();

			$chunk = [];

			while ( $iterator->valid() ) {
				$chunk[ $iterator->key() ] = $iterator->current();

				if ( count( $chunk ) === $size ) {
					yield (new static( $chunk ))->tap( function () use ( &$chunk, $step ): void {
						$chunk = array_slice( $chunk, $step, null, true );
					} );

					// If the $step between chunks is bigger than each chunk's $size
					// we will skip the extra items (which should never be in any
					// chunk) before we continue to the next chunk in the loop.
					if ( $step > $size ) {
						$skip = $step - $size;

						for ( $i = 0; $i < $skip && $iterator->valid(); $i++ ) {
							$iterator->next();
						}
					}
				}

				$iterator->next();
			}
		} );
	}

	/**
	 * Skip the first {$count} items.
	 *
	 * @param  int $count
	 */
	public function skip( $count ): static {
		return new static( function () use ( $count ) {
			$iterator = $this->getIterator();

			while ( $iterator->valid() && $count-- ) {
				$iterator->next();
			}

			while ( $iterator->valid() ) {
				yield $iterator->key() => $iterator->current();

				$iterator->next();
			}
		} );
	}

	/**
	 * Skip items in the collection until the given condition is met.
	 *
	 * @param  TValue|callable(TValue,TKey): bool $value
	 */
	public function skip_until( $value ): static {
		$callback = $this->use_as_callable( $value ) ? $value : $this->equality( $value );

		return $this->skip_while( $this->negate( $callback ) );
	}

	/**
	 * Skip items in the collection while the given condition is met.
	 *
	 * @param  TValue|callable(TValue,TKey): bool $value
	 */
	public function skip_while( $value ): static {
		$callback = $this->use_as_callable( $value ) ? $value : $this->equality( $value );

		return new static( function () use ( $callback ) {
			$iterator = $this->getIterator();

			while ( $iterator->valid() && $callback( $iterator->current(), $iterator->key() ) ) {
				$iterator->next();
			}

			while ( $iterator->valid() ) {
				yield $iterator->key() => $iterator->current();

				$iterator->next();
			}
		} );
	}

	/**
	 * Get a slice of items from the enumerable.
	 *
	 * @param  int      $offset
	 * @param  int|null $length
	 */
	public function slice( $offset, $length = null ): static {
		if ( $offset < 0 || $length < 0 ) {
			return $this->passthru( 'slice', func_get_args() );
		}

		$instance = $this->skip( $offset );

		return is_null( $length ) ? $instance : $instance->take( $length );
	}

	/**
	 * Split a collection into a certain number of groups.
	 *
	 * @param  int $number_of_groups
	 * @return static<int, static>
	 */
	public function split( $number_of_groups ): static {
		return $this->passthru( 'split', func_get_args() );
	}

	/**
	 * Get the first item in the collection, but only if exactly one item exists. Otherwise, throw an exception.
	 *
	 * @param  (callable(TValue, TKey): bool)|string|null $key
	 * @param  mixed                                      $operator
	 * @param  mixed                                      $value
	 * @return TValue
	 *
	 * @throws \Mantle\Support\ItemNotFoundException
	 * @throws \Mantle\Support\MultipleItemsFoundException
	 */
	public function sole( $key = null, $operator = null, $value = null ) {
		$filter = func_num_args() > 1
			? $this->operator_for_where( ...func_get_args() )
			: $key;

		return $this
			->unless( $filter === null )
			->filter( $filter )
			->take( 2 )
			->collect()
			->sole();
	}

	/**
	 * Get the first item in the collection but throw an exception if no matching items exist.
	 *
	 * @param  (callable(TValue, TKey): bool)|string|null $key
	 * @param  mixed                                      $operator
	 * @param  mixed                                      $value
	 * @return TValue
	 *
	 * @throws \Mantle\Support\ItemNotFoundException
	 */
	public function first_or_fail( $key = null, $operator = null, $value = null ) {
		$filter = func_num_args() > 1
			? $this->operator_for_where( ...func_get_args() )
			: $key;

		return $this
			->unless( $filter === null )
			->filter( $filter )
			->take( 1 )
			->collect()
			->first_or_fail();
	}

	/**
	 * Chunk the collection into chunks of the given size.
	 *
	 * @param  int  $size
	 * @param  bool $preserve_keys
	 * @return ($preserve_keys is true ? static<int, static> : static<int, static<int, TValue>>)
	 */
	public function chunk( $size, $preserve_keys = true ) {
		if ( $size <= 0 ) {
			return static::empty();
		}

		$add = match ( $preserve_keys ) {
			true => fn ( array &$chunk, Traversable $iterator ) => $chunk[ $iterator->key() ] = $iterator->current(),
			false => fn ( array &$chunk, Traversable $iterator ) => $chunk[] = $iterator->current(),
		};

		return new static( function () use ( $size, $add ) {
			$iterator = $this->getIterator();

			while ( $iterator->valid() ) {
				$chunk = [];

				while ( true ) {
					$add( $chunk, $iterator );

					if ( count( $chunk ) < $size ) {
						$iterator->next();

						if ( ! $iterator->valid() ) {
							break;
						}
					} else {
						break;
					}
				}

				yield new static( $chunk );

				$iterator->next();
			}
		} );
	}

	/**
	 * Split a collection into a certain number of groups, and fill the first groups completely.
	 *
	 * @param  int $number_of_groups
	 * @return static<int, static>
	 */
	public function split_in( $number_of_groups ) {
		return $this->chunk( (int) ceil( $this->count() / $number_of_groups ) );
	}

	/**
	 * Chunk the collection into chunks with a callback.
	 *
	 * @param  callable(TValue, TKey, Collection<TKey, TValue>): bool $callback
	 * @return static<int, static<TKey, TValue>>
	 */
	public function chunk_while( callable $callback ): static {
		return new static( function () use ( $callback ) {
			$iterator = $this->getIterator();

			$chunk = new Collection();

			if ( $iterator->valid() ) {
				$chunk[ $iterator->key() ] = $iterator->current();

				$iterator->next();
			}

			while ( $iterator->valid() ) {
				if ( ! $callback( $iterator->current(), $iterator->key(), $chunk ) ) {
					yield new static( $chunk );

					$chunk = new Collection();
				}

				$chunk[ $iterator->key() ] = $iterator->current();

				$iterator->next();
			}

			if ( $chunk->is_not_empty() ) {
				yield new static( $chunk );
			}
		} );
	}

	/**
	 * Sort through each item with a callback.
	 *
	 * @param  (callable(TValue, TValue): int)|null|int $callback
	 */
	public function sort( $callback = null ): static {
		return $this->passthru( 'sort', func_get_args() );
	}

	/**
	 * Sort items in descending order.
	 *
	 * @param  int $options
	 */
	public function sort_desc( $options = SORT_REGULAR ): static {
		return $this->passthru( 'sort_desc', func_get_args() );
	}

	/**
	 * Sort the collection using the given callback.
	 *
	 * @param  array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>|(callable(TValue, TKey): mixed)|string $callback
	 * @param  int                                                                                                                                                     $options
	 * @param  bool                                                                                                                                                    $descending
	 */
	public function sort_by( $callback, $options = SORT_REGULAR, $descending = false ): static {
		return $this->passthru( 'sort_by', func_get_args() );
	}

	/**
	 * Sort the collection in descending order using the given callback.
	 *
	 * @param  array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>|(callable(TValue, TKey): mixed)|string $callback
	 * @param  int                                                                                                                                                     $options
	 */
	public function sort_by_desc( $callback, $options = SORT_REGULAR ): static {
		return $this->passthru( 'sort_by_desc', func_get_args() );
	}

	/**
	 * Sort the collection keys.
	 *
	 * @param  int  $options
	 * @param  bool $descending
	 */
	public function sort_keys( $options = SORT_REGULAR, $descending = false ): static {
		return $this->passthru( 'sort_keys', func_get_args() );
	}

	/**
	 * Sort the collection keys in descending order.
	 *
	 * @param  int $options
	 */
	public function sort_keys_desc( $options = SORT_REGULAR ): static {
		return $this->passthru( 'sort_keys_desc', func_get_args() );
	}

	/**
	 * Sort the collection keys using a callback.
	 *
	 * @param  callable(TKey, TKey): int $callback
	 */
	public function sort_keys_using( callable $callback ): static {
		return $this->passthru( 'sort_keys_using', func_get_args() );
	}

	/**
	 * Take the first or last {$limit} items.
	 *
	 * @param  int $limit
	 * @return static<TKey, TValue>
	 */
	public function take( $limit ): static {
		if ( $limit < 0 ) {
			return new static( function () use ( $limit ) {
				$limit      = abs( $limit );
				$ringBuffer = [];
				$position   = 0;

				foreach ( $this as $key => $value ) {
					$ringBuffer[ $position ] = [ $key, $value ];
					$position                = ( $position + 1 ) % $limit;
				}

				for ( $i = 0, $end = min( $limit, count( $ringBuffer ) ); $i < $end; $i++ ) {
					$pointer = ( $position + $i ) % $limit;
					yield $ringBuffer[ $pointer ][0] => $ringBuffer[ $pointer ][1];
				}
			} );
		}

		return new static( function () use ( $limit ) {
			$iterator = $this->getIterator();

			while ( $limit-- ) {
				if ( ! $iterator->valid() ) {
					break;
				}

				yield $iterator->key() => $iterator->current();

				if ( $limit !== 0 ) {
					$iterator->next();
				}
			}
		} );
	}

	/**
	 * Take items in the collection until the given condition is met.
	 *
	 * @param  TValue|callable(TValue,TKey): bool $value
	 * @return static<TKey, TValue>
	 */
	public function take_until( $value ): static {
		/** @var callable(TValue, TKey): bool $callback */
		$callback = $this->use_as_callable( $value ) ? $value : $this->equality( $value );

		return new static( function () use ( $callback ) {
			foreach ( $this as $key => $item ) {
				if ( $callback( $item, $key ) ) {
					break;
				}

				yield $key => $item;
			}
		} );
	}

	/**
	 * Take items in the collection until a given point in time, with an optional callback on timeout.
	 *
	 * @param  \DateTimeInterface                           $timeout
	 * @param  callable(TValue|null, TKey|null): mixed|null $callback
	 * @return static<TKey, TValue>
	 */
	public function take_until_timeout( DateTimeInterface $timeout, ?callable $callback = null ): static {
		$timeout = $timeout->getTimestamp();

		return new static( function () use ( $timeout, $callback ) {
			if ( $this->now() >= $timeout ) {
				if ( $callback ) {
					$callback( null, null );
				}

				return;
			}

			foreach ( $this as $key => $value ) {
				yield $key => $value;

				if ( $this->now() >= $timeout ) {
					if ( $callback ) {
						$callback( $value, $key );
					}

					break;
				}
			}
		} );
	}

	/**
	 * Take items in the collection while the given condition is met.
	 *
	 * @param  TValue|callable(TValue,TKey): bool $value
	 * @return static<TKey, TValue>
	 */
	public function take_while( $value ): static {
		/** @var callable(TValue, TKey): bool $callback */
		$callback = $this->use_as_callable( $value ) ? $value : $this->equality( $value );

		return $this->take_until( fn ( $item, $key ) => ! $callback( $item, $key ) );
	}

	/**
	 * Pass each item in the collection to the given callback, lazily.
	 *
	 * @param  callable(TValue, TKey): mixed $callback
	 * @return static<TKey, TValue>
	 */
	public function tap_each( callable $callback ): static {
		return new static( function () use ( $callback ) {
			foreach ( $this as $key => $value ) {
				$callback( $value, $key );

				yield $key => $value;
			}
		} );
	}

	/**
	 * Throttle the values, releasing them at most once per the given seconds.
	 *
	 * @return static<TKey, TValue>
	 */
	public function throttle( float $seconds ): static {
		return new static( function () use ( $seconds ) {
			$microseconds = $seconds * 1_000_000;

			foreach ( $this as $key => $value ) {
				$fetched_at = $this->precise_now();

				yield $key => $value;

				$sleep = $microseconds - ( $this->precise_now() - $fetched_at );

				$this->usleep( (int) $sleep );
			}
		} );
	}

	/**
	 * Flatten a multi-dimensional associative array with dots.
	 */
	public function dot(): static {
		return $this->passthru( 'dot', [] );
	}

	/**
	 * Convert a flatten "dot" notation array into an expanded array.
	 */
	public function undot(): static {
		return $this->passthru( 'undot', [] );
	}

	/**
	 * Return only unique items from the collection array.
	 *
	 * @param  (callable(TValue, TKey): mixed)|string|null $key
	 * @param  bool                                        $strict
	 * @return static<TKey, TValue>
	 */
	public function unique( $key = null, $strict = false ): static {
		$callback = $this->value_retriever( $key );

		return new static( function () use ( $callback, $strict ) {
			$exists = [];

			foreach ( $this as $key => $item ) {
				if ( ! in_array( $id = $callback( $item, $key ), $exists, $strict ) ) {
					yield $key => $item;

					$exists[] = $id;
				}
			}
		} );
	}

	/**
	 * Reset the keys on the underlying array.
	 *
	 * @return static<int, TValue>
	 */
	public function values(): static {
		return new static( function () {
			foreach ( $this as $item ) {
				yield $item;
			}
		} );
	}

	/**
	 * Run the given callback every time the interval has passed.
	 *
	 * @return static<TKey, TValue>
	 */
	public function with_heartbeat( DateInterval|int $interval, callable $callback ): static {
		$seconds = is_int( $interval ) ? $interval : $this->interval_seconds( $interval );

		return new static( function () use ( $seconds, $callback ) {
			$start = $this->now();

			foreach ( $this as $key => $value ) {
				$now = $this->now();

				if ( ( $now - $start ) >= $seconds ) {
					$callback();

					$start = $now;
				}

				yield $key => $value;
			}
		} );
	}

	/**
	 * Get the total seconds from the given interval.
	 */
	protected function intervalSeconds( DateInterval $interval ): int {
		$start = new DateTimeImmutable();

		return $start->add( $interval )->getTimestamp() - $start->getTimestamp();
	}

	/**
	 * Zip the collection together with one or more arrays.
	 *
	 * e.g. new LazyCollection([1, 2, 3])->zip([4, 5, 6]);
	 *      => [[1, 4], [2, 5], [3, 6]]
	 *
	 * @template TZipValue
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TZipValue>|iterable<array-key, TZipValue> ...$items
	 * @return static<int, static<int, TValue|TZipValue>>
	 */
	public function zip( $items ): static {
		$iterables = func_get_args();

		return new static( function () use ( $iterables ) {
			$iterators = ( new Collection( $iterables ) )
				->map( fn ( $iterable ) => $this->make_iterator( $iterable ) )
				->prepend( $this->getIterator() );

			while ( $iterators->contains->valid() ) {
				yield new static( $iterators->map->current() );

				$iterators->each->next();
			}
		} );
	}

	/**
	 * Pad collection to the specified length with a value.
	 *
	 * @template TPadValue
	 *
	 * @param  int       $size
	 * @param  TPadValue $value
	 * @return static<int, TValue|TPadValue>
	 */
	public function pad( $size, $value ): static {
		if ( $size < 0 ) {
			return $this->passthru( 'pad', func_get_args() );
		}

		return new static( function () use ( $size, $value ) {
			$yielded = 0;

			foreach ( $this as $index => $item ) {
				yield $index => $item;

				$yielded++;
			}

			while ( $yielded++ < $size ) {
				yield $value;
			}
		} );
	}

	/**
	 * Get the values iterator.
	 *
	 * @return \Traversable<TKey, TValue>
	 */
	public function getIterator(): Traversable {
		return $this->make_iterator( $this->source );
	}

	/**
	 * Count the number of items in the collection.
	 */
	public function count(): int {
		if ( is_array( $this->source ) ) {
			return count( $this->source );
		}

		return iterator_count( $this->getIterator() );
	}

	/**
	 * Make an iterator from the given source.
	 *
	 * @template TIteratorKey of array-key
	 * @template TIteratorValue
	 *
	 * @param  \IteratorAggregate<TIteratorKey, TIteratorValue>|array<TIteratorKey, TIteratorValue>|(callable(): \Generator<TIteratorKey, TIteratorValue>) $source
	 * @return \Traversable<TIteratorKey, TIteratorValue>
	 */
	protected function make_iterator( $source ) {
		if ( $source instanceof IteratorAggregate ) {
			return $source->getIterator();
		}

		if ( is_array( $source ) ) {
			return new ArrayIterator( $source );
		}

		if ( is_callable( $source ) ) {
			$maybe_traversable = $source();

			return $maybe_traversable instanceof Traversable
				? $maybe_traversable
				: new ArrayIterator( Arr::wrap( $maybe_traversable ) );
		}

		return new ArrayIterator( (array) $source );
	}

	/**
	 * Explode the "value" and "key" arguments passed to "pluck".
	 *
	 * @param  string|string[]      $value
	 * @param  string|string[]|null $key
	 * @return array{string[],string[]|null}
	 */
	protected function explode_pluck_parameters( $value, $key ): array {
		$value = is_string( $value ) ? explode( '.', $value ) : $value;

		$key = is_null( $key ) || is_array( $key ) || $key instanceof Closure ? $key : explode( '.', $key );

		return [ $value, $key ];
	}

	/**
	 * Pass this lazy collection through a method on the collection class.
	 *
	 * @param  string       $method
	 * @param  array<mixed> $params
	 */
	protected function passthru( $method, array $params ): static {
		return new static( function () use ( $method, $params ) {
			yield from $this->collect()->$method( ...$params );
		} );
	}

	/**
	 * Get the current time.
	 *
	 * @return int
	 */
	protected function now() {
		return class_exists( Carbon::class )
			? Carbon::now()->timestamp
			: time();
	}

	/**
	 * Get the precise current time.
	 *
	 * @return float
	 */
	protected function precise_now() {
		return class_exists( Carbon::class )
			? Carbon::now()->getPreciseTimestamp()
			: microtime( true ) * 1_000_000;
	}

	/**
	 * Sleep for the given amount of microseconds.
	 *
	 * @return void
	 */
	protected function usleep( int $microseconds ) {
		if ( $microseconds <= 0 ) {
			return;
		}

		usleep( $microseconds );
	}
}
