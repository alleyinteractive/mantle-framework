<?php
namespace Mantle\Tests;

use Mantle\Framework\Application;
use Mockery as m;

class Test_Application extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	public function test_environment() {
		$app = new Application();

		$_ENV['env'] = 'test-env';
		$this->assertEquals( 'test-env', $app->environment() );

		$_ENV['env'] = 'another-test-env';
		$this->assertEquals( 'another-test-env', $app->environment() );
	}

	public function test_is_environment() {
		$_ENV['env'] = 'test-env';
		$app         = new Application();

		$this->assertTrue( $app->is_environment( 'test-env', 'another-thing' ) );
		$this->assertTrue( $app->is_environment( 'test-env' ) );
		$this->assertFalse( $app->is_environment( 'not-the-correct-env' ) );
	}
}
