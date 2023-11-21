<?php
/**
 * Test_Response_Factory test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Console;

use Mantle\Http\Request;

class ResponseFactoryTest extends \Mockery\Adapter\Phpunit\MockeryTestCase {

	public function setUp(): void {
		parent::setUp();

		app()->instance( 'request', new Request() );
	}

	public function tearDown(): void {
		parent::tearDown();
		app()->forget_instance( 'request' );
	}

	public function test_raw_response() {
		$response = response()->make( "I'm a teapot", 418 );
		$this->assertEquals( "I'm a teapot", $response->getContent() );
	}

	public function test_raw_response_through_helper() {
		$response = response( "I'm a teapot", 418 );
		$this->assertEquals( "I'm a teapot", $response->getContent() );
	}

	public function test_json_response() {
		$response = response()->json( [ 'response' => 'test' ] );
		$this->assertEquals( '{"response":"test"}', $response->getContent() );
		$this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
	}

	public function test_no_content_response() {
		$this->assertEmpty( response()->no_content()->getContent() );
	}
}
