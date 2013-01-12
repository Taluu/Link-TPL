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

    /** {@inheritDoc} */
    public function getFilters() {
        return array();
    };
}

/*
 * EOF
 */
