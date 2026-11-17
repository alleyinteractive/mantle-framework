<?php
namespace Mantle\Tests\Support\InteractsWithDataTest;

use Mantle\Support\Option;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Option test case.
 */
#[Group('interactsWithData')]
class OptionTest extends FrameworkTestCase {
	public function test_as_string(): void {
		update_option( 'test_option', 'test' );

		$this->assertSame( 'test', Option::of( 'test_option' )->string() );
	}

	public function test_json_serializable(): void {
		update_option( 'test_option', [ 'test' ] );

		$this->assertSame( json_encode( [ 'test' ] ), Option::of( 'test_option' )->to_json() );
	}

	public function test_update(): void {
		update_option( 'test_option', 'test' );

		$option = Option::of( 'test_option' );

		$this->assertSame( 'test', $option->string() );

		$option->set( 'new-test' );
		$option->save();

		$this->assertSame( 'new-test', $option->string() );
		$this->assertSame( 'new-test', get_option( 'test_option' ) );
	}

	/**
	 * @dataProvider save_autoload_provider
	 *
	 * @param bool|null $existing Autoload of the existing option. Null if the option does not exist yet.
	 * @param bool|null $autoload Autoload argument passed to save().
	 * @param bool      $expected Whether the option is autoloaded after saving.
	 */
	#[DataProvider( 'save_autoload_provider' )]
	public function test_save_autoload( ?bool $existing, ?bool $autoload, bool $expected ): void {
		if ( null !== $existing ) {
			update_option( 'test_option', 'test', $existing );
		}

		$option = new Option( 'test_option', 'new-test' );
		$option->save( $autoload );

		$this->assertSame( 'new-test', get_option( 'test_option' ) );
		$this->assertSame( $expected, $this->is_autoloaded( 'test_option' ) );
	}

	/**
	 * @return array<string, array{bool|null, bool|null, bool}>
	 */
	public static function save_autoload_provider(): array {
		return [
			'new option, default' => [ null, null, true ],
			'new option, autoload' => [ null, true, true ],
			'new option, no autoload' => [ null, false, false ],
			'autoloaded option, default keeps autoload' => [ true, null, true ],
			'autoloaded option, no autoload' => [ true, false, false ],
			'non-autoloaded option, default keeps no autoload' => [ false, null, false ],
			'non-autoloaded option, autoload' => [ false, true, true ],
		];
	}

	/**
	 * Check if an option is autoloaded in the database.
	 *
	 * WordPress 6.6+ stores 'on'/'off'/'auto-*'; older versions use 'yes'/'no'.
	 */
	protected function is_autoloaded( string $option ): bool {
		global $wpdb;

		$value = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", $option ) );

		return in_array( $value, [ 'yes', 'on', 'auto-on', 'auto' ], true );
	}

	public function test_arrayaccess_get(): void {
		update_option( 'test_option', [ 'test', 'test2' ] );

		$option = Option::of( 'test_option' );

		$this->assertSame( 'test', $option[0] );
		$this->assertSame( 'test2', $option[1] );
	}

	public function test_arrayaccess_set(): void {
		update_option( 'test_option', [ 'test', 'test2' ] );

		$option = Option::of( 'test_option' );

		$this->assertSame( [ 'test', 'test2' ], $option->array() );

		$option[0] = 'new-test';

		$option->save();

		$this->assertSame( [ 'new-test', 'test2' ], $option->array() );
		$this->assertSame( [ 'new-test', 'test2' ], get_option( 'test_option' ) );
	}

	public function test_arrayaccess_unset(): void {
		update_option( 'test_option', [ 'test', 'test2' ] );

		$option = Option::of( 'test_option' );

		$this->assertSame( [ 'test', 'test2' ], $option->array() );

		unset( $option[0] );

		$option->save();

		$this->assertSame( [ 1 => 'test2' ], $option->array() );
		$this->assertSame( [ 1 => 'test2' ], get_option( 'test_option' ) );
	}

	public function test_arrayaccess_isset(): void {
		update_option( 'test_option', [ 'test', 'test2' ] );

		$option = Option::of( 'test_option' );

		$this->assertTrue( isset( $option[0] ) );
		$this->assertFalse( isset( $option[2] ) );
	}

	public function test_array_has(): void {
		update_option( 'test_option', [
			'key' => 'test',
			'key2' => 'test2',
		] );

		$option = Option::of( 'test_option' );

		$this->assertTrue( $option->has( 'key' ) );
		$this->assertTrue( $option->has( 'key', 'key2' ) );
		$this->assertFalse( $option->has( 'key3' ) );
		$this->assertFalse( $option->has( 'key2', 'key3' ) );
	}

	public function test_array_has_any(): void {
		update_option( 'test_option', [
			'key' => 'test',
			'key2' => 'test2',
		] );

		$option = Option::of( 'test_option' );

		$this->assertTrue( $option->has_any( 'key' ) );
		$this->assertTrue( $option->has_any( 'key', 'key3' ) );
		$this->assertFalse( $option->has_any( 'key3', 'key4' ) );
		$this->assertFalse( $option->has_any( 'key3', 'key4', 'key5' ) );
	}
}
