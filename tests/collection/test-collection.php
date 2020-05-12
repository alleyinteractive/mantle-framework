<?php

namespace Mantle\Tests\Container;

use Mantle\Framework\Collection\Collection;
use PHPUnit\Framework\TestCase;

class Test_Collection extends TestCase {

	/**
	 * @dataProvider collectionClassProvider
	 */
	public function testAll($collection) {
		$c = new $collection([1,2,3]);
		$this->assertSame([1,2,3], $c->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 */
	public function testFirstReturnsFirstItemInCollection($collection) {
		$c = new $collection(['foo', 'bar']);
		$this->assertSame('foo', $c->first());
	}

	/**
	 * @dataProvider collectionClassProvider
	 */
	public function testFirstWithCallback($collection) {
		$data = new $collection(['foo', 'bar', 'baz']);
		$result = $data->first(function ($value) {
			return $value === 'bar';
		});
		$this->assertSame('bar', $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 */
	public function testFirstWithCallbackAndDefault($collection) {
		$data = new $collection(['foo', 'bar']);
		$result = $data->first(function ($value) {
			return $value === 'baz';
		}, 'default');
		$this->assertSame('default', $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 */
	public function testFirstWithDefaultAndWithoutCallback($collection) {
		$data = new $collection;
		$result = $data->first(null, 'default');
		$this->assertSame('default', $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 */
	public function testLastReturnsLastItemInCollection($collection)
	{
		$c = new $collection(['foo', 'bar']);
		$this->assertSame('bar', $c->last());
	}

	/**
	 * @dataProvider collectionClassProvider
	 */
	public function testLastWithCallback($collection)
	{
		$data = new $collection([100, 200, 300]);
		$result = $data->last(function ($value) {
			return $value < 250;
		});
		$this->assertEquals(200, $result);
		$result = $data->last(function ($value, $key) {
			return $key < 2;
		});
		$this->assertEquals(200, $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 */
	public function testLastWithCallbackAndDefault($collection)
	{
		$data = new $collection(['foo', 'bar']);
		$result = $data->last(function ($value) {
			return $value === 'baz';
		}, 'default');
		$this->assertSame('default', $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 */
	public function testLastWithDefaultAndWithoutCallback($collection)
	{
		$data = new $collection;
		$result = $data->last(null, 'default');
		$this->assertSame('default', $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 */
	public function testMap($collection)
	{
		$data = new $collection(['first' => 'taylor', 'last' => 'otwell']);
		$data = $data->map(function ($item, $key) {
			return $key.'-'.strrev($item);
		});
		$this->assertEquals(['first' => 'first-rolyat', 'last' => 'last-llewto'], $data->all());
	}
	/**
	 * Provides each collection class, respectively.
	 *
	 * @return array
	 */
	public function collectionClassProvider() {
		return [
			[Collection::class],
			//[LazyCollection::class],
		];
	}
}
