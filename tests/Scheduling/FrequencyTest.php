<?php

namespace Mantle\Tests\Scheduling;

use Mantle\Scheduling\Event;
use Mantle\Testing\FrameworkTestCase;
use Mockery as m;

class FrequencyTest extends FrameworkTestCase {

	/*
	 * @var Event
	 */
	protected Event $event;

	protected function setUp(): void {
		parent::setUp();

		$this->event = new Event(
			'php foo'
		);
	}

	public function testEveryMinute(): void {
		$this->assertSame( '* * * * *', $this->event->get_expression() );
		$this->assertSame( '* * * * *', $this->event->everyMinute()->get_expression() );
	}

	public function testEveryXMinutes(): void {
		$this->assertSame( '*/2 * * * *', $this->event->everyTwoMinutes()->get_expression() );
		$this->assertSame( '*/3 * * * *', $this->event->everyThreeMinutes()->get_expression() );
		$this->assertSame( '*/4 * * * *', $this->event->everyFourMinutes()->get_expression() );
		$this->assertSame( '*/5 * * * *', $this->event->everyFiveMinutes()->get_expression() );
	}

	public function testDaily(): void {
		$this->assertSame( '0 0 * * *', $this->event->daily()->get_expression() );
	}

	public function testTwiceDaily(): void {
		$this->assertSame( '0 3,15 * * *', $this->event->twiceDaily( 3, 15 )->get_expression() );
	}

	public function testOverrideWithHourly(): void {
		$this->assertSame( '0 * * * *', $this->event->everyFiveMinutes()->hourly()->get_expression() );
		$this->assertSame( '37 * * * *', $this->event->hourlyAt( 37 )->get_expression() );
		$this->assertSame( '15,30,45 * * * *', $this->event->hourlyAt( array( 15, 30, 45 ) )->get_expression() );
	}

	public function testHourly(): void {
		$this->assertSame( '0 */2 * * *', $this->event->everyTwoHours()->get_expression() );
		$this->assertSame( '0 */3 * * *', $this->event->everyThreeHours()->get_expression() );
		$this->assertSame( '0 */4 * * *', $this->event->everyFourHours()->get_expression() );
		$this->assertSame( '0 */6 * * *', $this->event->everySixHours()->get_expression() );
	}

	public function testMonthlyOn(): void {
		$this->assertSame( '0 15 4 * *', $this->event->monthlyOn( 4, '15:00' )->get_expression() );
	}

	public function testTwiceMonthly(): void {
		$this->assertSame( '0 0 1,16 * *', $this->event->twiceMonthly( 1, 16 )->get_expression() );
	}

	public function testMonthlyOnWithMinutes(): void {
		$this->assertSame( '15 15 4 * *', $this->event->monthlyOn( 4, '15:15' )->get_expression() );
	}

	public function testWeekdaysDaily(): void {
		$this->assertSame( '0 0 * * 1-5', $this->event->weekdays()->daily()->get_expression() );
	}

	public function testWeekdaysHourly(): void {
		$this->assertSame( '0 * * * 1-5', $this->event->weekdays()->hourly()->get_expression() );
	}

	public function testWeekdays(): void {
		$this->assertSame( '* * * * 1-5', $this->event->weekdays()->get_expression() );
	}

	public function testSundays(): void {
		$this->assertSame( '* * * * 0', $this->event->sundays()->get_expression() );
	}

	public function testMondays(): void {
		$this->assertSame( '* * * * 1', $this->event->mondays()->get_expression() );
	}

	public function testTuesdays(): void {
		$this->assertSame( '* * * * 2', $this->event->tuesdays()->get_expression() );
	}

	public function testWednesdays(): void {
		$this->assertSame( '* * * * 3', $this->event->wednesdays()->get_expression() );
	}

	public function testThursdays(): void {
		$this->assertSame( '* * * * 4', $this->event->thursdays()->get_expression() );
	}

	public function testFridays(): void {
		$this->assertSame( '* * * * 5', $this->event->fridays()->get_expression() );
	}

	public function testSaturdays(): void {
		$this->assertSame( '* * * * 6', $this->event->saturdays()->get_expression() );
	}

	public function testQuarterly(): void {
		$this->assertSame( '0 0 1 1-12/3 *', $this->event->quarterly()->get_expression() );
	}

	public function testFrequencyMacro(): void {
		Event::macro(
			'everyXMinutes',
			function ( $x ) {
				return $this->splice_into_position( 1, "*/{$x}" );
			}
		);

		$this->assertSame( '*/6 * * * *', $this->event->everyXMinutes( 6 )->get_expression() ); // @phpstan-ignore method.notFound (macro resolved via __call)
	}
}
