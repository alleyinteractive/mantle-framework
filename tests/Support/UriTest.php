<?php

namespace Mantle\Tests\Support;

use Mantle\Support\Uri;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\TestCase;

class UriTest extends FrameworkTestCase {
	public function test_basic_uri(): void {
		$uri = Uri::of( 'https://example.com/path?query=string#fragment' );

		$this->assertInstanceOf( Uri::class, $uri );
		$this->assertEquals( 'https://example.com/path?query=string#fragment', (string) $uri->get_uri() );
		$this->assertEquals( 'https', $uri->get_uri()->getScheme() );
		$this->assertEquals( 'example.com', $uri->get_uri()->getHost() );
		$this->assertEquals( '/path', $uri->get_uri()->getPath() );
		$this->assertEquals( 'query=string', $uri->get_uri()->getQuery() );
		$this->assertEquals( 'fragment', $uri->get_uri()->getFragment() );
		$this->assertEquals( 'example.com', $uri->host() );

		// Uri_Query_String:
		$this->assertEquals( 'string', $uri->query()->get( 'query' ) );
		$this->assertEquals( [ 'query' => 'string' ], $uri->query()->all() );
		$this->assertTrue( $uri->query()->has( 'query' ) );
		$this->assertFalse( $uri->query()->has( 'nonexistent' ) );
		$this->assertTrue( $uri->query()->missing( 'nonexistent' ) );
	}

	public function test_from_current(): void {
		$this->get( $post = static::factory()->post->create_and_get() )
			->assertOk()
			->assertQueryTrue( 'is_single', 'is_singular' );

		$this->assertEquals(
			get_permalink( $post ),
			(string) Uri::current(),
		);

		$uri = Uri::current()->with_query( [ 'test' => 'value' ]);

		$this->assertEquals( get_permalink( $post ) . '?test=value', $uri->value() );
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

	public function test_complex_query_string(): void {
		$uri = Uri::of( 'https://example.com/users?key_1=value&key_2[sub_field]=value&key_3[]=value&key_4[9]=value&key_5[][][foo][9]=bar&key.6=value&flag_value' );

		$this->assertEquals( [
			'key_1' => 'value',
			'key_2' => [
				'sub_field' => 'value',
			],
			'key_3' => [
				'value',
			],
			'key_4' => [
				9 => 'value',
			],
			'key_5' => [
				[
					[
						'foo' => [
							9 => 'bar',
						],
					],
				],
			],
			'key.6' => 'value',
			'flag_value' => '',
		], $uri->query()->all() );

		$this->assertEquals(
			'key_1=value&key_2[sub_field]=value&key_3[]=value&key_4[9]=value&key_5[][][foo][9]=bar&key.6=value&flag_value',
			$uri->query()->decode(),
		);
	}

	public function test_manipulation(): void {
		$uri = Uri::of( 'https://example.com/path?query=string#fragment' );

		// Add a query parameter.
		$uri = $uri->with_query( [ 'new_param' => 'new_value' ] );
		$this->assertEquals( 'https://example.com/path?query=string&new_param=new_value#fragment', (string) $uri->get_uri() );

		// Remove a query parameter.
		$uri = $uri->without_query( 'query' );
		$this->assertEquals( 'https://example.com/path?new_param=new_value#fragment', (string) $uri->get_uri() );

		// Change the fragment.
		$uri = $uri->with_fragment( 'new_fragment' );
		$this->assertEquals( 'https://example.com/path?new_param=new_value#new_fragment', (string) $uri->get_uri() );

		// Change the path.
		$uri = $uri->with_path( '/new-path' );
		$this->assertEquals( 'https://example.com/new-path?new_param=new_value#new_fragment', (string) $uri->get_uri() );

		// Change the host.
		$uri = $uri->with_host( 'new-example.com' );
		$this->assertEquals( 'https://new-example.com/new-path?new_param=new_value#new_fragment', (string) $uri->get_uri() );

		// Change the port.
		$uri = $uri->with_port( 8080 );
		$this->assertEquals( 'https://new-example.com:8080/new-path?new_param=new_value#new_fragment', (string) $uri->get_uri() );

		// Change the scheme.
		$uri = $uri->with_scheme( 'http' );
		$this->assertEquals( 'http://new-example.com:8080/new-path?new_param=new_value#new_fragment', (string) $uri->get_uri() );

		// Change the username and password.
		$uri = $uri->with_user( 'user', 'pass' );
		$this->assertEquals( 'http://user:pass@new-example.com:8080/new-path?new_param=new_value#new_fragment', (string) $uri->get_uri() );

		// Test with_query_if_missing
		$uri = Uri::of( 'https://example.com/path?query=string#fragment' );
		$uri = $uri->with_query_if_missing( [ 'new_param' => 'new_value' ] );
		$this->assertEquals( 'https://example.com/path?query=string&new_param=new_value#fragment', (string) $uri->get_uri() );

		// Test with_query_if_missing when the parameter already exists
		$uri = $uri->with_query_if_missing( [ 'query' => 'replaced' ] );
		$this->assertEquals( 'https://example.com/path?query=string&new_param=new_value#fragment', (string) $uri->get_uri() );

		// Test removing the query.
		$uri = $uri->without_query();
		$this->assertEquals( 'https://example.com/path#fragment', (string) $uri->get_uri() );
	}

	public function test_url_with_dot_query_string_parameter(): void {
		$uri = Uri::of( 'https://dot.test/?foo.bar=baz' );

		$this->assertEquals(
			'foo.bar=baz&foo[bar]=zab',
			$uri->with_query( [ 'foo.bar' => 'zab' ] )->query()->decode(),
		);

		$this->assertEquals(
			'foo[bar]=zab',
			$uri->replace_query( [ 'foo.bar' => 'zab' ] )->query()->decode(),
		);
	}
}
