<?php

namespace Mantle\Tests\Filesystem;

use Mantle\Filesystem\Filesystem;
use Mantle\Testing\Assert;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use League\Flysystem\FileNotFoundException;

class Test_Filesystem extends TestCase {

	private static $temp_dir;

	/**
	 * @beforeClass
	 */
	public static function setUptemp_dir() {
		static::$temp_dir = get_temp_dir() . '/mantle-fs';

		$files = new Filesystem();
		$files->ensure_directory_exists( static::$temp_dir );
		$files->clean_directory( static::$temp_dir );
	}

	/**
	 * @afterClass
	 */
	public static function tearDowntemp_dir() {
		$files = new Filesystem();
		$files->delete_directory( static::$temp_dir );
		static::$temp_dir = null;
	}

	protected function tearDown(): void {
		m::close();

		$files = new Filesystem();
		$files->clean_directory( static::$temp_dir );
	}

	public function testGetRetrievesFiles() {
		file_put_contents( static::$temp_dir . '/file.txt', 'Hello World' );
		$files = new Filesystem();
		Assert::assertSame( 'Hello World', $files->get( static::$temp_dir . '/file.txt' ) );
	}

	public function testPutStoresFiles() {
		$files = new Filesystem();
		$files->put( static::$temp_dir . '/file.txt', 'Hello World' );
		Assert::assertStringEqualsFile( static::$temp_dir . '/file.txt', 'Hello World' );
	}

	public function testReplaceCreatesFile() {
		$tempFile = static::$temp_dir . '/file.txt';

		$filesystem = new Filesystem();

		$filesystem->replace( $tempFile, 'Hello World' );
		Assert::assertStringEqualsFile( $tempFile, 'Hello World' );
	}

	public function testReplaceWhenUnixSymlinkExists() {
		$tempFile   = static::$temp_dir . '/file.txt';
		$symlinkDir = static::$temp_dir . '/symlink_dir';
		$symlink    = "{$symlinkDir}/symlink.txt";

		mkdir( $symlinkDir );
		symlink( $tempFile, $symlink );

		// Prevent changes to symlink_dir
		chmod( $symlinkDir, 0555 );

		// Test with a weird non-standard umask.
		$umask         = 0131;
		$originalUmask = umask( $umask );

		$filesystem = new Filesystem();

		// Test replacing non-existent file.
		$filesystem->replace( $tempFile, 'Hello World' );
		Assert::assertStringEqualsFile( $tempFile, 'Hello World' );
		Assert::assertEquals( $umask, 0777 - $this->get_file_permissions( $tempFile ) );

		// Test replacing existing file.
		$filesystem->replace( $tempFile, 'Something Else' );
		Assert::assertStringEqualsFile( $tempFile, 'Something Else' );
		Assert::assertEquals( $umask, 0777 - $this->get_file_permissions( $tempFile ) );

		// Test replacing symlinked file.
		$filesystem->replace( $symlink, 'Yet Something Else Again' );
		Assert::assertStringEqualsFile( $tempFile, 'Yet Something Else Again' );
		Assert::assertEquals( $umask, 0777 - $this->get_file_permissions( $tempFile ) );

		umask( $originalUmask );

		// Reset changes to symlink_dir
		chmod( $symlinkDir, 0777 - $originalUmask );
	}

	public function testSetChmod() {
		file_put_contents( static::$temp_dir . '/file.txt', 'Hello World' );
		$files = new Filesystem();
		$files->chmod( static::$temp_dir . '/file.txt', 0755 );
		$filePermission      = substr( sprintf( '%o', fileperms( static::$temp_dir . '/file.txt' ) ), -4 );
		$expectedPermissions = DIRECTORY_SEPARATOR == '\\' ? '0666' : '0755';
		Assert::assertEquals( $expectedPermissions, $filePermission );
	}

	public function testGetChmod() {
		file_put_contents( static::$temp_dir . '/file.txt', 'Hello World' );
		chmod( static::$temp_dir . '/file.txt', 0755 );

		$files               = new Filesystem();
		$filePermission      = $files->chmod( static::$temp_dir . '/file.txt' );
		$expectedPermissions = DIRECTORY_SEPARATOR == '\\' ? '0666' : '0755';
		Assert::assertEquals( $expectedPermissions, $filePermission );
	}

	public function testDeleteRemovesFiles() {
		file_put_contents( static::$temp_dir . '/file1.txt', 'Hello World' );
		file_put_contents( static::$temp_dir . '/file2.txt', 'Hello World' );
		file_put_contents( static::$temp_dir . '/file3.txt', 'Hello World' );

		$files = new Filesystem();
		$files->delete( static::$temp_dir . '/file1.txt' );
		Assert::assertFileDoesNotExist( static::$temp_dir . '/file1.txt' );

		$files->delete( [ static::$temp_dir . '/file2.txt', static::$temp_dir . '/file3.txt' ] );
		Assert::assertFileDoesNotExist( static::$temp_dir . '/file2.txt' );
		Assert::assertFileDoesNotExist( static::$temp_dir . '/file3.txt' );
	}

	public function testPrependExistingFiles() {
		$files = new Filesystem();
		$files->put( static::$temp_dir . '/file.txt', 'World' );
		$files->prepend( static::$temp_dir . '/file.txt', 'Hello ' );
		Assert::assertStringEqualsFile( static::$temp_dir . '/file.txt', 'Hello World' );
	}

	public function testPrependNewFiles() {
		$files = new Filesystem();
		$files->prepend( static::$temp_dir . '/file.txt', 'Hello World' );
		Assert::assertStringEqualsFile( static::$temp_dir . '/file.txt', 'Hello World' );
	}

	public function testMissingFile() {
		$files = new Filesystem();
		Assert::assertTrue( $files->missing( static::$temp_dir . '/file.txt' ) );
	}

	public function testDeleteDirectory() {
		mkdir( static::$temp_dir . '/foo' );
		file_put_contents( static::$temp_dir . '/foo/file.txt', 'Hello World' );
		$files = new Filesystem();
		$files->delete_directory( static::$temp_dir . '/foo' );
		Assert::assertDirectoryDoesNotExist( static::$temp_dir . '/foo' );
		Assert::assertFileDoesNotExist( static::$temp_dir . '/foo/file.txt' );
	}

	public function testDeleteDirectoryReturnFalseWhenNotADirectory() {
		mkdir( static::$temp_dir . '/bar' );
		file_put_contents( static::$temp_dir . '/bar/file.txt', 'Hello World' );
		$files = new Filesystem();
		Assert::assertFalse( $files->delete_directory( static::$temp_dir . '/bar/file.txt' ) );
	}

	public function testCleanDirectory() {
		mkdir( static::$temp_dir . '/baz' );
		file_put_contents( static::$temp_dir . '/baz/file.txt', 'Hello World' );
		$files = new Filesystem();
		$files->clean_directory( static::$temp_dir . '/baz' );
		Assert::assertDirectoryExists( static::$temp_dir . '/baz' );
		Assert::assertFileDoesNotExist( static::$temp_dir . '/baz/file.txt' );
	}

	public function testMacro() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'Hello World' );
		$files   = new Filesystem();
		$temp_dir = static::$temp_dir;
		$files->macro(
			'getFoo',
			function () use ( $files, $temp_dir ) {
				return $files->get( $temp_dir . '/foo.txt' );
			}
		);
		Assert::assertSame( 'Hello World', $files->getFoo() );
	}

	public function testFilesMethod() {
		mkdir( static::$temp_dir . '/views' );
		file_put_contents( static::$temp_dir . '/views/1.txt', '1' );
		file_put_contents( static::$temp_dir . '/views/2.txt', '2' );
		mkdir( static::$temp_dir . '/views/_layouts' );
		$files   = new Filesystem();
		$results = $files->files( static::$temp_dir . '/views' );
		Assert::assertInstanceOf( SplFileInfo::class, $results[0] );
		Assert::assertInstanceOf( SplFileInfo::class, $results[1] );
		unset( $files );
	}

	public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory() {
		$files = new Filesystem();
		Assert::assertFalse( $files->copy_directory( static::$temp_dir . '/breeze/boom/foo/bar/baz', static::$temp_dir ) );
	}

	public function testCopyDirectoryMovesEntireDirectory() {
		mkdir( static::$temp_dir . '/tmp', 0777, true );
		file_put_contents( static::$temp_dir . '/tmp/foo.txt', '' );
		file_put_contents( static::$temp_dir . '/tmp/bar.txt', '' );
		mkdir( static::$temp_dir . '/tmp/nested', 0777, true );
		file_put_contents( static::$temp_dir . '/tmp/nested/baz.txt', '' );

		$files = new Filesystem();
		$files->copy_directory( static::$temp_dir . '/tmp', static::$temp_dir . '/tmp2' );
		Assert::assertDirectoryExists( static::$temp_dir . '/tmp2' );
		Assert::assertFileExists( static::$temp_dir . '/tmp2/foo.txt' );
		Assert::assertFileExists( static::$temp_dir . '/tmp2/bar.txt' );
		Assert::assertDirectoryExists( static::$temp_dir . '/tmp2/nested' );
		Assert::assertFileExists( static::$temp_dir . '/tmp2/nested/baz.txt' );
	}

	public function testMoveDirectoryMovesEntireDirectory() {
		mkdir( static::$temp_dir . '/tmp2', 0777, true );
		file_put_contents( static::$temp_dir . '/tmp2/foo.txt', '' );
		file_put_contents( static::$temp_dir . '/tmp2/bar.txt', '' );
		mkdir( static::$temp_dir . '/tmp2/nested', 0777, true );
		file_put_contents( static::$temp_dir . '/tmp2/nested/baz.txt', '' );

		$files = new Filesystem();
		$files->move_directory( static::$temp_dir . '/tmp2', static::$temp_dir . '/tmp3' );
		Assert::assertDirectoryExists( static::$temp_dir . '/tmp3' );
		Assert::assertFileExists( static::$temp_dir . '/tmp3/foo.txt' );
		Assert::assertFileExists( static::$temp_dir . '/tmp3/bar.txt' );
		Assert::assertDirectoryExists( static::$temp_dir . '/tmp3/nested' );
		Assert::assertFileExists( static::$temp_dir . '/tmp3/nested/baz.txt' );
		Assert::assertDirectoryDoesNotExist( static::$temp_dir . '/tmp2' );
	}

	public function testMoveDirectoryMovesEntireDirectoryAndOverwrites() {
		mkdir( static::$temp_dir . '/tmp4', 0777, true );
		file_put_contents( static::$temp_dir . '/tmp4/foo.txt', '' );
		file_put_contents( static::$temp_dir . '/tmp4/bar.txt', '' );
		mkdir( static::$temp_dir . '/tmp4/nested', 0777, true );
		file_put_contents( static::$temp_dir . '/tmp4/nested/baz.txt', '' );
		mkdir( static::$temp_dir . '/tmp5', 0777, true );
		file_put_contents( static::$temp_dir . '/tmp5/foo2.txt', '' );
		file_put_contents( static::$temp_dir . '/tmp5/bar2.txt', '' );

		$files = new Filesystem();
		$files->move_directory( static::$temp_dir . '/tmp4', static::$temp_dir . '/tmp5', true );
		Assert::assertDirectoryExists( static::$temp_dir . '/tmp5' );
		Assert::assertFileExists( static::$temp_dir . '/tmp5/foo.txt' );
		Assert::assertFileExists( static::$temp_dir . '/tmp5/bar.txt' );
		Assert::assertDirectoryExists( static::$temp_dir . '/tmp5/nested' );
		Assert::assertFileExists( static::$temp_dir . '/tmp5/nested/baz.txt' );
		Assert::assertFileDoesNotExist( static::$temp_dir . '/tmp5/foo2.txt' );
		Assert::assertFileDoesNotExist( static::$temp_dir . '/tmp5/bar2.txt' );
		Assert::assertDirectoryDoesNotExist( static::$temp_dir . '/tmp4' );
	}

	public function testMoveDirectoryReturnsFalseWhileOverwritingAndUnableToDeleteDestinationDirectory() {
		mkdir( static::$temp_dir . '/tmp6', 0777, true );
		file_put_contents( static::$temp_dir . '/tmp6/foo.txt', '' );
		mkdir( static::$temp_dir . '/tmp7', 0777, true );

		$files = m::mock( Filesystem::class )->makePartial();
		$files->shouldReceive( 'delete_directory' )->once()->andReturn( false );
		Assert::assertFalse( $files->move_directory( static::$temp_dir . '/tmp6', static::$temp_dir . '/tmp7', true ) );
	}

	public function testGetThrowsExceptionNonexisitingFile() {
		$this->expectException( FileNotFoundException::class );

		$files = new Filesystem();
		$files->get( static::$temp_dir . '/unknown-file.txt' );
	}

	public function testGetRequireReturnsProperly() {
		file_put_contents( static::$temp_dir . '/file.php', '<?php return "Howdy?"; ?>' );
		$files = new Filesystem();
		Assert::assertSame( 'Howdy?', $files->get_require( static::$temp_dir . '/file.php' ) );
	}

	public function testGetRequireThrowsExceptionNonExistingFile() {
		$this->expectException( FileNotFoundException::class );

		$files = new Filesystem();
		$files->get_require( static::$temp_dir . '/file.php' );
	}

	public function testAppendAddsDataToFile() {
		file_put_contents( static::$temp_dir . '/file.txt', 'foo' );
		$files        = new Filesystem();
		$bytesWritten = $files->append( static::$temp_dir . '/file.txt', 'bar' );
		Assert::assertEquals( mb_strlen( 'bar', '8bit' ), $bytesWritten );
		Assert::assertFileExists( static::$temp_dir . '/file.txt' );
		Assert::assertStringEqualsFile( static::$temp_dir . '/file.txt', 'foobar' );
	}

	public function testMoveMovesFiles() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		$files->move( static::$temp_dir . '/foo.txt', static::$temp_dir . '/bar.txt' );
		Assert::assertFileExists( static::$temp_dir . '/bar.txt' );
		Assert::assertFileDoesNotExist( static::$temp_dir . '/foo.txt' );
	}

	public function testNameReturnsName() {
		file_put_contents( static::$temp_dir . '/foobar.txt', 'foo' );
		$filesystem = new Filesystem();
		Assert::assertSame( 'foobar', $filesystem->name( static::$temp_dir . '/foobar.txt' ) );
	}

	public function testExtensionReturnsExtension() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		Assert::assertSame( 'txt', $files->extension( static::$temp_dir . '/foo.txt' ) );
	}

	public function testBasenameReturnsBasename() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		Assert::assertSame( 'foo.txt', $files->basename( static::$temp_dir . '/foo.txt' ) );
	}

	public function testDirnameReturnsDirectory() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		Assert::assertEquals( static::$temp_dir, $files->dirname( static::$temp_dir . '/foo.txt' ) );
	}

	public function testTypeIdentifiesFile() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		Assert::assertSame( 'file', $files->type( static::$temp_dir . '/foo.txt' ) );
	}

	public function testTypeIdentifiesDirectory() {
		mkdir( static::$temp_dir . '/foo-dir' );
		$files = new Filesystem();
		Assert::assertSame( 'dir', $files->type( static::$temp_dir . '/foo-dir' ) );
	}

	public function testSizeOutputsSize() {
		$size  = file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		Assert::assertEquals( $size, $files->size( static::$temp_dir . '/foo.txt' ) );
	}

	/**
	 * @requires extension fileinfo
	 */
	public function testMimeTypeOutputsMimeType() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		Assert::assertSame( 'text/plain', $files->mime_type( static::$temp_dir . '/foo.txt' ) );
	}

	public function testIsWritable() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		@chmod( static::$temp_dir . '/foo.txt', 0444 );
		Assert::assertFalse( $files->is_writable( static::$temp_dir . '/foo.txt' ) );
		@chmod( static::$temp_dir . '/foo.txt', 0777 );
		Assert::assertTrue( $files->is_writable( static::$temp_dir . '/foo.txt' ) );
	}

	public function testIsReadable() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		// chmod is noneffective on Windows
		if ( DIRECTORY_SEPARATOR === '\\' ) {
			Assert::assertTrue( $files->is_readable( static::$temp_dir . '/foo.txt' ) );
		} else {
			@chmod( static::$temp_dir . '/foo.txt', 0000 );
			Assert::assertFalse( $files->is_readable( static::$temp_dir . '/foo.txt' ) );
			@chmod( static::$temp_dir . '/foo.txt', 0777 );
			Assert::assertTrue( $files->is_readable( static::$temp_dir . '/foo.txt' ) );
		}
		Assert::assertFalse( $files->is_readable( static::$temp_dir . '/doesnotexist.txt' ) );
	}

	public function testGlobFindsFiles() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		file_put_contents( static::$temp_dir . '/bar.txt', 'bar' );
		$files = new Filesystem();
		$glob  = $files->glob( static::$temp_dir . '/*.txt' );
		Assert::assertContains( static::$temp_dir . '/foo.txt', $glob );
		Assert::assertContains( static::$temp_dir . '/bar.txt', $glob );
	}

	public function testAllFilesFindsFiles() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		file_put_contents( static::$temp_dir . '/bar.txt', 'bar' );
		$files    = new Filesystem();
		$allFiles = [];
		foreach ( $files->all_files( static::$temp_dir ) as $file ) {
			$allFiles[] = $file->getFilename();
		}
		Assert::assertContains( 'foo.txt', $allFiles );
		Assert::assertContains( 'bar.txt', $allFiles );
	}

	public function testDirectoriesFindsDirectories() {
		mkdir( static::$temp_dir . '/film' );
		mkdir( static::$temp_dir . '/music' );
		$files       = new Filesystem();
		$directories = $files->directories( static::$temp_dir );
		Assert::assertContains( static::$temp_dir . DIRECTORY_SEPARATOR . 'film', $directories );
		Assert::assertContains( static::$temp_dir . DIRECTORY_SEPARATOR . 'music', $directories );
	}

	public function testMakeDirectory() {
		$files = new Filesystem();
		Assert::assertTrue( $files->make_directory( static::$temp_dir . '/created' ) );
		Assert::assertFileExists( static::$temp_dir . '/created' );
	}

	public function testRequireOnceRequiresFileProperly() {
		$filesystem = new Filesystem();
		mkdir( static::$temp_dir . '/scripts' );
		file_put_contents( static::$temp_dir . '/scripts/foo.php', '<?php function random_function_xyz(){};' );
		$filesystem->require_once( static::$temp_dir . '/scripts/foo.php' );
		file_put_contents( static::$temp_dir . '/scripts/foo.php', '<?php function random_function_xyz_changed(){};' );
		$filesystem->require_once( static::$temp_dir . '/scripts/foo.php' );
		Assert::assertTrue( function_exists( 'random_function_xyz' ) );
		Assert::assertFalse( function_exists( 'random_function_xyz_changed' ) );
	}

	public function testCopyCopiesFileProperly() {
		$filesystem = new Filesystem();
		$data       = 'contents';
		mkdir( static::$temp_dir . '/text' );
		file_put_contents( static::$temp_dir . '/text/foo.txt', $data );
		$filesystem->copy( static::$temp_dir . '/text/foo.txt', static::$temp_dir . '/text/foo2.txt' );
		Assert::assertFileExists( static::$temp_dir . '/text/foo2.txt' );
		Assert::assertEquals( $data, file_get_contents( static::$temp_dir . '/text/foo2.txt' ) );
	}

	public function testIsFileChecksFilesProperly() {
		$filesystem = new Filesystem();
		mkdir( static::$temp_dir . '/help' );
		file_put_contents( static::$temp_dir . '/help/foo.txt', 'contents' );
		Assert::assertTrue( $filesystem->is_file( static::$temp_dir . '/help/foo.txt' ) );
		Assert::assertFalse( $filesystem->is_file( static::$temp_dir . './help' ) );
	}

	public function testFilesMethodReturnsFileInfoObjects() {
		mkdir( static::$temp_dir . '/objects' );
		file_put_contents( static::$temp_dir . '/objects/1.txt', '1' );
		file_put_contents( static::$temp_dir . '/objects/2.txt', '2' );
		mkdir( static::$temp_dir . '/objects/bar' );
		$files = new Filesystem();
		Assert::assertContainsOnlyInstancesOf( SplFileInfo::class, $files->files( static::$temp_dir . '/objects' ) );
		unset( $files );
	}

	public function testAllFilesReturnsFileInfoObjects() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		file_put_contents( static::$temp_dir . '/bar.txt', 'bar' );
		$files = new Filesystem();
		Assert::assertContainsOnlyInstancesOf( SplFileInfo::class, $files->all_files( static::$temp_dir ) );
	}

	public function testHash() {
		file_put_contents( static::$temp_dir . '/foo.txt', 'foo' );
		$filesystem = new Filesystem();
		Assert::assertSame( 'acbd18db4cc2f85cedef654fccc4a4d8', $filesystem->hash( static::$temp_dir . '/foo.txt' ) );
	}

	public function test_guess_class_name() {
		$filesystem = new Filesystem();

		$this->assertEquals( 'Example_Class', $filesystem->guess_class_name( __DIR__ . '/data/class-example-class.php' ) );
		$this->assertEquals( 'PsrStyleClass', $filesystem->guess_class_name( __DIR__ . '/data/PsrStyleClass.php' ) );
	}

	/**
	 * @param  string $file
	 * @return int
	 */
	private function get_file_permissions( $file ) {
		$filePerms = fileperms( $file );
		$filePerms = substr( sprintf( '%o', $filePerms ), -3 );

		return (int) base_convert( $filePerms, 8, 10 );
	}
}
