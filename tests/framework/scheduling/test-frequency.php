<?php

namespace Illuminate\Tests\Console\Scheduling;

use Mantle\Scheduling\Event;
use Mantle\Testing\Framework_Test_Case;
use Mockery as m;

class Test_Frequency extends Framework_Test_Case {

	/*
	 * @var Event
	 */
	protected $event;

	protected function setUp(): void {
		$this->event = new Event(
			'php foo'
		);
	}

	public function testEveryMinute() {
		$this->assertSame( '* * * * *', $this->event->get_expression() );
		$this->assertSame( '* * * * *', $this->event->everyMinute()->get_expression() );
	}

	public function testEveryXMinutes() {
		$this->assertSame( '*/2 * * * *', $this->event->everyTwoMinutes()->get_expression() );
		$this->assertSame( '*/3 * * * *', $this->event->everyThreeMinutes()->get_expression() );
		$this->assertSame( '*/4 * * * *', $this->event->everyFourMinutes()->get_expression() );
		$this->assertSame( '*/5 * * * *', $this->event->everyFiveMinutes()->get_expression() );
	}

	public function testDaily() {
		$this->assertSame( '0 0 * * *', $this->event->daily()->get_expression() );
	}

	public function testTwiceDaily() {
		$this->assertSame( '0 3,15 * * *', $this->event->twiceDaily( 3, 15 )->get_expression() );
	}

	public function testOverrideWithHourly() {
		$this->assertSame( '0 * * * *', $this->event->everyFiveMinutes()->hourly()->get_expression() );
		$this->assertSame( '37 * * * *', $this->event->hourlyAt( 37 )->get_expression() );
		$this->assertSame( '15,30,45 * * * *', $this->event->hourlyAt( array( 15, 30, 45 ) )->get_expression() );
	}

	public function testHourly() {
		$this->assertSame( '0 */2 * * *', $this->event->everyTwoHours()->get_expression() );
		$this->assertSame( '0 */3 * * *', $this->event->everyThreeHours()->get_expression() );
		$this->assertSame( '0 */4 * * *', $this->event->everyFourHours()->get_expression() );
		$this->assertSame( '0 */6 * * *', $this->event->everySixHours()->get_expression() );
	}

	public function testMonthlyOn() {
		$this->assertSame( '0 15 4 * *', $this->event->monthlyOn( 4, '15:00' )->get_expression() );
	}

	public function testTwiceMonthly() {
		$this->assertSame( '0 0 1,16 * *', $this->event->twiceMonthly( 1, 16 )->get_expression() );
	}

	public function testMonthlyOnWithMinutes() {
		$this->assertSame( '15 15 4 * *', $this->event->monthlyOn( 4, '15:15' )->get_expression() );
	}

	public function testWeekdaysDaily() {
		$this->assertSame( '0 0 * * 1-5', $this->event->weekdays()->daily()->get_expression() );
	}

	public function testWeekdaysHourly() {
		$this->assertSame( '0 * * * 1-5', $this->event->weekdays()->hourly()->get_expression() );
	}

	public function testWeekdays() {
		$this->assertSame( '* * * * 1-5', $this->event->weekdays()->get_expression() );
	}

	public function testSundays() {
		$this->assertSame( '* * * * 0', $this->event->sundays()->get_expression() );
	}

	public function testMondays() {
		$this->assertSame( '* * * * 1', $this->event->mondays()->get_expression() );
	}

	public function testTuesdays() {
		$this->assertSame( '* * * * 2', $this->event->tuesdays()->get_expression() );
	}

	public function testWednesdays() {
		$this->assertSame( '* * * * 3', $this->event->wednesdays()->get_expression() );
	}

	public function testThursdays() {
		$this->assertSame( '* * * * 4', $this->event->thursdays()->get_expression() );
	}

	public function testFridays() {
		$this->assertSame( '* * * * 5', $this->event->fridays()->get_expression() );
	}

	public function testSaturdays() {
		$this->assertSame( '* * * * 6', $this->event->saturdays()->get_expression() );
	}

	public function testQuarterly() {
		$this->assertSame( '0 0 1 1-12/3 *', $this->event->quarterly()->get_expression() );
	}

	public function testFrequencyMacro() {
		Event::macro(
			'everyXMinutes',
			function ( $x ) {
				return $this->spliceIntoPosition( 1, "*/{$x}" );
			}
		);

		$this->assertSame( '*/6 * * * *', $this->event->everyXMinutes( 6 )->get_expression() );
	}
}
