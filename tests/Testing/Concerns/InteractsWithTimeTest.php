<?php

namespace Mantle\Tests\Testing\Concerns;

use DateTime;
use Mantle\Support\Carbon;
use Mantle\Testing\FrameworkTestCase;

use function Mantle\Support\Helpers\now;

class InteractsWithTimeTest extends FrameworkTestCase {

	public function test_uses_proper_time_by_default(): void {
		$this->assertEquals(
			( new DateTime( 'now' ) )->format( 'Y-m-d H:i' ),
			Carbon::now()->format( 'Y-m-d H:i' ),
		);
	}

	public function test_can_travel_back_in_time(): void {
		$ref = now()->subDays()->setTime( 9, 0 );

		$this->travel( $ref );

		$this->assertEquals(
			$ref->format( 'Y-m-d H:i' ),
			Carbon::now()->format( 'Y-m-d H:i' ),
		);

		$this->assertEquals(
			$ref->format( 'Y-m-d H:i' ),
			now()->format( 'Y-m-d H:i' ),
		);
	}

	public function test_it_can_travel_time_for_closure(): void {
		$ref = now()->addDays()->setTime( 15, 30 );

		$this->travel_to( $ref, function () use ( $ref ) {
			$this->assertEquals(
				$ref->format( 'Y-m-d H:i' ),
				Carbon::now()->format( 'Y-m-d H:i' ),
			);

			$this->assertEquals(
				$ref->format( 'Y-m-d H:i' ),
				now()->format( 'Y-m-d H:i' ),
			);
		} );

		$this->assertNotEquals(
			$ref->format( 'Y-m-d H:i' ),
			Carbon::now()->format( 'Y-m-d H:i' ),
		);

		$this->assertNotEquals(
			$ref->format( 'Y-m-d H:i' ),
			now()->format( 'Y-m-d H:i' ),
		);
	}

	public function test_previous_time_modifications_are_cleaned_up(): void {
		$this->assertEquals(
			( new DateTime( 'now' ) )->format( 'Y-m-d H:i' ),
			Carbon::now()->format( 'Y-m-d H:i' ),
		);
	}
}
