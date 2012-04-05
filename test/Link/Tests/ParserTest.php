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
 * Tests the templates' parser
 * 
 * @package Link.test
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 *  
 */
class Link_Tests_ParserTest extends PHPUnit_Framework_TestCase {
  /** @var Link_Parser */
  private $_parser = null;
  
  public function setUp() {
    $this->_parser = new Link_Parser;
  }
  
  public function testConditions() {
    $datas = array(
        '<if cond="test">...</if>' => '<?php if (test) : ?>...<?php endif; ?>', 
        '<if condition="test">...</if>' => '<?php if (test) : ?>...<?php endif; ?>',
        
        '<elif cond="test" />...' => '<?php elseif (test) : ?>...', 
        '<elseif cond="test" />...' => '<?php elseif (test) : ?>...',
        
        '<if cond="test">..<else />..</if>' => '<?php if (test) : ?>..<?php else : ?>..<?php endif; ?>',
        
        '<if>... not valid' => '<if>... not valid', 
        'not valid :)' => 'not valid :)' 
      );
    
    foreach ($datas as $data => $expected) {
      $this->assertEquals($expected, $this->_parser->parse($data));
    }
  }
  
  public function testLoops() {
    $this->markTestIncomplete('Not implemented yet :(');
  }
  
  public function testVars() {
    $this->markTestIncomplete('Not implemented yet :(');
  }
  
  public function testIncludes() {
    $this->markTestIncomplete('Not implemented yet :(');
  }
}
