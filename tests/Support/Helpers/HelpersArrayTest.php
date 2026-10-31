<?php

use PHPUnit\Framework\TestCase;
use function Mantle\Support\Helpers\data_fill;
use function Mantle\Support\Helpers\data_get;
use function Mantle\Support\Helpers\data_set;
use function Mantle\Support\Helpers\value;

class HelpersArrayTest extends TestCase {
	public function testValue(): void {
		$this->assertSame( 'foo', value( 'foo' ) );
		$this->assertSame( 'foo', value( function () {
			return 'foo';
		} ) );
	}

	public function testDataGet(): void {
		$object      = (object) [ 'users' => [ 'name' => [ 'Taylor', 'Otwell' ] ] ];
		$array       = [ (object) [ 'users' => [ (object) [ 'name' => 'Taylor' ] ] ] ];
		$dottedArray = [
			'users' => [
				'first.name'  => 'Taylor',
				'middle.name' => null,
			],
		];
		$arrayAccess = new SupportTestArrayAccess( [
			'price' => 56,
			'user'  => new SupportTestArrayAccess( [ 'name' => 'John' ] ),
			'email' => null,
		] );

		$this->assertSame( 'Taylor', data_get( $object, 'users.name.0' ) );
		$this->assertSame( 'Taylor', data_get( $array, '0.users.0.name' ) );
		$this->assertNull( data_get( $array, '0.users.3' ) );
		$this->assertSame( 'Not found', data_get( $array, '0.users.3', 'Not found' ) );
		$this->assertSame( 'Not found', data_get( $array, '0.users.3', function () {
			return 'Not found';
		} ) );
		$this->assertSame( 'Taylor', data_get( $dottedArray, [
			'users',
			'first.name',
		] ) );
		$this->assertNull( data_get( $dottedArray, [ 'users', 'middle.name' ] ) );
		$this->assertSame( 'Not found', data_get( $dottedArray, [
			'users',
			'last.name',
		], 'Not found' ) );
		$this->assertSame( 56, data_get( $arrayAccess, 'price' ) );
		$this->assertSame( 'John', data_get( $arrayAccess, 'user.name' ) );
		$this->assertSame( 'void', data_get( $arrayAccess, 'foo', 'void' ) );
		$this->assertSame( 'void', data_get( $arrayAccess, 'user.foo', 'void' ) );
		$this->assertNull( data_get( $arrayAccess, 'foo' ) );
		$this->assertNull( data_get( $arrayAccess, 'user.foo' ) );
		$this->assertNull( data_get( $arrayAccess, 'email', 'Not found' ) );
	}

	public function testDataGetWithNestedArrays(): void {
		$array = [
			[ 'name' => 'taylor', 'email' => 'taylorotwell@gmail.com' ],
			[ 'name' => 'abigail' ],
			[ 'name' => 'dayle' ],
		];

		$this->assertSame(
			[ 'taylor', 'abigail', 'dayle' ],
			data_get( $array, '*.name' )
		);
		$this->assertSame(
			[ 'taylorotwell@gmail.com', null, null ],
			data_get( $array, '*.email', 'irrelevant' )
		);

		$array = [
			'users' => [
				[
					'first' => 'taylor',
					'last'  => 'otwell',
					'email' => 'taylorotwell@gmail.com',
				],
				[ 'first' => 'abigail', 'last' => 'otwell' ],
				[ 'first' => 'dayle', 'last' => 'rees' ],
			],
			'posts' => null,
		];

		$this->assertSame( [
			'taylor',
			'abigail',
			'dayle',
		], data_get( $array, 'users.*.first' ) );
		$this->assertSame(
			[ 'taylorotwell@gmail.com', null, null ],
			data_get( $array, 'users.*.email', 'irrelevant' )
		);
		$this->assertSame( 'not found', data_get( $array, 'posts.*.date', 'not found' ) );
		$this->assertNull( data_get( $array, 'posts.*.date' ) );
	}

	public function testDataGetWithDoubleNestedArraysCollapsesResult(): void {
		$array = [
			'posts' => [
				[
					'comments' => [
						[ 'author' => 'taylor', 'likes' => 4 ],
						[ 'author' => 'abigail', 'likes' => 3 ],
					],
				],
				[
					'comments' => [
						[ 'author' => 'abigail', 'likes' => 2 ],
						[ 'author' => 'dayle' ],
					],
				],
				[
					'comments' => [
						[ 'author' => 'dayle' ],
						[ 'author' => 'taylor', 'likes' => 1 ],
					],
				],
			],
		];

		$this->assertSame(
			[ 'taylor', 'abigail', 'abigail', 'dayle', 'dayle', 'taylor' ],
			data_get( $array, 'posts.*.comments.*.author' )
		);
		$this->assertSame(
			[ 4, 3, 2, null, null, 1 ],
			data_get( $array, 'posts.*.comments.*.likes' )
		);
		$this->assertSame( [], data_get( $array, 'posts.*.users.*.name', 'irrelevant' ) );
		$this->assertSame( [], data_get( $array, 'posts.*.users.*.name' ) );
	}

	public function testDataFill(): void {
		$data = [ 'foo' => 'bar' ];

		$this->assertSame( [
			'foo' => 'bar',
			'baz' => 'boom',
		], data_fill( $data, 'baz', 'boom' ) );
		$this->assertSame( [
			'foo' => 'bar',
			'baz' => 'boom',
		], data_fill( $data, 'baz', 'noop' ) );
		$this->assertSame( [
			'foo' => [],
			'baz' => 'boom',
		], data_fill( $data, 'foo.*', 'noop' ) );
		$this->assertSame(
			[ 'foo' => [ 'bar' => 'kaboom' ], 'baz' => 'boom' ],
			data_fill( $data, 'foo.bar', 'kaboom' )
		);
	}

	public function testDataFillWithStar(): void {
		$data = [ 'foo' => 'bar' ];

		$this->assertSame(
			[ 'foo' => [] ],
			data_fill( $data, 'foo.*.bar', 'noop' )
		);

		$this->assertSame(
			[ 'foo' => [], 'bar' => [ [ 'baz' => 'original' ], [] ] ],
			data_fill( $data, 'bar', [ [ 'baz' => 'original' ], [] ] )
		);

		$this->assertSame(
			[
				'foo' => [],
				'bar' => [ [ 'baz' => 'original' ], [ 'baz' => 'boom' ] ],
			],
			data_fill( $data, 'bar.*.baz', 'boom' )
		);

		$this->assertSame(
			[
				'foo' => [],
				'bar' => [ [ 'baz' => 'original' ], [ 'baz' => 'boom' ] ],
			],
			data_fill( $data, 'bar.*', 'noop' )
		);
	}

	public function testDataFillWithDoubleStar(): void {
		$data = [
			'posts' => [
				(object) [
					'comments' => [
						(object) [ 'name' => 'First' ],
						(object) [],
					],
				],
				(object) [
					'comments' => [
						(object) [],
						(object) [ 'name' => 'Second' ],
					],
				],
			],
		];

		data_fill( $data, 'posts.*.comments.*.name', 'Filled' );

		$this->assertEquals( [
			'posts' => [
				(object) [
					'comments' => [
						(object) [ 'name' => 'First' ],
						(object) [ 'name' => 'Filled' ],
					],
				],
				(object) [
					'comments' => [
						(object) [ 'name' => 'Filled' ],
						(object) [ 'name' => 'Second' ],
					],
				],
			],
		], $data );
	}

	public function testDataSet(): void {
		$data = [ 'foo' => 'bar' ];

		$this->assertSame(
			[ 'foo' => 'bar', 'baz' => 'boom' ],
			data_set( $data, 'baz', 'boom' )
		);

		$this->assertSame(
			[ 'foo' => 'bar', 'baz' => 'kaboom' ],
			data_set( $data, 'baz', 'kaboom' )
		);

		$this->assertSame(
			[ 'foo' => [], 'baz' => 'kaboom' ],
			data_set( $data, 'foo.*', 'noop' )
		);

		$this->assertSame(
			[ 'foo' => [ 'bar' => 'boom' ], 'baz' => 'kaboom' ],
			data_set( $data, 'foo.bar', 'boom' )
		);

		$this->assertSame(
			[ 'foo' => [ 'bar' => 'boom' ], 'baz' => [ 'bar' => 'boom' ] ],
			data_set( $data, 'baz.bar', 'boom' )
		);

		$this->assertSame(
			[
				'foo' => [ 'bar' => 'boom' ],
				'baz' => [ 'bar' => [ 'boom' => [ 'kaboom' => 'boom' ] ] ],
			],
			data_set( $data, 'baz.bar.boom.kaboom', 'boom' )
		);
	}

	public function testDataSetWithStar(): void {
		$data = [ 'foo' => 'bar' ];

		$this->assertSame(
			[ 'foo' => [] ],
			data_set( $data, 'foo.*.bar', 'noop' )
		);

		$this->assertSame(
			[ 'foo' => [], 'bar' => [ [ 'baz' => 'original' ], [] ] ],
			data_set( $data, 'bar', [ [ 'baz' => 'original' ], [] ] )
		);

		$this->assertSame(
			[ 'foo' => [], 'bar' => [ [ 'baz' => 'boom' ], [ 'baz' => 'boom' ] ] ],
			data_set( $data, 'bar.*.baz', 'boom' )
		);

		$this->assertSame(
			[ 'foo' => [], 'bar' => [ 'overwritten', 'overwritten' ] ],
			data_set( $data, 'bar.*', 'overwritten' )
		);
	}

	public function testDataSetWithDoubleStar(): void {
		$data = [
			'posts' => [
				(object) [
					'comments' => [
						(object) [ 'name' => 'First' ],
						(object) [],
					],
				],
				(object) [
					'comments' => [
						(object) [],
						(object) [ 'name' => 'Second' ],
					],
				],
			],
		];

		data_set( $data, 'posts.*.comments.*.name', 'Filled' );

		$this->assertEquals( [
			'posts' => [
				(object) [
					'comments' => [
						(object) [ 'name' => 'Filled' ],
						(object) [ 'name' => 'Filled' ],
					],
				],
				(object) [
					'comments' => [
						(object) [ 'name' => 'Filled' ],
						(object) [ 'name' => 'Filled' ],
					],
				],
			],
		], $data );
	}
}

class SupportTestArrayAccess implements ArrayAccess
{
	/**
	 * @var array<mixed>
	 */
	protected array $attributes = [];

	/**
	 * @param array<mixed> $attributes Attributes.
	 */
	public function __construct(array $attributes = [])
	{
		$this->attributes = $attributes;
	}

	public function offsetExists(mixed $offset): bool
	{
		return array_key_exists($offset, $this->attributes);
	}

	public function offsetGet(mixed $offset): mixed
	{
		return $this->attributes[$offset];
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->attributes[$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->attributes[$offset]);
	}
}
