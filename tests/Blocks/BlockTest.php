<?php
namespace Mantle\Tests\Blocks;

use Mantle\Blocks\Block;
use Mantle\Testing\FrameworkTestCase;
use ReflectionMethod;

class BlockTest extends FrameworkTestCase {
	public function test_editor_assets_register_with_dependencies(): void {
		$block = new class() extends Block {
			protected string $name = 'example-block';

			protected array $editor_script_dependencies = [ 'wp-blocks' ];
		};

		$version = new ReflectionMethod( $block, 'get_editor_script_version' );
		$this->assertIsString( $version->invoke( $block ) );

		$register = new ReflectionMethod( $block, 'register_editor_assets' );
		$register->invoke( $block );

		$this->assertTrue( true );
	}
}
