<?php
/**
 * Test_Response_Factory test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Console;

use Mantle\Filesystem\Filesystem;
use Mantle\Http\Request;

class ResponseFactoryTest extends \Mockery\Adapter\Phpunit\MockeryTestCase {

	public function setUp(): void {
		parent::setUp();

		app()->instance( 'request', new Request() );

		if ( ! app()->bound( 'files' ) ) {
			app()->instance( 'files', new Filesystem() );
		}
	}

	public function tearDown(): void {
		parent::tearDown();
		app()->forget_instance( 'request' );
	}

	public function test_raw_response(): void {
		$response = response()->make( "I'm a teapot", 418 );
		$this->assertSame( "I'm a teapot", $response->getContent() );
	}

	public function test_raw_response_through_helper(): void {
		$response = response( "I'm a teapot", 418 );
		$this->assertSame( "I'm a teapot", $response->getContent() );
	}

	public function test_json_response(): void {
		$response = response()->json( [ 'response' => 'test' ] );
		$this->assertSame( '{"response":"test"}', $response->getContent() );
		$this->assertSame( 'application/json', $response->headers->get( 'Content-Type' ) );
	}

	public function test_no_content_response(): void {
		$this->assertEmpty( response()->no_content()->getContent() );
	}
}
