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

        $_autoFilters = array(),

        $_forceReload = false,

        /** @var Link_Interface_Loader */
        $_loader = null,

        /** @var Link_Interface_Parser */
        $_parser = null,

        /** @var Link_Interface_Cache */
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
     * @param Link_Interface_Loader $_loader  Loader to use
     * @param Link_Interface_Cache  $_cache   Cache engine used
     * @param array                 $_options Options for the templating engine
     */
    public function __construct(Link_Interface_Loader $_loader, Link_Interface_Cache $_cache = null, array $_options = array()) {
        // -- Options
        $defaults = array(
            'dependencies' => array(
                'parser' => null
            ),

            'force_reload' => false
        );

        $options = array_replace_recursive($defaults, $_options);

        // -- Dependency Injection
        $this->setParser($options['dependencies']['parser'] !== null ? $options['dependencies']['parser'] : new Link_Parser);
        $this->setCache($_cache !== null ? $_cache : new Link_Cache_None);
        $this->setLoader($_loader);

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
        if (is_array($vars)) {
            $this->_vars = array_replace_recursive($this->_vars, $vars);

            return;
        }

        $this->_vars[$vars] = $value;
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
        if (!$this->getParser()->hasParameter('filters')) { // filters not parsed...
            return;
        }

        if (!method_exists($this->getParser()->getParameter('filters'), $name)) {
            throw new Link_Exception(array('The filter %s doesn\'t exist...', $name), 404);
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
     */
    public function bind($var, &$value) {
        $this->_vars[$var] = &$value;
        $this->_references[] = $var;
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
        $vars = array_diff_key($this->_vars, array_flip($this->_references));
        $context = array_replace_recursive($vars, $_context);

        if ($this->getParser()->hasParameter('filters')) {
            foreach ($this->_autoFilters as &$filter) {
                array_walk_recursive($context, array($this->getParser()->getParameter('filters'), $filter));
            }
        }

        $context += array_diff($this->_vars, $vars);

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

            /*
            * If this is a require tag, throws an exception
            * Link_Exceptions_Runtime instead of Link_Exceptions_Loader. If not, and
            * it is still the error n°6, it prints the error message.
            *
            * Otherwise, the error will be throwed back.
            */
            if ($e->getCode() === 6) {
                if ($type == self::REQUIRE_TPL) {
                    throw new Link_Exception_Runtime(array('The template <strong>%s</strong> does not exist ; Being in a require tag, the script shall then be interrupted.', $file), 7);
                }

                echo $e->getMessage();
            } else {
                throw $e;
            }
        }

        $this->_currentContext = $oldContext;
        echo $data;
    }

    /**#@+ Accessors */

    /** @return Link_Interface_Parser */
    public function getParser() {
        return $this->_parser;
    }

    /** Sets the TPL parser */
    public function setParser(Link_Interface_Parser $_parser) {
        $this->_parser = $_parser;
    }

    /** @return Link_Interface_Cache */
    public function getCache() {
        return $this->_cache;
    }

    /** Sets the cache engine */
    public function setCache(Link_Interface_Cache $_cache) {
        $this->_cache = $_cache;
    }

    /** @return Link_Interface_Loader */
    public function getLoader() {
        return $this->_loader;
    }

    /** Sets the loader */
    public function setLoader(Link_Interface_Loader $_loader) {
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
