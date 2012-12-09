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

defined('PHP_EXT') || define('PHP_EXT', pathinfo(__FILE__, PATHINFO_EXTENSION));

/**
 * Filesystem cache handler for Link TPL.
 *
 * @package Link
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @since   1.4.0
 */
class Link_Cache_Filesystem implements Link_CacheInterface {
    protected $_dir = null;

    /**
     * Constructor
     *
     * @param string $_dir dir where the cache will be stored
     *
     * @see Link_Cache_Filesystem::setDir()
     */
    public function __construct($_dir = null) {
        clearstatcache();
        $this->setDir($_dir);
    }

    /**
     * Directory Setter
     *
     * @param string $_dir Directory for the cache
     *
     * @throws Link_Exception_Cache
     */
    public function setDir($_dir = null) {
        if ($_dir === null) {
            $this->_dir = rtrim(strtr(sys_get_temp_dir(), '\\', '/'), '/');

            return;
        }

        $dir = rtrim(strtr($_dir, '\\', '/'), '/');

        if (!is_dir($dir)) {
            throw new Link_Exception_Cache(array('The directory <strong>"%s"</strong> doesn\'t exist.', $_dir));
        }

        $this->_dir = $dir;
    }

    /** @return string dir where the cache will be stored */
    public function getDir() {
        return $this->_dir;
    }

    /** {@inheritDoc} */
    public function getTimestamp($_key) {
        $file = $this->getFile($_key);

        return file_exists($file) ? filemtime($file) : 0;
    }

    /** {@inheritDoc} */
    public function put($_key, $data) {
        $file = $this->getFile($_key);

        // -- Setting a homemade LOCK
        $lockFile = sprintf('%1$s/__link_flock__.%2$s', $this->getDir(), sha1($file));
        $lock = @fclose(fopen($lockFile, 'x'));

        if (!$lock) {
            throw new Link_Exception_Cache('Writing in the cache not possible for now');
        }

        file_put_contents($file, $data);
        chmod($file, 0664);

        // -- Removing the LOCK
        unlink($lockFile);
    }

    /** {@inheritDoc} */
    public function destroy($_key) {
        unlink($this->getFile($_key));
    }

    /** {@inheritDoc} */
    public function exec($_key, Link_Environment $_env, array $_context = array()) {
        $file = $this->getFile($_key);

        if (!file_exists($file)) {
            throw new Link_Exception_Cache('Beware, this file is a ghost... !');
        }

        if (extract($_context, EXTR_PREFIX_ALL | EXTR_REFS, '__tpl_vars_') < count($_context)) {
            trigger_error('Some variables couldn\'t be extracted...', E_USER_NOTICE);
        }

        include $file;

        return true;
    }

    /**
     * Executes the file's content
     * Implementation of the magic method __invoke() for PHP >= 5.3
     *
     * @param string           $_key     Key representating the cache file
     * @param Link_Environment $_env     TPL environnement to be used during cache reading
     * @param array            $_context Variables to be given to the template
     *
     * @return bool
     *
     * @see self::exec()
     */
    public function __invoke($_key, Link_Environment $_env, array $_context = array()) {
        return $this->exec($_key, $_env, $_context);
    }

    /**
     * Gets the filename for the cache.
     *
     * @param string $_key cache key
     *
     * @return string
     */
    protected function getFile($_key) {
        return sprintf('%1$s/link_%2$s.%3$s', $this->getDir(), $_key, PHP_EXT);
    }
}

/**
 * EOF
 */
