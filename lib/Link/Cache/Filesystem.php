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
    $_dir = null,
    $_key = array();
  
  /**
   * Constructor
   * 
   * @param string $_dir dir where the cache will be stored
   * @see Link_Cache_Filesystem::setDir()
   */
  function __construct($_dir = null) {
    $this->setDir($_dir);
  }

  /** @param string $_dir Directory for the cache */
  public function setDir($_dir = null) {
    if ($_dir === null) {
      $this->_dir = sys_get_temp_dir();
      return;
    }
    
    $dir = realpath(rtrim($_dir, DIRECTORY_SEPARATOR));

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
  
  /** @param string $_key key designing the cache */
  public function setKey($_key) {
    $file = sprintf('%1$s%4$stpl_%2$s.%3$s', $this->getDir(), sha1($_key), PHP_EXT, DIRECTORY_SEPARATOR);

    $filemtime = 0;
    $filesize = 0;

    if (is_file($file)) {
      $filemtime = filemtime($file);
      $filesize = filesize($file);
    }

    $this->_key = array(
      'file' => $file,
      'last_modif' => $filemtime,
      'size' => $filesize
     );
  }

  /**
   * Check if the cache file is still valid
   *
   * @param integer $time Last modification's timestamp
   * @return boolean true if still valid, false if not
   */
  public function isValid($time) {
    return $this->_key['last_modif'] >= abs($time) && $this->_key['size'] > 0;
  }

  /**
   * Write the content in the cache file
   *
   * @param string $data Data to be written
   * @return boolean
   */
  public function put($data) {
    if ($this->_key === array()) {
      throw new Link_Exception_Cache('You should select a key to work on before putting any datas inside...');
    }
    
    // -- Setting a homemade LOCK
    $lockFile = sprintf('%1$s%3$s__tpl_flock__.%2$s', $this->getDir(), sha1($this->_key['file']), DIRECTORY_SEPARATOR);
    $lock = @fclose(fopen($lockFile, 'x'));

    if (!$lock){
      throw new Link_Exception_Cache('Writing in the cache not possible for now');
    }

    file_put_contents($this->_key['file'], $data);
    chmod($this->_key['file'], 0664);

    // -- Removing the LOCK
    unlink($lockFile);
    return true;
  }

  /**
   * Delete the cache file... If it exists.
   *
   * @return void
   */
  public function destroy() {
    if ($this->_key !== array()) {
      unlink($this->_key['file']);
      $this->_key = array();
    }
  }

  /**
   * Executes the file's content
   *
   * @param Link_Environnement $_env Templating environnement to be used in this file
   * @param array $_context Variables to be given to the template
   * @return bool execution's status
   */
  public function exec(Link_Environnement $_env, array $_context = array()) {
    if ($this->_key === array()) {
      throw new Link_Exception_Cache('Beware, this file is a ghost !');
    }

    $varCount = extract($_context, EXTR_PREFIX_ALL | EXTR_REFS, '__tpl_vars_');

    if ($varCount < count($_context)) {
      trigger_error('Some variables couldn\'t be extracted...', E_USER_NOTICE);
    }

    include $this->_key['file'];
    return true;
  }

  /**
   * Executes the file's content
   * Implementation of the magic method __invoke() for PHP >= 5.3
   *
   * @param Link_Environnement $tpl TPL environnement to be used during cache reading
   * @param array $_context Variables to be given to the template
   * @return bool
   *
   * @see self::exec()
   */
  public function __invoke(Link_Environnement $_env, array $_context = array()) {
    return $this->exec($tpl);
  }
}

/**
 * EOF
 */
