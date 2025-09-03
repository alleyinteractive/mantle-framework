<?php
namespace Mantle\Tests\Support;

use Mantle\Support\Memoize;
use PHPUnit\Framework\TestCase;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\memo;

class MemoizeTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		Memoize::enable();
		Memoize::flush();
	}

	public function test_it_can_memoize(): void {
		$values = [];
		for ( $i = 0; $i < 3; $i++ ) {
			$values[] = memo( fn () => rand( 1, PHP_INT_MAX ) );
		};

		$this->assertCount( 1, collect( $values )->unique()->all() );

		// Ensure it is unique across multiple calls.
		$values_2 = [];
		for ( $i = 0; $i < 3; $i++ ) {
			$values_2[] = memo( fn () => rand( 1, PHP_INT_MAX ) );
		};

		$this->assertCount( 1, collect( $values_2 )->unique()->all() );
		$this->assertNotEquals( $values[0], $values_2[0] );
	}

	public function test_it_can_memoize_with_dependencies(): void {
		$values  = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$values[] = memo(
				fn () => rand( 1, PHP_INT_MAX ),
				// This will result in two unique values as dependencies.
				[ $i % 2 ],
			);
		};

		$values = collect( $values )->unique()->values()->all();

		$this->assertCount( 2, $values );
		$this->assertNotEquals( $values[0], $values[1] );
	}

	public function test_it_can_disable_memoization(): void {
		Memoize::disable();

		$values = [];
		for ( $i = 0; $i < 3; $i++ ) {
			$values[] = memo( fn () => rand( 1, PHP_INT_MAX ) );
		};

		$this->assertCount( 3, collect( $values )->unique()->all() );

		Memoize::enable();

		$values_2 = [];
		for ( $i = 0; $i < 3; $i++ ) {
			$values_2[] = memo( fn () => rand( 1, PHP_INT_MAX ) );
		};

		$this->assertCount( 1, collect( $values_2 )->unique()->all() );
	}

	public function test_it_can_flush_memoization(): void {
		$callable = function (): array {
			$values = [];
			for ( $i = 0; $i < 3; $i++ ) {
				$values[] = memo( fn () => rand( 1, PHP_INT_MAX ) );
			};

			return $values;
		};

		$values = $callable();

		$this->assertCount( 1, collect( $values )->unique()->all() );

		Memoize::flush();

		$values_2 = $callable();

		$this->assertCount( 1, collect( $values_2 )->unique()->all() );
		$this->assertNotEquals( $values[0], $values_2[0] );
	}
}
