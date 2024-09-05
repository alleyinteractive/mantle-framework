<?php
namespace Mantle\Tests\Cache;

use InvalidArgumentException;
use Mantle\Facade\Cache;
use Mantle\Testing\Framework_Test_Case;

class WordPressCacheRepositoryTest extends Framework_Test_Case {
	public function test_cache() {
		$this->assertFalse( Cache::has( 'cache-key' ) );
		$this->assertEquals( 'default', Cache::get( 'cache-key', 'default' ) );
		$this->assertTrue( Cache::put( 'cache-key', 'cache-value' ) );
		$this->assertEquals( 'cache-value', Cache::get( 'cache-key', 'default' ) );

		$this->assertNull( Cache::get( 'sear-key' ) );
		Cache::sear( 'sear-key', fn () => 'sear-value' );
		$this->assertEquals( 'sear-value', Cache::get( 'sear-key' ) );

		$this->assertNull( Cache::get( 'remember-key' ) );
		Cache::remember( 'remember-key', 3600, fn () => 'remember-value' );
		$this->assertEquals( 'remember-value', Cache::get( 'remember-key' ) );

		$this->assertEmpty( Cache::get( 'pull-key' ) );
		Cache::put( 'pull-key', 'pull-value' );
		$this->assertEquals( 'pull-value', Cache::pull( 'pull-key' ) );
		$this->assertEmpty( Cache::get( 'pull-key' ) );
	}

	public function test_multiple() {
		$keys = [ 'key1', 'key2' ];
		$this->assertEquals( [ 'key1' => null, 'key2' => null ], Cache::get_multiple( $keys ) );

		$this->assertTrue( Cache::set_multiple( [ 'key1' => 'value1', 'key2' => 'value2' ] ) );
		$this->assertEquals( [ 'key1' => 'value1', 'key2' => 'value2' ], Cache::get_multiple( $keys ) );

		$this->assertTrue( Cache::delete_multiple( $keys ) );
		$this->assertEquals( [ 'key1' => null, 'key2' => null ], Cache::get_multiple( $keys ) );
	}

	public function test_increment_decrement() {
		$this->assertEmpty( Cache::get( 'increment' ) );

		$this->assertFalse( Cache::increment( 'increment' ) );

		Cache::set( 'increment', 0 );
		$this->assertEquals( 1, Cache::increment( 'increment' ) );
		$this->assertEquals( 2, Cache::increment( 'increment' ) );
		$this->assertEquals( 1, Cache::decrement( 'increment' ) );
		$this->assertEquals( 0, Cache::decrement( 'increment' ) );
	}

	public function test_tags() {
		$tags = Cache::tags('prefix');

		$tags->set( 'key1', 'value1' );

		$this->assertEquals( 'value1', $tags->get( 'key1' ) );

		// Check the underlying object cache.
		$this->assertEquals( 'value1', wp_cache_get( 'key1', 'prefix' ) );
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
}
