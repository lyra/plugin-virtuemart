<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for VirtueMart. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
*/

defined('JPATH_BASE') or die();

if (! class_exists('JFormFieldPayzenLabel')) {
    require_once(rtrim(JPATH_PLUGINS, DS) . DS . 'vmpayment' . DS . 'payzen' . DS . 'payzen' . DS . 'fields' . DS . 'payzenlabel.php');
}

/**
 * Renders a label element.
 */
class JFormFieldPayzenMultiLabel extends JFormFieldPayzenLabel
{
    var $type = 'PayzenmultiLabel';

    function getInput()
    {
        if ($this->fieldname == 'warning') {
            if (! class_exists('com_payzenInstallerScript')) {
                require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'script.install.php');
            }

            $plugin_features = com_payzenInstallerScript::$plugin_features;

            $display_warning = $plugin_features['restrictmulti'] ? '' : 'display:none;';
            return  '<p style="' . $display_warning . 'background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; font-size: 13px; margin: 0 0 20px; padding: 10px;">'
                        . JText::_($this->value) .
                    '</p>';
        }

        return parent::getInput();
    }
}
