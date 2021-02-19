<?php
/**
 * Test_Controller test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Framework\Http;

use Mantle\Facade\Route;
use Mantle\Framework\Http\Controller;
use Mantle\Framework\Testing\Framework_Test_Case;

class Test_Controller extends Framework_Test_Case {
	public function test_controller_at_method() {
		Route::get( '/example-controller-at-route', Example_Controller::class . '@example_method' );

		$this->get( '/example-controller-at-route' )->assertContent( 'controller-response' );
	}

	public function test_controller_callable_method() {
		Route::get( '/example-controller-route', [ Example_Controller::class, 'example_method' ] );

		$this->get( '/example-controller-route' )->assertContent( 'controller-response' );
	}

	public function test_invokable_controller() {
		Route::get( '/example-invokable-controller', Invokable_Controller::class );

		$this->get( '/example-invokable-controller' )->assertContent( 'invoke-response' );
	}
}

class Example_Controller extends Controller {
	public function example_method() {
		return 'controller-response';
	}
}

class Invokable_Controller extends Controller {
	public function __invoke() {
		return 'invoke-response';
	}
}
