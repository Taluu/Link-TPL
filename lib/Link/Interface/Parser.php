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
 * Interface to implement a new Parser for the templates.
 *
 * @package Link
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
interface Link_Interface_Parser extends Link_Interface_Parameters {
    /**
     * Transform a TPL syntax towards an optimized PHP syntax
     *
     * @param string $str TPL script to parse
     *
     * @return string
     */
    public function parse($str);
}

/*
 * EOF
 */
