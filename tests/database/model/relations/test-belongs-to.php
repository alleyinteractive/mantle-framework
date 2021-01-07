<?php
namespace Mantle\Tests\Database\Model\Relations\Belongs_To;

use Carbon\Carbon;
use Mantle\Framework\Testing\Framework_Test_Case;
use Mantle\Framework\Database\Model\Concerns\Has_Relationships as Relationships;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Relations\Has_One_Or_Many;
use Mantle\Framework\Database\Model\Term;

class Test_Belongs_To extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		register_post_type( 'sponsor' );
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
		$post = Testable_Post::find(
			static::factory()->post->create( [ 'post_date' => Carbon::now()->subWeek()->toDateTimeString() ] )
		);


		$tag = Testable_Tag::find( static::factory()->tag->create() );

		static::factory()->post->create_many( 10 );
		static::factory()->tag->create_many( 10 );

		$post->tag()->save( $tag );

		$this->assertEquals( $tag->id, $post->tag->id );
	}

	public function test_post_to_terms() {
		$post = Testable_Post::find(
			static::factory()->post->create( [ 'post_date' => Carbon::now()->subWeek()->toDateTimeString() ] )
		);

		$tag = Testable_Tag::find( static::factory()->tag->create() );

		$post->tags()->save( $tag );

		$this->assertEquals( $tag->id, $post->tags[0]->id );
		$this->assertNotEmpty( get_the_tags( $post->id ) );
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

	public function tag() {
		return $this->belongs_to( Testable_Tag::class );
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

	public function posts() {
		return $this->belongs_to_many( Testable_Post::class );
	}
}
