<?php
namespace Mantle\Tests\Concerns;

use Mantle\Console\Command;
use Mantle\Facade\Console;
use Mantle\Testing\Attributes\Environment;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group testing
 */
#[Group( 'testing' )]
class InteractsWithEnvironmentTest extends FrameworkTestCase {
	public function test_default_environment(): void {
		$this->assertEmpty( getenv( 'WP_ENVIRONMENT_TYPE' ) );
		$this->assertTrue( ! defined( 'WP_ENVIRONMENT_TYPE' ) );
		$this->assertSame( 'production', wp_get_environment_type() );
	}

	#[Environment( Environment::LOCAL )]
	public function test_set_environment(): void {
		$this->assertSame( 'local', wp_get_environment_type() );
	}

	public function test_manually_set_environment(): void {
		$this->assertTrue( ! defined( 'WP_ENVIRONMENT_TYPE' ) );
		$this->assertSame( 'production', wp_get_environment_type() );

		$this->set_environment_type( Environment::LOCAL );

		$this->assertSame( 'local', wp_get_environment_type() );
	}
}
