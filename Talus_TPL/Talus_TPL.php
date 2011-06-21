<?php
/**
 * Template Engine
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
 * @license http://www.gnu.org/licenses/lgpl.html LGNU Public License 2+
 * @version $Id$
 */

if (!defined('PHP_EXT')) {
  define('PHP_EXT', pathinfo(__FILE__, PATHINFO_EXTENSION));
}

if (!defined('E_USER_DEPRECATED')) {
  define('E_USER_DEPRECATED', E_USER_NOTICE);
}

class Talus_TPL {
  protected
    $_root = './',

    $_last = array(),
    $_included = array(),

    $_vars = array(),
    $_references = array(),

     $_autoFilters = array(),

    /**
     * @var Talus_TPL_Parser_Interface
     */
    $_parser = null,

    /**
     * @var Talus_TPL_Cache_Interface
     */
    $_cache = null;

  protected static $_autoloadSet = false;

  const
    INCLUDE_TPL = 0,
    REQUIRE_TPL = 1,
    VERSION = '1.10.6';

  /**
   * Initialisation.
   *
   * @param string $root Directory where the templates files are.
   * @param string $cache Directory where the php version of the templates will be stored.
   * @param array $dependencies Dependency Injection
   * @return void
   */
  public function __construct($root, $cache, array $dependencies = array()){
    // -- Resetting the PHP cache concerning the files' information.
    clearstatcache();

    // -- Setting the autoload for the whole library
    if (self::$_autoloadSet === false) {
      spl_autoload_register('self::_autoload');
      self::$_autoloadSet = true;
    }

    // -- Parameters' initialisation
    $this->_last = array();
    $this->_included = array();
    $this->_vars = array();

    // -- Dependency Injection
    $this->dependencies($dependencies);

    // -- Default behaviour if no dependency injection
    if ($this->_parser === null) {
      $this->_parser = new Talus_TPL_Parser;
    }

    if ($this->_cache === null) {
      $this->_cache = new Talus_TPL_Cache;
    }

    $this->dir($root, $cache);
  }

  /**
   * Autoloader
   *
   * If the class to load is from this current library, tries a smart load of the
   * file from this directory.If it fails, throws an Talus_TPL_Autoload_Exception
   * exception.
   *
   * @param string $class Class' name
   * @throws Talus_TPL_Autoload_Exception Class declaration not found
   * @return bool False if not a class from this library or file not found, true
   *              if everything went smoothly
   */
  private static function _autoload($class) {
    if (mb_strpos($class, __CLASS__) !== 0) {
      return false;
    }

    $dir = dirname(__FILE__);
    $className = mb_substr($class, mb_strlen(__CLASS__) + 1);
    $className = explode('_', $className);

    // -- Exceptions & Interfaces are in different directories.
    if (in_array($className[count($className) - 1], array('Exception', 'Interface'))) {
      $dir .= sprintf('/%1$ss', $className[count($className) - 1]);

      if (count($className) > 1) {
        array_pop($className);
      }
    }

    $file = sprintf('%1$s/%2$s.%3$s', $dir, implode('_', $className), PHP_EXT);

    if (!file_exists($file)) {
      throw new Talus_TPL_Autoload_Exception(array('Class %s not found', $class), 8);
      return false;
    }

    require $file;
    return true;
  }

  /**
   * Set the templates & cache directory.
   *
   * @param string $root Directory containing the original templates.
   * @param string $cache Directory containing the cache files.
   * @throws Talus_Dir_Exception
   * @return void
   *
   * @since 1.7.0
   */
  public function dir($root = './', $cache = './cache/') {
    // -- Removing the final "/", if it's there.
    $root = rtrim($root, '/');

    if (!is_dir($root)) {
      throw new Talus_TPL_Dir_Exception(array('%s is not a directory.', $root), 1);
      return;
    }

    $this->_root = $root;

    // -- Let the cache engine handle his own directory !
    $this->_cache->dir($cache);
  }

  /**
   * Setter (and getter) for the templates variables.
   *
   * @param array|string $vars Var(s)' name (tpl side)
   * @param mixed $value Var's value if $vars is not an array
   * @return array
   *
   * @since 1.3.0
   */
  public function set($vars, $value = null){
    if (is_array($vars)) {
      foreach ($vars as $var => &$val) {
        $this->set($var, $val);
      }
    } elseif ($vars !== null) {
      foreach ($this->autoFilters(null) as $filter) {
        $value = $this->_mapRecursive($value, array('Talus_TPL_Filters', $filter));
      }

      $this->_vars[$vars] = $value;
    }

    return $this->_vars;
  }

  /**
   * Adds defaults filters to be applied to every variables (... except references)
   * WARNING : BEWARE of the order of declaration !
   *
   * @param array|string $name Filters' names ; if null, gets all the filters and do nothing
   * @throws Talus_TPL_Filter_Exception
   * @return array
   *
   * @since 1.9.0
   */
  public function autoFilters($name = null) {
    if ($name !== null) {
      if (is_array($name)) {
        foreach ($name as $filter) {
          $this->autoFilters($filter);
        }

        return $this->_autoFilters;
      }

      if (!method_exists('Talus_TPL_Filters', $name)) {
        throw new Talus_TPL_Filter_Exception(array('The filter %s doesn\'t exist...', $name), 404);
      }

      // -- Applying this filter to all previously declared vars... Except references
      foreach ($this->_vars as $var => &$value) {
        if (in_array($var, $this->_references)) {
          continue;
        }

        $value = $this->_mapRecursive($value, array('Talus_TPL_Filters', $name));
      }
    }

    return $this->_autoFilters;
  }

  /**
   * Sets a variable $var, referencing $value.
   *
   * @param mixed $var Var's name
   * @param mixed &$value Variable to be referenced by $var
   * @throws Talus_TPL_Var_Exception
   * @return void
   *
   * @since 1.7.0
   */
  public function bind($var, &$value) {
    if (mb_strtolower(gettype($var)) != 'string') {
      throw new Talus_TPL_Var_Exception('Reference\'s name not valid.', 3);
      return;
    }

    $this->_vars[$var] = &$value;
    $this->_references[] = $var;
  }

  /**
   * Adds an iteration to the block $block
   *
   * Can act as a getter for this block if $vars is null and $block is a
   * root block. If $vars is not null, nothing will be returned ;
   * If $vars is not an array (lets say... a string), $value will be the value
   * of the only variable for this iteration.
   *
   * Upwards 1.9.0, blocks are now deprecated. This method acts
   * now as a stub for {@see Talus_TPL::set()}.
   *
   * @param string $block Block's name.
   * @param array|string $vars Variable(s) to be used in this iteration
   * @param string $value $vars value if $vars is a string
   * @throws Talus_TPL_Var_Exception
   * @return mixed
   *
   * @since 1.5.1
   * @deprecated DEV
   */
  public function block($block, $vars = null, $value = null) {
    /*
     * Taking the last two blocks names, et imploding them with a _, validating
     * this block name as a full qualified variable
     */
    if (strpos($block, '.') !== false) {
      $block = array_reverse(explode('.', $block));
      $block = implode('_', array($block[1], $block[0]));
    }

    if (!is_array($vars)) {
      $vars = array($vars => $value);
    }

    $vars['FIRST'] = true;
    $vars['LAST'] = true;
    $vars['SIZE'] = 1;
    $vars['CURRENT'] = 1;

    if (!isset($this->_vars[$block])) {
      $this->_vars[$block] = array();
    } else {
      $vars['SIZE'] = &$this->_vars[$block][0]['SIZE'];
      $vars['CURRENT'] = $vars['SIZE'];
      $vars['SIZE']++;
    }

    $this->_vars[$block][] = $vars;
  }

  /**
   * Parse and execute the Template $tpl.
   *
   * If $tpl is an array of files, all the files will be parsed.
   *
   * @param mixed $tpl TPL to be parsed & executed
   * @param mixed $cache If the cache exists, use it
   * @throws Talus_TPL_Parse_Exception
   * @return bool
   */
  public function parse($tpl, $cache = true){
    if (func_num_args() > 2 || is_array($tpl)) {
      // -- Removing the second arg ($cache)
      if (func_num_args() > 2) {
        $tpl = func_get_args();
        array_shift($tpl); array_shift($tpl); array_unshift($tpl, func_get_arg(0));
      }

      foreach ($tpl as &$file) {
        $this->parse($file);
      }

      return true;
    }

    // -- Critical error if the argument $tpl is empty
    if (strlen((string) $tpl) === 0) {
      throw new Talus_TPL_Parse_Exception('No template to be parsed.', 5);
      return false;
    }

    $file = sprintf('%1$s/%2$s', $this->_root, $tpl);

    if (!isset($this->_last[$file])) {
      if (!is_file($file)) {
        throw new Talus_TPL_Parse_Exception(array('The template <b>%s</b> doesn\'t exist.', $tpl), 6);
        return false;
      }

      $this->_last[$file] = filemtime($file);
    }

    $this->_cache->file($tpl, 0);

    if (!$this->_cache->isValid($this->_last[$file]) || !$cache) {
      $this->_cache->put($this->str(file_get_contents($file), false));
    }

    $this->_cache->exec($this);
    return true;
  }

  /**
   * Parse & execute a string
   *
   * @param string $str String to parse
   * @param bool $exec Execute the result ?
   * @throws Talus_TPL_Parse_Exception
   * @return string PHP Code generated
   */
  public function str($str, $exec = true) {
    if (empty($str)) {
      return '';
    }

    // -- Compilation
    $compiled = $this->_parser->parse($str);

    // -- Cache if need to be executed. Will be destroyed right after the execution
    if ($exec === true) {
      $this->_cache->file(sprintf('tmp_%s.html', sha1($str)), 0);
      $this->_cache->put($compiled);
      $this->_cache->exec($this);
      $this->_cache->destroy();
    }

    return $compiled;
  }

  /**
   * Parse a TPL
   * Implemention of magic method __invoke() for PHP >= 5.3
   *
   * @param mixed $tpl TPL to be parsed & executed
   * @see Talus_TPL::parse()
   * @return void
   */
  public function __invoke($tpl) {
    return $this->parse($tpl);
  }

  /**
   * Parse and execute a template
   *
   * Do the exact same thing as Talus_TPL::parse(), but instead of just executing
   * the template, returns the final result (already executed by PHP).
   *
   * @param string $tpl Template's name.
   * @param integer $ttl Time to live for the cache 2. Not implemented yet
   * @return string
   *
   * @todo Cache 2 ?
   */
  public function pparse($tpl = '', $ttl = 0){
    ob_start();
    $this->parse($tpl);
    return ob_get_clean();
  }

  /**
   * Include a template into another
   *
   * @param string $file File to include.
   * @param bool $once Allow the inclusion once or several times
   * @param integer $type Inclusion or requirement ?
   * @return void
   *
   * @see Talus_TPL_Parser::parse()
   * @throws Talus_TPL_Runtime_Exception
   * @throws Talus_TPL_Parse_Exception
   */
  public function includeTpl($file, $once = false, $type = self::INCLUDE_TPL){
    // -- Parameters extraction
    $qString = '';

    if (strpos($file, '?') !== false) {
      list($file, $qString) = explode('?', $file, 2);
    }

    /*
     * If the file have to be included only once, checking if it was not already
     * included.
     *
     * If it was, we're not treating it ; If not, we add it to the stack.
     */
    if ($once){
      $toInclude = sprintf('%1$s/%2$s', $this->_root, $file);

      if (in_array($toInclude, $this->_included)) {
        return;
      }

      $this->_included[] = $toInclude;
    }

    $data = '';
    $save = array(
      'vars' => $this->_vars
     );

    try {
      // -- Changing the variables only if there is a QS
      if (!empty($qString)) {
        // -- Parameters recuperation
        $vars = array();
        parse_str($qString, $vars);

        // -- If MAGIC_QUOTES is ON (grmph), Removing the \s...
        if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
          $vars = array_map('stripslashes', $vars);
        }

        // -- Adding the new variables to this template
        $this->set(array_change_key_case($vars, CASE_UPPER));
      }

      $data = $this->pparse($file);
    } catch (Talus_TPL_Parse_Exception $e) {
      /*
       * If we encounter error n°6 AND it is a require tag, throws an exception
       * Talus_TPL_Runtime_Exception instead of Talus_TPL_Parse_Exception. If not,
       * and still a n°6 error, printing the error message, or else throwing this
       * error back.
       */
      if ($e->getCode() === 6) {
        if ($type == self::REQUIRE_TPL) {
          throw new Talus_TPL_Runtime_Exception(array('That was a "require" tag ; The template <b>%s</b> not existing,  the script shall then be interrupted.', $file), 7);
          exit;
        }

        echo $e->getMessage();
      } else {
        throw $e;
      }
    }

    $this->_vars = $save['vars'];

    echo $data;
  }

  /**#@+
   * Getters / Setters
   */

  /**
   * Parser
   *
   * @return Talus_TPL_Parser_Interface
   */
  public function parser() {
    return $this->_parser;
  }

  /**
   * Cache
   *
   * @return Talus_TPL_Cache_Interface
   */
  public function cache() {
    return $this->_cache;
  }

  /**
   * Dependency Injection handler.
   *
   * @contributor Jordane Vaspard
   * @param mixed $dependencies,.. Dependencies
   * @return void
   * @throws Talus_TPL_Dependency_Exception
   */
  public function dependencies($dependencies = array()) {
    if (func_num_args() > 1) {
      $dependencies = func_get_args();
    } elseif (!is_array($dependencies)) {
      $dependencies = array($dependencies);
    }

    foreach ($dependencies as &$dependency) {
      if ($dependency instanceof Talus_TPL_Parser_Interface) {
        $this->_parser = $dependency;
      } elseif ($dependency instanceof Talus_TPL_Cache_Interface) {
        $this->_cache = $dependency;
      } else {
        throw new Talus_TPL_Dependency_Exception(
                array('%s is not an acknowledged dependency.', get_class($dependency)));
      }
    }
  }

  /**
   * Apply a function on a multidimentionnal array.
   *
   * @param mixed $ary concerned array
   * @param string $fct function
   * @return array
   */
  protected function _mapRecursive($ary, $fct) {
    // -- Is it a scalar ? Fine then, just have to execute $fct on $ary !
    if (is_scalar($ary)) {
      return $fct($ary);
    }

    // -- Not traversable (resource, object) ?
    if ((is_object($ary) && !($ary instanceof Traversable)) || is_resource($ary)) {
      return $ary;
    }

    foreach ($ary as &$val) {
      $val = $this->_mapRecursive($val, $fct);
    }

    return $ary;
  }


  /**#@-*/
}

/*
 * EOF
 */
