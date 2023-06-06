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

class Test_Url_Generator extends Framework_Test_Case {
	protected Router $router;

	protected Url_Generator $url;

	public function setUp(): void {
		parent::setUp();

		$this->router = $this->app['router'];
		$this->url    = $this->app['url'];

		$this->set_permalink_structure( '/%postname%/' );
	}

	public function test_home_url_check() {
		// Ensure that home_url() generates the correct URL for asserting against.
		$this->assertStringEndsNotWith( '/', home_url( '/no/trailing/slash' ) );
		$this->assertStringEndsWith( '/', home_url( '/no/trailing/slash/' ) );
	}

	/**
	 * @dataProvider urlGenerationProvider
	 */
	public function test_basic_generation( $expected, $args ) {
		$this->assertEquals( home_url( $expected ), $this->url->to( ...$args ) );
	}

	public static function urlGenerationProvider() {
		return [
			'/'                              => [ '/', [ '/' ] ],
			'/foo'                           => [ '/foo', [ '/foo' ] ],
			'/foo/bar'                       => [ '/foo/bar', [ '/foo/bar' ] ],
			'/foo/bar?baz=boom'              => [ '/foo/bar?baz=boom', [ '/foo/bar?baz=boom' ] ],
			'/foo/bar?baz=boom&lim=lip'      => [ '/foo/bar?baz=boom&lim=lip', [ '/foo/bar?baz=boom', [ 'lim' => 'lip' ] ] ],
			'/trailing/slash/'               => [ '/trailing/slash/', [ '/trailing/slash/' ] ],
			'/no/trailing/slash'             => [ '/no/trailing/slash', [ 'no/trailing/slash' ] ],
			'/with/query/string'             => [ '/with/query/string?foo=bar&boo=bang', [ '/with/query/string', [ 'foo' => 'bar', 'boo' => 'bang' ] ] ],
			'/with/query/string/'            => [ '/with/query/string/?foo=bar&boo=bang', [ '/with/query/string/', [ 'foo' => 'bar', 'boo' => 'bang' ] ] ],
			'/with/extra/params'             => [ '/with/extra/params/baz/boom', [ '/with/extra/params', [], [ 'baz', 'boom' ] ] ],
			'/with/extra/params/'            => [ '/with/extra/params/baz/boom/', [ '/with/extra/params/', [], [ 'baz', 'boom' ] ] ],
			'/with/extra/params/baz?foo=bar' => [ '/with/extra/params/baz?foo=bar', [ '/with/extra/params', [ 'foo' => 'bar' ], [ 'baz' ] ] ],
		];
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
