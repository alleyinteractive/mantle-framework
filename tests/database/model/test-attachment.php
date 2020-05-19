<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Framework\Database\Model\Attachment;
use WP_UnitTestCase;

/**
 * @todo Replace with the Mantle Testing Framework
 */
class Test_Attachment extends WP_UnitTestCase {
	/**
	 * Disabled until this can be converted to be unit-testable.
	 */
	public function test_create_from_url() {
		$attachment = $this->get_attachment();

		$this->assertNotEmpty( $attachment->id() );

		// Test calling it again to ensure the ID matches (should only download one attachment).
		$attachment_2 = $this->get_attachment();
		$this->assertEquals( $attachment->id(), $attachment_2->id() );

		$this->assertNotEmpty( $attachment->image_url( 'thumbnail' ) );
	}

	public function test_attachment_url() {
		$attachment = Attachment::find( static::factory()->attachment->create() );
		$this->assertNotEmpty( $attachment->url() );
	}

	/**
	 * @return Attachment
	 */
	protected function get_attachment() {
		return Attachment::create_from_url(
			'https://placehold.it/100x100.jpg',
			[
				'caption'     => 'Caption',
				'description' => 'Description',
			]
		);
	}
}
