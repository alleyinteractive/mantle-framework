<?php
namespace Mantle\Tests\Filesystem;

use InvalidArgumentException;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use Mantle\Framework\Application;
use Mantle\Filesystem\Filesystem_Manager;
use PHPUnit\Framework\TestCase;
use Mantle\Contracts\Filesystem\Filesystem as Filesystem_Contract;
use function Mantle\Support\Helpers\tap;

class Test_Filesystem_Manager extends TestCase {
	public function test_invalid_disk() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Disk [unsupported] does not have a configured driver.' );

		$filesystem = new Filesystem_Manager(
			tap(
				new Application(),
				function( Application $app ) {
					$app['config'] = [ 'filesystem' => [] ];
				}
			)
		);

		$filesystem->drive( 'unsupported' );
	}

	public function test_unknown_driver() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Disk [valid-disk-unknown-driver] uses a driver [unknown] that is not supported.' );

		$filesystem = new Filesystem_Manager(
			tap(
				new Application(),
				function( Application $app ) {
					$app['config'] = [
						'filesystem.disks.valid-disk-unknown-driver' => [
							'driver' => 'unknown',
						],
					];
				}
			)
		);

		$filesystem->drive( 'valid-disk-unknown-driver' );
	}

	public function test_custom_driver() {
		$_SERVER['__custom_driver_called'] = 0;

		$filesystem = new Filesystem_Manager(
			tap(
				new Application(),
				function( Application $app ) {
					$app['config'] = [
						'filesystem.disks.custom-driver' => [
							'driver' => 'custom-driver',
						],
					];
				}
			)
		);

		$filesystem->extend(
			'custom-driver',
			function () {
				$_SERVER['__custom_driver_called']++;
				return new NullAdapter();
			}
		);

		$this->assertInstanceOf( Filesystem_Contract::class, $filesystem->drive( 'custom-driver' ) );

		// Invoke the disk again and see if the variable is incremented.
		$drive = $filesystem->drive( 'custom-driver' );

		$this->assertEquals( 1, $_SERVER['__custom_driver_called'], 'Disk should be reused.' );
		$this->assertFalse( $drive->exists( '/path' ) );
	}
}
