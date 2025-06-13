<?php

namespace Mantle\Support;

use Mantle\Contracts\Support\Arrayable;
use Mantle\Support\Traits\InteractsWithData;
use League\Uri\QueryString;
use Stringable;

class Uri_Query_String implements Arrayable, Stringable
{
	use Interacts_With_Data;

	/**
	 * Create a new URI query string instance.
	 *
	 * @param  mixed  $value
	 * @return static
	 */
	public static function create( mixed $value ): static {
		assert( $value instanceof Uri );

		return new static( QueryString::extract( $value->getUri()->getQuery() ) );
	}

	/**
	 * Create a new URI query string instance.
	 */
	public function __construct(array $value) {
		$this->value = $value;
	}
	// 	$this->value = QueryString::extract( $uri->getUri()->getQuery() );
	// }

	// public function get( string $property, mixed $default = null ): static {
	// 	$value = data_get($this->value, $property, $default);
	// // {
	// // 	// dd(QueryString::extract( $uri->getUri()->getQuery() ));
	// // 	$this->value = QueryString::extract( $uri->getUri()->getQuery() );
	// // }

	/**
	 * Get the instance as an array.
	 *
	 * @return array<TKey, TValue>
	 */
	// public function to_array() {
	// 	return [];
	// 	return $this->data();
	// }

	/**
	 * Retrieve all data from the instance.
	 *
	 * @param  array|mixed|null  $keys
	 * @return array
	 */
	// // public function all($keys = null)
	// // {
	// // 	$query = $this->to_array();

	// // 	if (! $keys) {
	// // 		return $query;
	// // 	}

	// // 	$results = [];

	// // 	foreach (is_array($keys) ? $keys : func_get_args() as $key) {
	// // 		Arr::set($results, $key, Arr::get($query, $key));
	// // 	}

	// // 	return $results;
	// // }

	// // /**
	// //  * Retrieve data from the instance.
	// //  *
	// //  * @param  string|null  $key
	// //  * @param  mixed  $default
	// //  * @return mixed
	// //  */
	// // protected function data($key = null, $default = null)
	// // {
	// // 	return $this->get($key, $default);
	// // }

	// // /**
	// //  * Get a query string parameter.
	// //  */
	// // public function get(?string $key = null, mixed $default = null): mixed
	// // {
	// // 	return data_get($this->to_array(), $key, $default);
	// // }

	public function all(): array
	{
		return $this->value;
	}

	// /**
	//  * Get the URL decoded version of the query string.
	//  */
	public function decode(): string
	{
		return rawurldecode((string) $this);
	}

	/**
	 * Get the string representation of the query string.
	 */
	// public function value(): string
	// {
	// 	return (string) $this;
	// }

	/**
	 * Convert the query string into an array.
	 */
	public function to_array(): array {
		return $this->value;
		// return QueryString::extract($this->value());
	}

	/**
	 * Get the string representation of the query string.
	 */
	public function __toString(): string {
		return QueryString::build( $this->value ) ?: '';
		return (string) $this->uri->getUri()->getQuery();
	}
}
