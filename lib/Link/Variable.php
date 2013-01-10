<?php
/**
 * This file is part of Link TPL
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Copyleft (c) 2007+, Baptiste Clavié, Talus' Works
 * @link      http://www.talus-works.net Talus' Works
 * @license   http://www.opensource.org/licenses/BSD-3-Clause Modified BSD License
 * @version   $Id$
 */

/**
 * Represents a variable in a template
 *
 * @package Link
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class Link_Variable implements Link_VariableInterface {
    private $_value = null;

    public function __construct($value = null) {
        $this->setValue($value);
    }

    /** {@inheritDoc} */
    public function offsetGet($offset) {
        if (!isset($this->_value[$offset])) {
            trigger_error('The offset "' . $offset . '" is not defined for this variable', E_USER_NOTICE);
            return null;
        }

        return $this->_value[$offset];
    }

    /** {@inheritDoc} */
    public function offsetSet($offset = null, $value) {
        if (!is_array($this->_value) && !$this->_value instanceof ArrayAccess && ('string' !== getType($this->_value) && 'integer' !== getType($offset))) {
            throw new Link_Exception_Runtime('This variable is not an array, a traversable, or a string and a numeric offset');
        }

        if (null !== $offset) {
            $this->_value[$offset] = $this->toSelf($value);

            return;
        }

        $this->_value[] = $this->toSelf($value);
    }

    /** {@inheritDoc} */
    public function offsetExists($offset) {
        return isset($this->_value[$offset]);
    }

    /** {@inheritDoc} */
    public function offsetUnset($offset) {
        unset($this->_value[$offset]);
    }

    /** {@inheritDoc} */
    public function getIterator() {
        if ($this->getValue() instanceof Traversable) {
            return new IteratorIterator($this->getValue());
        }

        if (is_array($this->getValue())) {
            return new ArrayIterator($this->getValue());
        }

        trigger_error('This variable is not iterable', E_USER_WARNING);

        return new EmptyIterator;
    }

    /** {@inheritDoc} */
    public function count() {
        return count($this->getValue());
    }

    /** {@inheritDoc} */
    public function getValue() {
        return $this->_value;
    }

    public function setValue($value) {
        if (!$value instanceof self && ($value instanceof Traversable || is_array($value))) {
            foreach ($value as &$val) {
                $val = $this->toSelf($val);
            }
        }

        $this->_value = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function __toString() {
        return (string) $this->getValue();
    }

    /** {@inheritDoc} */
    public function __get($property) {
        if (is_object($this->getValue())) {
            // try to find a getter
            $getter = 'get' . ucfirst($property);

            if (method_exists($this->getValue(), $getter)) {
                return $this->toSelf($this->getValue()->$getter());
            }

            if (isset($this->getValue()->$property) || property_exists($this->getValue(), $property)) {
                return $this->toSelf($this->getValue()->$property);
            }
        }

        throw new Link_Exception_Runtime(sprintf('Property "%s" is not defined on this variable', $property));
    }

    /** {@inheritDoc} */
    public function __set($property, $value) {
        if (is_object($this->getValue())) {
            $setter = 'set' . ucfirst($property);

            if (method_exists($this->getValue(), $setter)) {
                return $this->getValue()->$setter($value);
            }

            $this->getValue()->$property = $value;

            return;
        }

        throw new Link_Exception_Runtime(sprintf('Property "%s" is not defined or accessible on this variable', $property));
    }

    /** {@inheritDoc} */
    public function __call($method, array $arguments) {
        if (is_object($this->getValue()) && method_exists($this->getValue(), $method) && is_callable(array($this->getValue(), $method))) {
            return call_user_func_array(array($this->getValue(), $method), $arguments);
        }

        throw new Link_Exception_Runtime(sprintf('Method "%s" is not defined or not accessible on this variable.', $method));
    }

    /**
     * Transforms a value to an instance of this class
     *
     * @param mixed $value value to transform
     * @return Link_VariableInterface
     */
    private function toSelf($value) {
        if (!$value instanceof self) {
            $value = new self($value);
        }

        return $value;
    }

}