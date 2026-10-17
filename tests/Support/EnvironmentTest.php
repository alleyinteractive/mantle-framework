<?php

namespace Mantle\Tests\Support;

use Mantle\Support\Environment;
use Mantle\Testing\FrameworkTestCase;

class EnvironmentTest extends FrameworkTestCase {
	protected function setUp(): void {
		parent::setUp();
		Environment::clear();
	}

	public function test_read_env_variables() {
		$_ENV['VARIABLE_TO_CHECK'] = 'value-to-compare';

		$this->assertSame( 'value-to-compare', Environment::get( 'VARIABLE_TO_CHECK' ) );
		$this->assertSame( 'fallback', Environment::get( 'UNKNOWN_VARIABLE', 'fallback' ) );
		$this->assertSame( 'closure-fallback', Environment::get( 'ANOTHER_UNKNOWN_VARIABLE', function() { return 'closure-fallback'; } ) );
	}

	public function test_defined_constant() {
		define( 'TEST_CONSTANT', 'test-var' );

		$this->assertSame( Environment::get( 'TEST_CONSTANT' ), 'test-var' );
	}

	public function test_vip_env_var() {
		define( 'VIP_ENV_VAR_TEST_VIP_VAR', 'vip-var-value' );

		$this->assertSame( Environment::get( 'TEST_VIP_VAR' ), 'vip-var-value' );
	}
}
