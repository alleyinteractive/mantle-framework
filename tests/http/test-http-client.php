<?php
/**
 * Test_Redirector test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Http;

use Closure;
use Mantle\Facade\Http;
use Mantle\Http\Client\Http_Client;
use Mantle\Http\Client\Http_Client_Exception;
use Mantle\Http\Client\Request;
use Mantle\Http\Client\Response;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Mock_Http_Response;

class Test_Http_Client extends Framework_Test_Case {
	/**
	 * @var Http_Client
	 */
	protected Http_Client $http_factory;

	protected function setUp(): void {
		parent::setUp();

		$this->http_factory = new Http_Client();
	}

	public function test_make_post_request() {
		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_status( 200 )
			->with_header( 'Example-Header', 'Example-Value' )
			->with_json( [ 'example' => 'value' ] )
		);

		$response = $this->http_factory->post( 'https://alley.co/', [
			'example' => 'value',
		] );

		$this->assertTrue( $response->ok() );
		$this->assertEquals( 'Example-Value', $response->headers()['example-header'] );
		$this->assertEquals( 'Example-Value', $response->header('example-header') );
		$this->assertEquals( [ 'example' => 'value' ], $response->json() );
		$this->assertEquals( 'value', $response->json( 'example' ) );
	}

	public function test_make_get_request() {
		$this->fake_request( fn () => Mock_Http_Response::create()->with_status( 200 ) );

		$response = $this->http_factory->get( 'https://example.com/' );

		$this->assertTrue( $response->ok() );
	}

	public function test_make_get_request_with_query() {
		$this->fake_request();

		$this->http_factory->get( 'https://example.com/', [ 'example' => 'value' ] );

		$this->assertRequestSent( 'https://example.com/?example=value' );
		$this->assertRequestSent(
			fn ( Request $request ) => 'https://example.com/?example=value' === $request->url()
		);
	}

	public function test_make_request_with_cookies() {
		$cookie = new \WP_Http_Cookie( [
			'name' => 'example',
			'value' => 'value',
		] );

		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_cookie( $cookie )
			->with_status( 200 )
		);

		$response = $this->http_factory->get( 'https://example.com/' );

		$this->assertNotEmpty( $response->cookie( 'example' ) );
		$this->assertEquals( 'value', $response->cookie( 'example' )->value );
	}

	public function test_make_request_with_json() {
		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_status( 200 )
			->with_json( [ 'example' => 'value' ] )
		);

		$this->http_factory->post( 'https://example.com/', [
			'example' => 'value',
		] );

		$this->assertRequestSent( 'https://example.com/' );
		$this->assertRequestSent(
			fn ( Request $request ) => 'https://example.com/' === $request->url()
				&& $request->is_json()
				&& $request->json() === [ 'example' => 'value' ]
		);
	}

	public function test_make_request_with_basic_auth() {
		$this->fake_request();

		$this->http_factory
			->with_basic_auth( 'user', 'pass' )
			->get( 'https://example.com/basic-auth/' );

		$this->assertRequestSent( fn ( Request $request ) => $request
			->has_header( 'Authorization', 'Basic dXNlcjpwYXNz' )
			&& 'https://example.com/basic-auth/' === $request->url()
			&& 'GET' === $request->method()
		);
	}

	public function test_make_request_with_token() {
		$this->fake_request();

		$this->http_factory
			->with_token( 'token' )
			->get( 'https://example.com/token/' );

		$this->assertRequestSent( fn ( Request $request ) => $request
			->has_header( 'Authorization', 'Bearer token' )
			&& 'https://example.com/token/' === $request->url()
			&& 'GET' === $request->method()
		);
	}

	public function test_nothing_sent() {
		$this->assertNoRequestSent();

		$this->fake_request();

		$this->http_factory->get( 'https://example.com/' );

		$this->assertRequestSent();
	}

	public function test_make_request_with_files() {
		$this->markTestSkipped( 'Not implemented yet.' );
	}

	public function test_http_client_with_base_url() {
		$this->fake_request();

		$rest_client = Http::base_url( 'https://example.com/' );

		$rest_client->get( '/wp-json/wp/v2/posts/' );

		$this->assertRequestSent( 'https://example.com/wp-json/wp/v2/posts/' );
	}

	public function test_facade_request() {
		$this->fake_request();

		Http::get( 'https://example.com/facade/' );

		$this->assertRequestSent( 'https://example.com/facade/' );
	}

	public function test_no_exception_thrown_by_default() {
		$this->fake_request( fn () => Mock_Http_Response::create()->with_status( 500 ) );

		$response = $this->http_factory->get( 'https://example.com/' );

		$this->assertInstanceOf( Response::class, $response );
	}

	public function test_no_exception_thrown_if_muted() {
		$this->fake_request( fn () => Mock_Http_Response::create()->with_status( 500 ) );

		$response = $this->http_factory
			->dont_throw_exception()
			->get( 'https://example.com/' );

		$this->assertInstanceOf( Response::class, $response );
	}

	public function test_exception_thrown_on_failure() {
		$this->fake_request( fn () => Mock_Http_Response::create()->with_status( 500 ) );

		$this->expectException( Http_Client_Exception::class );

		$this->http_factory
			->throw_exception()
			->get( 'https://example.com/' );
	}

	public function test_retry_exception_on_error() {
		$this->fake_request( fn () => Mock_Http_Response::create()->with_status( 500 ) );

		$this->http_factory
			->retry( 5 )
			->get( 'https://example.com/retry/' );

		$this->assertRequestSent( 'https://example.com/retry/', 5 );
	}

	public function test_retry_exception_on_error_with_exception() {
		$this->fake_request( fn () => Mock_Http_Response::create()->with_status( 500 ) );

		$this->expectException( Http_Client_Exception::class );

		$this->http_factory
			->retry( 5 )
			->throw_exception()
			->get( 'https://example.com/retry/' );

		$this->assertRequestSent( 'https://example.com/retry/', 5 );
	}

	public function test_middleware_request() {
		$this->fake_request();

		$this->http_factory
			->middleware( function ( Http_Client $client, Closure $next ) {
				$client->url( 'https://example.com/middleware/?modified=true' );

				return $next( $client );
			} )
			->get( 'https://example.com/middleware/' );

		$this->assertRequestSent( 'https://example.com/middleware/?modified=true' );
	}

	public function test_middleware_response() {
		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_header( 'test-header', 'origin-value' )
			->with_body( 'example-body' )
		);

		$response = $this->http_factory
			->middleware( function ( Http_Client $client, Closure $next ) {
				$response = $next( $client );

				return new Response( array_merge(
					$response->response(),
					[
						'headers' => [
							'test-header' => 'modified-value',
						],
					],
				) );
			} )
			->get( 'https://example.com/middleware/' );

		$this->assertRequestSent( 'https://example.com/middleware/' );
		$this->assertEquals( 'modified-value', $response->header( 'test-header' ) );
		$this->assertEquals( 'example-body', $response->body() );
	}

	public function test_wp_error_response() {
		$error = new \WP_Error( 'http_request_failed', 'An error occurred.' );

		$this->fake_request( fn () => $error );

		$response = $this->http_factory->get( 'https://example.com/wp-error/' );

		$this->assertTrue( $response->is_wp_error() );
		$this->assertTrue( $response->failed() );

		$this->assertEquals( 'An error occurred.', $response->body() );
	}

	public function test_invalid_json() {
		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_header( 'content-type', 'application/json' )
			->with_body( 'text-body' )
		);

		$response = $this->http_factory->get( 'https://example.com/wp-error/' );

		$this->assertEquals( 'text-body', $response->body() );
		$this->assertNull( $response->json() );
	}

	public function test_timeout() {
		$this->fake_request();

		$this->http_factory
			->timeout( 9 )
			->get( 'https://example.com/timeout/' );

		$this->assertRequestSent(
			fn ( Request $request ) => 'https://example.com/timeout/' === $request->url()
				&& 9 === $request->get( 'timeout' )
		);
	}

	public function test_xml_response() {
		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_header( 'content-type', 'application/xml' )
			->with_body(
				<<<EOF
<?xml version="1.0"?>
	<slideshow
		title="Sample Slide Show"
		date="Date of publication"
		author="Yours Truly"
	>
		<slide type="all">
			<title>First Slide Title</title>
			<point>Very interesting!</point>
		</slide>

		<slide type="specific">
			<title>Second Slide Title</title>
			<point>Another point!</point>
		</slide>
</slideshow>
EOF
			)
		);

		$response = $this->http_factory->get( 'https://example.com/xml/' );

		$this->assertEquals( 'First Slide Title', $response->xml()->slide[0]->title );
		$this->assertEquals( 'Second Slide Title', $response->xml()->slide[1]->title );

		$this->assertEquals( 'Another point!', $response->xml( '/slideshow/slide[@type="specific"]/point' )[0] ?? '' );
	}
}
