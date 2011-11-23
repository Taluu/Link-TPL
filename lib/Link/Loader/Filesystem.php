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
defined('__DIR__') || define('__DIR__', dirname(__FILE__));

/**
 * Filesystem Loader for Link TPL.
 * 
 * Loads a template from the filesystem.
 *
 * @package Link
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @since 1.4.0
 */
class Link_Loader_Filesystem implements Link_Interface_Loader {
  protected $_dir;
  
  public function __construct($_dir) {
    $this->setDir($_dir);
  }
  
  public function getCacheKey($_name) {
    return $this->findFileName($_name);
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
    
  }
  
  /** @param string $_dir directory to use **/
  public function setDir($_dir) {
    $dir = realpath($_dir);
    
    if ($dir === false) {
      throw new Link_Exception_Loader('The directory ' . $_dir . ' does not exist...');
    }
    
    $this->_dir = $dir;
  }
  
  /** @return string directory used **/
  public function getDir() {
    return $this->_dir;
  }
}


/*
 * EOF
 */
