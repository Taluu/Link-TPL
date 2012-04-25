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

// -- Useful constants....
defined('PHP_EXT') || define('PHP_EXT', pathinfo(__FILE__, PATHINFO_EXTENSION));
defined('__DIR__') || define('__DIR__', dirname(__FILE__));

/**
 * Autoloader
 *
 * If the class to load is from this current library, tries a smart load of the
 * file from this directory.
 *
 * This autoloader is PSR-0 compliant.
 *
 * @package Link
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @link http://groups.google.com/group/php-standards/web/psr-0-final-proposal
 */
class Link_Autoloader {
  static public function register() {
    spl_autoload_register(array('self', 'load'));
  }

  static public function unregister() {
    spl_autoload_unregister(array('self', 'load'));
  }

  /**
   * Autotoloads the `$class` class
   *
   * @param string $_class class to be loaded
   */
  static public function load($_class) {
    if (strpos($_class, 'Link') !== 0) {
      return false;
    }

    $file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
    $className = $_class;

    // -- checking for namespaces (only for php > 5.3)
    if (($last = strripos($className, '\\')) !== false) {
      $namespace = substr($className, 0, $last);
      $className = substr($className, $last + 1);

      $file .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }

    $file .= str_replace(array('_', "\0"), array(DIRECTORY_SEPARATOR, ''), $className) . '.' . PHP_EXT;

    if (!file_exists($file)) {
      return false;
    }

    require $file;
    return true;
  }
}

/*
 * EOF
 */
