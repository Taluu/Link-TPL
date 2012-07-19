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
 * Interface to implement a new Loader engine for the templates
 *
 * @package Link
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
interface Link_Interface_Loader {
    /**
     * Checks whether the object is fresher or not than the `$_time`
     *
     * @param string  $_name Object's name / value
     * @param integer $_time Last modification's timestamp
     *
     * @return boolean true if fresher, false if not
     */
    public function isFresh($_name, $_time);

    /**
     * Gets the content of the object
     *
     * @param string $_name Name of the content to be retrieved
     *
     * @return string object's content
     */
    public function getSource($_name);

    /**
     * Gets the cache key associated with the object
     *
     * @param string $_name Name designing the object
     *
     * @return string Cache key to be used, hashed by sha1
     */
    public function getCacheKey($_name);
}

/*
 * EOF
 */
