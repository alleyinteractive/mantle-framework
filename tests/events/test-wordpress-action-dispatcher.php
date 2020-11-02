<?php
namespace Mantle\Tests\Events;

use Mantle\Framework\Events\Dispatcher;
use Exception;
use Mantle\Framework\Container\Container;
use Mantle\Framework\Support\Collection;
use Mantle\Framework\Testing\Framework_Test_Case;
use ReflectionParameter;
use RuntimeException;
use WP_Query;

use function Mantle\Framework\Helpers\add_action;
use function Mantle\Framework\Helpers\add_filter;
use function Mantle\Framework\Helpers\collect;

class Test_WordPress_Action_Dispatcher extends Framework_Test_Case {
	public function test_action_handler() {
		$_SERVER['__action_fired'] = false;

		$d = new Dispatcher( $this->app );
		$d->action(
			'action_to_listen',
			function() {
				$_SERVER['__action_fired'] = true;
			}
		);

		do_action( 'action_to_listen' );

		$this->assertTrue( $_SERVER['__action_fired'] );

		$_SERVER['__action_value'] = 'a string when it should be an array';
		$d->action(
			'test_action_handler',
			function( array $posts ) {
				$_SERVER['__action_value'] = $posts;
			}
		);

		do_action( 'test_action_handler', $_SERVER['__action_value'] );

		$this->assertIsArray( $_SERVER['__action_value'] );
	}

	public function test_action_handler_non_builtin_typecast() {
		$_SERVER['__action_value'] = [ 1, 2, 3 ];

		$d = new Dispatcher( $this->app );
		$d->action(
			'test_action_handler_non_builtin_typecast',
			function( Collection $posts ) {
				$_SERVER['__action_value'] = $posts;
			}
		);

		do_action( 'test_action_handler_non_builtin_typecast', $_SERVER['__action_value'] );

		$this->assertInstanceOf( Collection::class, $_SERVER['__action_value'] );
	}

	public function test_action_handler_non_builtin_typecast_to_array() {
		$_SERVER['__action_value'] = collect( [ 1, 2, 3 ] );

		$d = new Dispatcher( $this->app );
		$d->action(
			'test_action_handler_non_builtin_typecast_to_array',
			function( array $posts ) {
				$_SERVER['__action_value'] = $posts;
			}
		);

		do_action( 'test_action_handler_non_builtin_typecast_to_array', $_SERVER['__action_value'] );

		$this->assertIsArray( $_SERVER['__action_value'] );

		// Assert that it converted it to an array of values.
		$this->assertEquals( [ 1, 2, 3 ], $_SERVER['__action_value'] );
	}

	public function test_action_handler_invalid_typehint() {
		$_SERVER['__action_value'] = [ 1, 2, 3 ];

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Unknown type hinted parameter on callback: [WP_Query]' );

		$this->app->set_environment( 'testing' );
		$d = new Dispatcher( $this->app );
		$d->action(
			'test_action_handler_invalid_typehint',
			function( \WP_Query $posts ) {
				$_SERVER['__action_value'] = $posts;
			}
		);

		do_action( 'test_action_handler_invalid_typehint', $_SERVER['__action_value'] );
	}

	/**
	 * In production this should be handled gracefully.
	 *
	 * @return void
	 */
	public function test_action_handler_invalid_typehint_production() {
		$this->setExpectedIncorrectUsage( 'validate_argument_type' );

		$_SERVER['__action_value'] = [ 1, 2, 3 ];

		$this->app->set_environment( 'production' );
		$d = new Dispatcher( $this->app );
		$d->action(
			'test_action_handler_invalid_typehint_production',
			function( WP_Query $posts ) {
				$_SERVER['__action_value'] = $posts;
			}
		);

		do_action( 'test_action_handler_invalid_typehint_production', $_SERVER['__action_value'] );
		$this->assertInstanceOf( WP_Query::class, $_SERVER['__action_value'] );
	}

	/**
	 * Allow the unknown type hint to be handled via an event filter.
	 */
	public function test_action_handler_invalid_typehint_override() {
		$_SERVER['__action_value'] = [ 1, 2, 3 ];

		$this->app->set_environment( 'testing' );
		$d = new Dispatcher( $this->app );
		$d->listen(
			'event-typehint:WP_Query',
			function( $arg, $parameter ) {
				$this->assertIsArray( $arg );
				$this->assertInstanceOf( ReflectionParameter::class, $parameter );

				$instance = new WP_Query();
				$instance->posts = $arg;
				return $instance;
			}
		);

		$d->action(
			'test_action_handler_invalid_typehint_override',
			function( \WP_Query $posts ) {
				$_SERVER['__action_value'] = $posts;
			}
		);

		do_action( 'test_action_handler_invalid_typehint_override', $_SERVER['__action_value'] );

		$this->assertInstanceOf( WP_Query::class, $_SERVER['__action_value'] );
		$this->assertEquals( [ 1, 2, 3 ], $_SERVER['__action_value']->posts );
	}

	public function test_filter_handler() {
		$this->app['events']->filter(
			'test_filter_handler',
			function( string $string ) {
				return strtoupper( $string );
			}
		);

		$this->assertEquals(
			'TOUPPERCASE',
			apply_filters( 'test_filter_handler', 'touppercase' )
		);
	}

	public function test_filter_handler_typehint() {
		$this->app['events']->filter(
			'test_filter_handler_typehint',
			function( array $string ) {
				return $string;
			}
		);

		$this->assertEquals(
			[ 'touppercase' ],
			apply_filters( 'test_filter_handler_typehint', 'touppercase' )
		);
	}

	public function test_helpers() {
		add_filter(
			'test_filter_handler_typehint',
			function( array $value ) {
				return $value;
			}
		);

		$this->assertEquals(
			[ 'touppercase' ],
			apply_filters( 'test_filter_handler_typehint', 'touppercase' )
		);

		$_SERVER['__test'] = false;
		add_action(
			'test_action_to_fire',
			function() {
				$_SERVER['__test'] = true;
			}
		);

		do_action( 'test_action_to_fire' );
		$this->assertTrue( $_SERVER['__test'] );
	}
}
