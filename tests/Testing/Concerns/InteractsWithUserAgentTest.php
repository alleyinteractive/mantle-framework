<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Attributes\UserAgent;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group testing
 */
#[Group( 'testing' )]
class InteractsWithUserAgentTest extends FrameworkTestCase {
	public function test_normal_user_agent(): void {
		$this->assertEmpty( $_SERVER['HTTP_USER_AGENT'] ?? '' );
		$this->get( '/' );
		$this->assertEmpty( $_SERVER['HTTP_USER_AGENT'] ?? '' );

		$this->assertFalse( wp_is_mobile() );
	}

	#[UserAgent( UserAgent::MOBILE )]
	public function test_mobile_user_agent(): void {
		$captured_user_agent = null;

		$this->assertEquals( UserAgent::MOBILE, $_SERVER['HTTP_USER_AGENT'] ?? '' );

		$this->assertTrue( wp_is_mobile() );

		$this->before_request( function() use ( &$captured_user_agent ) {
			$captured_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		} );

		$this->get( '/' );

		$this->assertEquals( UserAgent::MOBILE, $captured_user_agent );
	}

	#[UserAgent( UserAgent::TABLET )]
	public function test_tablet_user_agent(): void {
		$this->assertEquals( UserAgent::TABLET, $_SERVER['HTTP_USER_AGENT'] ?? '' );

		$this->assertTrue( wp_is_mobile() );
	}

	#[UserAgent( UserAgent::DESKTOP )]
	public function test_desktop_user_agent(): void {
		$this->assertEquals( UserAgent::DESKTOP, $_SERVER['HTTP_USER_AGENT'] ?? '' );

		$this->assertFalse( wp_is_mobile() );
	}
}
