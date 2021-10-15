<?php
/**
 * Printer class file.
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;

/**
 * Generates PHP code compatible with WordPress coding standards.
 */
class Printer extends \Nette\PhpGenerator\Printer {
	/**
	 * Print the class.
	 *
	 * @inheritDoc
	 */
	public function printClass( ClassType $class, PhpNamespace $namespace = null ): string {
		return str_replace(
			"\n{\n",
			" {\n",
			parent::printClass( $class, $namespace ),
		);
	}

	/**
	 * Print the class method.
	 *
	 * @inheritDoc
	 */
	public function printMethod( Method $method, PhpNamespace $namespace = null ): string {
		$abstract = $method->isAbstract();
		$method   = parent::printMethod( $method, $namespace );
		$lines    = explode( "\n", $method );

		foreach ( $lines as $i => $line ) {
			if ( false === strpos( $line, ' function ' ) ) {
				continue;
			}

			$lines[ $i ] = str_replace(
				[ '(', ')' ],
				[ '( ', ' )' ],
				$lines[ $i ],
			);
		}

		$method = implode( "\n", $lines );

		if ( $abstract ) {
			return $method;
		}

		// Move the method's opening bracket to the same line as the function.
		return str_replace( "\n{", ' {', $method );
	}
}
