<?php

namespace Mantle\Tests\Filesystem;

use InvalidArgumentException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use Mantle\Filesystem\Filesystem;
use Mantle\Filesystem\Filesystem_Adapter;
use Mantle\Http\Uploaded_File;
use Mantle\Testing\Assert;
use PHPUnit\Framework\TestCase;
use Mockery as m;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Test_Filesystem_Adapter extends TestCase {
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

	public function testResponse() {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$files    = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$response = $files->response( 'file.txt' );

		ob_start();
		$response->sendContent();
		$content = ob_get_clean();

		$this->assertInstanceOf( StreamedResponse::class, $response );
		$this->assertSame( 'Hello World', $content );
		$this->assertSame( 'inline; filename=file.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testDownload() {
	$this->filesystem->write( 'file.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
	$response = $files->download( 'file.txt', 'hello.txt' );
	$this->assertInstanceOf( StreamedResponse::class, $response );
	$this->assertSame( 'attachment; filename=hello.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testDownloadNonAsciiFilename() {
	$this->filesystem->write( 'file.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
	$response = $files->download( 'file.txt', 'пиздюк.txt' );
	$this->assertInstanceOf( StreamedResponse::class, $response );
	$this->assertSame( "attachment; filename=pizdiuk.txt; filename*=utf-8''%D0%BF%D0%B8%D0%B7%D0%B4%D1%8E%D0%BA.txt", $response->headers->get( 'content-disposition' ) );
	}

	public function testDownloadNonAsciiEmptyFilename() {
	$this->filesystem->write( 'пиздюк.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
	$response = $files->download( 'пиздюк.txt' );
	$this->assertInstanceOf( StreamedResponse::class, $response );
	$this->assertSame( 'attachment; filename=pizdiuk.txt; filename*=utf-8\'\'%D0%BF%D0%B8%D0%B7%D0%B4%D1%8E%D0%BA.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testDownloadPercentInFilename() {
	$this->filesystem->write( 'Hello%World.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
	$response = $files->download( 'Hello%World.txt', 'Hello%World.txt' );
	$this->assertInstanceOf( StreamedResponse::class, $response );
	$this->assertSame( 'attachment; filename=HelloWorld.txt; filename*=utf-8\'\'Hello%25World.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testExists() {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$this->assertTrue( $filesystem_adapter->exists( 'file.txt' ) );
	}

	public function testMissing() {
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$this->assertTrue( $filesystem_adapter->missing( 'file.txt' ) );
	}

	public function testPath() {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$filesystem_adapter = new Filesystem_Adapter(
			$this->filesystem,
			$this->adapter,
			[
				'root' => $this->temp_dir . DIRECTORY_SEPARATOR,
			]
		);
		$this->assertEquals( $this->temp_dir . DIRECTORY_SEPARATOR . 'file.txt', $filesystem_adapter->path( 'file.txt' ) );
	}

	public function testGet() {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$this->assertSame( 'Hello World', $filesystem_adapter->get( 'file.txt' ) );
	}

	public function testGetFileNotFound() {
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$this->assertNull( $filesystem_adapter->get( 'file.txt' ) );
	}

	public function testPut() {
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->put( 'file.txt', 'Something inside' );
		$this->assertStringEqualsFile( $this->temp_dir . '/file.txt', 'Something inside' );
	}

	public function testPrepend() {
		file_put_contents( $this->temp_dir . '/file.txt', 'World' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->prepend( 'file.txt', 'Hello ' );
		$this->assertStringEqualsFile( $this->temp_dir . '/file.txt', 'Hello ' . PHP_EOL . 'World' );
	}

	public function testAppend() {
		file_put_contents( $this->temp_dir . '/file.txt', 'Hello ' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->append( 'file.txt', 'Moon' );
		$this->assertStringEqualsFile( $this->temp_dir . '/file.txt', 'Hello ' . PHP_EOL . 'Moon' );
	}

	public function testDelete() {
		file_put_contents( $this->temp_dir . '/file.txt', 'Hello World' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$this->assertTrue( $filesystem_adapter->delete( 'file.txt' ) );
		Assert::assertFileDoesNotExist( $this->temp_dir . '/file.txt' );
	}

	public function testCopy() {
		$data = '33232';
		mkdir( $this->temp_dir . '/foo' );
		file_put_contents( $this->temp_dir . '/foo/foo.txt', $data );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->copy( '/foo/foo.txt', '/foo/foo2.txt' );

		$this->assertFileExists( $this->temp_dir . '/foo/foo.txt' );
		$this->assertEquals( $data, file_get_contents( $this->temp_dir . '/foo/foo.txt' ) );

		$this->assertFileExists( $this->temp_dir . '/foo/foo2.txt' );
		$this->assertEquals( $data, file_get_contents( $this->temp_dir . '/foo/foo2.txt' ) );
	}

	public function testMove() {
		$data = '33232';
		mkdir( $this->temp_dir . '/foo' );
		file_put_contents( $this->temp_dir . '/foo/foo.txt', $data );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->move( '/foo/foo.txt', '/foo/foo2.txt' );

		Assert::assertFileDoesNotExist( $this->temp_dir . '/foo/foo.txt' );

		$this->assertFileExists( $this->temp_dir . '/foo/foo2.txt' );
		$this->assertEquals( $data, file_get_contents( $this->temp_dir . '/foo/foo2.txt' ) );
	}

	public function testStream() {
		$this->filesystem->write( 'file.txt', $original_content = 'Hello World' );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$readStream         = $filesystem_adapter->readStream( 'file.txt' );
		$filesystem_adapter->writeStream( 'copy.txt', $readStream );
		$this->assertEquals( $original_content, $filesystem_adapter->get( 'copy.txt' ) );
	}

	public function testStreamBetweenFilesystems() {
		$secondFilesystem = new Flysystem( $adapter = new LocalFilesystemAdapter( $this->temp_dir . '/second' ) );
		$this->filesystem->write( 'file.txt', $original_content = 'Hello World' );
		$filesystem_adapter       = new Filesystem_Adapter( $this->filesystem, $this->adapter );
		$secondFilesystem_Adapter = new Filesystem_Adapter( $secondFilesystem, $adapter );
		$readStream               = $filesystem_adapter->readStream( 'file.txt' );
		$secondFilesystem_Adapter->writeStream( 'copy.txt', $readStream );
		$this->assertEquals( $original_content, $secondFilesystem_Adapter->get( 'copy.txt' ) );
	}

	public function testStreamToExistingFileOverwrites() {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$this->filesystem->write( 'existing.txt', 'Dear Kate' );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter, [ 'throw' => false ] );;
		$readStream         = $filesystem_adapter->readStream( 'file.txt' );

		$this->assertTrue(
			$filesystem_adapter->writeStream( 'existing.txt', $readStream ),
		);
	}

	public function testReadStreamNonExistentFile() {
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );

		$this->assertFalse( $filesystem_adapter->readStream( 'nonexistent.txt' ) );
	}

	public function testReadStreamNonExistentFileThrows() {
		$this->expectException( UnableToReadFile::class );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter, [ 'throw' => true ] );

		$filesystem_adapter->readStream( 'nonexistent.txt' );
	}

	public function testStreamInvalidResourceThrows() {
		$this->expectException( InvalidArgumentException::class );
		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;
		$filesystem_adapter->writeStream( 'file.txt', 'foo bar' );
	}

	public function testPutFileAs() {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'uploaded file content' );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;

		$uploadedFile = new Uploaded_File( $filePath, 'org.txt', null, null, true );

		$storagePath = $filesystem_adapter->put_file_as( '/', $uploadedFile, 'new.txt' );

		$this->assertSame( 'new.txt', $storagePath );

		$this->assertFileExists( $filePath );

		$filesystem_adapter->assertExists( $storagePath );

		$this->assertSame( 'uploaded file content', $filesystem_adapter->read( $storagePath ) );
	}

	public function testPutFileAsWithAbsoluteFilePath() {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'normal file content' );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;

		$storagePath = $filesystem_adapter->put_file_as( '/', $filePath, 'new.txt' );

		$this->assertSame( 'normal file content', $filesystem_adapter->read( $storagePath ) );
	}

	public function testPutFile() {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'uploaded file content' );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );;

		$uploadedFile = new Uploaded_File( $filePath, 'org.txt', null, null, true );

		$storagePath = $filesystem_adapter->put_file( '/', $uploadedFile );

		$this->assertSame( 44, strlen( $storagePath ) ); // random 40 characters + ".txt"

		$this->assertFileExists( $filePath );

		$filesystem_adapter->assertExists( $storagePath );
	}

	public function testPutFileWithAbsoluteFilePath() {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'uploaded file content' );

		$filesystem_adapter = new Filesystem_Adapter( $this->filesystem, $this->adapter );

		$storagePath = $filesystem_adapter->put_file( '/', $filePath );

		$this->assertSame( 44, strlen( $storagePath ) ); // random 40 characters + ".txt"

		$filesystem_adapter->assertExists( $storagePath );
	}

	public function test_url() {
		$this->assertEquals(
			home_url( '/wp-content/uploads/foo.txt' ),
			( new Filesystem_Adapter( $this->filesystem, $this->adapter ) )->url( '/foo.txt' )
		);

		// TODO: add test for config url.
	}
}
