<?php
namespace Mantle\Tests\Database\Builder\Post_Query_Relationships;

use Mantle\Database\Model\Post;
use Mantle\Database\Model\Relations\Has_One;
use Mantle\Database\Model\Relations\Has_Many;
use Mantle\Database\Model\Relations\Belongs_To;
use Mantle\Database\Model\Concerns\Has_Relationships as Relationships;
use Mantle\Database\Model\Relations\Relation;
use Mantle\Database\Model\Term;
use Mantle\Database\Model_Service_Provider;
use Mantle\Testing\FrameworkTestCase;
use Mantle\Testing\Utils;

class PostQueryRelationshipsTest extends FrameworkTestCase {
	protected function setUp(): void {
		Utils::delete_all_posts();
		parent::setUp();
		$name = Testable_Sponsor::get_object_name();

		$this->assertNotEmpty( $name );

		register_post_type( strtolower( $name ) );

		if ( ! taxonomy_exists( Relation::RELATION_TAXONOMY ) ) {
			Model_Service_Provider::register_internal_taxonomy();
		}
	}

	protected function tearDown(): void {
		parent::tearDown();
		$name = Testable_Sponsor::get_object_name();

		$this->assertNotNull( $name );

		unregister_post_type( $name );
	}

	public function test_relationship_has(): void {
		$post_a     = $this->get_random_post_id();
		$sponsor_id = $this->get_random_post_id( [ 'post_type' => 'sponsor' ] );

		// Associate the post with the sponsor.
		update_post_meta( $post_a, 'testable_sponsor_id', $sponsor_id );

		$first = Testable_Post::query()->has( 'sponsor' )->first();

		$this->assertInstanceOf( Testable_Post::class, $first );
		$this->assertSame( $post_a, $first->id() );
	}

	public function test_relationship_has_compare(): void {
		$post_a     = $this->get_random_post_id();
		$sponsor_id = $this->get_random_post_id( [ 'post_type' => 'sponsor' ] );

		// Associate the post with the sponsor.
		update_post_meta( $post_a, 'testable_sponsor_id', $sponsor_id );

		$missing = Testable_Post::query()->has( 'sponsor', 'non-exist' )->first();
		$this->assertEmpty( $missing );

		$expected = Testable_Post::query()->has( 'sponsor', $sponsor_id )->first(); // @phpstan-ignore argument.type (int passed on purpose; has() $compare parameter is ?string)

		$this->assertInstanceOf( Testable_Post::class, $expected );
		$this->assertSame( $post_a, $expected->id() );
	}

	public function test_relationship_doesnt_have(): void {
		$post_a     = $this->get_random_post_id();
		$sponsor_id = $this->get_random_post_id( [ 'post_type' => 'sponsor' ] );

		// Associate the post with the sponsor.
		update_post_meta( $post_a, 'testable_sponsor_id', $sponsor_id );

		$first = Testable_Post::query()->doesnt_have( 'sponsor' )->first();

		$this->assertInstanceOf( Testable_Post::class, $first );
		$this->assertNotSame( $post_a, $first->id() );
	}

	/**
	 * Get a random post ID, ensures the post ID is not the last in the set.
	 *
	 * @param array<string, mixed> $args Arguments for the posts.
	 * @return integer
	 */
	protected function get_random_post_id( $args = [] ): int {
		$post_ids = static::factory()->post->create_many( 11, $args );
		array_pop( $post_ids );
		return $post_ids[ array_rand( $post_ids ) ];
	}
}

class Testable_Post extends Post {
	/** @use Relationships<static> */
	use Relationships;
	public static $object_name = 'post';

	/**
	 * @return Belongs_To<static, Testable_Sponsor>
	 */
	public function sponsor(): Belongs_To {
		return $this->belongs_to( Testable_Sponsor::class );
	}

	/**
	 * @return Has_Many<static, Testable_Tag_Relationships>
	 */
	public function tags(): Has_Many {
		return $this->has_many( Testable_Tag_Relationships::class );
	}
}

class Testable_Sponsor extends Post {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'sponsor';

	/**
	 * @return Has_One<static, Testable_Post>
	 */
	public function post(): Has_One {
		return $this->has_one( Testable_Post::class );
	}

	/**
	 * @return Has_Many<static, Testable_Post>
	 */
	public function posts(): Has_Many {
		return $this->has_many( Testable_Post::class );
	}
}

class Testable_Tag_Relationships extends Term {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'post_tag';

	/**
	 * @return Has_Many<static, Testable_Post>
	 */
	public function posts(): Has_Many {
		return $this->has_many( Testable_Post::class );
	}
}
