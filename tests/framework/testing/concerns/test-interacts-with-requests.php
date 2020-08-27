<?php
namespace Mantle\Tests\Framework\Testing\Concerns;

use Mantle\Framework\Testing\Mock_Http_Response;
use Mantle\Framework\Testing\Test_Case;

class Test_Interacts_With_Requests extends Test_Case {
	public function test_fake_request() {
		$this->fake_request( 'https://testing.com/*' )
			->with_response_code( 404 )
			->with_body( 'test body' );

		$this->fake_request( 'https://github.com/*' )
			->with_response_code( 500 )
			->with_body( 'fake body' );

		$response = wp_remote_get( 'https://testing.com/' );
		$this->assertEquals( 'test body', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 404, wp_remote_retrieve_response_code( $response ) );

		$response = wp_remote_get( 'https://github.com/' );
		$this->assertEquals( 'fake body', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 500, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_fake_all_requests() {
		$this->fake_request()
			->with_response_code( 206 )
			->with_body( 'another fake body' );

		$response = wp_remote_get( 'https://github.com/' );
		$this->assertEquals( 'another fake body', wp_remote_retrieve_body( $response ) );
		$this->assertEquals( 206, wp_remote_retrieve_response_code( $response ) );
	}

	public function test_fake_callback() {
		$this->fake_request(
			function() {
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
}
