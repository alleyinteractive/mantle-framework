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
			->assertContent( $this->encode_json( 'example-closure' ) );

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
			->assertContent( $this->encode_json( 'example-array' ) );

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
			->assertContent( $this->encode_json( 'function-response' ) );
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
			->assertContent( $this->encode_json( 'example-group-get' ) );

		$this->get( rest_url( '/namespace/v1/example-with-param/the-slug' ) )
			->assertOk()
			->assertContent( $this->encode_json( 'the-slug' ) );

		$this->post( rest_url( '/namespace/v1/example-post' ) )
			->assertOk()
			->assertContent( $this->encode_json( 'example-post' ) );
	}

	public function test_controller_route(): void {
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
			->assertContent( $this->encode_json( 'invoke-response' ) );

		$this->get( rest_url( '/namespace/v1/example-controller/index' ) )
			->assertOk()
			->assertContent( $this->encode_json( 'index-response' ) );

		$this->get( rest_url( '/namespace/v1/example-controller/show' ) )
			->assertOk()
			->assertContent( $this->encode_json( 'show-response' ) );
	}

	public function test_register_with_controller_at_method_string(): void {
		Route::rest_api(
			'namespace/v1',
			'/example-at-method',
			Testable_Rest_Api_Controller::class . '@index',
		);

		$this->get( rest_url( '/namespace/v1/example-at-method' ) )
			->assertOk()
			->assertContent( $this->encode_json( 'index-response' ) );
	}

	public function test_fluent_attributes_do_not_leak_to_sibling_routes(): void {
		Route::rest_api(
			'namespace/v1',
			function (): void {
				Route::middleware( Testable_Before_Middleware::class )->get( '/sibling-admin', fn () => 'admin' );
				Route::get( '/sibling-public', fn () => 'public' );
			},
		);

		$this->get( rest_url( '/namespace/v1/sibling-admin' ) )
			->assertOk()
			->assertContent( $this->encode_json( 'middleware-response' ) );

		$this->get( rest_url( '/namespace/v1/sibling-public' ) )
			->assertOk()
			->assertContent( $this->encode_json( 'public' ) );
	}

	public function test_registrar_prefix_applies_to_rest_route(): void {
		Route::prefix( 'prefixed' )->rest_api( 'namespace/v1', '/rest-prefix', fn () => 'rest-prefix' );

		$this->get( rest_url( '/namespace/v1/prefixed/rest-prefix' ) )
			->assertOk()
			->assertContent( $this->encode_json( 'rest-prefix' ) );
	}

	public function test_registrar_prefix_applies_to_rest_route_in_callback(): void {
		Route::rest_api(
			'namespace/v1',
			function (): void {
				Route::prefix( 'inner' )->get( '/rest-inner-prefix', fn () => 'inner-prefix' );
				Route::get( '/rest-no-prefix', fn () => 'no-prefix' );
			},
		);

		$this->get( rest_url( '/namespace/v1/inner/rest-inner-prefix' ) )
			->assertOk()
			->assertContent( $this->encode_json( 'inner-prefix' ) );

		$this->get( rest_url( '/namespace/v1/rest-no-prefix' ) )
			->assertOk()
			->assertContent( $this->encode_json( 'no-prefix' ) );
	}

	public function test_same_path_in_different_namespaces(): void {
		Route::rest_api( 'namespace-a/v1', '/same-path', fn () => 'a' );
		Route::rest_api( 'namespace-b/v1', '/same-path', fn () => 'b' );

		$this->get( rest_url( '/namespace-a/v1/same-path' ) )->assertOk()->assertContent( $this->encode_json( 'a' ) );
		$this->get( rest_url( '/namespace-b/v1/same-path' ) )->assertOk()->assertContent( $this->encode_json( 'b' ) );
	}

	public function test_middleware_class_route(): void {
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
			->assertContent( $this->encode_json( 'middleware-response' ) );
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
			->assertContent( $this->encode_json( 'modified' ) );
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
			->assertContent( $this->encode_json( 'middleware-response' ) );
	}

	public function test_group_route(): void {
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
			->assertContent( $this->encode_json( 'middleware-response' ) );
	}

	/**
	 * Encode a value as JSON for comparing against response content.
	 *
	 * @param mixed $value Value to encode.
	 */
	protected function encode_json( mixed $value ): string {
		return json_encode( $value, JSON_THROW_ON_ERROR );
	}
}

class Testable_Before_Middleware {
	public function handle( mixed $request, Closure $next ): string {
		return 'middleware-response';
	}
}

class Testable_Rest_Api_Controller extends Controller {
	public function index(): string {
		return 'index-response';
	}

	public function show(): string {
		return 'show-response';
	}
}

class Testable_Invokable_Rest_Api_Controller extends Controller {
	public function __invoke(): string {
		return 'invoke-response';
	}
}

function testable_function_name(): string {
	return 'function-response';
}
