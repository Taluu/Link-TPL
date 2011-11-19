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
 * Interface to implement a new Cache engine for the templates
 * 
 * @package Link
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
interface Link_Interfaces_Cache extends Link_Interfaces_Dependency {
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
   * Fetches & executes the cache content
   *
   * @param Link_Environnement $_env TPL environnement to be given to the template
   * @param array $_context Local variables to the template
   */
  public function exec(Link_Environnement $_env, array $_context = array());
}

/*
 * EOF
 */
