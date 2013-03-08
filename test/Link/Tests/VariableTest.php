<?php
/**
 * This file is part of Link TPL.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Copyleft (c) 2007+, Baptiste Clavié, Talus' Works
 * @link      http://www.talus-works.net Talus' Works
 * @license   http://www.opensource.org/licenses/BSD-3-Clause Modified BSD License
 * @version   $Id$
 */

/**
 * Tests the variable's handler
 *
 * @package Link.test
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 *
 */
class Link_Tests_VariableTest extends PHPUnit_Framework_TestCase {
    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::__toString
     * @covers Link_Variable::getValue
     * @covers Link_Variable::setValue
     * @covers Link_Variable::toSelf
     */
    public function testValue() {
        $var = new Link_Variable('test');

        $this->assertEquals('test', $var->getValue());
        $this->assertEquals('test', (string) $var);
    }

    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::__toString
     * @covers Link_Variable::offsetGet
     * @covers Link_Variable::getValue
     * @covers Link_Variable::setValue
     * @covers Link_Variable::toSelf
     * @covers Link_Variable::count
     *
     * @expectedException        PHPUnit_Framework_Error_Notice
     * @expectedExceptionMessage The offset "1" is not defined for this variable
     */
    public function testOffsetGet() {
        $var = new Link_Variable(array('a'));

        $this->assertInternalType('array', $var->getValue());

        $this->assertCount(1, $var);
        $this->assertEquals('a', $var[0]);

        $this->assertNull($var[1]);
    }

    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::__toString
     * @covers Link_Variable::offsetSet
     * @covers Link_Variable::getValue
     * @covers Link_Variable::setValue
     * @covers Link_Variable::toSelf
     *
     * @expectedException        Link_Exception_Runtime
     * @expectedExceptionMessage This variable is not an array, or a string with a numeric offset
     */
    public function testOffsetSet() {
        $var = new Link_Variable(array());

        $var[0] = 'a';
        $var[] = 'b';

        $this->assertCount(2, $var);
        $this->assertEquals('a', $var[0]);
        $this->assertEquals('b', $var[1]);

        $var->setValue(new ObjectTest);
        $var->offsetSet(0, null);

        $this->fail('Should not get here');
    }

    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::__toString
     * @covers Link_Variable::getValue
     * @covers Link_Variable::setValue
     * @covers Link_Variable::toSelf
     * @covers Link_Variable::__get
     */
    public function testPropertyGet() {
        $var = new Link_Variable(new ObjectTest);

        $this->assertInternalType('object', $var->getValue());

        $this->assertInternalType('string', $var->publicScalar);
        $this->assertInternalType('array', $var->publicArray);

        $this->assertEquals('a', $var->publicScalar);

        $this->assertCount(2, $var->publicArray);
        $this->assertEquals('a', $var->publicArray[0]);
        $this->assertEquals('b', $var->publicArray[1]);
    }

    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::__toString
     * @covers Link_Variable::getValue
     * @covers Link_Variable::setValue
     * @covers Link_Variable::toSelf
     * @covers Link_Variable::__set
     */
    public function testPropertySet() {
        $var = new Link_Variable(new ObjectTest);

        $var->publicScalar = 'b';

        $this->assertEquals('b', $var->publicScalar);
    }

    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::__toString
     * @covers Link_Variable::getValue
     * @covers Link_Variable::setValue
     * @covers Link_Variable::toSelf
     * @covers Link_Variable::__get

     * @expectedException        Link_Exception_Runtime
     * @expectedExceptionMessage Property "ObjectTest::$noAccessor" is not defined or accessible
     */
    public function testPropertyGetter() {
        $var = new Link_Variable(new ObjectTest);

        $this->assertInternalType('object', $var->getValue());

        $this->assertInternalType('null', $var->withAccessor);
        $this->assertNull($var->withAccessor);

        $var->noAccessor;
        $this->fail('Should not get here !');
    }

    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::__toString
     * @covers Link_Variable::getValue
     * @covers Link_Variable::setValue
     * @covers Link_Variable::toSelf
     * @covers Link_Variable::__set

     * @expectedException        Link_Exception_Runtime
     * @expectedExceptionMessage Property "ObjectTest::$noAccessor" is not accessible
     */
    public function testPropertySetter() {
        $var = new Link_Variable(new ObjectTest);

        $var->withAccessor = 'a';

        $this->assertInternalType('object', $var->getValue());
        $this->assertInstanceOf('ObjectTest', $var->getValue());

        $this->assertInternalType('string', $var->withAccessor);
        $this->assertNotNull($var->withAccessor);
        $this->assertEquals('a', $var->withAccessor);

        $var->noAccessor = 'a';
        $this->fail('Should not get here !');
    }

    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::getValue
     * @covers Link_Variable::setValue
     * @covers Link_Variable::__call
     *
     * @expectedException        Link_Exception_Runtime
     * @expectedExceptionMessage Method "ObjectTest::aPrivateMethod()" is not accessible or not defined.
     */
    public function testMethodCaller() {
        $var = new Link_Variable(new ObjectTest);

        $this->assertInternalType('array', $var->aMethod());
        $this->assertEquals(array('a', 'b'), $var->aMethod('a', 'b'));

        $var->aPrivateMethod();
        $this->fail('Should not be here !');
    }

    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::offsetExists
     * @covers Link_Variable::setValue
     * @covers Link_Variable::toSelf
     */
    public function testOffsetExists() {
        $var = new Link_Variable(array('a' => 'b', 'c' => 'd'));

        $this->assertTrue(isset($var['a']));
        $this->assertFalse(isset($var['e']));
    }

    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::offsetUnset
     * @covers Link_Variable::setValue
     * @covers Link_Variable::toSelf
     */
    public function testOffsetUnset() {
        $var = new Link_Variable(array('a' => 'b', 'c' => 'd'));

        $this->assertTrue(isset($var['a']));

        unset($var['a']);

        $this->assertFalse(isset($var['a']));
    }

    /**
     * @covers Link_Variable::__construct
     * @covers Link_Variable::getIterator
     * @covers Link_Variable::getValue
     * @covers Link_Variable::setValue
     * @covers Link_Variable::toSelf
     *
     * @expectedException        PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage This variable is not iterable
     */
    public function testIterators() {
        $iterator = new Link_Variable(new Link_Variable(array()));
        $array    = new Link_Variable(array());
        $none     = new Link_Variable(null);

        $this->assertInstanceOf('IteratorIterator', $iterator->getIterator());
        $this->assertInstanceOf('ArrayIterator', $array->getIterator());
        $this->assertInstanceOf('EmptyIterator', $none->getIterator());
    }
}

/**
 * Anonymous object, just for the tests
 *
 * @package Link.test
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class ObjectTest {
    public $publicScalar = 'a';
    public $publicArray  = array('a', 'b');

    private $withAccessor = null;
    private $noAccessor   = null;

    public function getWithAccessor() {
        return $this->withAccessor;
    }

    public function setWithAccessor($value) {
        $this->withAccessor = $value;
    }

    public function aMethod($arg1 = null, $arg2 = null) {
        return func_get_args();
    }

    private function aPrivateMethod() {
        return null;
    }
}
