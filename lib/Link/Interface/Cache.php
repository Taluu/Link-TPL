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
interface Link_Interface_Cache {
  /**
   * Gets the last modified time for the selected key
   *
   * @param string $_key key designing the cache
   * @return integer last modification unix timestamp of the file
   */
  public function getTimestamp($_key);

  /**
   * Write the content in the cache file
   *
   * @param string $_key key designing the cache
   * @param string $_data Data to be written
   * @return boolean
   */
  public function put($_key, $_data);

  /**
   * Delete the current cache id.
   *
   * @param string $_key key designing the cache
   * @return void
   */
  public function destroy($_key);

  /**
   * Fetches & executes the cache content
   *
   * @param string $_key key designing the cache
   * @param Link_Environment $_env TPL environnement to be given to the template
   * @param array $_context Local variables to the template
   */
  public function exec($_key, Link_Environment $_env, array $_context = array());
}

/*
 * EOF
 */
