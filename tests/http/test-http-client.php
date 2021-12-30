<?php
/**
 * Test_Redirector test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Http;

use Mantle\Http\Client\Factory;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Mock_Http_Response;
use PHPUnit\Framework\TestCase;

class Test_Http_Client extends Framework_Test_Case {
	protected Factory $http_factory;

	protected function setUp(): void {
		parent::setUp();

		$this->http_factory = new Factory();
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

		$response = $this->http_factory->get( 'https://wordpress.org/' );

		$this->assertTrue( $response->ok() );
	}

	public function test_make_get_request_with_query() {
		$this->fake_request();

		$this->http_factory->get( 'https://wordpress.org/', [ 'example' => 'value' ] );

		$this->assertRequestSent( 'https://wordpress.org/?example=value' );
	}
}
