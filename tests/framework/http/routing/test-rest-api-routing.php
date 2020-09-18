<?php
namespace Mantle\Tests\Framework\Http\Routing;

use Mantle\Framework\Facade\Route;
use Mantle\Framework\Testing\Framework_Test_Case;
use WP_REST_Request;

class Test_REST_API_Routing extends Framework_Test_Case {
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
	}
}
