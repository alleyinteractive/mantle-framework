<?php
namespace Mantle\Tests\Http\Routing;

use Closure;
use Mantle\Facade\Route;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Framework_Test_Case;
use WP_REST_Request;

class Test_REST_API_Routing extends Framework_Test_Case {
	use Refresh_Database;

	public function test_generic_route() {
		Route::rest_api(
			'namespace/v1',
			'/example-closure-third',
			fn () => 'example-closure-third',
		);

		Route::rest_api(
			'namespace/v1',
			'/example-array-third',
			[
				'callback' => fn () => 'example-array-third',
			]
		);

		Route::rest_api(
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

		$this->get( rest_url( '/namespace/v1/example-closure-third' ) )
			->assertOk()
			->assertContent( json_encode( 'example-closure-third' ) );

		$this->get( rest_url( '/namespace/v1/example-array-third' ) )
			->assertOk()
			->assertContent( json_encode( 'example-array-third' ) );

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

	public function test_middleware_route() {
		Route::middleware( Testable_Before_Middleware::class )
			->rest_api(
				'namespace/v1',
				'/example-middleware-route',
				function() {
					return 'base-response';
				}
			);

		Route::middleware(
			function( WP_REST_Request $request, $next ) {
				$request->set_param( 'input', 'modified' );
				return $next( $request );
			}
		)
		->rest_api(
			'namespace/v1',
			'/example-middleware-modify-post',
			[
				'methods' => 'POST',
				'callback' => fn ( WP_REST_Request $request ) => $request['input'],
			]
		);

		$this->get( rest_url( '/namespace/v1/example-middleware-route' ) )
			->assertOk()
			->assertContent( json_encode( 'middleware-response' ) );

		$this->post( rest_url( '/namespace/v1/example-middleware-modify-post' ), [ 'input' => 'value' ] )
			->assertOk()
			->assertContent( json_encode( 'modified' ) );
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
