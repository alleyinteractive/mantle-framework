<?php
/**
 * Assertions trait file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Support\HTML;

use PHPUnit\Framework\Assert;

/**
 * Assertions for the HTML class.
 *
 * @mixin \Mantle\Support\HTML
 */
trait Assertions {
	/**
	 * Assert that the node has all of the specified classes.
	 *
	 * @param string ...$class Class names.
	 */
	public function assertHasClass( string ...$class ): static {
		Assert::assertTrue( $this->has_class( ...$class ), sprintf(
			'Failed asserting that the node has class(es): %s',
			implode( ', ', $class )
		) );

		return $this;
	}

	/**
	 * Assert that the node does not have all of the specified classes.
	 *
	 * @param string ...$class Class names.
	 */
	public function assertNotHasClass( string ...$class ): static {
		Assert::assertFalse( $this->has_class( ...$class ), sprintf(
			'Failed asserting that the node does not have class(es): %s',
			implode( ', ', $class )
		) );

		return $this;
	}

	/**
	 * Assert that the node has any of the specified classes.
	 *
	 * @param string ...$class Class names.
	 */
	public function assertHasAnyClass( string ...$class ): static {
		Assert::assertTrue( $this->has_any_class( ...$class ), sprintf(
			'Failed asserting that the node has any of the class(es): %s',
			implode( ', ', $class )
		) );

		return $this;
	}

	/**
	 * Assert that the node has none of the specified classes.
	 *
	 * @param string ...$class Class names.
	 */
	public function assertNotHasAnyClass( string ...$class ): static {
		Assert::assertFalse( $this->has_any_class( ...$class ), sprintf(
			'Failed asserting that the node has none of the class(es): %s',
			implode( ', ', $class )
		) );

		return $this;
	}

	/**
	 * Assert that the node has all of the specified classes.
	 *
	 * @deprecated Use assertHasClass() instead.
	 * @param string ...$class Class names.
	 */
	public function assertNodeHasClass( string ...$class ): static {
		return $this->assertHasClass( ...$class );
	}

	/**
	 * Assert that the node has any of the specified classes.
	 *
	 * @deprecated Use assertHasAnyClass() instead.
	 * @param string ...$class Class names.
	 */
	public function assertNodeHasAnyClass( string ...$class ): static {
		return $this->assertHasAnyClass( ...$class );
	}

	/**
	 * Assert that the current filter has the specified number of nodes.
	 *
	 * @param int|null $count Expected number of nodes. If null, only checks if any nodes exist.
	 */
	public function assertHasNodes( ?int $count = null ): static {
		if ( null === $count ) {
			Assert::assertTrue( $this->count() > 0, 'Failed asserting that the node has children.' );
		} else {
			Assert::assertEquals( $count, $this->count(), sprintf(
				'Failed asserting that the node has %d children, found %d.',
				$count,
				$this->count()
			) );
		}

		return $this;
	}

	/**
	 * Assert that the node has children matching the specified selector.
	 *
	 * @param string|null $selector CSS selector to match children against.
	 * @param int|null    $count    Expected number of children. If null, only checks if any children exist.
	 */
	public function assertHasChildren( ?string $selector = null, ?int $count = null ): static {
		if ( null === $count ) {
			Assert::assertTrue( count( $this->children( $selector ) ) > 0, 'Failed asserting that the node has children.' );
		} else {
			Assert::assertEquals( $count, count( $this->children( $selector ) ), sprintf(
				'Failed asserting that the node has %d children, found %d.',
				$count,
				count( $this->children( $selector ) )
			) );
		}

		return $this;
	}
}
