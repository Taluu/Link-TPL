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
 * Extensions to be registered for the template engine
 *
 * @package Link
 * @author  Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
interface Link_ExtensionInterface {
    /**
     * Gets the name of this extension
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the globals to be registered when using this extension
     *
     * @return array
     */
    public function getGlobals();

    /**
     * Gets the filters usable by this extension
     *
     * @return array
     */
    public function getFilters();
}