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
    private
        /** @var Link_Environment */
        $_environment = null,

        $_value = null;

    public function __construct(Link_Environment $env, $value) {
        $this->setEnvironment($env);
        $this->setValue($value);
    }

    /** {@inheritDoc} */
    public function offsetGet($offset) {
        if (!isset($this->_value[$offset])) {
            trigger_error('The offset "' . $offset . '" is not defined for this variable', E_USER_NOTICE);
            return null;
        }

        if (!$this->_value[$offset] instanceof self) {
            $this->_value[$offset] = new self($this->_environment, $this->_value[$offset]);
        }

        return $this->_value[$offset];
    }

    /** {@inheritDoc} */
    public function offsetSet($offset, $value) {
        if (!$value instanceof self) {
            $value = new self($this->_environment, $value);
        }

        $this->_value[$offset] = $value;
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
        $this->_value = $value;

        return $this;
    }

    /** @return Link_Environement */
    public function getEnvironment() {
        return $this->_environment;
    }

    public function setEnvironment(Link_Environment $env) {
        $this->_environment = $env;

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
                return $this->getValue()->$getter();
            }

            return $this->getValue()->$property;
        }

        throw new Link_Exception_Runtime(sprintf('Property "%s" is not defined on this variable', $property));
    }

    /** {@inheritDoc} */
    public function __set($property, $value) {
        if (is_object($this->getValue())) {
            $setter = 'set' . ucfirst($property);

            if (method_exists($this->getValue(), $setter)) {
                $this->getValue()->$setter($value);

                return;
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

}