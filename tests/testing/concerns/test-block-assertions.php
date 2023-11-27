<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Concerns\With_Faker;
use Mantle\Testing\Framework_Test_Case;
use WP_Post;

/**
 * @group testing
 */
class Test_Block_Assertions extends Framework_Test_Case {
	use With_Faker;

	protected WP_Post $post;

	public function setUp(): void {
		parent::setUp();

		$this->post = static::factory()->post->create_and_get( [
			'post_content' => $this->faker->blocks( [
				$this->faker->paragraph_block,
				$this->faker->heading_block( 3 ),
				$this->faker->paragraph_block,
				$this->faker->heading_block( 4 ),
				$this->faker->paragraph_block,
			] ),
		] );
	}

	public function test_string_has_content() {
		$this->assertStringHasBlock( $this->post->post_content, 'core/paragraph' );
		$this->assertStringHasBlock( $this->post->post_content, 'core/heading' );
		$this->assertStringHasBlock( $this->post->post_content, 'core/heading', [ 'level' => 3 ] );
		$this->assertStringNotHasBlock( $this->post->post_content, 'core/heading', [ 'level' => 5 ] );
	}

	public function test_post_has_content() {
		$this->assertPostHasBlock( $this->post, 'core/paragraph' );
		$this->assertPostHasBlock( $this->post, 'core/heading' );
		$this->assertPostHasBlock( $this->post, 'core/heading', [ 'level' => 3 ] );
		$this->assertPostNotHasBlock( $this->post, 'core/heading', [ 'level' => 5 ] );
	}
}
