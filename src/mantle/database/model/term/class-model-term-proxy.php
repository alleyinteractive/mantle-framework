<?php
/**
 * Model_Term_Proxy class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Term;

use Mantle\Database\Model\Post;
use RuntimeException;

/**
 * Allow term to be retrieve as an attribute on the object.
 */
class Model_Term_Proxy {
	/**
	 * Model to retrieve meta from.
	 *
	 * @var Post
	 */
	protected Post $model;

	/**
	 * Constructor.
	 *
	 * @param Post $model Model to reference.
	 */
	public function __construct( Post $model ) {
		$this->model = $model;
	}

	/**
	 * Retrieve model terms by taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return mixed
	 */
	public function __get( string $taxonomy ) {
		$queued_value = $this->model->get_queued_term_attribute( $taxonomy );

		if ( null !== $queued_value ) {
			return $queued_value;
		}

		return $this->model->get_terms( $taxonomy );
	}

	/**
	 * Set model term.
	 *
	 * @param string $taxonomy Taxonomy name..
	 * @param mixed  $value Meta value.
	 */
	public function __set( string $taxonomy, $value ) {
		$this->model->queue_term_attribute( $taxonomy, $value );
	}

	/**
	 * Delete model terms.
	 *
	 * @throws RuntimeException Thrown on usage.
	 * @param string $key Taxonomy key.
	 */
	public function __unset( string $key ) {
		throw new RuntimeException( 'Deleting model terms by attribute is not supported.' );
	}
}
