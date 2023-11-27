<?php

namespace Mantle\Tests\Http;

use Mantle\Application\Application;
use Mantle\Database\Model\Post;
use Mantle\Events\Dispatcher;
use Mantle\Http\Request;
use Mantle\Http\Routing\Middleware\Substitute_Bindings;
use Mantle\Http\Routing\Router;
use Mantle\Testing\Framework_Test_Case;

class Test_Router extends Framework_Test_Case {
	public function test_basic_dispatching() {
		$router = $this->get_router();
		$router->get(
			'foo/bar',
			function () {
				return 'hello';
			}
		);
		$this->assertSame( 'hello', $router->dispatch( Request::create( 'foo/bar', 'GET' ) )->getContent() );

		$router = $this->get_router();
		$router->get(
			'foo/bar',
			function () {
				return 'hello';
			}
		);
		$router->post(
			'foo/bar',
			function () {
				return 'post hello';
			}
		);
		$this->assertSame( 'hello', $router->dispatch( Request::create( 'foo/bar', 'GET' ) )->getContent() );
		$this->assertSame( 'post hello', $router->dispatch( Request::create( 'foo/bar', 'POST' ) )->getContent() );

		$router = $this->get_router();
		$router->get(
			'foo/{bar}',
			function ( $name ) {
				return $name;
			}
		);
		$this->assertSame( 'taylor', $router->dispatch( Request::create( 'foo/taylor', 'GET' ) )->getContent() );

		$router = $this->get_router();
		$router->get(
			'foo/{file}',
			function ( $file ) {
				return $file;
			}
		);
		$this->assertSame( 'oxygen%20', $router->dispatch( Request::create( 'http://test.com/foo/oxygen%2520', 'GET' ) )->getContent() );

		$router = $this->get_router();
		$router->patch(
			'foo/bar',
			array(
				'as'       => 'foo',
				'callback' => function () {
					return 'bar';
				},
			)
		);
		$this->assertSame( 'bar', $router->dispatch( Request::create( 'foo/bar', 'PATCH' ) )->getContent() );

		$router = $this->get_router();
		$router->get( 'head/request', fn () => 'hello' );

		$this->assertEmpty(
			$router->dispatch( Request::create('head/request', 'HEAD' ) )->getContent()
		);

		$router = $this->get_router();
		$router->any( 'any/request', fn () => 'hello' );

		$this->assertSame( 'hello', $router->dispatch( Request::create( 'any/request', 'GET' ) )->getContent() );
		$this->assertSame( 'hello', $router->dispatch( Request::create( 'any/request', 'POST' ) )->getContent() );
		$this->assertEmpty( $router->dispatch( Request::create( 'any/request', 'HEAD' ) )->getContent() );

		$router = $this->get_router();
		$router->get(
			'foo/bar',
			function () {
				return 'first';
			}
		);
		$router->get(
			'foo/bar',
			function () {
				return 'second';
			}
		);
		$this->assertSame( 'second', $router->dispatch( Request::create( 'foo/bar', 'GET' ) )->getContent() );

		$router = $this->get_router();
		$router->get(
			'foo/bar/åαф',
			function () {
				return 'hello';
			}
		);
		$this->assertSame( 'hello', $router->dispatch( Request::create( 'foo/bar/%C3%A5%CE%B1%D1%84', 'GET' ) )->getContent() );

		$router = $this->get_router();
		$router->get(
			'foo/bar',
			[
				'boom'     => 'auth',
				'callback' => fn () => 'closure',
			]
		);

		$this->assertSame( 'closure', $router->dispatch( Request::create( 'foo/bar', 'GET' ) )->getContent() );
	}

	public function testClosureMiddleware() {
		   $router      = $this->get_router();
			$middleware = function ( $request, $next ) {
					return 'caught';
			};
			$router->get(
				'foo/bar',
				array(
					'middleware' => $middleware,
					function () {
						return 'hello';
					},
				)
			);
			$this->assertSame( 'caught', $router->dispatch( Request::create( 'foo/bar', 'GET' ) )->getContent() );
	}

	public function test_route_binding() {
			$router = $this->get_router();
			$router->bind(
				'name',
				function ( $value ) {
					return strtoupper( $value );
				}
			);

			$router->get(
				'foo/{name}',
				array(
					'middleware' => Substitute_Bindings::class,
					'callback'   => function ( $name ) {
						return $name;
					},
				)
			);
			$this->assertSame( 'TAYLOR', $router->dispatch( Request::create( 'foo/taylor', 'GET' ) )->getContent() );
	}

	public function test_model_binding() {
		$router = $this->get_router();
		$router->get(
			'foo/{bar}',
			array(
				'middleware' => Substitute_Bindings::class,
				'callback'   => function ( $name ) {
					return $name;
				},
			)
		);
		$router->bind_model( 'bar', RouteModelBindingStub::class );
		$this->assertSame( 'TAYLOR', $router->dispatch( Request::create( 'foo/taylor', 'GET' ) )->getContent() );
	}

	public function test_implicit_bindings() {
		$router = $this->get_router();

		$router->get(
			'foo/{bar}',
			array(
				'middleware' => Substitute_Bindings::class,
				'callback'   => function ( Routing_Test_User_Model $bar ) {
					$this->assertInstanceOf( Routing_Test_User_Model::class, $bar );

					return $bar->get( 'route_key' );
				},
			)
		);

		$router->get(
			'names/{two}/{three}',
			array(
				'middleware' => Substitute_Bindings::class,
				'callback'   => function ( Routing_Test_User_Model $two, Routing_Test_User_Model $three ) {
					$this->assertInstanceOf( Routing_Test_User_Model::class, $two );
					$this->assertInstanceOf( Routing_Test_User_Model::class, $three );

					return $two->get( 'route_key' ) . '-' . $three->get( 'route_key' );
				},
			)
		);

		$this->assertSame( 'testable-name', $router->dispatch( Request::create( 'foo/testable-name', 'GET' ) )->getContent() );
		$this->assertSame( 'secondary-name', $router->dispatch( Request::create( 'foo/secondary-name', 'GET' ) )->getContent() );

		// Test with multiple models.
		$this->assertSame( 'first_name-last_name', $router->dispatch( Request::create( 'names/first_name/last_name', 'GET' ) )->getContent() );
	}

	public function test_route_typehint_non_model() {
		$router = $this->get_router();

		$router->get(
			'example/non-binding/{who}',
			[
				'middleware' => Substitute_Bindings::class,
				'callback'   => fn ( string $who ) => "Hello {$who}",
			],
		);

		// Test without a model typehint.
		$this->assertSame(
			'Hello sean',
			$router->dispatch( Request::create( 'example/non-binding/sean', 'GET' ) )->getContent(),
		);
	}

	// Ensure that a route with multiple variables and a single type-hinted one
	// works properly.
	public function test_implicit_binding_multiple_variables() {
		$router = $this->get_router();

		$router->get(
			'{year}/{month}/{day}/{post}',
			array(
				'middleware' => Substitute_Bindings::class,
				'callback'   => function ( $year, $month, $day, Routing_Test_Post_Model $post ) {
					$this->assertInstanceOf( Routing_Test_Post_Model::class, $post );

					return $post->name();
				},
			)
		);

		$post = static::factory()->post->create_and_get();

		$this->assertSame( $post->post_title, $router->dispatch( Request::create( 'year/month/day/' . $post->post_name, 'GET' ) )->getContent() );
	}

	public function test_route_name_url() {
		$router = $this->get_router();
		$router->get(
			'/example-route-url',
			[
				'callback' => fn () => 'response',
				'name' => 'test_route_name_url',
			]
		);

		$router->sync_routes_to_url_generator();

		$this->assertEquals( home_url( '/example-route-url' ), route( 'test_route_name_url' ) );
	}

	public function test_fluent_routing() {
		$router = $this->get_router();

		$router->get( '/test_fluent_routing' )
			->callback( function() {} )
			->name( 'route-name' );

		$router->get( '/test_fluent_routing_with_var/{var}' )
				->callback( function() {} )
				->name( 'route-name-with-var' );

		$router->sync_routes_to_url_generator();

		$this->assertEquals( home_url( '/test_fluent_routing' ), route( 'route-name' ) );
		$this->assertEquals(
			home_url( '/test_fluent_routing_with_var/var_to_compare' ),
			route( 'route-name-with-var', [ 'var' => 'var_to_compare' ] )
		);
	}

	public function test_middleware_group_registration() {
		$router = $this->get_router();

		$router->middleware( Testable_Middleware_Router::class )->group(
			fn () => $router->get( '/example-route-with-middleware', fn () => 'The response' )->name( 'example-route' ),
		);

		$this->get( '/example-route-with-middleware' )->assertContent( 'The response' );

		$this->assertEquals( '/example-route-with-middleware', $_SERVER['__middleware'] );
	}

	public function test_without_middleware_registration() {
		$_SERVER['__middleware'] = $_SERVER['closure_middleware'] = null;

		$router = $this->get_router();

		$router->middleware( Testable_Middleware_Router::class )->group(
			fn () => $router
				->get( '/example-route-with-middleware-without', fn () => 'The response' )
				->middleware( function ( $request, $next ) {
					$_SERVER['closure_middleware'] = true;

					return $next( $request );
				} )
				->without_middleware( Testable_Middleware_Router::class )
		);

		$this->get( '/example-route-with-middleware-without' )->assertContent( 'The response' );

		$this->assertEmpty( $_SERVER['__middleware'] );
		$this->assertTrue( $_SERVER['closure_middleware'] );
	}

	public function test_route_prefix() {
		$router = $this->get_router();

		$router->prefix( 'example-prefix' )->group(
			fn () => $router->get( '/example-route-prefix', fn () => 'The response' )->name( 'example-route' ),
		);

		$router->sync_routes_to_url_generator();

		$this->get( '/example-prefix/example-route-prefix' )->assertContent( 'The response' );

		$this->assertStringEndsWith( '/example-prefix/example-route-prefix', route( 'example-route' ) );
	}

	public function test_route_name_prefix() {
		$router = $this->get_router();

		$router->name( 'example-prefix.' )->group(
			fn () => $router->get( '/example-route-prefix', fn () => 'The response' )->name( 'example-route' ),
		);

		$router->sync_routes_to_url_generator();

		$this->get( '/example-route-prefix' )->assertContent( 'The response' );

		$this->assertStringEndsWith( '/example-route-prefix', route( 'example-prefix.example-route' ) );
	}

	protected function get_router(): Router {
		$router = new Router( $this->app['events'], $this->app );

		$this->app['router'] = $router;

		$this->app->instance( \Mantle\Contracts\Http\Routing\Router::class, $router );
		$this->app->instance( 'request', new Request() );

		return $router;
	}
}

class RouteModelBindingStub extends Post {

	public function get_route_key_name(): string {
		return 'route_key';
	}

	public function resolve_route_binding( $value, $field = null ) {
		$this->set_attribute( $field ?? $this->get_route_key_name(), $value );
		return $this->first();
	}

	public function first() {
		return strtoupper( $this->route_key );
	}
}

class Routing_Test_User_Model extends Post {

	public function get_route_key_name(): string {
		return 'route_key';
	}

	public function where( $key, $value ) {
		$this->value = $value;

		return $this;
	}

	public function resolve_route_binding( $value, $field = null ) {
		$this->set_attribute( $field ?? $this->get_route_key_name(), $value );
		return $this;
	}

	public function first() {
		return $this;
	}

	public function firstOrFail() {
		return $this;
	}
}

class Routing_Test_Post_Model extends Post {
	public static $object_name = 'post';
}

class Testable_Middleware_Router {
	public function handle( Request $request, $next ) {
		$_SERVER['__middleware'] = $request->getPathInfo();

		return $next( $request );
	}
}
