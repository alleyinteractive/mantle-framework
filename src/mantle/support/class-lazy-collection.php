<?php
/**
 * Lazy_Collection class file.
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName, Squiz.Commenting.FunctionComment.MissingParamTag, Squiz.Commenting.FunctionComment
 *
 * @package Mantle
 */

namespace Mantle\Support;

use ArrayIterator;
use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Generator;
use Mantle\Support\Traits\Enumerates_Values;
use Mantle\Support\Traits\Macroable;
use InvalidArgumentException;
use IteratorAggregate;
use stdClass;
use Traversable;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @implements \Mantle\Support\Enumerable<TKey, TValue>
 */
class Lazy_Collection implements Enumerable {
	/**
	 * @use \Mantle\Support\Traits\Enumerates_Values<TKey, TValue>
	 */
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
	 * @throws \InvalidArgumentException
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>|(Closure(): \Generator<TKey, TValue, mixed, void>)|self<TKey, TValue>|array<TKey, TValue>|null $source
	 * @return void
	 */
	public function __construct( $source = null ) {
		if ( $source instanceof Closure || $source instanceof self ) {
			$this->source = $source;
		} elseif ( is_null( $source ) ) {
			$this->source = static::empty();
		} elseif ( $source instanceof Generator ) {
			throw new InvalidArgumentException(
				'Generators should not be passed directly to Lazy_Collection. Instead, pass a generator function.'
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
	public static function make( $items = [] ) {
		return new static( $items );
	}

	/**
	 * Create a collection with the given range.
	 *
	 * @param  int $from
	 * @param  int $to
	 * @return static<int, int>
	 */
	public static function range( $from, $to ) {
		return new static( function () use ( $from, $to ) {
			if ( $from <= $to ) {
				for ( ; $from <= $to; $from++ ) {
					yield $from;
				}
			} else {
				for ( ; $from >= $to; $from-- ) {
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
	public function all() {
		if ( is_array( $this->source ) ) {
			return $this->source;
		}

		return iterator_to_array( $this->getIterator() );
	}

	/**
	 * Eager load all items into a new lazy collection backed by an array.
	 *
	 * @return static
	 */
	public function eager() {
		return new static( $this->all() );
	}

	/**
	 * Cache values as they're enumerated.
	 *
	 * @return static
	 */
	public function remember() {
		$iterator = $this->getIterator();

		$iteratorIndex = 0;

		$cache = [];

		return new static( function () use ( $iterator, &$iteratorIndex, &$cache ) {
			for ( $index = 0; true; $index++ ) {
				if ( array_key_exists( $index, $cache ) ) {
					yield $cache[ $index ][0] => $cache[ $index ][1];

					continue;
				}

				if ( $iteratorIndex < $index ) {
					$iterator->next();

					$iteratorIndex++;
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
	 * Get the average value of a given key.
	 *
	 * @param  (callable(TValue): float|int)|string|null $callback
	 * @return float|int|null
	 */
	public function avg( $callback = null ) {
		return $this->collect()->avg( $callback );
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
	public function collapse() {
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
				if ( $value == $needle ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
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
	 * @return bool
	 */
	public function doesnt_contain( $key, $operator = null, $value = null ) {
		return ! $this->contains( ...func_get_args() );
	}

	/**
	 * Cross join the given iterables, returning all possible permutations.
	 *
	 * @template TCrossJoinKey of array-key
	 * @template TCrossJoinValue
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TCrossJoinKey, TCrossJoinValue>|iterable<TCrossJoinKey, TCrossJoinValue> ...$arrays
	 * @return static<int, array<int, TValue|TCrossJoinValue>>
	 */
	public function cross_join( ...$arrays ) {
		return $this->passthru( 'cross_join', func_get_args() );
	}

	/**
	 * Count the number of items in the collection by a field or using a callback.
	 *
	 * @param  (callable(TValue, TKey): array-key)|string|null $count_by
	 * @return static<array-key, int>
	 */
	public function count_by( $count_by = null ) {
		$count_by = is_null( $count_by )
			? $this->identity()
			: $this->value_retriever( $count_by );

		return new static( function () use ( $count_by ) {
			$counts = [];

			foreach ( $this as $key => $value ) {
				$group = $count_by( $value, $key );

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
	 * @return static
	 */
	public function diff( $items ) {
		return $this->passthru( 'diff', func_get_args() );
	}

	/**
	 * Get the items that are not present in the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue> $items
	 * @param  callable(TValue, TValue): int                                                      $callback
	 * @return static
	 */
	public function diff_using( $items, callable $callback ) {
		return $this->passthru( 'diff_using', func_get_args() );
	}

	/**
	 * Get the items whose keys and values are not present in the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 * @return static
	 */
	public function diff_assoc( $items ) {
		return $this->passthru( 'diff_assoc', func_get_args() );
	}

	/**
	 * Get the items whose keys and values are not present in the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 * @param  callable(TKey, TKey): int                                                $callback
	 * @return static
	 */
	public function diff_assoc_using( $items, callable $callback ) {
		return $this->passthru( 'diff_assoc_using', func_get_args() );
	}

	/**
	 * Get the items whose keys are not present in the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 * @return static
	 */
	public function diff_keys( $items ) {
		return $this->passthru( 'diff_keys', func_get_args() );
	}

	/**
	 * Get the items whose keys are not present in the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 * @param  callable(TKey, TKey): int                                                $callback
	 * @return static
	 */
	public function diff_keys_using( $items, callable $callback ) {
		return $this->passthru( 'diff_keys_using', func_get_args() );
	}

	/**
	 * Retrieve duplicate items.
	 *
	 * @param  (callable(TValue): bool)|string|null $callback
	 * @param  bool                                 $strict
	 * @return static
	 */
	public function duplicates( $callback = null, $strict = false ) {
		return $this->passthru( 'duplicates', func_get_args() );
	}

	/**
	 * Retrieve duplicate items using strict comparison.
	 *
	 * @param  (callable(TValue): bool)|string|null $callback
	 * @return static
	 */
	public function duplicates_strict( $callback = null ) {
		return $this->passthru( 'duplicates_strict', func_get_args() );
	}

	/**
	 * Get all items except for those with the specified keys.
	 *
	 * @param  \Mantle\Support\Enumerable<array-key, TKey>|array<array-key, TKey> $keys
	 * @return static
	 */
	public function except( $keys ) {
		return $this->passthru( 'except', func_get_args() );
	}

	/**
	 * Run a filter over each of the items.
	 *
	 * @param  (callable(TValue, TKey): bool)|null $callback
	 * @return static
	 */
	public function filter( ?callable $callback = null ) {
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
	public function flatten( $depth = INF ) {
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
	public function flip() {
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
	 * @param  (callable(TValue, TKey): array-key)|array|string $groupBy
	 * @param  bool                                             $preserveKeys
	 * @return static<array-key, static<array-key, TValue>>
	 */
	public function group_by( $groupBy, $preserveKeys = false ) {
		return $this->passthru( 'group_by', func_get_args() );
	}

	/**
	 * Key an associative array by a field or using a callback.
	 *
	 * @param  (callable(TValue, TKey): array-key)|array|string $keyBy
	 * @return static<array-key, TValue>
	 */
	public function key_by( $keyBy ) {
		return new static( function () use ( $keyBy ) {
			$keyBy = $this->value_retriever( $keyBy );

			foreach ( $this as $key => $item ) {
				$resolvedKey = $keyBy( $item, $key );

				if ( is_object( $resolvedKey ) ) {
					$resolvedKey = (string) $resolvedKey;
				}

				yield $resolvedKey => $item;
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
	public function hasAny( $key ): bool {
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
	 * @param  callable|string $value
	 * @param  string|null     $glue
	 * @return string
	 */
	public function implode( $value, $glue = null ) {
		return $this->collect()->implode( ...func_get_args() );
	}

	/**
	 * Concatenate values of a given key as a string and returns a stringable class.
	 *
	 * @param  callable|string $value
	 * @param  string|null     $glue
	 * @return string
	 */
	public function implode_str( $value, $glue = null ) {
		return $this->collect()->implode_str( ...func_get_args() );
	}

	/**
	 * Intersect the collection with the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 * @return static
	 */
	public function intersect( $items ) {
		return $this->passthru( 'intersect', func_get_args() );
	}

	/**
	 * Intersect the collection with the given items, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue> $items
	 * @param  callable(TValue, TValue): int                                                      $callback
	 * @return static
	 */
	public function intersect_using( $items, callable $callback ) {
		return $this->passthru( 'intersect_using', func_get_args() );
	}

	/**
	 * Intersect the collection with the given items with additional index check.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 * @return static
	 */
	public function intersect_assoc( $items ) {
		return $this->passthru( 'intersect_assoc', func_get_args() );
	}

	/**
	 * Intersect the collection with the given items with additional index check, using the callback.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue> $items
	 * @param  callable(TValue, TValue): int                                                      $callback
	 * @return static
	 */
	public function intersect_assoc_using( $items, callable $callback ) {
		return $this->passthru( 'intersect_assoc_using', func_get_args() );
	}

	/**
	 * Intersect the collection with the given items by key.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 * @return static
	 */
	public function intersect_by_keys( $items ) {
		return $this->passthru( 'intersect_by_keys', func_get_args() );
	}

	/**
	 * Determine if the items are empty or not.
	 *
	 * @return bool
	 */
	public function is_empty() {
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
	 * @param  string $finalGlue
	 * @return string
	 */
	public function join( $glue, $finalGlue = '' ) {
		return $this->collect()->join( ...func_get_args() );
	}

	/**
	 * Get the keys of the collection items.
	 *
	 * @return static<int, TKey>
	 */
	public function keys() {
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
		$needle = $placeholder = new stdClass();

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
	 * @return static<int, mixed>
	 */
	public function pluck( $value, $key = null ) {
		return new static( function () use ( $value, $key ) {
			[$value, $key] = $this->explode_pluck_parameters( $value, $key );

			foreach ( $this as $item ) {
				$itemValue = data_get( $item, $value );

				if ( is_null( $key ) ) {
					yield $itemValue;
				} else {
					$itemKey = data_get( $item, $key );

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
	public function map( callable $callback ) {
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
	public function map_to_dictionary( callable $callback ) {
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
	public function map_with_keys( callable $callback ) {
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
	 * @return static
	 */
	public function merge( $items ) {
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
	public function merge_recursive( $items ) {
		return $this->passthru( 'merge_recursive', func_get_args() );
	}

	/**
	 * Create a collection by using this collection for keys and another for its values.
	 *
	 * @template TCombineValue
	 *
	 * @param  \IteratorAggregate<array-key, TCombineValue>|array<array-key, TCombineValue>|(callable(): \Generator<array-key, TCombineValue>) $values
	 * @return static<TValue, TCombineValue>
	 */
	public function combine( $values ) {
		return new static( function () use ( $values ) {
			$values = $this->make_iterator( $values );

			$error_message = 'Both parameters should have an equal number of elements';

			foreach ( $this as $key ) {
				if ( ! $values->valid() ) {
					trigger_error( esc_html( $error_message ), E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error

					break;
				}

				yield $key => $values->current();

				$values->next();
			}

			if ( $values->valid() ) {
				trigger_error( esc_html( $error_message ), E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			}
		} );
	}

	/**
	 * Union the collection with the given items.
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
	 * @return static
	 */
	public function union( $items ) {
		return $this->passthru( 'union', func_get_args() );
	}

	/**
	 * Create a new collection consisting of every n-th element.
	 *
	 * @param  int $step
	 * @param  int $offset
	 * @return static
	 */
	public function nth( $step, $offset = 0 ) {
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
	 * @return static
	 */
	public function only( $keys ) {
		if ( $keys instanceof Enumerable ) {
			$keys = $keys->all();
		} elseif ( ! is_null( $keys ) ) { // @phpstan-ignore-line always evaluate to false
			$keys = is_array( $keys ) ? $keys : func_get_args();
		}

		return new static( function () use ( $keys ) {
			if ( is_null( $keys ) ) { // @phpstan-ignore-line always evaluate to false
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
	 * @return static
	 */
	public function select( $keys ) {
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
	public function concat( $source ) {
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
	 * @return static
	 */
	public function replace( $items ) {
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
	 * @return static
	 */
	public function replace_recursive( $items ) {
		return $this->passthru( 'replace_recursive', func_get_args() );
	}

	/**
	 * Reverse items order.
	 *
	 * @return static
	 */
	public function reverse() {
		return $this->passthru( 'reverse', func_get_args() );
	}

	/**
	 * Search the collection for a given value and return the corresponding key if successful.
	 *
	 * @param  TValue|(callable(TValue,TKey): bool) $value
	 * @param  bool                                 $strict
	 * @return TKey|false
	 */
	public function search( $value, $strict = false ) {
		/** @var (callable(TValue,TKey): bool) $predicate */
		$predicate = $this->use_as_callable( $value )
			? $value
			: ( fn ( $item ) => $strict ? $item === $value : $item == $value ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual

		foreach ( $this as $key => $item ) {
			if ( $predicate( $item, $key ) ) {
				return $key;
			}
		}

		return false;
	}

	/**
	 * Shuffle the items in the collection.
	 *
	 * @return static
	 */
	public function shuffle( $seed = null ) {
		return $this->passthru( 'shuffle', [] );
	}

	/**
	 * Create chunks representing a "sliding window" view of the items in the collection.
	 *
	 * @param  int $size
	 * @param  int $step
	 * @return static<int, static>
	 */
	public function sliding( $size = 2, $step = 1 ) {
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

						for ( $i = 0; $i < $skip && $iterator->valid(); $i++ ) { // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed
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
	 * @return static
	 */
	public function skip( $count ) {
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
	 * @return static
	 */
	public function skip_until( $value ) {
		$callback = $this->use_as_callable( $value ) ? $value : $this->equality( $value );

		return $this->skip_while( $this->negate( $callback ) );
	}

	/**
	 * Skip items in the collection while the given condition is met.
	 *
	 * @param  TValue|callable(TValue,TKey): bool $value
	 * @return static
	 */
	public function skip_while( $value ) {
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
	 * @return static
	 */
	public function slice( $offset, $length = null ) {
		if ( $offset < 0 || $length < 0 ) {
			return $this->passthru( 'slice', func_get_args() );
		}

		$instance = $this->skip( $offset );

		return is_null( $length ) ? $instance : $instance->take( $length );
	}

	/**
	 * Split a collection into a certain number of groups.
	 *
	 * @param  int $numberOfGroups
	 * @return static<int, static>
	 */
	public function split( $numberOfGroups ) {
		return $this->passthru( 'split', func_get_args() );
	}

	/**
	 * Get the first item in the collection, but only if exactly one item exists. Otherwise, throw an exception.
	 *
	 * @param  (callable(TValue, TKey): bool)|string $key
	 * @param  mixed                                 $operator
	 * @param  mixed                                 $value
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
	 * @param  (callable(TValue, TKey): bool)|string $key
	 * @param  mixed                                 $operator
	 * @param  mixed                                 $value
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
			->firstOrFail();
	}

	/**
	 * Chunk the collection into chunks of the given size.
	 *
	 * @param  int $size
	 * @return static<int, static>
	 */
	public function chunk( $size ) {
		if ( $size <= 0 ) {
			return static::empty();
		}

		return new static( function () use ( $size ) {
			$iterator = $this->getIterator();

			while ( $iterator->valid() ) {
				$chunk = [];

				while ( true ) {
					$chunk[ $iterator->key() ] = $iterator->current();

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
	 * @param  int $numberOfGroups
	 * @return static<int, static>
	 */
	public function split_in( $numberOfGroups ) {
		return $this->chunk( ceil( $this->count() / $numberOfGroups ) );
	}

	/**
	 * Chunk the collection into chunks with a callback.
	 *
	 * @param  callable(TValue, TKey, Collection<TKey, TValue>): bool $callback
	 * @return static<int, static<int, TValue>>
	 */
	public function chunk_while( callable $callback ) {
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
	 * @return static
	 */
	public function sort( $callback = null ) {
		return $this->passthru( 'sort', func_get_args() );
	}

	/**
	 * Sort items in descending order.
	 *
	 * @param  int $options
	 * @return static
	 */
	public function sort_desc( $options = SORT_REGULAR ) {
		return $this->passthru( 'sort_desc', func_get_args() );
	}

	/**
	 * Sort the collection using the given callback.
	 *
	 * @param  array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>|(callable(TValue, TKey): mixed)|string $callback
	 * @param  int                                                                                                                                                     $options
	 * @param  bool                                                                                                                                                    $descending
	 * @return static
	 */
	public function sort_by( $callback, $options = SORT_REGULAR, $descending = false ) {
		return $this->passthru( 'sort_by', func_get_args() );
	}

	/**
	 * Sort the collection in descending order using the given callback.
	 *
	 * @param  array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>|(callable(TValue, TKey): mixed)|string $callback
	 * @param  int                                                                                                                                                     $options
	 * @return static
	 */
	public function sort_by_desc( $callback, $options = SORT_REGULAR ) {
		return $this->passthru( 'sort_by_desc', func_get_args() );
	}

	/**
	 * Sort the collection keys.
	 *
	 * @param  int  $options
	 * @param  bool $descending
	 * @return static
	 */
	public function sort_keys( $options = SORT_REGULAR, $descending = false ) {
		return $this->passthru( 'sort_keys', func_get_args() );
	}

	/**
	 * Sort the collection keys in descending order.
	 *
	 * @param  int $options
	 * @return static
	 */
	public function sort_keys_desc( $options = SORT_REGULAR ) {
		return $this->passthru( 'sort_keys_desc', func_get_args() );
	}

	/**
	 * Sort the collection keys using a callback.
	 *
	 * @param  callable(TKey, TKey): int $callback
	 * @return static
	 */
	public function sort_keys_using( callable $callback ) {
		return $this->passthru( 'sort_keys_using', func_get_args() );
	}

	/**
	 * Take the first or last {$limit} items.
	 *
	 * @param  int $limit
	 * @return static
	 */
	public function take( $limit ) {
		if ( $limit < 0 ) {
			return new static( function () use ( $limit ) {
				$limit      = abs( $limit );
				$ringBuffer = [];
				$position   = 0;

				foreach ( $this as $key => $value ) {
					$ringBuffer[ $position ] = [ $key, $value ];
					$position                = ( $position + 1 ) % $limit; // phpcs:ignore Squiz.Operators.IncrementDecrementUsage.Found
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

				if ( $limit ) {
					$iterator->next();
				}
			}
		} );
	}

	/**
	 * Take items in the collection until the given condition is met.
	 *
	 * @param  TValue|callable(TValue,TKey): bool $value
	 * @return static
	 */
	public function take_until( $value ) {
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
	 * Take items in the collection until a given point in time.
	 *
	 * @param  \DateTimeInterface $timeout
	 * @return static
	 */
	public function take_until_timeout( DateTimeInterface $timeout ) {
		$timeout = $timeout->getTimestamp();

		return new static( function () use ( $timeout ) {
			if ( $this->now() >= $timeout ) {
				return;
			}

			foreach ( $this as $key => $value ) {
				yield $key => $value;

				if ( $this->now() >= $timeout ) {
					break;
				}
			}
		} );
	}

	/**
	 * Take items in the collection while the given condition is met.
	 *
	 * @param  TValue|callable(TValue,TKey): bool $value
	 * @return static
	 */
	public function take_while( $value ) {
		/** @var callable(TValue, TKey): bool $callback */
		$callback = $this->use_as_callable( $value ) ? $value : $this->equality( $value );

		return $this->take_until( fn ( $item, $key ) => ! $callback( $item, $key ) );
	}

	/**
	 * Pass each item in the collection to the given callback, lazily.
	 *
	 * @param  callable(TValue, TKey): mixed $callback
	 * @return static
	 */
	public function tap_each( callable $callback ) {
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
	public function throttle( float $seconds ) {
		return new static( function () use ( $seconds ) {
			$microseconds = $seconds * 1_000_000;

			foreach ( $this as $key => $value ) {
				$fetchedAt = $this->precise_now();

				yield $key => $value;

				$sleep = $microseconds - ( $this->precise_now() - $fetchedAt );

				$this->usleep( (int) $sleep );
			}
		} );
	}

	/**
	 * Flatten a multi-dimensional associative array with dots.
	 *
	 * @return static
	 */
	public function dot() {
		return $this->passthru( 'dot', [] );
	}

	/**
	 * Convert a flatten "dot" notation array into an expanded array.
	 *
	 * @return static
	 */
	public function undot() {
		return $this->passthru( 'undot', [] );
	}

	/**
	 * Return only unique items from the collection array.
	 *
	 * @param  (callable(TValue, TKey): mixed)|string|null $key
	 * @param  bool                                        $strict
	 * @return static
	 */
	public function unique( $key = null, $strict = false ) {
		$callback = $this->value_retriever( $key );

		return new static( function () use ( $callback, $strict ) {
			$exists = [];

			foreach ( $this as $key => $item ) {
				if ( ! in_array( $id = $callback( $item, $key ), $exists, $strict ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
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
	public function values() {
		return new static( function () {
			foreach ( $this as $item ) {
				yield $item;
			}
		} );
	}

	/**
	 * Zip the collection together with one or more arrays.
	 *
	 * Example:
	 *
	 *   new Lazy_Collection([1, 2, 3])->zip([4, 5, 6]);
	 *     => [[1, 4], [2, 5], [3, 6]]
	 *
	 * @template TZipValue
	 *
	 * @param  \Mantle\Contracts\Support\Arrayable<array-key, TZipValue>|iterable<array-key, TZipValue> ...$items
	 * @return static<int, static<int, TValue|TZipValue>>
	 */
	public function zip( $items ) {
		$iterables = func_get_args();

		return new static( function () use ( $iterables ) {
			$iterators = Collection::make( $iterables )->map( fn( $iterable ) => $this->make_iterator( $iterable ) )->prepend( $this->getIterator() );

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
	public function pad( $size, $value ) {
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
			$maybeTraversable = $source();

			return $maybeTraversable instanceof Traversable
				? $maybeTraversable
				: new ArrayIterator( Arr::wrap( $maybeTraversable ) );
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
	protected function explode_pluck_parameters( $value, $key ) {
		$value = is_string( $value ) ? explode( '.', $value ) : $value;

		$key = is_null( $key ) || is_array( $key ) ? $key : explode( '.', $key );

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
	 */
	protected function now(): int {
		return class_exists( Carbon::class )
			? Carbon::now()->timestamp
			: time();
	}

	/**
	 * Get the precise current time.
	 */
	protected function precise_now(): float {
		return class_exists( Carbon::class )
			? Carbon::now()->getPreciseTimestamp()
			: microtime( true ) * 1_000_000;
	}

	/**
	 * Sleep for the given amount of microseconds.
	 */
	protected function usleep( int $microseconds ): void {
		if ( $microseconds <= 0 ) {
			return;
		}

		usleep( $microseconds );
	}
}
