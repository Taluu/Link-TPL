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
 * Tests the prebuilt loader using the Filesystem
 *
 * These tests are right from the twig template engine
 *
 * @package Link.test
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 *
 */
class Link_Tests_Loader_FilesystemTest extends PHPUnit_Framework_TestCase {
    /** @var Link_Loader_Filesystem */
    private $_loader = null;

    /** @dataProvider getSecurityTests */
    public function testSecurity($template) {
        try {
            $this->_loader->getSource($template);
            $this->fail();
        } catch (Link_Exception_Loader $e) {
            $this->assertNotContains('does not seem to exist', $e->getMessage());
        }
    }

    public function getSecurityTests() {
        return array(
            array("AutoloaderTest\0.php"),
            array('..\\AutoloaderTest.php'),
            array('..\\\\\\AutoloaderTest.php'),
            array('../AutoloaderTest.php'),
            array('..////AutoloaderTest.php'),
            array('./../AutoloaderTest.php'),
            array('.\\..\\AutoloaderTest.php'),
            array('././././././../AutoloaderTest.php'),
            array('.\\./.\\./.\\./../AutoloaderTest.php'),
            array('foo/../../AutoloaderTest.php'),
            array('foo\\..\\..\\AutoloaderTest.php'),
            array('foo/../bar/../../AutoloaderTest.php'),
            array('foo/bar/../../../AutoloaderTest.php'),
            array('filters/../../AutoloaderTest.php'),
            array('filters//..//..//AutoloaderTest.php'),
            array('filters\\..\\..\\AutoloaderTest.php'),
            array('filters\\\\..\\\\..\\\\AutoloaderTest.php'),
            array('filters\\//../\\/\\..\\AutoloaderTest.php'),
        );
    }

    public function testLoad() {
        // -- should not be any errors...
        $this->_loader->getSource('FilesystemTest.php');
        $this->_loader->getSource('FilesystemTest.php');

        $this->_loader->appendDir(dirname(__FILE__) . '/../Loader');
        $this->_loader->prependDir(dirname(__FILE__) . '/../Loader');
    }

    public function testFail() {
        $item = 'not_existant';

        try {
            $this->_loader->getSource($item);
            $this->fail('Can\'t detect if a file does not exist...');
        } catch (Link_Exception_Loader $e) {
            $this->assertContains('does not seem to exist', $e->getMessage());
        }

        try {
            $this->_loader->appendDir($item);
            $this->fail('Can\'t detect if a directory does not exist...');
        } catch (Link_Exception_Loader $e) {
            $this->assertContains('does not seem to exist', $e->getMessage());
        }

        try {
            $this->_loader->prependDir($item);
            $this->fail('Can\'t detect if a directory does not exist...');
        } catch (Link_Exception_Loader $e) {
            $this->assertContains('does not seem to exist', $e->getMessage());
        }
    }

    public function testStatus() {
        $this->assertEquals(sha1(dirname(__FILE__) . '/FilesystemTest.php'), $this->_loader->getCacheKey('FilesystemTest.php'));
        $this->assertTrue($this->_loader->isFresh('FilesystemTest.php', 0));
    }

    public function setUp() {
        $this->_loader = new Link_Loader_Filesystem(dirname(__FILE__));
    }
}