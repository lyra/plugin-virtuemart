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
defined('JPATH_BASE') or die();

class JElementPayzenRadio extends JElement
{
    /**
    * Element name.
    *
    * @access protected
    * @var string
    */
    var $_name = 'PayzenRadio';

    public function fetchTooltip($label, $description, &$xmlElement, $control_name = '', $name = '')
    {
        if (! class_exists('com_payzenInstallerScript')) {
            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'script.install.php');
        }

        $plugin_features = com_payzenInstallerScript::$plugin_features;
        if ($plugin_features['shatwo'] && $name == 'sign_algo') {
            $description = preg_replace('#<br /><b>[^<>]+</b>#', '', JText::_($description));
        }

        return parent::fetchTooltip($label, $description, $xmlElement, $control_name, $name);
    }

    function fetchElement($name, $value, &$node, $control_name)
    {
        // Base name of the HTML control.
        $ctrl = $control_name . '[' . $name . ']';

        // Construct an array of the HTML OPTION statements.
        $options = array();
        foreach ($node->children() as $option) {
            $val = $option->attributes('value');
            $text = $option->data();
            $options[] = JHtml::_('select.option', $val, JText::_($text));
        }

        if (! class_exists('com_payzenInstallerScript')) {
            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'script.install.php');
        }

        $plugin_features = com_payzenInstallerScript::$plugin_features;
        $disabled = ($plugin_features['qualif'] && $name == 'ctx_mode') ? ' disabled="disabled"' : '';

        return JHTML::_('select.radiolist', $options, $ctrl, 'class="radiobtn"' . $disabled, 'value', 'text', $value, $control_name . $name);
    }
}
