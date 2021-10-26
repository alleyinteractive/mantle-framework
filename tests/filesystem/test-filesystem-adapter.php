<?php

namespace Mantle\Tests\Filesystem;

use GuzzleHttp\Psr7\Stream;
use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem as Flysystem;
use Mantle\Filesystem\Filesystem;
use Mantle\Filesystem\Filesystem_Adapter;
use Mantle\Http\Uploaded_File;
use Mantle\Testing\Assert;
use PHPUnit\Framework\TestCase;
use Mockery as m;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Test_Filesystem_Adapter extends TestCase {
	private $temp_dir;

	/**
	 * @var Flysystem
	 */
	private $filesystem;

	protected function setUp(): void {
		$this->temp_dir   = get_temp_dir() . '/mantle-fs-adp';
		$this->filesystem = new Flysystem( new Local( $this->temp_dir ) );

		$files = new Filesystem();
		$files->ensure_directory_exists( $this->temp_dir );
		$files->clean_directory( $this->temp_dir );

	}

	protected function tearDown(): void {
		$this->filesystem->deleteDir( $this->temp_dir );
		m::close();
	}

	public function testResponse() {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$files    = new Filesystem_Adapter( $this->filesystem );
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
	$files    = new Filesystem_Adapter( $this->filesystem );
	$response = $files->download( 'file.txt', 'hello.txt' );
	$this->assertInstanceOf( StreamedResponse::class, $response );
	$this->assertSame( 'attachment; filename=hello.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testDownloadNonAsciiFilename() {
	$this->filesystem->write( 'file.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem );
	$response = $files->download( 'file.txt', 'пиздюк.txt' );
	$this->assertInstanceOf( StreamedResponse::class, $response );
	$this->assertSame( "attachment; filename=pizdyuk.txt; filename*=utf-8''%D0%BF%D0%B8%D0%B7%D0%B4%D1%8E%D0%BA.txt", $response->headers->get( 'content-disposition' ) );
	}

	public function testDownloadNonAsciiEmptyFilename() {
	$this->filesystem->write( 'пиздюк.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem );
	$response = $files->download( 'пиздюк.txt' );
	$this->assertInstanceOf( StreamedResponse::class, $response );
	$this->assertSame( 'attachment; filename=pizdyuk.txt; filename*=utf-8\'\'%D0%BF%D0%B8%D0%B7%D0%B4%D1%8E%D0%BA.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testDownloadPercentInFilename() {
	$this->filesystem->write( 'Hello%World.txt', 'Hello World' );
	$files    = new Filesystem_Adapter( $this->filesystem );
	$response = $files->download( 'Hello%World.txt', 'Hello%World.txt' );
	$this->assertInstanceOf( StreamedResponse::class, $response );
	$this->assertSame( 'attachment; filename=HelloWorld.txt; filename*=utf-8\'\'Hello%25World.txt', $response->headers->get( 'content-disposition' ) );
	}

	public function testExists() {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$this->assertTrue( $Filesystem_Adapter->exists( 'file.txt' ) );
	}

	public function testMissing() {
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$this->assertTrue( $Filesystem_Adapter->missing( 'file.txt' ) );
	}

	public function testPath() {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$this->assertEquals( $this->temp_dir . DIRECTORY_SEPARATOR . 'file.txt', $Filesystem_Adapter->path( 'file.txt' ) );
	}

	public function testGet() {
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$this->assertSame( 'Hello World', $Filesystem_Adapter->get( 'file.txt' ) );
	}

	public function testGetFileNotFound() {
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$this->expectException( FileNotFoundException::class );
		$Filesystem_Adapter->get( 'file.txt' );
	}

	public function testPut() {
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$Filesystem_Adapter->put( 'file.txt', 'Something inside' );
		$this->assertStringEqualsFile( $this->temp_dir . '/file.txt', 'Something inside' );
	}

	public function testPrepend() {
		file_put_contents( $this->temp_dir . '/file.txt', 'World' );
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$Filesystem_Adapter->prepend( 'file.txt', 'Hello ' );
		$this->assertStringEqualsFile( $this->temp_dir . '/file.txt', 'Hello ' . PHP_EOL . 'World' );
	}

	public function testAppend() {
		file_put_contents( $this->temp_dir . '/file.txt', 'Hello ' );
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$Filesystem_Adapter->append( 'file.txt', 'Moon' );
		$this->assertStringEqualsFile( $this->temp_dir . '/file.txt', 'Hello ' . PHP_EOL . 'Moon' );
	}

	public function testDelete() {
		file_put_contents( $this->temp_dir . '/file.txt', 'Hello World' );
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$this->assertTrue( $Filesystem_Adapter->delete( 'file.txt' ) );
		Assert::assertFileDoesNotExist( $this->temp_dir . '/file.txt' );
	}

	public function testDeleteReturnsFalseWhenFileNotFound() {
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$this->assertFalse( $Filesystem_Adapter->delete( 'file.txt' ) );
	}

	public function testCopy() {
		$data = '33232';
		mkdir( $this->temp_dir . '/foo' );
		file_put_contents( $this->temp_dir . '/foo/foo.txt', $data );

		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$Filesystem_Adapter->copy( '/foo/foo.txt', '/foo/foo2.txt' );

		$this->assertFileExists( $this->temp_dir . '/foo/foo.txt' );
		$this->assertEquals( $data, file_get_contents( $this->temp_dir . '/foo/foo.txt' ) );

		$this->assertFileExists( $this->temp_dir . '/foo/foo2.txt' );
		$this->assertEquals( $data, file_get_contents( $this->temp_dir . '/foo/foo2.txt' ) );
	}

	public function testMove() {
		$data = '33232';
		mkdir( $this->temp_dir . '/foo' );
		file_put_contents( $this->temp_dir . '/foo/foo.txt', $data );

		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$Filesystem_Adapter->move( '/foo/foo.txt', '/foo/foo2.txt' );

		Assert::assertFileDoesNotExist( $this->temp_dir . '/foo/foo.txt' );

		$this->assertFileExists( $this->temp_dir . '/foo/foo2.txt' );
		$this->assertEquals( $data, file_get_contents( $this->temp_dir . '/foo/foo2.txt' ) );
	}

	public function testStream() {
		$this->filesystem->write( 'file.txt', $original_content = 'Hello World' );
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$readStream         = $Filesystem_Adapter->readStream( 'file.txt' );
		$Filesystem_Adapter->writeStream( 'copy.txt', $readStream );
		$this->assertEquals( $original_content, $Filesystem_Adapter->get( 'copy.txt' ) );
	}

	public function testStreamBetweenFilesystems() {
		$secondFilesystem = new Flysystem( new Local( $this->temp_dir . '/second' ) );
		$this->filesystem->write( 'file.txt', $original_content = 'Hello World' );
		$Filesystem_Adapter       = new Filesystem_Adapter( $this->filesystem );
		$secondFilesystem_Adapter = new Filesystem_Adapter( $secondFilesystem );
		$readStream               = $Filesystem_Adapter->readStream( 'file.txt' );
		$secondFilesystem_Adapter->writeStream( 'copy.txt', $readStream );
		$this->assertEquals( $original_content, $secondFilesystem_Adapter->get( 'copy.txt' ) );
	}

	public function testStreamToExistingFileThrows() {
		$this->expectException( FileExistsException::class );
		$this->filesystem->write( 'file.txt', 'Hello World' );
		$this->filesystem->write( 'existing.txt', 'Dear Kate' );
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$readStream         = $Filesystem_Adapter->readStream( 'file.txt' );
		$Filesystem_Adapter->writeStream( 'existing.txt', $readStream );
	}

	public function testReadStreamNonExistentFileThrows() {
		$this->expectException( FileNotFoundException::class );
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$Filesystem_Adapter->readStream( 'nonexistent.txt' );
	}

	public function testStreamInvalidResourceThrows() {
		$this->expectException( InvalidArgumentException::class );
		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );
		$Filesystem_Adapter->writeStream( 'file.txt', 'foo bar' );
	}

	public function testPutWithStreamInterface() {
		file_put_contents( $this->temp_dir . '/foo.txt', 'some-data' );
		$spy = m::spy( $this->filesystem );

		$Filesystem_Adapter = new Filesystem_Adapter( $spy );
		$stream             = fopen( $this->temp_dir . '/foo.txt', 'r' );
		$guzzleStream       = new Stream( $stream );
		$Filesystem_Adapter->put( 'bar.txt', $guzzleStream );
		fclose( $stream );

		$spy->shouldHaveReceived( 'putStream' );
		$this->assertSame( 'some-data', $Filesystem_Adapter->get( 'bar.txt' ) );
	}

	public function testPutFileAs() {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'uploaded file content' );

		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );

		$uploadedFile = new Uploaded_File( $filePath, 'org.txt', null, null, true );

		$storagePath = $Filesystem_Adapter->put_file_as( '/', $uploadedFile, 'new.txt' );

		$this->assertSame( 'new.txt', $storagePath );

		$this->assertFileExists( $filePath );

		$Filesystem_Adapter->assertExists( $storagePath );

		$this->assertSame( 'uploaded file content', $Filesystem_Adapter->read( $storagePath ) );
	}

	public function testPutFileAsWithAbsoluteFilePath() {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'normal file content' );

		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );

		$storagePath = $Filesystem_Adapter->put_file_as( '/', $filePath, 'new.txt' );

		$this->assertSame( 'normal file content', $Filesystem_Adapter->read( $storagePath ) );
	}

	public function testPutFile() {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'uploaded file content' );

		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );

		$uploadedFile = new Uploaded_File( $filePath, 'org.txt', null, null, true );

		$storagePath = $Filesystem_Adapter->put_file( '/', $uploadedFile );

		$this->assertSame( 44, strlen( $storagePath ) ); // random 40 characters + ".txt"

		$this->assertFileExists( $filePath );

		$Filesystem_Adapter->assertExists( $storagePath );
	}

	public function testPutFileWithAbsoluteFilePath() {
		file_put_contents( $filePath = $this->temp_dir . '/foo.txt', 'uploaded file content' );

		$Filesystem_Adapter = new Filesystem_Adapter( $this->filesystem );

		$storagePath = $Filesystem_Adapter->put_file( '/', $filePath );

		$this->assertSame( 44, strlen( $storagePath ) ); // random 40 characters + ".txt"

		$Filesystem_Adapter->assertExists( $storagePath );
	}

	public function test_url() {
		$this->assertEquals(
			home_url( '/wp-content/uploads/foo.txt' ),
			( new Filesystem_Adapter( $this->filesystem ) )->url( '/foo.txt' )
		);
	}
}
