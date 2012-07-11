<?php
/**
 * This file is part of Link TPL.
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
 * Top Exception for the whole library
 *
 * @package Link
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class Link_Exception extends Exception
{
    /**
     * constructor
     *
     * @param array|string $message if an array, will be formatted by vsprintf
     * @param int          $code    error code
     */
    public function __construct($message = '', $code = 0)
    {
        if (is_array($message)) {
            $str = array_shift($message);
            $message = vsprintf($str, $message);
        }

        parent::__construct($message, $code);
    }
}

/*
 * EOF
 */
