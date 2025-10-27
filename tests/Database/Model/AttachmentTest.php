<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Attachment;
use Mantle\Testing\FrameworkTestCase;

class AttachmentTest extends FrameworkTestCase {
	public function test_attachment_image_urls() {
		$attachment_id = $this->factory->attachment->with_image( DIR_TESTDATA . '/images/test-image.png', 0 )->create();

		$attachment = Attachment::find( $attachment_id );

		$this->assertNotEmpty( $attachment->id() );
		$this->assertNotEmpty( $attachment->url() );
		$this->assertNotEmpty( $attachment->image_url( 'thumbnail' ) );
	}
}
