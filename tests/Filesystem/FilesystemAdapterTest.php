<?php

namespace Mantle\Tests\Filesystem;

use InvalidArgumentException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use Mantle\Filesystem\Filesystem;
use Mantle\Filesystem\Filesystem_Adapter;
use Mantle\Http\Uploaded_File;
use Mantle\Testing\Assert;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class FilesystemAdapterTest extends TestCase {
	private string $temp_dir;

	private Flysystem $filesystem;

	private FilesystemAdapter $adapter;

	protected function setUp(): void {
		$this->temp_dir   = get_temp_dir() . '/mantle-fs-adp';
		$this->filesystem = new Flysystem(
			$this->adapter = new LocalFilesystemAdapter( $this->temp_dir ),
		);

		$files = new Filesystem();
		$files->ensure_directory_exists( $this->temp_dir );
		$files->clean_directory( $this->temp_dir );

	}

	protected function tearDown(): void {
		( new Filesystem() )->delete_directory( $this->temp_dir );

		m::close();

		parent::tearDown();
	}

	public function testPutReturnsFalseWhenWriteFails(): void {
		mkdir( $this->temp_dir . '/ro', 0500 );

		$files = new Filesystem_Adapter( $this->filesystem, $this->adapter );

		$this->assertFalse( $files->put( 'ro/sub/file.txt', 'contents' ) );

		$throwing = new Filesystem_Adapter( $this->filesystem, $this->adapter, [ 'throw' => true ] );

		$this->expectException( \League\Flysystem\FilesystemException::class );

		$throwing->put( 'ro/sub/file.txt', 'contents' );
	}

	public function testResponse(): void {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$files    = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$response = $files->response( 'file.txt' );

		ob_start();
		$response->sendContent();
		$content = ob_get_clean();

		$this->assertSame( 'Hello World', $content );
		$this->assertSame( 'inline; filename=file.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testDownload(): void {
	$this->filesystem->write( 'file.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
	$response = $files->download( 'file.txt', 'hello.txt' );
	$this->assertSame( 'attachment; filename=hello.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testDownloadNonAsciiFilename(): void {
	$this->filesystem->write( 'file.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
	$response = $files->download( 'file.txt', 'пиздюк.txt' );
	$this->assertSame( "attachment; filename=pizdiuk.txt; filename*=utf-8''%D0%BF%D0%B8%D0%B7%D0%B4%D1%8E%D0%BA.txt", $response->headers->get( 'content-disposition' ) );
	}

	public function testDownloadNonAsciiEmptyFilename(): void {
	$this->filesystem->write( 'пиздюк.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
	$response = $files->download( 'пиздюк.txt' );
	$this->assertSame( 'attachment; filename=pizdiuk.txt; filename*=utf-8\'\'%D0%BF%D0%B8%D0%B7%D0%B4%D1%8E%D0%BA.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testDownloadPercentInFilename(): void {
	$this->filesystem->write( 'Hello%World.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
	$response = $files->download( 'Hello%World.txt', 'Hello%World.txt' );
	$this->assertSame( 'attachment; filename=HelloWorld.txt; filename*=utf-8\'\'Hello%25World.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testExists(): void {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$this->assertTrue( $filesystem_adapter->exists( 'file.txt' ) );
	}

	public function testMissing(): void {
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$this->assertTrue( $filesystem_adapter->missing( 'file.txt' ) );
	}

	public function testPath(): void {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$filesystem_adapter = new Filesystem_Adapter(
			$this->filesystem,
			$this->adapter,
			[
				'root' => $this->temp_dir . DIRECTORY_SEPARATOR,
			]
		);
		$this->assertSame( $this->temp_dir . DIRECTORY_SEPARATOR . 'file.txt', $filesystem_adapter->path( 'file.txt' ) );
	}

	public function testGet(): void {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$this->assertSame( 'Hello World', $filesystem_adapter->get( 'file.txt' ) );
	}

	public function testGetFileNotFound(): void {
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$this->assertNull( $filesystem_adapter->get( 'file.txt' ) );
	}

	public function testPut(): void {
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->put( 'file.txt', 'Something inside' );
		$this->assertStringEqualsFile( $this->temp_dir . '/file.txt', 'Something inside' );
	}

	public function testPrepend(): void {
		file_put_contents( $this->temp_dir . '/file.txt', 'World' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->prepend( 'file.txt', 'Hello ' );
		$this->assertStringEqualsFile( $this->temp_dir . '/file.txt', 'Hello ' . PHP_EOL . 'World' );
	}

	public function testAppend(): void {
		file_put_contents( $this->temp_dir . '/file.txt', 'Hello ' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->append( 'file.txt', 'Moon' );
		$this->assertStringEqualsFile( $this->temp_dir . '/file.txt', 'Hello ' . PHP_EOL . 'Moon' );
	}

	public function testDelete(): void {
		file_put_contents( $this->temp_dir . '/file.txt', 'Hello World' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$this->assertTrue( $filesystem_adapter->delete( 'file.txt' ) );
		$this->assertFileDoesNotExist( $this->temp_dir . '/file.txt' );
	}

	public function testCopy(): void {
		$data = '33232';
		mkdir( $this->temp_dir . '/foo' );
		file_put_contents( $this->temp_dir . '/foo/foo.txt', $data );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->copy( '/foo/foo.txt', '/foo/foo2.txt' );

		$this->assertFileExists( $this->temp_dir . '/foo/foo.txt' );
		$this->assertSame( $data, file_get_contents( $this->temp_dir . '/foo/foo.txt' ) );

		$this->assertFileExists( $this->temp_dir . '/foo/foo2.txt' );
		$this->assertSame( $data, file_get_contents( $this->temp_dir . '/foo/foo2.txt' ) );
	}

	public function testMove(): void {
		$data = '33232';
		mkdir( $this->temp_dir . '/foo' );
		file_put_contents( $this->temp_dir . '/foo/foo.txt', $data );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->move( '/foo/foo.txt', '/foo/foo2.txt' );

		$this->assertFileDoesNotExist( $this->temp_dir . '/foo/foo.txt' );

		$this->assertFileExists( $this->temp_dir . '/foo/foo2.txt' );
		$this->assertSame( $data, file_get_contents( $this->temp_dir . '/foo/foo2.txt' ) );
	}

	public function testStream(): void {
		$this->filesystem->write( 'file.txt', $original_content = 'Hello World' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$readStream         = $filesystem_adapter->readStream( 'file.txt' );
		$filesystem_adapter->writeStream( 'copy.txt', $readStream );
		$this->assertSame( $original_content, $filesystem_adapter->get( 'copy.txt' ) );
	}

	public function testStreamBetweenFilesystems(): void {
		$secondFilesystem = new Flysystem( $adapter = new LocalFilesystemAdapter( $this->temp_dir . '/second' ) );
		$this->filesystem->write( 'file.txt', $original_content = 'Hello World' );
		$filesystem_adapter       = new Filesystem_Adapter( $this->filesystem, $this->adapter );
		$secondFilesystem_Adapter = new Filesystem_Adapter( $secondFilesystem, $adapter );
		$readStream               = $filesystem_adapter->readStream( 'file.txt' );
		$secondFilesystem_Adapter->writeStream( 'copy.txt', $readStream );
		$this->assertSame( $original_content, $secondFilesystem_Adapter->get( 'copy.txt' ) );
	}

	public function testStreamToExistingFileOverwrites(): void {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$this->filesystem->write( 'existing.txt', 'Dear Kate' );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter, [ 'throw' => false ] );;
		$readStream         = $filesystem_adapter->readStream( 'file.txt' );

		$this->assertTrue(
			$filesystem_adapter->writeStream( 'existing.txt', $readStream ),
		);
	}

	public function testReadStreamNonExistentFile(): void {
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );

		$this->assertFalse( $filesystem_adapter->readStream( 'nonexistent.txt' ) );
	}

	public function testReadStreamNonExistentFileThrows(): void {
		$this->expectException( UnableToReadFile::class );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter, [ 'throw' => true ] );

		$filesystem_adapter->readStream( 'nonexistent.txt' );
	}

	public function testStreamInvalidResourceThrows(): void {
		$this->expectException( InvalidArgumentException::class );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->writeStream( 'file.txt', 'foo bar' );
	}

	public function testPutFileAs(): void {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'uploaded file content' );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;

		$uploadedFile = new Uploaded_File( $filePath, 'org.txt', null, null, true );

		$storagePath = $filesystem_adapter->put_file_as( '/', $uploadedFile, 'new.txt' );

		$this->assertSame( 'new.txt', $storagePath );

		$this->assertFileExists( $filePath );

		$filesystem_adapter->assertExists( $storagePath );

		$this->assertSame( 'uploaded file content', $filesystem_adapter->read( $storagePath ) );
	}

	public function testPutFileAsWithAbsoluteFilePath(): void {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'normal file content' );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;

		$storagePath = $filesystem_adapter->put_file_as( '/', $filePath, 'new.txt' );

		$this->assertSame( 'normal file content', $filesystem_adapter->read( $storagePath ) );
	}

	public function testPutFile(): void {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'uploaded file content' );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;

		$uploadedFile = new Uploaded_File( $filePath, 'org.txt', null, null, true );

		$storagePath = $filesystem_adapter->put_file( '/', $uploadedFile );

		$this->assertSame( 44, strlen( $storagePath ) ); // random 40 characters + ".txt"

		$this->assertFileExists( $filePath );

		$filesystem_adapter->assertExists( $storagePath );
	}

	public function testPutFileWithAbsoluteFilePath(): void {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'uploaded file content' );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );

		$storagePath = $filesystem_adapter->put_file( '/', $filePath );

		$this->assertSame( 44, strlen( $storagePath ) ); // random 40 characters + ".txt"

		$filesystem_adapter->assertExists( $storagePath );
	}
}
