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
use PHPUnit\Framework\Attributes\DataProvider;

class UrlGeneratorTest extends Framework_Test_Case {
	protected Router $router;

	protected Url_Generator $url;

	public function setUp(): void {
		parent::setUp();

		$this->router = $this->app['router'];
		$this->url    = $this->app['url'];

		$request = new Request( [], [], [], [], [], [
			'SERVER_NAME' => wp_parse_url( home_url(), PHP_URL_HOST ),
			'SERVER_PORT' => 80,
		] );

		$this->url->set_request( $request );
	}

	public function test_home_url_check() {
		// Ensure that home_url() generates the correct URL for asserting against.
		$this->assertStringEndsNotWith( '/', home_url( '/no/trailing/slash' ) );
		$this->assertStringEndsWith( '/', home_url( '/no/trailing/slash/' ) );
	}

	/**
	 * @dataProvider urlGenerationProvider
	 */
	#[DataProvider( 'urlGenerationProvider' )]
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
		/** @var \Mantle\Http\Routing\Router $router */
		$router = $this->app['router'];

		$router->get( '/foo/bar', fn () => 'hello' )->name( 'get' );
		$router->get( '/trailing/slash/', fn () => 'hello' )->name( 'trailing-slash' );
		$router->post( '/baz/post', fn () => 'hello' )->name( 'post' );
		$router->get( '/foo/bar/{biz}', fn () => 'hello' )->name( 'get-with-param' );

		$router->sync_routes_to_url_generator();

		$this->assertEquals( '/foo/bar', $this->url->route( 'get', [], false ) );
		$this->assertEquals( home_url( '/foo/bar' ), $this->url->route( 'get', [], true ) );
		$this->assertEquals( '/foo/bar?query=string', $this->url->route( 'get', [ 'query' => 'string' ], false ) );

		// TODO: Reinforce trailing slash.
		// $this->assertEquals( '/trailing/slash/', $this->url->route( 'trailing-slash', [], false ) );
		// $this->assertEquals( home_url( '/trailing/slash/' ), $this->url->route( 'trailing-slash', [], true ) );

		$this->assertEquals( '/baz/post', $this->url->route( 'post', [], false ) );
		$this->assertEquals( home_url( '/baz/post' ), $this->url->route( 'post', [], true ) );

		$this->assertEquals( '/foo/bar/biz', $this->url->route( 'get-with-param', [ 'biz' => 'biz' ], false ) );
		$this->assertEquals( home_url( '/foo/bar/biz' ), $this->url->route( 'get-with-param', [ 'biz' => 'biz' ], true ) );
	}
}
