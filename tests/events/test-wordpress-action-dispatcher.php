<?php
namespace Mantle\Tests\Events;

use Mantle\Events\Dispatcher;
use Mantle\Support\Collection;
use Mantle\Testing\Framework_Test_Case;

use function Mantle\Support\Helpers\add_action;
use function Mantle\Support\Helpers\add_filter;
use function Mantle\Support\Helpers\collect;

/**
 * @group events
 */
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

	public function test_action_if_should_run() {
		$_SERVER['test_action_if_should_run'] = false;

		$d = new Dispatcher( $this->app );
		$d->action_if(
			fn () => true,
			'test_action_if_should_run',
			fn () => $_SERVER['test_action_if_should_run'] = true,
		);

		do_action( 'test_action_if_should_run' );

		$this->assertTrue( $_SERVER['test_action_if_should_run'] ?? false );
	}

	public function test_action_if_should_not_run() {
		$_SERVER['test_action_if_should_not_run'] = false;

		$d = new Dispatcher( $this->app );
		$d->action_if(
			fn () => false,
			'test_action_if_should_not_run',
			fn () => $_SERVER['test_action_if_should_not_run'] = true,
		);

		$d->action_if(
			false,
			'test_action_if_should_not_run',
			fn () => $_SERVER['test_action_if_should_not_run'] = true,
		);

		do_action( 'test_action_if_should_not_run' );

		$this->assertFalse( $_SERVER['test_action_if_should_not_run'] ?? false );
	}

	public function test_filter_handler() {
		$this->app['events']->filter(
			'test_filter_handler',
			fn( string $string ) => strtoupper( $string ),
		);

		$this->assertEquals(
			'TOUPPERCASE',
			apply_filters( 'test_filter_handler', 'touppercase' )
		);
	}

	public function test_filter_handler_typehint() {
		$this->app['events']->filter(
			'test_filter_handler_typehint',
			fn ( array $string ) => $string,
		);

		$this->assertEquals(
			[ 'string-in-array' ],
			apply_filters( 'test_filter_handler_typehint', 'string-in-array' )
		);
	}

	public function test_filter_handler_typehint_collection_returns_array() {
		$this->app['events']->filter(
			'test_filter_handler_typehint_collection_returns_array',
			fn ( Collection $col ): array => $col->to_array(),
		);

		$this->assertEquals(
			[ 'array-not-collection' ],
			apply_filters( 'test_filter_handler_typehint_collection_returns_array', [ 'array-not-collection' ] )
		);
	}

	public function test_helpers() {
		add_filter(
			'test_filter_handler_typehint',
			fn ( array $value ) => $value,
		);

		$this->assertEquals(
			[ 'string-in-array' ],
			apply_filters( 'test_filter_handler_typehint', 'string-in-array' )
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
