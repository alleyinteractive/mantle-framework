<?php
namespace Mantle\Tests\Http\Routing;

use Closure;
use InvalidArgumentException;
use Mantle\Facade\Route;
use Mantle\Http\Routing\Route as RouteObject;
use Mantle\Http\Controller;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\FrameworkTestCase;
use WP_REST_Request;

class RestApiRoutingTest extends FrameworkTestCase {
	use Refresh_Database;

	public function test_register_rest_with_callback_directly(): void {
		$route = Route::rest_api(
			'namespace/v1',
			'/example-closure',
			fn () => 'example-closure',
		);

		$this->assertInstanceOf( RouteObject::class, $route );

		$this->get( rest_url( '/namespace/v1/example-closure' ) )
			->assertOk()
			->assertContent( json_encode( 'example-closure' ) );

		$this->post( rest_url( '/namespace/v1/example-closure' ) )
			->assertNotFound()
			->assertJsonPath( 'code', 'rest_no_route' );
	}

	public function test_register_rest_with_callback_in_argument_array(): void {
		$route = Route::rest_api(
			'namespace/v1',
			'/example-array',
			[
				'callback' => fn () => 'example-array',
			]
		);

		$this->assertInstanceOf( RouteObject::class, $route );

		$this->get( rest_url( '/namespace/v1/example-array' ) )
			->assertOk()
			->assertContent( json_encode( 'example-array' ) );

		$this->post( rest_url( '/namespace/v1/example-array' ) )
			->assertNotFound()
			->assertJsonPath( 'code', 'rest_no_route' );
	}

	public function test_register_with_string_callback(): void {
		$route = Route::rest_api(
			'namespace/v1',
			'/example-string',
			__NAMESPACE__ . '\testable_function_name',
		);

		$this->assertInstanceOf( RouteObject::class, $route );

		$this->get( rest_url( '/namespace/v1/example-string' ) )
			->assertOk()
			->assertContent( json_encode( 'function-response' ) );
	}

	public function test_register_routes_in_callback(): void {
		$return = Route::rest_api(
			'namespace/v1',
			function() {
				Route::get( '/example-group-get', fn () => 'example-group-get' );

				Route::get(
					'/example-with-param/(?P<slug>[a-z\-]+)',
					fn ( WP_REST_Request $request) => $request['slug'],
				);

				Route::post( '/example-post', fn () => 'example-post' );
			}
		);

		$this->assertNull( $return );

		$this->get( rest_url( '/namespace/v1/example-group-get' ) )
			->assertOk()
			->assertContent( json_encode( 'example-group-get' ) );

		$this->get( rest_url( '/namespace/v1/example-with-param/the-slug' ) )
			->assertOk()
			->assertContent( json_encode( 'the-slug' ) );

		$this->post( rest_url( '/namespace/v1/example-post' ) )
			->assertOk()
			->assertContent( json_encode( 'example-post' ) );
	}

	public function test_controller_route() {
		// Registering a group of routes from controller methods.
		Route::rest_api(
			'namespace/v1',
			function () {
				Route::get( '/example-invoke', Testable_Invokable_Rest_Api_Controller::class );

				Route::get( '/example-controller/index', [ Testable_Rest_Api_Controller::class, 'index' ] );
				Route::get( '/example-controller/show', [ Testable_Rest_Api_Controller::class, 'show' ] );
			},
		);

		$this->get( rest_url( '/namespace/v1/example-invoke' ) )
			->assertOk()
			->assertContent( json_encode( 'invoke-response' ) );

		$this->get( rest_url( '/namespace/v1/example-controller/index' ) )
			->assertOk()
			->assertContent( json_encode( 'index-response' ) );

		$this->get( rest_url( '/namespace/v1/example-controller/show' ) )
			->assertOk()
			->assertContent( json_encode( 'show-response' ) );
	}

	public function test_middleware_class_route() {
		Route::middleware( Testable_Before_Middleware::class )
			->rest_api(
				'namespace/v1',
				'/example-middleware-route',
				function() {
					return 'base-response';
				}
			);

		$this->get( rest_url( '/namespace/v1/example-middleware-route' ) )
			->assertOk()
			->assertContent( json_encode( 'middleware-response' ) );
	}

	public function test_middleware_closure_route(): void {
		Route::middleware(
			function( WP_REST_Request $request, Closure $next ) {
				$request->set_param( 'input', 'modified' );
				return $next( $request );
			}
		)->rest_api(
			'namespace/v1',
			'/example-middleware-modify-post',
			[
				'methods' => 'POST',
				'callback' => fn ( WP_REST_Request $request ) => $request['input'],
			]
		);

		$this->post( rest_url( '/namespace/v1/example-middleware-modify-post' ), [ 'input' => 'value' ] )
			->assertOk()
			->assertContent( json_encode( 'modified' ) );
	}

	public function test_middleware_added_fluently(): void {
		Route::rest_api(
				'namespace/v1',
				function (): void {
					Route::get( '/example-middleware-fluent', function() {
						return 'base-response';
					} )->middleware( function ( WP_REST_Request $request, Closure $next ) {
						return 'middleware-response';
					} );
				},
		);

		$this->get( rest_url( '/namespace/v1/example-middleware-fluent' ) )
			->assertOk()
			->assertContent( json_encode( 'middleware-response' ) );
	}

	public function test_group_route() {
		Route::middleware( Testable_Before_Middleware::class )->group(
			function() {
				Route::rest_api(
					'namespace/v1',
					'example-group',
					function() {
						return 'response';
					}
				);
			}
		);

		$this->get( rest_url( '/namespace/v1/example-group' ) )
			->assertOk()
			->assertContent( json_encode( 'middleware-response' ) );
	}
}

class Testable_Before_Middleware {
	public function handle( $request, Closure $next ) {
		return 'middleware-response';
	}
}

class Testable_Rest_Api_Controller extends Controller {
	public function index() {
		return 'index-response';
	}

	public function show() {
		return 'show-response';
	}
}

class Testable_Invokable_Rest_Api_Controller extends Controller {
	public function __invoke() {
		return 'invoke-response';
	}
}

function testable_function_name() {
	return 'function-response';
}
