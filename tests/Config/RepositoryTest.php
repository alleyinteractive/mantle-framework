<?php
namespace Mantle\Tests\Support;

use Mantle\Config\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase {
	protected Repository $repository;

	/**
	 * Setup the unit test.
	 */
	protected function setUp(): void {
		$this->repository = new Repository(
			[
				'foo' => 'bar',
				'test' => 'value',
				'null' => null,
				'parent' => [
					'child' => 'child-value',
				],
			]
		);
	}

	public function test_has() {
		$this->assertTrue( $this->repository->has( 'foo' ) );
		$this->assertFalse( $this->repository->has( 'nope' ) );
	}

	public function test_get() {
		$this->assertSame( 'bar', $this->repository->get( 'foo' ) );
		$this->assertSame( 'bar', $this->repository['foo'] );

		$this->assertSame( 'child-value', $this->repository->get( 'parent.child' ) );
		$this->assertSame( 'child-value', $this->repository['parent.child'] );
	}

	public function test_set() {
		$this->assertNull( $this->repository->get( 'test-item' ) );
		$this->repository->set( 'test-item', 'the-value' );
		$this->assertSame( 'the-value', $this->repository->get( 'test-item' ) );

		$this->assertNull( $this->repository->get( 'parent.new_child' ) );
		$this->repository->set( 'parent.new_child', 'new-child-value' );
		$this->assertSame( 'new-child-value', $this->repository->get( 'parent.new_child' ) );

		// Check getting the whole parent.
		$this->assertSame(
			[
				'child'     => 'child-value',
				'new_child' => 'new-child-value'
			],
			$this->repository->get( 'parent' )
		);
	}

	public function test_unset() {
		$this->assertTrue( $this->repository->has( 'foo' ) );
		unset( $this->repository['foo'] );
		$this->assertNull( $this->repository->get( 'foo' ) );
	}
}
