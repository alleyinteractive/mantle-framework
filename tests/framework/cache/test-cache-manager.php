<?php
namespace Mantle\Tests\Framework\Cache;

use InvalidArgumentException;
use Mantle\Cache\Cache_Manager;
use Mantle\Framework\Facade\Cache;
use Mantle\Framework\Testing\Framework_Test_Case;
use Predis\Connection\ConnectionException;

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

	public function test_array_driver() {
		$this->assertFalse( Cache::store( 'array' )->has( 'cache-key' ) );
		$this->assertEquals( 'default', Cache::store( 'array' )->get( 'cache-key', 'default' ) );
		$this->assertTrue( Cache::store( 'array' )->put( 'cache-key', 'cache-value' ) );
		$this->assertEquals( 'cache-value', Cache::store( 'array' )->get( 'cache-key', 'default' ) );
	}

	public function test_cache_helper() {
		$key = 'cache-helper-' . wp_rand();
		$this->assertNull( cache( $key ) );
		$this->assertTrue( cache( [ $key => 'cache-value' ], 3600 ) );
		$this->assertEquals( 'cache-value', cache( $key ) );

		cache()->remember( 'remember-key', 3600, function() {
			return 'cache-value';
		} );

		$this->assertEquals( 'cache-value', cache( 'remember-key' ) );
	}

	public function test_redis_driver() {
		if ( ! class_exists( 'Predis\Client' ) ) {
			$this->markTestSkipped( 'Redis not loaded. ');
			return;
		}

		$key = 'test_key_' . wp_rand();

		try {
			$this->assertFalse( Cache::store( 'redis' )->has( $key ) );
			$this->assertEquals( 'default', Cache::store( 'redis' )->get( $key, 'default' ) );
			$this->assertNotEmpty( Cache::store( 'redis' )->put( $key, 'cache-value' ) );
			$this->assertEquals( 'cache-value', Cache::store( 'redis' )->get( $key, 'default' ) );

			Cache::store( 'redis' )->clear();
		} catch ( ConnectionException $e ) {
			unset( $e );
			$this->markTestSkipped( 'Redis not connected.' );
		}
	}
}
