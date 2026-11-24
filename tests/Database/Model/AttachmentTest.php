<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Attachment;
use Mantle\Testing\FrameworkTestCase;

class AttachmentTest extends FrameworkTestCase {
	public function test_attachment_image_urls(): void {
		$attachment_id = static::factory()->attachment->with_image( DIR_TESTDATA . '/images/test-image.png', 0 )->create();

		$this->assertIsInt( $attachment_id );

		$attachment = Attachment::find_or_fail( $attachment_id );

		$this->assertNotEmpty( $attachment->id() );
		$this->assertNotEmpty( $attachment->url() );
		$this->assertNotEmpty( $attachment->image_url( 'thumbnail' ) );
	}

	public function test_webp_attachment(): void {
		$attachment_id = static::factory()->attachment->with_image( DIR_TESTDATA . '/images/test-image.webp' )->create();

		$this->assertIsInt( $attachment_id );
		$this->assertSame( 'image/webp', get_post_mime_type( $attachment_id ) );
		$this->assertStringEndsWith( '.webp', (string) wp_get_attachment_url( $attachment_id ) );

		$metadata = wp_get_attachment_metadata( $attachment_id );

		$this->assertIsArray( $metadata );
		$this->assertSame( 50, $metadata['width'] );
		$this->assertSame( 50, $metadata['height'] );
	}
}
