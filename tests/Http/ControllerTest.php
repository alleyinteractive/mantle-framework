<?php
/**
 * Test_Controller test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Http;

use Mantle\Contracts\Http\Routing\Router;
use Mantle\Facade\Route;
use Mantle\Http\Controller;
use Mantle\Http\Request;
use Mantle\Testing\FrameworkTestCase;

class ControllerTest extends FrameworkTestCase {
	public function test_controller_at_method(): void {
		Route::get( '/example-controller-at-route', Example_Controller::class . '@example_method' );

		$this->get( '/example-controller-at-route' )
			->assertOk()
			->assertContent( 'controller-response' );
	}

	public function test_controller_callable_method(): void {
		Route::get( '/example-controller-route', [ Example_Controller::class, 'example_method' ] );
		$this->get( '/example-controller-route' )
			->assertOk()
			->assertContent( 'controller-response' );
	}

	public function test_controller_callable_method_param(): void {
		Route::get( '/hello/{who}', [ Example_Controller::class, 'example_method_with_params' ] );
		$this->get( '/hello/sean' )
			->assertOk()
			->assertContent( 'Welcome sean!' );
	}

	public function test_invokable_controller(): void {
		Route::get( '/example-invokable-controller', Invokable_Controller::class );

		$this->get( '/example-invokable-controller' )
			->assertOk()
			->assertContent( 'invoke-response' );
	}

	public function test_request_with_parameters(): void {
		Route::get( '/example-controller-with-request/{who}', Example_Controller::class . '@example_method_with_request' );
		Route::get( '/flipped-example-controller-with-request/{who}', Example_Controller::class . '@example_method_with_request' );

		$this->get( '/example-controller-with-request/name' )
			->assertOk()
			->assertContent( 'namename' );

		$this->get( '/flipped-example-controller-with-request/name' )
			->assertOk()
			->assertContent( 'namename' );
	}
}

class Example_Controller extends Controller {
	public function example_method(): string {
		return 'controller-response';
	}

	public function example_method_with_params( string $who ): string {
		return "Welcome {$who}!";
	}

	public function example_method_with_request( Request $request, string $who ): string {
		return $request['who'] . $who;
	}

	public function flipped_example_method_with_request( string $who, Request $request ): string {
		return $request['who'] . $who;
	}
}

class Invokable_Controller extends Controller {
	public function __invoke(): string {
		return 'invoke-response';
	}
}
