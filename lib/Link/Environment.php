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
        $_last = array(),
        $_included = array(),

        $_vars = array(),
        $_references = array(),
        $_currentContext = array(),

        $_extensions = array(),
        $_filters = array(),

        $_autoFilters = array(),

        $_forceReload = false,

        /** @var Link_VariableInterface */
        $_varFactory = null,

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
     *  - dependencies : Handle the dependencies (parser, ...). Each of these must
     *                   be an object.
     *
     *  - force_reload : Whether or not the cache should be reloaded each time it
     *                   is called, the object being up to date or not. default to
     *                   `false`.
     *
     * @param Link_LoaderInterface $_loader  Loader to use
     * @param Link_CacheInterface  $_cache   Cache engine used
     * @param array                $_options Options for the templating engine
     */
    public function __construct(Link_LoaderInterface $_loader = null, Link_CacheInterface $_cache = null, array $_options = array()) {
        // -- Options
        $defaults = array(
            'dependencies' => array(
                'parser'           => null,
                'variablesFactory' => null
            ),

            'force_reload' => false
        );

        $options = array_replace_recursive($defaults, $_options);

        // -- Dependency Injection
        $this->setParser($options['dependencies']['parser'] !== null ? $options['dependencies']['parser'] : new Link_Parser);
        $this->setVariablesFactory($options['dependencies']['variablesFactory'] !== null ? $options['dependencies']['variablesFactory'] : new Link_Variable);
        $this->setCache($_cache !== null ? $_cache : new Link_Cache_None);
        $this->setLoader($_loader !== null ? $_loader : new Link_Loader_String);

        // -- Options treatment
        $this->_forceReload = (bool)$options['force_reload'];
    }

    /**
     * Sets the global variable for all the templates
     *
     * @param array|string $vars  Var(s)' name (tpl side)
     * @param mixed        $value Var's value if $vars is not an array
     *
     * @since 1.3.0
     */
    public function set($vars, $value = null) {
        if (!is_array($vars)) {
            $vars = array($vars => $value);
        }

        $this->_vars = array_replace_recursive($this->_vars, $vars);
    }

    /**
     * Adds a default filter to be applied on variables (except references)
     * WARNING : BEWARE of the order of declaration !
     *
     * @param string $name Filters' names
     *
     * @throws Link_Exception
     * @return array
     *
     * @since 1.9.0
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
     *
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
     */
    public function registerExtension(Link_ExtensionInterface $extension) {
        if (in_array($extension->getName(), $this->_extensions)) {
            throw new Link_Exception(sprintf('The extension %s is already registered', $extension->getName()));
        }

        $this->set($extension->getGlobals());

        foreach ($extension->getFilters() as $name => $filter) {
            $this->_filters[$extension->getName() . '.' . $name] = $filter;

            // use a global alias only if this filter was not yet registered
            if (!isset($this->_filters[$name])) {
                $this->_filters[$name] = $filter;
            }
        }

        $this->_extensions[] = $extension->getName();
    }

    /**
     * Parse and execute the Template $tpl.
     *
     * @param mixed $_tpl     TPL to be parsed & executed
     * @param array $_context Local variables to be given to the template
     *
     * @throws Link_Exception_Parser
     * @return bool
     */
    public function parse($_tpl, array $_context = array()) {
        // -- Applying the auto filters...
        $context = array_replace_recursive($this->_vars, $_context);

        foreach ($context as &$value) {
            if (!$value instanceof Link_VariableInterface) {
                $value = $this->cloneVariablesFactory()->setValue($value);
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
     * @param string  $tpl      Template's name.
     * @param array   $_context Local variables to be given to the template
     * @param integer $ttl      Time to live for the cache 2. Not implemented yet
     *
     * @return string
     *
     * @todo Cache 2 ?
     */
    public function pparse($tpl = '', array $_context = array(), $ttl = 0) {
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
        $args = func_get_args(); array_shift($args); // remove $filter and $arg

        // stub for the next feature : extensions handlers
        if (!is_callable(array('Link_Filters', $filter))) {
            throw new Link_Exception_Runtime(sprintf('The filter "%s" is not accessible', $filter));
        }

        if ($args[0] instanceof Link_VariableInterface) {
            $args[0] = $args[0]->getValue();
        }

        return $this->cloneVariablesFactory()->setValue(call_user_func_array(array('Link_Filters', $filter), $args));
    }

    /**#@+ Accessors */

    /** @return Link_ParserInterface */
    public function getParser() {
        return $this->_parser;
    }

    /** Sets the TPL parser */
    public function setParser(Link_ParserInterface $_parser) {
        $this->_parser = $_parser;
    }

    /** @return Link_VariableInterface */
    public function cloneVariablesFactory() {
        return clone $this->_variablesFactory;
    }

    /** Sets the TPL variables factory */
    public function setVariablesFactory(Link_VariableInterface $_variablesFactory) {
        $this->_variablesFactory = $_variablesFactory;
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
 * EOF
 */
