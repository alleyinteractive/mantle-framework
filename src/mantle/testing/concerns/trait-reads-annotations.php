<?php
/**
 * Reads_Annotations trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use PHPUnit\Metadata\Annotation\Parser\DocBlock;
use PHPUnit\Metadata\Annotation\Parser\Registry;
use PHPUnit\Util\Test;

/**
 * Read annotations for testing that supports multiple versions of PHPUnit.
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait Reads_Annotations {
	/**
	 * Read annotations for the current test case and method.
	 *
	 * @return array
	 */
	public function get_annotations_for_method(): array {
		// PHPUnit 9.4 and below method.
		if ( method_exists( $this, 'getAnnotations' ) ) {
			return $this->getAnnotations();
		}

		// Use the PHPUnit ^9.5 method if available.
		if ( method_exists( Test::class, 'parseTestMethodAnnotations' ) ) {
			return Test::parseTestMethodAnnotations(
				static::class,
				$this->getName()
			);
		}

		// Use the PHPUnit 10.x method if available.
		if ( class_exists( Registry::class ) && class_exists( DocBlock::class ) ) {
			$registry = Registry::getInstance();

			return [
				'class'  => $registry->forClassName( static::class )->symbolAnnotations(),
				'method' => $registry->forMethod( static::class, $this->name() )->symbolAnnotations(),
			];
		}

		// Throw a warning if we can't read annotations.
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			'Unable to read annotations for test method. Please file an issue with https://github.com/alleyinteractive/mantle-framework',
			E_USER_WARNING
		);

		return [];
	}
}
