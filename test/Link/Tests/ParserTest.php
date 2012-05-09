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
    $datas = array(
      // Normal foreach
      '<foreach array="{$sth}">' => '<?php
      $__tpl_foreach__sth = array(
        \'value\' => null,
        \'key\' => null,
        \'size\' => isset($__tpl_vars__sth) ? count($__tpl_vars__sth) : 0,
        \'current\' => 0
       );

      if ($__tpl_foreach__sth[\'size\'] > 0) :
        foreach ($__tpl_vars__sth as $__tpl_foreach__sth[\'key\'] => $__tpl_foreach__sth[\'value\']) {
          ++$__tpl_foreach__sth[\'current\']; ?>',

      // with Shortcut
      '<foreach ary="{$sth}">' => '<?php
      $__tpl_foreach__sth = array(
        \'value\' => null,
        \'key\' => null,
        \'size\' => isset($__tpl_vars__sth) ? count($__tpl_vars__sth) : 0,
        \'current\' => 0
       );

      if ($__tpl_foreach__sth[\'size\'] > 0) :
        foreach ($__tpl_vars__sth as $__tpl_foreach__sth[\'key\'] => $__tpl_foreach__sth[\'value\']) {
          ++$__tpl_foreach__sth[\'current\']; ?>',

      // Something not really valid...
      '<foreach array="{$sth[\'not\']->valid}">' => '<foreach array="$__tpl_vars__sth[\'not\']->valid">',

      // Heavy syntax
      '<foreach array="{$sth.value[\'which\']->is}" as="{$valid}">' => '<?php
      $__tpl_foreach__valid = array(
        \'value\' => null,
        \'key\' => null,
        \'size\' => isset($__tpl_foreach__sth[\'value\'][\'which\']->is) ? count($__tpl_foreach__sth[\'value\'][\'which\']->is) : 0,
        \'current\' => 0
       );

      if ($__tpl_foreach__valid[\'size\'] > 0) :
        foreach ($__tpl_foreach__sth[\'value\'][\'which\']->is as $__tpl_foreach__valid[\'key\'] => $__tpl_foreach__valid[\'value\']) {
          ++$__tpl_foreach__valid[\'current\']; ?>'
     );

    foreach ($datas as $data => $expected) {
      $this->assertEquals($expected, $this->_parser->parse($data));
    }
  }

  /** @dataProvider getVarsTests */
  public function testVars($tpl, $expected) {
    $this->assertEquals($expected, $this->_parser->parse($tpl));
  }
  
  public function getVarsTests() {
    return array(
      // basic vars
      array('{abcd}', '<?php echo $__tpl_vars__abcd; ?>'),
      array('{$abcd}', '$__tpl_vars__abcd'),
      
      // with suffix like array or objects
      array('{$abcd[\'with`\']->some[\'stuff\']}', '$__tpl_vars__abcd[\'with`\']->some[\'stuff\']'),
      
      // filters
      array('{$abcd|protect|safe}', 'Link_Filters::safe(Link_Filters::protect($__tpl_vars__abcd))'),
      array('{abcd|protect|safe}', '<?php echo Link_Filters::safe(Link_Filters::protect($__tpl_vars__abcd)); ?>'),
      
      // foreaches
      array('{$abcd.value}', '$__tpl_foreach__abcd[\'value\']'),
      array('{$abcd.key}', '$__tpl_foreach__abcd[\'key\']'),
      
      // not valid
      array('{0_a}', '{0_a}'),
      array('{$0_a}', '{$0_a}'),
      array('{$_-a}', '{$_-a}')
     );
  }

  public function testIncludes() {
    $this->markTestIncomplete('Not implemented yet :(');
  }
}
