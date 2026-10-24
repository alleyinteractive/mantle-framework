<?php
namespace Mantle\Tests\Database\Model;

use Faker\Factory as Faker;
use Mantle\Contracts\Database\Scope;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Post;
use Mantle\Database\Query\Builder;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Testing\FrameworkTestCase;

class ModelScopeTest extends FrameworkTestCase {
	public function test_local_scope(): void {
		$post_id = $this->get_random_post_id();

		$this->assertEmpty( Testable_Post_For_Scope::active()->get()->to_array() );

		// Now set the post as the active one.
		update_post_meta( $post_id, 'active', '1' );

		$first = Testable_Post_For_Scope::active()->first();

		$this->assertInstanceOf( Testable_Post_For_Scope::class, $first );
		$this->assertSame( $post_id, $first->id() );
	}

	public function test_multiple_local_scopes(): void {
		$post_id = $this->get_random_post_id();

		$this->assertEmpty( Testable_Post_For_Scope::active()->ofType( 'type-to-check' )->get()->to_array() );

		// Now set the post as the active one.
		update_post_meta( $post_id, 'active', '1' );

		// Should still be inactive.
		$this->assertEmpty( Testable_Post_For_Scope::active()->ofType( 'type-to-check' )->get() );

		update_post_meta( $post_id, 'type', 'type-to-check' );

		$this->assertSame( $post_id, Testable_Post_For_Scope::active()->ofType( 'type-to-check' )->first()->id() );
	}

	public function test_global_scope(): void {
		$post_id = $this->get_random_post_id();

		$this->assertEmpty( Testable_Post_For_Scope_Global_Scope::first() );
		update_post_meta( $post_id, 'global_scope_active', '1' );

		$this->assertSame( $post_id, Testable_Post_For_Scope_Global_Scope::first()->id() );
	}

	public function test_global_scope_class(): void {
		$post_id = $this->get_random_post_id();

		$this->assertEmpty( Testable_Post_For_Scope_Global_Scope_Class::first() );
		update_post_meta( $post_id, 'global_scope_named_active', '1' );

		$this->assertSame( $post_id, Testable_Post_For_Scope_Global_Scope_Class::first()->id() );
	}

	/**
	 * Get a random post ID, ensures the post ID is not the last in the set.
	 *
	 * @todo Replace with a model factory.
	 * @param array<mixed> $args Unused.
	 * @return integer
	 */
	protected function get_random_post_id( $args = [] ): int {
		$faker    = Faker::create();
		$post_ids = [];

		for ( $i = 0; $i <= 5; $i++ ) {
			$post_ids[] = Testable_Post_For_Scope::create(
				[
					'content' => $faker->paragraph(),
					'name'    => $faker->name(),
					'status'  => 'publish',
				]
			)->id();
		}

		return $post_ids[ array_rand( $post_ids ) ];
	}
}

/**
 * @method static Post_Query_Builder<static> active()
 */
class Testable_Post_For_Scope extends Post {
	public static $object_name = 'post';

	/**
	 * @param Post_Query_Builder<static> $query
	 * @return Post_Query_Builder<static>
	 */
	public function scopeActive( Post_Query_Builder $query ): Post_Query_Builder {
		return $query->whereMeta( 'active', '1' );
	}

	/**
	 * @param Post_Query_Builder<static> $query
	 * @return Post_Query_Builder<static>
	 */
	public function scopeOfType( Post_Query_Builder $query, string $type ): Post_Query_Builder {
		return $query->whereMeta( 'type', $type );
	}
}

class Testable_Post_For_Scope_Global_Scope extends Testable_Post_For_Scope {

	protected static function boot(): void {
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

	protected static function boot(): void {
		parent::boot();

		static::add_global_scope( new Test_Scope() );
	}
}

class Test_Scope implements Scope {
	public function apply( Builder $builder, Model $model ): Builder {
		return $builder->whereMeta( 'global_scope_named_active', '1' );
	}
}
