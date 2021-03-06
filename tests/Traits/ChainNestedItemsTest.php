<?php
namespace Michaels\Manager\Test;

use Michaels\Manager\Test\Stubs\ChainsNestedItemsTraitStub;

class ChainsNestedItemsTest extends \PHPUnit_Framework_TestCase {

    private $manager;
    private $testData;

    public function setup()
    {
        $this->testData = [
            'one' => [
                'two' => [
                    'three' => 'three-value'
                ],
                'two_b' => 'two_b-value'
            ]
        ];

        $this->manager = new ChainsNestedItemsTraitStub();
        $this->manager->initManager($this->testData);
    }

    public function testGetSingleItem()
    {
        $this->manager->add('key', 'value');

        $this->assertEquals('value', $this->manager->key, 'failed to return a single item through magic method');
    }

    public function testGetThroughNestedMagicMethods()
    {
        $expectedA = $this->testData['one']['two']['three'];
        $actualA = $this->manager->one()->two()->three;

        $expectedB = $this->testData['one']['two_b'];
        $actualB = $this->manager->one()->two_b;

        $expectedC = $this->testData['one'];
        $actualC = $this->manager->one;

        $this->assertEquals($expectedA, $actualA, 'failed to retrieve first nested value');
        $this->assertEquals($expectedB, $actualB, 'failed to retrieve second nested value');
        $this->assertEquals($expectedC, $actualC, 'failed to retrieve third nested value');
    }

    public function testDoesntSetLevelForValidMethodCall() {
        $this->manager->someMethod();
        $this->manager->one = "one";
        $this->assertEquals("one", $this->manager->get('one'));
        $this->assertEquals("one", $this->manager->one);
        $this->assertFalse($this->manager->has('someMethod'));
    }

    public function testAddTopLevelItemThroughMagicMethod(){
        $this->manager->one = "one";
        $this->assertEquals("one", $this->manager->get('one'));
        $this->assertEquals("one", $this->manager->one);
    }

    public function testAddNestedItemThroughMagicMethod() {
        $this->manager->one()->two()->three = "three";
        $this->assertEquals("three", $this->manager->get('one.two.three'));
        $this->assertEquals("three", $this->manager->one()->two()->three);
    }

    public function testAddNestedItemToArrayThroughMagicMethod() {
        $this->manager->one()->two = [];
        $this->manager->one()->two()->three()->four = "four";

        $this->assertEquals("four", $this->manager->get('one.two.three.four'));
        $this->assertEquals("four", $this->manager->one()->two()->three()->four);

        $this->manager->five()->six = [
            'seven' => [
                'eight' => 'eight',
            ]
        ];
        $this->manager->five()->six()->seven()->nine = "nine";

        $this->assertEquals("eight", $this->manager->get('five.six.seven.eight'));
        $this->assertEquals("nine", $this->manager->five()->six()->seven()->nine);
    }

    public function testUpdateNestedItemThroughMagicMethod() {
        $this->manager->one()->two = [];
        $this->manager->one()->two()->three()->four = "four";

        $this->manager->one()->two()->three()->four = "new-four";

        $this->assertEquals("new-four", $this->manager->get('one.two.three.four'), "failed to update property");
    }

    public function testDeleteNestedItemThroughMagicMethod() {
        $this->manager->one()->two = 'two';
        $this->manager->one()->three = 'three';

        $this->manager->one()->three()->drop();
        $this->assertFalse($this->manager->has('one.three'), 'failed to remove property');
        $this->assertTrue($this->manager->has('one.two'), 'removed wrong property');
    }
}

