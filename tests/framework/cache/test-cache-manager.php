<?php
namespace Mantle\Tests\Framework\Cache;

use InvalidArgumentException;
use Mantle\Framework\Cache\Cache_Manager;
use Mantle\Framework\Testing\Framework_Test_Case;

class Test_Cache_Manager extends Framework_Test_Case {
	public function test_invalid_store() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Driver not specified for [invalid-store}.' );

		$this->get_manager()->store( 'invalid-store' );
	}

	protected function get_manager(): Cache_Manager {
		return new Cache_Manager( $this->app );
	}
}
