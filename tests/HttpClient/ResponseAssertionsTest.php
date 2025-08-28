<?php
/**
 * ResponseAssertionsTest test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Http_Client;

use Mantle\Http_Client\Response;
use Mantle\Testing\FrameworkTestCase;
use Mantle\Testing\Mock_Http_Response;

class ResponseAssertionsTest extends FrameworkTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->prevent_stray_requests();
	}

	public function test_successful_assertion() {
		$this->fake_request( fn () => Mock_Http_Response::create()->with_status( 200 ) );

		$response = $this->app->make( 'http-client' )->get( 'https://example.com/' );

		$response->assertSuccessful();
		$response->assertOk();
		$response->assertStatus( 200 );
	}

	public function test_status_assertions() {
		$this->fake_request( [
			'https://example.com/created' => Mock_Http_Response::create()->with_status( 201 ),
			'https://example.com/not-found' => Mock_Http_Response::create()->with_status( 404 ),
			'https://example.com/forbidden' => Mock_Http_Response::create()->with_status( 403 ),
			'https://example.com/unauthorized' => Mock_Http_Response::create()->with_status( 401 ),
			'https://example.com/server-error' => Mock_Http_Response::create()->with_status( 500 ),
		] );

		$created_response = $this->app->make( 'http-client' )->get( 'https://example.com/created' );
		$created_response->assertCreated();

		$not_found_response = $this->app->make( 'http-client' )->get( 'https://example.com/not-found' );
		$not_found_response->assertNotFound();

		$forbidden_response = $this->app->make( 'http-client' )->get( 'https://example.com/forbidden' );
		$forbidden_response->assertForbidden();

		$unauthorized_response = $this->app->make( 'http-client' )->get( 'https://example.com/unauthorized' );
		$unauthorized_response->assertUnauthorized();

		$server_error_response = $this->app->make( 'http-client' )->get( 'https://example.com/server-error' );
		$server_error_response->assertServerError();
	}

	public function test_client_and_server_error_assertions() {
		$this->fake_request( [
			'https://example.com/bad-request' => Mock_Http_Response::create()->with_status( 400 ),
			'https://example.com/server-error' => Mock_Http_Response::create()->with_status( 500 ),
		] );

		$client_error_response = $this->app->make( 'http-client' )->get( 'https://example.com/bad-request' );
		$client_error_response->assertClientError();

		$server_error_response = $this->app->make( 'http-client' )->get( 'https://example.com/server-error' );
		$server_error_response->assertServerError();
	}

	public function test_redirect_assertion() {
		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_status( 302 )
			->with_header( 'Location', 'https://redirect.example.com/' )
		);

		$response = $this->app->make( 'http-client' )->get( 'https://example.com/' );

		$response->assertRedirect();
		$response->assertRedirect( 'https://redirect.example.com/' );
		$response->assertLocation( 'https://redirect.example.com/' );
	}

	public function test_header_assertions() {
		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_header( 'Content-Type', 'application/json' )
			->with_header( 'X-Custom-Header', 'Custom-Value' )
		);

		$response = $this->app->make( 'http-client' )->get( 'https://example.com/' );

		$response->assertHeader( 'Content-Type' );
		$response->assertHeader( 'Content-Type', 'application/json' );
		$response->assertHeader( 'X-Custom-Header', 'Custom-Value' );
		$response->assertHeaderMissing( 'X-Nonexistent-Header' );
	}

	public function test_content_assertions() {
		$content = 'This is the response content';
		$this->fake_request( fn () => Mock_Http_Response::create()->with_body( $content ) );

		$response = $this->app->make( 'http-client' )->get( 'https://example.com/' );

		$response->assertContent( $content );
		$response->assertSee( 'response content' );
		$response->assertContains( 'This is' );
		$response->assertDontSee( 'not present' );
	}

	public function test_json_assertions() {
		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_header( 'Content-Type', 'application/json' )
			->with_json( [ 'message' => 'Hello World' ] )
		);

		$response = $this->app->make( 'http-client' )->get( 'https://example.com/' );

		$response->assertIsJson();
	}

	public function test_html_assertions() {
		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_header( 'Content-Type', 'text/html' )
			->with_body( '<html><body>Hello World</body></html>' )
		);

		$response = $this->app->make( 'http-client' )->get( 'https://example.com/' );

		$response->assertIsHtml();
	}

	public function test_no_content_assertion() {
		$this->fake_request( fn () => Mock_Http_Response::create()
			->with_status( 204 )
			->with_body( '' )
		);

		$response = $this->app->make( 'http-client' )->get( 'https://example.com/' );

		$response->assertNoContent();
	}
}