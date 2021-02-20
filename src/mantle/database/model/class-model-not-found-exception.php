<?php
/**
 * Model_Not_Found_Exception class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Support\Arr;

/**
 * Model Not Found Exception
 */
class Model_Not_Found_Exception extends Model_Exception {
	/**
	 * Name of the affected Eloquent model.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * The affected model IDs.
	 *
	 * @var int|array
	 */
	protected $ids;

	/**
	 * Set the affected Eloquent model and instance ids.
	 *
	 * @param string    $model Model name.
	 * @param int|array $ids Model ID(s).
	 * @return static
	 */
	public function set_model( string $model, $ids = [] ) {
		$this->model = $model;
		$this->ids   = Arr::wrap( $ids );

		$this->message = "No query results for model [{$model}]";

		if ( count( $this->ids ) > 0 ) {
			$this->message .= ' ' . implode( ', ', $this->ids );
		} else {
			$this->message .= '.';
		}

		return $this;
	}

	/**
	 * Get the affected Eloquent model.
	 *
	 * @return string
	 */
	public function get_model(): string {
		return $this->model;
	}

	/**
	 * Get the affected Eloquent model IDs.
	 *
	 * @return int|array
	 */
	public function get_ids() {
		return $this->ids;
	}
}
