<?php
/**
 * CachedHttpClientTest test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Http_Client;

use Mantle\Http_Client\Cache_Middleware;
use Mantle\Http_Client\Factory;
use Mantle\Http_Client\Pending_Request;
use Mantle\Http_Client\Response;
use Mantle\Testing\FrameworkTestCase;

use function Mantle\Support\Helpers\collect;
use function Mantle\Testing\mock_http_response;

class CachedHttpClientTest extends FrameworkTestCase {
	protected Pending_Request $client;

	protected function setUp(): void {
		parent::setUp();

		$this->client = Factory::create()->cache();

		$this->prevent_stray_requests();

		remove_all_actions( 'shutdown' );
	}

	public function test_can_create_cached_client() {
		$this->assertInstanceOf( Pending_Request::class, $this->client );
		$this->assertTrue(
			collect( $this->client->get_middleware() )->contains( fn( $middleware ) => $middleware instanceof Cache_Middleware )
		);
	}

	public function test_it_can_make_http_request() {
		// $this->allow_stray_requests();
		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$response = $this->client->get( 'https://example.com' );

		$this->assertEquals( 'value', $response->json( 'example' ) );

		$response = $this->client->get( 'https://example.com' );

		$this->assertEquals( 'value', $response->json( 'example' ) );
		$this->assertTrue( $response->cached );

		$this->assertRequestCount( 1 );
	}

	public function test_it_can_detect_different_http_methods() {
		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$this->client->get( 'https://example.com' );
		$this->client->post( 'https://example.com' );

		$this->assertRequestCount( 2 );
	}

	public function test_it_can_detect_different_bodies() {
		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$this->client->post( 'https://example.com', [ 'body' => [ 'example' => 'value' ] ] );
		$this->client->post( 'https://example.com', [ 'body' => [ 'example' => 'value' ] ] );

		$this->assertRequestCount( 1 );

		$this->client->post( 'https://example.com', [ 'body' => [ 'example' => 'value2' ] ] );

		$this->assertRequestCount( 2 );
	}

	public function test_it_can_control_the_cache_ttl() {
		$_SERVER['__ttl_called'] = false;

		$this->client = Factory::create()->cache( function () {
			$_SERVER['__ttl_called'] = true;

			return DAY_IN_SECONDS;
		} );

		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$this->client->get( 'https://example.com' );

		$this->assertRequestCount( 1 );
		$this->assertTrue( $_SERVER['__ttl_called'] );
	}

	public function test_it_can_purge_cache() {
		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$this->client->get( 'https://example.com' );
		$this->client->get( 'https://example.com' );

		$this->assertRequestCount( 1 );

		$this->assertTrue( $this->client->url( 'https://example.com' )->purge() );

		$this->client->get( 'https://example.com' );

		$this->assertRequestCount( 2 );
	}

	public function test_it_can_cache_with_a_callback_as_ttl(): void {
		$this->client = Factory::create()->cache( function ( Pending_Request $request, Response $response ): int {
			$this->assertEquals( 'https://example.com', $request->url() );

			return HOUR_IN_SECONDS;
		} );

		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$this->client->get( 'https://example.com' );
		$this->client->get( 'https://example.com' );

		$this->assertRequestCount( 1 );
	}

	public function test_it_throws_an_exception_when_passing_an_invalid_ttl_callback(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->client = Factory::create()->cache( fn () => 'string' );

		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$this->client->get( 'https://example.com' );
	}

	public function test_it_can_use_flexible_cache(): void {
		$this->client = Factory::create()->cache_flexible(
			stale: now()->addHour(),
			expire: now()->addDay(),
		);

		$i = 0;

		$this->fake_request( function () use ( &$i ) {
			$i++;

			return mock_http_response()->with_json( [ 'request' => $i ] );
		} );

		$this->client->get( 'https://example.com' );

		$second_request = $this->client->get( 'https://example.com' );

		$this->assertRequestCount( 1 );
		$this->assertEquals( 1, $second_request->json( 'request' ) );

		// Fire the shutdown actions to confirm the deferred refresh does not happen.
		do_action( 'shutdown' );

		$this->assertRequestCount( 1 );
	}

	public function test_it_can_use_flexible_cache_while_stale(): void {
		$this->client = Factory::create()->cache_flexible(
			stale: now()->subMinute(),
			expire: now()->addDay(),
		);

		$i = 0;

		$this->fake_request( function () use ( &$i ) {
			$i++;

			return mock_http_response()->with_json( [ 'request' => $i ] );
		} );

		$this->client->get( 'https://example.com' );

		$second_request = $this->client->get( 'https://example.com' );

		$this->assertRequestCount( 1 );
		$this->assertEquals( 1, $second_request->json( 'request' ) );

		// Fire the shutdown actions to trigger the deferred refresh.
		do_action( 'shutdown' );

		// The deferred refresh should have happened and we're at 2 requests now.
		$this->assertRequestCount( 2 );
	}

	public function test_it_throws_an_exception_when_passing_a_lower_stale_time_than_expire_time(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Stale time must be less than expire time for flexible caching.' );

		$this->client = Factory::create()->cache_flexible(
			stale: now()->addDay(),
			expire: now()->addHour(),
		);

		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$this->client->get( 'https://example.com' );
	}

	public function test_it_can_cache_flexible_with_a_callback(): void {
		$stale_called  = false;
		$expire_called = false;

		$this->client = Factory::create()->cache_flexible(
			stale: function ( Pending_Request $request, Response $response ) use ( &$stale_called ): \DateTimeInterface {
				$stale_called = true;

				$this->assertEquals( 'https://example.com', $request->url() );

				return now()->addHour();
			},
			expire: function ( Pending_Request $request, Response $response ) use ( &$expire_called ): \DateTimeInterface {
				$expire_called = true;

				$this->assertEquals( 'https://example.com', $request->url() );

				return now()->addDay();
			},
		);

		$i = 0;

		$this->fake_request( function () use ( &$i ) {
			$i++;

			return mock_http_response()->with_json( [ 'request' => $i ] );
		} );

		$this->client->get( 'https://example.com' );
		$this->client->get( 'https://example.com' );

		$this->assertRequestCount( 1 );
		$this->assertTrue( $stale_called );
		$this->assertTrue( $expire_called );
	}

	public function test_it_can_use_a_custom_cache_key(): void {
		$this->client = Factory::create()->cache( key: 'custom-cache-key' );

		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$this->assertEmpty( wp_cache_get( 'custom-cache-key', Cache_Middleware::CACHE_GROUP ) );

		$this->client->get( 'https://example.com' );
		$this->client->get( 'https://example.com' );

		$this->assertRequestCount( 1 );
		$this->assertNotEmpty( wp_cache_get( 'custom-cache-key', Cache_Middleware::CACHE_GROUP ) );
	}

	public function test_it_can_use_a_custom_cache_key_with_flexible_caching(): void {
		$this->client = Factory::create()->cache_flexible(
			stale: now()->addHour(),
			expire: now()->addDay(),
			key: 'custom-flexible-cache-key',
		);

		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$this->assertEmpty( wp_cache_get( 'custom-flexible-cache-key', Cache_Middleware::CACHE_GROUP ) );

		$this->client->get( 'https://example.com' );
		$this->client->get( 'https://example.com' );

		$this->assertRequestCount( 1 );
		$this->assertNotEmpty( wp_cache_get( 'custom-flexible-cache-key', Cache_Middleware::CACHE_GROUP ) );
	}
}
