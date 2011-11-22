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

// -- emulating E_USER_DEPRECATED if php < 5.3
defined('E_USER_DEPRECATED') || define('E_USER_DEPRECATED', E_USER_NOTICE);

// -- If PHP < 5.2.7, emulate PHP_VERSION_ID
if (!defined('PHP_VERSION_ID')) {
  $v = explode('.', PHP_VERSION);

  define('PHP_VERSION_ID', $v[0] * 10000 + $v[1] * 100 + $v[2]);
}

/**
 * Templates' Parser
 *
 * This class handle the transformation from a Link TPL code to an optimized
 * PHP code, which can be used by PHP.
 *
 * @package Link
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class Link_Parser implements Link_Interface_Parser {
  const
    FILTERS = 1,
    INCLUDES = 2,
    CONDITIONS = 4,
    CONSTANTS = 8,

    BASICS = 4,
    DEFAULTS = 15,
    ALL = 15,

    // -- Regex used
    REGEX_PHP_ID = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*', // PHP Identifier
    REGEX_PHP_SUFFIX = '(?:\[[^]]+?]|->[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*'; // PHP Suffixes (arrays, objects)

  protected
    $_compact = false,
    $_filters = 'Link_Filters',
    $_parse = self::DEFAULTS;

  /**
   * Initialisation
   *
   * Options to be given to the parser :
   *  - compact : Compact the resulting php source code (deleting any blanks
   *              between a closing and an opening php tag, ...) ? true / false
   *
   *  - parse : Defines what are the objects to be parsed (inclusions, filters,
   *            conditions, ...). Can be a combination of the class' constants.
   *
   *  - filters : Defines which class handles the filters.
   *
   * @param array $_options options to be given to the parser (see above)
   * @return void
   */
  public function __construct(array $_options = array()){
    $defaults = array(
      'compact' => false,
      'filters' => 'Link_Filters',
      'parse' => self::DEFAULTS
     );

    $options = array_replace_recursive($defaults, $_options);

    $this->_compact = $options['compact'];
    $this->_filters = $options['filters'];
    $this->_parse = $options['parse'];
  }

  /**
   * Accessor for a given parameter
   *
   * @param string $param Parameter's name
   * @param mixed $value Parameter's value (if setter)
   * @return mixed Parameter's value
   */
  public function parameter($param, $value = null) {
    if (!property_exists($this, '_' . $param)) {
      return null;
    }

    $param = '_' . $param;
    $param = &$this->$param;

    if ($value !== null) {
      $param = $value;
    }

    return $param;
  }

  /**
   * Transform a TPL syntax towards an optimized PHP syntax
   *
   * @param string $script TPL script to parse
   * @return string
   */
  public function parse($script){
    $script = str_replace('<?' ,'<?php echo \'<?\'; ?>', $script);
    $script = preg_replace('`/\*.*?\*/`s', '', $script);

    // -- Filter's transformations
    if ($this->_parse & self::FILTERS) {
      $matches = array();
      while (preg_match('`\{(\$?' . self::REGEX_PHP_ID . '(?:\.val(?:ue)?' . self::REGEX_PHP_SUFFIX . '|key|current|size|' . self::REGEX_PHP_SUFFIX . ')?)\|((?:' . self::REGEX_PHP_ID . '(?::\{\$' . self::REGEX_PHP_ID . self::REGEX_PHP_SUFFIX . '}|[^|}]+?)*\|?)+)}`', $script, $matches)) {
        $script = str_replace($matches[0], $this->_filters($matches[1], $matches[2]), $script);
      }
    }

    // -- Inclusions
    if ($this->_parse & self::INCLUDES) {
      $script = preg_replace_callback('`<(include|require) tpl="(.+?)(?=(?<!\\)")"(?: once="(true|false)")? />`', array($this, '_includes'), $script);
    }

    // -- <foreach> tags
    $script = preg_replace_callback('`<foreach ar(?:ra)?y="\{\$(' . self::REGEX_PHP_ID . ')}">`', array($this, '_foreach'), $script);
    $script = preg_replace_callback('`<foreach ar(?:ra)?y="\{\$(' . self::REGEX_PHP_ID . '(?:\.val(?:ue)?' . self::REGEX_PHP_SUFFIX . ')?)}" as="\{\$(' . self::REGEX_PHP_ID . ')}">`', array($this, '_foreach'), $script);

    // -- Simple regex which doesn't need any recursive treatment.
    $not_recursives = array(
       // -- Foreach special vars (key, size, is_last, is_first, current)
       // -- keys : key of this iteration
       '`\{(' . self::REGEX_PHP_ID . ').key}`' => '<?php echo $__tpl_foreach__$1[\'key\']; ?>',
       '`\{\$(' . self::REGEX_PHP_ID . ').key}`' => '$__tpl_foreach__$1[\'key\']',

       // -- size : shows the size of the array
       '`\{(' . self::REGEX_PHP_ID . ').size}`' => '<?php echo $__tpl_foreach__$1[\'size\']; ?>',
       '`\{\$(' . self::REGEX_PHP_ID . ').size}`' => '$__tpl_foreach__$1[\'size\']',

       // -- current : returns in which iteration we are
       '`\{(' . self::REGEX_PHP_ID . ').cur(?:rent)?}`' => '<?php echo $__tpl_foreach__$1[\'current\']; ?>',
       '`\{\$(' . self::REGEX_PHP_ID . ').cur(?:rent)?}`' => '$__tpl_foreach__$1[\'current\']',

       // -- is_first : checks if this is the first iteration
       '`\{\$(' . self::REGEX_PHP_ID . ').is_first}`' => '($__tpl_foreach__$1[\'current\'] == 1)',

       // -- is_last : checks if this is the last iteration
       '`\{\$(' . self::REGEX_PHP_ID . ').is_last}`' => '($__tpl_foreach__$1[\'current\'] == $__tpl_foreach__$1[\'size\'])'
      );

    $recursives = array(
      // -- Foreach values
      '`\{(' . self::REGEX_PHP_ID . ').val(?:ue)?(' . self::REGEX_PHP_SUFFIX . ')}`' => '<?php echo $__tpl_foreach__$1[\'value\']$2; ?>',
      '`\{\$(' . self::REGEX_PHP_ID . ').val(?:ue)?(' . self::REGEX_PHP_SUFFIX . ')}`' => '$__tpl_foreach__$1[\'value\']$2',

      // -- Simple variables ({VAR1}, {VAR2[with][a][set][of][keys]}, ...)
      '`\{(' . self::REGEX_PHP_ID . self::REGEX_PHP_SUFFIX . ')}`' => '<?php echo $__tpl_vars__$1; ?>',
      '`\{\$(' . self::REGEX_PHP_ID . self::REGEX_PHP_SUFFIX . ')}`' => '$__tpl_vars__$1'
     );

    // -- No Regex (faster !)
    $noRegex = array(
      /*
       * with ref :
       * <code>
       *  $__tpl_refering_var = array_pop($__tpl_foreach_ref);
       *
       *  if (isset($__tpl_foreach[$__tpl_refering_var]))
       *    unset($__tpl_foreach[$__tpl_refering_var]);
       * </code>
       */
      '</foreach>' => '<?php } endif; ?>',
      '<foreachelse />' => '<?php } else : if (true) { ?>',

      '{\\' =>  '{'
     );

    // -- Constants
    if ($this->_parse & self::CONSTANTS) {
      //[a-zA-Z_\xe0-\xf6\xf8-\xff\xc0-\xd6\xd8-\xde][a-zA-Z0-9_\xe0-\xf6\xf8-\xff\xc0-\xd6\xd8-\xde]*
      $not_recursives['`\{__((?:' . self::REGEX_PHP_ID . '::)?' . self::REGEX_PHP_ID . ')__}`i'] = '<?php echo $1; ?>';
      $not_recursives['`\{__$((?:' . self::REGEX_PHP_ID . '::)?' . self::REGEX_PHP_ID . ')__}`i'] = '$1';
    }

    // -- Conditions tags (<if>, <elseif />, <else />)
    if ($this->_parse & self::CONDITIONS) {
      $not_recursives = array_merge($not_recursives, array(
        '`<if cond(?:ition)?="(.+?)(?=(?<!\\)">)">`' => '<?php if ($1) : ?>',
        '`<el(?:se)?if cond(?:ition)?="(.+?)(?=(?<!\\)" />)" />`' => '<?php elseif ($1) : ?>'
       ));

      $noRegex['<else />'] = '<?php else : ?>';
      $noRegex['</if>'] = '<?php endif; ?>';
    }

    $script = preg_replace(array_keys($not_recursives), array_values($not_recursives), $script);

    foreach ($recursives as $regex => $replace) {
      while(preg_match($regex, $script)) {
        $script = preg_replace($regex, $replace, $script);
      }
    }

    $script = str_replace(array_keys($noRegex), array_values($noRegex), $script);

    /*
     * Cleaning the newly made script... depending on the value of the `$compact`
     * parameter.
     *
     * If it is on, everything considered as "emptyness" between two php tags
     * (?`><?php`), meaning any spaces, newlines, tabs, or whatever will be
     * cleansed, including the PHP tags in the middle.
     * Also, if `PHP_VERSION_ID >= 50400`, then we can use the small syntax
     * `<?=` instead of `<?php echo`, as it is not dependant of the value of
     * the parameter "short_syntax" of php.ini.
     *
     * ... But if it is off (by default), only the ?><?php tags will be removed.
     */
    if ($this->_compact === true) {
      $script = preg_replace('`\?>\s*<\?php`', '', $script);

      if (PHP_VERSION_ID >= 50400) {
        $script = str_replace('<?php echo', '<?=', $script);
      }
    } else {
      $script = str_replace('?><?php', '', $script);
    }

    return $script;
  }

  /**
   * Parse a TPL script
   * Implementation of the magic method __invoke() for PHP >= 5.3
   *
   * @param string $script TPL Script to be parsed
   * @return string PHP Code made
   * @see self::parse()
   */
  public function __invoke($script) {
    return $this->parse($script);
  }

  /**
   * Foreach interpretor
   *
   * @param array $matches REGEX's matches
   * @return string
   */
  protected function _foreach($matches) {
    $varName = $matches[1];

    // -- Is the attribute "as" set ?
    if (isset($matches[2])) {
      $varName = $matches[2];
    }

    // with ref : $__tpl_foreach_ref[] = \'%1$s\';
    return sprintf('<?php
      $__tpl_foreach__%1$s = array(
        \'value\' => null,
        \'key\' => null,
        \'size\' => isset({$%2$s}) ? count({$%2$s}) : 0,
        \'current\' => 0
       );

      if ({$%1$s.size} > 0) :
        foreach ({$%2$s} as {$%1$s.key} => {$%1$s.value}) {
          ++{$%1$s.current}; ?>', $varName, $matches[1]);
  }

  /**
   * Filters implementation
   *
   * Parse all the $filters given for the var $var
   *
   * @param mixed $var Variable
   * @param string $filters Filters
   * @param string $type Variable's type (for {TYPE,VAR})
   * @return string filtered var
   */
  protected function _filters($var = '', $filters = ''){
    $brackets = 0;
    $toPrint = false;
    $return = sprintf('{%s}', $var);
    $filters = array_reverse(array_filter(explode('|', $filters)));

    /*
     * If we wish to print the variable (the significative $ is missing), we have
     * to set up the variable to have a $... Being printed and not returned.
     *
     * We just have to add the $ in front of the name of the variable, and clearly
     * say we have to print the result.
     */
    if ($return[1] != '$') {
      $return = '{$' . mb_substr($return, 1);
      $toPrint = true;
    }

    foreach ($filters as &$filter) {
      $params = explode(':', $filter);
      $fct = array_shift($params);

      // -- unimplemented filter ?
      if (!method_exists($this->_filters, $fct)){
        trigger_error("The filter \"$fct\" does not exist, and thus shall be ignored.\n\n",
                      E_USER_NOTICE);
        continue;
      }

      // -- Filter's Parameters
      if (count($params) > 0) {
        foreach ($params as &$param) {
          $param = $this->_escape($param);
        }

        $params = ', ' . implode(', ', $params);
      } else {
        $params = '';
      }

      $return = sprintf('%1$s::%2$s(%3$s%4$s)', $this->_filters, $fct, $return, $params);
    }

    // -- Printing the return rather than returning it
    if ($toPrint === true){
      $return = sprintf('<?php echo %s; ?>', $return);
    }

    return $return;
  }

  /**
   * Inclusions' Parser
   *
   * @param array $match Regex matchs
   * @return string include function with the right parameters
   * @todo Find a better way to handle variables in the QS
   */
  protected function _includes(array $match) {
    $qs = '';

    // -- A QS was found
    if (strpos($match[2], '?') !== false) {
      list($match[2], $qs) = explode('?', $match[2], 2);
      $qs = sprintf(' . "?%s"', str_replace(array('{', '}'), array('{{', '}}'), $qs));
    }

    return sprintf('<?php $tpl->includeTpl(%1$s%2$s, %3$s, Link_Environnement::%4$s_TPL); ?>',
                   $this->_escape($match[2]), $qs,
                   isset($match[3]) && $match[3] == 'true' ? 'true' : 'false',
                   mb_strtoupper($match[1]));
  }

  /**
   * Escape a given value
   *
   * Will act accordingly if it is a string, a variable, or numbers
   *
   * @param string $arg Value to escape
   * @return string Escaped value
   */
  protected function _escape($arg) {
    if (!filter_var($arg, FILTER_VALIDATE_INT)
     && !empty($arg)
     && !preg_match('`^([\'"]).*?\1$`', $arg)
     && ($arg[0] != '{' || $arg[mb_strlen($arg) - 1] != '}')) {
      switch ($arg) {
        case 'true':
        case 'on':
          $arg = 'true';
          break;

        case 'false':
        case 'off':
          $arg = 'false';
          break;

        default:
          $arg = sprintf('\'%1$s\'', addcslashes($arg, '\''));
          break;
      }
    }

    return $arg;
  }
}

/** EOF /**/
