<?php
/**
 * This file is part of Talus' TPL.
 * 
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Copyleft (c) 2007+, Baptiste Clavié, Talus' Works
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.opensource.org/licenses/BSD-3-Clause Modified BSD License
 * @version $Id$
 */

// -- File constants
if (!defined('PHP_EXT')) {
  define('PHP_EXT', pathinfo(__FILE__, PATHINFO_EXTENSION));
}

/**
 * Filecache engine for Talus' TPL.
 *
 * @package Talus_TPL
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @since 1.4.0
 */
class Talus_TPL_Cache implements Talus_TPL_Cache_Interface {
  protected
    $_dir = null,
    $_file = array();

  /**
   * Accessor for $this->_dir
   *
   * @param string $dir Directory for the cache
   * @return string
   */
  public function dir($dir = null) {
    if ($dir !== null) {
      $dir = rtrim($dir, '/');

      if (!is_dir($dir)){
        throw new Talus_TPL_Dir_Exception(array('The directory <b>"%s"</b> doesn\'t exist.', $dir));
        return false;
      }

      $this->_dir = $dir;
    } elseif ($this->_dir === null) {
      $this->_dir = sys_get_temp_dir();
    }

    return $this->_dir;
  }

  /**
   * Set the file to use
   *
   * @param string $file File's name
   * @return array Information on  the file
   */
  public function file($file = null) {
    if ($file !== null) {
      $file = sprintf('%1$s/tpl_%2$s.%3$s', $this->dir(null), sha1(trim($file, '.')), PHP_EXT);

      $filemtime = 0;
      $filesize = 0;

      if (is_file($file)) {
        $filemtime = filemtime($file);
        $filesize = filesize($file);
      }

      $this->_file = array(
        'url' => $file,
        'last_modif' => $filemtime,
        'size' => $filesize
       );
    }

    return $this->_file;
  }

  /**
   * Check if the cache file is still valid
   *
   * @param integer $time Last modification's timestamp
   * @return boolean true if still valid, false if not
   */
  public function isValid($time) {
    $file = $this->file(null);
    return $file['last_modif'] >= abs($time) && $file['size'] > 0;
  }

  /**
   * Write the content in the cache file
   *
   * @param string $data Data to be written
   * @return boolean
   */
  public function put($data) {
    $file = $this->file(null);

    // -- Setting a homemade LOCK
    $lockFile = sprintf('%1$s/__tpl_flock__.%2$s', $this->dir(null), sha1($file['url']));
    $lock = @fclose(fopen($lockFile, 'x'));

    if (!$lock){
      throw new Talus_TPL_Write_Exception('Writing in the cache not possible for now');
      return false;
    }

    file_put_contents($file['url'], $data);
    chmod($file['url'], 0664);

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
    $file = $this->file(null);

    if ($file !== array()) {
      unlink($file['url']);
      unset($file);

      $this->_file = array();
    }
  }

  /**
   * Executes the file's content
   *
   * @param Talus_TPL $tpl Templating object to be used in this file
   * @return bool execution's status
   */
  public function exec(Talus_TPL $tpl) {
    $file = $this->file(null);
    $vars = $tpl->set(null);

    if ($file === array()) {
      throw new Talus_TPL_Exec_Exception('Beware, this file is a ghost !');
      return false;
    }

    $varCount = extract($vars, EXTR_PREFIX_ALL | EXTR_REFS, '__tpl_vars_');

    if ($varCount < count($vars)) {
      trigger_error('Some variables couldn\'t be extracted (invalid name maybe ?)...', E_USER_NOTICE);
    }

    include $file['url'];
    return true;
  }

  /**
   * Executes the file's content
   * Implementation of the magic method __invoke() for PHP >= 5.3
   *
   * @param Talus_TPL $tpl Objet TPL Ã  utiliser lors de la lecture du cache
   * @return bool
   *
   * @see self::exec()
   */
  public function __invoke(Talus_TPL $tpl) {
    return $this->exec($tpl);
  }
}

/**
 * EOF
 */
