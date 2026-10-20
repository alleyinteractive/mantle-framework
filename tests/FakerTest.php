<?php
namespace Mantle\Tests;

use Faker\Factory;
use Mantle\Faker\Faker_Provider;

class FakerTest extends \PHPUnit\Framework\TestCase {
	protected \Faker\Generator $faker;

	protected function setUp(): void {
		parent::setUp();

		$this->faker = Factory::create();
		$this->faker->addProvider( new Faker_Provider( $this->faker ) );
	}

	public function test_block() {
		$this->assertSame(
			'<!-- wp:namespace/block /-->',
			$this->faker->block( 'namespace/block' )
		);

		$this->assertSame(
			'<!-- wp:namespace/block {"exampleAttr":true,"another":false} /-->',
			$this->faker->block( 'namespace/block', null, [ 'exampleAttr' => true, 'another' => false ] )
		);

		$this->assertSame(
			sprintf( '<!-- wp:namespace/block {"exampleAttr":true,"another":false} -->%1$sThe Content%1$s<!-- /wp:namespace/block -->', PHP_EOL ),
			$this->faker->block( 'namespace/block', 'The Content', [ 'exampleAttr' => true, 'another' => false ] )
		);

		$this->assertSame(
			sprintf( '<!-- wp:namespace/block -->%1$sThe Content%1$s<!-- /wp:namespace/block -->', PHP_EOL ),
			$this->faker->block( 'namespace/block', 'The Content', [] )
		);
	}

	public function test_paragraph() {
		$block = $this->faker->paragraph_block();

		$this->assertStringContainsString( '<!-- wp:paragraph -->' . PHP_EOL . '<p>', $block );
		$this->assertStringContainsString( '</p>' . PHP_EOL . '<!-- /wp:paragraph -->', $block );
	}
}
