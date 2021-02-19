<?php
/**
 * Length_Aware_Paginator class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Pagination;

/**
 * Length Aware Paginator
 */
class Length_Aware_Paginator extends Paginator {
	/**
	 * Storage of the found rows.
	 *
	 * @var int
	 */
	protected $found_rows;

	/**
	 * View name to load.
	 *
	 * @var string
	 */
	protected $view = 'paginator';

	/**
	 * Set the items for the paginator.
	 *
	 * @return static
	 */
	protected function set_items() {
		$builder = $this->builder;

		$this->items      = $builder->get();
		$this->found_rows = $builder->get_found_rows();

		return $this;
	}

	/**
	 * Elements for the paginator.
	 *
	 * @return array
	 */
	public function elements(): array {
		$elements     = [];
		$current_page = $this->current_page();
		$max_pages    = $this->max_pages();

		// Previous two pages.
		for ( $i = $current_page - 2; $i < $current_page; $i++ ) {
			if ( $i < 1 ) {
				continue;
			}

			$elements[][ $i ] = $this->url( $i );
		}

		// Current page.
		$elements[][ $current_page ] = $this->url( $current_page );

		// Previous next pages.
		for ( $i = $current_page + 1; $i < $max_pages; $i++ ) {
			$elements[][ $i ] = $this->url( $i );
		}

		return $elements;
	}

	/**
	 * Determine if there are more items in the data source.
	 *
	 * @param bool $has_more Flag if it has more, unused.
	 * @return bool
	 */
	public function has_more( bool $has_more = null ): bool {
		if ( empty( $this->found_rows ) ) {
			return false;
		}

		return ( ( $this->current_page() - 1 ) * $this->per_page ) < $this->found_rows;
	}

	/**
	 * Retrieve the max number of pages.
	 *
	 * @return int
	 */
	public function max_pages(): int {
		if ( empty( $this->found_rows ) ) {
			return 0;
		}

		return ceil( $this->found_rows / $this->per_page );
	}

	/**
	 * Determine if the paginator has a previous page.
	 *
	 * @return int
	 */
	public function has_previous(): int {
		return $this->current_page() > $this->max_pages();
	}
}
