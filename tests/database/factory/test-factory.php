<?php

namespace Mantle\Tests\Database\Factory;

use Mantle\Database\Factory;
use Mantle\Database\Model;
use Mantle\Testing\Framework_Test_Case;

class Test_Factory extends Framework_Test_Case {
	public function test_create_basic_model() {
		$factory = Testable_Post::factory();

		$this->assertInstanceOf( Factory\Factory::class, $factory );
		$this->assertInstanceOf( Factory\Post_Factory::class, $factory );

		$post = $factory->create_and_get();

		$this->assertInstanceOf( Testable_Post::class, $post );
	}

	public function test_create_model_with_custom_factory() {
		$factory = Testable_Post_With_Factory::factory();

		$this->assertInstanceOf( Testable_Post_Factory::class, $factory );

		$post = $factory->create_and_get();

		$this->assertInstanceOf( Testable_Post_With_Factory::class, $post );

		// Ensure that the post's definition was used.
		$this->assertEquals( 'Title from the custom factory', $post->post_title );
	}

	public function test_create_model_with_scope() {
		$post = Testable_Post_With_Factory::factory()->custom_state()->create_and_get();

		$this->assertEquals( 'Title from the custom state', $post->post_title );
	}

	/**
	 * @dataProvider factory_resolve_custom_names
	 */
	public function test_resolve_custom_names( string $model, string $expected ) {
		$this->assertEquals( $expected, Factory\Factory::resolve_custom_factory_name( $model ) );
	}

	public static function factory_resolve_custom_names(): array {
		return [
			'App\\Models\\Post' => [ 'App\\Models\\Post', 'App\\Database\\Factory\\Post_Factory' ],
			'App\\Models\\Term' => [ 'App\\Models\\Term', 'App\\Database\\Factory\\Term_Factory' ],
			'App\\Models\\User' => [ 'App\\Models\\User', 'App\\Database\\Factory\\User_Factory' ],
			'App\\Models\\Example' => [ 'App\\Models\\Example', 'App\\Database\\Factory\\Example_Factory' ],
		];
	}

	/**
	 * @dataProvider factory_resolve_default
	 */
	public function test_resolve_default( string $model, string $expected ) {
		$this->assertEquals( $expected, Factory\Factory::default_factory_name( $model ) );
	}

	public static function factory_resolve_default(): array {
		return [
			Model\Attachment::class => [ Model\Attachment::class, Factory\Post_Factory::class ],
			Model\Post::class => [ Model\Post::class, Factory\Post_Factory::class ],
			Model\Term::class => [ Model\Term::class, Factory\Term_Factory::class ],
			Model\User::class => [ Model\User::class, Factory\User_Factory::class ],
			Model\Site::class => [ Model\Site::class, Factory\Blog_Factory::class ],
			Model\Comment::class => [ Model\Comment::class, Factory\Comment_Factory::class ],
			Testable_Post::class => [ Testable_Post::class, Factory\Post_Factory::class ],
			Testable_Category::class => [ Testable_Category::class, Factory\Term_Factory::class ],
		];
	}

	public function test_throws_exception_on_unknown_class() {
		$class = new class {};

		$this->expectException( \InvalidArgumentException::class );

		Factory\Factory::default_factory_name( $class::class ); // @phpstan-ignore-line
	}

	public function test_create_multiple_fluently() {
		$post_ids = Testable_Post::factory()->count( 3 )->create()->all();

		$this->assertIsArray( $post_ids );
		$this->assertCount( 3, $post_ids );
		$this->assertContainsOnly( 'int', $post_ids );

		$posts = Testable_Post::factory()->count( 12 )->create_and_get()->all();

		$this->assertIsArray( $posts );
		$this->assertCount( 12, $posts );
		$this->assertContainsOnlyInstancesOf( Testable_Post::class, $posts );

		$this->assertInstanceOf(
			Testable_Post::class,
			Testable_Post::factory()->count( 1 )->create_and_get(),
		);
	}

	public function test_create_multiple_fluently_with_scopes() {
		$posts = Testable_Post_With_Factory::factory()
			->count( 3 )
			->custom_state()
			->as_models()
			->create_and_get()
			->all();

		$this->assertIsArray( $posts );
		$this->assertCount( 3, $posts );
		$this->assertEquals(
			'Title from the custom state',
			$posts[0]->title,
		);
	}

	public function test_create_custom_post_type_model() {
		register_post_type(
			'custom_post_type',
			[
				'public' => true,
			]
		);

		$post = Testable_Custom_Post_Type::factory()->create_and_get();

		$this->assertInstanceOf( Testable_Custom_Post_Type::class, $post );
		$this->assertEquals( 'custom_post_type', $post->post_type );
		$this->assertNotEmpty( $post->title );
		$this->assertNotEmpty( $post->content );
	}

	public function test_create_custom_taxonomy_model() {
		register_taxonomy(
			'custom_taxonomy',
			'post',
			[
				'public' => true,
			]
		);

		$post = Testable_Custom_Taxonomy::factory()->create_and_get();

		$this->assertInstanceOf( Testable_Custom_Taxonomy::class, $post );
		$this->assertEquals( 'custom_taxonomy', $post->taxonomy );
		$this->assertNotEmpty( $post->name );
	}
}

class Testable_Post extends Model\Post {
	public static $object_name = 'post';
}

/**
 * @method static Testable_Post_Factory factory()
 */
class Testable_Post_With_Factory extends Model\Post {
	public static $object_name = 'post';

	protected static function new_factory(): ?Factory\Factory {
		return app()->make( Testable_Post_Factory::class );
	}
}

class Testable_Category extends Model\Term {
	public static $object_name = 'category';
}

class Testable_Post_Factory extends Factory\Post_Factory {
	public function definition(): array {
		return [
			'title' => 'Title from the custom factory',
		];
	}

	public function custom_state()
	{
		return $this->state(
			fn ( array $attributes ) => [
				'title' => 'Title from the custom state',
			]
		);
	}
}

class Testable_Custom_Post_Type extends Model\Post {
	public static $object_name = 'custom_post_type';
}

class Testable_Custom_Taxonomy extends Model\Term {
	public static $object_name = 'custom_taxonomy';
}
