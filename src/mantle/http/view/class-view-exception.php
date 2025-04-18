<?php
/**
 * View_Exception class file
 *
 * @package Mantle
 */

namespace Mantle\Http\View;

use RuntimeException;

/**
 * View Exception class.
 */
class View_Exception extends RuntimeException {
	/**
	 * Constructor.
	 *
	 * @param string $message Exception message.
	 * @param string $view  View name.
	 */
	public function __construct( string $message, public readonly string $view ) {
		parent::__construct( $message );
	}
}
