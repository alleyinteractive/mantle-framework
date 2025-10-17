<?php
declare(strict_types=1);

namespace Mantle\Tests\Support\Helpers;

use Mantle\Testing\FrameworkTestCase;

use function Mantle\Support\Helpers\mixed_query_var;

class HelpersMixedTest extends FrameworkTestCase {
	public function test_mixed_query_var(): void {
		$this->assertEquals( '', mixed_query_var( 'non_existent_key' ) );

		set_query_var( 'test_key_string', 'test_value' );

		$this->assertEquals( 'test_value', mixed_query_var( 'test_key_string' )->string() );
		$this->assertEquals( [ 'test_value' ], mixed_query_var( 'test_key_string' )->array() );

		$this->assertEquals( 'default_value', mixed_query_var( 'non_existent_key', 'default_value' )->string() );
	}
}
