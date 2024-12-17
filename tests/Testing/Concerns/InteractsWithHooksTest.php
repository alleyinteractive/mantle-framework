<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group testing
 */
#[Group( 'testing' )]
class InteractsWithHooksTest extends Framework_Test_Case {
	public function test_hook_applied() {
		$this->assertHookNotApplied( 'hook_to_check' );
		$this->assertHookNotApplied( 'filter_to_check' );

		do_action( 'hook_to_check' );
		apply_filters( 'filter_to_check', null );

		$this->assertHookApplied( 'hook_to_check', 1 );
		$this->assertHookApplied( 'filter_to_check', 1 );
	}

	public function test_hook_applied_times() {
		$this->expectApplied( 'action_to_check' )->twice();

		do_action( 'action_to_check', 'value_to_check', 'secondary_value_to_check' );
		do_action( 'action_to_check', 'value_to_check', 'secondary_value_to_check' );
	}

	public function test_hook_applied_arguments() {
		$this->expectApplied( 'action_to_check' )
			->twice()
			->with( 'value_to_check', 'secondary_value_to_check' );

		do_action( 'action_to_check', 'value_to_check', 'secondary_value_to_check' );
		do_action( 'action_to_check', 'value_to_check', 'secondary_value_to_check' );
	}

	public function test_hook_applied_arguments_callback() {
		$this->expectApplied( 'action_to_check' )
			->twice()
			->withArgs( fn ( $args ) => 'value' === $args[0] && 'second argument' === $args[1] );

		do_action( 'action_to_check', 'value', 'second argument' );
		do_action( 'action_to_check', 'value', 'second argument' );
	}

	public function test_hook_applied_times_and_returns_false() {
		$this->expectApplied( 'falsey_filter_to_check' )
			->once()
			->andReturnFalse();

		add_filter( 'falsey_filter_to_check', '__return_false' );
		apply_filters( 'falsey_filter_to_check', true );
	}

	public function test_hook_not_applied() {
		$this->expectApplied( 'action_that_shouldnt_fire' )->never();
	}

	public function test_hook_applied_return_boolean() {
		$this->expectApplied( 'true_hook_to_add' )->once()->andReturnTrue();
		$this->expectApplied( 'false_hook_to_add' )->once()->andReturnFalse();
		$this->expectApplied( 'true_hook_to_add' )->once()->andReturnBoolean();
		$this->expectApplied( 'false_hook_to_add' )->once()->andReturnBoolean();

		add_filter( 'true_hook_to_add', '__return_true' );
		add_filter( 'false_hook_to_add', '__return_false' );

		$this->assertTrue( apply_filters( 'true_hook_to_add', false ) );
		$this->assertFalse( apply_filters( 'false_hook_to_add', true ) );
	}

	public function test_hook_applied_return_truthy_falsy() {
		$this->expectApplied( 'truthy_hook_to_add' )->once()->andReturnTruthy();
		$this->expectApplied( 'falsy_hook_to_add' )->once()->andReturnFalsy();

		add_filter( 'truthy_hook_to_add', fn () => 123 );
		add_filter( 'falsy_hook_to_add', fn () => 0 );

		apply_filters( 'truthy_hook_to_add', false );
		apply_filters( 'falsy_hook_to_add', true );
	}

	public function test_hook_applied_return_null() {
		$this->expectApplied( 'null_hook_to_add' )->once()->andReturnNull();

		add_filter( 'null_hook_to_add', '__return_null' );

		$this->assertNull( apply_filters( 'null_hook_to_add', 'not_null' ) );
	}

	public function test_hook_applied_return_empty() {
		$this->expectApplied( 'empty_hook_to_add' )->once()->andReturnEmpty();

		add_filter( 'empty_hook_to_add', fn () => '' );

		$this->assertEmpty( apply_filters( 'empty_hook_to_add', 'not_empty' ) );
	}

	public function test_hook_applied_return_array() {
		$this->expectApplied( 'array_hook_to_add' )->once()->andReturnArray();

		add_filter( 'array_hook_to_add', fn () => [] );

		$this->assertIsArray( apply_filters( 'array_hook_to_add', 'not_array' ) );
	}

	public function test_hook_applied_return_string() {
		$this->expectApplied( 'string_hook_to_add' )->once()->andReturnString();

		add_filter( 'string_hook_to_add', fn () => 'string' );

		$this->assertIsString( apply_filters( 'string_hook_to_add', 'not_string' ) );
	}

	public function test_hook_applied_return_int() {
		$this->expectApplied( 'int_hook_to_add' )->once()->andReturnInteger();

		add_filter( 'int_hook_to_add', fn () => 123 );

		$this->assertIsInt( apply_filters( 'int_hook_to_add', 'not_int' ) );
	}

	public function test_hook_applied_event() {
		$this->expectApplied( Example_Event::class )->once();

		$this->app['events']->dispatch( new Example_Event() );

		$this->assertHookApplied( Example_Event::class, 1 );
	}

	public function test_hook_applied_returns_callback() {
		$passed_value = null;

		$this->expectApplied( 'callback_hook_to_add' )->once()->andReturn(
			function ( $value ) use ( &$passed_value ) {
				$passed_value = $value;

				return 'filtered-value' === $value;
			}
		);

		add_filter( 'callback_hook_to_add', fn () => 'filtered-value' );

		apply_filters( 'callback_hook_to_add', 'passed-value' );
	}

	public function test_hook_added() {
		$this->expectAdded( 'hook_to_add' );

		add_action( 'hook_to_add', '__return_true' );
	}

	public function test_hook_added_with_callback() {
		$this->expectAdded( 'hook_to_add', '__return_true' );

		add_action( 'hook_to_add', '__return_fatrue' );
	}

	// public function test_hook_added_declaration() {
	// 	$this->expectAdded( 'hook_to_add' )
	// 		->once()
	// 		->andReturn( true );

	// 	add_action( 'hook_to_add', '__return_true' );

	// 	$this->expectAdded( 'filter_to_add', '__return_true' );

	// 	add_filter( 'filter_to_add', '__return_true' );
	// }

}

class Example_Event {}
