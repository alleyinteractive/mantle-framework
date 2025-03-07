<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Concerns\With_Faker;
use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Attributes\Group;
use WP_Post;

/**
 * @group testing
 */
#[Group( 'testing' )]
class BlockAssertionsTest extends Framework_Test_Case {
	use With_Faker;

	protected WP_Post $post;

	public function setUp(): void {
		parent::setUp();

		$this->post = static::factory()->post->create_and_get( [
			'post_content' => $this->faker->blocks( [
				$this->faker->paragraph_block(),
				$this->faker->heading_block( 3 ),
				$this->faker->paragraph_block(),
				$this->faker->heading_block( 4 ),
				$this->faker->paragraph_block(),
				$this->faker->block(
					'vendor/example-name',
					'',
					[
						'moduleId' => 1234,
					]
					),
			] ),
		] );
	}

	public function test_string_has_content() {
		$this->assertStringHasBlock( $this->post->post_content, 'core/paragraph' );
		$this->assertStringHasBlock( $this->post->post_content, 'core/heading' );
		$this->assertStringHasBlock( $this->post->post_content, 'core/heading', [ 'level' => 3 ] );
		$this->assertStringHasBlock( $this->post->post_content, 'vendor/example-name', [ 'moduleId' => 1234 ] );
		$this->assertStringNotHasBlock( $this->post->post_content, 'core/heading', [ 'level' => 5 ] );
	}

	public function test_post_has_content() {
		$this->assertPostHasBlock( $this->post, 'core/paragraph' );
		$this->assertPostHasBlock( $this->post, 'core/heading' );
		$this->assertPostHasBlock( $this->post, 'core/heading', [ 'level' => 3 ] );
		$this->assertPostHasBlock( $this->post, 'vendor/example-name', [ 'moduleId' => 1234 ] );
		$this->assertPostNotHasBlock( $this->post, 'core/heading', [ 'level' => 5 ] );
	}
}
