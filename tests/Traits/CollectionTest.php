<?php
namespace Michaels\Manager\Test\Traits;

// Originally from https://github.com/laravel/framework/blob/5.0/tests/Support/SupportCollectionTest.php
// Last pulled from upstream 6-3-2015

use Michaels\Manager\Collection as Collection;

use StdClass;

class CollectionTest extends \PHPUnit_Framework_TestCase
{

    public $manager;
    public $testData;
    private $simpleNestData;

    public function setup()
    {
        $this->testData = [
            'one' => [
                'two' => [
                    'three' => 'three-value',
                    'four' => [
                        'five' => 'five-value'
                    ],
                ],
                'six' => [
                    'seven' => 'seven-value',
                    'eight' => 'eight-value'
                ]
            ],
            'top' => 'top-value',
        ];

        $this->simpleNestData = ['one' => ['two' => ['three' => 'three-value']]];

        $this->manager = new Collection();
    }

    private function assertFullManifest($manifest = null, $message = false)
    {
        $expected = ($manifest) ? $manifest : $this->testData;
        $message = ($message) ? $message : 'failed to add nested items';

        $actual = $this->manager->getAll();
        $this->assertEquals($expected, $actual, $message);
    }

    /** Begin Tests **/
    public function testInitializeWithData()
    {
        $manager = new Collection($this->testData);

        $this->assertEquals($this->testData, $manager->getAll(), "Failed to return identical values set at instantiation");
    }

    public function testAddAndGetSingleItem()
    {
        $this->manager->add('alias', 'value');

        $this->assertArrayHasKey('alias', $this->manager->getAll(), 'Failed to confirm that manager has item `alias`');
        $this->assertEquals('value', $this->manager->get('alias'), 'Failed to get a single item');
    }

    public function testAddMultipleItemsAtOnce()
    {
        $this->manager->add([
            'objectTest'    => new StdClass(),
            'closureTest'   => function() {
                return true;
            },
            'stringTest'     => 'value'
        ]);

        $items = $this->manager->getAll();

        $this->assertArrayHasKey('objectTest', $items, 'Failed to confirm that manager has key `objectTest`');
        $this->assertArrayHasKey('closureTest', $items, 'Failed to confirm that manager has key `closureTest`');
        $this->assertArrayHasKey('stringTest', $items, 'Failed to confirm that manager has key `stringTest`');
    }

    public function testReturnTrueIfItemExists()
    {
        $this->manager->add('test', 'test-item');
        $this->manager->add('booleantest', false);

        $this->assertTrue($this->manager->exists('test'), "Failed to confirm that `test` exists");
        $this->assertTrue($this->manager->exists('booleantest'), "Failed to confirm that boolean false value exists");
    }

    public function testReturnFalseIfItemDoesNotExist()
    {
        $this->assertFalse($this->manager->exists('test'));
    }

    /* has() is an alias of exists(), tested here for coverage */
    public function testReturnTrueIfHasItem()
    {
        $this->manager->add('test', 'test-item');
        $this->manager->add('booleantest', false);

        $this->assertTrue($this->manager->has('test'), "Failed to confirm that `test` exists");
        $this->assertTrue($this->manager->has('booleantest'), "Failed to confirm that boolean false value exists");
    }

    public function testReturnFalseIfDoesNotHaveItem()
    {
        $this->assertFalse($this->manager->has('test'));
    }

    public function testProvidesFallbackValue()
    {
        $this->manager->add('one', 'one-value');

        $actual = $this->manager->get('two', 'default-value');

        $this->assertEquals('default-value', $actual, 'failed to return a fallback value');
    }

    /**
     * @expectedException \Michaels\Manager\Exceptions\ItemNotFoundException
     */
    public function testThrowsExceptionIfItemNotFound()
    {
        $this->manager->get('doesntexist');
    }

    public function testUpdateSingleItem()
    {
        $this->manager->add('item', 'original-value');
        $this->manager->set('item', 'new-value');

        $this->assertEquals('new-value', $this->manager->get('item'), 'Failed to update a single item');
    }

    public function testUpdateMultipleItems()
    {
        $this->manager->add('item', 'original-value');
        $this->manager->add('item2', 'other-original-value');
        $this->manager->set(['item' => 'new-value', 'item2' => 'other-new-value']);

        $this->assertEquals('new-value', $this->manager->get('item'), 'Failed to update first item');
        $this->assertEquals('other-new-value', $this->manager->get('item2'), 'Failed to update second item');
    }

    public function testRemoveSingleItem()
    {
        $this->manager->add([
            'one' => 'one',
            'two' => 'two'
        ]);

        $this->manager->remove('one');

        $items = $this->manager->getAll();

        $this->assertArrayNotHasKey('one', $items, 'failed to remove `one`');
        $this->assertArrayHasKey('two', $items, 'failed to leave `two` intact');
    }

    public function testClear()
    {
        $this->manager->add([
            'one'    => 'one',
            'two'     => 'two'
        ]);

        $this->manager->clear();
        $items = $this->manager->getAll();

        $this->assertEmpty($items, "Failed to empty manager");
    }

    public function testAddNestedItems()
    {
        $this->manager->add('one.two.three', 'three-value');
        $this->manager->add('one.two.four.five', 'five-value');
        $this->manager->add('one.six', ['seven' => 'seven-value']);
        $this->manager->add('one.six.eight', 'eight-value');
        $this->manager->add('top', 'top-value');

        $this->assertFullManifest();
    }

    public function testCheckExistenceOfNestedItems()
    {
        $this->manager->add('one.two.three', 'three-value');

        $this->assertFullManifest($this->simpleNestData);
        $this->assertTrue($this->manager->exists('one.two.three'), 'failed to confirm existence of a nested item');
        $this->assertFalse($this->manager->exists('one.two.no'), 'failed to deny existence of a nested item');
    }

    public function testGetNestedItems()
    {
        $this->manager->add('one.two.three', 'three-value');

        $this->assertFullManifest($this->simpleNestData);
        $this->assertEquals('three-value', $this->manager->get('one.two.three'), 'failed to get a single item');
    }

    public function testRemoveNestedItems()
    {
        $this->manager->add('one.two.three', 'three-value');
        $this->manager->add('one.two.four', 'four-value');

        $this->manager->remove('one.two.three');
        $this->manager->remove('does.not.exist');

        $this->assertFullManifest(['one' => ['two' => ['four' => 'four-value']]]);
        $this->assertTrue($this->manager->exists('one.two.four'), 'failed to leave nested item in tact');
        $this->assertFalse($this->manager->exists('one.two.three'), 'failed to remove nested item');
    }

    public function testResetItems()
    {
        $this->manager->add($this->testData);

        $expected = ['reset' => ['me' => ['now']]];

        $this->manager->reset($expected);

        $this->assertEquals($expected, $this->manager->getAll(), "failed to reset manager");
    }

    public function testToJson()
    {
        $this->manager->add($this->testData);

        $expected = json_encode($this->testData);

        $this->assertEquals($expected, $this->manager->toJson(), "failed to serialize json");
    }

    public function testToString()
    {
        $this->manager->add($this->testData);

        $expected = json_encode($this->testData);

        $this->assertEquals($expected, "$this->manager", "failed to return json when called as a string");
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->manager->isEmpty(), "failed to confirm an empty manager");

        $this->manager->add($this->testData);
        $this->assertFalse($this->manager->isEmpty(), "failed to deny an empty manager");
    }

    /**
     * @expectedException \Michaels\Manager\Exceptions\NestingUnderNonArrayException
     */
    public function testThrowExceptionIfTryingToNestUnderANonArray()
    {
        $manager = new Collection(['one' => 1, 'two' => 2]);

        $manager->add("one.two.three", "three-value");
    }

    /**
     * @expectedException \Michaels\Manager\Exceptions\InvalidItemsObjectException
     */
    public function testThrowExceptionIfInitializingWithInvalidItems()
    {
        // The Manager can only be initialized with an array or \Traversable (which is turned into an array)
        $manager = new Collection(3);
    }

    public function testFirstReturnsFirstItemInCollection()
    {
        $c = new Collection(array('foo', 'bar'));
        $this->assertEquals('foo', $c->first());
    }


    public function testLastReturnsLastItemInCollection()
    {
        $c = new Collection(array('foo', 'bar'));

        $this->assertEquals('bar', $c->last());
    }


    public function testPopReturnsAndRemovesLastItemInCollection()
    {
        $c = new Collection(array('foo', 'bar'));

        $this->assertEquals('bar', $c->pop());
        $this->assertEquals('foo', $c->first());
    }


    public function testShiftReturnsAndRemovesFirstItemInCollection()
    {
        $c = new Collection(array('foo', 'bar'));

        $this->assertEquals('foo', $c->shift());
        $this->assertEquals('bar', $c->first());
    }

    public function testFilter()
    {
        $c = new Collection(array(array('id' => 1, 'name' => 'Hello'), array('id' => 2, 'name' => 'World')));
        $this->assertEquals(array(1 => array('id' => 2, 'name' => 'World')), $c->filter(function($item)
        {
            return $item['id'] == 2;
        })->all());
    }


    public function testWhere()
    {
        $c = new Collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);

        $this->assertEquals([['v' => 3]], $c->where('v', 3)->values()->all());
        $this->assertEquals([['v' => 3], ['v' => '3']], $c->whereLoose('v', 3)->values()->all());
    }


    public function testValues()
    {
        $c = new Collection(array(array('id' => 1, 'name' => 'Hello'), array('id' => 2, 'name' => 'World')));
        $this->assertEquals(array(array('id' => 2, 'name' => 'World')), $c->filter(function($item)
        {
            return $item['id'] == 2;
        })->values()->all());
    }


    public function testFlatten()
    {
        $c = new Collection(array(array('#foo', '#bar'), array('#baz')));
        $this->assertEquals(array('#foo', '#bar', '#baz'), $c->flatten()->all());
    }


    public function testMergeArray()
    {
        $c = new Collection(array('name' => 'Hello'));
        $this->assertEquals(array('name' => 'Hello', 'id' => 1), $c->merge(array('id' => 1))->all());
    }


    public function testMergeCollection()
    {
        $c = new Collection(array('name' => 'Hello'));
        $this->assertEquals(array('name' => 'World', 'id' => 1), $c->merge(new Collection(array('name' => 'World', 'id' => 1)))->all());
    }


    public function testDiffCollection()
    {
        $c = new Collection(array('id' => 1, 'first_word' => 'Hello'));
        $this->assertEquals(array('id' => 1), $c->diff(new Collection(array('first_word' => 'Hello', 'last_word' => 'World')))->all());
    }


    public function testIntersectCollection()
    {
        $c = new Collection(array('id' => 1, 'first_word' => 'Hello'));
        $this->assertEquals(array('first_word' => 'Hello'), $c->intersect(new Collection(array('first_world' => 'Hello', 'last_word' => 'World')))->all());
    }


    public function testUnique()
    {
        $c = new Collection(array('Hello', 'World', 'World'));
        $this->assertEquals(array('Hello', 'World'), $c->unique()->all());
    }


    public function testCollapse()
    {
        $data = new Collection(array(array($object1 = new StdClass), array($object2 = new StdClass)));
        $this->assertEquals(array($object1, $object2), $data->collapse()->all());
    }

    public function testCollapseWithNestedCollactions()
    {
        $data = new Collection([new Collection([1, 2, 3]), new Collection([4, 5, 6])]);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $data->collapse()->all());
    }


    public function testSort()
    {
        $data = new Collection(array(5, 3, 1, 2, 4));
        $data->sort(function($a, $b)
        {
            if ($a === $b)
            {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        $this->assertEquals(range(1, 5), array_values($data->all()));
    }


    public function testSortBy()
    {
        $data = new Collection(array('taylor', 'dayle'));
        $data = $data->sortBy(function($x) { return $x; });

        $this->assertEquals(array('dayle', 'taylor'), array_values($data->all()));

        $data = new Collection(array('dayle', 'taylor'));
        $data->sortByDesc(function($x) { return $x; });

        $this->assertEquals(array('taylor', 'dayle'), array_values($data->all()));
    }


    public function testSortByString()
    {
        $data = new Collection(array(array('name' => 'taylor'), array('name' => 'dayle')));
        $data = $data->sortBy('name');

        $this->assertEquals(array(array('name' => 'dayle'), array('name' => 'taylor')), array_values($data->all()));
    }


    public function testReverse()
    {
        $data = new Collection(array('zaeed', 'alan'));
        $reversed = $data->reverse();

        $this->assertEquals(array('alan', 'zaeed'), array_values($reversed->all()));
    }


    public function testFlip()
    {
        $data = new Collection(array('name' => 'taylor', 'framework' => 'laravel'));
        $this->assertEquals(array('taylor' => 'name', 'laravel' => 'framework'), $data->flip()->toArray());
    }


    public function testChunk()
    {
        $data = new Collection(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10));
        $data = $data->chunk(3);

        $this->assertInstanceOf('Illuminate\Support\Collection', $data);
        $this->assertInstanceOf('Illuminate\Support\Collection', $data[0]);
        $this->assertEquals(4, $data->count());
        $this->assertEquals(array(1, 2, 3), $data[0]->toArray());
        $this->assertEquals(array(10), $data[3]->toArray());
    }


    public function testListsWithArrayAndObjectValues()
    {
        $data = new Collection(array((object) array('name' => 'taylor', 'email' => 'foo'), array('name' => 'dayle', 'email' => 'bar')));
        $this->assertEquals(array('taylor' => 'foo', 'dayle' => 'bar'), $data->lists('email', 'name'));
        $this->assertEquals(array('foo', 'bar'), $data->lists('email'));
    }

    public function testImplode()
    {
        $data = new Collection([['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);
        $this->assertEquals('foobar', $data->implode('email'));
        $this->assertEquals('foo,bar', $data->implode('email', ','));

        $data = new Collection(['taylor', 'dayle']);
        $this->assertEquals('taylordayle', $data->implode(''));
        $this->assertEquals('taylor,dayle', $data->implode(','));
    }


    public function testTake()
    {
        $data = new Collection(array('taylor', 'dayle', 'shawn'));
        $data = $data->take(2);
        $this->assertEquals(array('taylor', 'dayle'), $data->all());
    }


    public function testRandom()
    {
        $data = new Collection(array(1, 2, 3, 4, 5, 6));
        $random = $data->random();
        $this->assertInternalType('integer', $random);
        $this->assertContains($random, $data->all());
        $random = $data->random(3);
        $this->assertCount(3, $random);
    }


    public function testRandomOnEmpty()
    {
        $data = new Collection();
        $random = $data->random();
        $this->assertNull($random);
    }


    public function testTakeLast()
    {
        $data = new Collection(array('taylor', 'dayle', 'shawn'));
        $data = $data->take(-2);
        $this->assertEquals(array('dayle', 'shawn'), $data->all());
    }


    public function testTakeAll()
    {
        $data = new Collection(array('taylor', 'dayle', 'shawn'));
        $data = $data->take();
        $this->assertEquals(array('taylor', 'dayle', 'shawn'), $data->all());
    }


    public function testMakeMethod()
    {
        $collection = Collection::make('foo');
        $this->assertEquals(array('foo'), $collection->all());
    }


    public function testMakeMethodFromNull()
    {
        $collection = Collection::make(null);
        $this->assertEquals([], $collection->all());

        $collection = Collection::make();
        $this->assertEquals([], $collection->all());
    }

    public function testMakeMethodFromCollection()
    {
        $firstCollection = Collection::make(['foo' => 'bar']);
        $secondCollection = Collection::make($firstCollection);
        $this->assertEquals(['foo' => 'bar'], $secondCollection->all());
    }


    public function testMakeMethodFromArray()
    {
        $collection = Collection::make(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }

    public function testConstructMakeFromObject()
    {
        $object = new stdClass();
        $object->foo = 'bar';
        $collection = Collection::make($object);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }


    public function testConstructMethod()
    {
        $collection = new Collection('foo');
        $this->assertEquals(array('foo'), $collection->all());
    }


    public function testConstructMethodFromNull()
    {
        $collection = new Collection(null);
        $this->assertEquals([], $collection->all());

        $collection = new Collection();
        $this->assertEquals([], $collection->all());
    }


    public function testConstructMethodFromCollection()
    {
        $firstCollection = new Collection(['foo' => 'bar']);
        $secondCollection = new Collection($firstCollection);
        $this->assertEquals(['foo' => 'bar'], $secondCollection->all());
    }


    public function testConstructMethodFromArray()
    {
        $collection = new Collection(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }


    public function testConstructMethodFromObject()
    {
        $object = new stdClass();
        $object->foo = 'bar';
        $collection = new Collection($object);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }


    public function testSplice()
    {
        $data = new Collection(array('foo', 'baz'));
        $data->splice(1, 0, 'bar');
        $this->assertEquals(array('foo', 'bar', 'baz'), $data->all());

        $data = new Collection(array('foo', 'baz'));
        $data->splice(1, 1);
        $this->assertEquals(array('foo'), $data->all());

        $data = new Collection(array('foo', 'baz'));
        $cut = $data->splice(1, 1, 'bar');
        $this->assertEquals(array('foo', 'bar'), $data->all());
        $this->assertEquals(array('baz'), $cut->all());
    }

    public function testTransform()
    {
        $data = new Collection(array('taylor', 'colin', 'shawn'));
        $data->transform(function($item) { return strrev($item); });
        $this->assertEquals(array('rolyat', 'niloc', 'nwahs'), array_values($data->all()));
    }


    public function testFirstWithCallback()
    {
        $data = new Collection(array('foo', 'bar', 'baz'));
        $result = $data->first(function($key, $value) { return $value === 'bar'; });
        $this->assertEquals('bar', $result);
    }


    public function testFirstWithCallbackAndDefault()
    {
        $data = new Collection(array('foo', 'bar'));
        $result = $data->first(function($key, $value) { return $value === 'baz'; }, 'default');
        $this->assertEquals('default', $result);
    }


    public function testGroupByAttribute()
    {
        $data = new Collection(array(array('rating' => 1, 'url' => '1'), array('rating' => 1, 'url' => '1'), array('rating' => 2, 'url' => '2')));

        $result = $data->groupBy('rating');
        $this->assertEquals(array(1 => array(array('rating' => 1, 'url' => '1'), array('rating' => 1, 'url' => '1')), 2 => array(array('rating' => 2, 'url' => '2'))), $result->toArray());

        $result = $data->groupBy('url');
        $this->assertEquals(array(1 => array(array('rating' => 1, 'url' => '1'), array('rating' => 1, 'url' => '1')), 2 => array(array('rating' => 2, 'url' => '2'))), $result->toArray());
    }


    public function testKeyByAttribute()
    {
        $data = new Collection([['rating' => 1, 'name' => '1'], ['rating' => 2, 'name' => '2'], ['rating' => 3, 'name' => '3']]);

        $result = $data->keyBy('rating');
        $this->assertEquals([1 => ['rating' => 1, 'name' => '1'], 2 => ['rating' => 2, 'name' => '2'], 3 => ['rating' => 3, 'name' => '3']], $result->all());

        $result = $data->keyBy(function($item){ return $item['rating'] * 2; });
        $this->assertEquals([2 => ['rating' => 1, 'name' => '1'], 4 => ['rating' => 2, 'name' => '2'], 6 => ['rating' => 3, 'name' => '3']], $result->all());
    }


    public function testContains()
    {
        $c = new Collection([1, 3, 5]);

        $this->assertTrue($c->contains(1));
        $this->assertFalse($c->contains(2));
        $this->assertTrue($c->contains(function($value) { return $value < 5; }));
        $this->assertFalse($c->contains(function($value) { return $value > 5; }));

        $c = new Collection([['v' => 1], ['v' => 3], ['v' => 5]]);

        $this->assertTrue($c->contains('v', 1));
        $this->assertFalse($c->contains('v', 2));

        $c = new Collection(['date', 'class', (object) ['foo' => 50]]);

        $this->assertTrue($c->contains('date'));
        $this->assertTrue($c->contains('class'));
        $this->assertFalse($c->contains('foo'));
    }


    public function testGettingSumFromCollection()
    {
        $c = new Collection(array((object) array('foo' => 50), (object) array('foo' => 50)));
        $this->assertEquals(100, $c->sum('foo'));

        $c = new Collection(array((object) array('foo' => 50), (object) array('foo' => 50)));
        $this->assertEquals(100, $c->sum(function($i) { return $i->foo; }));
    }


    public function testCanSumValuesWithoutACallback()
    {
        $c = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(15, $c->sum());
    }


    public function testGettingSumFromEmptyCollection()
    {
        $c = new Collection();
        $this->assertEquals(0, $c->sum('foo'));
    }


    public function testValueRetrieverAcceptsDotNotation()
    {
        $c = new Collection(array(
            (object) array('id' => 1, 'foo' => array('bar' => 'B')), (object) array('id' => 2, 'foo' => array('bar' => 'A'))
        ));

        $c = $c->sortBy('foo.bar');
        $this->assertEquals(array(2, 1), $c->lists('id'));
    }


    public function testPullRetrievesItemFromCollection()
    {
        $c = new Collection(array('foo', 'bar'));

        $this->assertEquals('foo', $c->pull(0));
    }


    public function testPullRemovesItemFromCollection()
    {
        $c = new Collection(array('foo', 'bar'));
        $c->pull(0);
        $this->assertEquals(array(1 => 'bar'), $c->all());
    }


    public function testPullReturnsDefault()
    {
        $c = new Collection(array());
        $value = $c->pull(0, 'foo');
        $this->assertEquals('foo', $value);
    }


    public function testRejectRemovesElementsPassingTruthTest()
    {
        $c = new Collection(['foo', 'bar']);
        $this->assertEquals(['foo'], $c->reject('bar')->values()->all());

        $c = new Collection(['foo', 'bar']);
        $this->assertEquals(['foo'], $c->reject(function($v) { return $v == 'bar'; })->values()->all());

        $c = new Collection(['foo', null]);
        $this->assertEquals(['foo'], $c->reject(null)->values()->all());

        $c = new Collection(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $c->reject('baz')->values()->all());

        $c = new Collection(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $c->reject(function($v) { return $v == 'baz'; })->values()->all());
    }


    public function testSearchReturnsIndexOfFirstFoundItem()
    {
        $c = new Collection([1, 2, 3, 4, 5, 2, 5, 'foo' => 'bar']);

        $this->assertEquals(1, $c->search(2));
        $this->assertEquals('foo', $c->search('bar'));
        $this->assertEquals(4, $c->search(function($value){ return $value > 4; }));
        $this->assertEquals('foo', $c->search(function($value){ return ! is_numeric($value); }));
    }


    public function testSearchReturnsFalseWhenItemIsNotFound()
    {
        $c = new Collection([1, 2, 3, 4, 5, 'foo' => 'bar']);

        $this->assertFalse($c->search(6));
        $this->assertFalse($c->search('foo'));
        $this->assertFalse($c->search(function($value){ return $value < 1 && is_numeric($value); }));
        $this->assertFalse($c->search(function($value){ return $value == 'nope'; }));
    }


    public function testKeys()
    {
        $c = new Collection(array('name' => 'taylor', 'framework' => 'laravel'));
        $this->assertEquals(array('name', 'framework'), $c->keys()->all());
    }


    public function testPaginate()
    {
        $c = new Collection(['one', 'two', 'three', 'four']);
        $this->assertEquals(['one', 'two'], $c->forPage(1, 2)->all());
        $this->assertEquals(['three', 'four'], $c->forPage(2, 2)->all());
        $this->assertEquals([], $c->forPage(3, 2)->all());
    }
}