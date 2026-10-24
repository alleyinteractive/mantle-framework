<?php
namespace Mantle\Tests\Blocks;

use Mantle\Blocks\Block;
use Mantle\Testing\FrameworkTestCase;

class BlockTest extends FrameworkTestCase {
	public function test_editor_assets_register_with_dependencies(): void {
		$block = new class() extends Block {
			protected string $name = 'example-block';

			protected array $editor_script_dependencies = [ 'wp-blocks' ];
		};

		$this->assertNotEmpty( invade( $block )->get_editor_script_version() );

		invade( $block )->register_editor_assets();
	}
}
