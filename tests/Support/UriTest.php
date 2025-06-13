<?php

namespace Mantle\Tests\Support;

use Mantle\Support\Uri;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\TestCase;

class UriTest extends FrameworkTestCase {

	public function test_basic_uri_interactions() {
		$uri = Uri::of( $originalUri = 'https://laravel.com/docs/installation' );

		$this->assertEquals( 'https', $uri->scheme() );
		$this->assertNull( $uri->user() );
		$this->assertNull( $uri->password() );
		$this->assertEquals( 'laravel.com', $uri->host() );
		$this->assertNull( $uri->port() );
		$this->assertEquals( 'docs/installation', $uri->path() );
		$this->assertEquals( [], $uri->query()->to_array() );
		$this->assertEquals( '', (string) $uri->query() );
		$this->assertEquals( '', $uri->query()->decode() );
		$this->assertNull( $uri->fragment() );
		$this->assertEquals( $originalUri, (string) $uri );

		$uri = Uri::of( 'https://taylor:password@laravel.com/docs/installation?version=1#hello' );

		$this->assertEquals( 'taylor', $uri->user() );
		$this->assertEquals( 'password', $uri->password() );
		$this->assertEquals( 'hello', $uri->fragment() );
		$this->assertEquals( [ 'version' => 1 ], $uri->query()->all() );
		$this->assertEquals( 1, $uri->query()->integer( 'version' ) );
	}

	public function test_from_current_request(): void {
		$this->get( $post = static::factory()->post->create_and_get() )
			->assertOk()
			->assertQueryTrue( 'is_single', 'is_singular' );

		$uri = Uri::current()->with_query( [ 'test' => 'value' ]);

		$this->assertEquals( get_permalink( $post ) . '?test=value', $uri->value() );
	}

	public function test_complicated_query_string_parsing() {
		$uri = Uri::of( 'https://example.com/users?key_1=value&key_2[sub_field]=value&key_3[]=value&key_4[9]=value&key_5[][][foo][9]=bar&key.6=value&flag_value' );

		$this->assertEquals( [
			'key_1'      => 'value',
			'key_2'      => [
				'sub_field' => 'value',
			],
			'key_3'      => [
				'value',
			],
			'key_4'      => [
				9 => 'value',
			],
			'key_5'      => [
				[
					[
						'foo' => [
							9 => 'bar',
						],
					],
				],
			],
			'key.6'      => 'value',
			'flag_value' => '',
		], $uri->query()->all() );

		$this->assertEquals( 'key_1=value&key_2[sub_field]=value&key_3[]=value&key_4[9]=value&key_5[][][foo][9]=bar&key.6=value&flag_value', $uri->query()->decode() );
	}

	public function test_uri_building() {
		$uri = Uri::of();

		$uri = $uri->with_host( 'laravel.com' )
			->with_scheme( 'https' )
			->with_user( 'taylor', 'password' )
			->with_path( '/docs/installation' )
			->with_port( 80 )
			->with_query( [ 'version' => 1 ] )
			->with_fragment( 'hello' );

		$this->assertEquals( 'https://taylor:password@laravel.com:80/docs/installation?version=1#hello', (string) $uri );
	}

	public function test_complicated_query_string_manipulation() {
		$uri = Uri::of( 'https://laravel.com' );

		$uri = $uri->with_query( [
			'name' => 'Taylor',
			'age'  => 38,
			'role' => [
				'title' => 'Developer',
				'focus' => 'PHP',
			],
			'tags' => [
				'person',
				'employee',
			],
			'flag' => '',
		] )->with_query( [ 'name' ] );

		$this->assertEquals( 'age=38&role[title]=Developer&role[focus]=PHP&tags[0]=person&tags[1]=employee&flag=', $uri->query()->decode() );
		$this->assertEquals( 'name=Taylor', $uri->replace_query( [ 'name' => 'Taylor' ] )->query()->decode() );

		// Push onto multi-value and missing items...
		$uri = Uri::of( 'https://laravel.com?tags[]=foo' );

		$this->assertEquals( [ 'tags' => [ 'foo', 'bar' ] ], $uri->push_onto_query( 'tags', 'bar' )->query()->all() );
		$this->assertEquals( [ 'tags' => [ 'foo', 'bar', 'baz' ] ], $uri->push_onto_query( 'tags', [ 'bar', 'baz' ] )->query()->all() );
		$this->assertEquals( [
			'tags'  => [ 'foo' ],
			'names' => [ 'Taylor' ],
		], $uri->push_onto_query( 'names', 'Taylor' )->query()->all() );

		// Push onto single value item...
		$uri = Uri::of( 'https://laravel.com?tag=foo' );

		$this->assertEquals( [ 'tag' => [ 'foo', 'bar' ] ], $uri->push_onto_query( 'tag', 'bar' )->query()->all() );
	}

	public function test_query_strings_with_dots_can_be_replaced_or_merged_consistently() {
		$uri = Uri::of( 'https://dot.test/?foo.bar=baz' );

		$this->assertEquals( 'foo.bar=baz&foo[bar]=zab', $uri->with_query( [ 'foo.bar' => 'zab' ] )->query()->decode() );
		$this->assertEquals( 'foo[bar]=zab', $uri->replace_query( [ 'foo.bar' => 'zab' ] )->query()->decode() );
	}

	public function test_decoding_the_entire_uri() {
		$uri = Uri::of( 'https://laravel.com/docs/11.x/installation' )->with_query( [ 'tags' => [ 'first', 'second' ] ] );

		$this->assertEquals( 'https://laravel.com/docs/11.x/installation?tags[0]=first&tags[1]=second', $uri->decode() );
	}

	public function test_with_query_if_missing() {
		// Test adding new parameters while preserving existing ones
		$uri = Uri::of( 'https://laravel.com?existing=value' );

		$uri = $uri->with_query_if_missing( [
			'new'      => 'parameter',
			'existing' => 'new_value',
		] );

		$this->assertEquals( 'existing=value&new=parameter', $uri->query()->decode() );

		// Test adding complex nested arrays to empty query string
		$uri = Uri::of( 'https://laravel.com' );

		$uri = $uri->with_query_if_missing( [
			'name' => 'Taylor',
			'role' => [
				'title' => 'Developer',
				'focus' => 'PHP',
			],
			'tags' => [
				'person',
				'employee',
			],
		] );

		$this->assertEquals( 'name=Taylor&role[title]=Developer&role[focus]=PHP&tags[0]=person&tags[1]=employee', $uri->query()->decode() );

		// Test partial array merging and preserving indexed arrays
		$uri = Uri::of( 'https://laravel.com?name=Taylor&tags[0]=person' );

		$uri = $uri->with_query_if_missing( [
			'name' => 'Changed',
			'age'  => 38,
			'tags' => [ 'should', 'not', 'change' ],
		] );

		$this->assertEquals( 'name=Taylor&tags[0]=person&age=38', $uri->query()->decode() );
		$this->assertEquals( [
			'name' => 'Taylor',
			'tags' => [ 'person' ],
			'age'  => 38,
		], $uri->query()->all() );

		$uri = Uri::of( 'https://laravel.com?user[name]=Taylor' );

		$uri = $uri->with_query_if_missing( [
			'user'     => [
				'name' => 'Should Not Change',
				'age'  => 38,
			],
			'settings' => [
				'theme' => 'dark',
			],
		] );
		$this->assertEquals( [
			'user'     => [
				'name' => 'Taylor',
			],
			'settings' => [
				'theme' => 'dark',
			],
		], $uri->query()->all() );
	}

	public function test_with_query_prevents_empty_query_string() {
		$uri = Uri::of( 'https://laravel.com' );

		$this->assertEquals( 'https://laravel.com', (string) $uri );
		$this->assertEquals( 'https://laravel.com', (string) $uri->with_query( [] ) );
	}

	public function test_path_segments() {
		$uri = Uri::of( 'https://laravel.com' );

		$this->assertEquals( [], $uri->path_segments()->to_array() );

		$uri = Uri::of( 'https://laravel.com/one/two/three' );

		$this->assertEquals( [ 'one', 'two', 'three' ], $uri->path_segments()->to_array() );
		$this->assertEquals( 'one', $uri->path_segments()->first() );

		$uri = Uri::of( 'https://laravel.com/one/two/three?foo=bar' );

		$this->assertEquals( 3, $uri->path_segments()->count() );

		$uri = Uri::of( 'https://laravel.com/one/two/three/?foo=bar' );

		$this->assertEquals( 3, $uri->path_segments()->count() );

		$uri = Uri::of( 'https://laravel.com/one/two/three/#foo=bar' );

		$this->assertEquals( 3, $uri->path_segments()->count() );
	}

	public function test_macroable() {
		Uri::macro( 'myMacro', function () {
			return $this->with_path( 'foobar' );
		} );

		$uri = new Uri( 'https://laravel.com/' );

		$this->assertSame( 'https://laravel.com/foobar', (string) $uri->myMacro() );
	}
}
