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

// @codeCoverageIgnoreStart
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
        return 'core';
    }

    /** {@inheritDoc} */
    public function getGlobals() {
        return array();
    }

    /** {@inheritDoc} */
    public function getFilters() {
        return array(
            'escape' => array(
                'filter'  => '__link_core__escape',
                'options' => array()
            ),

            'safe' => array(
                'filter'  => '__link_core__safe',
                'options' => array()
            ),

            'defaults' => array(
                'filter'  => '__link_core__defaults',
                'options' => array()
            )
        );
    }
}
// @codeCoverageIgnoreEnd

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
    static $styles = null;

    // init styles substitution array
    if (null === $styles) {
        $styles = array('no quotes' => ENT_NOQUOTES,
                        'quotes'    => ENT_QUOTES,
                        'compat'    => ENT_COMPAT);

        if (PHP_VERSION_ID > 50300) {
            $styles['ignore'] = ENT_IGNORE;

            if (PHP_VERSION_ID > 50400) {
                $styles = array_merge($styles, array('substitute' => ENT_SUBSTITUTE,
                                                     'disallowed' => ENT_DISALLOWED,
                                                     'html 4.01'  => ENT_HTML401,
                                                     'html 5'     => ENT_HTML5,
                                                     'xml 1'      => ENT_XML1,
                                                     'xhtml'      => ENT_XHTML));
            }
        }
    }

    if (isset($styles[$quote_style])) {
        $quote_style = $styles[$quote_style];
    }

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
