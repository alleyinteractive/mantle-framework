<?php
namespace Mantle\Tests\Database\Model\Relations\Belongs_To;

use Carbon\Carbon;
use InvalidArgumentException;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Framework\Database\Model\Concerns\Has_Relationships as Relationships;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Relations\Has_One;
use Mantle\Framework\Database\Model\Relations\Has_One_Or_Many;
use Mantle\Framework\Database\Model\Term;
use Mantle\Framework\Providers\Model_Service_Provider;

class Test_Belongs_To_One_Or_Many extends Framework_Test_Case {
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

	public function test_post_to_post() {
		$post = Testable_Post::find(
			static::factory()->post->create( [ 'post_date' => Carbon::now()->subWeek()->toDateTimeString() ] )
		);

		$sponsor = $post->sponsor()->save(
			Testable_Sponsor::find(
				static::factory()->post->create(
					[
						'post_date' => Carbon::now()->subWeek()->toDateTimeString(),
						'post_type' => 'sponsor',
					]
				)
			)
		);

		$this->assertInstanceOf( Testable_Sponsor::class, $sponsor );
		$this->assertEquals( $sponsor->id, $post->sponsor->id );
		$this->assertEquals( $sponsor->id, get_post_meta( $post->id, 'testable_sponsor_id', true ) );
	}

	public function test_post_to_post_using_terms() {
		$post = Testable_Post::find(
			static::factory()->post->create( [ 'post_date' => Carbon::now()->subWeek()->toDateTimeString() ] )
		);

		$sponsor = $post->sponsor_with_terms()->save(
			Testable_Sponsor::find(
				static::factory()->post->create(
					[
						'post_date' => Carbon::now()->subWeek()->toDateTimeString(),
						'post_type' => 'sponsor',
					]
				)
			)
		);

		$this->assertInstanceOf( Testable_Sponsor::class, $sponsor );
		$this->assertEquals( $sponsor->id, $post->sponsor_with_terms->id );
		$this->assertNotEmpty( get_the_terms( $post->id, Has_One_Or_Many::RELATION_TAXONOMY ) );
	}

	public function test_post_to_term() {
		$this->expectException( InvalidArgumentException::class );

		$post = Testable_Post::find( static::factory()->post->create() );
		$tag = Testable_Tag::find( static::factory()->tag->create() );

		$post->tags()->save( $tag );
	}

	public function test_term_to_term() {
		$tag     = Testable_Tag::find( static::factory()->tag->create() );
		$related = $tag->related_tag()->save( new Testable_Tag( [ 'name' => 'Related' ] ) );

		static::factory()->tag->create_many( 10 );

		$this->assertNotNull( $tag->related_tag, 'Term should have related relationship.' );
		$this->assertEquals( $related->id, $tag->related_tag->id );
	}
}

class Testable_Post extends Post {
	use Relationships;

	public static $object_name = 'post';

	public function sponsor() {
		return $this->belongs_to( Testable_Sponsor::class );
	}

	public function sponsor_with_terms() {
		return $this->belongs_to( Testable_Sponsor::class )->uses_terms();
	}

	public function tags() {
		return $this->belongs_to_many( Testable_Tag::class );
	}
}


class Testable_Sponsor extends Post {
	use Relationships;

	public static $object_name = 'sponsor';
}

class Testable_Tag extends Term {
	use Relationships;

	public static $object_name = 'post_tag';

	public function related_tag() {
		return $this->belongs_to( Testable_Tag::class );
	}
}
