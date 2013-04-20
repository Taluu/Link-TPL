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

            // Will not be reached by the tests (the E_USER_NOTICE will intercept it)
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        return $this->_value[$offset];
    }

    /** {@inheritDoc} */
    public function offsetSet($offset = null, $value) {
        if (!is_array($this->_value) && !$this->_value instanceof ArrayAccess && ('string' !== getType($this->_value) || 'integer' !== getType($offset))) {
            throw new Link_Exception_Runtime('This variable is not an array, or a string with a numeric offset');
        }

        if (null !== $offset) {
            $this->_value[$offset] = $value;

            return;
        }

        $this->_value[] = $value;
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

        if (is_array($this->getValue()) || is_object($this->getValue())) {
            return new ArrayIterator($this->getValue());
        }

        trigger_error('This variable is not iterable', E_USER_WARNING);


        // Will not be reached by the tests (the E_USER_WARNING will intercept it)
        // @codeCoverageIgnoreStart
        return new EmptyIterator;
        // @codeCoverageIgnoreEnd
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

    /** {@inheritDoc} */
    public function __toString() {
        return (string) $this->getValue();
    }

    /** {@inheritDoc} */
    public function __get($property) {
        // @codeCoverageIgnoreStart
        if (!is_object($this->getValue())) {
            throw new Link_Exception_Runtime(sprintf('This variable is not an object, but a(n) "%s"', getType($this->getValue())));
        }
        // @codeCoverageIgnoreEnd

        // try to find a getter
        $getter     = 'get' . ucfirst($property);
        $reflection = new ReflectionClass($this->getValue());

        if ($reflection->hasMethod($getter) && $reflection->getMethod($getter)->isPublic()) {
            return $this->getValue()->$getter();
        }

        if (($reflection->hasProperty($property) && $reflection->getProperty($property)->isPublic()) || $reflection->hasMethod('__get')) {
            return $this->getValue()->$property;
        }

        throw new Link_Exception_Runtime(sprintf('Property "%1$s::$%2$s" is not defined or accessible', get_class($this->getValue()), $property));
    }

    /** {@inheritDoc} */
    public function __set($property, $value) {
        // @codeCoverageIgnoreStart
        if (!is_object($this->getValue())) {
            throw new Link_Exception_Runtime(sprintf('This variable is not an object, but a(n) "%s"', getType($this->getValue())));
        }
        // @codeCoverageIgnoreEnd

        $setter     = 'set' . ucfirst($property);
        $reflection = new ReflectionClass($this->getValue());

        if ($reflection->hasMethod($setter) && $reflection->getMethod($setter)->isPublic()) {
            $this->getValue()->$setter($value);

            return;
        }

        if ($reflection->hasProperty($property) && !$reflection->getProperty($property)->isPublic() && !$reflection->hasMethod('__set')) {
            throw new Link_Exception_Runtime(sprintf('Property "%1$s::$%2$s" is not accessible', get_class($this->getValue()), $property));
        }

        $this->getValue()->$property = $value;
    }

    /** {@inheritDoc} */
    public function __call($method, array $arguments) {
        // @codeCoverageIgnoreStart
        if (!is_object($this->getValue())) {
            throw new Link_Exception_Runtime(sprintf('This variable is not an object, but a(n) "%s"', getType($this->getValue())));
        }
        // @codeCoverageIgnoreEnd

        $reflection = new ReflectionClass($this->getValue());

        if ($reflection->hasMethod($method) && $reflection->getMethod($method)->isPublic()) {
            return $reflection->getMethod($method)->invokeArgs($this->getValue(), $arguments);
        }

        // @codeCoverageIgnoreStart
        if ($reflection->hasMethod('__call')) {
            return call_user_func_array(array($this->getValue(), $method), $arguments);
        }
        // @codeCoverageIgnoreEnd

        throw new Link_Exception_Runtime(sprintf('Method "%1$s::%2$s()" is not accessible or not defined.', get_class($this->getValue()), $method));
    }
}
