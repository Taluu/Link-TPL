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
    /** @covers Link_Variable::toSelf */
    public function testValue() {
        $var = new Link_Variable('test');

        $this->assertEquals('test', $var->getValue());
        $this->assertEquals('test', (string) $var);
    }

    /**
     * @covers Link_Variable::offsetGet
     * @covers Link_Variable::count
     */
    public function testOffsetGet() {
        $var = new Link_Variable(array('a'));

        $this->assertInternalType('array', $var->getValue());
        $this->assertInstanceOf('Link_Variable', $var[0]);

        $this->assertCount(1, $var);
        $this->assertEquals('a', (string) $var[0]);
    }

    /** @covers Link_Variable::offsetSet */
    public function testOffsetSet() {
        $var = new Link_Variable(array());

        $var[0] = 'a';
        $var[] = 'b';

        $this->assertInstanceOf('Link_Variable', $var[0]);
        $this->assertInstanceOf('Link_Variable', $var[1]);

        $this->assertCount(2, $var);
        $this->assertEquals('a', (string) $var[0]);
        $this->assertEquals('b', (string) $var[1]);
    }

    /** @covers Link_Variable::__get */
    public function testPropertyGet() {
        $var = new Link_Variable(new ObjectTest);

        $this->assertInternalType('object', $var->getValue());
        $this->assertInstanceOf('Link_Variable', $var->publicScalar);
        $this->assertInstanceOf('Link_Variable', $var->publicArray);

        $this->assertInternalType('string', $var->publicScalar->getValue());
        $this->assertInternalType('array', $var->publicArray->getValue());

        $this->assertEquals('a', $var->publicScalar->getValue());
        $this->assertEquals('a', (string) $var->publicScalar);

        $this->assertCount(2, $var->publicArray);
        $this->assertEquals('a', $var->publicArray[0]);
        $this->assertEquals('b', $var->publicArray[1]);
    }

    /** @covers Link_Variable::__set */
    public function testPropertySet() {
        $var = new Link_Variable(new ObjectTest);

        $var->newProp = 'a';

        $this->assertInstanceOf('Link_Variable', $var->newProp);

        $this->assertEquals('a', (string) $var->newProp);
    }

    /**
     * @covers Link_Variable::__get
     * @expectedException Link_Exception_Runtime
     */
    public function testPropertyGetter() {
        $var = new Link_Variable(new ObjectTest);

        $this->assertInternalType('object', $var->getValue());
        $this->assertInstanceOf('Link_Variable', $var->withAccessor);

        $this->assertInternalType('null', $var->withAccessor->getValue());
        $this->assertNull($var->withAccessor->getValue());

        $this->assertInstanceOf('Link_Variable', $var->noAccessor);
        $this->fail('Should not get here !');
    }

    /**
     * @covers Link_Variable::__set
     * @expectedException Link_Exception_Runtime
     */
    public function testPropertySetter() {
        $var = new Link_Variable(new ObjectTest);

        $var->withAccessor = 'a';

        $this->assertInternalType('object', $var->getValue());
        $this->assertInstanceOf('ObjectTest', $var->getValue());

        $this->assertInternalType('string', $var->withAccessor->getValue());
        $this->assertNotNull($var->withAccessor->getValue());
        $this->assertEquals('a', $var->withAccessor->getValue());

        $var->noAccessor = 'a';
        $this->fail('Should not get here !');
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
}