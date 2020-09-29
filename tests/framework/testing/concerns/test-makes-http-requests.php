<?php
namespace Mantle\Tests\Framework\Testing\Concerns;

use Mantle\Framework\Http\Response;
use Mantle\Framework\Providers\Routing_Service_Provider;
use Mantle\Framework\Testing\Concerns\Refresh_Database;
use Mantle\Framework\Testing\Framework_Test_Case;

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

		$this->post( '/test-post' )
			->assertCreated()
			->assertHeader( 'test-header', 'test-value' )
			->assertContent( 'yes' );
	}

	public function test_rest_api_route() {
		$post_id = static::factory()->post->create();

		$this->get( rest_url("wp/v2/posts/{$post_id}" ) )
			->assertOk()
			->assertJsonPath( 'id', $post_id )
			->assertJsonPath( 'title.rendered', get_the_title( $post_id ) );
	}

	public function test_multiple_requests() {
		$this->test_get_singular();
		$this->test_get_mantle_route();
		$this->test_post_mantle_route();
		$this->test_rest_api_route();
	}
}
