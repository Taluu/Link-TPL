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
 * Filesystem Loader for Link TPL.
 *
 * Loads a template from a string. Note, if the sourcecode changes even a little
 * bit, the generated cache key *will be* different. So, please mind to update
 * your cache whenever you are changing the sourcecode... or to disable it.
 *
 * It's up to you. :)
 *
 * This class comes from the Twig project.
 *
 * @package Link
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @since   1.4.0
 */
class Link_Loader_String implements Link_LoaderInterface {
    /** {@inheritDoc} */
    public function getCacheKey($_name) {
        return sha1($_name);
    }

    /** {@inheritDoc} */
    public function getSource($_name) {
        return $_name;
    }

    /** {@inheritDoc} */
    public function isFresh($_name, $_time) {
        return true;
    }
}

/*
 * EOF
 */
