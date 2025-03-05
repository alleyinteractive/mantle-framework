<?php
namespace Mantle\Tests\Testing;

use Mantle\Testing\Block_Factory;
use PHPUnit\Framework\TestCase;

use function Mantle\Testing\block_factory;

class BlockFactoryTest extends TestCase {
	public static function tearDownAfterClass(): void {
		Block_Factory::clear_presets();

		parent::tearDownAfterClass();
	}

	public function test_it_can_generate_blocks() {
		$this->assertStringStartsWith(
			'<!-- wp:paragraph -->',
			block_factory()->paragraph(),
		);

		$this->assertEquals(
			"<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"https://picsum.photos/353/580\"/></figure>\n<!-- /wp:image -->",
			block_factory()->image( 'https://picsum.photos/353/580' ),
		);

		$this->assertEquals(
			'<!-- wp:heading {"level":2} -->
<h2>Heading Here</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Paragraph here.</p>
<!-- /wp:paragraph -->',
			block_factory()->blocks( [
				block_factory()->heading( 'Heading Here' ),
				block_factory()->paragraph( 'Paragraph here.' ),
			] ),
		);

		$this->assertEquals(
			'<!-- wp:heading {"level":2} -->
<h2>Heading Here</h2>
<!-- /wp:heading -->',
			block_factory()->blocks(
				block_factory()->heading( 'Heading Here' ),
			),
		);

		$this->assertEquals(
			'<!-- wp:heading {"level":2} -->
<h2>Heading Here</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Paragraph here.</p>
<!-- /wp:paragraph -->',
			block_factory()->blocks(
				block_factory()->heading( 'Heading Here' ),
				block_factory()->paragraph( 'Paragraph here.' ),
			),
		);
	}

	public function test_it_throws_an_exception_for_invalid_blocks() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unknown block factory method: unknown_method' );

		block_factory()->unknown_method();
	}

	public function test_it_can_generate_a_preset() {
		Block_Factory::register_preset(
			'test',
			block_factory()->blocks( [
				block_factory()->heading(),
				block_factory()->paragraph(),
			] ),
		);

		Block_Factory::register_preset(
			'test2',
			fn ( Block_Factory $factory ) => $factory->blocks( [
				$factory->heading(),
				$factory->paragraphs( 5 ),
			] ),
		);

		Block_Factory::register_preset(
			'title_block',
			fn ( Block_Factory $factory ) => $factory->block(
				'namespace/multititle',
				'',
				[
					'seo' => 'Attribute on the block',
				]
			),
		);

		Block_Factory::register_preset(
			'title_with_arguments',
			fn ( Block_Factory $factory, string $title ) => $factory->block(
				'namespace/multititle',
				'',
				[
					'seo' => $title,
				]
			),
		);

		$this->assertStringStartsWith(
			'<!-- wp:heading {"level":2} -->',
			block_factory()->test(),
		);

		$preset_test2 = block_factory()->test2();

		$this->assertStringStartsWith(
			'<!-- wp:heading {"level":2} -->',
			$preset_test2,
		);

		$this->assertEquals( 5, substr_count( $preset_test2, '<!-- wp:paragraph -->' ) );

		$this->assertEquals(
			'<!-- wp:namespace/multititle {"seo":"Attribute on the block"} /-->',
			block_factory()->title_block(),
		);

		$this->assertEquals(
			'<!-- wp:namespace/multititle {"seo":"Attribute on the block"} /-->',
			block_factory()->preset( 'title_block' ),
		);

		$this->assertEquals(
			'<!-- wp:namespace/multititle {"seo":"Title Here"} /-->',
			block_factory()->title_with_arguments( 'Title Here' ),
		);
	}
}
