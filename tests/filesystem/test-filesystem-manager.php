<?php
namespace Mantle\Tests\Filesystem;

use InvalidArgumentException;
use Mantle\Application\Application;
use Mantle\Filesystem\Filesystem;
use Mantle\Filesystem\Filesystem_Manager;
use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\TestCase;
use Mockery as m;

use function Mantle\Support\Helpers\tap;

class Test_Filesystem_Manager extends Framework_Test_Case {
	protected function tearDown(): void {
		parent::tearDown();

		m::close();

		( new Filesystem() )->delete( wp_upload_dir()['basedir'] . '/file.txt' );
	}

	public function test_default_disk() {
		$this->expectApplied( 'mantle_filesystem_local_config' )->once()->andReturnArray();

		$filesystem = $this->app->make( Filesystem_Manager::class );

		$drive = $filesystem->drive();

		$drive->put( 'file.txt', 'contents' );

		$this->assertTrue( $drive->exists( 'file.txt' ) );
		$this->assertEquals( 'contents', $drive->read( 'file.txt' ) );

		// Attempt to read the URL/path for the file.
		$this->assertEquals( home_url( '/wp-content/uploads/file.txt' ), $drive->url( 'file.txt' ) );
		$this->assertEquals( wp_upload_dir()['basedir'] . '/file.txt', $drive->path( 'file.txt' ) );
		$this->assertTrue( file_exists( $drive->path( 'file.txt' ) ) );

		$drive->delete( 'file.txt' );

		$this->assertFalse( $drive->exists( 'file.txt' ) );
	}

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
							'extra_config' => 'value',
							'root' => '/path',
						],
					];
				}
			)
		);

		$adapter = m::mock( \Mantle\Contracts\Filesystem\Filesystem::class );

		$adapter->shouldReceive( 'exists' )
			->once()
			->with( '/path' )
			->andReturn( true );

		$_SERVER['__custom_driver_called'] = 0;

		$filesystem->extend(
			'custom-driver',
			function ( $app, array $config ) use ( $adapter ) {
				$this->assertInstanceof( \Mantle\Contracts\Application::class, $app );
				$this->assertEquals( [ 'driver' => 'custom-driver', 'extra_config' => 'value', 'root' => '/path' ], $config );

				$_SERVER['__custom_driver_called']++;

				return $adapter;
			},
		);

		$this->assertInstanceOf( $adapter::class, $filesystem->drive( 'custom-driver' ) );
		$this->assertTrue( $filesystem->drive( 'custom-driver' )->exists( '/path' ) );

		// Ensure that the custom driver was instantiated once.
		$this->assertEquals( 1, $_SERVER['__custom_driver_called'] );
	}
}
