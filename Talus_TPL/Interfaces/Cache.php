<?php
/**
 * Interface to implement a new Cache engine for the templates
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @package Talus' TPL
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @copyright ©Talus, Talus' Works 2006+
 * @link http://www.talus-works.net Talus' Works
 * @link http://www.slideshare.net/fabpot/dependency-injection-with-php-53 Slideshare DI
 * @license http://www.gnu.org/licenses/lgpl.html LGNU Public License 3+
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
   * @param Talus_TPL $tpl TPL à executer
   */
  public function exec(Talus_TPL $tpl);
}

/*
 * EOF
 */
