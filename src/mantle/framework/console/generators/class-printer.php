<?php
/**
 * Printer class file.
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\GlobalFunction;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\TraitType;

use function Mantle\Support\Helpers\str;

/**
 * Generates PHP code compatible with WordPress coding standards.
 */
class Printer extends \Nette\PhpGenerator\Printer {
	/**
	 * Print the file
	 *
	 * @inheritDoc
	 */
	public function printFile( PhpFile $file ): string {
		return str(
			str_replace(
				[
					"<?php\n\n",
					'(  ) {',
					'(  ):',
				],
				[
					"<?php\n",
					'() {',
					'():',
				],
				parent::printFile( $file ),
			) 
		)->rtrim( PHP_EOL )->append( PHP_EOL );
	}

	/**
	 * Print the class.
	 *
	 * @inheritDoc
	 */
	public function printClass( ClassType|InterfaceType|TraitType|EnumType $class, ?PhpNamespace $namespace = null ): string {
		return trim(
			str_replace(
				"\n{\n",
				" {\n",
				parent::printClass( $class, $namespace ),
			)
		);
	}

	/**
	 * Print the class method.
	 *
	 * @inheritDoc
	 */
	public function printMethod( Method $method, ?PhpNamespace $namespace = null, bool $isInterface = false ): string {
		$abstract = $method->isAbstract();
		$method   = parent::printMethod( $method, $namespace, $isInterface );
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

	/**
	 * Print a single function.
	 *
	 * @inheritDoc
	 */
	public function printFunction( GlobalFunction $function, ?PhpNamespace $namespace = null ): string {
		$function = parent::printFunction( $function, $namespace );
		$lines    = explode( "\n", $function );

		foreach ( $lines as $i => $line ) {
			if ( ! str_starts_with( $line, 'function ' ) ) {
				continue;
			}

			$lines[ $i ] = str_replace(
				[
					'(',
					')',
				],
				[
					'( ',
					' )',
				],
				$lines[ $i ],
			);
			break;
		}

		return str_replace(
			[
				'(  )',
				"\n{\n\t",
			],
			[
				'()',
				" {\n\t",
			],
			implode( "\n", $lines ),
		);
	}
}
