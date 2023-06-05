<?php

namespace Mantle\Tests\Http;

use Mantle\Application\Application;
use Mantle\Database\Model\Post;
use Mantle\Events\Dispatcher;
use Mantle\Http\Request;
use Mantle\Http\Routing\Middleware\Substitute_Bindings;
use Mantle\Http\Routing\Router;
use Mantle\Http\Routing\Url_Generator;
use Mantle\Testing\Framework_Test_Case;

class Test_URL_Generator extends Framework_Test_Case {
	protected Router $router;

	protected Url_Generator $url;

	public function setUp(): void {
		parent::setUp();

		$this->router = $this->app['router'];
		$this->url    = $this->app['url'];

		$this->set_permalink_structure( '/%postname%/' );
	}

	public function test_basic_generation() {
		$this->assertEquals( home_url( '/' ), $this->url->to( '/' ) );
		$this->assertEquals( home_url( '/foo/' ), $this->url->to( '/foo/' ) );
		$this->assertEquals( home_url( '/foo/bar' ), $this->url->to( '/foo/bar' ) );
		$this->assertEquals( home_url( '/foo/bar/' ), $this->url->to( '/foo/bar/' ) );
		$this->assertEquals( home_url( '/foo/bar/?example=true' ), $this->url->to( '/foo/bar/', [ 'example' => true ] ) );
	}

	public function test_route_generation() {

	}

	// public function test_trailing_slash_routing() {
	// 	$this->set_permalink_structure( '/%postname%/' );

	// 	$router = $this->get_router();
	// 	$router->get( 'foo/bar/', fn () => 'hello' )->name( 'foo' );

	// 	$router->sync_routes_to_url_generator();

	// 	$this->assertSame( '/foo/bar/', route( 'foo' ) );

	// 	$this->get( '/foo/bar/' )
	// 		->assertContent( 'hello' );

	// 	$this->get( '/foo/bar' )
	// 		->assertRedirect( '/foo/bar/' );
	// }
}
