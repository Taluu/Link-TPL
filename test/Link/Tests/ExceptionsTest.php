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

class Link_Tests_ExceptionsTest extends PHPUnit_Framework_TestCase {
    /**
     * @expectedException Link_Exception_Cache
     * @expectedExceptionMessage test
     */
    public function testCacheException() {
        throw new Link_Exception_Cache(array('%s', 'test'));
    }

    /**
     * @expectedException Link_Exception_Loader
     * @expectedExceptionMessage test
     */
    public function testLoaderException() {
        throw new Link_Exception_Loader(array('%s', 'test'));
    }

    /**
     * @expectedException Link_Exception_Parser
     * @expectedExceptionMessage test
     */
    public function testParserException() {
        throw new Link_Exception_Parser(array('%s', 'test'));
    }

    /**
     * @expectedException Link_Exception_Runtime
     * @expectedExceptionMessage test
     */
    public function testRuntimeException() {
        throw new Link_Exception_Runtime(array('%s', 'test'));
    }
}