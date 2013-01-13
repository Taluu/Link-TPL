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

if (!defined('PHP_VERSION_ID')) {
    $v = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', $v[0] * 10000 + $v[1] * 100 + $v[2]);
}

/**
 * The core extension, registering default filters
 *
 * @package Link
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class Link_Extension_Core implements Link_ExtensionInterface {
    /** {@inheritDoc} */
    public function getName() {
        return 'Core';
    }

    /** {@inheritDoc} */
    public function getGlobals() {
        return array();
    }

    /** {@inheritDoc} */
    public function getFilters() {
        return array(
            'escape' => array(
                'filter'  => '__link_core_escape',
                'options' => array()
            ),

            'safe' => array(
                'filter'  => '__link_core_safe',
                'options' => array()
            ),

            'void' => array(
                'filter'  => '__link_core_void',
                'options' => array()
            ),

            'defaults' => array(
                'filter'  => '__link_core_defaults',
                'options' => array()
            )
        );
    }
}

/**
 * Convert special characters to HTML entities
 *
 * @param string $arg           The string being converted
 * @param int    $quote_style   <b>[Optional]</b>
 *                              The optional second argument, quote_style, tells the
 *                              function what to do with single and double quote
 *                              characters. The default mode, ENT_COMPAT, is the
 *                              backwards compatible mode which only translates the
 *                              double-quote character and leaves the single-quote
 *                              untranslated. If ENT_QUOTES is set, both single and
 *                              double quotes are translated and if ENT_NOQUOTES is
 *                              set neither single nor double quotes are translated.
 * @param string $charset       <b>[Optional]</b>
 *                        <p>Defines character set used in conversion. The
 *                              default character set is ISO-8859-1.</p>
 *                        <p>For the purposes of this function, the charsets
 *                              ISO-8859-1, ISO-8859-15, UTF-8, cp866, cp1251,
 *                              cp1252, and KOI8-R are effectively equivalent, as the
 *                              characters affected by htmlspecialchars occupy the
 *                              same positions in all of these charsets.</p>
 *                        &reference.strings.charsets
 * @param bool   $double_encode <b>[Optional]</b>
 *                              When double_encode is turned off PHP will not
 *                              encode existing html entities, the default is to
 *                              convert everything.
 *
 * @return string The converted string
 */
function __link_core__escape($arg, $quote_style = ENT_COMPAT, $charset = 'ISO-8859-1', $double_encode = true) {
    return htmlspecialchars($arg, $quote_style, $charset, $double_encode);
}



/**
 * Unescape a var (meaning she is "safe")
 *
 * @param string $arg         Content to mark safe
 * @param int    $quote_style <b>[Optional]</b>
 *                         <p>Like the htmlspecialchars and htmlentities
 *                            functions you can optionally specify the quote_style
 *                            you are working with.</p>
 *                            See the description of these modes in htmlspecialchars.
 *
 * @return string unescaped var
 */
function __link_core__safe($arg, $quote_style = ENT_COMPAT) {
    return htmlspecialchars_decode($arg, $quote_style);
}

/**
 * Just do... Nothing.
 *
 * @param string $arg Variable
 *
 * @return string the variable's value
 */
function __link_core__void($arg) {
    return $arg;
}

/**
 * Sets a default value for $arg if it's empty, false, ... etc
 *
 * @param mixed $arg     Variable
 * @param mixed $default default value
 *
 * @return mixed default value if variable is empty, variables value otherwise
 */
function __link_core__defaults($arg, $default = '') {
    if (!$arg) {
        return $default;
    }

    return $arg;
}

/*
 * EOF
 */
