<?php
/**
 * This file is part of Link TPL.
 * 
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Copyleft (c) 2007+, Baptiste Clavié, Talus' Works
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.opensource.org/licenses/BSD-3-Clause Modified BSD License
 * @version $Id$
 */

/**
 * Tests all the prebuilt filters provided in Link
 * 
 * @package Link.test
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 *  
 */
class Link_Tests_FiltersTest extends PHPUnit_Framework_TestCase {
  public function testCeil() {
    $this->assertEquals(43, Link_Filters::ceil('42.1337'), '->Filter:ceil() does not round up correctly a value');
  }
  
  public function testFloor() {
    $this->assertEquals(42, Link_Filters::floor('42.1337'), '->Filter:floor() does not round down correctly a value');
  }
  
  public function testProtect() {
    //$this->assertEquals('', $actual, $message)
    
  }
  
  public function testUCFirst() {}
  
  public function testLCFirst() {}
  
  public function testConvertCase() {}
  
  public function testMinimize() {}
  
  public function testMaximize() {}
  
  public function testNl2br() {}
  
  public function testSlugify() {}
  
  public function testCut() {}
  
  public function testParagraphy() {}
  
  public function testSafe() {}
  
  public function testVoid() {}
  
  public function testDefaults() {}
}
