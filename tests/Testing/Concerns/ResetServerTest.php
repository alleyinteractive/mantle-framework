<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Concerns\Reset_Server;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Utils;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group testing
 */
#[Group( 'testing' )]
class ResetServerTest extends Framework_Test_Case {
	use Reset_Server;

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		Utils::reset_server();
	}

	public function test_modify_server_http_host() {
		$this->assertSame( WP_TESTS_DOMAIN, $_SERVER['HTTP_HOST'] );

		$_SERVER['HTTP_HOST'] = 'other.org';

		$this->assertSame( 'other.org', $_SERVER['HTTP_HOST'] );
	}

	public function test_modify_server_request_uri() {
		$this->assertEquals( WP_TESTS_DOMAIN, $_SERVER['HTTP_HOST'] );
	}
}
