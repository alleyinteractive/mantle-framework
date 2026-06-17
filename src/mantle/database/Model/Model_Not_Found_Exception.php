<?php
/**
 * Model_Not_Found_Exception class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Database\Model\Model;
use Mantle\Support\Arr;

/**
 * Model Not Found Exception
 */
class Model_Not_Found_Exception extends Model_Exception {
	/**
	 * Constructor.
	 *
	 * @param array<class-string<Model>>|string $model Name of the affected Eloquent model(s).
	 * @param array<mixed>                      $ids Model ID(s).
	 */
	public function __construct( public array|string $model, public array $ids = [] ) {
		$this->set_message();
	}

	/**
	 * Set the affected Eloquent model and instance ids.
	 *
	 * @param array<class-string<Model>>|string $model Name of the affected Eloquent model(s).
	 * @param array<mixed>                      $ids Model ID(s).
	 */
	public function set_model( array|string $model, array $ids = [] ): static {
		$this->model = $model;
		$this->ids   = Arr::wrap( $ids );

		$this->set_message();

		return $this;
	}

	/**
	 * Get the affected Eloquent model.
	 *
	 * @return array<class-string<Model>>|string
	 */
	public function get_model(): array|string {
		return $this->model;
	}

	/**
	 * Get the affected Eloquent model IDs.
	 *
	 * @return array<mixed>
	 */
	public function get_ids(): array {
		return $this->ids;
	}

	/**
	 * Set the exception message.
	 */
	protected function set_message(): void {
		$model = is_array( $this->model ) ? implode( ', ', $this->model ) : $this->model;

		$this->message = "No query results for model [{$model}]";

		if ( $this->ids !== [] ) {
			$this->message .= ' ' . implode( ', ', $this->ids );
		} else {
			$this->message .= '.';
		}
	}
}
