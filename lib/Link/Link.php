<?php
/**
 * This file is part of Link TPL
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Copyleft (c) 2007+, Baptiste Clavié, Talus' Works
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.opensource.org/licenses/BSD-3-Clause Modified BSD License
 * @version $Id$
 */

defined('E_USER_DEPRECATED') || define('E_USER_DEPRECATED', E_USER_NOTICE);

/**
 * The templating engine itself
 *
 * @package Link
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class Link {
  protected
    $_root = null,

    $_last = array(),
    $_included = array(),

    $_vars = array(),
    $_references = array(),

     $_autoFilters = array(),
     $_filtersClass = 'Link_Filters',

    /**
     * @var Link_Interfaces_Parser
     */
    $_parser = null,

    /**
     * @var Link_Interfaces_Cache
     */
    $_cache = null;

  const
    INCLUDE_TPL = 0,
    REQUIRE_TPL = 1,
    VERSION = 'DEV';

  /**
   * Initialisation.
   *
   * Available options :
   *  - dependencies : Handle the dependencies (parser, cache, ...). Each of
   *                   these must be an object.
   *
   * @param string $root Directory where the templates files are.
   * @param string $cache Directory where the php version of the templates will be stored.
   * @param array $_options Options for the templating engine
   * @return void
   */
  public function __construct($root, $cache, array $_options = array()){
    // -- Resetting the PHP cache concerning the files' information.
    clearstatcache();

    // -- Options
    $defaults = array(
      'dependencies' => array(
        'parser' => new Link_Parser,
        'cache' => new Link_Cache,
       )
     );

    $_options = array_replace_recursive($defaults, $_options);

    // -- Dependency Injection
    $this->dependencies($_options['dependencies']['parser'],
                        $_options['dependencies']['cache']);

    $this->_filtersClass = $this->parser()->parameter('filters');

    $this->dir($root, $cache);
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
  public function dir($root = null, $cache = null) {
    if ($root === null) {
      $root = $this->_root;
    }

    // -- Removing the final "/", if it's there.
    $root = rtrim($root, '/');

    if (!is_dir($root)) {
      throw new Link_Exceptions_Dir(array('%s is not a directory.', $root), 1);
      return;
    }

    $this->_root = $root;

    // -- Let the cache engine handle his own directory !
    $this->_cache->dir($cache);
  }

  /**
   * Accessor for the templates variables.
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
        $value = array_map_recursive(array($this->_filtersClass, $filter), $value);
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
   * @throws Link_Exceptions_Filter
   * @return array
   *
   * @since 1.9.0
   */
  public function autoFilters($name = null) {
    if ($name !== null) {
      if (!method_exists($this->_filtersClass, $name)) {
        throw new Link_Exceptions_Filter(array('The filter %s doesn\'t exist...', $name), 404);
      }

      // -- Applying this filter to all previously declared vars... Except references
      foreach ($this->_vars as $var => &$value) {
        if (in_array($var, $this->_references)) {
          continue;
        }

        $value = array_map_recursive(array($this->_filtersClass, $name), $value);
      }
    }

    return $this->_autoFilters;
  }

  /**
   * Sets a variable $var, referencing $value.
   *
   * @param mixed $var Var's name
   * @param mixed &$value Variable to be referenced by $var
   * @throws Link_Exceptions_Var
   * @return void
   *
   * @since 1.7.0
   */
  public function bind($var, &$value) {
    $this->_vars[$var] = &$value;
    $this->_references[] = $var;
  }

  /**
   * Parse and execute the Template $tpl.
   *
   * If $tpl is an array of files, all the files will be parsed.
   *
   * @param mixed $tpl TPL to be parsed & executed
   * @param mixed $cache If the cache exists, use it
   * @throws Link_Exceptions_Parse
   * @return bool
   */
  public function parse($tpl, $cache = true){
    // -- Critical error if the argument $tpl is empty
    if (strlen((string) $tpl) === 0) {
      throw new Link_Exceptions_Parse('No template to be parsed.', 5);
      return false;
    }

    $file = sprintf('%1$s/%2$s', $this->_root, $tpl);

    if (!isset($this->_last[$file])) {
      if (!is_file($file)) {
        throw new Link_Exceptions_Parse(array('The template <b>%s</b> doesn\'t exist.', $tpl), 6);
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
   * @throws Link_Exceptions_Parse
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
   * @see Link::parse()
   * @return void
   */
  public function __invoke($tpl) {
    return $this->parse($tpl);
  }

  /**
   * Parse and execute a template
   *
   * Do the exact same thing as Link::parse(), but instead of just executing
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
   * @see Link_Parser::parse()
   * @throws Link_Exceptions_Runtime
   * @throws Link_Exceptions_Parse
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
        $this->set($vars);
      }

      $data = $this->pparse($file);
    } catch (Link_Exceptions_Parse $e) {
      /*
       * If we encounter error nÂ°6 AND it is a require tag, throws an exception
       * Link_Exceptions_Runtime instead of Link_Exceptions_Parse. If not,
       * and still a nÂ°6 error, printing the error message, or else throwing this
       * error back.
       */
      if ($e->getCode() === 6) {
        if ($type == self::REQUIRE_TPL) {
          throw new Link_Exceptions_Runtime(array('That was a "require" tag ; The template <b>%s</b> not existing,  the script shall then be interrupted.', $file), 7);
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
   * @return Link_Interfaces_Parser
   */
  public function parser() {
    return $this->_parser;
  }

  /**
   * Cache
   *
   * @return Link_Interfaces_Cache
   */
  public function cache() {
    return $this->_cache;
  }

  /**
   * Dependency Injection handler.
   *
   * @param mixed $dependencies,... Dependencies
   * @throws Link_Exceptions_Dependency
   * @return void
   *
   * @todo Review the mechanism, instead of having too many conditions...
   */
  public function dependencies($dependencies = array()) {
    foreach (func_get_args() as $dependency) {
      if ($dependency instanceof Link_Interfaces_Parser) {
        $this->_parser = $dependency;
      } elseif ($dependency instanceof Link_Interfaces_Cache) {
        $this->_cache = $dependency;
      } else {
        throw new Link_Exceptions_Dependency(
                array('%s is not an acknowledged dependency.', get_class($dependency)));
      }
    }
  }

  /**#@-*/
}

/*
 * Functions dependencies
 */
if (!function_exists('array_replace_recursive')) {
  /**
   * **array_replace_recursive()** replaces the values of the first array with
   * the same values from all the following arrays.
   *
   * If a key from the first array exists in the second array, its value will be
   * replaced by the value from the second array. If the key exists in the
   * second array, and not the first, it will be created in the first array. If
   * a key only exists in the first array, it will be left as is. If several
   * arrays are passed for replacement, they will be processed in order, the
   * later array overwriting the previous values.
   *
   * **array_replace_recursive()** is recursive : it will recurse into arrays
   * and apply the same process to the inner value.
   *
   * When the value in array is scalar, it will be replaced by the value in
   * array1, may it be scalar or array. When the value in array and array1 are
   * both arrays, **array_replace_recursive()** will replace their respective
   * value recursively.
   *
   * @param array &$original The array in which elements are replaced.
   * @param array &$array,... The arrays from which elements will be extracted.
   * @link http://www.php.net/manual/en/function.array-replace-recursive.php#92224
   * @return array Joined array
   */
  function array_replace_recursive(array &$original, array &$array) {
    $arrays = func_get_args();
    $return = array_shift($arrays);

    foreach ($arrays as &$array) {
      foreach ($array as $key => &$value) {
        if (isset($original[$key]) && is_array($original[$key]) && is_array($value)) {
          $return[$key] = array_replace_recursive($return[$key], $value);
        } else {
          $return[$key] = $value;
        }
      }
    }

    return $return;
  }
}

if (!function_exists('array_map_recursive')) {
  /**
   * Applies a function on an item recursively.
   *
   * Unlike it's sister `array_map`, it doesn't accept more than two parameters.
   * Works a bit like  `array_walk_recursive`, but returns an array modified by
   * the callback `$callback`.
   *
   * If the `$item` is a scalar, the function will be directly applied on it. If
   * it is not an object implementing Traversable, or a resource, it will
   * directly be returned.
   *
   * @param callback $callback A valid PHP callback.
   * @param mixed $item the item on which the callback must be applied
   * @param array $userdata Data to be passed on as the third parameter of the callback
   * @return mixed the transformed value
   */
  function array_map_recursive($callback, $item, $userdata = array()) {
    // -- verification that $callback is callable
    if (!is_callable($callback)) {
      trigger_error('recursive_call : not a valid callback', E_USER_WARNING);
      return $item;
    }

    // -- Is it a scalar ? Fine then, just have to execute $fct on $ary !
    if (is_scalar($item)) {
      array_unshift($userdata, $item);
      return call_user_func_array($callback, $userdata);
    }

    // -- Not traversable (resource, object) ?
    if ((is_object($item) && !($item instanceof Traversable)) || is_resource($item)) {
      return $item;
    }

    foreach ($item as &$val) {
      $val = array_map_recursive($callback, $val, $userdata);
    }

    return $item;
  }
}

/*
 * EOF
 */
