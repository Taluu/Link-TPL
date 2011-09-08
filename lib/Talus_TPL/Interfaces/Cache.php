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

/**
 * Interface to implement a new Cache engine for the templates
 * 
 * @package Talus_TPL
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
interface Talus_TPL_Cache_Interface extends Talus_TPL_Dependency_Interface {
  /**
   * Accessor for $this->_dir
   *
   * $dir should be either a directory for FTP Cache, a DB if SQL, ...
   *
   * @param string $dir Directory for the cache
   * @return string
   */
  public function dir($dir = null);

  /**
   * Sets the id to use for the cache engine
   *
   * @param string $file File's name
   * @return array Information on the file
   */
  public function file($file = null);

  /**
   * Check if the cache file is still valid
   *
   * @param integer $time Last modification's timestamp
   * @return boolean true if still valid, false if not
   */
  public function isValid($time);

  /**
   * Write the content in the cache file
   *
   * @param string $data Data to be written
   * @return boolean
   */
  public function put($data);

  /**
   * Delete the current cache id.
   *
   * @return void
   */
  public function destroy();

  /**
   * Execute le contenu du cache
   *
   * @param Talus_TPL $tpl TPL Ã  executer
   */
  public function exec(Talus_TPL $tpl);
}

/*
 * EOF
 */
