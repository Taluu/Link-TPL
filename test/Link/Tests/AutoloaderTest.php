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

class Link_Tests_AutoloaderTest extends PHPUnit_Framework_TestCase {
  public function testAutoload() {
    $this->assertFalse(class_exists('FooBarFoo'), '->load() does not try to load classes that does not begin with Link');

    $this->assertFalse(Link_Autoloader::load('Foo'), '->load() does not return false if it is not able to load a class');
    $this->assertTrue(Link_Autoloader::load('Link_Environment'), '->load() does not successfully loads a Link TPL class');
    $this->assertFalse(Link_Autoloader::load('Link\\Mock'), '->load() does not return false if a Link class does not exist');
  }
}
