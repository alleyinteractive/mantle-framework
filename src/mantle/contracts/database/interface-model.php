<?php
/**
 * Model interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Database;

/**
 * Model Contract
 */
interface Model {
	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object = [] );
}
