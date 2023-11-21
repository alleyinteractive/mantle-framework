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
use Mantle\Testing\Framework_Test_Case;

class ControllerTest extends Framework_Test_Case {
	public function test_controller_at_method() {
		Route::get( '/example-controller-at-route', Example_Controller::class . '@example_method' );

		$this->get( '/example-controller-at-route' )->assertContent( 'controller-response' );
	}

	public function test_controller_callable_method() {
		Route::get( '/example-controller-route', [ Example_Controller::class, 'example_method' ] );
		$this->get( '/example-controller-route' )->assertContent( 'controller-response' );
	}

	public function test_controller_callable_method_param() {
		Route::get( '/hello/{who}', [ Example_Controller::class, 'example_method_with_params' ] );
		$this->get( '/hello/sean' )->assertContent( 'Welcome sean!' );
	}

	public function test_invokable_controller() {
		Route::get( '/example-invokable-controller', Invokable_Controller::class );

		$this->get( '/example-invokable-controller' )->assertContent( 'invoke-response' );
	}

	public function test_request_with_parameters() {
		Route::get( '/example-controller-with-request/{who}', Example_Controller::class . '@example_method_with_request' );
		Route::get( '/flipped-example-controller-with-request/{who}', Example_Controller::class . '@example_method_with_request' );

		$this->get( '/example-controller-with-request/name' )->assertContent( 'namename' );
		$this->get( '/flipped-example-controller-with-request/name' )->assertContent( 'namename' );
	}
}

class Example_Controller extends Controller {
	public function example_method() {
		return 'controller-response';
	}

	public function example_method_with_params(string $who) {
		return "Welcome {$who}!";
	}

	public function example_method_with_request( Request $request, string $who ) {
		return $request['who'] . $who;
	}

	public function flipped_example_method_with_request( string $who, Request $request ) {
		return $request['who'] . $who;
	}
}

class Invokable_Controller extends Controller {
	public function __invoke() {
		return 'invoke-response';
	}
}
