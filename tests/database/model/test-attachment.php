<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Attachment;
use Mantle\Testing\Framework_Test_Case;

class Test_Attachment extends Framework_Test_Case {
	/**
	 * @var int
	 */
	protected $attachment_id;

	protected function setUp(): void {
		parent::setUp();
		$this->attachment_id = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.png', 0 );
	}

	protected function tearDown(): void {
		parent::tearDown();
		wp_delete_post( $this->attachment_id );
	}

	public function test_attachment_image_urls() {
		$attachment = Attachment::find( $this->attachment_id );

		$this->assertNotEmpty( $attachment->id() );
		$this->assertNotEmpty( $attachment->url() );
		$this->assertNotEmpty( $attachment->image_url( 'thumbnail' ) );
	}
}
