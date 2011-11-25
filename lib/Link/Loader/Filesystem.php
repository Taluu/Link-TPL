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

/**
 * Filesystem Loader for Link TPL.
 * 
 * Loads a template from the filesystem.
 * 
 * This class comes from the Twig project.
 *
 * @package Link
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @since 1.4.0
 */
class Link_Loader_Filesystem implements Link_Interface_Loader {
  protected 
    $_dirs = array(),
    $_cache = array();
  
  public function __construct($_dirs) {
    clearstatcache();
    $this->setDirs($_dirs);
  }
  
  public function getCacheKey($_name) {
    return sha1($this->findFileName($_name));
  }
    
  public function getSource($_name) {
    return file_get_contents($this->_findFileName($_name));
  }
  
  public function isFresh($_name, $_time) {
    return filemtime($this->_findFileName($_name)) > $_time;
  }
  
  /**
   * Find the file designated by $_name, checks if it exists
   * 
   * @param string $_name name of the file to retrieve
   * @return string the corresponding file name
   * @throws Link_Exception_Loader if the file is not found or not accessible
   */
  protected function _findFileName($_name) {
    $file = strtr($_name, '\\', '/');
    
    if (isset($this->_cache[$_name])) {
      return $this->_cache[$_name];
    }
    
    // -- Checking the key name validity...
    $level = 0;
    $parts = explode('/', $file);
    
    foreach ($parts as &$part) {
      if ($part == '..') {
        --$level;
      } elseif ($part != '.') {
        ++$level;
      }
      
      if ($level < 0) {
        throw new Link_Exception_Loader('You may not access a template outside its directory.');
      }
    }
    
    foreach ($this->_dirs as &$dir) {
      $f = $this->_dirs . '/' . $file;
      if (file_exists($f)) {
        $this->_cache[$_name] = $f;
        return $f;
      }
    }
    
    throw new Link_Exception_Loader('The template ' . $_name . ' does not seem to exist.');
  }
  
  /** @return array directories used **/
  public function getDirs() {
    return $this->_dirs;
  }
  
  /** @param string|array $_dirs directories to use **/
  public function setDirs($_dirs) {
    if (!is_array($_dirs)) {
      $_dirs = array($_dirs);
    }
    
    $this->_dirs = array();
    $this->_cache = array();
    
    foreach ($_dirs as &$dir) {
      $this->appendDir($dir);
    }
  }
  
  /**
   * Appends a directory to the list of directories
   * 
   * @param string $_dir Directory to add
   * @throws Link_Exception_Loader
   */
  public function appendDir($_dir) {
    $dir = rtrim(strtr($_dir, '\\', '/'), '/');
    
    if (!is_dir($dir)) {
      throw new Link_Exception_Loader('The directory ' . $_dir . ' does not seem to exist.');
    }
    
    $this->_dirs[] = $dir;
  }
  
  /**
   * Prepends a directory on the top of the pile
   * 
   * @param type $_dir 
   */
  public function prependDir($_dir) {
    $dir = rtrim(strtr($_dir, '\\', '/'), '/');
    
    if (!is_dir($dir)) {
      throw new Link_Exception_Loader('The directory ' . $_dir . ' does not seem to exist.');
    }
    
    $this->_cache = array();
    array_unshift($this->_dirs, $dir);
  }
}


/*
 * EOF
 */
