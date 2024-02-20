<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Mail\Mail_Message;

/**
 * @group testing
 */
class InteractsWithMailTest extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		wp_mail(
			'test@example.com',
			'Test Subject',
			'Test Body',
			[
				'From: Test <noreply@example.com>',
			],
		);
	}

	public function testMailSentSuccessfully() {
		$this->assertMailSent();
		$this->assertMailSent( 'test@example.com' );
		$this->assertMailSent(
			fn ( Mail_Message $message ) => $message->subject === 'Test Subject'
		);
	}

	public function testMailNotSent() {
		$this->assertMailNotSent( 'other@example.org' );
		$this->assertMailNotSent(
			fn ( Mail_Message $message ) => $message->subject === 'Test Subject' && $message->body === 'Non the body',
		);
	}
}
