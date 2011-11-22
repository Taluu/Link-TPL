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
 * Filecache engine for Link TPL.
 *
 * @package Link
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @since 1.4.0
 */
class Link_Cache implements Link_Interfaces_Cache {
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
        throw new Link_Exception_Cache(array('The directory <b>"%s"</b> doesn\'t exist.', $dir));
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
      throw new Link_Exception_Cache('Writing in the cache not possible for now');
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
   * @param Link_Environnement $_env Templating environnement to be used in this file
   * @param array $_context Variables to be given to the template
   * @return bool execution's status
   */
  public function exec(Link_Environnement $_env, array $_context = array()) {
    $file = $this->file(null);

    if ($file === array()) {
      throw new Link_Exception_Cache('Beware, this file is a ghost !');
    }

    $varCount = extract($_context, EXTR_PREFIX_ALL | EXTR_REFS, '__tpl_vars_');

    if ($varCount < count($_context)) {
      trigger_error('Some variables couldn\'t be extracted...', E_USER_NOTICE);
    }

    include $file['url'];
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
