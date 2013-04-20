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

// If php >= 5.3, use composer instead of homemade autoloader
if (PHP_VERSION_ID > 50300) {
    set_include_path(get_include_path() . ';' . __DIR__ . '/../vendor/pear-pear.bovigo.org/vfsStream');
    require __DIR__ . '/../vendor/autoload.php';

    return;
}

require dirname(__FILE__) . '/../lib/Link/Autoloader.php';
Link_Autoloader::register();

