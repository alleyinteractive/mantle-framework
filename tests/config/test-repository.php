<?php
namespace Mantle\Tests\Support;

use Mantle\Config\Repository;
use PHPUnit\Framework\TestCase;

class Test_Repository extends TestCase {
	/**
	 * @var Repository
	 */
	protected $repository;

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
		$this->assertEquals( 'bar', $this->repository->get( 'foo' ) );
		$this->assertEquals( 'bar', $this->repository['foo'] );

		$this->assertEquals( 'child-value', $this->repository->get( 'parent.child' ) );
		$this->assertEquals( 'child-value', $this->repository['parent.child'] );
	}

	public function test_set() {
		$this->assertNull( $this->repository->get( 'test-item' ) );
		$this->repository->set( 'test-item', 'the-value' );
		$this->assertEquals( 'the-value', $this->repository->get( 'test-item' ) );

		$this->assertNull( $this->repository->get( 'parent.new_child' ) );
		$this->repository->set( 'parent.new_child', 'new-child-value' );
		$this->assertEquals( 'new-child-value', $this->repository->get( 'parent.new_child' ) );

		// Check getting the whole parent.
		$this->assertEquals(
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
