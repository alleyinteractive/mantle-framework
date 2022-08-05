<?php
namespace Mantle\Tests\Testing;

use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Utils;

class Test_Permalink_Structure extends Framework_Test_Case {
  public function test_default_permalink_structure() {
		$this->assertEquals(
			Utils::DEFAULT_PERMALINK_STRUCTURE,
			get_option( 'permalink_structure' ),
		);

    $post = static::factory()->post->create_and_get( [
			'post_date' => '2018-01-01 00:00:00',
			'post_name' => 'test-post',
		] );

		$permalink = get_permalink( $post );

		$this->assertEquals( home_url( '/2018/01/01/test-post/' ), $permalink );

		// Ensure the permalink can be reached by the testing framework.
		$this->get( $permalink )
			->assertOk()
			->assertQueriedObject( $post )
			->assertQueryTrue( 'is_single', 'is_singular' );
  }
}
