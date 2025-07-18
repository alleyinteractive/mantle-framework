<?php
/**
 * HttpClientTest test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Http_Client;

use Closure;
use Mantle\Facade\Http;
use Mantle\Http_Client\Client;
use Mantle\Http_Client\Factory;
use Mantle\Http_Client\Http_Client_Exception;
use Mantle\Http_Client\Http_Method;
use Mantle\Http_Client\Pending_Request;
use Mantle\Http_Client\Pool;
use Mantle\Http_Client\Request;
use Mantle\Http_Client\Response;
use Mantle\Testing\Concerns\Prevent_Remote_Requests;
use Mantle\Testing\FrameworkTestCase;
use Mantle\Testing\Mock_Http_Response;

use function Mantle\Http_Client\http_client;

class HttpClientNewTest extends FrameworkTestCase {
	use Prevent_Remote_Requests;

	protected Factory $http_factory;

	public function test_get_request(): void {
		$this->fake_request( 'https://example.com' )->with_json( [
			'foo' => 'bar',
		] );

		$this->assertEquals( [ 'foo' => 'bar' ], Client::create()->get( 'https://example.com' )->json() );
	}

	public function test_get_with_query_parameters(): void {
		$this->fake_request( 'https://example.com/?custom=query' )->with_json( [
			'foo' => 'bar',
		] );

		$this->assertEquals(
			[ 'foo' => 'bar' ],
			Client::create()->get( 'https://example.com/', [ 'custom' => 'query' ] )->json()
		);
	}

	public function test_post_with_body(): void {
		$this->fake_request( fn ( string $url, array $data ) => dd($url, $data));

		$this->assertEquals(
			[ 'foo' => 'bar' ],
			Client::create()->post( 'https://example.com', [ 'foo' => 'bar' ] )->json()
		);
	}
}
