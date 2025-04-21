<?php
namespace Mantle\Tests\View;

use Mantle\Facade\Blade;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group( 'views' )]
class BladeViewsTest extends FrameworkTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->app['view.loader']
			->clear_paths()
			->add_path( MANTLE_PHPUNIT_TEMPLATE_PATH . '/blade', 'blade' );
	}

	public function test_basic() {
		$contents = (string) view( '@blade/basic', [ 'name' => 'world' ] );
		$this->assertSame( 'Hello, world.', trim( $contents ) );
	}

	public function test_if_else() {
		$this->assertStringContainsString(
			'True!',
			(string) view( '@blade/if-else', [ 'should_if' => true ] ),
		);

		$this->assertStringContainsString(
			'False',
			(string) view( '@blade/if-else', [ 'should_if' => false ] ),
		);
	}

	public function test_include() {
		$this->assertStringContainsString(
			'child',
			(string) view( '@blade/parent' )
		);
	}

	public function test_compile_from_string(): void {
		$this->assertEquals(
			'Hello, <?php echo e($name); ?>.',
			Blade::compile_string( 'Hello, {{ $name }}.', [ 'name' => 'world' ] )
		);
	}

	public function test_render_string(): void {
		$this->assertEquals(
			'Hello, world.',
			Blade::render_string( 'Hello, {{ $name }}.', [ 'name' => 'world' ] )
		);
	}

	public function test_blade_directive(): void {
		Blade::directive( 'test', function ( $expression ) {
			return "<?php echo 'Test: $expression'; ?>";
		} );

		$this->assertEquals(
			'Test: test',
			Blade::render_string( '@test(test)' )
		);
	}
}
