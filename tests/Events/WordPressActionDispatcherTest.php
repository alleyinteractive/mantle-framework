<?php
namespace Mantle\Tests\Events;

use Mantle\Events\Dispatcher;
use Mantle\Support\Collection;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

use function Mantle\Support\Helpers\add_action;
use function Mantle\Support\Helpers\add_filter;
use function Mantle\Support\Helpers\collect;

/**
 * @group events
 */
#[Group( 'events' )]
class WordPressActionDispatcherTest extends FrameworkTestCase {
	public function test_action_handler(): void {
		$_SERVER['__action_fired'] = false;

		$d = new Dispatcher( $this->app );
		$d->action(
			'action_to_listen',
			static function() {
				$_SERVER['__action_fired'] = true;
			}
		);

		do_action( 'action_to_listen' );

		$this->assertTrue( $_SERVER['__action_fired'] );

		$_SERVER['__action_value'] = 'a string when it should be an array';
		$d->action(
			'test_action_handler',
			static function( array $posts ) {
				$_SERVER['__action_value'] = $posts;
			}
		);

		do_action( 'test_action_handler', $_SERVER['__action_value'] );

		$this->assertIsArray( $_SERVER['__action_value'] );
	}

	public function test_action_handler_non_builtin_typecast(): void {
		$_SERVER['__action_value'] = [ 1, 2, 3 ];

		$d = new Dispatcher( $this->app );
		$d->action(
			'test_action_handler_non_builtin_typecast',
			static function( Collection $posts ) {
				$_SERVER['__action_value'] = $posts;
			}
		);

		do_action( 'test_action_handler_non_builtin_typecast', $_SERVER['__action_value'] );

		$this->assertInstanceOf( Collection::class, $_SERVER['__action_value'] );
	}

	public function test_action_handler_non_builtin_typecast_to_array(): void {
		$_SERVER['__action_value'] = collect( [ 1, 2, 3 ] );

		$d = new Dispatcher( $this->app );
		$d->action(
			'test_action_handler_non_builtin_typecast_to_array',
			static function( array $posts ) {
				$_SERVER['__action_value'] = $posts;
			}
		);

		do_action( 'test_action_handler_non_builtin_typecast_to_array', $_SERVER['__action_value'] );

		$this->assertIsArray( $_SERVER['__action_value'] );

		// Assert that it converted it to an array of values.
		$this->assertSame( [ 1, 2, 3 ], $_SERVER['__action_value'] );
	}

	public function test_action_if_should_run(): void {
		$_SERVER['test_action_if_should_run'] = false;

		$d = new Dispatcher( $this->app );
		$d->action_if(
			static fn () => true,
			'test_action_if_should_run',
			static fn () => $_SERVER['test_action_if_should_run'] = true,
		);

		do_action( 'test_action_if_should_run' );

		$this->assertTrue( $_SERVER['test_action_if_should_run'] ?? false );
	}

	public function test_action_if_should_not_run(): void {
		$_SERVER['test_action_if_should_not_run'] = false;

		$d = new Dispatcher( $this->app );
		$d->action_if(
			static fn () => false,
			'test_action_if_should_not_run',
			static fn () => $_SERVER['test_action_if_should_not_run'] = true,
		);

		$d->action_if(
			false,
			'test_action_if_should_not_run',
			static fn () => $_SERVER['test_action_if_should_not_run'] = true,
		);

		do_action( 'test_action_if_should_not_run' );

		$this->assertFalse( $_SERVER['test_action_if_should_not_run'] ?? false );
	}

	public function test_filter_handler(): void {
		app()['events']->filter(
			'test_filter_handler',
			static fn( string $string ) => strtoupper( $string ),
		);

		$this->assertSame(
			'TOUPPERCASE',
			apply_filters( 'test_filter_handler', 'touppercase' )
		);
	}

	public function test_filter_handler_typehint(): void {
		app()['events']->filter(
			'test_filter_handler_typehint',
			static fn ( array $string ) => $string,
		);

		$this->assertSame(
			[ 'string-in-array' ],
			apply_filters( 'test_filter_handler_typehint', 'string-in-array' )
		);
	}

	public function test_filter_handler_typehint_collection_returns_array(): void {
		app()['events']->filter(
			'test_filter_handler_typehint_collection_returns_array',
			static fn ( Collection $col ): array => $col->to_array(),
		);

		$this->assertSame(
			[ 'array-not-collection' ],
			apply_filters( 'test_filter_handler_typehint_collection_returns_array', [ 'array-not-collection' ] )
		);
	}

	public function test_helpers(): void {
		add_filter(
			'test_filter_handler_typehint',
			static fn ( array $value ) => $value,
		);

		$this->assertSame(
			[ 'string-in-array' ],
			apply_filters( 'test_filter_handler_typehint', 'string-in-array' )
		);

		$_SERVER['__test'] = false;
		add_action(
			'test_action_to_fire',
			static function() {
				$_SERVER['__test'] = true;
			}
		);

		do_action( 'test_action_to_fire' );
		$this->assertTrue( $_SERVER['__test'] );
	}

	public function test_typehint_action_argument(): void {
		$_SERVER['__test'] = false;

		add_action(
			Example_Typehint_Event::class,
			static function ( Example_Typehint_Event $event ): void {
				$_SERVER['__test'] = $event;
			}
		);

		app()['events']->dispatch( new Example_Typehint_Event( 'test' ) );

		$this->assertInstanceOf( Example_Typehint_Event::class, $_SERVER['__test'] );
		$this->assertSame( 'test', $_SERVER['__test']->example );
	}

	public function test_mixed_typehint_action_argument(): void {
		$_SERVER['__test'] = null;

		$d = new Dispatcher( $this->app );
		$d->action(
			'test_mixed_typehint_action_argument',
			static function ( mixed $value ): void {
				$_SERVER['__test'] = $value;
			}
		);

		do_action( 'test_mixed_typehint_action_argument', [ 'a' => 1 ] );

		$this->assertSame( [ 'a' => 1 ], $_SERVER['__test'] );
	}

	public function test_wildcard_listener(): void {
		$listener = [];

		$d = new Dispatcher( $this->app );
		$d->listen( 'example:*', static function ( string $event_name, ...$args ) use ( &$listener ) {
			$listener[] = [ $event_name, ...$args ];
		} );

		do_action( 'test_wildcard_listener' );
		do_action( 'test_wildcard_listener', 'foo' );
		do_action( 'example:test_wildcard_listener' );
		do_action( 'example:test_wildcard_listener', 'bar', 'baz' );
		do_action( 'example:another_test_wildcard_listener' );

		$this->assertCount( 3, $listener );
		$this->assertSame( [
			[ 'example:test_wildcard_listener' ],
			[ 'example:test_wildcard_listener', 'bar', 'baz' ],
			[ 'example:another_test_wildcard_listener' ],
		], $listener );
	}
}

class Example_Typehint_Event {
	public function __construct( public string $example ) {}
}
