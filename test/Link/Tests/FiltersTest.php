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
  
  public function testInvertCase() {
    $str = 'oNce Upon A time There was a GREAT Llama';
    $this->assertEquals('OnCE uPON a TIME tHERE WAS A great lLAMA', Link_Filters::invertCase($str));
  }
  
  public function testMinimize() {
    $str = 'A cool Llama is a llama that Rules';
    $this->assertEquals('a cool llama is a llama that rules', Link_Filters::minimize($str));
  }
  
  public function testMaximize() {
    $str = 'A cool Llama is a llama that Rules';
    $this->assertEquals('A COOL LLAMA IS A LLAMA THAT RULES', Link_Filters::maximize($str));
  }
  
  public function testNl2br() {
    $str = "A llama is great. \n really.";
    $this->assertEquals("A llama is great. <br />\n really.", Link_Filters::nl2br($str));
    $this->assertEquals("A llama is great. <br>\n really.", Link_Filters::nl2br($str, false));
  }
  
  public function testSlugify() {
    $this->assertEquals('my-title-is-a-llama-the-wisest-llama-ever', Link_Filters::slugify('My title is a llama. The wisest llama ever.'));
    $this->assertEquals('n-a', Link_Filters::slugify('(>°_°)==>')); // kiiirby... PUNCH !
  }
  
  public function testCut() {
    $str = 'This is a film about a man and a fish.';
    $this->assertEquals('This is...', Link_Filters::cut($str, 10));
    $this->assertEquals($str, Link_Filters::cut($str, 100));
  }
  
  public function testParagraphy() {
    $str = <<<TXT
This is a film about a man and a fish
This is a film about dramatic relationship between man and fish

The man stands between life and death
The man thinks
The horse thinks
The sheep thinks
The cow thinks
The dog thinks
The fish doesn't think

The fish is mute, expressionless
The fish doesn't think because the fish knows everything
The fish knows everything
TXT;
    
    $expected = <<<TXT
<p>This is a film about a man and a fish<br />
This is a film about dramatic relationship between man and fish</p>

<p>The man stands between life and death<br />
The man thinks<br />
The horse thinks<br />
The sheep thinks<br />
The cow thinks<br />
The dog thinks<br />
The fish doesn't think</p>

<p>The fish is mute, expressionless<br />
The fish doesn't think because the fish knows everything<br />
The fish knows everything</p>
TXT;
    
    $this->assertEquals($expected, Link_Filters::paragraphy($str));
  }
  
  public function testSafe() {
    // ent_compat
    $str = Link_Filters::protect('once upon a \'time\' there was a <little> "llama" & a giant panda.', ENT_COMPAT);
    
    $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', Link_Filters::safe($str));
    $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', Link_Filters::safe($str, ENT_QUOTES));
    $this->assertEquals('once upon a \'time\' there was a <little> &quot;llama&quot; & a giant panda.', Link_Filters::safe($str, ENT_NOQUOTES));
    
    // ent_quotes
    $str = Link_Filters::protect('once upon a \'time\' there was a <little> "llama" & a giant panda.', ENT_QUOTES);
    
    $this->assertEquals('once upon a &#039;time&#039; there was a <little> "llama" & a giant panda.', Link_Filters::safe($str));
    $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', Link_Filters::safe($str, ENT_QUOTES));
    $this->assertEquals('once upon a &#039;time&#039; there was a <little> &quot;llama&quot; & a giant panda.', Link_Filters::safe($str, ENT_NOQUOTES));
    
    // ent_noquotes    
    $str = Link_Filters::protect('once upon a \'time\' there was a <little> "llama" & a giant panda.', ENT_NOQUOTES);
    
    $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', Link_Filters::safe($str));
    $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', Link_Filters::safe($str, ENT_QUOTES));
    $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', Link_Filters::safe($str, ENT_NOQUOTES));
  }
  
  public function testVoid() {
    $str = 'Yellow lemon tree';
    $this->assertEquals($str, Link_Filters::void($str));
  }
  
  public function testDefaults() {
    $str = 'Yellow lemon tree';
    $this->assertEquals($str, Link_Filters::defaults($str, 'no lemon tree :('));
    $this->assertEquals($str, Link_Filters::defaults(null, $str));
    $this->assertEquals('empty array', Link_Filters::defaults(array(), 'empty array'));
  }
}
