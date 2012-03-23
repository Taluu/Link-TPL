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
    $str = 'once upon a \'time\' there was a <little> "llama" & a giant panda.';
    
    $this->assertEquals('once upon a \'time\' there was a &lt;little&gt; &quot;llama&quot; &amp; a giant panda.', Link_Filters::protect($str));
    $this->assertEquals('once upon a &#039;time&#039; there was a &lt;little&gt; &quot;llama&quot; &amp; a giant panda.', Link_Filters::protect($str, ENT_QUOTES));
    $this->assertEquals('once upon a \'time\' there was a &lt;little&gt; "llama" &amp; a giant panda.', Link_Filters::protect($str, ENT_NOQUOTES));
  }
  
  public function testUCFirst() {
    $str = 'oNce Upon A time There was a GREAT Llama';
    $this->assertEquals('ONce Upon A time There was a GREAT Llama', Link_Filters::ucfirst($str));
  }
  
  public function testLCFirst() {
    $str = 'ONce Upon A time There was a GREAT Llama';
    $this->assertEquals('oNce Upon A time There was a GREAT Llama', Link_Filters::lcfirst($str));
  }
  
  public function testConvertCase() {
    $str = 'A cool Llama is a llama that Rules';
    
    $this->assertEquals('a cool llama is a llama that rules', Link_Filters::convertCase($str, MB_CASE_LOWER));
    $this->assertEquals('A COOL LLAMA IS A LLAMA THAT RULES', Link_Filters::convertCase($str, MB_CASE_UPPER));
    $this->assertEquals('A Cool Llama Is A Llama That Rules', Link_Filters::convertCase($str, MB_CASE_TITLE));
  }
  
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
