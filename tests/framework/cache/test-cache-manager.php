<?php
namespace Mantle\Tests\Framework\Cache;

use InvalidArgumentException;
use Mantle\Framework\Cache\Cache_Manager;
use Mantle\Framework\Facade\Cache;
use Mantle\Framework\Testing\Framework_Test_Case;

class Test_Cache_Manager extends Framework_Test_Case {
	public function test_unconfigured_store() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Driver not specified for [invalid-store].' );

		Cache::store( 'invalid-store' );
	}

	public function test_unknown_driver() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Driver [unknown-driver] not supported.' );

		Cache::store( 'unknown-driver' );
	}

	public function test_wordpress_driver() {
		$this->assertFalse( Cache::has( 'cache-key' ) );
		$this->assertEquals( 'default', Cache::get( 'cache-key', 'default' ) );
		$this->assertTrue( Cache::put( 'cache-key', 'cache-value' ) );
		$this->assertEquals( 'cache-value', Cache::get( 'cache-key', 'default' ) );
	}
}
