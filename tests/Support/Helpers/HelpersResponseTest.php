<?php

namespace Mantle\Tests\Support\Helpers;

use Mantle\Testing\FrameworkTestCase;

use function Mantle\Support\Helpers\send_json_response;

class HelpersResponseTest extends FrameworkTestCase {
	public function test_send_json(): void {
		add_action( 'template_redirect', fn () => send_json_response( [
			'success' => true,
			'data'    => [ 'foo' => 'bar' ],
		], 201 ) );

		$this->get( '/' )
			->assertStatus( 201 )
			->assertIsJson()
			->assertJsonPath( 'success', true )
			->assertJsonPath( 'data.foo', 'bar' );
	}
}
