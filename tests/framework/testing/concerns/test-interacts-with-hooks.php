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
		$this->expectAction( 'action_to_check' )
			->once()
			->with( 'value_to_check' );

		do_action( 'action_to_check', 'value_to_check' );
	}

	public function test_filter_applied_declaration() {
		$this->expectFilter( 'filter_to_check' )
			->once()
			->with( 'value_to_compare' )
			->andReturn( 'updated_value' );

		$this->assertEquals(
			'updated_value',
			apply_filters( 'value_to_compare', 'value_to_compare' )
		);
	}
}
