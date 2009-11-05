<?php
/**
 * Gestion des exceptions pour Talus' TPL
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
 * @copyright ©Talus, Talus' Works 2007+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/lgpl.html Lesser GNU Public License 3+
 * @version $Id$
 */

/**
 * Exception Mère
 */
class Talus_TPL_Exception extends Exception {
  public function __construct($message = '', $code = 0, Exception $previous = null) {
    if (is_array($message)) {
      $str = array_shift($message);
      $message = vsprintf($str, $message);
    }

    parent::__construct($message, $code, $previous);
  }
}


// -- Exceptions Filles
class Talus_TPL_Dir_Exception extends Talus_TPL_Exception {}
class Talus_TPL_Parse_Exception extends Talus_TPL_Exception {}
class Talus_TPL_Runtime_Exception extends Talus_TPL_Exception {}

class Talus_TPL_Var_Exception extends Talus_TPL_Exception {}
class Talus_TPL_Block_Exception extends Talus_TPL_Exception {}


class Talus_TPL_Exec_Exception extends Talus_TPL_Exception {}
class Talus_TPL_Write_Exception extends Talus_TPL_Exception {}


/*
 * EOF
 */
