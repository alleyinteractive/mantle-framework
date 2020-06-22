<?php
/**
 * Test_View_Factory test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Framework\Http;

use Mantle\Framework\Config\Repository;
use Mantle\Framework\Facade\Facade;
use Mantle\Framework\Facade\View;
use Mantle\Framework\Http\Request;
use Mantle\Framework\Http\Routing\Redirector;
use Mantle\Framework\Http\Routing\Response_Factory;
use Mantle\Framework\Http\Routing\Url_Generator;
use Mantle\Framework\Providers\View_Service_Provider;
use Mantle\Framework\Service_Provider;
use Mockery as m;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Test_View_Factory extends \Mockery\Adapter\Phpunit\MockeryTestCase {

	public function setUp(): void {
		parent::setUp();
	}

	public function test_share_service_provider() {
		$app = app();
		$factory = $app['view'];
		$this->assertEquals( 'default-value', $factory->shared( 'test-to-share', 'default-value' ) );

	}
}

class Service_Provider_Sharing extends Service_Provider {
	public function boot() {
		View::share( 'test-to-share', 'the-value-to-compare' );
	}
}
