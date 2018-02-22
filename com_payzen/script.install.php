<?php
/**
 * PayZen V2-Payment Module version 2.1.0 for VirtueMart 3.x. Support contact : support@payzen.eu.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html  GNU General Public License (GPL v2)
 * @category  payment
 * @package   payzen
 */
defined('_JEXEC') or die('Restricted access');

class com_payzenInstallerScript
{
    static $plugin_features = array(
        'multi' => true,
        'qualif' => false
    );

    function install() {
        defined('DS') or define('DS', DIRECTORY_SEPARATOR );

        $installer = new JInstaller();

        $installer->install(realpath(dirname(__FILE__)) . DS . 'plg_vmpayment_payzen');

        if (self::$plugin_features['multi']) {
            $installer->install(realpath(dirname(__FILE__)) . DS . 'plg_vmpayment_payzenmulti');
        }

        $src = JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_payzen';
        $dst = JPATH_ROOT . DS . 'images' . DS . 'virtuemart' . DS . 'payment';

        require(JPATH_ROOT . '/administrator/components/com_virtuemart/version.php');
        if (version_compare(vmVersion::$RELEASE, '3.2.1', '<')) {
            $dst = JPATH_ROOT . DS . 'images' . DS . 'stories' . DS . 'virtuemart' . DS . 'payment';
        }

        JFile::copy($src . DS . 'plg_vmpayment_payzen' . DS . 'payzen' . DS . 'assets' . DS . 'images' . DS . 'payzen.png', $dst . DS . 'payzen.png');

        if (self::$plugin_features['multi']) {
            JFile::copy($src . DS . 'plg_vmpayment_payzenmulti' . DS . 'payzenmulti' . DS . 'assets' . DS . 'images' . DS . 'payzenmulti.png', $dst . DS . 'payzenmulti.png');
        }
    }
}

// Joomla 1.5
function com_install()
{
    $installClass = new com_payzenInstallerScript();
    $installClass->install();
}
