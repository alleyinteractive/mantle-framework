<?php
namespace Mantle\Tests\Database\Model\Relations\Belongs_To;

use Carbon\Carbon;
use InvalidArgumentException;
use Mantle\Testing\FrameworkTestCase;
use Mantle\Database\Model\Concerns\Has_Relationships as Relationships;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Relations\Belongs_To_Many;
use Mantle\Database\Model\Relations\Belongs_To;
use Mantle\Database\Model\Relations\Has_One;
use Mantle\Database\Model\Relations\Has_One_Or_Many;
use Mantle\Database\Model\Term;
use Mantle\Database\Model_Service_Provider;

class BelongsToOneOrManyTest extends FrameworkTestCase {
	protected function setUp(): void {
		parent::setUp();

		register_post_type( 'sponsor' );

		if ( ! taxonomy_exists( Has_One::RELATION_TAXONOMY ) ) {
			Model_Service_Provider::register_internal_taxonomy();
		}
	}

	protected function tearDown(): void {
		parent::tearDown();

		unregister_post_type( 'sponsor' );
	}

	public function test_post_to_post(): void {
		$post = Testable_Post::find_or_fail(
			static::factory()->post->create_and_get( [ 'post_date' => Carbon::now()->subWeek()->toDateTimeString() ] )
		);

		$sponsor = $post->sponsor()->save(
			Testable_Sponsor::find_or_fail(
				static::factory()->post->create_and_get(
					[
						'post_date' => Carbon::now()->subWeek()->toDateTimeString(),
						'post_type' => 'sponsor',
					]
				)
			)
		);

		$this->assertInstanceOf( Testable_Sponsor::class, $sponsor );
		$this->assertSame( $sponsor->id(), $post->sponsor->id() );
		$this->assertSame( (string) $sponsor->id(), get_post_meta( $post->id(), 'testable_sponsor_id', true ) );
	}

	public function test_post_to_post_using_terms(): void {
		$post = Testable_Post::find_or_fail(
			static::factory()->post->create_and_get( [ 'post_date' => Carbon::now()->subWeek()->toDateTimeString() ] )
		);

		$sponsor = $post->sponsor_with_terms()->save(
			Testable_Sponsor::find_or_fail(
				static::factory()->post->create_and_get(
					[
						'post_date' => Carbon::now()->subWeek()->toDateTimeString(),
						'post_type' => 'sponsor',
					]
				)
			)
		);

		$this->assertInstanceOf( Testable_Sponsor::class, $sponsor );
		$this->assertSame( $sponsor->id(), $post->sponsor_with_terms->id() );
		$this->assertNotEmpty( get_the_terms( $post->id(), Has_One_Or_Many::RELATION_TAXONOMY ) );
	}

	public function test_post_to_term(): void {
		$this->expectException( InvalidArgumentException::class );

		$post = Testable_Post::find_or_fail( static::factory()->post->create_and_get() );
		$tag = Testable_Tag::find_or_fail( static::factory()->tag->create_and_get() );

		$post->tags()->save( $tag );
	}

	public function test_term_to_term(): void {
		$tag     = Testable_Tag::find_or_fail( static::factory()->tag->create_and_get() );
		$related = $tag->related_tag()->save( new Testable_Tag( [ 'name' => 'Related' ] ) );

		static::factory()->tag->create_many( 10 );

		$this->assertNotNull( $tag->related_tag, 'Term should have related relationship.' );
		$this->assertSame( $related->get( 'id' ), $tag->related_tag->get( 'id' ) );
	}
}

/**
 * @property Testable_Sponsor $sponsor
 * @property Testable_Sponsor $sponsor_with_terms
 */
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
	 * @return Belongs_To<static, Testable_Sponsor>
	 */
	public function sponsor_with_terms(): Belongs_To {
		return $this->belongs_to( Testable_Sponsor::class )->uses_terms();
	}

	/**
	 * @return Belongs_To_Many<static, Testable_Tag>
	 */
	public function tags(): Belongs_To_Many {
		return $this->belongs_to_many( Testable_Tag::class );
	}
}

class Testable_Sponsor extends Post {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'sponsor';
}

/**
 * @property Testable_Tag|null $related_tag
 */
class Testable_Tag extends Term {
	/** @use Relationships<static> */
	use Relationships;

	public static $object_name = 'post_tag';

	/**
	 * @return Belongs_To<static, Testable_Tag>
	 */
	public function related_tag(): Belongs_To {
		return $this->belongs_to( Testable_Tag::class );
	}
}
