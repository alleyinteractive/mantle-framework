<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Http\Response;
use Mantle\Framework\Providers\Routing_Service_Provider;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Framework_Test_Case;

class Test_Makes_Http_Requests extends Framework_Test_Case {
	use Refresh_Database;

	public function test_get_home() {
		$this->get( home_url( '/' ) );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
	}

	public function test_get_singular() {
		$post_id = static::factory()->post->create();
		$this->get( get_permalink( $post_id ) )
			->assertQueryTrue( 'is_single', 'is_singular' )
			->assertQueriedObjectId( $post_id );
	}

	public function test_get_term() {
		$category_id = static::factory()->category->create();

		$this->get( get_term_link( $category_id, 'category' ) );
		$this->assertQueryTrue( 'is_archive', 'is_category' );
		$this->assertQueriedObjectId( $category_id );
	}

	public function test_wordpress_404() {
		$this
			->get( '/not-found/should-404/' )
			->assertNotFound();
	}

	/**
	 * Test checking against a Mantle route.
	 */
	public function test_get_mantle_route() {
		$_SERVER['__route_run'] = false;

		// Ensure routing is enabled.
		$this->assertNotNull( $this->app->get_provider( Routing_Service_Provider::class ) );

		// Register a route.
		$this->app['router']->get(
			'/test-route',
			function() {
				$_SERVER['__route_run'] = true;
				return 'yes';
			}
		);

		$this->get( '/test-route' )
			->assertOk()
			->assertContent( 'yes' );

		$this->assertTrue( $_SERVER['__route_run'] );
	}

	public function test_get_mantle_route_404() {
		// Ensure routing is enabled.
		$this->assertNotNull( $this->app->get_provider( Routing_Service_Provider::class ) );

		// Register a route.
		$this->app['router']->get(
			'/test-route-404',
			function() {
				return response()->make( 'not-found', 404 );
			}
		);

		$this->get( '/test-route-404' )
			->assertNotFound()
			->assertContent( 'not-found' );
	}

	public function test_post_mantle_route() {
		// Ensure routing is enabled.
		$this->assertNotNull( $this->app->get_provider( Routing_Service_Provider::class ) );

		// Register a route.
		$this->app['router']->post(
			'/test-post',
			function() {
				return new Response( 'yes', 201, [ 'test-header' => 'test-value' ] );
			}
		);

		$this->app['router']->get(
			'/404',
			function() {
				return new Response( 'yes', 404 );
			}
		);

		$this->post( '/test-post' )
			->assertCreated()
			->assertHeader( 'test-header', 'test-value' )
			->assertContent( 'yes' );

		$this->get( '/404' )->assertNotFound();
	}

	public function test_rest_api_route() {
		$post_id = static::factory()->post->create();

		$this->get( rest_url( "wp/v2/posts/{$post_id}" ) )
			->assertOk()
			->assertJsonPath( 'id', $post_id )
			->assertJsonPath( 'title.rendered', get_the_title( $post_id ) )
			->assertJsonPathExists( 'guid' )
			->assertJsonPathMissing( 'example_path' );
	}

	public function test_rest_api_route_error() {
		$this->get( rest_url( '/an/unknown/route' ) )
			->assertStatus( 404 );
	}

	public function test_redirect_response() {
		$this->app['router']->get(
			'/route-to-redirect/',
			fn () => redirect()->to( '/redirected', 302, [ 'Other-Header' => '123' ] ),
		);

		$this->get( '/route-to-redirect/' )
			->assertHeader( 'location', home_url( '/redirected' ) )
			->assertHeader( 'Location', home_url( '/redirected' ) )
			->assertRedirect( '/redirected' )
			->assertHeader( 'Other-Header', '123' );
	}

	public function test_multiple_requests() {
		// Re-run all test methods on this class in a single pass.
		foreach ( get_class_methods( $this ) as $method ) {
			if ( __FUNCTION__ === $method || 'test_' !== substr( $method, 0, 5 ) ) {
				continue;
			}

			$this->$method();
		}
	}
}
