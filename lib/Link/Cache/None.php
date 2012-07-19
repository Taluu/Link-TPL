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
 * This is a ghost cache.
 *
 * Acts a dummy to disable the cache.
 *
 * @package Link
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @since   1.13.0
 */
class Link_Cache_None implements Link_Interface_Cache {
    protected $_datas = array();

    /** {@inheritDoc} */
    public function destroy($_key) {
        return; // no reason to do anything, is there ? :o
    }

    /** {@inheritDoc} */
    public function getTimestamp($_key) {
        return 0; // the template is always fresher than the cache
    }

    /** {@inheritDoc} */
    public function put($_key, $_data) {
        $this->_datas[$_key] = $_data; // Stocking the compilation result only...
    }

    /** {@inheritDoc} */
    public function exec($_key, Link_Environment $_env, array $_context = array()) {
        if (!isset($this->_datas[$_key])) {
            throw new Link_Exception_Cache('No data sent.');
        }

        if (extract($_context, EXTR_PREFIX_ALL | EXTR_REFS, '__tpl_vars_') < count($_context)) {
            trigger_error('Some variables couldn\'t be extracted...', E_USER_NOTICE);
        }

        // -- GAWD I don't like this method :(
        return (bool)eval('?>' . $this->_datas[$_key] . '<?php');
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
}

/*
 * EOF
 */
