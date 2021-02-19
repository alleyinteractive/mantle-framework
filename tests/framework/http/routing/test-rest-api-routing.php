<?php
namespace Mantle\Tests\Framework\Http\Routing;

use Closure;
use Mantle\Facade\Route;
use Mantle\Framework\Testing\Concerns\Refresh_Database;
use Mantle\Framework\Testing\Framework_Test_Case;
use WP_REST_Request;

class Test_REST_API_Routing extends Framework_Test_Case {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();

		update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );
	}

	public function test_generic_route() {
		Route::rest_api(
			'namespace/v1',
			'/example-closure-third',
			function() {
				return 'example-closure-third';
			}
		);

		Route::rest_api(
			'namespace/v1',
			'/example-array-third',
			[
				'callback' => function() {
					return 'example-array-third';
				},
			]
		);

		Route::rest_api(
			'namespace/v1',
			function() {
				Route::get(
					'/example-group-get',
					function() {
						return 'example-group-get';
					}
				);

				Route::get(
					'/example-with-param/(?P<slug>[a-z\-]+)',
					function( WP_REST_Request $request) {
						return $request['slug'];
					}
				);

				Route::post(
					'/example-post',
					function() {
						return 'example-post';
					}
				);
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
				'callback' => function( WP_REST_Request $request ) {
					return $request['input'];
				}
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
