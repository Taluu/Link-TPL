<?php
/**
 * Interface to implement a new Parser for the templates.
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

interface Talus_TPL_Parser_Interface extends Talus_TPL_Dependency_Interface {
  /**
   * Accessor for a given parameter
   *
   * @param string $param Parameter's name
   * @param mixed $value Parameter's value (if setter)
   * @return mixed Parameter's value
   */
  public function parameter($name, $val = null);

  /**
   * Transform a TPL syntax towards an optimized PHP syntax
   *
   * @param string $script TPL script to parse
   * @return string
   */
  public function parse($str);
}

/*
 * EOF
 */
