<?php

namespace Mantle\Tests\Filesystem;

use Mantle\Filesystem\File_Not_Found_Exception;
use Mantle\Filesystem\Filesystem;
use Mockery as m;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class FilesystemTest extends TestCase {

	private static string $temp_dir = '';

	protected function setUp(): void {
		parent::setUp();

		self::$temp_dir = get_temp_dir() . '/mantle-fs';

		$files = new Filesystem();
		$files->ensure_directory_exists( self::$temp_dir );
		$files->clean_directory( self::$temp_dir );
	}

	public function testLinkCreatesSymlink(): void {
		file_put_contents( self::$temp_dir . '/link-target.txt', 'target' );

		( new Filesystem() )->link( self::$temp_dir . '/link-target.txt', self::$temp_dir . '/link.txt' );

		$this->assertTrue( is_link( self::$temp_dir . '/link.txt' ) );
		$this->assertSame( 'target', file_get_contents( self::$temp_dir . '/link.txt' ) );
	}

	protected function tearDown(): void {
		parent::tearDown();

		m::close();

		$files = new Filesystem();
		$files->delete_directory( self::$temp_dir );

		self::$temp_dir = '';
	}

	public function testGetRetrievesFiles(): void {
		file_put_contents( self::$temp_dir . '/file.txt', 'Hello World' );
		$files = new Filesystem();
		$this->assertSame( 'Hello World', $files->get( self::$temp_dir . '/file.txt' ) );
	}

	public function testPutStoresFiles(): void {
		$files = new Filesystem();
		$files->put( self::$temp_dir . '/file.txt', 'Hello World' );
		$this->assertStringEqualsFile( self::$temp_dir . '/file.txt', 'Hello World' );
	}

	public function testPutJson(): void {
		$files = new Filesystem();
		$files->put_json( self::$temp_dir . '/file.json', [ 'foo' => 'bar' ] );
		$this->assertStringEqualsFile( self::$temp_dir . '/file.json', '{"foo":"bar"}' );
	}

	public function testReplaceCreatesFile(): void {
		$tempFile = self::$temp_dir . '/file.txt';

		$filesystem = new Filesystem();

		$filesystem->replace( $tempFile, 'Hello World' );
		$this->assertStringEqualsFile( $tempFile, 'Hello World' );
	}

	public function testReplaceWhenUnixSymlinkExists(): void {
		$tempFile   = self::$temp_dir . '/file.txt';
		$symlinkDir = self::$temp_dir . '/symlink_dir';
		$symlink    = "{$symlinkDir}/symlink.txt";

		if ( ! is_dir( $symlinkDir ) ) {
			mkdir( $symlinkDir );
		}

		symlink( $tempFile, $symlink );

		// Prevent changes to symlink_dir
		chmod( $symlinkDir, 0555 );

		// Test with a weird non-standard umask.
		$umask         = 0131;
		$originalUmask = umask( $umask );

		$filesystem = new Filesystem();

		// Test replacing non-existent file.
		$filesystem->replace( $tempFile, 'Hello World' );
		$this->assertStringEqualsFile( $tempFile, 'Hello World' );
		$this->assertSame( $umask, 0777 - $this->get_file_permissions( $tempFile ) );

		// Test replacing existing file.
		$filesystem->replace( $tempFile, 'Something Else' );
		$this->assertStringEqualsFile( $tempFile, 'Something Else' );
		$this->assertSame( $umask, 0777 - $this->get_file_permissions( $tempFile ) );

		// Test replacing symlinked file.
		$filesystem->replace( $symlink, 'Yet Something Else Again' );
		$this->assertStringEqualsFile( $tempFile, 'Yet Something Else Again' );
		$this->assertSame( $umask, 0777 - $this->get_file_permissions( $tempFile ) );

		umask( $originalUmask );

		// Reset changes to symlink_dir
		chmod( $symlinkDir, 0777 - $originalUmask );
	}

	public function testSetChmod(): void {
		file_put_contents( self::$temp_dir . '/file.txt', 'Hello World' );
		$files = new Filesystem();
		$files->chmod( self::$temp_dir . '/file.txt', 0755 );
		$filePermission      = substr( sprintf( '%o', fileperms( self::$temp_dir . '/file.txt' ) ), -4 );
		$expectedPermissions = DIRECTORY_SEPARATOR == '\\' ? '0666' : '0755';
		$this->assertSame( $expectedPermissions, $filePermission );
	}

	public function testGetChmod(): void {
		file_put_contents( self::$temp_dir . '/file.txt', 'Hello World' );
		chmod( self::$temp_dir . '/file.txt', 0755 );

		$files               = new Filesystem();
		$filePermission      = $files->chmod( self::$temp_dir . '/file.txt' );
		$expectedPermissions = DIRECTORY_SEPARATOR == '\\' ? '0666' : '0755';
		$this->assertSame( $expectedPermissions, $filePermission );
	}

	public function testDeleteRemovesFiles(): void {
		file_put_contents( self::$temp_dir . '/file1.txt', 'Hello World' );
		file_put_contents( self::$temp_dir . '/file2.txt', 'Hello World' );
		file_put_contents( self::$temp_dir . '/file3.txt', 'Hello World' );

		$files = new Filesystem();
		$files->delete( self::$temp_dir . '/file1.txt' );
		$this->assertFileDoesNotExist( self::$temp_dir . '/file1.txt' );

		$files->delete( self::$temp_dir . '/file2.txt', self::$temp_dir . '/file3.txt' );
		$this->assertFileDoesNotExist( self::$temp_dir . '/file2.txt' );
		$this->assertFileDoesNotExist( self::$temp_dir . '/file3.txt' );
	}

	public function testPrependExistingFiles(): void {
		$files = new Filesystem();
		$files->put( self::$temp_dir . '/file.txt', 'World' );
		$files->prepend( self::$temp_dir . '/file.txt', 'Hello ' );
		$this->assertStringEqualsFile( self::$temp_dir . '/file.txt', 'Hello World' );
	}

	public function testPrependNewFiles(): void {
		$files = new Filesystem();
		$files->prepend( self::$temp_dir . '/file.txt', 'Hello World' );
		$this->assertStringEqualsFile( self::$temp_dir . '/file.txt', 'Hello World' );
	}

	public function testMissingFile(): void {
		$files = new Filesystem();
		$this->assertTrue( $files->missing( self::$temp_dir . '/file.txt' ) );
	}

	public function testDeleteDirectory(): void {
		mkdir( self::$temp_dir . '/foo' );
		file_put_contents( self::$temp_dir . '/foo/file.txt', 'Hello World' );
		$files = new Filesystem();
		$files->delete_directory( self::$temp_dir . '/foo' );
		$this->assertDirectoryDoesNotExist( self::$temp_dir . '/foo' );
		$this->assertFileDoesNotExist( self::$temp_dir . '/foo/file.txt' );
	}

	public function testDeleteDirectoryReturnFalseWhenNotADirectory(): void {
		mkdir( self::$temp_dir . '/bar' );
		file_put_contents( self::$temp_dir . '/bar/file.txt', 'Hello World' );
		$files = new Filesystem();
		$this->assertFalse( $files->delete_directory( self::$temp_dir . '/bar/file.txt' ) );
	}

	public function testCleanDirectory(): void {
		mkdir( self::$temp_dir . '/baz' );
		file_put_contents( self::$temp_dir . '/baz/file.txt', 'Hello World' );
		$files = new Filesystem();
		$files->clean_directory( self::$temp_dir . '/baz' );
		$this->assertDirectoryExists( self::$temp_dir . '/baz' );
		$this->assertFileDoesNotExist( self::$temp_dir . '/baz/file.txt' );
	}

	public function testMacro(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'Hello World' );
		$files   = new Filesystem();
		$temp_dir = self::$temp_dir;
		$files->macro(
			'getFoo',
			function () use ( $files, $temp_dir ) {
				return $files->get( $temp_dir . '/foo.txt' );
			}
		);
		$this->assertSame( 'Hello World', $files->getFoo() );
	}

	public function testFilesMethod(): void {
		mkdir( self::$temp_dir . '/views' );
		file_put_contents( self::$temp_dir . '/views/1.txt', '1' );
		file_put_contents( self::$temp_dir . '/views/2.txt', '2' );
		mkdir( self::$temp_dir . '/views/_layouts' );
		$files   = new Filesystem();
		$results = $files->files( self::$temp_dir . '/views' );
		$this->assertContainsOnlyInstancesOf( SplFileInfo::class, $results );
		unset( $files );
	}

	public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory(): void {
		$files = new Filesystem();
		$this->assertFalse( $files->copy_directory( self::$temp_dir . '/breeze/boom/foo/bar/baz', self::$temp_dir ) );
	}

	public function testCopyDirectoryMovesEntireDirectory(): void {
		mkdir( self::$temp_dir . '/tmp', 0777, true );
		file_put_contents( self::$temp_dir . '/tmp/foo.txt', '' );
		file_put_contents( self::$temp_dir . '/tmp/bar.txt', '' );
		mkdir( self::$temp_dir . '/tmp/nested', 0777, true );
		file_put_contents( self::$temp_dir . '/tmp/nested/baz.txt', '' );

		$files = new Filesystem();
		$files->copy_directory( self::$temp_dir . '/tmp', self::$temp_dir . '/tmp2' );
		$this->assertDirectoryExists( self::$temp_dir . '/tmp2' );
		$this->assertFileExists( self::$temp_dir . '/tmp2/foo.txt' );
		$this->assertFileExists( self::$temp_dir . '/tmp2/bar.txt' );
		$this->assertDirectoryExists( self::$temp_dir . '/tmp2/nested' );
		$this->assertFileExists( self::$temp_dir . '/tmp2/nested/baz.txt' );
	}

	public function testMoveDirectoryMovesEntireDirectory(): void {
		mkdir( self::$temp_dir . '/tmp2', 0777, true );
		file_put_contents( self::$temp_dir . '/tmp2/foo.txt', '' );
		file_put_contents( self::$temp_dir . '/tmp2/bar.txt', '' );
		mkdir( self::$temp_dir . '/tmp2/nested', 0777, true );
		file_put_contents( self::$temp_dir . '/tmp2/nested/baz.txt', '' );

		$files = new Filesystem();
		$files->move_directory( self::$temp_dir . '/tmp2', self::$temp_dir . '/tmp3' );
		$this->assertDirectoryExists( self::$temp_dir . '/tmp3' );
		$this->assertFileExists( self::$temp_dir . '/tmp3/foo.txt' );
		$this->assertFileExists( self::$temp_dir . '/tmp3/bar.txt' );
		$this->assertDirectoryExists( self::$temp_dir . '/tmp3/nested' );
		$this->assertFileExists( self::$temp_dir . '/tmp3/nested/baz.txt' );
		$this->assertDirectoryDoesNotExist( self::$temp_dir . '/tmp2' );
	}

	public function testMoveDirectoryMovesEntireDirectoryAndOverwrites(): void {
		mkdir( self::$temp_dir . '/tmp4', 0777, true );
		file_put_contents( self::$temp_dir . '/tmp4/foo.txt', '' );
		file_put_contents( self::$temp_dir . '/tmp4/bar.txt', '' );
		mkdir( self::$temp_dir . '/tmp4/nested', 0777, true );
		file_put_contents( self::$temp_dir . '/tmp4/nested/baz.txt', '' );
		mkdir( self::$temp_dir . '/tmp5', 0777, true );
		file_put_contents( self::$temp_dir . '/tmp5/foo2.txt', '' );
		file_put_contents( self::$temp_dir . '/tmp5/bar2.txt', '' );

		$files = new Filesystem();
		$files->move_directory( self::$temp_dir . '/tmp4', self::$temp_dir . '/tmp5', true );
		$this->assertDirectoryExists( self::$temp_dir . '/tmp5' );
		$this->assertFileExists( self::$temp_dir . '/tmp5/foo.txt' );
		$this->assertFileExists( self::$temp_dir . '/tmp5/bar.txt' );
		$this->assertDirectoryExists( self::$temp_dir . '/tmp5/nested' );
		$this->assertFileExists( self::$temp_dir . '/tmp5/nested/baz.txt' );
		$this->assertFileDoesNotExist( self::$temp_dir . '/tmp5/foo2.txt' );
		$this->assertFileDoesNotExist( self::$temp_dir . '/tmp5/bar2.txt' );
		$this->assertDirectoryDoesNotExist( self::$temp_dir . '/tmp4' );
	}

	public function testMoveDirectoryReturnsFalseWhileOverwritingAndUnableToDeleteDestinationDirectory(): void {
		mkdir( self::$temp_dir . '/tmp6', 0777, true );
		file_put_contents( self::$temp_dir . '/tmp6/foo.txt', '' );
		mkdir( self::$temp_dir . '/tmp7', 0777, true );

		$files = $this->getMockBuilder( Filesystem::class )->onlyMethods( [ 'delete_directory' ] )->getMock();
		$files->expects( $this->once() )->method( 'delete_directory' )->willReturn( false );
		$this->assertFalse( $files->move_directory( self::$temp_dir . '/tmp6', self::$temp_dir . '/tmp7', true ) );
	}

	public function testGetThrowsExceptionNonexisitingFile(): void {
		$this->expectException( File_Not_Found_Exception::class );

		$files = new Filesystem();
		$files->get( self::$temp_dir . '/unknown-file.txt' );
	}

	public function testGetRequireReturnsProperly(): void {
		file_put_contents( self::$temp_dir . '/file.php', '<?php return "Howdy?"; ?>' );
		$files = new Filesystem();
		$this->assertSame( 'Howdy?', $files->get_require( self::$temp_dir . '/file.php' ) );
	}

	public function testGetRequireThrowsExceptionNonExistingFile(): void {
		$this->expectException( File_Not_Found_Exception::class );

		$files = new Filesystem();
		$files->get_require( self::$temp_dir . '/file.php' );
	}

	public function testAppendAddsDataToFile(): void {
		file_put_contents( self::$temp_dir . '/file.txt', 'foo' );
		$files        = new Filesystem();
		$bytesWritten = $files->append( self::$temp_dir . '/file.txt', 'bar' );
		$this->assertSame( mb_strlen( 'bar', '8bit' ), $bytesWritten );
		$this->assertFileExists( self::$temp_dir . '/file.txt' );
		$this->assertStringEqualsFile( self::$temp_dir . '/file.txt', 'foobar' );
	}

	public function testMoveMovesFiles(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		$files->move( self::$temp_dir . '/foo.txt', self::$temp_dir . '/bar.txt' );
		$this->assertFileExists( self::$temp_dir . '/bar.txt' );
		$this->assertFileDoesNotExist( self::$temp_dir . '/foo.txt' );
	}

	public function testNameReturnsName(): void {
		file_put_contents( self::$temp_dir . '/foobar.txt', 'foo' );
		$filesystem = new Filesystem();
		$this->assertSame( 'foobar', $filesystem->name( self::$temp_dir . '/foobar.txt' ) );
	}

	public function testExtensionReturnsExtension(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		$this->assertSame( 'txt', $files->extension( self::$temp_dir . '/foo.txt' ) );
	}

	public function testBasenameReturnsBasename(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		$this->assertSame( 'foo.txt', $files->basename( self::$temp_dir . '/foo.txt' ) );
	}

	public function testDirnameReturnsDirectory(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		$this->assertSame( self::$temp_dir, $files->dirname( self::$temp_dir . '/foo.txt' ) );
	}

	public function testTypeIdentifiesFile(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		$this->assertSame( 'file', $files->type( self::$temp_dir . '/foo.txt' ) );
	}

	public function testTypeIdentifiesDirectory(): void {
		mkdir( self::$temp_dir . '/foo-dir' );
		$files = new Filesystem();
		$this->assertSame( 'dir', $files->type( self::$temp_dir . '/foo-dir' ) );
	}

	public function testSizeOutputsSize(): void {
		$size  = file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		$this->assertSame( $size, $files->size( self::$temp_dir . '/foo.txt' ) );
	}

	/**
	 * @requires extension fileinfo
	 */
	#[RequiresPhpExtension( 'fileinfo' )]
	public function testMimeTypeOutputsMimeType(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		$this->assertSame( 'text/plain', $files->mime_type( self::$temp_dir . '/foo.txt' ) );
	}

	public function testIsWritable(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		@chmod( self::$temp_dir . '/foo.txt', 0444 );
		$this->assertFalse( $files->is_writable( self::$temp_dir . '/foo.txt' ) );
		@chmod( self::$temp_dir . '/foo.txt', 0777 );
		$this->assertTrue( $files->is_writable( self::$temp_dir . '/foo.txt' ) );
	}

	public function testIsReadable(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		$files = new Filesystem();
		// chmod is noneffective on Windows
		if ( DIRECTORY_SEPARATOR === '\\' ) {
			$this->assertTrue( $files->is_readable( self::$temp_dir . '/foo.txt' ) );
		} else {
			@chmod( self::$temp_dir . '/foo.txt', 0000 );
			$this->assertFalse( $files->is_readable( self::$temp_dir . '/foo.txt' ) );
			@chmod( self::$temp_dir . '/foo.txt', 0777 );
			$this->assertTrue( $files->is_readable( self::$temp_dir . '/foo.txt' ) );
		}
		$this->assertFalse( $files->is_readable( self::$temp_dir . '/doesnotexist.txt' ) );
	}

	public function testGlobFindsFiles(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		file_put_contents( self::$temp_dir . '/bar.txt', 'bar' );
		$files = new Filesystem();
		$glob  = $files->glob( self::$temp_dir . '/*.txt' );
		$this->assertContains( self::$temp_dir . '/foo.txt', $glob );
		$this->assertContains( self::$temp_dir . '/bar.txt', $glob );
	}

	public function testAllFilesFindsFiles(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		file_put_contents( self::$temp_dir . '/bar.txt', 'bar' );
		$files    = new Filesystem();
		$allFiles = [];
		foreach ( $files->all_files( self::$temp_dir ) as $file ) {
			$allFiles[] = $file->getFilename();
		}
		$this->assertContains( 'foo.txt', $allFiles );
		$this->assertContains( 'bar.txt', $allFiles );
	}

	public function testDirectoriesFindsDirectories(): void {
		mkdir( self::$temp_dir . '/film' );
		mkdir( self::$temp_dir . '/music' );
		$files       = new Filesystem();
		$directories = $files->directories( self::$temp_dir );
		$this->assertContains( self::$temp_dir . DIRECTORY_SEPARATOR . 'film', $directories );
		$this->assertContains( self::$temp_dir . DIRECTORY_SEPARATOR . 'music', $directories );
	}

	public function testMakeDirectory(): void {
		$files = new Filesystem();
		$this->assertTrue( $files->make_directory( self::$temp_dir . '/created' ) );
		$this->assertFileExists( self::$temp_dir . '/created' );
	}

	public function testRequireOnceRequiresFileProperly(): void {
		$filesystem = new Filesystem();
		mkdir( self::$temp_dir . '/scripts' );
		file_put_contents( self::$temp_dir . '/scripts/foo.php', '<?php function random_function_xyz(){};' );
		$filesystem->require_once( self::$temp_dir . '/scripts/foo.php' );
		file_put_contents( self::$temp_dir . '/scripts/foo.php', '<?php function random_function_xyz_changed(){};' );
		$filesystem->require_once( self::$temp_dir . '/scripts/foo.php' );
		$this->assertTrue( function_exists( 'random_function_xyz' ) );
		$this->assertFalse( function_exists( 'random_function_xyz_changed' ) );
	}

	public function testCopyCopiesFileProperly(): void {
		$filesystem = new Filesystem();
		$data       = 'contents';
		mkdir( self::$temp_dir . '/text' );
		file_put_contents( self::$temp_dir . '/text/foo.txt', $data );
		$filesystem->copy( self::$temp_dir . '/text/foo.txt', self::$temp_dir . '/text/foo2.txt' );
		$this->assertFileExists( self::$temp_dir . '/text/foo2.txt' );
		$this->assertSame( $data, file_get_contents( self::$temp_dir . '/text/foo2.txt' ) );
	}

	public function testIsFileChecksFilesProperly(): void {
		$filesystem = new Filesystem();
		mkdir( self::$temp_dir . '/help' );
		file_put_contents( self::$temp_dir . '/help/foo.txt', 'contents' );
		$this->assertTrue( $filesystem->is_file( self::$temp_dir . '/help/foo.txt' ) );
		$this->assertFalse( $filesystem->is_file( self::$temp_dir . './help' ) );
	}

	public function testFilesMethodReturnsFileInfoObjects(): void {
		mkdir( self::$temp_dir . '/objects' );
		file_put_contents( self::$temp_dir . '/objects/1.txt', '1' );
		file_put_contents( self::$temp_dir . '/objects/2.txt', '2' );
		mkdir( self::$temp_dir . '/objects/bar' );
		$files = new Filesystem();
		$this->assertContainsOnlyInstancesOf( SplFileInfo::class, $files->files( self::$temp_dir . '/objects' ) );
		unset( $files );
	}

	public function testAllFilesReturnsFileInfoObjects(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		file_put_contents( self::$temp_dir . '/bar.txt', 'bar' );
		$files = new Filesystem();
		$this->assertContainsOnlyInstancesOf( SplFileInfo::class, $files->all_files( self::$temp_dir ) );
	}

	public function testHash(): void {
		file_put_contents( self::$temp_dir . '/foo.txt', 'foo' );
		$filesystem = new Filesystem();
		$this->assertSame( 'acbd18db4cc2f85cedef654fccc4a4d8', $filesystem->hash( self::$temp_dir . '/foo.txt' ) );
	}

	public function test_guess_class_name(): void {
		$filesystem = new Filesystem();

		$this->assertSame( 'Example_Class', $filesystem->guess_class_name( __DIR__ . '/data/class-example-class.php' ) );
		$this->assertSame( 'PsrStyleClass', $filesystem->guess_class_name( __DIR__ . '/data/PsrStyleClass.php' ) );
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
