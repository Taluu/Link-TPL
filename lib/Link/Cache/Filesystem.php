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

defined('PHP_EXT') || define('PHP_EXT', pathinfo(__FILE__, PATHINFO_EXTENSION));

/**
 * Filesystem cache handler for Link TPL.
 *
 * @package Link
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @since 1.4.0
 */
class Link_Cache_Filesystem implements Link_Interface_Cache {
  protected
    $_dir = null;
  
  /**
   * Constructor
   * 
   * @param string $_dir dir where the cache will be stored
   * @see Link_Cache_Filesystem::setDir()
   */
  function __construct($_dir = null) {
    clearstatcache();
    $this->setDir($_dir);
  }

  /** @param string $_dir Directory for the cache */
  public function setDir($_dir = null) {
    if ($_dir === null) {
      $this->_dir = rtrim(strtr(sys_get_temp_dir(), '\\', '/'), '/');
      return;
    }
    
    $dir = rtrim(strtr($_dir, '\\', '/'), '/');

    if (!is_dir($dir)){
      throw new Link_Exception_Cache(array('The directory <b>"%s"</b> doesn\'t exist.', $_dir));
      return;
    }

    $this->_dir = $dir;
  }
  
  /** @return string dir where the cache will be stored */
  public function getDir() {
    return $this->_dir;
  }

  public function getTimestamp($_key) {
    $file = sprintf('%1$s/link_%2$s.%3$s', $this->getDir(), $_key, PHP_EXT);
    
    return file_exists($file) ? filemtime($file) : 0;
  }

  public function put($_key, $data) {
    $file = sprintf('%1$s/link_%2$s.%3$s', $this->getDir(), $_key, PHP_EXT);
    
    // -- Setting a homemade LOCK
    $lockFile = sprintf('%1$s/__link_flock__.%2$s', $this->getDir(), sha1($file));
    $lock = @fclose(fopen($lockFile, 'x'));

    if (!$lock){
      throw new Link_Exception_Cache('Writing in the cache not possible for now');
    }

    file_put_contents($file, $data);
    chmod($file, 0664);

    // -- Removing the LOCK
    unlink($lockFile);
    return true;
  }

  public function destroy($_key) {
    $file = sprintf('%1$s/link_%2$s.%3$s', $this->getDir(), $_key, PHP_EXT);
    unlink($file);
  }

  public function exec($_key, Link_Environnement $_env, array $_context = array()) {
    $file = sprintf('%1$s/link_%2$s.%3$s', $this->getDir(), $_key, PHP_EXT);
    
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
   * @param string $_key Key representating the cache file
   * @param Link_Environnement $tpl TPL environnement to be used during cache reading
   * @param array $_context Variables to be given to the template
   * @return bool
   *
   * @see self::exec()
   */
  public function __invoke($_key, Link_Environnement $_env, array $_context = array()) {
    return $this->exec($_key, $_env, $_context);
  }
}

/**
 * EOF
 */
