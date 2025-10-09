<?php

namespace Mantle\Tests\Support\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Mantle\Support\Helpers\normalize_cache_ttl;
use function Mantle\Support\Helpers\now;

class HelpersCacheTest extends TestCase {
	#[DataProvider( 'normalize_cache_ttl_dataprovider' )]
	public function test_normalize_cache_ttl( mixed $expected, mixed $value ): void {
		$this->assertSame( $expected, normalize_cache_ttl( $value ) );
	}

	public static function normalize_cache_ttl_dataprovider(): array {
		return [
			'null'                => [ 0, null ],
			'seconds'             => [ 3600, 3600 ],
			'unix timestamp'      => [ 3600, now()->addHour() ],
			'date interval'       => [ 3600, new \DateInterval( 'PT1H' ) ],
			'negative timestamp'  => [ 0, now()->subHour() ],
			'zero'                => [ 0, 0 ],
			'negative integer'    => [ 0, -100 ],
		];
	}
}
