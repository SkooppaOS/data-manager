<?php
namespace Michaels\Manager\Test\Traits;

use Michaels\Manager\IocManager as Manager;
use Michaels\Manager\Test\Stubs\DependencyFactoryStub;
use StdClass;

class ManagesIocTest extends \PHPUnit_Framework_TestCase
{
    public $manager;
    public $testData;

    public function setUp()
    {
        $object = new stdClass();
        $object->type = 'object';

        $this->testData = [
            'string' => '\SplObjectStorage', // Used here for overhead
            'callable' => function () {
                $return = new stdClass();
                $return->type = 'callable';
                return $return;
            },
            'object' => $object
        ];
    }

    /** Begin Tests **/
    public function testInitIocContainer()
    {
        $manager = new Manager();
        $manager->initDi($this->testData);

        $this->assertEquals($this->testData, $manager->get('_diManifest'), "Failed to return di manifest");
    }

    // THIS IS NOT PART OF THE TRAIT, ONLY THE CONCRETE CLASS. Tested here to save time.
    public function testInitViaConstructor()
    {
        $manager = new Manager($this->testData, ['other' => ['items' => true]]);
        $this->assertTrue($manager->get("other.items"), "failed to set generic items");
        $this->assertEquals($this->testData, $manager->get('_diManifest'), "Failed to return di manifest");
    }

    public function testGetIocManifest()
    {
        $manager = new Manager($this->testData);

        $this->assertEquals($this->testData, $manager->getIocManifest(), "Failed to return di manifest");
    }

    public function testGetEmptyManifestIfUninitialized()
    {
        $manager = new Manager();

        $this->assertEquals([], $manager->getIocManifest(), "Failed to return di manifest");
    }

    public function testAddDependencies()
    {
        $manager = new Manager();

        foreach ($this->testData as $key => $value) {
            $manager->di($key, $value);
        }

        $this->assertEquals($this->testData, $manager->get('_diManifest'), "Failed to return di manifest");
    }

    public function testFetchDependencies()
    {
        $manager = new Manager();

        $manager->initDi($this->testData);

        $string = $manager->fetch('string'); // Should return Manager
        $callable = $manager->fetch('callable'); // Should return stdClass::type = callable
        $object = $manager->fetch('object'); // Should return stdClass::type = object

        $this->assertInstanceOf('\SplObjectStorage', $string, "Failed to return string factory");
        $this->assertEquals('callable', $callable->type, 'Failed to return callable factory');
        $this->assertEquals('object', $object->type, "Failed to return object factory");
    }

    public function testManagerInstanceAsFactory()
    {
        $factory = new Manager([
            'container' => '\stdClass',
        ]);

        $manager = new Manager([
            'container' => $factory
        ]);

        $actual= $manager->fetch('container'); // Should return Manager

        $this->assertInstanceOf('\stdClass', $actual, "failed to produce from a string");
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionOnInvalidFactory()
    {
        $manager = new Manager();

        $manager->di('one', true);

        $manager->fetch('one');
    }

    public function testShareObject()
    {
        // Setup
        $manager = new Manager();
        $object = new stdClass();
        $object->prop = 'property';

        $manager->initDi([
            'object' => $object
        ]);

        // Share and modify
        $manager->share('object');
        $manager->fetch('object')->prop = 'new-object-value';

        // Get the modified object back
        $actual = $manager->fetch('object');
        $this->assertEquals('new-object-value', $actual->prop, "failed to share string");
    }

    public function testShareString()
    {
        // Setup
        $manager = new Manager();
        $manager->initDi([
            'string' => 'stdClass'
        ]);

        // Share and modify
        $manager->share('string');
        $manager->fetch('string')->prop = 'new-string-value';

        // Get the shared back
        $actual = $manager->fetch('string');
        $this->assertEquals('new-string-value', $actual->prop, "failed to share string");
    }

    public function testShareFactory()
    {
        $manager = new Manager();
        $manager->initDi([
            'factory' => function () {
                return new stdClass();
            }
        ]);
        $manager->share('factory');

        $manager->fetch('factory')->prop = 'new-factory-value';

        $this->assertEquals('new-factory-value', $manager->fetch('factory')->prop, "failed to share string");
    }

    public function testFactoryManagerInjection()
    {
        $manager = new Manager();
        $email = new stdClass();
        $email->test = 'testing';

        // Register dependencies
        $manager->di('email', $email);

        $manager->di('logger', function ($di) {
            $logger = new stdClass();
            $logger->email = $di->fetch('email');
            return $logger;
        });

        $manager->di('application', function ($di) {
            $application = new stdClass();
            $application->logger = $di->fetch('logger');
            return $application;
        });

        $application = $manager->fetch('application');

        $this->assertEquals($email, $application->logger->email, "failed to set dependencies down the chain");
    }

    public function testFallbacks()
    {
        $manager = new Manager();
        $fallback = new stdClass();
        $fallback->testing = "yes";

        $actual = $manager->fetch('notset', $fallback);

        $this->assertEquals($fallback, $actual, "failed to return fallback");
    }

    public function testPrepareDependencies()
    {
        $manager = new Manager();
        $manager->di('prepared', new stdClass());
        $manager->di('unprepared', new stdClass());

        $manager->setup('prepared', function ($object, $manager) {
            $object->prepared = 'yes';
            return $object;
        });

        $this->assertEquals('yes', $manager->fetch('prepared')->prepared, "failed to prepare object");
        $this->assertFalse(isset($manager->fetch('unprepared')->prepared), "failed to return unprepared object");
    }

    public function testDeclaringDependenciesWithClassnames()
    {
        $manager = new Manager();

        // Setup the dependencies
        $one = new stdClass();
        $one->a = "A";

        $two = new stdClass();
        $two->b = "B";

        $manager->di('one', $one);
        $manager->di('two', $two);

        // Declare the one that needs dependencies
        $manager->di('three', 'Michaels\Manager\Test\Stubs\DependencyFactoryStub', ['one', 'two', true]);

        $three = $manager->fetch('three');

        $this->assertEquals("A", $three->one->a, "failed to set the first dependency");
        $this->assertEquals("B", $three->two->b, "failed to set the second dependency");
        $this->assertEquals(true, $three->three, "failed to set the passed argument");
    }

    public function testDeclaringDependenciesWithObjects()
    {
        $manager = new Manager();

        // Setup the dependencies
        $one = new stdClass();
        $one->a = "A";

        $two = new stdClass();
        $two->b = "B";

        $manager->di('one', $one);
        $manager->di('two', $two);

        // Declare the one that needs dependencies
        $manager->di('three', new DependencyFactoryStub(), ['one', 'two', true]);

        $three = $manager->fetch('three');

        $this->assertEquals("A", $three->one->a, "failed to set the first dependency");
        $this->assertEquals("B", $three->two->b, "failed to set the second dependency");
        $this->assertEquals(true, $three->three, "failed to set the passed argument");
    }

    public function testDeclaringDependenciesWithClosures()
    {
        $manager = new Manager();

        // Setup the dependencies
        $one = new stdClass();
        $one->a = "A";

        $two = new stdClass();
        $two->b = "B";

        $manager->di('one', $one);
        $manager->di('two', $two);

        // Declare the one that needs dependencies
        $manager->di('three', function ($di, $one, $two, $three) {
            return new DependencyFactoryStub($one, $two, $three);
        }, ['one', 'two', true]);

        $three = $manager->fetch('three');

        $this->assertEquals("A", $three->one->a, "failed to set the first dependency");
        $this->assertEquals("B", $three->two->b, "failed to set the second dependency");
        $this->assertEquals(true, $three->three, "failed to set the passed argument");
    }

    public function testGetAndSetItemsName()
    {
        $manager = new Manager();
        $this->assertEquals("_diManifest", $manager->getDiItemsName(), "failed to retrieve default manifest name");

        $manager->setDiItemsName("test");
        $this->assertEquals("test", $manager->getDiItemsName(), "failed to retrieve new manifest name");

        $manager->di('class', '\stdClass');
        $object = $manager->fetch('class');

        $this->assertInstanceOf('\stdClass', $object, "failed to return correct object");
    }

    /**
     * @expectedException \Michaels\Manager\Exceptions\ItemNotFoundException
     */
    public function testThrowsExceptionForNoItemSet()
    {
        $manager = new Manager();
        $manager->fetch('nothing_set');
    }

    public function testComplexExample()
    {
        /* Create some managers */
        $firstManager = new Manager(
            ['first' => '\stdClass'],
            ['one' => ['two' => ['three' => 'three-value']]]
        );

        $secondManager = new Manager();
        $secondManager->setDiItemsName("test");

        foreach ($this->testData as $key => $value) {
            $secondManager->di($key, $value);
        }
        $secondManager->di('first', $firstManager);

        /* Use a pipeline */
        $secondManager->setup('object', function ($object, $m) {
            $object->objectSetupProp = 'objectSetupProp';
            return $object;
        });

        $secondManager->setup('second', function ($object, $m) {
            $return = new stdClass();
            $return->second = $object;
            return $return;
        });

        $secondManager->setup('string', function ($object, $m) {
            $object->stringSetupProp = 'stringSetupProp';
            return $object;
        });

        /* Create some singletons */
        $secondManager->share('object');
        $secondManager->fetch('object')->objectProp = 'objectProp';

        $secondManager->share('first');
        $secondManager->fetch('first')->firstProp = 'firstProp';

        /* Declare some dependencies */
        $secondManager->di('third', function ($di, $one, $two, $three) {
            return new DependencyFactoryStub($one, $two, $three);
        }, ['first', 'string', true]);

        $secondManager->di('second', new DependencyFactoryStub(), ['third', 'callable']);

        /* Use a fallback */
        $fallback = new stdClass();
        $fallback->testing = "yes";

        /* Assertions */
        /*
         * A Table of dependencies
         * ------------------------------------------------------------------------
         *          name        type    Notes
         * ------------------------------------------------------------------------
         * shared   first     string    stdClass, through $firstManager
         *                              $firstManager['first']
         *                              $first->firstProp = 'firstProp'
         * pipe     second    object    $firstManager['second']
         *                              Instance of DepInjStub
         *                              Dependencies: [one => third, two => callable],
         *                              Pipeline wraps as $newObject
         *                              $newObject->second->one->one (first)
         *                              $newObject->second->one->one->firstProp = 'firstProp'
         *--------------------------------------------------------------------------
         * pipe     string      string  SplObjectStorage
         *                              Pipeline adds ->stringSetupProp = 'stringSetupProp'
         *          callable    closure $object->type = 'callable'
         * share    object      object  $object->type = 'object'
         * pipe                         $object->objectSetupProp = 'objectSetupProp'
         *                              $object->objectProp = 'objectProp' (from shared)
         *--------------------------------------------------------------------------
         *          third       closure Dependencies: [one => first, two => string, three => true]
         *                                            $third->one->firstProp = 'firstProp'
         */
        // First
        $this->assertInstanceOf('\stdClass', $secondManager->fetch('first'), "failed to return correct `first`");
        $this->assertEquals('firstProp', $secondManager->fetch('first')->firstProp, "failed to share `first`");

        // Second
        $this->assertInstanceOf('stdClass', $secondManager->fetch('second'), "failed to return pipelined `second`");
        $this->assertInstanceOf('Michaels\Manager\Test\Stubs\DependencyFactoryStub', $secondManager->fetch('second')->second, "failed to return correct `second`");
        $this->assertInstanceOf('Michaels\Manager\Test\Stubs\DependencyFactoryStub', $secondManager->fetch('second')->second->one, "failed to return correct `second` with `third` dependency");
        $this->assertEquals('firstProp', $secondManager->fetch('second')->second->one->one->firstProp, "failed to set correct `third` dependency chain");

        // Standards
        $string = $secondManager->fetch('string');
        $callable = $secondManager->fetch('callable');
        $object = $secondManager->fetch('object');

        $this->assertInstanceOf('SplObjectStorage', $string, "Failed to return string factory");
        $this->assertEquals('stringSetupProp', $string->stringSetupProp, "failed to pipeline string");
        $this->assertEquals('callable', $callable->type, 'Failed to return callable factory');
        $this->assertEquals('object', $object->type, "Failed to return object factory");
        $this->assertEquals('objectSetupProp', $object->objectSetupProp, "failed to pipeline object");
        $this->assertEquals('objectProp', $object->objectProp, "failed to share object");

        // Multi-level Dependencies [one => first, two => string, three => true]
        $third = $secondManager->fetch('third');
        $this->assertInstanceOf('Michaels\Manager\Test\Stubs\DependencyFactoryStub', $third, "Failed to return the correct `third`");

        $this->assertInstanceOf('\stdClass', $secondManager->fetch('third')->one, "failed to return correct `first`");
        $this->assertEquals('firstProp', $secondManager->fetch('third')->one->firstProp, "failed to share `first`");

        $this->assertInstanceOf('SplObjectStorage', $secondManager->fetch('third')->two, "Failed to return string factory");
        $this->assertEquals('stringSetupProp', $secondManager->fetch('third')->two->stringSetupProp, "failed to pipeline string");

        $this->assertTrue($secondManager->fetch('third')->three);

        /* Test it does not interfere with normal Manager */
        $this->assertEquals('three-value', $firstManager->get('one.two.three'), "Failed to return normal items");

        $firstManager->set('one.two.four', 'four-value');
        $this->assertEquals('four-value', $firstManager->get('one.two.four'), "Failed to return normal items");

        /* Test IoC Situations */
        // Fallback
        $this->assertEquals($fallback, $secondManager->fetch('notset', $fallback), "failed to return fallback");

    }
}