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
 * Tests the templates' parser
 *
 * @package Link.test
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
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
            '<if cond="test">...</if>'          => '<?php if (test) : ?>...<?php endif; ?>',
            '<if condition="test">...</if>'     => '<?php if (test) : ?>...<?php endif; ?>',

            '<elif cond="test" />...'           => '<?php elseif (test) : ?>...',
            '<elseif cond="test" />...'         => '<?php elseif (test) : ?>...',

            '<if cond="test">..<else />..</if>' => '<?php if (test) : ?>..<?php else : ?>..<?php endif; ?>',

            '<if>... not valid'                 => '<if>... not valid',
            'not valid :)'                      => 'not valid :)'
        );

        foreach ($datas as $data => $expected) {
            $this->assertEquals($expected, $this->_parser->parse($data));
        }
    }

    public function testLoops() {
        $foreach = '<?php
            $__tpl_foreach__%1$s = array(
                \'value\' => null,
                \'key\' => null,
                \'size\' => isset($__tpl_%2$s) ? count($__tpl_%2$s) : 0,
                \'current\' => 0
              );

            if ($__tpl_foreach__%1$s[\'size\'] > 0) :
                foreach ($__tpl_%2$s as $__tpl_foreach__%1$s[\'key\'] => $__tpl_foreach__%1$s[\'value\']) {
                  ++$__tpl_foreach__%1$s[\'current\']; ?>';
    
        $datas = array(
            // Normal foreach
            '<foreach array="{$sth}">'                                    => sprintf($foreach, 'sth', 'vars__sth'),

            // with Shortcut
            '<foreach ary="{$sth}">'                                      => sprintf($foreach, 'sth', 'vars__sth'),

            // Something not really valid...
            '<foreach array="{$sth[\'not\']->valid}">'                    => '<foreach array="$__tpl_vars__sth[\'not\']->valid">',

            // Heavy syntax
            '<foreach array="{$sth.value[\'which\']->is}" as="{$valid}">' => sprintf($foreach, 'valid', 'foreach__sth[\'value\'][\'which\']->is')
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

    /** @dataProvider getIncludesTests */
    public function testIncludes($tpl, $expected) {
        $this->assertEquals($expected, $this->_parser->parse($tpl));
    }

    public function getIncludesTests() {
        return array(
            // includes
            array('<include tpl="112.html" />', '<?php $_env->includeTpl(\'112.html\', false, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="112.html" once="true" />', '<?php $_env->includeTpl(\'112.html\', true, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="112.html" once="false" />', '<?php $_env->includeTpl(\'112.html\', false, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="112.html?a=b&c=d" />', '<?php $_env->includeTpl(\'112.html\' . "?a=b&c=d", false, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="112.html?a=b&c=d" once="true" />', '<?php $_env->includeTpl(\'112.html\' . "?a=b&c=d", true, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="112.html?a=b&c=d" once="false" />', '<?php $_env->includeTpl(\'112.html\' . "?a=b&c=d", false, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="{$TAGS}" />', '<?php $_env->includeTpl($__tpl_vars__TAGS, false, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="{$TAGS}" once="true" />', '<?php $_env->includeTpl($__tpl_vars__TAGS, true, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="{$TAGS}" once="false" />', '<?php $_env->includeTpl($__tpl_vars__TAGS, false, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="{$TAGS}?a=b&c=d" />', '<?php $_env->includeTpl($__tpl_vars__TAGS . "?a=b&c=d", false, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="{$TAGS}?a=b&c=d" once="true" />', '<?php $_env->includeTpl($__tpl_vars__TAGS . "?a=b&c=d", true, Link_Environment::INCLUDE_TPL); ?>'),
            array('<include tpl="{$TAGS}?a=b&c=d" once="false" />', '<?php $_env->includeTpl($__tpl_vars__TAGS . "?a=b&c=d", false, Link_Environment::INCLUDE_TPL); ?>'),

            // requires
            array('<require tpl="112.html" />', '<?php $_env->includeTpl(\'112.html\', false, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="112.html" once="true" />', '<?php $_env->includeTpl(\'112.html\', true, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="112.html" once="false" />', '<?php $_env->includeTpl(\'112.html\', false, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="112.html?a=b&c=d" />', '<?php $_env->includeTpl(\'112.html\' . "?a=b&c=d", false, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="112.html?a=b&c=d" once="true" />', '<?php $_env->includeTpl(\'112.html\' . "?a=b&c=d", true, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="112.html?a=b&c=d" once="false" />', '<?php $_env->includeTpl(\'112.html\' . "?a=b&c=d", false, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="{$TAGS}" />', '<?php $_env->includeTpl($__tpl_vars__TAGS, false, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="{$TAGS}" once="true" />', '<?php $_env->includeTpl($__tpl_vars__TAGS, true, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="{$TAGS}" once="false" />', '<?php $_env->includeTpl($__tpl_vars__TAGS, false, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="{$TAGS}?a=b&c=d" />', '<?php $_env->includeTpl($__tpl_vars__TAGS . "?a=b&c=d", false, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="{$TAGS}?a=b&c=d" once="true" />', '<?php $_env->includeTpl($__tpl_vars__TAGS . "?a=b&c=d", true, Link_Environment::REQUIRE_TPL); ?>'),
            array('<require tpl="{$TAGS}?a=b&c=d" once="false" />', '<?php $_env->includeTpl($__tpl_vars__TAGS . "?a=b&c=d", false, Link_Environment::REQUIRE_TPL); ?>'),
        );
    }
}
