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

if (!class_exists('vfsStream')) {
    require_once 'vfsStream/vfsStream.php';
}

class Link_Tests_FilesystemTest extends PHPUnit_Framework_TestCase {
    /**
     * @covers Link_Cache_Filesystem::__construct
     * @covers Link_Cache_Filesystem::setDir
     * @covers Link_Cache_Filesystem::getDir
     *
     * @expectedException Link_Exception_Cache
     */
    public function testDir() {
        $root = vfsStream::setup('root');

        $cache = new Link_Cache_Filesystem;

        $this->assertEquals(strtr(sys_get_temp_dir(), '\\', '/'), $cache->getDir());

        $cache->setDir(vfsStream::url('root'));
        $this->assertEquals(vfsStream::url('root'), $cache->getDir());

        $cache->setDir('not_existant_dir');
        $this->fail('Should not get here !');
    }

    /**
     * @covers Link_Cache_Filesystem::getTimestamp
     * @covers Link_Cache_Filesystem::getFile
     */
    public function testTimestamp()
    {
        $root  = vfsStream::setup('root', null, array('link_key.php' => 'some content here'));
        $cache = new Link_Cache_Filesystem(vfsStream::url('root'));

        $this->assertEquals($root->getChild('link_key.php')->filemtime(), $cache->getTimestamp('key'));
        $this->assertEquals(0, $cache->getTimestamp('not_existant_key'));
    }

    /**
     * @covers Link_Cache_Filesystem::put
     * @covers Link_Cache_Filesystem::getFile
     *
     * @expectedException Link_Exception_Cache
     */
    public function testPuts()
    {
        $root  = vfsStream::setup('root');
        $cache = new Link_Cache_Filesystem(vfsStream::url('root'));

        // disable warning because chmod is not available on new stream context in vfsStream < 1.0.0
        try {
            $cache->put('key', 'some data');
        } catch (PHPUnit_Framework_Error_Warning $e) {}

        $this->assertTrue($root->hasChild('link_key.php'));
        $this->assertEquals('some data', $root->getChild('link_key.php')->getContent());

        vfsStream::create(array('__link_flock__.' . sha1('link_key.php')), $root);

        $cache->put('key', 'some data');
        $this->fail('should not get here');
    }

    /**
     * @covers Link_Cache_Filesystem::destroy
     */
    public function testDelete()
    {
        $root  = vfsStream::setup('root', null, array('link_key.php' => 'some content'));
        $cache = new Link_Cache_Filesystem(vfsStream::url('root'));

        $cache->destroy('key');

        $this->assertFalse($root->hasChild('link_key.php'));
    }

    /**
     * @covers Link_Cache_Filesystem::exec
     * @covers Link_Cache_Filesystem::__invoke
     *
     * @expectedException PHPUnit_Framework_Error_Notice
     */
    public function testExec()
    {
        $env   = $this->getMockBuilder('Link_Environment')->disableOriginalConstructor()->getMock();
        $root  = vfsStream::setup('root', null, array('link_key.php' => '<?php //some data here'));
        $cache = new Link_Cache_Filesystem(vfsStream::url('root'));

        $cache->exec('key', $env, array());

        $this->assertContains(vfsStream::url('root/link_key.php'), get_included_files());

        try {
            $cache->exec('key_not_exists', $env, array());
            $this->fail('This test should fail, because key_not_exists doesn\'t exist');
        } catch (Link_Exception_Cache $e) {}

        $cache->__invoke('key', $env, array('$not_valid' => 'not valid variable'));
    }
}

