<?php
/**
 * CachedHttpClientTest test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Http_Client;

use Closure;
use Mantle\Facade\Http;
use Mantle\Http_Client\Cache_Middleware;
use Mantle\Http_Client\Factory;
use Mantle\Http_Client\Http_Client_Exception;
use Mantle\Http_Client\Pending_Request;
use Mantle\Http_Client\Pool;
use Mantle\Http_Client\Request;
use Mantle\Http_Client\Response;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Mock_Http_Response;

use function Mantle\Support\Helpers\collect;
use function Mantle\Testing\mock_http_response;

class CachedHttpClientTest extends Framework_Test_Case {
	protected Pending_Request $client;

	protected function setUp(): void {
		parent::setUp();

		$this->client = Factory::create()->cache();

		$this->prevent_stray_requests();
	}

	public function test_can_create_cached_client() {
		$this->assertInstanceOf( Pending_Request::class, $this->client );
		$this->assertTrue(
			collect( $this->client->get_middleware() )->contains( fn( $middleware ) => $middleware instanceof Cache_Middleware )
		);
	}

	public function test_it_can_make_http_request() {
		$this->fake_request( mock_http_response()->with_json( [ 'example' => 'value' ] ) );

		$this->client->get( 'https://example.com' );
		$this->client->get( 'https://example.com' );

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
}
