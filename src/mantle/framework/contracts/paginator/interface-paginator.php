<?php
/**
 * Paginator interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Paginator;

use Mantle\Framework\Support\Collection;

/**
 * Paginator Contract
 */
interface Paginator {
	public function path( string $path = null );
	public function get_path(): string;
	public function use_query_string();
	public function use_path();
	public function set_current_page( int $current_page = null );
	public function current_page(): int;
	public function items(): Collection;
	public function count(): int;
	public function append( $key, $value = null );
	public function with_query_string();
	public function query(): array;
	public function next_url(): ?string;
	public function previous_url(): ?string;
	// public function links(): string;
}
