<?php
namespace Mantle\Tests\Framework\Testing\Concerns;

use Mantle\Framework\Testing\Framework_Test_Case;

class Test_Interacts_With_Hooks extends Framework_Test_Case {
	public function test_hook_applied() {
		$this->assertHookNotApplied( 'hook_to_check' );
		$this->assertHookNotApplied( 'filter_to_check' );

		do_action( 'hook_to_check' );
		apply_filters( 'filter_to_check', null );

		$this->assertHookApplied( 'hook_to_check', 1 );
		$this->assertHookApplied( 'filter_to_check', 1 );
	}

	public function test_hook_applied_declaration() {
		$this->expectApplied( 'action_to_check' )
			->twice()
			->with( 'value_to_check', 'secondary_value_to_check' );

		$this->expectApplied( 'action_that_shouldnt_fire' )->never();

		do_action( 'action_to_check', 'value_to_check', 'secondary_value_to_check' );
		do_action( 'action_to_check', 'value_to_check', 'secondary_value_to_check' );

		$this->expectApplied( 'falsey_filter_to_check' )
			->once()
			->andReturnFalse();

		add_filter( 'falsey_filter_to_check', '__return_false' );
		apply_filters( 'falsey_filter_to_check', true );
	}

	public function test_hook_added_declaration() {
		$this->expectAdded( 'hook_to_add' )
			->once()
			->andReturn( true );

		add_action( 'hook_to_add', '__return_true' );

		$this->expectAdded( 'filter_to_add', '__return_true' );

		add_filter( 'filter_to_add', '__return_true' );
	}

	// public function test_filter_applied_declaration() {
	// 	$this->expectFilterApplied( 'filter_to_check' )
	// 		->once()
	// 		->with( 'value_to_compare' )
	// 		->andReturn( 'updated_value' );

	// 	$this->assertEquals(
	// 		'updated_value',
	// 		apply_filters( 'value_to_compare', 'value_to_compare' )
	// 	);
	// }
}
