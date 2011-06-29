<?php
/**
 * Parser for Talus' TPL's templates scripts.
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
 * @package Talus' Works
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @copyright ©Talus, Talus' Works 2008+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @version $Id$
 */

// -- If PHP < 5.3, emulating E_USER_DEPRECATED
if (!defined('E_USER_DEPRECATED')) {
  define('E_USER_DEPRECATED', E_USER_NOTICE);
}

// -- If PHP < 5.2.7, emulate PHP_VERSION_ID
if (!defined('PHP_VERSION_ID')) {
  $v = explode('.', PHP_VERSION);
  
  define('PHP_VERSION_ID', $v[0] * 10000 + $v[1] * 100 + $v[2]);
}

/**
 * Template's Parser
 *
 * This class handle the transformation from a Talus TPL code to an optimized
 * PHP code, which can be used by PHP.
 */
class Talus_TPL_Parser implements Talus_TPL_Parser_Interface {
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
    $_compact,
    $_filters,
    $_parse;

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
   * @params array $options options to be given to the parser (see above)
   * @return void
   */
  public function __construct(array $_options = array()){
    $options = array_merge(array(
      'compact' => false,
      'filters' => 'Talus_TPL_Filters',
      'parse' => self::DEFAULTS
     ), $_options);
    
    $this->_compact = $options['compact'];
    $this->_filters = $options['filters'];
    $this->_parse = $options['parse'];
  }

  /**
   * Accessor for a given parameter
   * 
   * (Not valid since 1.11 : acts as a stub for compatibilty)
   *
   * @param string $param Parameter's name
   * @param mixed $value Parameter's value (if setter)
   * @return mixed Parameter's value
   */
  public function parameter($param, $value = null) {
    static $params = array(
      'parse' => 'parse',
      'set_compact' => 'compact'
     );
    
    if (!isset($params[$param])) {
      return null;
    }
    
    $param = &$this->{'_' . $params[$param]};
    
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

    // -- Stubs for blocks
    $script = str_replace(array('<blockelse />', '</block>'), array('<foreachelse />', '</foreach>'), $script);
    $script = preg_replace_callback('`<block name="(' . self::REGEX_PHP_ID . ')"(?: parent="(' . self::REGEX_PHP_ID . ')")?>`', array($this, '_block'), $script);

    $recursives = array(
      // -- Block variables ({block.VAR1}, ...)
      // -- EX REGEX ; [a-z_\xe0-\xf6\xf8-\xff][a-z0-9_\xe0-\xf6\xf8-\xff]*
      '`\{(' . self::REGEX_PHP_ID . ')\.(?!val(?:ue)?)(' . self::REGEX_PHP_ID . ')(' . self::REGEX_PHP_SUFFIX . ')}`' => '{$1.value[\'$2\']$3}',
      '`\{\$(' . self::REGEX_PHP_ID . ')\.(?!val(?:ue)?)(' . self::REGEX_PHP_ID . ')(' . self::REGEX_PHP_SUFFIX . ')}`' => '{$$1.value[\'$2\']$3}'
     );

    // -- Filter's transformations
    if ($this->_parse & self::FILTERS) {
      $matches = array();
      while (preg_match('`\{(\$?' . self::REGEX_PHP_ID . '(?:\.value' . self::REGEX_PHP_SUFFIX . '|key|current|size|' . self::REGEX_PHP_SUFFIX . ')?)\|((?:' . self::REGEX_PHP_ID . '(?::.+?)*\|?)+)}`', $script, $matches)) {
        $script = str_replace($matches[0], $this->_filters($matches[1], $matches[2]), $script);
      }
    }

    // -- Inclusions
    // @todo optimize this stuff
    if ($this->_parse & self::INCLUDES) {
      $script = preg_replace_callback('`<(include|require) tpl="((?:.+?\.html(?:\?[^\"]*)?)|(?:\{\$(?:' . self::REGEX_PHP_ID . '(?:' . self::REGEX_PHP_SUFFIX . ')?})))"(?: once="(true|false)")? />`', array($this, '_includes'), $script);
    }

    // -- <foreach> tags
    $script = preg_replace_callback('`<foreach ar(?:ra)?y="\{\$(' . self::REGEX_PHP_ID . ')}">`', array($this, '_foreach'), $script);
    $script = preg_replace_callback('`<foreach ar(?:ra)?y="\{\$(' . self::REGEX_PHP_ID . '(?:\.value' . self::REGEX_PHP_SUFFIX . ')?)}" as="\{\$(' . self::REGEX_PHP_ID . ')}">`', array($this, '_foreach'), $script);

    // -- Simple regex which doesn't need any recursive treatment.
    $not_recursives = array(
       // -- Foreach special vars (key, size, is_last, is_first, current)
       // -- keys : key of this iteration
       '`\{(' . self::REGEX_PHP_ID . ').key}`' => '<?php echo $__tpl_foreach[\'$1\'][\'key\']; ?>',
       '`\{\$(' . self::REGEX_PHP_ID . ').key}`' => '$__tpl_foreach[\'$1\'][\'key\']',

       // -- size : shows the size of the array
       '`\{(' . self::REGEX_PHP_ID . ').size}`' => '<?php echo $__tpl_foreach[\'$1\'][\'size\']; ?>',
       '`\{\$(' . self::REGEX_PHP_ID . ').size}`' => '$__tpl_foreach[\'$1\'][\'size\']',

       // -- current : returns in which iteration we are
       '`\{(' . self::REGEX_PHP_ID . ').cur(?:rent)?}`' => '<?php echo $__tpl_foreach[\'$1\'][\'current\']; ?>',
       '`\{\$(' . self::REGEX_PHP_ID . ').cur(?:rent)?}`' => '$__tpl_foreach[\'$1\'][\'current\']',

       // -- is_first : checks if this is the first iteration
       '`\{\$(' . self::REGEX_PHP_ID . ').is_first}`' => '($__tpl_foreach[\'$1\'][\'current\'] == 1)',

       // -- is_last : checks if this is the last iteration
       '`\{\$(' . self::REGEX_PHP_ID . ').is_last}`' => '($__tpl_foreach[\'$1\'][\'current\'] == $__tpl_foreach[\'$1\'][\'size\'])'
      );

    $recursives = array_merge($recursives, array(
      // -- Foreach values
      '`\{(' . self::REGEX_PHP_ID . ').val(?:ue)?(' . self::REGEX_PHP_SUFFIX . ')}`' => '<?php echo $__tpl_foreach[\'$1\'][\'value\']$2; ?>',
      '`\{\$(' . self::REGEX_PHP_ID . ').val(?:ue)?(' . self::REGEX_PHP_SUFFIX . ')}`' => '$__tpl_foreach[\'$1\'][\'value\']$2',

      // -- Simple variables ({VAR1}, {VAR2[with][a][set][of][keys]}, ...)
      '`\{(' . self::REGEX_PHP_ID . self::REGEX_PHP_SUFFIX . ')}`' => '<?php echo $__tpl_vars__$1; ?>',
      '`\{\$(' . self::REGEX_PHP_ID . self::REGEX_PHP_SUFFIX . ')}`' => '$__tpl_vars__$1'
     ));

    // -- No Regex (faster !)
    $noRegex = array(
      '</foreach>' => '<?php } endif; $__tpl_refering_var = array_pop($__tpl_foreach_ref); if (isset($__tpl_foreach[$__tpl_refering_var])) unset($__tpl_foreach[$__tpl_refering_var]); ?>',
      '<foreachelse />' => '<?php } else : if (true) { ?>',

      '{\\' =>  '{'
     );

    // -- Constants
    if ($this->_parse & self::CONSTANTS) {
      //[a-zA-Z_\xe0-\xf6\xf8-\xff\xc0-\xd6\xd8-\xde][a-zA-Z0-9_\xe0-\xf6\xf8-\xff\xc0-\xd6\xd8-\xde]*
      $not_recursives['`\{__(' . self::REGEX_PHP_ID . ')__}`i'] = '<?php echo $1; ?>';
      $not_recursives['`\{__$(' . self::REGEX_PHP_ID . ')__}`i'] = '$1';
    }

    // -- Conditions tags (<if>, <elseif />, <else />)
    if ($this->_parse & self::CONDITIONS) {
      $not_recursives = array_merge($not_recursives, array(
        '`<if cond(?:ition)?="(.+?)">`' => '<?php if ($1) : ?>',
        '`<el(?:se)?if cond(?:ition)?="(.+?)" />`' => '<?php elseif ($1) : ?>'
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
     * (?><?php), meaning any spaces, newlines, tabs, or whatever will be
     * cleansed, including the PHP tags in the middle. 
     * Also, if `PHP_VERSION_ID >= 5040`, then we can use the small syntax
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

    return sprintf('<?php
      $__tpl_foreach_ref[] = \'%1$s\';
      $__tpl_foreach[\'%1$s\'] = array(
        \'value\' => null,
        \'key\' => null,
        \'size\' => isset({$%2$s}) ? count({$%2$s}) : 0,
        \'current\' => 0
       );

      if ($__tpl_foreach[\'%1$s\'][\'size\'] > 0) :
        foreach ({$%2$s} as {$%1$s.key} => &{$%1$s.value}) {
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

    return sprintf('<?php $tpl->includeTpl(%1$s%2$s, %3$s, Talus_TPL::%4$s_TPL); ?>',
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
   * @param string $delim String's delimiters
   * @return string Escaped value
   */
  protected function _escape($arg, $delim = '\'') {
    if (($arg[0] != $delim || $arg[mb_strlen($arg) - 1] != $delim)
     && ($arg[0] != '{' || $arg[mb_strlen($arg) - 1] != '}')
     && !filter_var($arg, FILTER_VALIDATE_INT)) {
      $arg = sprintf('%1$s%2$s%1$s', $delim, addcslashes($arg, $delim));
    }

    return $arg;
  }

  /**
   * Blocks interpretations
   *
   * This method is now acting as a stub for <block> tags ; it replaces them by
   * a foreach. If there is a parent block, we need to alter a little the block's
   * name.
   *
   * @param array $match Regex matches
   * @return string
   * @see self::compile()
   * @see 97
   * @deprecated 1.10
   */
  protected function _block(array $match){
    $blockName = $match[1];
    $as = '';

    if (!empty($match[2])) {
      $blockName = sprintf('%1$s_%2$s', $match[2], $match[1]);
      $as = sprintf(' as="{$%1$s}"', $match[1]);
    }

    // -- Little warning...
    trigger_error('Blocks are now deprecated. Please refrain from using them, and use <foreach> instead...', E_USER_DEPRECATED);
    return sprintf('<foreach array="{$%1$s}"%2$s>', $blockName, $as);
  }
}

/** EOF /**/
