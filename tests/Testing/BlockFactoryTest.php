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

	public function test_it_can_generate_a_heading(): void {
		$this->assertStringStartsWith(
			'<!-- wp:heading {"level":2} -->',
			block_factory()->heading(),
		);

		$this->assertStringStartsWith(
			'<!-- wp:heading {"level":3} -->',
			block_factory()->heading( 'Heading Here', 3 ),
		);

		$this->assertEquals(
			"<!-- wp:heading {\"level\":2} -->
<h2>Heading Here</h2>
<!-- /wp:heading -->",
			block_factory()->heading( 'Heading Here', 2 ),
		);

		$this->assertEquals(
			"<!-- wp:heading {\"level\":2} -->\n<h2>Heading Here</h2>\n<!-- /wp:heading -->",
			block_factory()->heading(
				text: 'Heading Here',
				level: 2,
			),
		);
	}

	public function test_it_can_generate_a_paragraph(): void {
		$this->assertStringStartsWith(
			'<!-- wp:paragraph -->',
			block_factory()->paragraph(),
		);

		$this->assertEquals(
			"<!-- wp:paragraph -->\n<p>Paragraph here.</p>\n<!-- /wp:paragraph -->",
			block_factory()->paragraph( 'Paragraph here.' ),
		);

		$this->assertEquals(
			"<!-- wp:paragraph -->\n<p>Paragraph here.</p>\n<!-- /wp:paragraph -->",
			block_factory()->paragraph(
				text: 'Paragraph here.',
			),
		);
	}

	public function test_it_can_generate_paragraphs(): void {
		$this->assertEquals( 3, substr_count( block_factory()->paragraphs(), '<!-- wp:paragraph -->' ) );
		$this->assertEquals( 5, substr_count( block_factory()->paragraphs( 5 ), '<!-- wp:paragraph -->' ) );
		$this->assertEquals( 6, substr_count( block_factory()->paragraphs( count: 6 ), '<!-- wp:paragraph -->' ) );
	}

	public function test_it_can_generate_an_image(): void {
		$this->assertStringStartsWith(
			'<!-- wp:image -->',
			block_factory()->image(),
		);

		$this->assertEquals(
			"<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"https://picsum.photos/353/580\"/></figure>\n<!-- /wp:image -->",
			block_factory()->image( 'https://picsum.photos/353/580' ),
		);

		$this->assertEquals(
			"<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"https://picsum.photos/353/580\" alt=\"Image alt text\"/></figure>\n<!-- /wp:image -->",
			block_factory()->image( 'https://picsum.photos/353/580', 'Image alt text' ),
		);

		$this->assertEquals(
			"<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"https://picsum.photos/353/580\" alt=\"Image alt text\"/></figure>\n<!-- /wp:image -->",
			block_factory()->image( url: 'https://picsum.photos/353/580', alt: 'Image alt text' ),
		);
	}

	public function test_it_can_generate_blocks_by_name(): void {
		$this->assertEquals(
			'<!-- wp:namespace/blockname /-->',
			block_factory()->block( 'namespace/blockname' ),
		);

		$this->assertEquals(
			'<!-- wp:namespace/blockname /-->',
			block_factory()->block( name: 'namespace/blockname' ),
		);

		$this->assertEquals(
			'<!-- wp:namespace/blockname {"id":"block-id"} /-->',
			block_factory()->block( 'namespace/blockname', null, [
				'id' => 'block-id',
			] ),
		);

		$this->assertEquals(
			'<!-- wp:namespace/blockname {"id":"block-id"} /-->',
			block_factory()->block(
				name: 'namespace/blockname',
				attributes: [
					'id' => 'block-id',
				],
			),
		);

		$this->assertEquals(
			"<!-- wp:namespace/blockname -->\nExample content\n<!-- /wp:namespace/blockname -->",
			block_factory()->block( 'namespace/blockname', 'Example content' ),
		);

		$this->assertEquals(
			"<!-- wp:namespace/blockname -->\nExample content\n<!-- /wp:namespace/blockname -->",
			block_factory()->block( name: 'namespace/blockname', content: 'Example content' ),
		);

		$this->assertEquals(
			"<!-- wp:namespace/blockname {\"id\":\"block-id\"} -->\nExample content\n<!-- /wp:namespace/blockname -->",
			block_factory()->block( 'namespace/blockname', 'Example content', [
				'id' => 'block-id',
			] ),
		);

		$this->assertEquals(
			"<!-- wp:namespace/blockname {\"id\":\"block-id\"} -->\nExample content\n<!-- /wp:namespace/blockname -->",
			block_factory()->block(
				name: 'namespace/blockname',
				content: 'Example content',
				attributes: [
					'id' => 'block-id',
				],
			),
		);
	}

	public function test_it_can_create_multiple_blocks(): void {
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
				null,
				[
					'seo' => 'Attribute on the block',
				]
			),
		);

		Block_Factory::register_preset(
			'title_with_arguments',
			fn ( Block_Factory $factory, string $title ) => $factory->block(
				'namespace/multititle',
				null,
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
