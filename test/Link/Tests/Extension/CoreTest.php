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

// -- If PHP < 5.2.7, emulate PHP_VERSION_ID
if (!defined('PHP_VERSION_ID')) {
    $v = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', $v[0] * 10000 + $v[1] * 100 + $v[2]);
}

require_once dirname(__FILE__) . '/../../../../lib/Link/Extension/Core.php';

/**
 * Tests the filters provided in Link's built-in Core Extension
 *
 * @package Link.test
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 *
 */
class Link_Tests_Extension_CoreTest extends PHPUnit_Framework_TestCase {
    public function testEscape() {
        $str = 'once upon a \'time\' there was a <little> "llama" & a giant panda.';

        $this->assertEquals('once upon a \'time\' there was a &lt;little&gt; &quot;llama&quot; &amp; a giant panda.', __link_core__escape($str));
        $this->assertEquals('once upon a &#039;time&#039; there was a &lt;little&gt; &quot;llama&quot; &amp; a giant panda.', __link_core__escape($str, ENT_QUOTES));
        $this->assertEquals('once upon a \'time\' there was a &lt;little&gt; "llama" &amp; a giant panda.', __link_core__escape($str, ENT_NOQUOTES));
    }

    public function testSafe() {
        // ent_compat
        $str = __link_core__escape('once upon a \'time\' there was a <little> "llama" & a giant panda.', ENT_COMPAT);

        $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', __link_core__safe($str));
        $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', __link_core__safe($str, ENT_QUOTES));
        $this->assertEquals('once upon a \'time\' there was a <little> &quot;llama&quot; & a giant panda.', __link_core__safe($str, ENT_NOQUOTES));

        // ent_quotes
        $str = __link_core__escape('once upon a \'time\' there was a <little> "llama" & a giant panda.', ENT_QUOTES);

        $this->assertEquals('once upon a &#039;time&#039; there was a <little> "llama" & a giant panda.', __link_core__safe($str));
        $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', __link_core__safe($str, ENT_QUOTES));
        $this->assertEquals('once upon a &#039;time&#039; there was a <little> &quot;llama&quot; & a giant panda.', __link_core__safe($str, ENT_NOQUOTES));

        // ent_noquotes
        $str = __link_core__escape('once upon a \'time\' there was a <little> "llama" & a giant panda.', ENT_NOQUOTES);

        $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', __link_core__safe($str));
        $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', __link_core__safe($str, ENT_QUOTES));
        $this->assertEquals('once upon a \'time\' there was a <little> "llama" & a giant panda.', __link_core__safe($str, ENT_NOQUOTES));
    }

    public function testDefaults() {
        $str = 'Yellow lemon tree';
        $this->assertEquals($str, __link_core__defaults($str, 'no lemon tree :('));
        $this->assertEquals($str, __link_core__defaults(null, $str));
        $this->assertEquals('empty array', __link_core__defaults(array(), 'empty array'));
    }
}
