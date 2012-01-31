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

/**
 * Template Variable
 * 
 * @package Link 
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class Link_Var implements ArrayAccess {
  /** @var mixed $_val */
  public $value = null;
  
  /** @param mixed $_val */
  public function __construct($_val) {
    $this->value = $_val;
  }
  
  public function offsetExists($_offset) {
    return isset($this->value[$_offset]);
  }

  public function offsetGet($_offset) {
    return $this->value[$_offset];
  }

  public function offsetSet($_offset, $_value) {
    if ($_offset === null) {
      $this->value[] = $_value;
    } else {
      $this->value[$_offset] = $_value;
    }
  }

  public function offsetUnset($_offset) {
    unset($this->value[$_offset]);
  }
  
  /**
   * Applies a filter on this variable
   * 
   * @param string $_filter Filter's name
   * @param mixed $arg,... Additionnal args
   * @return Link_Var new variable with the filter applied
   */
  public function filter($_filter) {
    if (!method_exists('Link_Filters', $_filter)) {
      trigger_error('The filter' . $_filter . ' is not declared ; it will be ignored.', E_USER_NOTICE);
      return $this;
    }
    
    $args = func_get_args();
    array_shift($args); array_unshift($args, $this->value);
    
    return new self(call_user_func_array('Link_Filters::' . $_filter, $args));
  }
  
  public function __toString() {
    return $this->value;
  }

  /**
   * Shortcut to call a filter ($var->nl2br()->protect(), ...)
   * 
   * {@inheritedDoc}
   */
  public function __call($_method, $_args) {
    return call_user_func_array(array($this, 'filter'), func_get_args());
  }
}
