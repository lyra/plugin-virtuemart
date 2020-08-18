<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for VirtueMart. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
*/

defined('_JEXEC') or die('Restricted access');

class com_payzenInstallerScript
{
    static $plugin_features = array(
        'qualif' => false,
        'prodfaq' => true,
        'shatwo' => true,
        'restrictmulti' => false,

        'multi' => true
    );

    function install()
    {
        defined('DS') or define('DS', DIRECTORY_SEPARATOR);

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

// Joomla 1.5.
function com_install()
{
    $installClass = new com_payzenInstallerScript();
    $installClass->install();
}
