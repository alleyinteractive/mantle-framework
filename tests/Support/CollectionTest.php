<?php

namespace Mantle\Tests\Support;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use CachingIterator;
use Exception;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Contracts\Support\Jsonable;
use Mantle\Support\Collection;
use Mantle\Support\HtmlString;
use Mantle\Database\Model;
use InvalidArgumentException;
use JsonSerializable;
use Mantle\Support\Stringable;
use Mantle\Testing\FrameworkTestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\with;

class CollectionTest extends FrameworkTestCase {
	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFirstReturnsFirstItemInCollection(string $collection ): void
	{
		$c = new $collection(['foo', 'bar']);
		$this->assertSame('foo', $c->first());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFirstWithCallback(string $collection ): void
	{
		$data = new $collection(['foo', 'bar', 'baz']);
		$result = $data->first(static function ($value) {
			return $value === 'bar';
		});
		$this->assertSame('bar', $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFirstWithCallbackAndDefault(string $collection ): void
	{
		$data = new $collection(['foo', 'bar']);
		$result = $data->first(static function ($value) {
			return $value === 'baz';
		}, 'default');
		$this->assertSame('default', $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFirstWithDefaultAndWithoutCallback(string $collection ): void
	{
		$data = new $collection;
		$result = $data->first(null, 'default');
		$this->assertSame('default', $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFirstWhere(string $collection ): void
	{
		$data = new $collection([
			['material' => 'paper', 'type' => 'book'],
			['material' => 'rubber', 'type' => 'gasket'],
		]);

		$this->assertSame('book', $data->first_where('material', 'paper')['type']);
		$this->assertSame('gasket', $data->first_where('material', 'rubber')['type']);
		$this->assertNull($data->first_where('material', 'nonexistent'));
		$this->assertNull($data->first_where('nonexistent', 'key'));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testLastReturnsLastItemInCollection(string $collection ): void
	{
		$c = new $collection(['foo', 'bar']);
		$this->assertSame('bar', $c->last());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testLastWithCallback(string $collection ): void
	{
		$data = new $collection([100, 200, 300]);
		$result = $data->last(static function ($value) {
			return $value < 250;
		});
		$this->assertSame(200, $result);
		$result = $data->last(static function ($value, $key) {
			return $key < 2;
		});
		$this->assertSame(200, $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testLastWithCallbackAndDefault(string $collection ): void
	{
		$data = new $collection(['foo', 'bar']);
		$result = $data->last(static function ($value) {
			return $value === 'baz';
		}, 'default');
		$this->assertSame('default', $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testLastWithDefaultAndWithoutCallback(string $collection ): void
	{
		$data = new $collection;
		$result = $data->last(null, 'default');
		$this->assertSame('default', $result);
	}

	public function testPopReturnsAndRemovesLastItemInCollection(): void
	{
		$c = new Collection(['foo', 'bar']);

		$this->assertSame('bar', $c->pop());
		$this->assertSame('foo', $c->first());
	}

	public function testShiftReturnsAndRemovesFirstItemInCollection(): void
	{
		$data = new Collection(['Taylor', 'Otwell']);

		$this->assertSame('Taylor', $data->shift());
		$this->assertSame('Otwell', $data->first());
		$this->assertSame('Otwell', $data->shift());
		$this->assertNull($data->first());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testEmptyCollectionIsEmpty(string $collection ): void
	{
		$c = new $collection;

		$this->assertTrue($c->is_empty());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testEmptyCollectionIsNotEmpty(string $collection ): void
	{
		$c = new $collection(['foo', 'bar']);

		$this->assertFalse($c->is_empty());
		$this->assertTrue($c->is_not_empty());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCollectionIsConstructed(string $collection ): void
	{
		$data = new $collection('foo');
		$this->assertSame(['foo'], $data->all());

		$data = new $collection(2);
		$this->assertSame([2], $data->all());

		$data = new $collection(false);
		$this->assertSame([false], $data->all());

		$data = new $collection(null);
		$this->assertEmpty($data->all());

		$data = new $collection;
		$this->assertEmpty($data->all());
	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testCollectionShuffleWithSeed($collection)
//	{
//		$data = new $collection(range(0, 100, 10));
//
//		$firstRandom = $data->shuffle(1234);
//		$secondRandom = $data->shuffle(1234);
//
//		$this->assertSame($firstRandom, $secondRandom);
//	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSkipMethod(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 5, 6]);

		$data = $data->skip(4)->values();

		$this->assertSame([5, 6], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSkipUntil(string $collection ): void
	{
		$data = new $collection([1, 1, 2, 2, 3, 3, 4, 4]);

		$data = $data->skip_until(3)->values();

		$this->assertSame([3, 3, 4, 4], $data->all());

		$data = $data->skip_until(static function ($value, $key) {
			return $value > 3;
		})->values();

		$this->assertSame([4, 4], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSkipWhile(string $collection ): void
	{
		$data = new $collection([1, 1, 2, 2, 3, 3, 4, 4]);

		$data = $data->skip_while(1)->values();

		$this->assertSame([2, 2, 3, 3, 4, 4], $data->all());

		$data = $data->skip_while(static function ($value, $key) {
			return $value < 3;
		})->values();

		$this->assertSame([3, 3, 4, 4], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGetArrayableItems(string $collection ): void
	{
		$data = new $collection;

		$items = new TestArrayableObject;
		$array = invade($data)->get_arrayable_items($items);
		$this->assertSame(['foo' => 'bar'], $array);

		$items = new TestJsonableObject;
		$array = invade($data)->get_arrayable_items($items);
		$this->assertSame(['foo' => 'bar'], $array);

		$items = new TestJsonSerializeObject;
		$array = invade($data)->get_arrayable_items($items);
		$this->assertSame(['foo' => 'bar'], $array);

		$items = new TestJsonSerializeWithScalarValueObject;
		$array = invade($data)->get_arrayable_items($items);
		$this->assertSame(['foo'], $array);

		$items = new $collection(['foo' => 'bar']);
		$array = invade($data)->get_arrayable_items($items);
		$this->assertSame(['foo' => 'bar'], $array);

		$items = ['foo' => 'bar'];
		$array = invade($data)->get_arrayable_items($items);
		$this->assertSame(['foo' => 'bar'], $array);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testToArrayCallsToArrayOnEachItemInCollection(string $collection ): void
	{
		$item1 = m::mock(Arrayable::class);
		$item1->shouldReceive('to_array')->once()->andReturn('foo.array');
		$item2 = m::mock(Arrayable::class);
		$item2->shouldReceive('to_array')->once()->andReturn('bar.array');
		$c = new $collection([$item1, $item2]);
		$results = $c->to_array();

		$this->assertSame(['foo.array', 'bar.array'], $results);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testJsonSerializeCallsToArrayOrJsonSerializeOnEachItemInCollection(string $collection ): void
	{
		$item1 = m::mock(JsonSerializable::class);
		$item1->shouldReceive('jsonSerialize')->once()->andReturn('foo.json');
		$item2 = m::mock(Arrayable::class);
		$item2->shouldReceive('to_array')->once()->andReturn('bar.array');
		$c = new $collection([$item1, $item2]);
		$results = $c->jsonSerialize();

		$this->assertSame(['foo.json', 'bar.array'], $results);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testToJsonEncodesTheJsonSerializeResult(string $collection ): void
	{
		$c = $this->getMockBuilder($collection)->onlyMethods(['jsonSerialize'])->getMock();
		$c->expects($this->once())->method('jsonSerialize')->willReturn('foo');
		$results = $c->to_json();
		$this->assertJsonStringEqualsJsonString('"foo"', $results);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCastingToStringJsonEncodesTheToArrayResult(string $collection ): void
	{
		$c = $this->getMockBuilder($collection)->onlyMethods(['jsonSerialize'])->getMock();
		$c->expects($this->once())->method('jsonSerialize')->willReturn('foo');

		$this->assertJsonStringEqualsJsonString('"foo"', (string) $c);
	}

	public function testOffsetAccess(): void
	{
		$c = new Collection(['name' => 'taylor']);
		$this->assertSame('taylor', $c['name']);
		$c['name'] = 'dayle';
		$this->assertSame('dayle', $c['name']);
		$this->assertTrue(isset($c['name']));
		unset($c['name']);
		$this->assertFalse(isset($c['name']));
		$c[] = 'jason';
		$this->assertSame('jason', $c[0]);
	}

	public function testArrayAccessOffsetExists(): void
	{
		$c = new Collection(['foo', 'bar']);
		$this->assertTrue($c->offsetExists(0));
		$this->assertTrue($c->offsetExists(1));
		$this->assertFalse($c->offsetExists(1000));
	}

	public function testArrayAccessOffsetGet(): void
	{
		$c = new Collection(['foo', 'bar']);
		$this->assertSame('foo', $c->offsetGet(0));
		$this->assertSame('bar', $c->offsetGet(1));
	}

	public function testArrayAccessOffsetSet(): void
	{
		$c = new Collection(['foo', 'foo']);

		$c->offsetSet(1, 'bar');
		$this->assertSame('bar', $c[1]);

		$c->offsetSet(null, 'qux');
		$this->assertSame('qux', $c[2]);
	}

	public function testArrayAccessOffsetUnset(): void
	{
		$c = new Collection(['foo', 'bar']);

		$c->offsetUnset(1);
		$this->assertFalse(isset($c[1]));
	}

	public function testForgetSingleKey(): void
	{
		$c = new Collection(['foo', 'bar']);
		$c = $c->forget(0)->all();
		$this->assertArrayNotHasKey(0, $c);

		$c = new Collection(['foo' => 'bar', 'baz' => 'qux']);
		$c = $c->forget('foo')->all();
		$this->assertArrayNotHasKey('foo', $c);
	}

	public function testForgetArrayOfKeys(): void
	{
		$c = new Collection(['foo', 'bar', 'baz']);
		$c = $c->forget([0, 2])->all();
		$this->assertFalse(isset($c[0]));
		$this->assertFalse(isset($c[2]));
		$this->assertTrue(isset($c[1]));

		$c = new Collection(['name' => 'taylor', 'foo' => 'bar', 'baz' => 'qux']);
		$c = $c->forget(['foo', 'baz'])->all();
		$this->assertFalse(isset($c['foo']));
		$this->assertFalse(isset($c['baz']));
		$this->assertTrue(isset($c['name']));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCountable(string $collection ): void
	{
		$c = new $collection(['foo', 'bar']);
		$this->assertCount(2, $c);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCountableByWithoutPredicate(string $collection ): void
	{
		$c = new $collection(['foo', 'foo', 'foo', 'bar', 'bar', 'foobar']);
		$this->assertSame(['foo' => 3, 'bar' => 2, 'foobar' => 1], $c->count_by()->all());

		$c = new $collection([true, true, false, false, false]);
		$this->assertSame([true => 2, false => 3], $c->count_by()->all());

		$c = new $collection([1, 5, 1, 5, 5, 1]);
		$this->assertSame([1 => 3, 5 => 3], $c->count_by()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCountableByWithPredicate(string $collection ): void
	{
		$c = new $collection(['alice', 'aaron', 'bob', 'carla']);
		$this->assertSame(['a' => 2, 'b' => 1, 'c' => 1], $c->count_by(static function ($name) {
			return substr($name, 0, 1);
		})->all());

		$c = new $collection([1, 2, 3, 4, 5]);
		$this->assertEquals([true => 2, false => 3], $c->count_by(static function ($i) {
			return $i % 2 === 0;
		})->all());
	}

	public function testIterable(): void
	{
		$c = new Collection(['foo']);
		$iterator = with($c->getIterator());
		$this->assertInstanceOf(ArrayIterator::class, $iterator);
		$this->assertSame(['foo'], $iterator->getArrayCopy());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFilter(string $collection ): void
	{
		$c = new $collection([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);
		$this->assertSame([1 => ['id' => 2, 'name' => 'World']], $c->filter(static function ($item) {
			return $item['id'] == 2;
		})->all());

		$c = new $collection(['', 'Hello', '', 'World']);
		$this->assertSame(['Hello', 'World'], $c->filter()->values()->to_array());

		$c = new $collection(['id' => 1, 'first' => 'Hello', 'second' => 'World']);
		$this->assertSame(['first' => 'Hello', 'second' => 'World'], $c->filter(static function ($item, $key) {
			return $key != 'id';
		})->all());
	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testHigherOrderKeyBy($collection)
//	{
//		$c = new $collection([
//			['id' => 'id1', 'name' => 'first'],
//			['id' => 'id2', 'name' => 'second'],
//		]);
//
//		$this->assertSame(['id1' => 'first', 'id2' => 'second'], $c->keyBy->id->map->name->all());
//	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testHigherOrderUnique($collection)
//	{
//		$c = new $collection([
//			['id' => '1', 'name' => 'first'],
//			['id' => '1', 'name' => 'second'],
//		]);
//
//		$this->assertCount(1, $c->unique->id);
//	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testHigherOrderFilter($collection)
//	{
//		$c = new $collection([
//			new class {
//				public $name = 'Alex';
//
//				public function active()
//				{
//					return true;
//				}
//			},
//			new class {
//				public $name = 'John';
//
//				public function active()
//				{
//					return false;
//				}
//			},
//		]);
//
//		$this->assertCount(1, $c->filter->active());
//	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testWhere($collection)
//	{
//		$c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
//
//		$this->assertSame(
//			[['v' => 3], ['v' => '3']],
//			$c->where('v', 3)->values()->all()
//		);
//		$this->assertSame(
//			[['v' => 3], ['v' => '3']],
//			$c->where('v', '=', 3)->values()->all()
//		);
//		$this->assertSame(
//			[['v' => 3], ['v' => '3']],
//			$c->where('v', '==', 3)->values()->all()
//		);
//		$this->assertSame(
//			[['v' => 3], ['v' => '3']],
//			$c->where('v', 'garbage', 3)->values()->all()
//		);
//		$this->assertSame(
//			[['v' => 3]],
//			$c->where('v', '===', 3)->values()->all()
//		);
//
//		$this->assertSame(
//			[['v' => 1], ['v' => 2], ['v' => 4]],
//			$c->where('v', '<>', 3)->values()->all()
//		);
//		$this->assertSame(
//			[['v' => 1], ['v' => 2], ['v' => 4]],
//			$c->where('v', '!=', 3)->values()->all()
//		);
//		$this->assertSame(
//			[['v' => 1], ['v' => 2], ['v' => '3'], ['v' => 4]],
//			$c->where('v', '!==', 3)->values()->all()
//		);
//		$this->assertSame(
//			[['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3']],
//			$c->where('v', '<=', 3)->values()->all()
//		);
//		$this->assertSame(
//			[['v' => 3], ['v' => '3'], ['v' => 4]],
//			$c->where('v', '>=', 3)->values()->all()
//		);
//		$this->assertSame(
//			[['v' => 1], ['v' => 2]],
//			$c->where('v', '<', 3)->values()->all()
//		);
//		$this->assertSame(
//			[['v' => 4]],
//			$c->where('v', '>', 3)->values()->all()
//		);
//
//		$object = (object) ['foo' => 'bar'];
//
//		$this->assertSame(
//			[],
//			$c->where('v', $object)->values()->all()
//		);
//
//		$this->assertSame(
//			[['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]],
//			$c->where('v', '<>', $object)->values()->all()
//		);
//
//		$this->assertSame(
//			[['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]],
//			$c->where('v', '!=', $object)->values()->all()
//		);
//
//		$this->assertSame(
//			[['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]],
//			$c->where('v', '!==', $object)->values()->all()
//		);
//
//		$this->assertSame(
//			[],
//			$c->where('v', '>', $object)->values()->all()
//		);
//
//		$c = new $collection([['v' => 1], ['v' => $object]]);
//		$this->assertSame(
//			[['v' => $object]],
//			$c->where('v', $object)->values()->all()
//		);
//
//		$this->assertSame(
//			[['v' => 1], ['v' => $object]],
//			$c->where('v', '<>', null)->values()->all()
//		);
//
//		$this->assertSame(
//			[],
//			$c->where('v', '<', null)->values()->all()
//		);
//
//		$c = new $collection([['v' => 1], ['v' => new HtmlString('hello')]]);
//		$this->assertSame(
//			[['v' => new HtmlString('hello')]],
//			$c->where('v', 'hello')->values()->all()
//		);
//
//		$c = new $collection([['v' => 1], ['v' => 'hello']]);
//		$this->assertSame(
//			[['v' => 'hello']],
//			$c->where('v', new HtmlString('hello'))->values()->all()
//		);
//
//		$c = new $collection([['v' => 1], ['v' => 2], ['v' => null]]);
//		$this->assertSame(
//			[['v' => 1], ['v' => 2]],
//			$c->where('v')->values()->all()
//		);
//	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereStrict(string $collection ): void
	{
		$c = new $collection([['v' => 3], ['v' => '3']]);

		$this->assertSame(
			[['v' => 3]],
			$c->where_strict('v', 3)->values()->all()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereInstanceOf(string $collection ): void
	{
		$c = new $collection([new stdClass, new stdClass, new $collection, new stdClass]);
		$this->assertCount(3, $c->where_instance_of(stdClass::class));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereIn(string $collection ): void
	{
		$c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
		$this->assertSame([['v' => 1], ['v' => 3], ['v' => '3']], $c->where_in('v', [1, 3])->values()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereInStrict(string $collection ): void
	{
		$c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
		$this->assertSame([['v' => 1], ['v' => 3]], $c->where_in_strict('v', [1, 3])->values()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereNotIn(string $collection ): void
	{
		$c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
		$this->assertSame([['v' => 2], ['v' => 4]], $c->where_not_in('v', [1, 3])->values()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereNotInStrict(string $collection ): void
	{
		$c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
		$this->assertSame([['v' => 2], ['v' => '3'], ['v' => 4]], $c->where_not_in_strict('v', [1, 3])->values()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testValues(string $collection ): void
	{
		$c = new $collection([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);
		$this->assertSame([['id' => 2, 'name' => 'World']], $c->filter(static function ($item) {
			return $item['id'] == 2;
		})->values()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testBetween(string $collection ): void
	{
		$c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);

		$this->assertSame([['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]],
												$c->where_between('v', [2, 4])->values()->all());
		$this->assertSame([['v' => 1]], $c->where_between('v', [-1, 1])->all());
		$this->assertSame([['v' => 3], ['v' => '3']], $c->where_between('v', [3, 3])->values()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereNotBetween(string $collection ): void
	{
		$c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);

		$this->assertSame([['v' => 1]], $c->where_not_between('v', [2, 4])->values()->all());
		$this->assertSame([['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]], $c->where_not_between('v', [-1, 1])->values()->all());
		$this->assertSame([['v' => 1], ['v' => 2], ['v' => 4]], $c->where_not_between('v', [3, 3])->values()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFlatten(string $collection ): void
	{
		// Flat arrays are unaffected
		$c = new $collection(['#foo', '#bar', '#baz']);
		$this->assertSame(['#foo', '#bar', '#baz'], $c->flatten()->all());

		// Nested arrays are flattened with existing flat items
		$c = new $collection([['#foo', '#bar'], '#baz']);
		$this->assertSame(['#foo', '#bar', '#baz'], $c->flatten()->all());

		// Sets of nested arrays are flattened
		$c = new $collection([['#foo', '#bar'], ['#baz']]);
		$this->assertSame(['#foo', '#bar', '#baz'], $c->flatten()->all());

		// Deeply nested arrays are flattened
		$c = new $collection([['#foo', ['#bar']], ['#baz']]);
		$this->assertSame(['#foo', '#bar', '#baz'], $c->flatten()->all());

		// Nested collections are flattened alongside arrays
		$c = new $collection([new $collection(['#foo', '#bar']), ['#baz']]);
		$this->assertSame(['#foo', '#bar', '#baz'], $c->flatten()->all());

		// Nested collections containing plain arrays are flattened
		$c = new $collection([new $collection(['#foo', ['#bar']]), ['#baz']]);
		$this->assertSame(['#foo', '#bar', '#baz'], $c->flatten()->all());

		// Nested arrays containing collections are flattened
		$c = new $collection([['#foo', new $collection(['#bar'])], ['#baz']]);
		$this->assertSame(['#foo', '#bar', '#baz'], $c->flatten()->all());

		// Nested arrays containing collections containing arrays are flattened
		$c = new $collection([['#foo', new $collection(['#bar', ['#zap']])], ['#baz']]);
		$this->assertSame(['#foo', '#bar', '#zap', '#baz'], $c->flatten()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFlattenWithDepth(string $collection ): void
	{
		// No depth flattens recursively
		$c = new $collection([['#foo', ['#bar', ['#baz']]], '#zap']);
		$this->assertSame(['#foo', '#bar', '#baz', '#zap'], $c->flatten()->all());

		// Specifying a depth only flattens to that depth
		$c = new $collection([['#foo', ['#bar', ['#baz']]], '#zap']);
		$this->assertSame(['#foo', ['#bar', ['#baz']], '#zap'], $c->flatten(1)->all());

		$c = new $collection([['#foo', ['#bar', ['#baz']]], '#zap']);
		$this->assertSame(['#foo', '#bar', ['#baz'], '#zap'], $c->flatten(2)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFlattenIgnoresKeys(string $collection ): void
	{
		// No depth ignores keys
		$c = new $collection(['#foo', ['key' => '#bar'], ['key' => '#baz'], 'key' => '#zap']);
		$this->assertSame(['#foo', '#bar', '#baz', '#zap'], $c->flatten()->all());

		// Depth of 1 ignores keys
		$c = new $collection(['#foo', ['key' => '#bar'], ['key' => '#baz'], 'key' => '#zap']);
		$this->assertSame(['#foo', '#bar', '#baz', '#zap'], $c->flatten(1)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMergeNull(string $collection ): void
	{
		$c = new $collection(['name' => 'Hello']);
		$this->assertSame(['name' => 'Hello'], $c->merge(null)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMergeArray(string $collection ): void
	{
		$c = new $collection(['name' => 'Hello']);
		$this->assertSame(['name' => 'Hello', 'id' => 1], $c->merge(['id' => 1])->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMergeCollection(string $collection ): void
	{
		$c = new $collection(['name' => 'Hello']);
		$this->assertSame(['name' => 'World', 'id' => 1], $c->merge(new $collection(['name' => 'World', 'id' => 1]))->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMergeRecursiveNull(string $collection ): void
	{
		$c = new $collection(['name' => 'Hello']);
		$this->assertSame(['name' => 'Hello'], $c->merge_recursive(null)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMergeRecursiveArray(string $collection ): void
	{
		$c = new $collection(['name' => 'Hello', 'id' => 1]);
		$this->assertSame(['name' => 'Hello', 'id' => [1, 2]], $c->merge_recursive(['id' => 2])->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMergeRecursiveCollection(string $collection ): void
	{
		$c = new $collection(['name' => 'Hello', 'id' => 1, 'meta' => ['tags' => ['a', 'b'], 'roles' => 'admin']]);
		$this->assertSame(
			['name' => 'Hello', 'id' => 1, 'meta' => ['tags' => ['a', 'b', 'c'], 'roles' => ['admin', 'editor']]],
			$c->merge_recursive(new $collection(['meta' => ['tags' => ['c'], 'roles' => 'editor']]))->all()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testReplaceNull(string $collection ): void
	{
		$c = new $collection(['a', 'b', 'c']);
		$this->assertSame(['a', 'b', 'c'], $c->replace(null)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testReplaceArray(string $collection ): void
	{
		$c = new $collection(['a', 'b', 'c']);
		$this->assertSame(['a', 'd', 'e'], $c->replace([1 => 'd', 2 => 'e'])->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testReplaceCollection(string $collection ): void
	{
		$c = new $collection(['a', 'b', 'c']);
		$this->assertSame(
			['a', 'd', 'e'],
			$c->replace(new $collection([1 => 'd', 2 => 'e']))->all()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testReplaceRecursiveNull(string $collection ): void
	{
		$c = new $collection(['a', 'b', ['c', 'd']]);
		$this->assertSame(['a', 'b', ['c', 'd']], $c->replace_recursive(null)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testReplaceRecursiveArray(string $collection ): void
	{
		$c = new $collection(['a', 'b', ['c', 'd']]);
		$this->assertSame(['z', 'b', ['c', 'e']], $c->replace_recursive(['z', 2 => [1 => 'e']])->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testReplaceRecursiveCollection(string $collection ): void
	{
		$c = new $collection(['a', 'b', ['c', 'd']]);
		$this->assertSame(
			['z', 'b', ['c', 'e']],
			$c->replace_recursive(new $collection(['z', 2 => [1 => 'e']]))->all()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnionNull(string $collection ): void
	{
		$c = new $collection(['name' => 'Hello']);
		$this->assertSame(['name' => 'Hello'], $c->union(null)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnionArray(string $collection ): void
	{
		$c = new $collection(['name' => 'Hello']);
		$this->assertSame(['name' => 'Hello', 'id' => 1], $c->union(['id' => 1])->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnionCollection(string $collection ): void
	{
		$c = new $collection(['name' => 'Hello']);
		$this->assertSame(['name' => 'Hello', 'id' => 1], $c->union(new $collection(['name' => 'World', 'id' => 1]))->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDiffCollection(string $collection ): void
	{
		$c = new $collection(['id' => 1, 'first_word' => 'Hello']);
		$this->assertSame(['id' => 1], $c->diff(new $collection(['first_word' => 'Hello', 'last_word' => 'World']))->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDiffUsingWithCollection(string $collection ): void
	{
		$c = new $collection(['en_GB', 'fr', 'HR']);
		// demonstrate that diffKeys wont support case insensitivity
		$this->assertSame(['en_GB', 'fr', 'HR'], $c->diff(new $collection(['en_gb', 'hr']))->values()->to_array());
		// allow for case insensitive difference
		$this->assertSame(['fr'], $c->diff_using(new $collection(['en_gb', 'hr']), 'strcasecmp')->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDiffUsingWithNull(string $collection ): void
	{
		$c = new $collection(['en_GB', 'fr', 'HR']);
		$this->assertSame(['en_GB', 'fr', 'HR'], $c->diff_using(null, 'strcasecmp')->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDiffNull(string $collection ): void
	{
		$c = new $collection(['id' => 1, 'first_word' => 'Hello']);
		$this->assertSame(['id' => 1, 'first_word' => 'Hello'], $c->diff(null)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDiffKeys(string $collection ): void
	{
		$c1 = new $collection(['id' => 1, 'first_word' => 'Hello']);
		$c2 = new $collection(['id' => 123, 'foo_bar' => 'Hello']);
		$this->assertSame(['first_word' => 'Hello'], $c1->diff_keys($c2)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDiffKeysUsing(string $collection ): void
	{
		$c1 = new $collection(['id' => 1, 'first_word' => 'Hello']);
		$c2 = new $collection(['ID' => 123, 'foo_bar' => 'Hello']);
		// demonstrate that diffKeys wont support case insensitivity
		$this->assertSame(['id'=>1, 'first_word'=> 'Hello'], $c1->diff_keys($c2)->all());
		// allow for case insensitive difference
		$this->assertSame(['first_word' => 'Hello'], $c1->diff_keys_using($c2, 'strcasecmp')->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDiffAssoc(string $collection ): void
	{
		$c1 = new $collection(['id' => 1, 'first_word' => 'Hello', 'not_affected' => 'value']);
		$c2 = new $collection(['id' => 123, 'foo_bar' => 'Hello', 'not_affected' => 'value']);
		$this->assertSame(['id' => 1, 'first_word' => 'Hello'], $c1->diff_assoc($c2)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDiffAssocUsing(string $collection ): void
	{
		$c1 = new $collection(['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red']);
		$c2 = new $collection(['A' => 'green', 'yellow', 'red']);
		// demonstrate that the case of the keys will affect the output when diffAssoc is used
		$this->assertSame(['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red'], $c1->diff_assoc($c2)->all());
		// allow for case insensitive difference
		$this->assertSame(['b' => 'brown', 'c' => 'blue', 'red'], $c1->diff_assoc_using($c2, 'strcasecmp')->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDuplicates(string $collection ): void
	{
		$duplicates = $collection::make([1, 2, 1, 'laravel', null, 'laravel', 'php', null])->duplicates()->all();
		$this->assertSame([2 => 1, 5 => 'laravel', 7 => null], $duplicates);

		// does loose comparison
		$duplicates = $collection::make([2, '2', [], null])->duplicates()->all();
		$this->assertSame([1 => '2', 3 => null], $duplicates);

		// works with mix of primitives
		$duplicates = $collection::make([1, '2', ['laravel'], ['laravel'], null, '2'])->duplicates()->all();
		$this->assertSame([3 => ['laravel'], 5 => '2'], $duplicates);

		// works with mix of objects and primitives **excepts numbers**.
		$expected = new Collection(['laravel']);
		$duplicates = $collection::make([new Collection(['laravel']), $expected, $expected, [], '2', '2'])->duplicates()->all();
		$this->assertSame([1 => $expected, 2 => $expected, 5 => '2'], $duplicates);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDuplicatesWithKey(string $collection ): void
	{
		$items = [['framework' => 'vue'], ['framework' => 'laravel'], ['framework' => 'laravel']];
		$duplicates = $collection::make($items)->duplicates('framework')->all();
		$this->assertSame([2 => 'laravel'], $duplicates);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDuplicatesWithCallback(string $collection ): void
	{
		$items = [['framework' => 'vue'], ['framework' => 'laravel'], ['framework' => 'laravel']];
		$duplicates = $collection::make($items)->duplicates(static function ($item) {
			return $item['framework'];
		})->all();
		$this->assertSame([2 => 'laravel'], $duplicates);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testDuplicatesWithStrict(string $collection ): void
	{
		$duplicates = $collection::make([1, 2, 1, 'laravel', null, 'laravel', 'php', null])->duplicates_strict()->all();
		$this->assertSame([2 => 1, 5 => 'laravel', 7 => null], $duplicates);

		// does strict comparison
		$duplicates = $collection::make([2, '2', [], null])->duplicates_strict()->all();
		$this->assertSame([], $duplicates);

		// works with mix of primitives
		$duplicates = $collection::make([1, '2', ['laravel'], ['laravel'], null, '2'])->duplicates_strict()->all();
		$this->assertSame([3 => ['laravel'], 5 => '2'], $duplicates);

		// works with mix of primitives, objects, and numbers
		$expected = new $collection(['laravel']);
		$duplicates = $collection::make([new $collection(['laravel']), $expected, $expected, [], '2', '2'])->duplicates_strict()->all();
		$this->assertSame([2 => $expected, 5 => '2'], $duplicates);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testEach(string $collection ): void
	{
		$c = new $collection($original = [1, 2, 'foo' => 'bar', 'bam' => 'baz']);

		$result = [];
		$c->each(static function ($item, $key) use (&$result) {
			$result[$key] = $item;
		});
		$this->assertSame($original, $result);

		$result = [];
		$c->each(static function ($item, $key) use (&$result) {
			$result[$key] = $item;
			if (is_string($key)) {
				return false;
			}
		});
		$this->assertSame([1, 2, 'foo' => 'bar'], $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testEachSpread(string $collection ): void
	{
		$c = new $collection([[1, 'a'], [2, 'b']]);

		$result = [];
		$c->each_spread(static function ($number, $character) use (&$result) {
			$result[] = [$number, $character];
		});
		$this->assertSame($c->all(), $result);

		$result = [];
		$c->each_spread(static function ($number, $character) use (&$result) {
			$result[] = [$number, $character];

			return false;
		});
		$this->assertSame([[1, 'a']], $result);

		$result = [];
		$c->each_spread(static function ($number, $character, $key) use (&$result) {
			$result[] = [$number, $character, $key];
		});
		$this->assertSame([[1, 'a', 0], [2, 'b', 1]], $result);

		$c = new $collection([new Collection([1, 'a']), new Collection([2, 'b'])]);
		$result = [];
		$c->each_spread(static function ($number, $character, $key) use (&$result) {
			$result[] = [$number, $character, $key];
		});
		$this->assertSame([[1, 'a', 0], [2, 'b', 1]], $result);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testIntersectNull(string $collection ): void
	{
		$c = new $collection(['id' => 1, 'first_word' => 'Hello']);
		$this->assertSame([], $c->intersect(null)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testIntersectCollection(string $collection ): void
	{
		$c = new $collection(['id' => 1, 'first_word' => 'Hello']);
		$this->assertSame(['first_word' => 'Hello'], $c->intersect(new $collection(['first_world' => 'Hello', 'last_word' => 'World']))->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testIntersectByKeysNull(string $collection ): void
	{
		$c = new $collection(['name' => 'Mateus', 'age' => 18]);
		$this->assertSame([], $c->intersect_by_keys(null)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testIntersectByKeys(string $collection ): void
	{
		$c = new $collection(['name' => 'Mateus', 'age' => 18]);
		$this->assertSame(['name' => 'Mateus'], $c->intersect_by_keys(new $collection(['name' => 'Mateus', 'surname' => 'Guimaraes']))->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnique(string $collection ): void
	{
		$c = new $collection(['Hello', 'World', 'World']);
		$this->assertSame(['Hello', 'World'], $c->unique()->all());

		$c = new $collection([[1, 2], [1, 2], [2, 3], [3, 4], [2, 3]]);
		$this->assertSame([[1, 2], [2, 3], [3, 4]], $c->unique()->values()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUniqueWithCallback(string $collection ): void
	{
		$c = new $collection([
			1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
			2 => ['id' => 2, 'first' => 'Taylor', 'last' => 'Otwell'],
			3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
			4 => ['id' => 4, 'first' => 'Abigail', 'last' => 'Otwell'],
			5 => ['id' => 5, 'first' => 'Taylor', 'last' => 'Swift'],
			6 => ['id' => 6, 'first' => 'Taylor', 'last' => 'Swift'],
		]);

		$this->assertSame([
			1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
			3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
		], $c->unique('first')->all());

		$this->assertSame([
			1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
			3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
			5 => ['id' => 5, 'first' => 'Taylor', 'last' => 'Swift'],
		], $c->unique(static function ($item) {
			return $item['first'].$item['last'];
		})->all());

		$this->assertSame([
			1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
			2 => ['id' => 2, 'first' => 'Taylor', 'last' => 'Otwell'],
		], $c->unique(static function ($item, $key) {
			return $key % 2;
		})->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUniqueStrict(string $collection ): void
	{
		$c = new $collection([
			[
				'id' => '0',
				'name' => 'zero',
			],
			[
				'id' => '00',
				'name' => 'double zero',
			],
			[
				'id' => '0',
				'name' => 'again zero',
			],
		]);

		$this->assertSame([
			[
				'id' => '0',
				'name' => 'zero',
			],
			[
				'id' => '00',
				'name' => 'double zero',
			],
		], $c->unique_strict('id')->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCollapse(string $collection ): void
	{
		$data = new $collection([[$object1 = new stdClass], [$object2 = new stdClass]]);
		$this->assertSame([$object1, $object2], $data->collapse()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCollapseWithNestedCollections(string $collection ): void
	{
		$data = new $collection([new $collection([1, 2, 3]), new $collection([4, 5, 6])]);
		$this->assertSame([1, 2, 3, 4, 5, 6], $data->collapse()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testJoin(string $collection ): void
	{
		$this->assertSame('a, b, c', (new $collection(['a', 'b', 'c']))->join(', '));

		$this->assertSame('a, b and c', (new $collection(['a', 'b', 'c']))->join(', ', ' and '));

		$this->assertSame('a and b', (new $collection(['a', 'b']))->join(', ', ' and '));

		$this->assertSame('a', (new $collection(['a']))->join(', ', ' and '));

		$this->assertSame('', (new $collection([]))->join(', ', ' and '));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCrossJoin(string $collection ): void
	{
		// Cross join with an array
		$this->assertSame(
			[[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']],
			(new $collection([1, 2]))->cross_join(['a', 'b'])->all()
		);

		// Cross join with a collection
		$this->assertSame(
			[[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']],
			(new $collection([1, 2]))->cross_join(new $collection(['a', 'b']))->all()
		);

		// Cross join with 2 collections
		$this->assertSame(
			[
				[1, 'a', 'I'], [1, 'a', 'II'],
				[1, 'b', 'I'], [1, 'b', 'II'],
				[2, 'a', 'I'], [2, 'a', 'II'],
				[2, 'b', 'I'], [2, 'b', 'II'],
			],
			(new $collection([1, 2]))->cross_join(
				new $collection(['a', 'b']),
				new $collection(['I', 'II'])
			)->all()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSort(string $collection ): void
	{
		$data = (new $collection([5, 3, 1, 2, 4]))->sort();
		$this->assertSame([1, 2, 3, 4, 5], $data->values()->all());

		$data = (new $collection([-1, -3, -2, -4, -5, 0, 5, 3, 1, 2, 4]))->sort();
		$this->assertSame([-5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5], $data->values()->all());

		$data = (new $collection(['foo', 'bar-10', 'bar-1']))->sort();
		$this->assertSame(['bar-1', 'bar-10', 'foo'], $data->values()->all());

		$data = (new $collection(['T2', 'T1', 'T10']))->sort();
		$this->assertSame(['T1', 'T10', 'T2'], $data->values()->all());

		$data = (new $collection(['T2', 'T1', 'T10']))->sort(SORT_NATURAL);
		$this->assertSame(['T1', 'T2', 'T10'], $data->values()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSortDesc(string $collection ): void
	{
		$data = (new $collection([5, 3, 1, 2, 4]))->sort_desc();
		$this->assertSame([5, 4, 3, 2, 1], $data->values()->all());

		$data = (new $collection([-1, -3, -2, -4, -5, 0, 5, 3, 1, 2, 4]))->sort_desc();
		$this->assertSame([5, 4, 3, 2, 1, 0, -1, -2, -3, -4, -5], $data->values()->all());

		$data = (new $collection(['bar-1', 'foo', 'bar-10']))->sort_desc();
		$this->assertSame(['foo', 'bar-10', 'bar-1'], $data->values()->all());

		$data = (new $collection(['T2', 'T1', 'T10']))->sort_desc();
		$this->assertSame(['T2', 'T10', 'T1'], $data->values()->all());

		$data = (new $collection(['T2', 'T1', 'T10']))->sort_desc(SORT_NATURAL);
		$this->assertSame(['T10', 'T2', 'T1'], $data->values()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSortWithCallback(string $collection ): void
	{
		$data = (new $collection([5, 3, 1, 2, 4]))->sort(static function ($a, $b) {
			if ($a === $b) {
				return 0;
			}

			return ($a < $b) ? -1 : 1;
		});

		$this->assertSame(range(1, 5), array_values($data->all()));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSortBy(string $collection ): void
	{
		$data = new $collection(['taylor', 'dayle']);
		$data = $data->sort_by(static function ($x) {
			return $x;
		});

		$this->assertSame(['dayle', 'taylor'], array_values($data->all()));

		$data = new $collection(['dayle', 'taylor']);
		$data = $data->sort_by_desc(static function ($x) {
			return $x;
		});

		$this->assertSame(['taylor', 'dayle'], array_values($data->all()));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSortByString(string $collection ): void
	{
		$data = new $collection([['name' => 'taylor'], ['name' => 'dayle']]);
		$data = $data->sort_by('name', SORT_STRING);

		$this->assertSame([['name' => 'dayle'], ['name' => 'taylor']], array_values($data->all()));

		$data = new $collection([['name' => 'taylor'], ['name' => 'dayle']]);
		$data = $data->sort_by('name', SORT_STRING);

		$this->assertSame([['name' => 'dayle'], ['name' => 'taylor']], array_values($data->all()));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSortByAlwaysReturnsAssoc(string $collection ): void
	{
		$data = new $collection(['a' => 'taylor', 'b' => 'dayle']);
		$data = $data->sort_by(static function ($x) {
			return $x;
		});

		$this->assertSame(['b' => 'dayle', 'a' => 'taylor'], $data->all());

		$data = new $collection(['taylor', 'dayle']);
		$data = $data->sort_by(static function ($x) {
			return $x;
		});

		$this->assertSame([1 => 'dayle', 0 => 'taylor'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSortKeys(string $collection ): void
	{
		$data = new $collection(['b' => 'dayle', 'a' => 'taylor']);

		$this->assertSame(['a' => 'taylor', 'b' => 'dayle'], $data->sort_keys()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSortKeysDesc(string $collection ): void
	{
		$data = new $collection(['a' => 'taylor', 'b' => 'dayle']);

		$this->assertSame(['b' => 'dayle', 'a' => 'taylor'], $data->sort_keys_desc()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testReverse(string $collection ): void
	{
		$data = new $collection(['zaeed', 'alan']);
		$reversed = $data->reverse();

		$this->assertSame([1 => 'alan', 0 => 'zaeed'], $reversed->all());

		$data = new $collection(['name' => 'taylor', 'framework' => 'laravel']);
		$reversed = $data->reverse();

		$this->assertSame(['framework' => 'laravel', 'name' => 'taylor'], $reversed->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFlip(string $collection ): void
	{
		$data = new $collection(['name' => 'taylor', 'framework' => 'laravel']);
		$this->assertSame(['taylor' => 'name', 'laravel' => 'framework'], $data->flip()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testChunk(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
		$data = $data->chunk(3);

		$this->assertInstanceOf($collection, $data);
		$first = $data->first();
		$this->assertInstanceOf($collection, $first);
		$this->assertCount(4, $data);
		$this->assertSame([1, 2, 3], $first->to_array());
		$fourth = $data->get(3);
		$this->assertInstanceOf($collection, $fourth);
		$this->assertSame([9 => 10], $fourth->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testChunkWhenGivenZeroAsSize(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

		$this->assertSame(
			[],
			$data->chunk(0)->to_array()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testChunkWhenGivenLessThanZero(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

		$this->assertSame(
			[],
			$data->chunk(-1)->to_array()
		);
	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testEvery($collection)
//	{
//		$c = new $collection([]);
//		$this->assertTrue($c->every('key', 'value'));
//		$this->assertTrue($c->every(function () {
//			return false;
//		}));
//
//		$c = new $collection([['age' => 18], ['age' => 20], ['age' => 20]]);
//		$this->assertFalse($c->every('age', 18));
//		$this->assertTrue($c->every('age', '>=', 18));
//		$this->assertTrue($c->every(function ($item) {
//			return $item['age'] >= 18;
//		}));
//		$this->assertFalse($c->every(function ($item) {
//			return $item['age'] >= 20;
//		}));
//
//		$c = new $collection([null, null]);
//		$this->assertTrue($c->every(function ($item) {
//			return $item === null;
//		}));
//
//		$c = new $collection([['active' => true], ['active' => true]]);
//		$this->assertTrue($c->every('active'));
//		$this->assertTrue($c->every->active);
//		$this->assertFalse($c->concat([['active' => false]])->every->active);
//	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testExcept($collection)
//	{
//		$data = new $collection(['first' => 'Taylor', 'last' => 'Otwell', 'email' => 'taylorotwell@gmail.com']);
//
//		$this->assertSame(['first' => 'Taylor'], $data->except(['last', 'email', 'missing'])->all());
//		$this->assertSame(['first' => 'Taylor'], $data->except('last', 'email', 'missing')->all());
//
//		$this->assertSame(['first' => 'Taylor'], $data->except(collect(['last', 'email', 'missing']))->all());
//		$this->assertSame(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->except(['last'])->all());
//		$this->assertSame(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->except('last')->all());
//	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testExceptSelf(string $collection ): void
	{
		$data = new $collection(['first' => 'Taylor', 'last' => 'Otwell']);
		$this->assertSame(['first' => 'Taylor', 'last' => 'Otwell'], $data->except($data)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPluckWithArrayAndObjectValues(string $collection ): void
	{
		$data = new $collection([(object) ['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);
		$this->assertSame(['taylor' => 'foo', 'dayle' => 'bar'], $data->pluck('email', 'name')->all());
		$this->assertSame(['foo', 'bar'], $data->pluck('email')->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPluckWithArrayAccessValues(string $collection ): void
	{
		$data = new $collection([
			new TestArrayAccessImplementation(['name' => 'taylor', 'email' => 'foo']),
			new TestArrayAccessImplementation(['name' => 'dayle', 'email' => 'bar']),
		]);

		$this->assertSame(['taylor' => 'foo', 'dayle' => 'bar'], $data->pluck('email', 'name')->all());
		$this->assertSame(['foo', 'bar'], $data->pluck('email')->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testHas(string $collection ): void
	{
		$data = new $collection(['id' => 1, 'first' => 'Hello', 'second' => 'World']);
		$this->assertTrue($data->has('first'));
		$this->assertFalse($data->has('third'));
		$this->assertTrue($data->has(['first', 'second']));
		$this->assertFalse($data->has(['third', 'first']));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testImplode(string $collection ): void
	{
		$data = new $collection([['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);
		$this->assertSame('foobar', $data->implode('email'));
		$this->assertSame('foo,bar', $data->implode('email', ','));

		$data = new $collection(['taylor', 'dayle']);
		$this->assertSame('taylordayle', $data->implode(''));
		$this->assertSame('taylor,dayle', $data->implode(','));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testImplodeStringable(string $collection ): void
	{
		$data = new $collection( [
			Stringable::make( 'example' ),
			Stringable::make( 'string' ),
			Stringable::make( 'here' ),
		] );

		$this->assertSame( 'example string here', $data->implode( ' ' ) );
		$this->assertSame( 'example,string,here', $data->implode( ',' ) );

		$this->assertSame(
			'another-example-here',
			Stringable::make( 'another example here' )->explode( ' ' )->implode( '-' ),
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testImplodeStr(string $collection ): void
	{
		$data = new $collection( [
			Stringable::make( 'example' ),
			Stringable::make( 'string' ),
			Stringable::make( 'here' ),
		] );

		$result = $data->implode_str( ' ' );
		$this->assertSame( 'example string here', $result->value() );
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTake(string $collection ): void
	{
		$data = new $collection(['taylor', 'dayle', 'shawn']);
		$data = $data->take(2);
		$this->assertSame(['taylor', 'dayle'], $data->all());
	}

	public function testPut(): void
	{
		$data = new Collection(['name' => 'taylor', 'email' => 'foo']);
		$data = $data->put('name', 'dayle');
		$this->assertSame(['name' => 'dayle', 'email' => 'foo'], $data->all());
	}

	public function testPutWithNoKey(): void
	{
		$data = new Collection(['taylor', 'shawn']);
		$data = $data->put(null, 'dayle');
		$this->assertSame(['taylor', 'shawn', 'dayle'], $data->all());
	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testRandom($collection)
//	{
//		$data = new $collection([1, 2, 3, 4, 5, 6]);
//
//		$random = $data->random();
//		$this->assertIsInt($random);
//		$this->assertContains($random, $data->all());
//
//		$random = $data->random(0);
//		$this->assertInstanceOf($collection, $random);
//		$this->assertCount(0, $random);
//
//		$random = $data->random(1);
//		$this->assertInstanceOf($collection, $random);
//		$this->assertCount(1, $random);
//
//		$random = $data->random(2);
//		$this->assertInstanceOf($collection, $random);
//		$this->assertCount(2, $random);
//
//		$random = $data->random('0');
//		$this->assertInstanceOf($collection, $random);
//		$this->assertCount(0, $random);
//
//		$random = $data->random('1');
//		$this->assertInstanceOf($collection, $random);
//		$this->assertCount(1, $random);
//
//		$random = $data->random('2');
//		$this->assertInstanceOf($collection, $random);
//		$this->assertCount(2, $random);
//	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testRandomOnEmptyCollection(string $collection ): void
	{
		$data = new $collection;

		$random = $data->random(0);
		$this->assertInstanceOf($collection, $random);
		$this->assertCount(0, $random);

		$random = $data->random('0');
		$this->assertInstanceOf($collection, $random);
		$this->assertCount(0, $random);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTakeLast(string $collection ): void
	{
		$data = new $collection(['taylor', 'dayle', 'shawn']);
		$data = $data->take(-2);
		$this->assertSame([1 => 'dayle', 2 => 'shawn'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTakeUntilUsingValue(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4]);

		$data = $data->take_until(3);

		$this->assertSame([1, 2], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTakeUntilUsingCallback(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4]);

		$data = $data->take_until(static function ($item) {
			return $item >= 3;
		});

		$this->assertSame([1, 2], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTakeUntilReturnsAllItemsForUnmetValue(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4]);

		$actual = $data->take_until(99);

		$this->assertSame($data->to_array(), $actual->to_array());

		$actual = $data->take_until(static function ($item) {
			return $item >= 99;
		});

		$this->assertSame($data->to_array(), $actual->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTakeUntilCanBeProxied(string $collection ): void
	{
		$data = new $collection([
			new TestSupportCollectionHigherOrderItem('Adam'),
			new TestSupportCollectionHigherOrderItem('Taylor'),
			new TestSupportCollectionHigherOrderItem('Jason'),
		]);

		$actual = $data->take_until->is('Jason');

		$this->assertCount(2, $actual);
		$this->assertSame('Adam', $actual->get(0)->name);
		$this->assertSame('Taylor', $actual->get(1)->name);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTakeWhileUsingValue(string $collection ): void
	{
		$data = new $collection([1, 1, 2, 2, 3, 3]);

		$data = $data->take_while(1);

		$this->assertSame([1, 1], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTakeWhileUsingCallback(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4]);

		$data = $data->take_while(static function ($item) {
			return $item < 3;
		});

		$this->assertSame([1, 2], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTakeWhileReturnsNoItemsForUnmetValue(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4]);

		$actual = $data->take_while(2);

		$this->assertSame([], $actual->to_array());

		$actual = $data->take_while(static function ($item) {
			return $item == 99;
		});

		$this->assertSame([], $actual->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTakeWhileCanBeProxied(string $collection ): void
	{
		$data = new $collection([
			new TestSupportCollectionHigherOrderItem('Adam'),
			new TestSupportCollectionHigherOrderItem('Adam'),
			new TestSupportCollectionHigherOrderItem('Taylor'),
			new TestSupportCollectionHigherOrderItem('Taylor'),
		]);

		$actual = $data->take_while->is('Adam');

		$this->assertCount(2, $actual);
		$this->assertSame('Adam', $actual->get(0)->name);
		$this->assertSame('Adam', $actual->get(1)->name);
	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testMacroable($collection)
//	{
//		// Foo() macro : unique values starting with A
//		$collection::macro('foo', function () {
//			return $this->filter(function ($item) {
//				return strpos($item, 'a') === 0;
//			})
//									->unique()
//									->values();
//		});
//
//		$c = new $collection(['a', 'a', 'aa', 'aaa', 'bar']);
//
//		$this->assertSame(['a', 'aa', 'aaa'], $c->foo()->all());
//	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testCanAddMethodsToProxy($collection)
//	{
//		$collection::macro('adults', function ($callback) {
//			return $this->filter(function ($item) use ($callback) {
//				return $callback($item) >= 18;
//			});
//		});
//
//		$collection::proxy('adults');
//
//		$c = new $collection([['age' => 3], ['age' => 12], ['age' => 18], ['age' => 56]]);
//
//		$this->assertSame([['age' => 18], ['age' => 56]], $c->adults->age->values()->all());
//	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMakeMethod(string $collection ): void
	{
		$data = $collection::make('foo');
		$this->assertSame(['foo'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMakeMethodFromNull(string $collection ): void
	{
		$data = $collection::make(null);
		$this->assertSame([], $data->all());

		$data = $collection::make();
		$this->assertSame([], $data->all()); // @phpstan-ignore method.alreadyNarrowedType (make() without arguments is inferred as an empty collection, so all() is known to be [])
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMakeMethodFromCollection(string $collection ): void
	{
		$firstCollection = $collection::make(['foo' => 'bar']);
		$secondCollection = $collection::make($firstCollection);
		$this->assertSame(['foo' => 'bar'], $secondCollection->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMakeMethodFromArray(string $collection ): void
	{
		$data = $collection::make(['foo' => 'bar']);
		$this->assertSame(['foo' => 'bar'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWrapWithScalar(string $collection ): void
	{
		$data = $collection::wrap('foo');
		$this->assertSame(['foo'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWrapWithArray(string $collection ): void
	{
		$data = $collection::wrap(['foo']);
		$this->assertSame(['foo'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWrapWithArrayable(string $collection ): void
	{
		$data = $collection::wrap($o = new TestArrayableObject);
		$this->assertSame([$o], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWrapWithJsonable(string $collection ): void
	{
		$data = $collection::wrap($o = new TestJsonableObject);
		$this->assertSame([$o], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWrapWithJsonSerialize(string $collection ): void
	{
		$data = $collection::wrap($o = new TestJsonSerializeObject);
		$this->assertSame([$o], $data->all());
	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testWrapWithCollectionClass($collection)
//	{
//		$data = $collection::wrap($collection::make(['foo']));
//		$this->assertSame(['foo'], $data->all());
//	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testWrapWithCollectionSubclass($collection)
//	{
//		$data = TestCollectionSubclass::wrap($collection::make(['foo']));
//		$this->assertSame(['foo'], $data->all());
//		$this->assertInstanceOf(TestCollectionSubclass::class, $data);
//	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testUnwrapCollection($collection)
//	{
//		$data = new $collection(['foo']);
//		$this->assertSame(['foo'], $collection::unwrap($data));
//	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnwrapCollectionWithArray(string $collection ): void
	{
		$this->assertSame(['foo'], $collection::unwrap(['foo']));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnwrapCollectionWithScalar(string $collection ): void
	{
		$this->assertSame('foo', $collection::unwrap('foo'));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTimesMethod(string $collection ): void
	{
		$two = $collection::times(2, static function ($number) {
			return 'slug-'.$number;
		});

		$zero = $collection::times(0, static function ($number) {
			return 'slug-'.$number;
		});

		$negative = $collection::times(-4, static function ($number) {
			return 'slug-'.$number;
		});

		$range = $collection::times(5);

		$this->assertSame(['slug-1', 'slug-2'], $two->all());
		$this->assertTrue($zero->is_empty());
		$this->assertTrue($negative->is_empty());
		$this->assertSame(range(1, 5), $range->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testConstructMakeFromObject(string $collection ): void
	{
		$object = new stdClass;
		$object->foo = 'bar';
		$data = $collection::make($object);
		$this->assertSame(['foo' => 'bar'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testConstructMethod(string $collection ): void
	{
		$data = new $collection('foo');
		$this->assertSame(['foo'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testConstructMethodFromNull(string $collection ): void
	{
		$data = new $collection(null);
		$this->assertSame([], $data->all());

		$data = new $collection;
		$this->assertSame([], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testConstructMethodFromCollection(string $collection ): void
	{
		$firstCollection = new $collection(['foo' => 'bar']);
		$secondCollection = new $collection($firstCollection);
		$this->assertSame(['foo' => 'bar'], $secondCollection->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testConstructMethodFromArray(string $collection ): void
	{
		$data = new $collection(['foo' => 'bar']);
		$this->assertSame(['foo' => 'bar'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testConstructMethodFromObject(string $collection ): void
	{
		$object = new stdClass;
		$object->foo = 'bar';
		$data = new $collection($object);
		$this->assertSame(['foo' => 'bar'], $data->all());
	}

	public function testSplice(): void
	{
		$data = new Collection(['foo', 'baz']);
		$data->splice(1);
		$this->assertSame(['foo'], $data->all());

		$data = new Collection(['foo', 'baz']);
		$data->splice(1, 0, 'bar');
		$this->assertSame(['foo', 'bar', 'baz'], $data->all());

		$data = new Collection(['foo', 'baz']);
		$data->splice(1, 1);
		$this->assertSame(['foo'], $data->all());

		$data = new Collection(['foo', 'baz']);
		$cut = $data->splice(1, 1, 'bar');
		$this->assertSame(['foo', 'bar'], $data->all());
		$this->assertSame(['baz'], $cut->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGetPluckValueWithAccessors(string $collection ): void
	{
		$model = new TestAccessorEloquentTestStub(['some' => 'foo']);
		$modelTwo = new TestAccessorEloquentTestStub(['some' => 'bar']);
		$data = new $collection([$model, $modelTwo]);

		$this->assertSame(['foo', 'bar'], $data->pluck('some')->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMap(string $collection ): void
	{
		$data = new $collection(['first' => 'taylor', 'last' => 'otwell']);
		$data = $data->map(static function ($item, $key) {
			return $key.'-'.strrev($item);
		});
		$this->assertSame(['first' => 'first-rolyat', 'last' => 'last-llewto'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapSpread(string $collection ): void
	{
		$c = new $collection([[1, 'a'], [2, 'b']]);

		$result = $c->map_spread(static function ($number, $character) {
			return "{$number}-{$character}";
		});
		$this->assertSame(['1-a', '2-b'], $result->all());

		$result = $c->map_spread(static function ($number, $character, $key) {
			return "{$number}-{$character}-{$key}";
		});
		$this->assertSame(['1-a-0', '2-b-1'], $result->all());

		$c = new $collection([new Collection([1, 'a']), new Collection([2, 'b'])]);
		$result = $c->map_spread(static function ($number, $character, $key) {
			return "{$number}-{$character}-{$key}";
		});
		$this->assertSame(['1-a-0', '2-b-1'], $result->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testFlatMap(string $collection ): void
	{
		$data = new $collection([
			['name' => 'taylor', 'hobbies' => ['programming', 'basketball']],
			['name' => 'adam', 'hobbies' => ['music', 'powerlifting']],
		]);
		$data = $data->flat_map(static function ($person) {
			return $person['hobbies'];
		});
		$this->assertSame(['programming', 'basketball', 'music', 'powerlifting'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapToDictionary(string $collection ): void
	{
		$data = new $collection([
			['id' => 1, 'name' => 'A'],
			['id' => 2, 'name' => 'B'],
			['id' => 3, 'name' => 'C'],
			['id' => 4, 'name' => 'B'],
		]);

		$groups = $data->map_to_dictionary(static function ($item, $key) {
			return [$item['name'] => $item['id']];
		});

		$this->assertInstanceOf($collection, $groups);
		$this->assertSame(['A' => [1], 'B' => [2, 4], 'C' => [3]], $groups->to_array());
		$this->assertIsArray($groups->get('A'));
	}

	public function testMapToDictionaryReturnFalsy(): void {
		$groups = ( new Collection([1, 2, 3, 4, 5]) )->map_to_dictionary(static function ($item, $key) {
			if (2 === $item) {
				return null;
			}

			return ['key' => $item];
		});

		$this->assertSame(['key' => [1, 3, 4, 5]], $groups->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapToDictionaryWithNumericKeys(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 2, 1]);

		$groups = $data->map_to_dictionary(static function ($item, $key) {
			return [$item => $key];
		});

		$this->assertSame([1 => [0, 4], 2 => [1, 3], 3 => [2]], $groups->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapToGroups(string $collection ): void
	{
		$data = new $collection([
			['id' => 1, 'name' => 'A'],
			['id' => 2, 'name' => 'B'],
			['id' => 3, 'name' => 'C'],
			['id' => 4, 'name' => 'B'],
		]);

		$groups = $data->map_to_groups(static function ($item, $key) {
			return [$item['name'] => $item['id']];
		});

		$this->assertInstanceOf($collection, $groups);
		$this->assertSame(['A' => [1], 'B' => [2, 4], 'C' => [3]], $groups->to_array());
		$this->assertInstanceOf($collection, $groups->get('A'));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapToGroupsWithNumericKeys(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 2, 1]);

		$groups = $data->map_to_groups(static function ($item, $key) {
			return [$item => $key];
		});

		$this->assertSame([1 => [0, 4], 2 => [1, 3], 3 => [2]], $groups->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapWithKeys(string $collection ): void
	{
		$data = new $collection([
			['name' => 'Blastoise', 'type' => 'Water', 'idx' => 9],
			['name' => 'Charmander', 'type' => 'Fire', 'idx' => 4],
			['name' => 'Dragonair', 'type' => 'Dragon', 'idx' => 148],
		]);
		$data = $data->map_with_keys(static function ($pokemon) {
			return [$pokemon['name'] => $pokemon['type']];
		});
		$this->assertSame(
			['Blastoise' => 'Water', 'Charmander' => 'Fire', 'Dragonair' => 'Dragon'],
			$data->all()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapWithKeysIntegerKeys(string $collection ): void
	{
		$data = new $collection([
			['id' => 1, 'name' => 'A'],
			['id' => 3, 'name' => 'B'],
			['id' => 2, 'name' => 'C'],
		]);
		$data = $data->map_with_keys(static function ($item) {
			return [$item['id'] => $item];
		});
		$this->assertSame(
			[1, 3, 2],
			$data->keys()->all()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapWithKeysMultipleRows(string $collection ): void
	{
		$data = new $collection([
			['id' => 1, 'name' => 'A'],
			['id' => 2, 'name' => 'B'],
			['id' => 3, 'name' => 'C'],
		]);
		$data = $data->map_with_keys(static function ($item) {
			return [$item['id'] => $item['name'], $item['name'] => $item['id']];
		});
		$this->assertSame(
			[
				1 => 'A',
				'A' => 1,
				2 => 'B',
				'B' => 2,
				3 => 'C',
				'C' => 3,
			],
			$data->all()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapWithKeysCallbackKey(string $collection ): void
	{
		$data = new $collection([
			3 => ['id' => 1, 'name' => 'A'],
			5 => ['id' => 3, 'name' => 'B'],
			4 => ['id' => 2, 'name' => 'C'],
		]);
		$data = $data->map_with_keys(static function ($item, $key) {
			return [$key => $item['id']];
		});
		$this->assertSame(
			[3, 5, 4],
			$data->keys()->all()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapInto(string $collection ): void
	{
		$data = new $collection([
			'first', 'second',
		]);

		$data = $data->map_into(TestCollectionMapIntoObject::class);

		$this->assertSame('first', $data->get(0)?->value);
		$this->assertSame('second', $data->get(1)?->value);
	}

	public function testNthWithOffsetBeyondStep(): void {
		$this->assertEquals( [ 6, 9 ], ( new Collection( range( 1, 10 ) ) )->nth( 3, 5 )->all() );
		$this->assertEquals( [ 3, 6, 9 ], ( new Collection( range( 1, 10 ) ) )->nth( 3, 2 )->all() );
	}

	public function testSoleThrowsMultipleItemsFoundException(): void {
		try {
			( new Collection( [ 1, 2 ] ) )->sole();
			$this->fail( 'Expected Multiple_Items_Found_Exception' );
		} catch ( \Mantle\Support\Multiple_Items_Found_Exception $e ) {
			$this->assertSame( 2, $e->get_count() );
		}
	}

	public function testDuplicatesStrictUsesStrictComparison(): void {
		$this->assertSame(
			[ 1 => 1, 8 => null ],
			( new Collection( [ 1, 1, '1', 2, 2.0, 'a', 'A', null, null ] ) )->duplicates_strict()->all()
		);
		$this->assertSame(
			[ 1 => 1, 2 => '1', 4 => 2.0, 8 => null ],
			( new Collection( [ 1, 1, '1', 2, 2.0, 'a', 'A', null, null ] ) )->duplicates()->all()
		);
	}

	public function testWhereLooseNotEqual(): void {
		$c = new Collection( [ [ 'id' => 1 ], [ 'id' => 2 ], [ 'id' => 3 ] ] );

		$this->assertEquals( [ 1, 3 ], $c->where( 'id', '!=', '2' )->pluck( 'id' )->values()->all() );
		$this->assertEquals( [ 1, 3 ], $c->where( 'id', '<>', '2' )->pluck( 'id' )->values()->all() );
		$this->assertEquals( [ 1, 2, 3 ], $c->where( 'id', '!==', '2' )->pluck( 'id' )->values()->all() );
		$this->assertFalse( ( new Collection( [ [ 'id' => 1 ] ] ) )->contains( 'id', '!=', '1' ) );
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testNth(string $collection ): void
	{
		$data = new $collection([
			6 => 'a',
			4 => 'b',
			7 => 'c',
			1 => 'd',
			5 => 'e',
			3 => 'f',
		]);

		$this->assertSame(['a', 'e'], $data->nth(4)->all());
		$this->assertSame(['b', 'f'], $data->nth(4, 1)->all());
		$this->assertSame(['c'], $data->nth(4, 2)->all());
		$this->assertSame(['d'], $data->nth(4, 3)->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMapWithKeysOverwritingKeys(string $collection ): void
	{
		$data = new $collection([
			['id' => 1, 'name' => 'A'],
			['id' => 2, 'name' => 'B'],
			['id' => 1, 'name' => 'C'],
		]);
		$data = $data->map_with_keys(static function ($item) {
			return [$item['id'] => $item['name']];
		});
		$this->assertSame(
			[
				1 => 'C',
				2 => 'B',
			],
			$data->all()
		);
	}

	public function testTransform(): void
	{
		$data = new Collection(['first' => 'taylor', 'last' => 'otwell']);
		$data->transform(static function ($item, $key) {
			return $key.'-'.strrev($item);
		});
		$this->assertSame(['first' => 'first-rolyat', 'last' => 'last-llewto'], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGroupByAttribute(string $collection ): void
	{
		$data = new $collection([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

		$result = $data->group_by('rating');
		$this->assertSame([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->to_array());

		$result = $data->group_by('url');
		$this->assertSame([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGroupByCallable(string $collection ): void
	{
		$data = new $collection([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

		$result = $data->group_by([$this, 'sortByRating']);
		$this->assertSame([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->to_array());

		$result = $data->group_by([$this, 'sortByUrl']);
		$this->assertSame([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->to_array());
	}

	/**
	 * @param array{rating: int, url: string} $value
	 */
	public function sortByRating(array $value): int|string
	{
		return $value['rating'];
	}

	/**
	 * @param array{rating: int, url: string} $value
	 */
	public function sortByUrl(array $value): int|string
	{
		return $value['url'];
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGroupByAttributePreservingKeys(string $collection ): void
	{
		$data = new $collection([10 => ['rating' => 1, 'url' => '1'],	 20 => ['rating' => 1, 'url' => '1'],	 30 => ['rating' => 2, 'url' => '2']]);

		$result = $data->group_by('rating', true);

		$expected_result = [
			1 => [10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1']],
			2 => [30 => ['rating' => 2, 'url' => '2']],
		];

		$this->assertSame($expected_result, $result->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGroupByClosureWhereItemsHaveSingleGroup(string $collection ): void
	{
		$data = new $collection([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

		$result = $data->group_by(static function ($item) {
			return $item['rating'];
		});

		$this->assertSame([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGroupByClosureWhereItemsHaveSingleGroupPreservingKeys(string $collection ): void
	{
		$data = new $collection([10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1'], 30 => ['rating' => 2, 'url' => '2']]);

		$result = $data->group_by(static function ($item) {
			return $item['rating'];
		}, true);

		$expected_result = [
			1 => [10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1']],
			2 => [30 => ['rating' => 2, 'url' => '2']],
		];

		$this->assertSame($expected_result, $result->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGroupByClosureWhereItemsHaveMultipleGroups(string $collection ): void
	{
		$data = new $collection([
			['user' => 1, 'roles' => ['Role_1', 'Role_3']],
			['user' => 2, 'roles' => ['Role_1', 'Role_2']],
			['user' => 3, 'roles' => ['Role_1']],
		]);

		$result = $data->group_by(static function ($item) {
			return $item['roles'];
		});

		$expected_result = [
			'Role_1' => [
				['user' => 1, 'roles' => ['Role_1', 'Role_3']],
				['user' => 2, 'roles' => ['Role_1', 'Role_2']],
				['user' => 3, 'roles' => ['Role_1']],
			],
			'Role_2' => [
				['user' => 2, 'roles' => ['Role_1', 'Role_2']],
			],
			'Role_3' => [
				['user' => 1, 'roles' => ['Role_1', 'Role_3']],
			],
		];

		$this->assertEquals($expected_result, $result->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGroupByClosureWhereItemsHaveMultipleGroupsPreservingKeys(string $collection ): void
	{
		$data = new $collection([
			10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
			20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
			30 => ['user' => 3, 'roles' => ['Role_1']],
		]);

		$result = $data->group_by(static function ($item) {
			return $item['roles'];
		}, true);

		$expected_result = [
			'Role_1' => [
				10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
				20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
				30 => ['user' => 3, 'roles' => ['Role_1']],
			],
			'Role_2' => [
				20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
			],
			'Role_3' => [
				10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
			],
		];

		$this->assertEquals($expected_result, $result->to_array());
	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testGroupByMultiLevelAndClosurePreservingKeys($collection)
//	{
//		$data = new $collection([
//			10 => ['user' => 1, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_3']],
//			20 => ['user' => 2, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_2']],
//			30 => ['user' => 3, 'skilllevel' => 2, 'roles' => ['Role_1']],
//			40 => ['user' => 4, 'skilllevel' => 2, 'roles' => ['Role_2']],
//		]);
//
//		$result = $data->group_by([
//			'skilllevel',
//			function ($item) {
//				return $item['roles'];
//			},
//		], true);
//
//		$expected_result = [
//			1 => [
//				'Role_1' => [
//					10 => ['user' => 1, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_3']],
//					20 => ['user' => 2, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_2']],
//				],
//				'Role_3' => [
//					10 => ['user' => 1, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_3']],
//				],
//				'Role_2' => [
//					20 => ['user' => 2, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_2']],
//				],
//			],
//			2 => [
//				'Role_1' => [
//					30 => ['user' => 3, 'skilllevel' => 2, 'roles' => ['Role_1']],
//				],
//				'Role_2' => [
//					40 => ['user' => 4, 'skilllevel' => 2, 'roles' => ['Role_2']],
//				],
//			],
//		];
//
//		$this->assertSame($expected_result, $result->to_array());
//	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testKeyByAttribute(string $collection ): void
	{
		$data = new $collection([['rating' => 1, 'name' => '1'], ['rating' => 2, 'name' => '2'], ['rating' => 3, 'name' => '3']]);

		$result = $data->key_by('rating');
		$this->assertSame([1 => ['rating' => 1, 'name' => '1'], 2 => ['rating' => 2, 'name' => '2'], 3 => ['rating' => 3, 'name' => '3']], $result->all());

		$result = $data->key_by(static function ($item) {
			return $item['rating'] * 2;
		});
		$this->assertSame([2 => ['rating' => 1, 'name' => '1'], 4 => ['rating' => 2, 'name' => '2'], 6 => ['rating' => 3, 'name' => '3']], $result->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testKeyByClosure(string $collection ): void
	{
		$data = new $collection([
			['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
			['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
		]);
		$result = $data->key_by(static function ($item, $key) {
			return strtolower($key.'-'.$item['firstname'].$item['lastname']);
		});
		$this->assertSame([
			'0-taylorotwell' => ['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
			'1-lucasmichot' => ['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
		], $result->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testKeyByObject(string $collection ): void
	{
		$data = new $collection([
			['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
			['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
		]);
		$result = $data->key_by(static function ($item, $key) use ($collection) {
			return new $collection([$key, $item['firstname'], $item['lastname']]);
		});
		$this->assertSame([
			'[0,"Taylor","Otwell"]' => ['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
			'[1,"Lucas","Michot"]' => ['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
		], $result->all());
	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testContains($collection)
//	{
//		$c = new $collection([1, 3, 5]);
//
//		$this->assertTrue($c->contains(1));
//		$this->assertTrue($c->contains('1'));
//		$this->assertFalse($c->contains(2));
//		$this->assertFalse($c->contains('2'));
//
//		$c = new $collection(['1']);
//		$this->assertTrue($c->contains('1'));
//		$this->assertTrue($c->contains(1));
//
//		$c = new $collection([null]);
//		$this->assertTrue($c->contains(false));
//		$this->assertTrue($c->contains(null));
//		$this->assertTrue($c->contains([]));
//		$this->assertTrue($c->contains(0));
//		$this->assertTrue($c->contains(''));
//
//		$c = new $collection([0]);
//		$this->assertTrue($c->contains(0));
//		$this->assertTrue($c->contains('0'));
//		$this->assertTrue($c->contains(false));
//		$this->assertTrue($c->contains(null));
//
//		$this->assertTrue($c->contains(function ($value) {
//			return $value < 5;
//		}));
//		$this->assertFalse($c->contains(function ($value) {
//			return $value > 5;
//		}));
//
//		$c = new $collection([['v' => 1], ['v' => 3], ['v' => 5]]);
//
//		$this->assertTrue($c->contains('v', 1));
//		$this->assertFalse($c->contains('v', 2));
//
//		$c = new $collection(['date', 'class', (object) ['foo' => 50]]);
//
//		$this->assertTrue($c->contains('date'));
//		$this->assertTrue($c->contains('class'));
//		$this->assertFalse($c->contains('foo'));
//
//		$c = new $collection([['a' => false, 'b' => false], ['a' => true, 'b' => false]]);
//
//		$this->assertTrue($c->contains->a);
//		$this->assertFalse($c->contains->b);
//
//		$c = new $collection([
//			null, 1, 2,
//		]);
//
//		$this->assertTrue($c->contains(function ($value) {
//			return is_null($value);
//		}));
//	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testSome($collection)
//	{
//		$c = new $collection([1, 3, 5]);
//
//		$this->assertTrue($c->some(1));
//		$this->assertFalse($c->some(2));
//		$this->assertTrue($c->some(function ($value) {
//			return $value < 5;
//		}));
//		$this->assertFalse($c->some(function ($value) {
//			return $value > 5;
//		}));
//
//		$c = new $collection([['v' => 1], ['v' => 3], ['v' => 5]]);
//
//		$this->assertTrue($c->some('v', 1));
//		$this->assertFalse($c->some('v', 2));
//
//		$c = new $collection(['date', 'class', (object) ['foo' => 50]]);
//
//		$this->assertTrue($c->some('date'));
//		$this->assertTrue($c->some('class'));
//		$this->assertFalse($c->some('foo'));
//
//		$c = new $collection([['a' => false, 'b' => false], ['a' => true, 'b' => false]]);
//
//		$this->assertTrue($c->some->a);
//		$this->assertFalse($c->some->b);
//
//		$c = new $collection([
//			null, 1, 2,
//		]);
//
//		$this->assertTrue($c->some(function ($value) {
//			return is_null($value);
//		}));
//	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testContainsStrict(string $collection ): void
	{
		$c = new $collection([1, 3, 5, '02']);

		$this->assertTrue($c->contains_strict(1));
		$this->assertFalse($c->contains_strict('1'));
		$this->assertFalse($c->contains_strict(2));
		$this->assertTrue($c->contains_strict('02'));
		$this->assertFalse($c->contains_strict(true));
		$this->assertTrue($c->contains_strict(static function ($value) {
			return $value < 5;
		}));
		$this->assertFalse($c->contains_strict(static function ($value) {
			return $value > 5;
		}));

		$c = new $collection([0]);
		$this->assertTrue($c->contains_strict(0));
		$this->assertFalse($c->contains_strict('0'));

		$this->assertFalse($c->contains_strict(false));
		$this->assertFalse($c->contains_strict(null));

		$c = new $collection([1, null]);
		$this->assertTrue($c->contains_strict(null));
		$this->assertFalse($c->contains_strict(0));
		$this->assertFalse($c->contains_strict(false));

		$c = new $collection([['v' => 1], ['v' => 3], ['v' => '04'], ['v' => 5]]);

		$this->assertTrue($c->contains_strict('v', 1));
		$this->assertFalse($c->contains_strict('v', 2));
		$this->assertFalse($c->contains_strict('v', '1'));
		$this->assertFalse($c->contains_strict('v', 4));
		$this->assertTrue($c->contains_strict('v', '04'));

		$c = new $collection(['date', 'class', (object) ['foo' => 50], '']);

		$this->assertTrue($c->contains_strict('date'));
		$this->assertTrue($c->contains_strict('class'));
		$this->assertFalse($c->contains_strict('foo'));
		$this->assertFalse($c->contains_strict(null));
		$this->assertTrue($c->contains_strict(''));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testContainsWithOperator(string $collection ): void
	{
		$c = new $collection([['v' => 1], ['v' => 3], ['v' => '4'], ['v' => 5]]);

		$this->assertTrue($c->contains('v', '=', 4));
		$this->assertTrue($c->contains('v', '==', 4));
		$this->assertFalse($c->contains('v', '===', 4));
		$this->assertTrue($c->contains('v', '>', 4));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGettingSumFromCollection(string $collection ): void
	{
		$c = new $collection([(object) ['foo' => 50], (object) ['foo' => 50]]);
		$this->assertSame(100, $c->sum('foo'));

		$c = new $collection([(object) ['foo' => 50], (object) ['foo' => 50]]);
		$this->assertSame(100, $c->sum(static function ($i) {
			return $i->foo;
		}));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCanSumValuesWithoutACallback(string $collection ): void
	{
		$c = new $collection([1, 2, 3, 4, 5]);
		$this->assertSame(15, $c->sum());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGettingSumFromEmptyCollection(string $collection ): void
	{
		$c = new $collection;
		$this->assertSame(0, $c->sum('foo'));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testValueRetrieverAcceptsDotNotation(string $collection ): void
	{
		$c = new $collection([
			(object) ['id' => 1, 'foo' => ['bar' => 'B']], (object) ['id' => 2, 'foo' => ['bar' => 'A']],
		]);

		$c = $c->sort_by('foo.bar');
		$this->assertSame([2, 1], $c->pluck('id')->all());
	}

	public function testPullRetrievesItemFromCollection(): void
	{
		$c = new Collection(['foo', 'bar']);

		$this->assertSame('foo', $c->pull(0));
	}

	public function testPullRemovesItemFromCollection(): void
	{
		$c = new Collection(['foo', 'bar']);
		$c->pull(0);
		$this->assertSame([1 => 'bar'], $c->all());
	}

	public function testPullReturnsDefault(): void
	{
		$c = new Collection([]);
		$value = $c->pull(0, 'foo');
		$this->assertSame('foo', $value); // @phpstan-ignore method.alreadyNarrowedType (pull() on an empty collection is inferred to return the 'foo' default)
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testRejectRemovesElementsPassingTruthTest(string $collection ): void
	{
		$c = new $collection(['foo', 'bar']);
		$this->assertSame(['foo'], $c->reject('bar')->values()->all());

		$c = new $collection(['foo', 'bar']);
		$this->assertSame(['foo'], $c->reject(static function ($v) {
			return $v == 'bar';
		})->values()->all());

		$c = new $collection(['foo', null]);
		$this->assertSame(['foo'], $c->reject(null)->values()->all());

		$c = new $collection(['foo', 'bar']);
		$this->assertSame(['foo', 'bar'], $c->reject('baz')->values()->all());

		$c = new $collection(['foo', 'bar']);
		$this->assertSame(['foo', 'bar'], $c->reject(static function ($v) {
			return $v == 'baz';
		})->values()->all());

		$c = new $collection(['id' => 1, 'primary' => 'foo', 'secondary' => 'bar']);
		$this->assertSame(['primary' => 'foo', 'secondary' => 'bar'], $c->reject(static function ($item, $key) {
			return $key == 'id';
		})->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testRejectWithoutAnArgumentRemovesTruthyValues(string $collection ): void
	{
		$data1 = new $collection([
			false,
			true,
			new $collection(),
			0,
		]);
		$this->assertSame([0 => false, 3 => 0], $data1->reject()->all());

		$data2 = new $collection([
			'a' => true,
			'b' => true,
			'c' => true,
		]);
		$this->assertTrue(
			$data2->reject()->is_empty()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSearchReturnsIndexOfFirstFoundItem(string $collection ): void
	{
		$c = new $collection([1, 2, 3, 4, 5, 2, 5, 'foo' => 'bar']);

		$this->assertSame(1, $c->search(2));
		$this->assertSame(1, $c->search('2'));
		$this->assertSame('foo', $c->search('bar'));
		$this->assertSame(4, $c->search(static function ($value) {
			return $value > 4;
		}));
		$this->assertSame('foo', $c->search(static function ($value) {
			return ! is_numeric($value);
		}));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSearchInStrictMode(string $collection ): void
	{
		$c = new $collection([false, 0, 1, [], '']);
		$this->assertFalse($c->search('false', true));
		$this->assertFalse($c->search('1', true));
		$this->assertSame(0, $c->search(false, true));
		$this->assertSame(1, $c->search(0, true));
		$this->assertSame(2, $c->search(1, true));
		$this->assertSame(3, $c->search([], true));
		$this->assertSame(4, $c->search('', true));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSearchReturnsFalseWhenItemIsNotFound(string $collection ): void
	{
		$c = new $collection([1, 2, 3, 4, 5, 'foo' => 'bar']);

		$this->assertFalse($c->search(6));
		$this->assertFalse($c->search('foo'));
		$this->assertFalse($c->search(static function ($value) {
			return $value < 1 && is_numeric($value);
		}));
		$this->assertFalse($c->search(static function ($value) {
			return $value == 'nope';
		}));
	}

	/**
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider('collectionClassProvider')]
	public function testBeforeReturnsItemBeforeTheGivenItem(string $collection): void
	{
		$c = new $collection([1, 2, 3, 4, 5, 2, 5, 'name' => 'taylor', 'framework' => 'laravel']);

		$this->assertSame(1, $c->before(2));
		$this->assertSame(1, $c->before('2'));
		$this->assertSame(5, $c->before('taylor'));
		$this->assertSame('taylor', $c->before('laravel'));
		$this->assertSame(4, $c->before(static function ($value) {
				return $value > 4;
		}));
		$this->assertSame(5, $c->before(static function ($value) {
				return ! is_numeric($value);
		}));
	}

	/**
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider('collectionClassProvider')]
	public function testBeforeInStrictMode(string $collection): void
	{
		$c = new $collection([false, 0, 1, [], '']);
		$this->assertNull($c->before('false', true));
		$this->assertNull($c->before('1', true));
		$this->assertNull($c->before(false, true));
		$this->assertFalse($c->before(0, true));
		$this->assertSame(0, $c->before(1, true));
		$this->assertSame(1, $c->before([], true));
		$this->assertSame([], $c->before('', true));
	}

	/**
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider('collectionClassProvider')]
	public function testBeforeReturnsNullWhenItemIsNotFound(string $collection): void
	{
		$c = new $collection([1, 2, 3, 4, 5, 'foo' => 'bar']);

		$this->assertNull($c->before(6));
		$this->assertNull($c->before('foo'));
		$this->assertNull($c->before(static function ($value) {
				return $value < 1 && is_numeric($value);
		}));
		$this->assertNull($c->before(static function ($value) {
				return $value === 'nope';
		}));
	}

	/**
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider('collectionClassProvider')]
	public function testBeforeReturnsNullWhenItemOnTheFirstitem(string $collection): void
	{
		$c = new $collection([1, 2, 3, 4, 5, 'foo' => 'bar']);

		$this->assertNull($c->before(1));
		$this->assertNull($c->before(static function ($value) {
				return $value < 2 && is_numeric($value);
		}));

		$c = new $collection(['foo' => 'bar', 1, 2, 3, 4, 5]);
		$this->assertNull($c->before('bar'));
	}

	/**
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider('collectionClassProvider')]
	public function testAfterReturnsItemAfterTheGivenItem(string $collection): void
	{
		$c = new $collection([1, 2, 3, 4, 2, 5, 'name' => 'taylor', 'framework' => 'laravel']);

		$this->assertSame(2, $c->after(1));
		$this->assertSame(3, $c->after(2));
		$this->assertSame(4, $c->after(3));
		$this->assertSame(2, $c->after(4));
		$this->assertSame('taylor', $c->after(5));
		$this->assertSame('laravel', $c->after('taylor'));

		$this->assertSame(4, $c->after(static function ($value) {
				return $value > 2;
		}));
		$this->assertSame('laravel', $c->after(static function ($value) {
				return ! is_numeric($value);
		}));
	}

	/**
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider('collectionClassProvider')]
	public function testAfterInStrictMode(string $collection): void
	{
		$c = new $collection([false, 0, 1, [], '']);

		$this->assertNull($c->after('false', true));
		$this->assertNull($c->after('1', true));
		$this->assertNull($c->after('', true));
		$this->assertSame(0, $c->after(false, true));
		$this->assertSame([], $c->after(1, true));
		$this->assertSame('', $c->after([], true));
	}

	/**
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider('collectionClassProvider')]
	public function testAfterReturnsNullWhenItemIsNotFound(string $collection): void
	{
		$c = new $collection([1, 2, 3, 4, 5, 'foo' => 'bar']);

		$this->assertNull($c->after(6));
		$this->assertNull($c->after('foo'));
		$this->assertNull($c->after(static function ($value) {
				return $value < 1 && is_numeric($value);
		}));
		$this->assertNull($c->after(static function ($value) {
				return $value === 'nope';
		}));
	}

	/**
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider('collectionClassProvider')]
	public function testAfterReturnsNullWhenItemOnTheLastItem(string $collection): void
	{
		$c = new $collection([1, 2, 3, 4, 5, 'foo' => 'bar']);

		$this->assertNull($c->after('bar'));
		$this->assertNull($c->after(static function ($value) {
				return $value > 4 && ! is_numeric($value);
		}));

		$c = new $collection(['foo' => 'bar', 1, 2, 3, 4, 5]);
		$this->assertNull($c->after(5));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testKeys(string $collection ): void
	{
		$c = new $collection(['name' => 'taylor', 'framework' => 'laravel']);
		$this->assertSame(['name', 'framework'], $c->keys()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPaginate(string $collection ): void
	{
		$c = new $collection(['one', 'two', 'three', 'four']);
		$this->assertSame(['one', 'two'], $c->for_page(0, 2)->all());
		$this->assertSame(['one', 'two'], $c->for_page(1, 2)->all());
		$this->assertSame([2 => 'three', 3 => 'four'], $c->for_page(2, 2)->all());
		$this->assertSame([], $c->for_page(3, 2)->all());
	}

	public function testPrepend(): void
	{
		$c = new Collection(['one', 'two', 'three', 'four']);
		$this->assertSame(['zero', 'one', 'two', 'three', 'four'], $c->prepend('zero')->all());

		$c = new Collection(['one' => 1, 'two' => 2]);
		$this->assertSame(['zero' => 0, 'one' => 1, 'two' => 2], $c->prepend(0, 'zero')->all());
	}

	public function testPrependMany(): void
	{
		$c = new Collection(['one', 'two', 'three', 'four']);
		$this->assertSame(['zero', 'new', 'one', 'two', 'three', 'four'], $c->prepend_many('zero', 'new')->all());
	}

	public function testPushWithOneItem(): void
	{
		$expected = [
			0 => 4,
			1 => 5,
			2 => 6,
			3 => ['a', 'b', 'c'],
			4 => ['who' => 'Jonny', 'preposition' => 'from', 'where' => 'Laroe'],
			5 => 'Jonny from Laroe',
		];

		$data = new Collection([4, 5, 6]);
		$data->push(['a', 'b', 'c']);
		$data->push(['who' => 'Jonny', 'preposition' => 'from', 'where' => 'Laroe']);
		$actual = $data->push('Jonny from Laroe')->to_array();

		$this->assertSame($expected, $actual);
	}

//	public function testPushWithMultipleItems()
//	{
//		$expected = [
//			0 => 4,
//			1 => 5,
//			2 => 6,
//			3 => 'Jonny',
//			4 => 'from',
//			5 => 'Laroe',
//			6 => 'Jonny',
//			7 => 'from',
//			8 => 'Laroe',
//			9 => 'a',
//			10 => 'b',
//			11 => 'c',
//		];
//
//		$data = new Collection([4, 5, 6]);
//		$data->push('Jonny', 'from', 'Laroe');
//		$data->push(...[11 => 'Jonny', 12 => 'from', 13 => 'Laroe']);
//		$data->push(...collect(['a', 'b', 'c']));
//		$actual = $data->push(...[])->to_array();
//
//		$this->assertSame($expected, $actual);
//	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testZip(string $collection ): void
	{
		$c = new $collection([1, 2, 3]);
		$c = $c->zip(new $collection([4, 5, 6]));
		$this->assertInstanceOf($collection, $c);
		$this->assertInstanceOf($collection, $c->get(0));
		$this->assertInstanceOf($collection, $c->get(1));
		$this->assertInstanceOf($collection, $c->get(2));
		$this->assertCount(3, $c);
		$this->assertSame([1, 4], $c->get(0)?->all());
		$this->assertSame([2, 5], $c->get(1)?->all());
		$this->assertSame([3, 6], $c->get(2)?->all());

		$c = new $collection([1, 2, 3]);
		$c = $c->zip([4, 5, 6], [7, 8, 9]);
		$this->assertCount(3, $c);
		$this->assertSame([1, 4, 7], $c->get(0)?->all());
		$this->assertSame([2, 5, 8], $c->get(1)?->all());
		$this->assertSame([3, 6, 9], $c->get(2)?->all());

		$c = new $collection([1, 2, 3]);
		$c = $c->zip([4, 5, 6], [7]);
		$this->assertCount(3, $c);
		$this->assertSame([1, 4, 7], $c->get(0)?->all());
		$this->assertSame([2, 5, null], $c->get(1)?->all());
		$this->assertSame([3, 6, null], $c->get(2)?->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPadPadsArrayWithValue(string $collection ): void
	{
		$c = new $collection([1, 2, 3]);
		$c = $c->pad(4, 0);
		$this->assertSame([1, 2, 3, 0], $c->all());

		$c = new $collection([1, 2, 3, 4, 5]);
		$c = $c->pad(4, 0);
		$this->assertSame([1, 2, 3, 4, 5], $c->all());

		$c = new $collection([1, 2, 3]);
		$c = $c->pad(-4, 0);
		$this->assertSame([0, 1, 2, 3], $c->all());

		$c = new $collection([1, 2, 3, 4, 5]);
		$c = $c->pad(-4, 0);
		$this->assertSame([1, 2, 3, 4, 5], $c->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGettingMaxItemsFromCollection(string $collection ): void
	{
		$c = new $collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
		$this->assertSame(20, $c->max(static function ($item) {
			return $item->foo;
		}));
		$this->assertSame(20, $c->max('foo'));
		$this->assertSame(20, $c->max->foo);

		$c = new $collection([['foo' => 10], ['foo' => 20]]);
		$this->assertSame(20, $c->max('foo'));
		$this->assertSame(20, $c->max->foo);

		$c = new $collection([1, 2, 3, 4, 5]);
		$this->assertSame(5, $c->max());

		$c = new $collection;
		$this->assertNull($c->max());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGettingMinItemsFromCollection(string $collection ): void
	{
		$c = new $collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
		$this->assertSame(10, $c->min(static function ($item) {
			return $item->foo;
		}));
		$this->assertSame(10, $c->min('foo'));
		$this->assertSame(10, $c->min->foo);

		$c = new $collection([['foo' => 10], ['foo' => 20]]);
		$this->assertSame(10, $c->min('foo'));
		$this->assertSame(10, $c->min->foo);

		$c = new $collection([['foo' => 10], ['foo' => 20], ['foo' => null]]);
		$this->assertSame(10, $c->min('foo'));
		$this->assertSame(10, $c->min->foo);

		$c = new $collection([1, 2, 3, 4, 5]);
		$this->assertSame(1, $c->min());

		$c = new $collection([1, null, 3, 4, 5]);
		$this->assertSame(1, $c->min());

		$c = new $collection([0, 1, 2, 3, 4]);
		$this->assertSame(0, $c->min());

		$c = new $collection;
		$this->assertNull($c->min());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testOnly(string $collection ): void
	{
		$data = new $collection( [
			'first' => 'Taylor',
			'last' => 'Otwell',
			'email' => 'taylorotwell@gmail.com',
		] );

		$this->assertSame( $data->all(), $data->only( null )->all() );
		$this->assertSame( [ 'first' => 'Taylor' ], $data->only( [ 'first', 'missing' ] )->all() );
		$this->assertSame( [ 'first' => 'Taylor' ], $data->only( 'first', 'missing' )->all() );
		$this->assertSame( [ 'first' => 'Taylor' ], $data->only( collect( [ 'first', 'missing' ] ) )->all());

		$this->assertSame( [ 'first' => 'Taylor', 'email' => 'taylorotwell@gmail.com' ], $data->only( [ 'first', 'email' ] )->all() );
		$this->assertSame( [ 'first' => 'Taylor', 'email' => 'taylorotwell@gmail.com' ], $data->only( 'first', 'email' )->all() );
		$this->assertSame( [ 'first' => 'Taylor', 'email' => 'taylorotwell@gmail.com' ], $data->only( collect( [ 'first', 'email' ] ) )->all() );
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testOnlyChildren(string $collection ): void
	{
		$data = new $collection( [
			[
				'first' => 'Taylor',
				'last' => 'Otwell',
				'email' => 'taylorotwell@gmail.com',
			],
			[
				'first' => 'John',
				'last' => 'Smith',
				'email' => 'john@gmail.com',
			],
			[
				'first' => 'Adam',
				'last' => 'Scott',
				'email' => 'adam@gmail.com',
			],
			[
				'first' => 'Leslie',
				'last' => 'Knope',
				'email' => 'leslie@gmail.com',
			],
		] );

		$this->assertSame( $data->all(), $data->only_children( null )->all() );

		$expected = [
			[
				'first' => 'Taylor',
				'last' => 'Otwell',
			],
			[
				'first' => 'John',
				'last' => 'Smith',
			],
			[
				'first' => 'Adam',
				'last' => 'Scott',
			],
			[
				'first' => 'Leslie',
				'last' => 'Knope',
			],
		];

		$this->assertSame( $expected, $data->only_children( [ 'first', 'last' ] )->all() );
		$this->assertSame( $expected, $data->only_children( 'first', 'last' )->all() );
		$this->assertSame( $expected, $data->only_children( 'first', 'last', 'missing' )->all() );
		$this->assertSame( $expected, $data->only_children( collect( [ 'first', 'last' ] ) )->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGettingAvgItemsFromCollection(string $collection ): void
	{
		$c = new $collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
		$this->assertSame(15, $c->avg(static function ($item) {
			return $item->foo;
		}));
		$this->assertSame(15, $c->avg('foo'));
		$this->assertSame(15, $c->avg->foo);

		$c = new $collection([(object) ['foo' => 10], (object) ['foo' => 20], (object) ['foo' => null]]);
		$this->assertSame(15, $c->avg(static function ($item) {
			return $item->foo;
		}));
		$this->assertSame(15, $c->avg('foo'));
		$this->assertSame(15, $c->avg->foo);

		$c = new $collection([['foo' => 10], ['foo' => 20]]);
		$this->assertSame(15, $c->avg('foo'));
		$this->assertSame(15, $c->avg->foo);

		$c = new $collection([1, 2, 3, 4, 5]);
		$this->assertSame(3, $c->avg());

		$c = new $collection;
		$this->assertNull($c->avg());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testJsonSerialize(string $collection ): void
	{
		$c = new $collection([
			new TestArrayableObject,
			new TestJsonableObject,
			new TestJsonSerializeObject,
			'baz',
		]);

		$this->assertSame([
			['foo' => 'bar'],
			['foo' => 'bar'],
			['foo' => 'bar'],
			'baz',
		], $c->jsonSerialize());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCombineWithArray(string $collection ): void
	{
		$expected = [
			1 => 4,
			2 => 5,
			3 => 6,
		];

		$c = new $collection(array_keys($expected));
		$actual = $c->combine(array_values($expected))->to_array();

		$this->assertSame($expected, $actual);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCombineWithCollection(string $collection ): void
	{
		$expected = [
			1 => 4,
			2 => 5,
			3 => 6,
		];

		$keyCollection = new $collection(array_keys($expected));
		$valueCollection = new $collection(array_values($expected));
		$actual = $keyCollection->combine($valueCollection)->to_array();

		$this->assertSame($expected, $actual);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testConcatWithArray(string $collection ): void
	{
		$expected = [
			0 => 4,
			1 => 5,
			2 => 6,
			3 => 'a',
			4 => 'b',
			5 => 'c',
			6 => 'Jonny',
			7 => 'from',
			8 => 'Laroe',
			9 => 'Jonny',
			10 => 'from',
			11 => 'Laroe',
		];

		$data = new $collection([4, 5, 6]);
		$data = $data->concat(['a', 'b', 'c']);
		$data = $data->concat(['who' => 'Jonny', 'preposition' => 'from', 'where' => 'Laroe']);
		$actual = $data->concat(['who' => 'Jonny', 'preposition' => 'from', 'where' => 'Laroe'])->to_array();

		$this->assertSame($expected, $actual);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testConcatWithCollection(string $collection ): void
	{
		$expected = [
			0 => 4,
			1 => 5,
			2 => 6,
			3 => 'a',
			4 => 'b',
			5 => 'c',
			6 => 'Jonny',
			7 => 'from',
			8 => 'Laroe',
			9 => 'Jonny',
			10 => 'from',
			11 => 'Laroe',
		];

		$firstCollection = new $collection([4, 5, 6]);
		$secondCollection = new $collection(['a', 'b', 'c']);
		$thirdCollection = new $collection(['who' => 'Jonny', 'preposition' => 'from', 'where' => 'Laroe']);
		$firstCollection = $firstCollection->concat($secondCollection);
		$firstCollection = $firstCollection->concat($thirdCollection);
		$actual = $firstCollection->concat($thirdCollection)->to_array();

		$this->assertSame($expected, $actual);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testReduce(string $collection ): void
	{
		$data = new $collection([1, 2, 3]);
		$this->assertSame(6, $data->reduce(static function ($carry, $element) {
			return $carry += $element;
		}));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testRandomThrowsAnExceptionUsingAmountBiggerThanCollectionSize(string $collection ): void
	{
		$this->expectException(InvalidArgumentException::class);

		$data = new $collection([1, 2, 3]);
		$data->random(4);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPipe(string $collection ): void
	{
		$data = new $collection([1, 2, 3]);

		$this->assertSame(6, $data->pipe(static function ($data) {
			return $data->sum();
		}));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMedianValueWithArrayCollection(string $collection ): void
	{
		$data = new $collection([1, 2, 2, 4]);

		$this->assertSame(2, $data->median());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMedianValueByKey(string $collection ): void
	{
		$data = new $collection([
			(object) ['foo' => 1],
			(object) ['foo' => 2],
			(object) ['foo' => 2],
			(object) ['foo' => 4],
		]);
		$this->assertSame(2, $data->median('foo'));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMedianOnCollectionWithNull(string $collection ): void
	{
		$data = new $collection([
			(object) ['foo' => 1],
			(object) ['foo' => 2],
			(object) ['foo' => 4],
			(object) ['foo' => null],
		]);
		$this->assertSame(2, $data->median('foo'));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testEvenMedianCollection(string $collection ): void
	{
		$data = new $collection([
			(object) ['foo' => 0],
			(object) ['foo' => 3],
		]);
		$this->assertSame(1.5, $data->median('foo'));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMedianOutOfOrderCollection(string $collection ): void
	{
		$data = new $collection([
			(object) ['foo' => 0],
			(object) ['foo' => 5],
			(object) ['foo' => 3],
		]);
		$this->assertSame(3, $data->median('foo'));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMedianOnEmptyCollectionReturnsNull(string $collection ): void
	{
		$data = new $collection;
		$this->assertNull($data->median());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testModeOnNullCollection(string $collection ): void
	{
		$data = new $collection;
		$this->assertNull($data->mode());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testMode(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 4, 5]);
		$this->assertSame([4], $data->mode());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testModeValueByKey(string $collection ): void
	{
		$data = new $collection([
			(object) ['foo' => 1],
			(object) ['foo' => 1],
			(object) ['foo' => 2],
			(object) ['foo' => 4],
		]);
		$this->assertSame([1], $data->mode('foo'));
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWithMultipleModeValues(string $collection ): void
	{
		$data = new $collection([1, 2, 2, 1]);
		$this->assertSame([1, 2], $data->mode());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSliceOffset(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
		$this->assertSame([4, 5, 6, 7, 8], $data->slice(3)->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSliceNegativeOffset(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
		$this->assertSame([6, 7, 8], $data->slice(-3)->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSliceOffsetAndLength(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
		$this->assertSame([4, 5, 6], $data->slice(3, 3)->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSliceOffsetAndNegativeLength(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
		$this->assertSame([4, 5, 6, 7], $data->slice(3, -1)->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSliceNegativeOffsetAndLength(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
		$this->assertSame([4, 5, 6], $data->slice(-5, 3)->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSliceNegativeOffsetAndNegativeLength(string $collection ): void
	{
		$data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
		$this->assertSame([3, 4, 5, 6], $data->slice(-6, -2)->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCollectionFromTraversable(string $collection ): void
	{
		$data = new $collection(new ArrayObject([1, 2, 3]));
		$this->assertSame([1, 2, 3], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCollectionFromTraversableWithKeys(string $collection ): void
	{
		$data = new $collection(new ArrayObject(['foo' => 1, 'bar' => 2, 'baz' => 3]));
		$this->assertSame(['foo' => 1, 'bar' => 2, 'baz' => 3], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSplitCollectionWithADivisibleCount(string $collection ): void
	{
		$data = new $collection(['a', 'b', 'c', 'd']);

		$this->assertSame(
			[['a', 'b'], ['c', 'd']],
			$data->split(2)->map(static function (Collection $chunk) {
				return $chunk->values()->to_array();
			})->to_array()
		);

		$data = new $collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

		$this->assertSame(
			[[1, 2, 3, 4, 5], [6, 7, 8, 9, 10]],
			$data->split(2)->map(static function (Collection $chunk) {
				return $chunk->values()->to_array();
			})->to_array()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSplitCollectionWithAnUndivisableCount(string $collection ): void
	{
		$data = new $collection(['a', 'b', 'c']);

		$this->assertSame(
			[['a', 'b'], ['c']],
			$data->split(2)->map(static function (Collection $chunk) {
				return $chunk->values()->to_array();
			})->to_array()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSplitCollectionWithCountLessThenDivisor(string $collection ): void
	{
		$data = new $collection(['a']);

		$this->assertSame(
			[['a']],
			$data->split(2)->map(static function (Collection $chunk) {
				return $chunk->values()->to_array();
			})->to_array()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSplitCollectionIntoThreeWithCountOfFour(string $collection ): void
	{
		$data = new $collection(['a', 'b', 'c', 'd']);

		$this->assertSame(
			[['a', 'b'], ['c'], ['d']],
			$data->split(3)->map(static function (Collection $chunk) {
				return $chunk->values()->to_array();
			})->to_array()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSplitCollectionIntoThreeWithCountOfFive(string $collection ): void
	{
		$data = new $collection(['a', 'b', 'c', 'd', 'e']);

		$this->assertSame(
			[['a', 'b'], ['c', 'd'], ['e']],
			$data->split(3)->map(static function (Collection $chunk) {
				return $chunk->values()->to_array();
			})->to_array()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSplitCollectionIntoSixWithCountOfTen(string $collection ): void
	{
		$data = new $collection(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j']);

		$this->assertSame(
			[['a', 'b'], ['c', 'd'], ['e', 'f'], ['g', 'h'], ['i'], ['j']],
			$data->split(6)->map(static function (Collection $chunk) {
				return $chunk->values()->to_array();
			})->to_array()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testSplitEmptyCollection(string $collection ): void
	{
		$data = new $collection;

		$this->assertSame(
			[],
			$data->split(2)->map(static function (Collection $chunk) {
				return $chunk->values()->to_array();
			})->to_array()
		);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testHigherOrderCollectionGroupBy(string $collection ): void
	{
		$data = new $collection([
			new TestSupportCollectionHigherOrderItem,
			new TestSupportCollectionHigherOrderItem('TAYLOR'),
			new TestSupportCollectionHigherOrderItem('foo'),
		]);

		$this->assertSame([
			'taylor' => [$data->get(0)],
			'TAYLOR' => [$data->get(1)],
			'foo' => [$data->get(2)],
		], $data->group_by->name->to_array());

		$this->assertSame([
			'TAYLOR' => [$data->get(0), $data->get(1)],
			'FOO' => [$data->get(2)],
		], $data->group_by->uppercase()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testHigherOrderCollectionMap(string $collection ): void
	{
		$person1 = (object) ['name' => 'Taylor'];
		$person2 = (object) ['name' => 'Yaz'];

		$data = new $collection([$person1, $person2]);

		$this->assertSame(['Taylor', 'Yaz'], $data->map->name->to_array());

		$data = new $collection([new TestSupportCollectionHigherOrderItem, new TestSupportCollectionHigherOrderItem]);

		$this->assertSame(['TAYLOR', 'TAYLOR'], $data->each->uppercase()->map->name->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testHigherOrderCollectionMapFromArrays(string $collection ): void
	{
		$person1 = ['name' => 'Taylor'];
		$person2 = ['name' => 'Yaz'];

		$data = new $collection([$person1, $person2]);

		$this->assertSame(['Taylor', 'Yaz'], $data->map->name->to_array());

		$data = new $collection([new TestSupportCollectionHigherOrderItem, new TestSupportCollectionHigherOrderItem]);

		$this->assertSame(['TAYLOR', 'TAYLOR'], $data->each->uppercase()->map->name->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPartition(string $collection ): void
	{
		$data = new $collection(range(1, 10));

		[$firstPartition, $secondPartition] = $data->partition(static function ($i) {
			return $i <= 5;
		})->all();

		$this->assertSame([1, 2, 3, 4, 5], $firstPartition->values()->to_array());
		$this->assertSame([6, 7, 8, 9, 10], $secondPartition->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPartitionCallbackWithKey(string $collection ): void
	{
		$data = new $collection(['zero', 'one', 'two', 'three']);

		[$even, $odd] = $data->partition(static function ($item, $index) {
			return $index % 2 === 0;
		})->all();

		$this->assertSame(['zero', 'two'], $even->values()->to_array());
		$this->assertSame(['one', 'three'], $odd->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPartitionByKey(string $collection ): void
	{
		$courses = new $collection([
			['free' => true, 'title' => 'Basic'], ['free' => false, 'title' => 'Premium'],
		]);

		[$free, $premium] = $courses->partition('free')->all();

		$this->assertSame([['free' => true, 'title' => 'Basic']], $free->values()->to_array());
		$this->assertSame([['free' => false, 'title' => 'Premium']], $premium->values()->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPartitionWithOperators(string $collection ): void
	{
		$data = new $collection([
			['name' => 'Tim', 'age' => 17],
			['name' => 'Agatha', 'age' => 62],
			['name' => 'Kristina', 'age' => 33],
			['name' => 'Tim', 'age' => 41],
		]);

		[$tims, $others] = $data->partition('name', 'Tim')->all();

		$this->assertSame($tims->values()->all(), [
			['name' => 'Tim', 'age' => 17],
			['name' => 'Tim', 'age' => 41],
		]);

		$this->assertSame($others->values()->all(), [
			['name' => 'Agatha', 'age' => 62],
			['name' => 'Kristina', 'age' => 33],
		]);

		[$adults, $minors] = $data->partition('age', '>=', 18)->all();

		$this->assertSame($adults->values()->all(), [
			['name' => 'Agatha', 'age' => 62],
			['name' => 'Kristina', 'age' => 33],
			['name' => 'Tim', 'age' => 41],
		]);

		$this->assertSame($minors->values()->all(), [
			['name' => 'Tim', 'age' => 17],
		]);
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPartitionPreservesKeys(string $collection ): void
	{
		$courses = new $collection([
			'a' => ['free' => true], 'b' => ['free' => false], 'c' => ['free' => true],
		]);

		[$free, $premium] = $courses->partition('free')->all();

		$this->assertSame(['a' => ['free' => true], 'c' => ['free' => true]], $free->to_array());
		$this->assertSame(['b' => ['free' => false]], $premium->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testPartitionEmptyCollection(string $collection ): void
	{
		$data = new $collection;

		$this->assertCount(2, $data->partition(static function () {
			return true;
		}));
	}

//	/**
//	 * @dataProvider collectionClassProvider
//	 */
//	#[DataProvider( 'collectionClassProvider' )]
//	public function testHigherOrderPartition($collection)
//	{
//		$courses = new $collection([
//			'a' => ['free' => true], 'b' => ['free' => false], 'c' => ['free' => true],
//		]);
//
//		[$free, $premium] = $courses->partition->free->all();
//
//		$this->assertSame(['a' => ['free' => true], 'c' => ['free' => true]], $free->to_array());
//
//		$this->assertSame(['b' => ['free' => false]], $premium->to_array());
//	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testTap(string $collection ): void
	{
		$data = new $collection([1, 2, 3]);

		$fromTap = [];
		$data = $data->tap(static function ($data) use (&$fromTap) {
			$fromTap = $data->slice(0, 1)->to_array();
		});

		$this->assertSame([1], $fromTap);
		$this->assertSame([1, 2, 3], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhen(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->when('adam', static function ($data, $newName) {
			return $data->concat([$newName]);
		});

		$this->assertSame(['michael', 'tom', 'adam'], $data->to_array());

		$data = new $collection(['michael', 'tom']);

		$data = $data->when(false, static function ($data) {
			return $data->concat(['adam']);
		});

		$this->assertSame(['michael', 'tom'], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhenDefault(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->when(false, static function ($data) {
			return $data->concat(['adam']);
		}, static function ($data) {
			return $data->concat(['taylor']);
		});

		$this->assertSame(['michael', 'tom', 'taylor'], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhenEmpty(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->when_empty(static function ($data) {
			return $data->concat(['adam']);
		});

		$this->assertSame(['michael', 'tom'], $data->to_array());

		$data = new $collection;

		$data = $data->when_empty(static function ($data) {
			return $data->concat(['adam']);
		});

		$this->assertSame(['adam'], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhenEmptyDefault(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->when_empty(static function ($data) {
			return $data->concat(['adam']);
		}, static function ($data) {
			return $data->concat(['taylor']);
		});

		$this->assertSame(['michael', 'tom', 'taylor'], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhenNotEmpty(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->when_not_empty(static function ($data) {
			return $data->concat(['adam']);
		});

		$this->assertSame(['michael', 'tom', 'adam'], $data->to_array());

		$data = new $collection;

		$data = $data->when_not_empty(static function ($data) {
			return $data->concat(['adam']);
		});

		$this->assertSame([], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhenNotEmptyDefault(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->when_not_empty(static function ($data) {
			return $data->concat(['adam']);
		}, static function ($data) {
			return $data->concat(['taylor']);
		});

		$this->assertSame(['michael', 'tom', 'adam'], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnless(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->unless(false, static function ($data) {
			return $data->concat(['caleb']);
		});

		$this->assertSame(['michael', 'tom', 'caleb'], $data->to_array());

		$data = new $collection(['michael', 'tom']);

		$data = $data->unless(true, static function ($data) {
			return $data->concat(['caleb']);
		});

		$this->assertSame(['michael', 'tom'], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnlessDefault(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->unless(true, static function ($data) {
			return $data->concat(['caleb']);
		}, static function ($data) {
			return $data->concat(['taylor']);
		});

		$this->assertSame(['michael', 'tom', 'taylor'], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnlessEmpty(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->unless_empty(static function ($data) {
			return $data->concat(['adam']);
		});

		$this->assertSame(['michael', 'tom', 'adam'], $data->to_array());

		$data = new $collection;

		$data = $data->unless_empty(static function ($data) {
			return $data->concat(['adam']);
		});

		$this->assertSame([], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnlessEmptyDefault(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->unless_empty(static function ($data) {
			return $data->concat(['adam']);
		}, static function ($data) {
			return $data->concat(['taylor']);
		});

		$this->assertSame(['michael', 'tom', 'adam'], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnlessNotEmpty(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->unless_not_empty(static function ($data) {
			return $data->concat(['adam']);
		});

		$this->assertSame(['michael', 'tom'], $data->to_array());

		$data = new $collection;

		$data = $data->unless_not_empty(static function ($data) {
			return $data->concat(['adam']);
		});

		$this->assertSame(['adam'], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testUnlessNotEmptyDefault(string $collection ): void
	{
		$data = new $collection(['michael', 'tom']);

		$data = $data->unless_not_empty(static function ($data) {
			return $data->concat(['adam']);
		}, static function ($data) {
			return $data->concat(['taylor']);
		});

		$this->assertSame(['michael', 'tom', 'taylor'], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testHasReturnsValidResults(string $collection ): void
	{
		$data = new $collection(['foo' => 'one', 'bar' => 'two', 1 => 'three']);
		$this->assertTrue($data->has('foo'));
		$this->assertTrue($data->has('foo', 'bar', 1));
		$this->assertFalse($data->has('foo', 'bar', 1, 'baz'));
		$this->assertFalse($data->has('baz'));
	}

	public function testPutAddsItemToCollection(): void
	{
		$data = new Collection;
		$this->assertSame([], $data->to_array());
		$data->put('foo', 1);
		$this->assertSame(['foo' => 1], $data->to_array());
		$data->put('bar', ['nested' => 'two']);
		$this->assertSame(['foo' => 1, 'bar' => ['nested' => 'two']], $data->to_array());
		$data->put('foo', 3);
		$this->assertSame(['foo' => 3, 'bar' => ['nested' => 'two']], $data->to_array());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testItThrowsExceptionWhenTryingToAccessNoProxyProperty(string $collection ): void
	{
		$data = new $collection;
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Property [foo] does not exist on this collection instance.');

		$data->foo; // @phpstan-ignore property.notFound (foo is not a defined or HigherOrderProxy property; the exception is the point), expr.resultUnused (property read only to trigger the exception)
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testGetWithNullReturnsNull(string $collection ): void
	{
		$data = new $collection([1, 2, 3]);
		$this->assertNull($data->get(null)); // @phpstan-ignore argument.type (null passed on purpose; get() key parameter is not nullable)
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereNull(string $collection ): void
	{
		$data = new $collection([
			['name' => 'Taylor'],
			['name' => null],
			['name' => 'Bert'],
			['name' => false],
			['name' => ''],
		]);

		$this->assertSame([
			1 => ['name' => null],
		], $data->where_null('name')->all());

		$this->assertSame([], $data->where_null()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereNullWithoutKey(string $collection ): void
	{
		$collection = new $collection([1, null, 3, 'null', false, true]);
		$this->assertSame([
			1 => null,
		], $collection->where_null()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereNotNull(string $collection ): void
	{
		$data = new $collection($originalData = [
			['name' => 'Taylor'],
			['name' => null],
			['name' => 'Bert'],
			['name' => false],
			['name' => ''],
		]);

		$this->assertSame([
			0 => ['name' => 'Taylor'],
			2 => ['name' => 'Bert'],
			3 => ['name' => false],
			4 => ['name' => ''],
		], $data->where_not_null('name')->all());

		$this->assertSame($originalData, $data->where_not_null()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testWhereNotNullWithoutKey(string $collection ): void
	{
		$data = new $collection([1, null, 3, 'null', false, true]);

		$this->assertSame([
			0 => 1,
			2 => 3,
			3 => 'null',
			4 => false,
			5 => true,
		], $data->where_not_null()->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function testCollect(string $collection ): void
	{
		$data = $collection::make([
			'a' => 1,
			'b' => 2,
			'c' => 3,
		])->collect();

		$data = with($data);
		$this->assertInstanceOf(Collection::class, $data);
		$this->assertSame([
			'a' => 1,
			'b' => 2,
			'c' => 3,
		], $data->all());
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function test_from_wp_query(string $collection ): void {
		static::factory()->post->create_many( 5 );
		$query = new \WP_Query(
			[
				'posts_per_page' => 5,
				'post_type'      => 'post',
			]
		);
		$c = $collection::from( $query );

		$this->assertCount( 5, $c->all() );
		$this->assertTrue( $c->every( static function ( $val, $key ) {
			return $val instanceof Model\Post;
		} ) );
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function test_from_empty_wp_query(string $collection ): void {
		$query = new \WP_Query();
		$c = $collection::from( $query );
		$this->assertTrue( $c->is_empty() );
	}

	/**
	 * @dataProvider collectionClassProvider
	 *
	 * @param class-string<Collection<array-key, mixed>> $collection
	 */
	#[DataProvider( 'collectionClassProvider' )]
	public function test_from_fallback(string $collection ): void {
		$c = $collection::from( ['a', 'b'] );
		$this->assertCount( 2, $c->all() );
		$this->assertSame( ['a', 'b'], $c->intersect( ['a', 'b'] )->all() );
	}

	/**
	 * Provides each collection class, respectively.
	 *
	 * @return array<mixed>
	 */
	public static function collectionClassProvider()
	{
		return [
			[Collection::class],
		];
	}
}

class TestSupportCollectionHigherOrderItem {
	public string $name;

	public function __construct(string $name = 'taylor')
	{
		$this->name = $name;
	}

	public function uppercase(): string
	{
		return $this->name = strtoupper($this->name);
	}

	public function is(string $name): bool
	{
		return $this->name === $name;
	}
}

class TestAccessorEloquentTestStub {
	/**
	 * @var array<string, mixed>
	 */
	protected array $attributes = [];

	/**
	 * @param array<string, mixed> $attributes
	 */
	public function __construct(array $attributes)
	{
		$this->attributes = $attributes;
	}

	public function __get(string $attribute): mixed
	{
		$accessor = 'get'.lcfirst($attribute).'Attribute';
		if (method_exists($this, $accessor)) {
			return $this->$accessor();
		}

		return $this->$attribute;
	}

	public function __isset(string $attribute): bool
	{
		$accessor = 'get'.lcfirst($attribute).'Attribute';

		if (method_exists($this, $accessor)) {
			return ! is_null($this->$accessor());
		}

		return isset($this->$attribute);
	}

	public function getSomeAttribute(): mixed
	{
		return $this->attributes['some'];
	}
}

class TestArrayAccessImplementation implements ArrayAccess {
	/**
	 * @var array<array-key, mixed>
	 */
	private array $arr;

	/**
	 * @param array<array-key, mixed> $arr
	 */
	public function __construct(array $arr)
	{
		$this->arr = $arr;
	}

	public function offsetExists(mixed $offset): bool
	{
		return isset($this->arr[$offset]);
	}

	public function offsetGet(mixed $offset): mixed
	{
		return $this->arr[$offset];
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->arr[$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->arr[$offset]);
	}
}

class TestArrayableObject implements Arrayable {
	public function to_array()
	{
		return ['foo' => 'bar'];
	}
}

class TestJsonableObject implements Jsonable {
	public function to_json($options = 0)
	{
		return '{"foo":"bar"}';
	}
}

class TestJsonSerializeObject implements JsonSerializable {
	public function jsonSerialize(): mixed
	{
		return ['foo' => 'bar'];
	}
}

class TestJsonSerializeWithScalarValueObject implements JsonSerializable {
	public function jsonSerialize(): mixed
	{
		return 'foo';
	}
}

class TestCollectionMapIntoObject {
	public mixed $value;

	public function __construct(mixed $value)
	{
		$this->value = $value;
	}
}

class TestCollectionSubclass extends Collection {
	//
}
