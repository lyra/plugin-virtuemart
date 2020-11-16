<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for VirtueMart. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
*/
// Check to ensure this file is within the rest of the framework.
defined('_JEXEC') or die('Restricted access');

if (! class_exists('com_payzenInstallerScript')) {
    class com_payzenInstallerScript
    {
        private static $GATEWAY_CODE = 'Payzen';
        private static $CMS_IDENTIFIER = 'VirtueMart_2.x';
        private static $PLUGIN_VERSION = '1.4.0';

        static $plugin_features = array(
            'qualif' => false,
            'prodfaq' => true,
            'shatwo' => true,
            'restrictmulti' => false,

            'multi' => true
        );

        public static function getDefault($name)
        {
            if (! is_string($name)) {
                return '';
            }

            if (! isset(self::$$name)) {
                return '';
            }

            return self::$$name;
        }

        public static function getDocPattern()
        {
            $version = self::getDefault('PLUGIN_VERSION');
            $minor = substr($version, 0, strrpos($version, '.'));

            return self::getDefault('GATEWAY_CODE') . '_' . self::getDefault('CMS_IDENTIFIER') . '_v' . $minor . '*.pdf';
        }

        function install()
        {
            defined('DS') or define('DS', DIRECTORY_SEPARATOR );

            $installer = new JInstaller();

            $installer->install(realpath(dirname(__FILE__)) . DS . 'plugins' . DS . 'plg_vmpayment_payzen');

            if (self::$plugin_features['multi']) {
                $installer->install(realpath(dirname(__FILE__)) . DS . 'plugins' . DS . 'plg_vmpayment_payzenmulti');
            }

            $src = JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_payzen' . DS . 'plugins';
            $dst = JPATH_ROOT . DS . 'images' . DS . 'stories' . DS . 'virtuemart' . DS . 'payment';

            JFile::copy($src . DS . 'plg_vmpayment_payzen' . DS . 'payzen' . DS . 'assets' . DS . 'images' . DS . 'payzen.png', $dst . DS . 'payzen.png');

            if (self::$plugin_features['multi']) {
               JFile::copy($src . DS . 'plg_vmpayment_payzenmulti' . DS . 'payzenmulti' . DS . 'assets' . DS . 'images' . DS . 'payzenmulti.png', $dst . DS . 'payzenmulti.png');
            }
        }
    }
}

// Joomla 1.5.
if (function_exists('com_install')) {
    function com_install()
    {
        $installClass = new com_payzenInstallerScript();
        $installClass->install();
    }
}
