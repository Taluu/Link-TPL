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

defined('E_USER_DEPRECATED') || define('E_USER_DEPRECATED', E_USER_NOTICE);

/**
 * The templating engine itself
 *
 * @package Link
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class Link_Environment {
    protected
        // templates
        $_included = array(),

        // variables
        $_vars           = array(),
        $_currentContext = array(),

        // extensions
        $_extensionsFrozen = false,
        $_extensions       = array(),

        // filters
        $_filters     = array(),
        $_autoFilters = array(),

        $_forceReload = false,

        $_variablesClassname = 'Link_Variable',

        /** @var Link_LoaderInterface */
        $_loader = null,

        /** @var Link_ParserInterface */
        $_parser = null,

        /** @var Link_CacheInterface */
        $_cache = null;

    const
        INCLUDE_TPL = 0,
        REQUIRE_TPL = 1;

    /**
     * Initialisation.
     *
     * Available options :
     *  - parser          : Parser used to parse the templates
     *
     *  - variables_class : Classname used to handle the variables
     *
     *  - force_reload    : Whether or not the cache should be reloaded each time it
     *                      is called, the object being up to date or not. default to
     *                      `false`.
     *
     *  - extensions      : Extensions to load with the startup of the
     *                      environment
     *
     * @param Link_LoaderInterface $_loader  Loader to use
     * @param Link_CacheInterface  $_cache   Cache engine used
     * @param array                $_options Options for the templating engine
     */
    public function __construct(Link_LoaderInterface $_loader = null, Link_CacheInterface $_cache = null, array $_options = array()) {
        $this->setCache($_cache !== null ? $_cache : new Link_Cache_None);
        $this->setLoader($_loader !== null ? $_loader : new Link_Loader_String);

        // -- Options
        $defaults = array(
            'parser'              => null,
            'variables_classname' => null,
            'force_reload'        => false,
            'extensions'          => array(),
        );

        $options = array_replace_recursive($defaults, $_options);

        // BC Break <= 1.14 handling ; now, all the parameters are on the same level
        if (isset($options['dependencies'])) {
            trigger_error('You should register the dependencies on the same level '
                        . 'of other options, not in a "dependencies" key anymore', E_USER_DEPRECATED);

            foreach ($options['dependencies'] as $key => $dependency) {
                $options[$key] = $dependency;
            }
        }

        $this->setParser($options['parser'] !== null ? $options['parser'] : new Link_Parser);
        $this->setVariablesClassname($options['variables_classname'] !== null ? $options['variables_classname'] : 'Link_Variable');
        $this->_forceReload = (bool) $options['force_reload'];

        $this->registerExtension(new Link_Extension_Core);

        foreach ($options['extensions'] as $extension) {
            $this->registerExtension($extension);
        }

    }

    /**
     * Sets the global variable for all the templates
     *
     * @param array|string $vars  Var(s)' name (tpl side)
     * @param mixed        $value Var's value if $vars is not an array
     *
     * @since 1.3.0
     * @api
     */
    public function set($vars, $value = null) {
        if (!is_array($vars)) {
            $vars = array($vars => $value);
        }

        $this->_vars = array_replace_recursive($this->_vars, $vars);
    }

    /**
     * Adds a default filter to be applied on variables
     * WARNING : BEWARE of the order of declaration !
     *
     * @param string $name Filters' names
     *
     * @throws Link_Exception
     * @return array
     *
     * @since 1.9.0
     * @deprecated 1.14 Will be removed in 1.15
     */
    public function autoFilters($name) {
        if (Link_ParserInterface::FILTERS & ~$this->getParser()->getParse()) { // filters not parsed...
            return;
        }

        $this->_autoFilters[] = $name;
    }

    /**
     * Sets a variable $var, referencing $value.
     *
     * @param mixed $var    Var's name
     * @param mixed &$value Variable to be referenced by $var
     *
     * @return void
     *
     * @since 1.7.0
     * @deprecated 1.14 Will be removed in 1.15
     */
    public function bind($var, &$value) {
        $this->set($var, $value);
    }

    /**
     * Registers an extension (globals, filters, ...)
     *
     * @param Link_ExtensionInterface $extension Extension to be registered
     *
     * @since 1.14.0
     * @api
     */
    public function registerExtension(Link_ExtensionInterface $extension) {
        if (true === $this->_extensionsFrozen) {
            throw new Link_Exception('Extensions are frozen, you may not add anymore');
        }

        if (isset($this->_extensions[$extension->getName()])) {
            throw new Link_Exception(sprintf('The extension %s is already registered', $extension->getName()));
        }

        $globals = $extension->getGlobals();

        if (!empty($globals)) {
            $this->set($globals);
        }

        foreach ($extension->getFilters() as $name => $filter) {
            if (!is_callable($filter['filter'], true)) {
                trigger_error(strtr('Uncallable filter "{{ name }}" from extension "{{ extension }}", it will not be registered.',
                                     array('{{ name }}'      => $name,
                                           '{{ extension }}' => $extenion->getName())), E_USER_WARNING);
                continue;
            }

            $this->_filters[$extension->getName() . '.' . $name] = $filter;

            if (isset($filter['options']['automatic']) && true === $filter['options']['automatic']) {
                $this->_autoFilters[] = $name;
            }

            // use a fast alias only if this filter was not yet registered
            if (!isset($this->_filters[$name])) {
                $this->_filters[$name] = $filter;
            }
        }

        $this->_extensions[$extension->getName()] = $extension;
    }

    /**
     * Parse and execute the Template $tpl.
     *
     * @param mixed $_tpl     TPL to be parsed & executed
     * @param array $_context Local variables to be given to the template
     *
     * @throws Link_Exception_Parser
     * @return bool
     *
     * @api
     */
    public function parse($_tpl, array $_context = array()) {
        // freezes the extensions
        $this->_extensionsFrozen = true;

        // -- Applying the auto filters...
        $context = array_replace_recursive($this->_vars, $_context);

        foreach ($context as &$value) {
            if (!$value instanceof Link_VariableInterface) {
                $value = $this->newVariable()->setValue($value);
            }

            if ($this->getParser()->getParse() & Link_ParserInterface::FILTERS) {
                foreach ($this->_autoFilters as $filter) {
                    $value = $this->filter($filter, $value);
                }
            }
        }

        // -- Calling the cache...
        $cache = $this->getLoader()->getCacheKey($_tpl);

        if ($this->getForceReload() === true || $this->getLoader()->isFresh($_tpl, $this->getCache()->getTimestamp($cache))) {
            $this->getCache()->put($cache, $this->getParser()->parse($this->getLoader()->getSource($_tpl)));
        }

        $this->_currentContext = $context;
        $this->getCache()->exec($cache, $this, $context);

        return true;
    }

    /**
     * Parse a TPL
     * Implemention of magic method __invoke() for PHP >= 5.3
     *
     * @param string $tpl      TPL to be parsed & executed
     * @param array  $_context Local variables to be given to the template
     *
     * @see Link_Environment::parse()
     * @return bool
     */
    public function __invoke($tpl, array $_context = array()) {
        return $this->parse($tpl, $_context);
    }

    /**
     * Parse and execute a template
     *
     * Do the exact same thing as Link_Environment::parse(), but instead of just executing
     * the template, returns the final result (already executed by PHP).
     *
     * @param string $tpl      Template's name.
     * @param array  $_context Local variables to be given to the template
     *
     * @return string
     */
    public function pparse($tpl = '', array $_context = array()) {
        ob_start();
        $this->parse($tpl, $_context);

        return ob_get_clean();
    }

    /**
     * Include a template into another
     *
     * @param string  $file File to include.
     * @param bool    $once Allow the inclusion once or several times
     * @param integer $type Inclusion or requirement ?
     *
     * @return void
     *
     * @throws Link_Exception_Runtime
     * @throws Link_Exception_Loader
     *
     * @see Link_Parser::parse()
     */
    public function includeTpl($file, $once = false, $type = self::INCLUDE_TPL) {
        $data = '';
        $oldContext = $this->_currentContext;
        $vars = array();

        try {
            $qString = '';

            if (strpos($file, '?') !== false) {
                list($file, $qString) = explode('?', $file, 2);
            }

            if ($once && in_array($this->getLoader()->getCacheKey($file), $this->_included)) {
                return;
            }

            $this->_included[] = $this->getLoader()->getCacheKey($file);

            if (!empty($qString)) {
                parse_str($qString, $vars);

                // -- If MAGIC_QUOTES is ON (grmph), Removing the slashes...
                if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
                    $vars = array_map('stripslashes', $vars);
                }
            }

            $data = $this->pparse($file, array_replace_recursive($oldContext, $vars));
        } catch (Link_Exception_Loader $e) {
            $this->_currentContext = $oldContext;

            if ($type == self::REQUIRE_TPL) {
                throw new Link_Exception_Runtime(array('There was an error while trying to load the template <strong>%s</strong>.', $file));
            }

            echo $e->getMessage();
        }

        $this->_currentContext = $oldContext;
        echo $data;
    }

    /**
     * Applies filter `$filter` on an argument
     *
     * @param string $filter Filter's name
     * @param mixed  $arg    Argument's value
     *
     * @return mixed
     * @throws Link_Exception_Runtime Filter not found
     */
    public function filter($filter, $arg) {
        if (!isset($this->_filters[$filter])) {
            throw new Link_Exception_Runtime(strtr('Unknown filter {{ name }}', array('{{ name }}' => $filter)));
        }

        $args = func_get_args(); array_shift($args);

        if ($args[0] instanceof Link_VariableInterface) {
            $args[0] = $args[0]->getValue();
        }

        if (isset($this->_filters[$filter]['options']['needs_environment']) && true === $this->_filters[$filter]['options']['needs_environment']) {
            array_unshift($args, $this);
        }

        return call_user_func_array($this->_filters[$filter]['filter'], $args);
    }

    /** @return Link_ExtensionInterface */
    public function getExtension($extension) {
        if (!isset($this->_extensions[$extension])) {
            throw new Link_Exception(strtr('Unknown extension "{{ extension }}". Have you registered it ?',
                                           array('{{ extension }}' => $extension)));
        }

        return $this->_extensions[$extension];
    }

    /**#@+ Accessors */
    // @codeCoverageIgnoreStart

    /** @return Link_ParserInterface */
    public function getParser() {
        return $this->_parser;
    }

    /** Sets the TPL parser */
    public function setParser(Link_ParserInterface $_parser) {
        $this->_parser = $_parser;
    }

    /** @return Link_VariableInterface */
    public function newVariable() {
        return new $this->_variablesClassname;
    }

    /** Sets the TPL variables factory */
    public function setVariablesClassname($_classname) {
        $this->_variablesClassname = $_classname;
    }

    /** @return Link_CacheInterface */
    public function getCache() {
        return $this->_cache;
    }

    /** Sets the cache engine */
    public function setCache(Link_CacheInterface $_cache) {
        $this->_cache = $_cache;
    }

    /** @return Link_LoaderInterface */
    public function getLoader() {
        return $this->_loader;
    }

    /** Sets the loader */
    public function setLoader(Link_LoaderInterface $_loader) {
        $this->_loader = $_loader;
    }

    /** @return bool */
    public function getForceReload() {
        return $this->_forceReload;
    }

    /** @param bool $_reload */
    public function setForceReload($_reload = false) {
        $this->_forceReload = (bool)$_reload;
    }

    public function enableForceReload() {
        $this->setForceReload(true);
    }

    public function disableForceReload() {
        $this->setForceReload(false);
    }

    // @codeCoverageIgnoreEnd
    /**#@-*/
}

/*
 * Functions dependencies
 *
 * @codeCoverageIgnoreStart
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
     * When the value in `$original` is not an array, it will be replaced by the
     * value in `$array`, whatever may its value be. When the value in `$original`
     * and `$array` are both arrays, **array_replace_recursive()** will replace
     * their respective value recursively.
     *
     * @param array $original  The array in which elements are replaced.
     * @param array $array,... The arrays from which elements will be extracted.
     *
     * @link http://www.php.net/manual/en/function.array-replace-recursive.php#92224
     * @return array Joined array
     */
    function array_replace_recursive(array $original, array $array) {
        $arrays = func_get_args(); array_shift($arrays);

        foreach ($arrays as &$array) {
            foreach ($array as $key => &$value) {
                if (isset($original[$key]) && is_array($original[$key]) && is_array($value)) {
                    $original[$key] = array_replace_recursive($original[$key], $value);
                } else {
                    $original[$key] = $value;
                }
            }
        }

        return $original;
    }
}

/*
 * @codeCoverageIgnoreEnd
 *
 * EOF
 */
