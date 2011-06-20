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

/**
 * Template's Parser
 *
 * This class handle the transformation from a Talus TPL code to an optimized
 * PHP code, which can be used by PHP.
 */
class Talus_TPL_Parser implements Talus_TPL_Parser_Interface {
  protected $_parameters = array();

  const
    SET = 1,
    FILTERS = 2,
    INCLUDES = 4,
    CONDITIONS = 8,
    CONSTANTS = 16,

    BASICS = 4,
    DEFAULTS = 30,
    ALL = 31,

    // -- Regex used
    REGEX_PHP_ID = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*', // PHP Identifier
    REGEX_ARRAYS = '(?:\[[^]]+?])+'; // PHP Arrays

  /**
   * Initialisation
   *
   * @return void
   */
  public function __construct(){
    $this->parameter('parse', self::DEFAULTS);
    $this->parameter('set_compact', false);
    $this->parameter('namespace', '');
  }

  /**
   * Accessor for a given parameter
   *
   * @param string $param Parameter's name
   * @param mixed $value Parameter's value (if setter)
   * @return mixed Parameter's value
   */
  public function parameter($param, $value = null) {
    if ($value !== null) {
      $this->_parameters[$param] = $value;
    }

    return $this->_parameters[$param];
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

    $nspace = $this->parameter('namespace');

    if (!empty($nspace)) {
      $nspace .= ':';
    }

    // -- Stubs for blocks
    $script = str_replace(array('<' . $nspace . 'blockelse />', '</' . $nspace . 'block>'), array('<' . $nspace . 'foreachelse />', '</' . $nspace . 'foreach>'), $script);
    $script = preg_replace_callback('`<' . $nspace . 'block name="(' . self::REGEX_PHP_ID . ')"(?: parent="(' . self::REGEX_PHP_ID . ')")?>`', array($this, '_block'), $script);

    $recursives = array(
      // -- Block variables ({block.VAR1}, ...)
      // -- EX REGEX ; [a-z_\xe0-\xf6\xf8-\xff][a-z0-9_\xe0-\xf6\xf8-\xff]*
      '`\{(' . self::REGEX_PHP_ID . ')\.(?!val(?:ue)?)(' . self::REGEX_PHP_ID . ')(' . self::REGEX_ARRAYS . ')?}`' => '{$1.value[\'$2\']$3}',
      '`\{\$(' . self::REGEX_PHP_ID . ')\.(?!val(?:ue)?)(' . self::REGEX_PHP_ID . ')(' . self::REGEX_ARRAYS . ')?}`' => '{$$1.value[\'$2\']$3}'
     );

    // -- Filter's transformations
    if ($this->parameter('parse') & self::FILTERS) {
      $matches = array();
      while (preg_match('`\{(\$?' . self::REGEX_PHP_ID . '(?:\.value(?:' . self::REGEX_ARRAYS . ')?|key|current|size)?)\|((?:' . self::REGEX_PHP_ID . '(?::.+?)*\|?)+)}`', $script, $matches)) {
        $script = str_replace($matches[0], $this->_filters($matches[1], $matches[2]), $script);
      }
    }

    // -- Inclusions
    // @todo optimize this stuff
    if ($this->parameter('parse') & self::INCLUDES) {
      $script = preg_replace_callback('`<' . $nspace . '(include|require) tpl="((?:.+?\.html(?:\?[^\"]*)?)|(?:\{\$(?:' . self::REGEX_PHP_ID . '(?:' . self::REGEX_ARRAYS . ')?})))"(?: once="(true|false)")? />`', array($this, '_includes'), $script);
    }

    // -- <foreach> tags
    $script = preg_replace_callback('`<' . $nspace . 'foreach ar(?:ra)?y="\{\$(' . self::REGEX_PHP_ID . ')}">`', array($this, '_foreach'), $script);
    $script = preg_replace_callback('`<' . $nspace . 'foreach ar(?:ra)?y="\{\$(' . self::REGEX_PHP_ID . '(?:\.value(?:' . self::REGEX_ARRAYS . ')?)?)}" as="\{\$(' . self::REGEX_PHP_ID . ')}">`', array($this, '_foreach'), $script);

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
       '`\{\$(' . self::REGEX_PHP_ID . ').is_last}`' => '($__tpl_foreach[\'$1\'][\'current\'] == $__tpl_foreach[\'$1\'][\'count\'])'
      );

    $recursives = array_merge($recursives, array(
      // -- Foreach values
      '`\{(' . self::REGEX_PHP_ID . ').val(?:ue)?(' . self::REGEX_ARRAYS . ')?}`' => '<?php echo $__tpl_foreach[\'$1\'][\'value\']$2; ?>',
      '`\{\$(' . self::REGEX_PHP_ID . ').val(?:ue)?(' . self::REGEX_ARRAYS . ')?}`' => '$__tpl_foreach[\'$1\'][\'value\']$2',

      // -- Simple variables ({VAR1}, {VAR2[with][a][set][of][keys]}, ...)
      '`\{(' . self::REGEX_PHP_ID . '(?:' . self::REGEX_ARRAYS . ')?)}`' => '<?php echo $__tpl_vars__$1; ?>',
      '`\{\$(' . self::REGEX_PHP_ID . '(?:' . self::REGEX_ARRAYS . ')?)}`' => '$__tpl_vars__$1'
     ));

    // -- No Regex (faster !)
    $noRegex = array(
      "</{$nspace}foreach>" => '<?php } endif; $__tpl_refering_var = array_pop($__tpl_foreach_ref); if (isset($__tpl_foreach[$__tpl_refering_var])) unset($__tpl_foreach[$__tpl_refering_var]); ?>',
      "<{$nspace}foreachelse />" => '<?php } else : if (true) { ?>',

      '{\\' =>  '{'
     );

    // -- <set> Tag
    if ($this->parameter('parse') & self::SET) {
      $not_recursives['`<' . $nspace . 'set var="(' . self::REGEX_PHP_ID . ')(\[(?!]">)(?:.*?)])?">(?!"</set>)(.+?)</set>`'] = '<?php $__tpl_vars__$1$2 = \'$3\'; ?>';
    }

    // -- Constants
    if ($this->parameter('parse') & self::CONSTANTS) {
      //[a-zA-Z_\xe0-\xf6\xf8-\xff\xc0-\xd6\xd8-\xde][a-zA-Z0-9_\xe0-\xf6\xf8-\xff\xc0-\xd6\xd8-\xde]*
      $not_recursives['`\{__(' . self::REGEX_PHP_ID . ')__}`i'] = '<?php echo $1; ?>';
      $not_recursives['`\{__$(' . self::REGEX_PHP_ID . ')__}`i'] = '$1';
    }

    // -- Conditions tags (<if>, <elseif />, <else />)
    if ($this->parameter('parse') & self::CONDITIONS) {
      $not_recursives = array_merge($not_recursives, array(
        '`<' . $nspace . 'if cond(?:ition)?="(.+?)">`' => '<?php if ($1) : ?>',
        '`<' . $nspace . 'el(?:se)?if cond(?:ition)?="(.+?)" />`' => '<?php elseif ($1) : ?>'
       ));

      $noRegex["<{$nspace}else />"] = '<?php else : ?>';
      $noRegex["</{$nspace}if>"] = '<?php endif; ?>';
    }
    
    $script = preg_replace(array_keys($not_recursives), array_values($not_recursives), $script);
    
    foreach ($recursives as $regex => $replace) {
      while(preg_match($regex, $script)) {
        $script = preg_replace($regex, $replace, $script);
      }
    }
    
    $script = str_replace(array_keys($noRegex), array_values($noRegex), $script);

    /*
     * Cleaning the newly made script...
     *
     * Depending on the value of the "set_compact" parameter, if it is on, everything
     * considered as "emptyness" between two php tags (?><?php), meaning any spaces,
     * newlines, tabs, or whatever will be cleansed, including the PHP tags in the
     * middle.
     *
     * If it is off (by default), only the ?><?php tags will be removed.
     */
    if ($this->parameter('set_compact')) {
      $script = preg_replace('`\?>\s*<\?php`', '', $script);
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
        \'size\' => count({$%2$s}),
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
     * Weh just have to add the $ in front of the name of the variable, and clearly
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
      if (!method_exists('Talus_TPL_Filters', $fct)){
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

      $return = sprintf('Talus_TPL_Filters::%1$s(%2$s%3$s)', $fct, $return, $params);
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
   * @deprecated Upwards 1.9.0
   */
  protected function _block(array $match){
    $blockName = $match[1];
    $as = '';

    $nspace = $this->parameter('namespace');

    if (!empty($nspace)) {
      $nspace .= ':';
    }

    if (!empty($match[2])) {
      $blockName = sprintf('%1$s_%2$s', $match[2], $match[1]);
      $as = sprintf(' as="%1$s"', $match[1]);
    }

    // -- Little warning...
    trigger_error('Blocks are now deprecated. Please refrain from using them, and use <foreach> instead...', E_USER_DEPRECATED);
    return sprintf('<' . $nspace . 'foreach array="%1$s"%2$s>', $blockName, $as);
  }


}

/** EOF /**/
