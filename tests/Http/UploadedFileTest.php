<?php

namespace Mantle\Tests\Http;

use Mantle\Database\Model\Attachment;
use Mantle\Http\Uploaded_File;
use Mantle\Testing\Framework_Test_Case;

class UploadedFileTest extends Framework_Test_Case {

	public function testUploadedFileCanRetrieveContentsFromTextFile() {
		$file = new Uploaded_File(
			MANTLE_PHPUNIT_INCLUDES_PATH . '/fixtures/test.txt',
			'test.txt',
			null,
			null,
			true
		);

		$this->assertSame( 'This is a story about something that happened long ago when your grandfather was a child.', trim( $file->get() ) );
	}

	public function test_store_uploaded_file_as_attachment() {
		$file = new Uploaded_File(
			MANTLE_PHPUNIT_INCLUDES_PATH . '/fixtures/test.txt',
			'test.txt',
			null,
			null,
			true
		);

		$attachment = $file->store_as_attachment( '/', 'test-uploaded-file.txt' );

		$this->assertInstanceOf( Attachment::class, $attachment );
		$this->assertStringContainsString( '/wp-content/uploads/test-uploaded-file.txt', $attachment->url() );
		$this->assertTrue( file_exists( WP_CONTENT_DIR . '/uploads/test-uploaded-file.txt' ) );
	}
}
