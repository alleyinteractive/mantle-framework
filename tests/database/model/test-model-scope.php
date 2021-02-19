<?php
namespace Mantle\Tests\Database\Model;

use Faker\Factory as Faker;
use Mantle\Framework\Contracts\Database\Scope;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Post;
use Mantle\Database\Query\Builder;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Testing\Framework_Test_Case;

class Test_Model_Scope extends Framework_Test_Case {
	public function test_local_scope() {
		$post_id = $this->get_random_post_id();

		$this->assertEmpty( Testable_Post_For_Scope::active()->get()->to_array() );

		// Now set the post as the active one.
		update_post_meta( $post_id, 'active', '1' );

		$this->assertEquals( $post_id, Testable_Post_For_Scope::active()->first()->id() );
	}

	public function test_multiple_local_scopes() {
		$post_id = $this->get_random_post_id();

		$this->assertEmpty( Testable_Post_For_Scope::active()->ofType( 'type-to-check' )->get()->to_array() );

		// Now set the post as the active one.
		update_post_meta( $post_id, 'active', '1' );

		// Should still be inactive.
		$this->assertEmpty( Testable_Post_For_Scope::active()->ofType( 'type-to-check' )->get() );

		update_post_meta( $post_id, 'type', 'type-to-check' );

		$this->assertEquals( $post_id, Testable_Post_For_Scope::active()->ofType( 'type-to-check' )->first()->id() );
	}

	public function test_global_scope() {
		$post_id = $this->get_random_post_id();

		$this->assertEmpty( Testable_Post_For_Scope_Global_Scope::first() );
		update_post_meta( $post_id, 'global_scope_active', '1' );

		$this->assertEquals( $post_id, Testable_Post_For_Scope_Global_Scope::first()->id() );
	}

	public function test_global_scope_class() {
		$post_id = $this->get_random_post_id();

		$this->assertEmpty( Testable_Post_For_Scope_Global_Scope_Class::first() );
		update_post_meta( $post_id, 'global_scope_named_active', '1' );

		$this->assertEquals( $post_id, Testable_Post_For_Scope_Global_Scope_Class::first()->id() );
	}

	/**
	 * Get a random post ID, ensures the post ID is not the last in the set.
	 *
	 * @todo Replace with a model factory.
	 * @return integer
	 */
	protected function get_random_post_id( $args = [] ): int {
		$faker    = Faker::create();
		$post_ids = [];

		for ( $i = 0; $i <= 5; $i++ ) {
			$post_ids[] = Testable_Post_For_Scope::create(
				[
					'content' => $faker->paragraph,
					'name'    => $faker->name,
					'status'  => 'publish',
				]
			)->id();
		}

		return $post_ids[ array_rand( $post_ids ) ];
	}
}

class Testable_Post_For_Scope extends Post {
	public static $object_name = 'post';

	public function scopeActive( Post_Query_Builder $query ) {
		return $query->whereMeta( 'active', '1' );
	}

	public function scopeOfType( Post_Query_Builder $query, string $type ) {
		return $query->whereMeta( 'type', $type );
	}
}

class Testable_Post_For_Scope_Global_Scope extends Testable_Post_For_Scope {

	protected static function boot() {
		parent::boot();

		static::add_global_scope(
			'active',
			function( Post_Query_Builder $query ) {
				return $query->whereMeta( 'global_scope_active', '1' );
			}
		);
	}
}

class Testable_Post_For_Scope_Global_Scope_Class extends Testable_Post_For_Scope {

	protected static function boot() {
		parent::boot();

		static::add_global_scope( new Test_Scope() );
	}
}

class Test_Scope implements Scope {
	public function apply( Builder $builder, Model $model ) {
		return $builder->whereMeta( 'global_scope_named_active', '1' );
	}
}
