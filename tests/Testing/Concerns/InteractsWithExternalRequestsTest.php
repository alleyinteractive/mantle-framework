<?php
namespace Mantle\Tests\Testing\Concerns;

use DateTime;
use InvalidArgumentException;
use Mantle\Facade\Http;
use Mantle\Http_Client\Factory;
use Mantle\Http_Client\Http_Method;
use Mantle\Http_Client\Pending_Request;
use Mantle\Http_Client\Request;
use Mantle\Testing\Concerns\Prevent_Remote_Requests;
use Mantle\Testing\Mock_Http_Response;
use Mantle\Testing\FrameworkTestCase;
use Mantle\Testing\Mock_Http_Sequence;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

use function Mantle\Testing\mock_http_response;

/**
 * Test for Mocking WP HTTP API Requests.
 *
 * @group testing
 */
#[Group( 'testing' )]
class InteractsWithExternalRequestsTest extends FrameworkTestCase {
	use Prevent_Remote_Requests;

	public function test_fake_request_no_arguments() {
		$this->fake_request();

		$response = wp_remote_get( 'https://example.com/' );
		$this->assertEquals( 200, wp_remote_retrieve_response_code( $response ) );
		$this->assertEmpty( wp_remote_retrieve_body( $response ) );
	}

	public function test_fake_request_catch_all() {
		$this->fake_request( $this->mock_response()->with_status( 201 )->with_json( [ 'name' => 'John Doe' ] ) );

		$response = wp_remote_get( 'https://example.com/' );

		$this->assertEquals( 201, wp_remote_retrieve_response_code( $response ) );
		$this->assertEquals( 'John Doe', json_decode( wp_remote_retrieve_body( $response ) )->name );
	}

	public function test_fake_request() {
		$this->fake_request( 'https://testing.com/*' )
			->with_response_code( 404 )
			->with_body( 'test body' );

		$this->fake_request( 'https://github.com/*' )
			->with_response_code( 500 )
			->with_body( 'fake body' );

		$this->fake_request( 'https://example.com/', Mock_Http_Response::create()->with_body( 'example body' ) );

		$response = wp_remote_get( 'https://testing.com/' );
		$this->assertEquals( 'test body', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 404, wp_remote_retrieve_response_code( $response ) );

		$response = wp_remote_get( 'https://github.com/' );
		$this->assertEquals( 'fake body', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 500, wp_remote_retrieve_response_code( $response ) );

		$response = wp_remote_get( 'https://example.com/' );
		$this->assertEquals( 'example body', wp_remote_retrieve_body( $response ) );
	}

	public function test_fake_request_with_method() {
		$this->fake_request( 'https://example.org/api/v1/users', method: 'POST' )
			->with_status( 201 )
			->with_json( [ 'name' => 'John Doe' ] );

		$this->fake_request_sequence( 'https://example.org/api/v1/users', method: 'GET' )
			->push_json( [
				'items' => [],
			] )
			->push_json( [
				'items' => [
					[
						'name' => 'John Doe',
					],
				],
			] );

		$users = Http::get( 'https://example.org/api/v1/users' );

		$this->assertEquals( 200, $users->status() );
		$this->assertEmpty( $users->json( 'items' ) );

		// Create the user.
		$user = Http::post( 'https://example.org/api/v1/users', [ 'name' => 'John Doe' ] );

		$this->assertEquals( 201, $user->status() );
		$this->assertEquals( 'John Doe', $user->json( 'name' ) );

		// Get the users.
		$users = Http::get( 'https://example.org/api/v1/users' );

		$this->assertEquals( 200, $users->status() );
		$this->assertEquals( 'John Doe', $users->json( 'items.0.name' ) );
	}

	public function test_fake_request_with_array(): void {
		$this->fake_request(
			'https://example.org/example-as-arguments',
			[
				'example' => 'value',
			],
		);

		$this->fake_request( [
			'https://example.org/example-as-array' => [
				'example' => 'value-2',
			] ],
		);

		$response = Http::get( 'https://example.org/example-as-arguments' );

		$this->assertEquals( 200, $response->status() );
		$this->assertEquals( 'value', $response->json( 'example' ) );

		$response = Http::get( 'https://example.org/example-as-array' );

		$this->assertEquals( 200, $response->status() );
		$this->assertEquals( 'value-2', $response->json( 'example' ) );
	}

	public function test_fake_all_requests() {
		$this->fake_request()
			->with_response_code( 206 )
			->with_body( 'another fake body' );

		$response = wp_remote_get( 'https://github.com/' );
		$this->assertEquals( 'another fake body', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 206, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_fake_request_double_response_invalid_argument() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Response object passed twice, only one response object should be passed.' );

		$this->fake_request( $this->mock_response(), $this->mock_response() );
	}

	public function test_fake_callback() {
		$this->fake_request(
			function() {
				$this->assertIsString( func_get_arg( 0 ) );
				$this->assertIsArray( func_get_arg( 1 ) );

				return Mock_Http_Response::create()
					->with_response_code( 123 )
					->with_body( 'apples' );
			}
		);

		$response = wp_remote_get( 'https://alley.co/' );
		$this->assertEquals( 'apples', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 123, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_fake_array_of_urls() {
		$this->fake_request(
			[
				'https://github.com/*'  => Mock_Http_Response::create()->with_body( 'github' ),
				'https://twitter.com/*' => Mock_Http_Response::create()->with_body( 'twitter' ),
			]
		);

		$response = wp_remote_get( 'https://github.com/' );
		$this->assertEquals( 'github', wp_remote_retrieve_body( $response ) );

		$response = wp_remote_get( 'https://twitter.com/' );
		$this->assertEquals( 'twitter', wp_remote_retrieve_body( $response ) );
	}

	public function test_fake_json_response() {
		$this->fake_request()->with_json( [ 1, 2, 3 ] );

		$response = wp_remote_get( 'https://github.com/' );
		$this->assertEquals( [ 1, 2, 3 ], json_decode( wp_remote_retrieve_body( $response ) ) );
	}

	public function test_permanent_redirect_response() {
		$this->fake_request()->with_redirect( 'https://wordpress.org/', 308 );

		$response = wp_remote_get( 'https://drupal.org/' );
		$this->assertEquals(
			'https://wordpress.org/',
			wp_remote_retrieve_header( $response, 'Location' )
		);

		$this->assertEquals( 308, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_redirect_response() {
		$this->fake_request()->with_redirect( 'https://wordpress.org/' );

		$response = wp_remote_get( 'https://drupal.org/' );
		$this->assertEquals(
			'https://wordpress.org/',
			wp_remote_retrieve_header( $response, 'Location' )
		);

		$this->assertEquals( 301, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_redirect_response_temporary() {
		$this->fake_request()->with_temporary_redirect( 'https://wordpress.org/' );

		$response = wp_remote_get( 'https://drupal.org/' );
		$this->assertEquals(
			'https://wordpress.org/',
			wp_remote_retrieve_header( $response, 'Location' )
		);

		$this->assertEquals( 302, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_error_response() {
		$this->fake_request(
			function() {
				return new \WP_Error( 'http-error', 'Error!' );
			}
		);

		$response = wp_remote_get( 'https://alley.co/' );
		$this->assertWPError( $response );

		$this->assertRequestSent( 'https://alley.co/', 1 );
		$this->assertRequestNotSent( 'https://anothersite.com/' );
	}

	public function test_sequence() {
		$this->fake_request(
			Mock_Http_Sequence::create()
				->push_status( 200 )
				->push_status( 400 )
				->push_status( 500 )
		);

		$http = new Factory();

		$this->assertEquals( 200, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 400, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 500, $http->get( 'https://example.com/sequence/' )->status() );
	}

	public function test_sequence_array() {
		$this->fake_request(
			[
				'github.com/*' => Mock_Http_Sequence::create()
					->push_status( 200 )
					->push_status( 400 )
					->push_status( 500 ),
				'alley.co/*' => Mock_Http_Sequence::create()
					->push_status( 200 )
					->push_status( 403 ),
			]
		);

		$http = new Pending_Request();

		$this->assertEquals( 200, $http->get( 'https://github.com/request/' )->status() );
		$this->assertEquals( 400, $http->get( 'https://github.com/request/' )->status() );

		$this->assertEquals( 200, $http->get( 'https://alley.co/test/' )->status() );
		$this->assertEquals( 403, $http->get( 'https://alley.co/test/' )->status() );
	}

	public function test_sequence_exception_empty() {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'No more responses in sequence.' );

		$this->fake_request(
			Mock_Http_Sequence::create()
				->push_status( 200 )
				->push_status( 400 )
		);

		$http = new Factory();

		$this->assertEquals( 200, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 400, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 500, $http->get( 'https://example.com/sequence/' )->status() );
	}

	public function test_sequence_fallback_empty() {
		$this->fake_request(
			Mock_Http_Sequence::create()
				->push_status( 200 )
				->push_status( 400 )
				->when_empty( Mock_Http_Response::create()->with_status( 202 ) )
		);

		$http = new Factory();

		$this->assertEquals( 200, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 400, $http->get( 'https://example.com/sequence/' )->status() );

		// These two should use the fallback response.
		$this->assertEquals( 202, $http->get( 'https://example.com/sequence/' )->status() );
		$this->assertEquals( 202, $http->get( 'https://example.com/sequence/' )->status() );
	}

	public function test_prevent_stray_requests() {
		$this->prevent_stray_requests(
			Mock_Http_Response::create()->with_status( 201 ),
		);

		$this->assertEquals( 201, Http::get( 'https://example.com/' )->status() );
		$this->assertRequestSent();
	}

	public function test_prevent_stray_requests_callback() {
		$this->prevent_stray_requests(
			fn () => Mock_Http_Response::create()->with_status( 400 ),
		);

		$this->assertEquals( 400, Http::get( 'https://example.com/' )->status() );
		$this->assertRequestSent();
	}

	public function test_prevent_stray_requests_no_fallback() {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Attempted request to [https://example.org/path/] without a matching fake.' );

		$this->prevent_stray_requests();

		Http::get( 'https://example.org/path/' );
	}

	// Note: This test will require a working internet connection and alley.com to be up.
	public function test_prevent_stray_requests_but_ignore_some() {
		$this->prevent_stray_requests();

		$this->ignore_stray_request( 'https://alley.com/*' );

		$request = Http::get( 'https://alley.com/' );

		$this->assertEquals( 200, $request->status() );
		$this->assertStringContainsString( 'Alley', $request->body() );

		$this->assertRequestSent( 'https://alley.com/' );

		// A non-ignored request will throw an exception.
		$this->expectException( RuntimeException::class );
		Http::get( 'https://example.com/' );
	}

	public function test_prevent_remote_requests_trait() {
		$this->assertTrue( $this->is_preventing_stray_requests() );

		wp_remote_get( 'https://example.com/' );
	}

	public function test_file_as_response() {
		$this->fake_request(
			fn() => Mock_Http_Response::create()->with_file( MANTLE_PHPUNIT_FIXTURES_PATH . '/images/alley.jpg' )
		);

		$filename = sys_get_temp_dir() . '/' . time() . '-alley.jpg';

		$response = Http::stream( $filename )->get( 'https://alley.com/wp-content/uploads/2021/12/NSF_Cover.png?w=960' );

		$this->assertEquals( 200, $response->status() );
		$this->assertNotEmpty( file_get_contents( $filename ) );
		$this->assertEquals( 'image/jpeg', $response->header( 'Content-Type' ) );
	}

	public function test_streamed_response() {
		$this->fake_request(
			fn() => Mock_Http_Response::create()->with_file( MANTLE_PHPUNIT_FIXTURES_PATH . '/images/alley.jpg' )
		);

		$response = Http::stream()->get( 'https://alley.com/wp-content/uploads/2021/12/NSF_Cover.png?w=960' );

		$this->assertEquals( 200, $response->status() );
		$this->assertNotEmpty( $response->file_contents() );
		$this->assertEquals( 'image/jpeg', $response->header( 'Content-Type' ) );
	}

	public function test_fake_request_with_file() {
		$file = __DIR__ . '/../../../src/mantle/testing/data/images/wordpress-gsoc-flyer.pdf';

		$this->fake_request( 'https://example.org/images/file.pdf' )->with_file( $file );

		$response = Http::get( 'https://example.org/images/file.pdf' );

		$this->assertTrue( $response->ok() );
		$this->assertEquals( 'application/pdf', $response->header( 'Content-Type' ) );
		$this->assertEquals(
			'attachment; filename="wordpress-gsoc-flyer.pdf"',
			$response->header( 'Content-Disposition' )
		);
		$this->assertEquals( file_get_contents( $file ), $response->body() );
	}

	public function test_fake_request_with_image() {
		$this->fake_request( 'https://example.org/images/alley.jpg' )->with_image();

		$response = Http::get( 'https://example.org/images/alley.jpg' );

		$this->assertTrue( $response->ok() );
		$this->assertEquals( 'image/jpeg', $response->header( 'Content-Type' ) );
		$this->assertNotEmpty( $response->body() );
		$this->assertTrue( $response->is_blob() );
		$this->assertTrue( $response->is_file() );
	}

	public function test_unknown_file_as_response() {
		$this->expectException( InvalidArgumentException::class );

		$this->fake_request(
			Mock_Http_Response::create()->with_file( 'unknown' )
		);
	}

	public function test_unknown_return_value_from_callback() {
		$this->fake_request(
			fn () => new DateTime(),
		);

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unknown response type returned for faked request to [https://example.com/]. Expected a (Mantle\Testing\Mock_Http_Response|Mantle\Contracts\Support\Arrayable|WP_Error|array), got object.' );

		Http::get( 'https://example.com/' );
	}

	public function test_fake_request_passed_request_object(): void {
		/** @var Request|null $object */
		$object = null;

		$this->fake_request( fn ( Request $request ) => null );

		$this->fake_request( function ( Request $request ) use ( &$object ) {
			$object = $request;

			return mock_http_response()->with_json( [ 'status' => 'ok' ] );
		} );

		$response = Http::get( 'https://example.com/?example=here' );

		$this->assertEquals( 'ok', $response->json( 'status' ) );
		$this->assertInstanceOf( Request::class, $object );
		$this->assertEquals( 'https://example.com/?example=here', $object->url() );
		$this->assertEquals( Http_Method::GET->value, $object->method() );
		$this->assertEquals( Http_Method::GET, $object->enum_method() );
	}

	public function test_fake_request_with_snapshot(): void {
		$this->fake_request( '*alley.com/*' )->with_snapshot();
		// $this->fake_request( 'https://alley.com/wp-json/*' )->with_snapshot();

		$this->assertEquals( 404, Http::get( 'https://alley.com/not-found-ever' )->status() );

		$response = Http::get( 'https://alley.com/wp-json/wp/v2/posts/5038' );

		$this->assertEquals( 200, $response->status() );
		$this->assertNotEmpty( $response->json() );

		// Assert that the request was made but was not a "real" request.
		$this->assertRequestSent();
		$this->assertRequestSent( 'https://alley.com/not-found-ever' );
		$this->assertRequestSent( 'https://alley.com/wp-json/wp/v2/posts/5038' );

		$this->assertActualRequestNotSent();
		$this->assertActualRequestNotSent( 'https://alley.com/not-found-ever' );
		$this->assertActualRequestNotSent( 'https://alley.com/wp-json/wp/v2/posts/5038' );

		$this->assertFileExists(
			__DIR__ . '/__http_snapshots__/InteractsWithExternalRequestsTest/test_fake_request_with_snapshot-get-https-alley-com-not-found-ever.json',
		);

		$this->assertFileExists(
			__DIR__ . '/__http_snapshots__/InteractsWithExternalRequestsTest/test_fake_request_with_snapshot-get-https-alley-com-wp-json-wp-v2-posts-5038.json',
		);
	}

	public function test_fake_request_with_snapshot_named(): void {
		$this->fake_request( 'http://alley.com/not-found-with-body' )->with_snapshot( 'snapshot-id' );

		$this->assertEquals( 404, Http::post( 'http://alley.com/not-found-with-body', [
			'example' => 'here',
		] )->status() );

		// Assert that the expected snapshot file exists.
		$this->assertFileExists(
			__DIR__ . '/__http_snapshots__/InteractsWithExternalRequestsTest/test_fake_request_with_snapshot_named-snapshot-id.json',
		);
	}
}
